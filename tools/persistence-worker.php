<?php

declare(strict_types=1);

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;

require dirname(__DIR__) . '/vendor/autoload.php';

$mode = $argv[1] ?? '';
$path = $argv[2] ?? '';
$iterations = (int) ($argv[3] ?? 1);
$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 10);

if ($mode === 'sequence') {
    $store = new PdoSequenceStore($pdo);
    $values = [];
    for ($index = 0; $index < $iterations; ++$index) {
        $values[] = $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice);
    }
    echo json_encode($values, JSON_THROW_ON_ERROR);
    exit(0);
}

if ($mode === 'submission') {
    $registry = new PdoSubmissionRegistry($pdo);
    echo $registry->claim(hash('sha256', 'pacote-concorrente')) ? '1' : '0';
    exit(0);
}

fwrite(STDERR, 'Modo de teste inválido.');
exit(2);
