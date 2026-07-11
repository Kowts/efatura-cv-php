<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\Environment;
use Kowts\Efatura\EfaturaFactory;
use Kowts\Efatura\Exception\ValidationException;
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

    public function testCriaConfiguracaoAPartirDeArray(): void
    {
        $config = EfaturaConfig::fromArray([
            'transmitter_nif' => '100200300',
            'transmitter_led' => '123',
            'software_code' => 'EFATURAPHP',
            'software_name' => 'e-Fatura PHP',
            'software_version' => '0.1.0',
            'platform_base_url' => 'https://platform.example.test',
            'dfa_base_url' => 'https://dfa.example.test',
            'environment' => Environment::Production,
        ]);

        self::assertSame('100200300', $config->transmitterNif);
        self::assertSame('https://platform.example.test', $config->platformBaseUrl);
        self::assertSame('https://dfa.example.test', $config->dfaBaseUrl);
        self::assertSame(Environment::Production, $config->environment);
    }

    public function testPermiteUsarAPlataformaSemConfigurarMiddleware(): void
    {
        $efatura = EfaturaFactory::fromArray([
            'transmitter_nif' => '100200300',
            'transmitter_led' => '123',
            'software_code' => 'EFATURAPHP',
            'software_name' => 'e-Fatura PHP',
            'software_version' => '0.1.0',
        ]);

        self::assertNull($efatura->config->middlewareBaseUrl);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('URL do middleware');
        $efatura->submitDfeZipResult('zip');
    }
}
