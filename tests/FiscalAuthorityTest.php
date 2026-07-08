<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Fiscal\Psr18FiscalAuthorityClient;
use Kowts\Efatura\Tests\Support\RecordingClient;
use Kowts\Efatura\Tests\Support\StubFiscalRegistry;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class FiscalAuthorityTest extends TestCase
{
    public function testClienteFiscalUsaRotasConfiguraveis(): void
    {
        $http = new RecordingClient(new Response(200, ['Content-Type' => 'application/json'], '{"active":true}'));
        $client = new Psr18FiscalAuthorityClient(
            $http,
            new Psr17Factory(),
            'https://services.example.test',
            ['taxpayer' => '/contribuintes/{nif}']
        );

        $result = $client->lookupTaxpayer('100200300', 'token');

        self::assertTrue($result->found);
        self::assertTrue($result->active);
        self::assertSame(
            'https://services.example.test/contribuintes/100200300',
            (string) $http->request?->getUri()
        );
        self::assertSame('Bearer token', $http->request?->getHeaderLine('Authorization'));
    }

    public function testReadinessAgregaRegistosObrigatorios(): void
    {
        $registry = new StubFiscalRegistry();
        $efatura = new Efatura(new EfaturaConfig(
            transmitterNif: '100200300',
            transmitterLed: '123',
            softwareCode: 'EFATURAPHP',
            softwareName: 'e-Fatura PHP',
            softwareVersion: '0.1.0',
            middlewareBaseUrl: 'https://middleware.example.test',
            emitter: invoiceFixture()['emitter']
        ));

        $result = $efatura->validateFiscalReadiness(
            invoiceFixture(),
            $registry,
            $registry,
            $registry
        );

        self::assertTrue($result['ready']);
        self::assertArrayHasKey('receiver', $result['checks']);
        self::assertCount(5, $result['checks']);
    }
}
