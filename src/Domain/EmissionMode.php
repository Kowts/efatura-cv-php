<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

/**
 * Modos de emissão previstos pelo e-Fatura.
 */
enum EmissionMode: string
{
    case Online = 'Online';
    case Offline = 'Offline';
    case Off = 'Off';

    public function code(): int
    {
        return match ($this) {
            self::Online => 1,
            self::Offline => 2,
            self::Off => 3,
        };
    }

    public function requiresContingency(): bool
    {
        return $this !== self::Online;
    }
}
