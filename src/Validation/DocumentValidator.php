<?php

declare(strict_types=1);

namespace Kowts\Efatura\Validation;

use DateTimeImmutable;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Valida e normaliza um documento antes de este ser serializado para XML.
 *
 * A biblioteca aceita arrays para facilitar a integração com qualquer
 * framework, mas devolve sempre a mesma estrutura normalizada.
 */
final class DocumentValidator
{
    private const MONEY_TOLERANCE = 0.01;

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public function validate(array $document): array
    {
        $type = $this->normaliseType($document['type'] ?? null);
        $document['type'] = $type;
        $document['issueDate'] = $this->date($document['issueDate'] ?? null, 'issueDate');
        $document['issueTime'] = $this->timeOrNull($document['issueTime'] ?? null, 'issueTime');
        $document['emitter'] = $this->party($document['emitter'] ?? null, 'emitter', true);
        $document['receiver'] = $this->optionalParty($document['receiver'] ?? null, 'receiver');
        $document['lines'] = $this->lines($document['lines'] ?? [], $type);
        $document['references'] = $this->recordList($document['references'] ?? [], 'references');
        $document['extraFields'] = $this->recordList($document['extraFields'] ?? [], 'extraFields');

        if ($this->requiresReceiver($type) && $document['receiver'] === null) {
            throw new ValidationException(
                'receiver',
                'O adquirente é obrigatório para este tipo de documento.',
                'document.receiver_required'
            );
        }

        if ($this->requiresTotals($type)) {
            if (!isset($document['totals']) || !is_array($document['totals'])) {
                throw new ValidationException(
                    'totals',
                    'Os totais são obrigatórios para este tipo de documento.',
                    'document.totals_required'
                );
            }

            /** @var array<string, mixed> $totals */
            $totals = $document['totals'];
            $document['totals'] = $this->totals($totals, $document['lines']);
        } elseif (isset($document['totals']) && is_array($document['totals'])) {
            /** @var array<string, mixed> $totals */
            $totals = $document['totals'];
            $document['totals'] = $this->totals($totals, $document['lines']);
        } else {
            $document['totals'] = null;
        }

        $this->validateTypeSpecificFields($document, $type);
        $this->validateFieldCompatibility($document, $type);
        $this->validateRempe($document, $type);

        return $document;
    }

    /**
     * @param mixed $value
     */
    private function normaliseType(mixed $value): DocumentType
    {
        if ($value instanceof DocumentType) {
            return $value;
        }

        if (is_string($value)) {
            $type = DocumentType::tryFrom(strtoupper(trim($value)));
            if ($type !== null) {
                return $type;
            }
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return DocumentType::fromCode($value);
        }

        throw new ValidationException('type', 'O tipo de documento é inválido.', 'document.type_invalid');
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function party(mixed $value, string $field, bool $emitter = false): array
    {
        if (!is_array($value)) {
            throw new ValidationException($field, "O campo {$field} deve ser um objecto.", 'party.invalid');
        }

        if (isset($value['reference']) && trim((string) $value['reference']) !== '') {
            return ['reference' => trim((string) $value['reference'])];
        }

        if (!isset($value['taxId']) || !is_array($value['taxId'])) {
            throw new ValidationException("{$field}.taxId", 'A identificação fiscal é obrigatória.', 'party.tax_id_required');
        }

        $country = strtoupper(trim((string) ($value['taxId']['countryCode'] ?? '')));
        $taxId = trim((string) ($value['taxId']['value'] ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $country) !== 1 || $taxId === '') {
            throw new ValidationException("{$field}.taxId", 'A identificação fiscal é inválida.', 'party.tax_id_invalid');
        }
        if ($country === 'CV') {
            EfaturaConfig::assertNif($taxId, "{$field}.taxId.value");
        }

        $name = trim((string) ($value['name'] ?? ''));
        if ($name === '') {
            throw new ValidationException("{$field}.name", 'O nome da entidade é obrigatório.', 'party.name_required');
        }

        $value['taxId'] = ['countryCode' => $country, 'value' => $taxId];
        $value['name'] = $name;

        if ($emitter) {
            if (!isset($value['address']) || !is_array($value['address'])) {
                throw new ValidationException(
                    "{$field}.address",
                    'A morada do emitente é obrigatória.',
                    'emitter.address_required'
                );
            }
            if (!isset($value['contacts']) || !is_array($value['contacts'])) {
                throw new ValidationException(
                    "{$field}.contacts",
                    'Os contactos do emitente são obrigatórios.',
                    'emitter.contacts_required'
                );
            }

            $email = trim((string) ($value['contacts']['email'] ?? ''));
            $phone = trim((string) ($value['contacts']['telephone'] ?? $value['contacts']['mobilephone'] ?? ''));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new ValidationException(
                    "{$field}.contacts.email",
                    'O endereço de correio electrónico do emitente é obrigatório e deve ser válido.',
                    'emitter.email_invalid'
                );
            }
            if ($phone === '') {
                throw new ValidationException(
                    "{$field}.contacts.telephone",
                    'O telefone ou telemóvel do emitente é obrigatório.',
                    'emitter.phone_required'
                );
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private function optionalParty(mixed $value, string $field): ?array
    {
        return $value === null ? null : $this->party($value, $field);
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private function lines(mixed $value, DocumentType $type): array
    {
        if ($type === DocumentType::ElectronicReceipt) {
            return [];
        }
        if (!is_array($value) || $value === []) {
            throw new ValidationException('lines', 'O documento deve ter, pelo menos, uma linha.', 'lines.required');
        }

        $lines = [];
        $ids = [];

        foreach (array_values($value) as $index => $line) {
            if (!is_array($line)) {
                throw new ValidationException("lines.{$index}", 'A linha é inválida.', 'line.invalid');
            }
            if (!isset($line['quantity']) || !is_array($line['quantity'])) {
                throw new ValidationException(
                    "lines.{$index}.quantity",
                    'A quantidade da linha é obrigatória.',
                    'line.quantity_required'
                );
            }
            if (!is_numeric($line['quantity']['value'] ?? null) || trim((string) ($line['quantity']['unitCode'] ?? '')) === '') {
                throw new ValidationException(
                    "lines.{$index}.quantity",
                    'A quantidade e a unidade da linha são inválidas.',
                    'line.quantity_invalid'
                );
            }
            if (!isset($line['item']) || !is_array($line['item']) || trim((string) ($line['item']['description'] ?? '')) === '') {
                throw new ValidationException(
                    "lines.{$index}.item",
                    'A descrição do artigo ou serviço é obrigatória.',
                    'line.item_required'
                );
            }

            $id = isset($line['id']) ? trim((string) $line['id']) : '';
            if ($id !== '' && isset($ids[$id])) {
                throw new ValidationException("lines.{$index}.id", 'O identificador da linha deve ser único.', 'line.id_unique');
            }
            if ($id !== '') {
                $ids[$id] = true;
            }

            $taxes = $this->taxes($line['taxes'] ?? [], "lines.{$index}.taxes");
            if ($this->requiresLineTax($type) && $taxes === []) {
                throw new ValidationException(
                    "lines.{$index}.taxes",
                    'O imposto da linha é obrigatório para este documento.',
                    'line.tax_required'
                );
            }

            $line['taxes'] = $taxes;
            foreach (['price', 'priceExtension', 'netTotal'] as $amount) {
                if (array_key_exists($amount, $line) && $line[$amount] !== null && !is_numeric($line[$amount])) {
                    throw new ValidationException(
                        "lines.{$index}.{$amount}",
                        'O valor monetário da linha é inválido.',
                        'line.amount_invalid'
                    );
                }
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private function taxes(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new ValidationException($field, 'A lista de impostos é inválida.', 'taxes.invalid');
        }

        $taxes = [];
        foreach (array_values($value) as $index => $tax) {
            if (!is_array($tax)) {
                throw new ValidationException("{$field}.{$index}", 'O imposto é inválido.', 'tax.invalid');
            }
            $code = strtoupper(trim((string) ($tax['taxTypeCode'] ?? '')));
            if (!in_array($code, ['NA', 'IVA', 'IS', 'IR'], true)) {
                throw new ValidationException(
                    "{$field}.{$index}.taxTypeCode",
                    'O tipo de imposto é inválido.',
                    'tax.type_invalid'
                );
            }
            if ($code === 'NA' && trim((string) ($tax['taxExemptionReasonCode'] ?? '')) === '') {
                throw new ValidationException(
                    "{$field}.{$index}.taxExemptionReasonCode",
                    'O motivo de isenção é obrigatório quando o imposto não é aplicável.',
                    'tax.exemption_reason_required'
                );
            }
            $tax['taxTypeCode'] = $code;
            $taxes[] = $tax;
        }

        return $taxes;
    }

    /**
     * @param array<string, mixed> $totals
     * @param list<array<string, mixed>> $lines
     * @return array<string, mixed>
     */
    private function totals(array $totals, array $lines): array
    {
        foreach (['priceExtensionTotalAmount', 'netTotalAmount', 'taxTotalAmount', 'payableAmount'] as $field) {
            if (!isset($totals[$field]) || !is_numeric($totals[$field])) {
                throw new ValidationException("totals.{$field}", "O total {$field} é obrigatório.", 'totals.required');
            }
            $totals[$field] = (float) $totals[$field];
        }

        if ($lines !== []) {
            $priceExtension = 0.0;
            $net = 0.0;
            $tax = 0.0;
            foreach ($lines as $index => $line) {
                if (($line['lineTypeCode'] ?? null) === 'I') {
                    continue;
                }
                $sign = ($line['lineTypeCode'] ?? null) === 'C' ? -1 : 1;
                foreach (['priceExtension', 'netTotal'] as $required) {
                    if (!isset($line[$required]) || !is_numeric($line[$required])) {
                        throw new ValidationException(
                            "lines.{$index}.{$required}",
                            'O valor da linha é necessário para reconciliar os totais.',
                            'totals.line_amount_required'
                        );
                    }
                }
                $priceExtension += $sign * (float) $line['priceExtension'];
                $net += $sign * (float) $line['netTotal'];
                foreach ($line['taxes'] as $lineTax) {
                    if (($lineTax['taxTypeCode'] ?? null) !== 'NA' && isset($lineTax['taxTotal'])) {
                        $tax += $sign * (float) $lineTax['taxTotal'];
                    }
                }
            }

            $this->assertMoney($totals['priceExtensionTotalAmount'], $priceExtension, 'totals.priceExtensionTotalAmount');
            $this->assertMoney($totals['netTotalAmount'], $net, 'totals.netTotalAmount');
            $this->assertMoney($totals['taxTotalAmount'], $tax, 'totals.taxTotalAmount');
        }

        $withholding = (float) ($totals['withholdingTaxTotalAmount'] ?? 0);
        $rounding = (float) ($totals['payableRoundingAmount'] ?? 0);
        $expectedPayable = $totals['netTotalAmount'] + $totals['taxTotalAmount'] - $withholding + $rounding;
        $this->assertMoney($totals['payableAmount'], $expectedPayable, 'totals.payableAmount');

        return $totals;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function validateTypeSpecificFields(array $document, DocumentType $type): void
    {
        if (
            $type === DocumentType::ElectronicSalesReceipt
            && $document['receiver'] === null
            && is_array($document['totals'])
            && ((float) $document['totals']['netTotalAmount'] + (float) $document['totals']['taxTotalAmount']) >= 20_000
        ) {
            throw new ValidationException(
                'receiver',
                'O adquirente é obrigatório em talões de venda de valor igual ou superior a 20 000.',
                'document.receiver_threshold'
            );
        }

        if (
            in_array($type, [
                DocumentType::ElectronicCreditNote,
                DocumentType::ElectronicDebitNote,
                DocumentType::ElectronicReturnNote,
            ], true)
            && trim((string) ($document['issueReasonCode'] ?? '')) === ''
        ) {
            throw new ValidationException(
                'issueReasonCode',
                'O motivo de emissão é obrigatório para notas correctivas.',
                'document.issue_reason_required'
            );
        }
        if (
            in_array($type, [
            DocumentType::ElectronicCreditNote,
            DocumentType::ElectronicDebitNote,
            DocumentType::ElectronicReturnNote,
            ], true)
        ) {
            $allowedReasons = match ($type) {
                DocumentType::ElectronicCreditNote => ['2', '3', '6', '7', '8', '9', 'IN', 'DRP'],
                DocumentType::ElectronicDebitNote => ['2', '3', '4', '6', '8', '9', 'DD', 'IN'],
                DocumentType::ElectronicReturnNote => ['0', '2', '3', '6', '7', '8', '9', 'IN'],
            };
            $reason = (string) ($document['issueReasonCode'] ?? '');
            if (!in_array($reason, $allowedReasons, true)) {
                throw new ValidationException(
                    'issueReasonCode',
                    'O motivo de emissão não é permitido para este documento.',
                    'document.issue_reason_invalid'
                );
            }
            if ($reason === 'DRP' && !is_array($document['rappelPeriod'] ?? null)) {
                throw new ValidationException(
                    'rappelPeriod',
                    'O período de rappel é obrigatório quando o motivo é DRP.',
                    'document.rappel_period_required'
                );
            }
            if ($reason !== 'IN' && $document['references'] === []) {
                throw new ValidationException(
                    'references',
                    'As referências são obrigatórias para documentos correctivos.',
                    'document.references_required'
                );
            }
        }
        if (
            $type === DocumentType::ElectronicTransportDocument
            && (
                trim((string) ($document['transportDocumentTypeCode'] ?? '')) === ''
                || !is_array($document['transportServiceProviderParty'] ?? null)
                || !is_array($document['transportRoute'] ?? null)
                || !is_array($document['transportRoute']['locations'] ?? null)
                || count($document['transportRoute']['locations']) < 2
            )
        ) {
            throw new ValidationException(
                'transport',
                'O tipo, o prestador e um percurso com pelo menos dois locais são obrigatórios no documento de transporte.',
                'transport.fields_required'
            );
        }
        if ($type === DocumentType::ElectronicTransportDocument) {
            $receiverType = $document['receiverTypeCode'] ?? null;
            $transportType = (string) ($document['transportDocumentTypeCode'] ?? '');
            if ($receiverType !== null && !in_array((string) $receiverType, ['1', '2', '3'], true)) {
                throw new ValidationException('receiverTypeCode', 'O tipo de destinatário deve estar entre 1 e 3.');
            }
            if (!in_array($transportType, ['1', '2', '3', '4', '5'], true)) {
                throw new ValidationException('transportDocumentTypeCode', 'O tipo de transporte deve estar entre 1 e 5.');
            }
            if ($transportType === '5' && $document['references'] === []) {
                throw new ValidationException(
                    'references',
                    'A devolução de cliente exige uma referência documental.',
                    'transport.references_required'
                );
            }
        }
        if ($type === DocumentType::ElectronicReceipt) {
            $receiptType = (string) ($document['receiptTypeCode'] ?? '');
            if (!in_array($receiptType, ['1', '2', '3', '4'], true)) {
                throw new ValidationException(
                    'receiptTypeCode',
                    'O tipo de recibo deve estar entre 1 e 4.',
                    'receipt.type_invalid'
                );
            }
            if ($receiptType === '4' && !is_array($document['rentReceipt'] ?? null)) {
                throw new ValidationException(
                    'rentReceipt',
                    'Os dados da renda são obrigatórios para recibos do tipo 4.',
                    'receipt.rent_required'
                );
            }
        }
    }

    /**
     * Impede que campos oficiais sejam silenciosamente ignorados num tipo incompatível.
     *
     * @param array<string, mixed> $document
     */
    private function validateFieldCompatibility(array $document, DocumentType $type): void
    {
        $allowed = [
            'dueDate' => [DocumentType::ElectronicInvoice],
            'orderReferenceId' => [DocumentType::ElectronicInvoice, DocumentType::ElectronicInvoiceReceipt],
            'taxPointDate' => [DocumentType::ElectronicInvoice, DocumentType::ElectronicInvoiceReceipt],
            'paymentParty' => [
                DocumentType::ElectronicInvoiceReceipt,
                DocumentType::ElectronicReceipt,
                DocumentType::ElectronicEntryNote,
            ],
            'delivery' => [
                DocumentType::ElectronicInvoice,
                DocumentType::ElectronicInvoiceReceipt,
                DocumentType::ElectronicSalesReceipt,
            ],
            'receiptTypeCode' => [DocumentType::ElectronicReceipt],
            'rentReceipt' => [DocumentType::ElectronicReceipt],
            'rappelPeriod' => [DocumentType::ElectronicCreditNote],
            'issueReasonCode' => [
                DocumentType::ElectronicCreditNote,
                DocumentType::ElectronicDebitNote,
                DocumentType::ElectronicReturnNote,
            ],
            'issueReasonDescription' => [DocumentType::ElectronicReturnNote],
            'receiverTypeCode' => [DocumentType::ElectronicTransportDocument],
            'transportDocumentTypeCode' => [DocumentType::ElectronicTransportDocument],
            'transportServiceProviderParty' => [DocumentType::ElectronicTransportDocument],
            'transportRoute' => [DocumentType::ElectronicTransportDocument],
        ];

        foreach ($allowed as $field => $types) {
            if ($this->hasContent($document[$field] ?? null) && !in_array($type, $types, true)) {
                throw new ValidationException(
                    $field,
                    "O campo {$field} não é permitido neste tipo de documento.",
                    'document.field_not_allowed'
                );
            }
        }

        $paymentsAllowed = [
            DocumentType::ElectronicInvoice,
            DocumentType::ElectronicInvoiceReceipt,
            DocumentType::ElectronicSalesReceipt,
            DocumentType::ElectronicReceipt,
            DocumentType::ElectronicEntryNote,
        ];
        if ($this->hasContent($document['payments'] ?? null) && !in_array($type, $paymentsAllowed, true)) {
            throw new ValidationException('payments', 'Os pagamentos não são permitidos neste tipo de documento.');
        }
    }

    /**
     * @param array<string, mixed> $document
     */
    private function validateRempe(array $document, DocumentType $type): void
    {
        if (
            $type !== DocumentType::ElectronicInvoice
            || strtoupper((string) ($document['emitter']['fiscalFramework'] ?? '')) !== 'REMPE'
        ) {
            return;
        }

        foreach ($document['lines'] as $lineIndex => $line) {
            foreach ($line['taxes'] as $taxIndex => $tax) {
                if ($tax['taxTypeCode'] !== 'NA') {
                    throw new ValidationException(
                        "lines.{$lineIndex}.taxes.{$taxIndex}.taxTypeCode",
                        'As facturas de emitentes REMPE devem usar o código de imposto NA.',
                        'tax.rempe_requires_na'
                    );
                }
            }
        }
    }

    private function hasContent(mixed $value): bool
    {
        return !($value === null || $value === '' || $value === []);
    }

    private function assertMoney(float $actual, float $expected, string $field): void
    {
        if (abs(round($actual, 2) - round($expected, 2)) > self::MONEY_TOLERANCE) {
            throw new ValidationException(
                $field,
                'Os totais do documento não correspondem aos valores das linhas.',
                'totals.mismatch'
            );
        }
    }

    private function date(mixed $value, string $field): string
    {
        $text = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $text);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new ValidationException($field, 'A data deve usar o formato AAAA-MM-DD.', 'date.invalid');
        }

        return $text;
    }

    private function timeOrNull(mixed $value, string $field): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $text = trim((string) $value);
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/', $text) !== 1) {
            throw new ValidationException($field, 'A hora é inválida.', 'time.invalid');
        }

        return $text;
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private function recordList(mixed $value, string $field): array
    {
        if (!is_array($value)) {
            throw new ValidationException($field, "O campo {$field} deve ser uma lista.", 'list.invalid');
        }

        $records = [];
        foreach (array_values($value) as $index => $record) {
            if (!is_array($record)) {
                throw new ValidationException("{$field}.{$index}", 'O elemento da lista é inválido.', 'list.item_invalid');
            }
            $records[] = $record;
        }

        return $records;
    }

    private function requiresReceiver(DocumentType $type): bool
    {
        return !in_array($type, [
            DocumentType::ElectronicSalesReceipt,
            DocumentType::ElectronicTransportDocument,
            DocumentType::ElectronicReturnNote,
        ], true);
    }

    private function requiresTotals(DocumentType $type): bool
    {
        return !in_array($type, [
            DocumentType::ElectronicReceipt,
            DocumentType::ElectronicTransportDocument,
        ], true);
    }

    private function requiresLineTax(DocumentType $type): bool
    {
        return !in_array($type, [
            DocumentType::ElectronicCreditNote,
            DocumentType::ElectronicTransportDocument,
            DocumentType::ElectronicReturnNote,
        ], true);
    }
}
