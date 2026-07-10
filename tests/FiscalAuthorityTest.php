<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Exception\ValidationException;
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

    public function testClienteFiscalPedeAutorizacaoDeAutofaturacao(): void
    {
        $http = new RecordingClient(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode([
                'succeeded' => true,
                'payload' => [
                    'authorizationId' => 'a4528b45-974d-40eb-b251-dd6fcd41e71c',
                    'authorizationCodeExpirationSeconds' => 3600,
                    'iud' => 'CV324021519999999300001010000000119503859337',
                    'serie' => 'AUTOFATURA',
                    'ledCode' => '1',
                    'documentNumber' => 1,
                ],
            ], JSON_THROW_ON_ERROR)
        ));
        $client = new Psr18FiscalAuthorityClient($http, new Psr17Factory(), 'https://services.example.test');

        $result = $client->authorizeSelfBilling(
            '900800700',
            DocumentType::ElectronicInvoice,
            '9911122',
            '1150.00',
            'token'
        );

        self::assertTrue($result->succeeded);
        self::assertSame('a4528b45-974d-40eb-b251-dd6fcd41e71c', $result->authorizationId);
        self::assertSame(3600, $result->authorizationCodeExpirationSeconds);
        self::assertSame('CV324021519999999300001010000000119503859337', $result->iud);
        self::assertSame('AUTOFATURA', $result->serie);
        self::assertSame('1', $result->ledCode);
        self::assertSame(1, $result->documentNumber);
        self::assertSame(
            'https://services.example.test/v1/dfe/self-billing/authorize',
            (string) $http->request?->getUri()
        );
        self::assertSame('POST', $http->request?->getMethod());
        self::assertSame('Bearer token', $http->request?->getHeaderLine('Authorization'));
        self::assertSame('application/json', $http->request?->getHeaderLine('Content-Type'));
        self::assertSame([
            'taxId' => '900800700',
            'documentTypeCode' => 1,
            'mobilePhoneNumber' => '9911122',
            'totalAmount' => '1150',
        ], json_decode((string) $http->request?->getBody(), true));
    }

    public function testAutofaturacaoRejeitaTiposNaoAutorizadosPeloManual(): void
    {
        $client = new Psr18FiscalAuthorityClient(
            new RecordingClient(new Response(200, [], '{}')),
            new Psr17Factory(),
            'https://services.example.test'
        );

        $this->expectException(ValidationException::class);

        $client->authorizeSelfBilling(
            '900800700',
            DocumentType::ElectronicTransportDocument,
            '9911122',
            '1150.00'
        );
    }
}
