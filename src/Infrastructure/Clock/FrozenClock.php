<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Clock;

use DateTimeImmutable;
use Kowts\Efatura\Contract\Clock;

/**
 * Relógio imutável útil em testes, importações e reprocessamentos controlados.
 */
final class FrozenClock implements Clock
{
    public function __construct(private readonly DateTimeImmutable $dateTime)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
