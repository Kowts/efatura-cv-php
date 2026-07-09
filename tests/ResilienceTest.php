<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Contract\DocumentStatusClient;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Fiscal\ReconciliationStatus;
use Kowts\Efatura\Fiscal\RegistryResult;
use Kowts\Efatura\Fiscal\SubmissionReconciler;
use Kowts\Efatura\Infrastructure\Http\RetryingPsr18Client;
use Kowts\Efatura\Tests\Support\SequenceClient;
use Kowts\Efatura\Tests\Support\StubFiscalRegistry;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ResilienceTest extends TestCase
{
    public function testRepeteApenasPedidosIdempotentes(): void
    {
        $delays = [];
        $inner = new SequenceClient([new Response(503), new Response(200)]);
        $client = new RetryingPsr18Client(
            $inner,
            initialDelayMs: 10,
            sleeper: static function (int $delay) use (&$delays): void {
                $delays[] = $delay;
            }
        );

        self::assertSame(200, $client->sendRequest(new Request('GET', 'https://example.test'))->getStatusCode());
        self::assertSame(2, $inner->calls);
        self::assertSame([10], $delays);

        $postInner = new SequenceClient([new Response(503), new Response(200)]);
        $postClient = new RetryingPsr18Client($postInner, sleeper: static function (int $delay): void {
        });
        self::assertSame(
            503,
            $postClient->sendRequest(new Request('POST', 'https://example.test'))->getStatusCode()
        );
        self::assertSame(1, $postInner->calls);
    }

    public function testReconciliaDocumentoEncontradoENaoEncontrado(): void
    {
        $iud = Iud::build(1, '2026-01-02', '100200300', '123', DocumentType::ElectronicInvoice, 1);

        $confirmed = (new SubmissionReconciler(new StubFiscalRegistry()))->reconcile($iud);
        $missing = (new SubmissionReconciler(new StubFiscalRegistry(false)))->reconcile($iud);

        self::assertSame(ReconciliationStatus::Confirmed, $confirmed->status);
        self::assertSame(ReconciliationStatus::NotFound, $missing->status);
    }

    public function testErroRemotoMantemEstadoIndeterminado(): void
    {
        $iud = Iud::build(1, '2026-01-02', '100200300', '123', DocumentType::ElectronicInvoice, 1);
        $registry = new class () implements DocumentStatusClient {
            public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult
            {
                return new RegistryResult(false, null, issues: ['HTTP 503']);
            }
        };

        $result = (new SubmissionReconciler($registry))->reconcile($iud);

        self::assertSame(ReconciliationStatus::Indeterminate, $result->status);
    }
}
