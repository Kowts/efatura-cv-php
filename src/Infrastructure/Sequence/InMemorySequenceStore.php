<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Sequence;

use Kowts\Efatura\Contract\SequenceStore;
use Kowts\Efatura\Domain\DocumentType;

/**
 * Armazenamento destinado apenas a testes e processos de curta duração.
 */
final class InMemorySequenceStore implements SequenceStore
{
    /** @var array<string, int> */
    private array $sequences = [];

    public function next(string $nif, int $year, string $led, DocumentType $type): int
    {
        $key = $this->key($nif, $year, $led, $type);
        return $this->sequences[$key] = ($this->sequences[$key] ?? 0) + 1;
    }

    public function current(string $nif, int $year, string $led, DocumentType $type): ?int
    {
        return $this->sequences[$this->key($nif, $year, $led, $type)] ?? null;
    }

    public function reset(string $nif, int $year, string $led, DocumentType $type): void
    {
        unset($this->sequences[$this->key($nif, $year, $led, $type)]);
    }

    private function key(string $nif, int $year, string $led, DocumentType $type): string
    {
        return implode(':', [$nif, $year, $led, $type->value]);
    }
}
