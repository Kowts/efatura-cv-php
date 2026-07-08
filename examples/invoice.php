<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;

$efatura = new Efatura(new EfaturaConfig(
    transmitterNif: '100200300',
    transmitterLed: '001',
    softwareCode: 'DEMO',
    softwareName: 'Demonstração',
    softwareVersion: '1.0.0',
    middlewareBaseUrl: 'https://middleware.example.test',
    defaultSerie: 'SER-F',
    emitter: [
        'taxId' => ['countryCode' => 'CV', 'value' => '100200300'],
        'name' => 'Emitente de Demonstração',
        'address' => ['countryCode' => 'CV', 'addressDetail' => 'Praia'],
        'contacts' => ['email' => 'facturacao@example.cv', 'telephone' => '2600000'],
    ]
));

$document = $efatura->document()
    ->type(DocumentType::ElectronicInvoice)
    ->issueDate(date('Y-m-d'))
    ->issueTime(date('H:i:s'))
    ->receiver([
        'taxId' => ['countryCode' => 'CV', 'value' => '900800700'],
        'name' => 'Cliente de Demonstração',
    ])
    ->line([
        'quantity' => ['value' => 1, 'unitCode' => 'UN'],
        'price' => 1000,
        'priceExtension' => 1000,
        'netTotal' => 1000,
        'taxes' => [[
            'taxTypeCode' => 'IVA',
            'taxPercentage' => 15,
            'taxTotal' => 150,
        ]],
        'item' => ['description' => 'Serviço', 'emitterIdentification' => 'SERV-1'],
    ])
    ->totals([
        'priceExtensionTotalAmount' => 1000,
        'netTotalAmount' => 1000,
        'taxTotalAmount' => 150,
        'payableAmount' => 1150,
    ])
    ->validate();

$iud = $efatura->buildSequentialIud($document['issueDate'], $document['type']);
$xml = $efatura->buildDfeXml($iud, $document);
$validation = $efatura->validateXml($xml);

echo $xml . PHP_EOL;
echo $validation['valid'] ? 'XML válido.' . PHP_EOL : 'XML inválido.' . PHP_EOL;
