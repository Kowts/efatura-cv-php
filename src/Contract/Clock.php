<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use DateTimeImmutable;

/**
 * Fonte de tempo substituível para regras fiscais e testes determinísticos.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
