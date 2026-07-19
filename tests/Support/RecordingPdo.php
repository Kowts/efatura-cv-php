<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use PDO;
use PDOStatement;

/**
 * PDO mínimo para testar SQL específico sem exigir o motor real no ambiente local.
 */
final class RecordingPdo extends PDO
{
    /**
     * @var list<string>
     */
    public array $preparedSql = [];

    /**
     * @var list<string>
     */
    public array $executedSql = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $statementParameters = [];

    /**
     * @var list<mixed>
     */
    public array $fetchColumns = [];

    public int $rowCount = 1;

    public int $beginTransactionCalls = 0;

    public int $commitCalls = 0;

    public int $rollBackCalls = 0;

    public function __construct(private readonly string $driver)
    {
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        return true;
    }

    public function getAttribute(int $attribute): mixed
    {
        return $attribute === PDO::ATTR_DRIVER_NAME ? $this->driver : null;
    }

    public function exec(string $statement): int|false
    {
        $this->executedSql[] = $statement;

        return 0;
    }

    /**
     * @param array<int, mixed> $options
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->preparedSql[] = $query;

        return new RecordingPdoStatement($this);
    }

    public function beginTransaction(): bool
    {
        ++$this->beginTransactionCalls;

        return true;
    }

    public function commit(): bool
    {
        ++$this->commitCalls;

        return true;
    }

    public function rollBack(): bool
    {
        ++$this->rollBackCalls;

        return true;
    }
}
