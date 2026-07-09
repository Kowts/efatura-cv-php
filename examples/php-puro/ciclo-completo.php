<?php

declare(strict_types=1);

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$config = new EfaturaConfig(
    transmitterNif: getenv('EFATURA_TRANSMITTER_NIF') ?: '',
    transmitterLed: getenv('EFATURA_TRANSMITTER_LED') ?: '001',
    softwareCode: getenv('EFATURA_SOFTWARE_CODE') ?: '',
    softwareName: getenv('EFATURA_SOFTWARE_NAME') ?: '',
    softwareVersion: getenv('EFATURA_SOFTWARE_VERSION') ?: '1.0.0',
    middlewareBaseUrl: getenv('EFATURA_MIDDLEWARE_URL') ?: '',
    transmitterKey: getenv('EFATURA_TRANSMITTER_KEY') ?: null,
    emitter: [
        'taxId' => ['countryCode' => 'CV', 'value' => getenv('EFATURA_TRANSMITTER_NIF') ?: ''],
        'name' => getenv('EFATURA_EMITTER_NAME') ?: '',
        'address' => [
            'countryCode' => 'CV',
            'addressDetail' => getenv('EFATURA_EMITTER_ADDRESS') ?: '',
        ],
    ]
);

$pdo = new PDO(getenv('DATABASE_DSN') ?: '');
$efatura = new Efatura($config, new PdoSequenceStore($pdo));

// Normalmente estes dados vêm do domínio comercial da aplicação.
$document = require dirname(__DIR__) . '/support/invoice-data.php';
$document['type'] = DocumentType::ElectronicInvoice;
$document = $efatura->validateDocument($document);

$iud = $efatura->buildSequentialIud($document['issueDate'], $document['type']);
$xml = $efatura->buildDfeXml($iud, $document);
$xsd = $efatura->validateXml($xml);

if (!$xsd['valid']) {
    throw new RuntimeException('O XML não respeita o XSD oficial.');
}

$credentials = $efatura->loadPkcs12(
    file_get_contents(getenv('EFATURA_PKCS12_PATH') ?: ''),
    getenv('EFATURA_PKCS12_PASSWORD') ?: ''
);
$signed = $efatura->signXml($xml, $credentials['certificate'], $credentials['privateKey']);
$zip = $efatura->buildDfeZip([['iud' => $iud, 'xml' => $signed['xml']]]);

// Persista IUD, XML e ZIP antes da chamada de rede.
$result = $efatura->submitDfeZipResult($zip);

echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
