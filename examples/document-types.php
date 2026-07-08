<?php

declare(strict_types=1);

use Kowts\Efatura\Domain\DocumentType;

/**
 * Alterações mínimas sobre uma factura base para cada tipo documental.
 *
 * Consulte invoice.php para a configuração e os campos comuns.
 */
return [
    DocumentType::ElectronicInvoice->value => [],
    DocumentType::ElectronicInvoiceReceipt->value => [],
    DocumentType::ElectronicSalesReceipt->value => ['receiver' => null],
    DocumentType::ElectronicReceipt->value => ['receiptTypeCode' => '1', 'lines' => [], 'totals' => null],
    DocumentType::ElectronicCreditNote->value => ['issueReasonCode' => '2', 'references' => [['fiscalDocument' => '...']]],
    DocumentType::ElectronicDebitNote->value => ['issueReasonCode' => '2', 'references' => [['fiscalDocument' => '...']]],
    DocumentType::ElectronicTransportDocument->value => [
        'transportDocumentTypeCode' => '1',
        'transportServiceProviderParty' => [],
        'transportRoute' => ['locations' => [[], []]],
        'totals' => null,
    ],
    DocumentType::ElectronicReturnNote->value => ['issueReasonCode' => '0', 'references' => [['fiscalDocument' => '...']]],
    DocumentType::ElectronicEntryNote->value => [],
];
