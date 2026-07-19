<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;
use Kowts\Efatura\Tests\Support\RecordingPdo;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSubmissionRegistryTest extends TestCase
{
    public function testReservaDigestApenasUmaVezEntreLigacoes(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-submissions-');
        self::assertNotFalse($path);

        try {
            $first = new PdoSubmissionRegistry(new PDO('sqlite:' . $path));
            $second = new PdoSubmissionRegistry(new PDO('sqlite:' . $path));
            $first->createTable();
            $digest = hash('sha256', 'pacote');

            self::assertTrue($first->claim($digest));
            self::assertFalse($second->claim($digest));
        } finally {
            @unlink($path);
        }
    }

    public function testApenasUmProcessoReservaODigest(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-concurrent-submission-');
        self::assertNotFalse($path);

        try {
            (new PdoSubmissionRegistry(new PDO('sqlite:' . $path)))->createTable();
            $worker = dirname(__DIR__) . '/tools/persistence-worker.php';
            $processes = [];
            for ($index = 0; $index < 6; ++$index) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, $worker, 'submission', $path, '1'],
                    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes
                );
                self::assertIsResource($process);
                $processes[] = [$process, $pipes];
            }

            $claims = [];
            foreach ($processes as [$process, $pipes]) {
                $claims[] = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                self::assertSame(0, proc_close($process), $error);
            }

            self::assertSame(1, count(array_filter($claims, static fn (string $value): bool => $value === '1')));
        } finally {
            @unlink($path);
        }
    }

    public function testSqlServerCriaTabelaComSintaxeCompativel(): void
    {
        $pdo = new RecordingPdo('sqlsrv');
        $registry = new PdoSubmissionRegistry($pdo);

        $registry->createTable();

        self::assertStringContainsString("IF OBJECT_ID(N'efatura_submissions', N'U') IS NULL", $pdo->executedSql[0]);
        self::assertStringContainsString('CREATE TABLE efatura_submissions', $pdo->executedSql[0]);
    }

    public function testSqlServerReservaDigestComInsertEChavePrimaria(): void
    {
        $pdo = new RecordingPdo('sqlsrv');
        $pdo->rowCount = -1;
        $registry = new PdoSubmissionRegistry($pdo);
        $digest = hash('sha256', 'pacote-sqlsrv');

        self::assertTrue($registry->claim($digest));
        self::assertStringStartsWith('INSERT INTO efatura_submissions', $pdo->preparedSql[0]);
        self::assertSame($digest, $pdo->statementParameters[0]['digest']);
    }
}
