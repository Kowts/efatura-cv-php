<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use Kowts\Efatura\Tests\Support\RecordingPdo;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSequenceStoreTest extends TestCase
{
    public function testPersisteEReiniciaSequencia(): void
    {
        $store = new PdoSequenceStore(new PDO('sqlite::memory:'));
        $store->createTable();

        self::assertSame(1, $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        self::assertSame(2, $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        self::assertSame(2, $store->current('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        $store->reset('100200300', 2026, '123', DocumentType::ElectronicInvoice);
        self::assertNull($store->current('100200300', 2026, '123', DocumentType::ElectronicInvoice));
    }

    public function testDuasLigacoesPartilhamSequenciaAtomica(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-sequence-');
        self::assertNotFalse($path);

        try {
            $first = new PdoSequenceStore(new PDO('sqlite:' . $path));
            $second = new PdoSequenceStore(new PDO('sqlite:' . $path));
            $first->createTable();

            $numbers = [];
            for ($index = 0; $index < 100; ++$index) {
                $store = $index % 2 === 0 ? $first : $second;
                $numbers[] = $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice);
            }

            self::assertSame(range(1, 100), $numbers);
            self::assertSame(
                100,
                $second->current('100200300', 2026, '123', DocumentType::ElectronicInvoice)
            );
        } finally {
            @unlink($path);
        }
    }

    public function testProcessosConcorrentesNaoRepetemNumeros(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-concurrent-sequence-');
        self::assertNotFalse($path);

        try {
            $store = new PdoSequenceStore(new PDO('sqlite:' . $path));
            $store->createTable();
            $outputs = $this->runWorkers('sequence', $path, 4, 25);
            $numbers = [];
            foreach ($outputs as $output) {
                $values = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
                self::assertIsArray($values);
                $numbers = array_merge($numbers, $values);
            }
            sort($numbers);

            self::assertSame(range(1, 100), $numbers);
        } finally {
            @unlink($path);
        }
    }

    public function testSqlServerCriaTabelaComSintaxeCompativel(): void
    {
        $pdo = new RecordingPdo('sqlsrv');
        $store = new PdoSequenceStore($pdo);

        $store->createTable();

        self::assertStringContainsString("IF OBJECT_ID(N'efatura_sequences', N'U') IS NULL", $pdo->executedSql[0]);
        self::assertStringContainsString('CREATE TABLE efatura_sequences', $pdo->executedSql[0]);
    }

    public function testSqlServerReservaProximoNumeroComLockTransaccional(): void
    {
        $pdo = new RecordingPdo('sqlsrv');
        $pdo->fetchColumns = [5];
        $store = new PdoSequenceStore($pdo);

        $next = $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice);

        self::assertSame(6, $next);
        self::assertSame(1, $pdo->beginTransactionCalls);
        self::assertSame(1, $pdo->commitCalls);
        self::assertSame(0, $pdo->rollBackCalls);
        self::assertStringContainsString(
            'WITH (UPDLOCK, HOLDLOCK)',
            $pdo->preparedSql[0]
        );
        self::assertStringStartsWith('UPDATE efatura_sequences SET current_value', $pdo->preparedSql[1]);
        self::assertSame(6, $pdo->statementParameters[1]['value']);
    }

    public function testSqlServerInserePrimeiroNumeroDaSequencia(): void
    {
        $pdo = new RecordingPdo('sqlsrv');
        $store = new PdoSequenceStore($pdo);

        $next = $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice);

        self::assertSame(1, $next);
        self::assertStringStartsWith('INSERT INTO efatura_sequences', $pdo->preparedSql[1]);
        self::assertSame(1, $pdo->statementParameters[1]['value']);
    }

    /**
     * @return list<string>
     */
    private function runWorkers(
        string $mode,
        string $path,
        int $workerCount,
        int $iterations
    ): array {
        $processes = [];
        $worker = dirname(__DIR__) . '/tools/persistence-worker.php';
        for ($index = 0; $index < $workerCount; ++$index) {
            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, $worker, $mode, $path, (string) $iterations],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            self::assertIsResource($process);
            $processes[] = [$process, $pipes];
        }

        $outputs = [];
        foreach ($processes as [$process, $pipes]) {
            $outputs[] = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $error);
        }

        return $outputs;
    }
}
