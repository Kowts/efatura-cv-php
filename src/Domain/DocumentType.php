<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Tipos de documento fiscal electrónico definidos pelo e-Fatura.
 */
enum DocumentType: string
{
    case ElectronicInvoice = 'FTE';
    case ElectronicInvoiceReceipt = 'FRE';
    case ElectronicSalesReceipt = 'TVE';
    case ElectronicReceipt = 'RCE';
    case ElectronicCreditNote = 'NCE';
    case ElectronicDebitNote = 'NDE';
    case ElectronicTransportDocument = 'DTE';
    case ElectronicReturnNote = 'DVE';
    case ElectronicEntryNote = 'NLE';

    public function code(): int
    {
        return match ($this) {
            self::ElectronicInvoice => 1,
            self::ElectronicInvoiceReceipt => 2,
            self::ElectronicSalesReceipt => 3,
            self::ElectronicReceipt => 4,
            self::ElectronicCreditNote => 5,
            self::ElectronicDebitNote => 6,
            self::ElectronicTransportDocument => 7,
            self::ElectronicReturnNote => 8,
            self::ElectronicEntryNote => 9,
        };
    }

    public function iudCode(): string
    {
        return str_pad((string) $this->code(), 2, '0', STR_PAD_LEFT);
    }

    public function xmlElement(): string
    {
        return match ($this) {
            self::ElectronicInvoice => 'Invoice',
            self::ElectronicInvoiceReceipt => 'InvoiceReceipt',
            self::ElectronicSalesReceipt => 'SalesReceipt',
            self::ElectronicReceipt => 'Receipt',
            self::ElectronicCreditNote => 'CreditNote',
            self::ElectronicDebitNote => 'DebitNote',
            self::ElectronicTransportDocument => 'Transport',
            self::ElectronicReturnNote => 'ReturnNote',
            self::ElectronicEntryNote => 'RegistrationNote',
        };
    }

    public static function fromCode(int|string $code): self
    {
        $numericCode = (int) $code;

        foreach (self::cases() as $type) {
            if ($type->code() === $numericCode) {
                return $type;
            }
        }

        throw new ValidationException('documentType', 'O tipo de documento é inválido.', 'document.type_invalid');
    }
}
