<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Sequence;

use Kowts\Efatura\Contract\SequenceStore;
use Kowts\Efatura\Domain\DocumentType;
use PDO;

/**
 * Sequências persistentes e atómicas para SQLite, MySQL/MariaDB, PostgreSQL e SQL Server.
 */
final class PdoSequenceStore implements SequenceStore
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $table = 'efatura_sequences'
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function createTable(): void
    {
        $table = $this->safeTable();
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = $driver === 'sqlsrv'
            ? "IF OBJECT_ID(N'{$table}', N'U') IS NULL BEGIN CREATE TABLE {$table} ("
                . 'scope_key VARCHAR(191) PRIMARY KEY, current_value INT NOT NULL, updated_at VARCHAR(32) NOT NULL) END'
            : "CREATE TABLE IF NOT EXISTS {$table} ("
                . 'scope_key VARCHAR(191) PRIMARY KEY, current_value INTEGER NOT NULL, updated_at VARCHAR(32) NOT NULL)';

        $this->pdo->exec($sql);
    }

    public function next(string $nif, int $year, string $led, DocumentType $type): int
    {
        $key = $this->key($nif, $year, $led, $type);
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $updatedAt = gmdate(DATE_ATOM);

        return match ($driver) {
            'sqlite' => $this->nextWithReturning($key, $updatedAt),
            'pgsql' => $this->nextWithPostgreSql($key, $updatedAt),
            'mysql' => $this->nextWithMySql($key, $updatedAt),
            'sqlsrv' => $this->nextWithSqlServer($key, $updatedAt),
            default => $this->nextWithTransaction($key, $updatedAt),
        };
    }

    private function nextWithReturning(string $key, string $updatedAt): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO {$this->safeTable()} (scope_key, current_value, updated_at)"
            . ' VALUES (:scope, 1, :updated)'
            . ' ON CONFLICT(scope_key) DO UPDATE SET'
            . ' current_value = current_value + 1, updated_at = excluded.updated_at'
            . ' RETURNING current_value'
        );
        $statement->execute(['scope' => $key, 'updated' => $updatedAt]);
        $value = $statement->fetchColumn();
        if ($value === false) {
            throw new \RuntimeException('A base de dados não devolveu o próximo número fiscal.');
        }

        return (int) $value;
    }

    private function nextWithPostgreSql(string $key, string $updatedAt): int
    {
        $table = $this->safeTable();
        $statement = $this->pdo->prepare(
            "INSERT INTO {$table} (scope_key, current_value, updated_at)"
            . ' VALUES (:scope, 1, :updated)'
            . ' ON CONFLICT(scope_key) DO UPDATE SET'
            . " current_value = {$table}.current_value + 1, updated_at = excluded.updated_at"
            . ' RETURNING current_value'
        );
        $statement->execute(['scope' => $key, 'updated' => $updatedAt]);
        $value = $statement->fetchColumn();
        if ($value === false) {
            throw new \RuntimeException('A base de dados não devolveu o próximo número fiscal.');
        }

        return (int) $value;
    }

    private function nextWithMySql(string $key, string $updatedAt): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO {$this->safeTable()} (scope_key, current_value, updated_at)"
            . ' VALUES (:scope, 1, :updated)'
            . ' ON DUPLICATE KEY UPDATE'
            . ' current_value = LAST_INSERT_ID(current_value + 1), updated_at = VALUES(updated_at)'
        );
        $statement->execute(['scope' => $key, 'updated' => $updatedAt]);

        return $statement->rowCount() === 1 ? 1 : (int) $this->pdo->lastInsertId();
    }

    private function nextWithSqlServer(string $key, string $updatedAt): int
    {
        $table = $this->safeTable();
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                "SELECT current_value FROM {$table} WITH (UPDLOCK, HOLDLOCK) WHERE scope_key = :scope"
            );
            $statement->execute(['scope' => $key]);
            $current = $statement->fetchColumn();
            $next = $current === false ? 1 : (int) $current + 1;

            $sql = $current === false
                ? "INSERT INTO {$table} (scope_key, current_value, updated_at)"
                    . ' VALUES (:scope, :value, :updated)'
                : "UPDATE {$table} SET current_value = :value, updated_at = :updated"
                    . ' WHERE scope_key = :scope';
            $update = $this->pdo->prepare($sql);
            $update->execute(['scope' => $key, 'value' => $next, 'updated' => $updatedAt]);
            $this->pdo->commit();

            return $next;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function nextWithTransaction(string $key, string $updatedAt): int
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                "SELECT current_value FROM {$this->safeTable()} WHERE scope_key = :scope FOR UPDATE"
            );
            $statement->execute(['scope' => $key]);
            $current = $statement->fetchColumn();
            $next = $current === false ? 1 : (int) $current + 1;

            $sql = $current === false
                ? "INSERT INTO {$this->safeTable()} (scope_key, current_value, updated_at)"
                    . ' VALUES (:scope, :value, :updated)'
                : "UPDATE {$this->safeTable()} SET current_value = :value, updated_at = :updated"
                    . ' WHERE scope_key = :scope';
            $update = $this->pdo->prepare($sql);
            $update->execute(['scope' => $key, 'value' => $next, 'updated' => $updatedAt]);
            $this->pdo->commit();

            return $next;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function current(string $nif, int $year, string $led, DocumentType $type): ?int
    {
        $statement = $this->pdo->prepare(
            "SELECT current_value FROM {$this->safeTable()} WHERE scope_key = :scope"
        );
        $statement->execute(['scope' => $this->key($nif, $year, $led, $type)]);
        $value = $statement->fetchColumn();

        return $value === false ? null : (int) $value;
    }

    public function reset(string $nif, int $year, string $led, DocumentType $type): void
    {
        $statement = $this->pdo->prepare("DELETE FROM {$this->safeTable()} WHERE scope_key = :scope");
        $statement->execute(['scope' => $this->key($nif, $year, $led, $type)]);
    }

    private function key(string $nif, int $year, string $led, DocumentType $type): string
    {
        return implode(':', [$nif, $year, $led, $type->value]);
    }

    private function safeTable(): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $this->table) !== 1) {
            throw new \InvalidArgumentException('O nome da tabela de sequências é inválido.');
        }

        return $this->table;
    }
}
