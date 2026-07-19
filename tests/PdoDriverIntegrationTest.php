<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoDriverIntegrationTest extends TestCase
{
    public function testMySql(): void
    {
        $this->exerciseDriver('MYSQL');
    }

    public function testPostgreSql(): void
    {
        $this->exerciseDriver('PGSQL');
    }

    public function testSqlServer(): void
    {
        $this->exerciseDriver('SQLSRV');
    }

    private function exerciseDriver(string $driver): void
    {
        $dsn = getenv("EFATURA_TEST_{$driver}_DSN");
        if ($dsn === false || $dsn === '') {
            self::markTestSkipped("A ligação de teste {$driver} não está configurada.");
        }
        $user = getenv("EFATURA_TEST_{$driver}_USER") ?: null;
        $password = getenv("EFATURA_TEST_{$driver}_PASSWORD") ?: null;
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $suffix = strtolower(bin2hex(random_bytes(5)));
        $sequenceTable = 'efatura_sequences_' . $suffix;
        $submissionTable = 'efatura_submissions_' . $suffix;

        try {
            $sequences = new PdoSequenceStore($pdo, $sequenceTable);
            $submissions = new PdoSubmissionRegistry($pdo, $submissionTable);
            $sequences->createTable();
            $submissions->createTable();

            self::assertSame(
                1,
                $sequences->next('100200300', 2026, '123', DocumentType::ElectronicInvoice)
            );
            self::assertSame(
                2,
                $sequences->next('100200300', 2026, '123', DocumentType::ElectronicInvoice)
            );
            $digest = hash('sha256', 'pacote-' . $suffix);
            self::assertTrue($submissions->claim($digest));
            self::assertFalse($submissions->claim($digest));
        } finally {
            $pdo->exec("DROP TABLE IF EXISTS {$sequenceTable}");
            $pdo->exec("DROP TABLE IF EXISTS {$submissionTable}");
        }
    }
}
