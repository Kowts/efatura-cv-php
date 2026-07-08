<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Domain\DocumentType;

/**
 * Reserva números fiscais de forma atómica por NIF, ano, LED e tipo.
 */
interface SequenceStore
{
    public function next(string $nif, int $year, string $led, DocumentType $type): int;

    public function current(string $nif, int $year, string $led, DocumentType $type): ?int;

    public function reset(string $nif, int $year, string $led, DocumentType $type): void;
}
