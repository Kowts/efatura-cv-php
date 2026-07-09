<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Environment;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Sequence\InMemorySequenceStore;
use Kowts\Efatura\Infrastructure\Clock\FrozenClock;
use Kowts\Efatura\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class EfaturaTest extends TestCase
{
    public function testFluxoDeGeracaoXmlEZip(): void
    {
        $efatura = $this->efatura();
        $iud = $efatura->buildIud(
            '2026-02-08',
            DocumentType::ElectronicInvoice,
            1,
            '1234567890'
        );
        $xml = $efatura->buildDfeXml($iud, invoiceFixture());

        self::assertStringContainsString('<Dfe xmlns="urn:cv:efatura:xsd:v1.0"', $xml);
        self::assertStringContainsString('<DocumentNumber>000000001</DocumentNumber>', $xml);
        self::assertStringContainsString('<PayableAmount>1150</PayableAmount>', $xml);
        $validation = $efatura->validateXml($xml);
        self::assertTrue(
            $validation['valid'],
            implode("\n", array_column($validation['errors'], 'message'))
        );

        $zipBytes = $efatura->buildDfeZip([['iud' => $iud, 'xml' => $xml]]);
        $path = tempnam(sys_get_temp_dir(), 'efatura-test-');
        self::assertNotFalse($path);
        file_put_contents($path, $zipBytes);
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));
        self::assertSame($xml, $zip->getFromName($iud . '.xml'));
        $zip->close();
        unlink($path);
    }

    public function testSequenciasSaoSeparadasPorTipo(): void
    {
        $efatura = $this->efatura();

        self::assertSame(1, $efatura->nextDocumentNumber('2026-01-01', DocumentType::ElectronicInvoice));
        self::assertSame(2, $efatura->nextDocumentNumber('2026-01-02', DocumentType::ElectronicInvoice));
        self::assertSame(1, $efatura->nextDocumentNumber('2026-01-02', DocumentType::ElectronicCreditNote));
    }

    public function testRejeitaIudDeOutroTipoDocumental(): void
    {
        $efatura = $this->efatura();
        $iud = $efatura->buildIud(
            '2026-02-08',
            DocumentType::ElectronicCreditNote,
            1,
            '1234567890'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('documentTypeCode');

        $efatura->buildDfeXml($iud, invoiceFixture());
    }

    public function testRejeitaZipCujoNomeNaoCorrespondeAoIdDoXml(): void
    {
        $efatura = $this->efatura();
        $xmlIud = $efatura->buildIud('2026-02-08', DocumentType::ElectronicInvoice, 1, '1234567890');
        $fileIud = $efatura->buildIud('2026-02-08', DocumentType::ElectronicInvoice, 2, '1234567890');
        $xml = $efatura->buildDfeXml($xmlIud, invoiceFixture());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não corresponde');

        $efatura->buildDfeZip([['iud' => $fileIud, 'xml' => $xml]]);
    }

    public function testProducaoRejeitaArmazenamentoApenasEmMemoria(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sequências persistente');

        new Efatura(new EfaturaConfig(
            transmitterNif: '100200300',
            transmitterLed: '123',
            softwareCode: 'EFATURAPHP',
            softwareName: 'e-Fatura PHP',
            softwareVersion: '0.1.0',
            middlewareBaseUrl: 'https://middleware.example.test',
            environment: Environment::Production
        ));
    }

    private function efatura(): Efatura
    {
        return new Efatura(
            new EfaturaConfig(
                transmitterNif: '100200300',
                transmitterLed: '123',
                softwareCode: 'EFATURAPHP',
                softwareName: 'e-Fatura PHP',
                softwareVersion: '0.1.0',
                middlewareBaseUrl: 'https://middleware.example.test',
                defaultSerie: 'SER-F',
                emitter: invoiceFixture()['emitter'],
                environment: Environment::Test
            ),
            new InMemorySequenceStore(),
            clock: new FrozenClock(new \DateTimeImmutable('2026-02-08T12:00:00-01:00'))
        );
    }
}
