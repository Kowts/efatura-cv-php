<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\Environment;
use Kowts\Efatura\EfaturaFactory;
use PHPUnit\Framework\TestCase;

final class EfaturaFactoryTest extends TestCase
{
    public function testCriaInstanciaAPartirDeConfiguracaoDeFramework(): void
    {
        $efatura = EfaturaFactory::fromArray([
            'transmitter_nif' => '100200300',
            'transmitter_led' => '123',
            'software_code' => 'EFATURAPHP',
            'software_name' => 'e-Fatura PHP',
            'software_version' => '0.1.0',
            'middleware_base_url' => 'https://middleware.example.test',
            'environment' => 'HOMOLOGATION',
        ]);

        self::assertSame(Environment::Homologation, $efatura->config->environment);
        self::assertSame(2, $efatura->config->repositoryCode());
    }
}
