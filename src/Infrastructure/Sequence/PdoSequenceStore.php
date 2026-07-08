<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Sequence;

use Kowts\Efatura\Contract\SequenceStore;
use Kowts\Efatura\Domain\DocumentType;
use PDO;
use Throwable;

/**
 * Sequências persistentes e atómicas para SQLite, MySQL/MariaDB e PostgreSQL.
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
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$table} ("
            . 'scope_key VARCHAR(191) PRIMARY KEY, current_value INTEGER NOT NULL, updated_at VARCHAR(32) NOT NULL)'
        );
    }

    public function next(string $nif, int $year, string $led, DocumentType $type): int
    {
        $key = $this->key($nif, $year, $led, $type);
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                "SELECT current_value FROM {$this->safeTable()} WHERE scope_key = :scope"
                . ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE')
            );
            $statement->execute(['scope' => $key]);
            $current = $statement->fetchColumn();
            $next = $current === false ? 1 : ((int) $current + 1);

            if ($current === false) {
                $statement = $this->pdo->prepare(
                    "INSERT INTO {$this->safeTable()} (scope_key, current_value, updated_at)"
                    . ' VALUES (:scope, :value, :updated)'
                );
            } else {
                $statement = $this->pdo->prepare(
                    "UPDATE {$this->safeTable()} SET current_value = :value, updated_at = :updated"
                    . ' WHERE scope_key = :scope'
                );
            }
            $statement->execute([
                'scope' => $key,
                'value' => $next,
                'updated' => gmdate(DATE_ATOM),
            ]);
            $this->pdo->commit();

            return $next;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
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
