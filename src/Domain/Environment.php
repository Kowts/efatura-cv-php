<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

/**
 * Ambientes de comunicação com a plataforma.
 */
enum Environment: string
{
    case Production = 'PRODUCTION';
    case Homologation = 'HOMOLOGATION';
    case Test = 'TEST';

    public function repositoryCode(): int
    {
        return match ($this) {
            self::Production => 1,
            self::Homologation => 2,
            self::Test => 3,
        };
    }
}
