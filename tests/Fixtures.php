<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;

/**
 * @return array<string, mixed>
 */
function invoiceFixture(array $overrides = []): array
{
    $invoice = [
        'type' => DocumentType::ElectronicInvoice,
        'issueDate' => '2026-02-08',
        'issueTime' => '10:30:00',
        'serie' => 'SER-F',
        'emitter' => [
            'taxId' => ['countryCode' => 'CV', 'value' => '100200300'],
            'name' => 'Emitente',
            'address' => ['countryCode' => 'CV', 'addressDetail' => 'Praia'],
            'contacts' => ['email' => 'emitente@example.cv', 'telephone' => '2600000'],
        ],
        'receiver' => [
            'taxId' => ['countryCode' => 'CV', 'value' => '900800700'],
            'name' => 'Adquirente',
            'address' => ['countryCode' => 'CV', 'addressDetail' => 'Mindelo'],
        ],
        'lines' => [[
            'lineTypeCode' => 'N',
            'quantity' => ['value' => 1, 'unitCode' => 'UN'],
            'price' => 1000,
            'priceExtension' => 1000,
            'netTotal' => 1000,
            'taxes' => [[
                'taxTypeCode' => 'IVA',
                'taxPercentage' => 15,
                'taxTotal' => 150,
            ]],
            'item' => [
                'description' => 'Serviço de consultoria',
                'emitterIdentification' => 'SERV-1',
            ],
        ]],
        'totals' => [
            'priceExtensionTotalAmount' => 1000,
            'chargeTotalAmount' => 0,
            'discountTotalAmount' => 0,
            'netTotalAmount' => 1000,
            'taxTotalAmount' => 150,
            'payableAmount' => 1150,
        ],
    ];

    return array_replace_recursive($invoice, $overrides);
}
