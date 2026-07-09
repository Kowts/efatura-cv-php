<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Contract\MiddlewareTransport;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Exception\SubmissionUncertainException;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Http\SubmissionResult;
use Kowts\Efatura\Infrastructure\Http\Psr18MiddlewareTransport;
use Kowts\Efatura\Tests\Support\CountingMiddlewareTransport;
use Kowts\Efatura\Tests\Support\RecordingClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpTransportTest extends TestCase
{
    public function testTransportePsr18PreservaPedidoEResposta(): void
    {
        $client = new RecordingClient(new Response(
            202,
            ['Content-Type' => 'application/json'],
            '{"requestId":"abc"}',
            'Accepted'
        ));
        $factory = new Psr17Factory();
        $transport = new Psr18MiddlewareTransport($client, $factory, $factory);

        $result = $transport->submit('https://middleware.example.test/', 'chave-secreta', 'zip');

        self::assertTrue($result->ok);
        self::assertSame(202, $result->status);
        self::assertSame('abc', $result->body['requestId']);
        self::assertSame('zip', (string) $client->request?->getBody());
        self::assertSame('chave-secreta', $client->request?->getHeaderLine('cv-ef-mw-core-transmitter-key'));
    }

    public function testImpedeReenvioAcidentalDoMesmoPacote(): void
    {
        $transport = new CountingMiddlewareTransport();
        $efatura = new Efatura(
            new EfaturaConfig(
                transmitterNif: '100200300',
                transmitterLed: '123',
                softwareCode: 'EFATURAPHP',
                softwareName: 'e-Fatura PHP',
                softwareVersion: '0.1.0',
                middlewareBaseUrl: 'https://middleware.example.test',
                transmitterKey: 'segredo'
            ),
            middlewareTransport: $transport
        );

        self::assertTrue($efatura->submitDfeZipResult('zip')->ok);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('já foi submetido');
        $efatura->submitDfeZipResult('zip');
    }

    public function testUsaCaminhoDeEndpointConfiguravel(): void
    {
        $client = new RecordingClient(new Response(202));
        $factory = new Psr17Factory();
        $transport = new Psr18MiddlewareTransport($client, $factory, $factory);

        $transport->submit('https://middleware.example.test', 'chave', 'zip', '/v1/dfes');

        self::assertSame(
            'https://middleware.example.test/v1/dfes',
            (string) $client->request?->getUri()
        );
    }

    public function testSubmeteEventosNoEndpointConfigurado(): void
    {
        $transport = new CountingMiddlewareTransport();
        $efatura = new Efatura(
            new EfaturaConfig(
                transmitterNif: '100200300',
                transmitterLed: '123',
                softwareCode: 'EFATURAPHP',
                softwareName: 'e-Fatura PHP',
                softwareVersion: '0.1.0',
                middlewareBaseUrl: 'https://middleware.example.test',
                transmitterKey: 'segredo',
                middlewareEventPath: '/v1/events'
            ),
            middlewareTransport: $transport
        );

        self::assertTrue($efatura->submitEventZipResult('zip')->ok);
        self::assertSame('/v1/events', $transport->lastEndpointPath);
    }

    public function testFalhaDeTransporteProduzResultadoIncerto(): void
    {
        $transport = new class () implements MiddlewareTransport {
            public function submit(
                string $baseUrl,
                string $transmitterKey,
                string $zip,
                string $endpointPath = '/v1/dfe'
            ): SubmissionResult {
                throw new RuntimeException('timeout');
            }
        };
        $efatura = new Efatura(
            new EfaturaConfig(
                transmitterNif: '100200300',
                transmitterLed: '123',
                softwareCode: 'EFATURAPHP',
                softwareName: 'e-Fatura PHP',
                softwareVersion: '0.1.0',
                middlewareBaseUrl: 'https://middleware.example.test',
                transmitterKey: 'segredo'
            ),
            middlewareTransport: $transport
        );

        try {
            $efatura->submitDfeZipResult('zip');
            self::fail('Era esperada uma falha de submissão incerta.');
        } catch (SubmissionUncertainException $exception) {
            self::assertSame('middleware', $exception->channel);
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }
}
