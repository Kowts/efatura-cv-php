<?php

declare(strict_types=1);

namespace Kowts\Efatura\Xml;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Validation\DocumentValidator;

/**
 * Gera XML DFE v11 compacto, seguindo a ordem dos elementos dos XSD oficiais.
 */
final class DfeXmlBuilder
{
    public const XML_NAMESPACE = 'urn:cv:efatura:xsd:v1.0';
    public const XML_VERSION = '1.0';

    public function __construct(
        private readonly EfaturaConfig $config,
        private readonly DocumentValidator $validator
    ) {
    }

    /**
     * @param array<string, mixed> $document
     */
    public function build(string $iud, array $document, EmissionMode $mode = EmissionMode::Online): string
    {
        if (!Iud::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD é inválido.', 'xml.iud_invalid');
        }

        $data = $this->validator->validate($document);
        /** @var DocumentType $type */
        $type = $data['type'];
        $documentNumber = Iud::parse($iud)['documentNumber'];
        $this->assertContingency($data, $mode);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Dfe xmlns="' . self::XML_NAMESPACE . '" Version="' . self::XML_VERSION
            . '" Id="' . Xml::escape($iud) . '" DocumentTypeCode="' . $type->code() . '">'
            . Xml::element('IsSpecimen', ($data['isSpecimen'] ?? null) === true ? true : null)
            . '<' . $type->xmlElement() . '>'
            . $this->documentContent($data, $type, $documentNumber, $mode)
            . '</' . $type->xmlElement() . '>'
            . $this->transmission($data, $mode)
            . Xml::element('RepositoryCode', $this->config->repositoryCode())
            . '</Dfe>';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function documentContent(
        array $data,
        DocumentType $type,
        string $documentNumber,
        EmissionMode $mode
    ): string {
        $header = $this->header($data, $documentNumber, $mode);
        $emitter = $this->party('EmitterParty', $data['emitter']);
        $receiver = is_array($data['receiver']) ? $this->party('ReceiverParty', $data['receiver']) : '';
        $linesTotals = $this->lines($data['lines'])
            . (is_array($data['totals']) ? $this->totals($data['totals']) : '');
        $references = $this->references($data['references']);
        $footer = Xml::element('Note', $data['note'] ?? null) . $this->extraFields($data['extraFields']);

        return match ($type) {
            DocumentType::ElectronicInvoice => $header
                . Xml::element('DueDate', $data['dueDate'] ?? null)
                . $this->orderReference($data['orderReferenceId'] ?? null)
                . Xml::element('TaxPointDate', $data['taxPointDate'] ?? null)
                . $emitter . $receiver . $linesTotals . $references
                . $this->invoicePayments($data['payments'] ?? null)
                . $this->delivery($data['delivery'] ?? null) . $footer,
            DocumentType::ElectronicInvoiceReceipt => $header
                . $this->orderReference($data['orderReferenceId'] ?? null)
                . Xml::element('TaxPointDate', $data['taxPointDate'] ?? null)
                . $emitter . $receiver . $this->optionalParty('PaymentParty', $data['paymentParty'] ?? null)
                . $linesTotals . $references . $this->paymentPayments($data['payments'] ?? null)
                . $this->delivery($data['delivery'] ?? null) . $footer,
            DocumentType::ElectronicSalesReceipt => $header . $emitter . $receiver
                . $linesTotals . $this->paymentPayments($data['payments'] ?? null)
                . $this->delivery($data['delivery'] ?? null) . $footer,
            DocumentType::ElectronicReceipt => $header . $emitter . $receiver
                . $this->optionalParty('PaymentParty', $data['paymentParty'] ?? null)
                . Xml::element('ReceiptTypeCode', Xml::required($data['receiptTypeCode'] ?? null, 'receiptTypeCode'))
                . $this->rentReceipt($data['rentReceipt'] ?? null)
                . $references . $this->paymentPayments($data['payments'] ?? null) . $footer,
            DocumentType::ElectronicCreditNote => $header
                . Xml::element('IssueReasonCode', Xml::required($data['issueReasonCode'] ?? null, 'issueReasonCode'))
                . $this->datePeriod('RappelPeriod', $data['rappelPeriod'] ?? null)
                . $emitter . $receiver . $linesTotals . $references . $footer,
            DocumentType::ElectronicDebitNote => $header
                . Xml::element('IssueReasonCode', Xml::required($data['issueReasonCode'] ?? null, 'issueReasonCode'))
                . $emitter . $receiver . $linesTotals . $references . $footer,
            DocumentType::ElectronicReturnNote => $header
                . Xml::element('IssueReasonCode', Xml::required($data['issueReasonCode'] ?? null, 'issueReasonCode'))
                . Xml::element('IssueReasonDescription', $data['issueReasonDescription'] ?? null)
                . $emitter . $receiver . $linesTotals . $references . $footer,
            DocumentType::ElectronicEntryNote => $header . $emitter . $receiver
                . $this->optionalParty('PaymentParty', $data['paymentParty'] ?? null)
                . $linesTotals . $references . $this->paymentPayments($data['payments'] ?? null) . $footer,
            DocumentType::ElectronicTransportDocument => $header
                . Xml::element('ReceiverTypeCode', $data['receiverTypeCode'] ?? null)
                . Xml::element(
                    'TransportDocumentTypeCode',
                    Xml::required($data['transportDocumentTypeCode'] ?? null, 'transportDocumentTypeCode')
                )
                . $emitter . $receiver
                . $this->optionalParty('TransportServiceProviderParty', $data['transportServiceProviderParty'] ?? null)
                . $this->lines($data['lines'])
                . $this->transportRoute($data['transportRoute'] ?? null)
                . $references . $footer,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function header(array $data, string $documentNumber, EmissionMode $mode): string
    {
        if ($mode !== EmissionMode::Off && ($data['issueTime'] ?? null) === null) {
            throw new ValidationException('issueTime', 'A hora de emissão é obrigatória.', 'document.issue_time_required');
        }

        return Xml::element('IsIsolatedAct', $data['isIsolatedAct'] ?? null)
            . $this->selfBilling($data['selfBilling'] ?? null)
            . Xml::element('LedCode', $this->config->transmitterLed)
            . Xml::element('Serie', Xml::required($data['serie'] ?? $this->config->defaultSerie, 'serie'))
            . Xml::element('DocumentNumber', $documentNumber)
            . Xml::element('InnerDocumentNumber', $data['innerDocumentNumber'] ?? null)
            . Xml::element('IssueDate', $data['issueDate'])
            . Xml::element('IssueTime', $data['issueTime']);
    }

    /**
     * @param array<string, mixed> $party
     */
    private function party(string $name, array $party): string
    {
        if (isset($party['reference'])) {
            return "<{$name}>" . Xml::element('Reference', $party['reference']) . "</{$name}>";
        }

        /** @var array<string, mixed> $taxId */
        $taxId = $party['taxId'];
        return "<{$name}>"
            . '<TaxId CountryCode="' . Xml::escape((string) $taxId['countryCode']) . '">'
            . Xml::escape((string) $taxId['value']) . '</TaxId>'
            . Xml::element('Name', $party['name'])
            . $this->address($party['address'] ?? null)
            . $this->contacts($party['contacts'] ?? null)
            . "</{$name}>";
    }

    private function optionalParty(string $name, mixed $party): string
    {
        return is_array($party) ? $this->party($name, $party) : '';
    }

    private function address(mixed $address): string
    {
        if (!is_array($address)) {
            return '';
        }
        $country = strtoupper((string) ($address['countryCode'] ?? 'CV'));
        $fields = [
            'State', 'City', 'Region', 'Street', 'StreetDetail', 'BuildingName',
            'BuildingNumber', 'BuildingFloor', 'PostalCode', 'AddressDetail', 'AddressCode',
        ];
        $content = '';
        foreach ($fields as $field) {
            $content .= Xml::element($field, $address[lcfirst($field)] ?? null);
        }

        return '<Address CountryCode="' . Xml::escape($country) . '">' . $content . '</Address>';
    }

    private function contacts(mixed $contacts): string
    {
        if (!is_array($contacts)) {
            return '';
        }
        return '<Contacts>'
            . Xml::element('Telephone', $contacts['telephone'] ?? null)
            . Xml::element('Mobilephone', $contacts['mobilephone'] ?? null)
            . Xml::element('Telefax', $contacts['telefax'] ?? null)
            . Xml::element('Email', $contacts['email'] ?? null)
            . Xml::element('Website', $contacts['website'] ?? null)
            . '</Contacts>';
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    private function lines(array $lines): string
    {
        $content = '';
        foreach ($lines as $line) {
            $attributes = isset($line['lineTypeCode'])
                ? ' LineTypeCode="' . Xml::escape((string) $line['lineTypeCode']) . '"' : '';
            $content .= '<Line' . $attributes . '>'
                . Xml::element('Id', $line['id'] ?? null)
                . Xml::element('LineReferenceId', $line['lineReferenceId'] ?? null)
                . Xml::element('OrderLineReference', $line['orderLineReference'] ?? null)
                . $this->quantity('Quantity', $line['quantity'])
                . Xml::element('Price', $line['price'] ?? null)
                . Xml::element('PriceExtension', $line['priceExtension'] ?? null)
                . $this->discount($line['discount'] ?? null)
                . Xml::element('NetTotal', $line['netTotal'] ?? null);
            foreach ($line['taxes'] as $tax) {
                $content .= $this->tax($tax);
            }
            $content .= $this->item($line['item']) . '</Line>';
        }

        return '<Lines>' . $content . '</Lines>';
    }

    /**
     * @param array<string, mixed> $quantity
     */
    private function quantity(string $name, array $quantity): string
    {
        $standard = array_key_exists('isStandardUnitCode', $quantity) && $quantity['isStandardUnitCode'] !== null
            ? ' IsStandardUnitCode="' . Xml::escape((bool) $quantity['isStandardUnitCode']) . '"' : '';
        return "<{$name} UnitCode=\"" . Xml::escape((string) $quantity['unitCode']) . "\"{$standard}>"
            . Xml::escape($quantity['value']) . "</{$name}>";
    }

    /**
     * @param array<string, mixed> $tax
     */
    private function tax(array $tax): string
    {
        return '<Tax TaxTypeCode="' . Xml::escape((string) $tax['taxTypeCode']) . '">'
            . Xml::element('StampTaxCode', $tax['stampTaxCode'] ?? null)
            . Xml::element('TaxPercentage', $tax['taxPercentage'] ?? null)
            . Xml::element('TaxAmount', $tax['taxAmount'] ?? null)
            . Xml::element('TaxExemptionReasonCode', $tax['taxExemptionReasonCode'] ?? null)
            . Xml::element('TaxTotal', $tax['taxTotal'] ?? null)
            . '</Tax>';
    }

    private function discount(mixed $discount): string
    {
        if (!is_array($discount)) {
            return '';
        }
        $type = isset($discount['valueType'])
            ? ' ValueType="' . Xml::escape((string) $discount['valueType']) . '"' : '';
        return '<Discount' . $type . '>' . Xml::escape($discount['value'] ?? '') . '</Discount>';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function item(array $item): string
    {
        $standardId = '';
        if (isset($item['standardIdentification']) && is_array($item['standardIdentification'])) {
            $standard = $item['standardIdentification'];
            $standardId = '<StandardIdentification>'
                . Xml::element((string) $standard['type'], $standard['value'])
                . '</StandardIdentification>';
        }
        $properties = '';
        foreach (($item['extraProperties'] ?? []) as $property) {
            if (is_array($property)) {
                $properties .= '<Property Name="' . Xml::escape((string) $property['name']) . '">'
                    . Xml::escape($property['value']) . '</Property>';
            }
        }

        return '<Item>'
            . Xml::element('Description', $item['description'])
            . (isset($item['packQuantity']) && is_array($item['packQuantity'])
                ? $this->quantity('PackQuantity', $item['packQuantity']) : '')
            . Xml::element('Name', $item['name'] ?? null)
            . Xml::element('BrandName', $item['brandName'] ?? null)
            . Xml::element('ModelName', $item['modelName'] ?? null)
            . Xml::element('EmitterIdentification', $item['emitterIdentification'] ?? null)
            . $standardId
            . Xml::element('HazardousRiskIndicator', $item['hazardousRiskIndicator'] ?? null)
            . ($properties === '' ? '' : '<ExtraProperties>' . $properties . '</ExtraProperties>')
            . '</Item>';
    }

    /**
     * @param array<string, mixed> $totals
     */
    private function totals(array $totals): string
    {
        $alternatives = '';
        foreach (($totals['payableAlternativeAmounts'] ?? []) as $amount) {
            if (is_array($amount)) {
                $alternatives .= '<PayableAlternativeAmount CurrencyCode="'
                    . Xml::escape((string) $amount['currencyCode']) . '" ExchangeRate="'
                    . Xml::escape($amount['exchangeRate']) . '">'
                    . Xml::escape($amount['value']) . '</PayableAlternativeAmount>';
            }
        }

        return '<Totals>'
            . Xml::element('PriceExtensionTotalAmount', $totals['priceExtensionTotalAmount'])
            . Xml::element('ChargeTotalAmount', $totals['chargeTotalAmount'] ?? null)
            . Xml::element('DiscountTotalAmount', $totals['discountTotalAmount'] ?? null)
            . Xml::element('NetTotalAmount', $totals['netTotalAmount'])
            . $this->discount($totals['discount'] ?? null)
            . Xml::element('TaxTotalAmount', $totals['taxTotalAmount'])
            . Xml::element('WithholdingTaxTotalAmount', $totals['withholdingTaxTotalAmount'] ?? null)
            . Xml::element('PayableRoundingAmount', $totals['payableRoundingAmount'] ?? null)
            . Xml::element('PayableAmount', $totals['payableAmount'])
            . $alternatives . '</Totals>';
    }

    /**
     * @param list<array<string, mixed>> $references
     */
    private function references(array $references): string
    {
        if ($references === []) {
            return '';
        }
        $content = '';
        foreach ($references as $reference) {
            $fiscalDocument = '';
            if (isset($reference['fiscalDocument'])) {
                $document = is_array($reference['fiscalDocument'])
                    ? $reference['fiscalDocument'] : ['value' => $reference['fiscalDocument']];
                $old = array_key_exists('isOldDocument', $document)
                    ? ' IsOldDocument="' . Xml::escape((bool) $document['isOldDocument']) . '"' : '';
                $fiscalDocument = '<FiscalDocument' . $old . '>'
                    . Xml::escape($document['value']) . '</FiscalDocument>';
            }
            $content .= '<Reference>' . $fiscalDocument
                . Xml::element('InnerDocumentNumber', $reference['innerDocumentNumber'] ?? null)
                . Xml::element('PaymentAmount', $reference['paymentAmount'] ?? null)
                . (isset($reference['tax']) && is_array($reference['tax']) ? $this->tax($reference['tax']) : '')
                . '</Reference>';
        }

        return '<References>' . $content . '</References>';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function transmission(array $data, EmissionMode $mode): string
    {
        $contingency = '';
        if ($mode->requiresContingency()) {
            /** @var array<string, mixed> $value */
            $value = $data['contingency'];
            $contingency = '<Contingency>'
                . Xml::element('LedCode', $value['ledCode'] ?? null)
                . Xml::element('IUC', $value['iuc'] ?? null)
                . Xml::element('IssueDate', $value['issueDate'])
                . Xml::element('IssueTime', $value['issueTime'] ?? null)
                . Xml::element('ReasonTypeCode', $value['reasonTypeCode'])
                . Xml::element('ReasonDescription', $value['reasonDescription'] ?? null)
                . '</Contingency>';
        }

        return '<Transmission>' . Xml::element('IssueMode', $mode->code())
            . '<TransmitterTaxId CountryCode="CV">' . Xml::escape($this->config->transmitterNif)
            . '</TransmitterTaxId><Software>'
            . Xml::element('Code', $this->config->softwareCode)
            . Xml::element('Name', $this->config->softwareName)
            . Xml::element('Version', $this->config->softwareVersion)
            . '</Software>' . $contingency . '</Transmission>';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertContingency(array $data, EmissionMode $mode): void
    {
        if ($mode === EmissionMode::Online && is_array($data['contingency'] ?? null)) {
            throw new ValidationException(
                'contingency',
                'Os dados de contingência não são permitidos na emissão online.',
                'contingency.not_allowed_online'
            );
        }
        if ($mode->requiresContingency() && !is_array($data['contingency'] ?? null)) {
            throw new ValidationException(
                'contingency',
                'Os dados de contingência são obrigatórios neste modo de emissão.',
                'contingency.required'
            );
        }
        if (!$mode->requiresContingency()) {
            return;
        }

        $contingency = $data['contingency'];
        if (trim((string) ($contingency['ledCode'] ?? '')) === '') {
            throw new ValidationException(
                'contingency.ledCode',
                'O LED é obrigatório na contingência.',
                'contingency.led_code_required'
            );
        }
        $reason = (string) ($contingency['reasonTypeCode'] ?? '');
        $allowedReasons = $mode === EmissionMode::Offline ? ['0', '1', '4', '5'] : ['0', '2', '3'];
        if (!in_array($reason, $allowedReasons, true)) {
            throw new ValidationException(
                'contingency.reasonTypeCode',
                'O motivo de contingência não é permitido neste modo de emissão.',
                'contingency.reason_type_invalid'
            );
        }
        if ($mode === EmissionMode::Offline && trim((string) ($contingency['issueTime'] ?? '')) === '') {
            throw new ValidationException(
                'contingency.issueTime',
                'A hora da contingência é obrigatória no modo Offline.',
                'contingency.issue_time_required'
            );
        }
        if ($mode === EmissionMode::Off && trim((string) ($data['contingency']['iuc'] ?? '')) === '') {
            throw new ValidationException(
                'contingency.iuc',
                'O IUC é obrigatório no modo Off.',
                'contingency.iuc_required'
            );
        }
        if ($reason === '0' && trim((string) ($contingency['reasonDescription'] ?? '')) === '') {
            throw new ValidationException(
                'contingency.reasonDescription',
                'A descrição é obrigatória quando o motivo da contingência é 0.',
                'contingency.reason_description_required'
            );
        }
    }

    private function selfBilling(mixed $value): string
    {
        return is_array($value)
            ? '<SelfBilling>' . Xml::element('AuthorizationId', $value['authorizationId'] ?? null)
                . Xml::element('AuthorizationCode', $value['authorizationCode'] ?? null) . '</SelfBilling>'
            : '';
    }

    private function orderReference(mixed $value): string
    {
        return $value === null || $value === '' ? '' : '<OrderReference>' . Xml::element('Id', $value) . '</OrderReference>';
    }

    private function invoicePayments(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }
        $accounts = '';
        foreach (($value['payeeFinancialAccounts'] ?? []) as $account) {
            $accounts .= $this->financialAccount($account);
        }
        $terms = isset($value['paymentTermsNote'])
            ? '<PaymentTerms>' . Xml::element('Note', $value['paymentTermsNote']) . '</PaymentTerms>' : '';
        return '<Payments>' . Xml::element('PaymentDueDate', $value['paymentDueDate'] ?? null)
            . $terms . $accounts . '</Payments>';
    }

    private function paymentPayments(mixed $value): string
    {
        if (!is_array($value) || !is_array($value['payments'] ?? null) || $value['payments'] === []) {
            return '';
        }
        $content = '';
        foreach ($value['payments'] as $payment) {
            if (is_array($payment)) {
                $content .= '<Payment>'
                    . Xml::element('PaymentMeansCode', $payment['paymentMeansCode'] ?? null)
                    . Xml::element('PaymentReference', $payment['paymentReference'] ?? null)
                    . Xml::element('PaymentDate', $payment['paymentDate'] ?? null)
                    . Xml::element('PaymentAmount', $payment['paymentAmount'] ?? null)
                    . $this->financialAccount($payment['payeeFinancialAccount'] ?? null)
                    . '</Payment>';
            }
        }
        return '<Payments>' . $content . '</Payments>';
    }

    private function financialAccount(mixed $account): string
    {
        if (!is_array($account)) {
            return '';
        }
        return '<PayeeFinancialAccount>'
            . Xml::element('AccountNumber', $account['accountNumber'] ?? null)
            . Xml::element('NIB', isset($account['accountNumber']) ? null : ($account['nib'] ?? null))
            . Xml::element('Name', $account['name'] ?? null)
            . '</PayeeFinancialAccount>';
    }

    private function delivery(mixed $value): string
    {
        return is_array($value)
            ? '<Delivery>' . Xml::element('DeliveryDate', $value['deliveryDate'] ?? null)
                . $this->address($value['address'] ?? null) . '</Delivery>' : '';
    }

    private function rentReceipt(mixed $value): string
    {
        return is_array($value)
            ? '<RentReceipt>' . Xml::element('AssetId', $value['assetId'] ?? null)
                . Xml::element('RentPurposeTypeCode', $value['rentPurposeTypeCode'] ?? null)
                . Xml::element('ContractTypeCode', $value['contractTypeCode'] ?? null)
                . Xml::element('RentTypeCode', $value['rentTypeCode'] ?? null)
                . Xml::element('ReferencePeriod', $value['referencePeriod'] ?? null)
                . $this->address($value['address'] ?? null) . '</RentReceipt>' : '';
    }

    private function datePeriod(string $name, mixed $value): string
    {
        return is_array($value)
            ? "<{$name}>" . Xml::element('StartDate', $value['startDate'] ?? null)
                . Xml::element('EndDate', $value['endDate'] ?? null) . "</{$name}>" : '';
    }

    private function transportRoute(mixed $value): string
    {
        if (!is_array($value) || !is_array($value['locations'] ?? null)) {
            return '';
        }
        $content = '';
        foreach ($value['locations'] as $location) {
            if (!is_array($location)) {
                continue;
            }
            $duration = is_array($location['duration'] ?? null) ? $location['duration'] : [];
            $content .= '<TransportLocation>' . $this->address($location['address'] ?? null)
                . '<Duration>' . Xml::element('StartDate', $duration['startDate'] ?? null)
                . Xml::element('StartTime', $duration['startTime'] ?? null)
                . Xml::element('EndDate', $duration['endDate'] ?? null)
                . Xml::element('EndTime', $duration['endTime'] ?? null)
                . '</Duration>'
                . Xml::element('TransportModeCode', $location['transportModeCode'] ?? null)
                . Xml::element('VehicleRegistrationCode', $location['vehicleRegistrationCode'] ?? null)
                . '</TransportLocation>';
        }
        return '<TransportRoute>' . $content . '</TransportRoute>';
    }

    /**
     * @param list<array<string, mixed>> $fields
     */
    private function extraFields(array $fields): string
    {
        if ($fields === []) {
            return '';
        }
        $content = '';
        foreach ($fields as $field) {
            $content .= $this->extraField($field);
        }
        return '<ExtraFields>' . $content . '</ExtraFields>';
    }

    /**
     * @param array<string, mixed> $field
     */
    private function extraField(array $field): string
    {
        $name = (string) ($field['name'] ?? '');
        Xml::assertName($name);
        $attributes = '';
        foreach (($field['attributes'] ?? []) as $attribute => $value) {
            Xml::assertName((string) $attribute);
            $attributes .= ' ' . $attribute . '="' . Xml::escape($value) . '"';
        }
        $content = '';
        if (is_array($field['children'] ?? null)) {
            foreach ($field['children'] as $child) {
                if (is_array($child)) {
                    $content .= $this->extraField($child);
                }
            }
        } elseif (isset($field['value'])) {
            $content = Xml::escape($field['value']);
        }

        return "<{$name}{$attributes}>{$content}</{$name}>";
    }
}
