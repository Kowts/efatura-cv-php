<?php

declare(strict_types=1);

return [
    'issueDate' => date('Y-m-d'),
    'issueTime' => date('H:i:s'),
    'receiver' => [
        'taxId' => ['countryCode' => 'CV', 'value' => '900800700'],
        'name' => 'Cliente de Demonstração',
    ],
    'lines' => [[
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
            'description' => 'Serviço de demonstração',
            'emitterIdentification' => 'SERV-1',
        ],
    ]],
    'totals' => [
        'priceExtensionTotalAmount' => 1000,
        'netTotalAmount' => 1000,
        'taxTotalAmount' => 150,
        'payableAmount' => 1150,
    ],
];
