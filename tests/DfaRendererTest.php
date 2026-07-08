<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use PHPUnit\Framework\TestCase;

final class DfaRendererTest extends TestCase
{
    public function testRenderizaPdfComNomeDoIud(): void
    {
        $efatura = new Efatura(new EfaturaConfig(
            transmitterNif: '100200300',
            transmitterLed: '123',
            softwareCode: 'EFATURAPHP',
            softwareName: 'e-Fatura PHP',
            softwareVersion: '0.1.0',
            middlewareBaseUrl: 'https://middleware.example.test',
            defaultSerie: 'SER-F',
            emitter: invoiceFixture()['emitter']
        ));
        $iud = $efatura->buildIud('2026-02-08', DocumentType::ElectronicInvoice, 1, '1234567890');

        $dfa = $efatura->renderDfa($iud, invoiceFixture());

        self::assertSame('application/pdf', $dfa->mimeType);
        self::assertSame($iud . '.pdf', $dfa->filename);
        self::assertStringStartsWith('%PDF-', $dfa->contents);
        self::assertGreaterThan(5_000, strlen($dfa->contents));
    }
}
