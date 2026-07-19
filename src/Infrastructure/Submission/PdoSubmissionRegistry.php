<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Submission;

use Kowts\Efatura\Contract\SubmissionRegistry;
use PDO;
use PDOException;

/**
 * Registo de idempotência persistente e atómico para SQLite, MySQL/MariaDB e PostgreSQL.
 *
 * Outros drivers PDO só devem ser usados em produção depois de implementação e
 * teste de concorrência específicos.
 */
final class PdoSubmissionRegistry implements SubmissionRegistry
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'efatura_submissions'
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function createTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$this->safeTable()} ("
            . 'digest CHAR(64) PRIMARY KEY, claimed_at VARCHAR(32) NOT NULL)'
        );
    }

    public function claim(string $digest): bool
    {
        if (preg_match('/^[a-f0-9]{64}$/', $digest) !== 1) {
            throw new \InvalidArgumentException('O digest de submissão deve ser SHA-256 hexadecimal.');
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = match ($driver) {
            'sqlite', 'pgsql' => "INSERT INTO {$this->safeTable()} (digest, claimed_at)"
                . ' VALUES (:digest, :claimed) ON CONFLICT(digest) DO NOTHING',
            'mysql' => "INSERT IGNORE INTO {$this->safeTable()} (digest, claimed_at)"
                . ' VALUES (:digest, :claimed)',
            default => "INSERT INTO {$this->safeTable()} (digest, claimed_at)"
                . ' VALUES (:digest, :claimed)',
        };

        try {
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['digest' => $digest, 'claimed' => gmdate(DATE_ATOM)]);

            return $statement->rowCount() === 1;
        } catch (PDOException $exception) {
            if (str_starts_with((string) $exception->getCode(), '23')) {
                return false;
            }
            throw $exception;
        }
    }

    private function safeTable(): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->table) !== 1) {
            throw new \InvalidArgumentException('O nome da tabela de submissões é inválido.');
        }

        return $this->table;
    }
}
