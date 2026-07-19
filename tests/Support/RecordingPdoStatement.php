<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use PDOStatement;

/**
 * Statement mínimo usado por RecordingPdo.
 */
final class RecordingPdoStatement extends PDOStatement
{
    public function __construct(private readonly RecordingPdo $pdo)
    {
    }

    /**
     * @param array<string, mixed>|null $params
     */
    public function execute(?array $params = null): bool
    {
        $this->pdo->statementParameters[] = $params ?? [];

        return true;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return array_shift($this->pdo->fetchColumns) ?? false;
    }

    public function rowCount(): int
    {
        return $this->pdo->rowCount;
    }
}
