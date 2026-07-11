<?php

declare(strict_types=1);

namespace Kowts\Efatura;

use Kowts\Efatura\Config\EfaturaConfig;

/**
 * Converte configuração de frameworks e ficheiros em objectos da biblioteca.
 */
final class EfaturaFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): Efatura
    {
        return new Efatura(EfaturaConfig::fromArray($config));
    }
}
