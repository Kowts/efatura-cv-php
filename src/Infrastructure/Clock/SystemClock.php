<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Kowts\Efatura\Contract\Clock;

/**
 * Relógio do sistema no fuso horário de Cabo Verde.
 */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Atlantic/Cape_Verde'));
    }
}
