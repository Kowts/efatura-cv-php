<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use DateTimeImmutable;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EventType;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Clock\FrozenClock;
use Kowts\Efatura\Validation\EventValidator;
use PHPUnit\Framework\TestCase;

final class EventValidatorTest extends TestCase
{
    public function testValidaEventoDeAnulacao(): void
    {
        $iud = Iud::build(3, '2026-07-08', '100200300', '1', DocumentType::ElectronicInvoice, 1, '1234567890');
        $event = (new EventValidator())->validate([
            'type' => EventType::FiscalDocumentCancellation,
            'issueDateTime' => '2026-07-08T12:00:00-01:00',
            'issueReasonDescription' => 'Documento emitido por engano.',
            'iuds' => [$iud],
        ]);

        self::assertSame([$iud], $event['iuds']);
    }

    public function testValidaIntervaloDeNumeracaoInutilizada(): void
    {
        $event = (new EventValidator())->validate([
            'type' => EventType::UnusedDocumentNumber,
            'issueDateTime' => '2026-07-08T12:00:00-01:00',
            'issueReasonDescription' => 'Série descontinuada.',
            'range' => [
                'year' => '2026',
                'ledCode' => '1',
                'serie' => 'SER-F',
                'documentType' => DocumentType::ElectronicInvoice,
                'documentNumberStart' => 10,
                'documentNumberEnd' => 20,
            ],
        ]);

        self::assertSame(20, $event['range']['documentNumberEnd']);
    }

    public function testRejeitaAlvosConflitantes(): void
    {
        $this->expectException(ValidationException::class);
        $iud = Iud::build(3, '2026-07-08', '100200300', '1', DocumentType::ElectronicInvoice, 1, '1234567890');

        (new EventValidator())->validate([
            'type' => EventType::UnusedDocumentNumber,
            'issueDateTime' => '2026-07-08T12:00:00-01:00',
            'issueReasonDescription' => 'Teste.',
            'iuds' => [$iud],
            'range' => [],
        ]);
    }

    public function testGeraValidaEEmpacotaEvento(): void
    {
        $efatura = new Efatura(
            new EfaturaConfig(
                transmitterNif: '100200300',
                transmitterLed: '123',
                softwareCode: 'EFATURAPHP',
                softwareName: 'e-Fatura PHP',
                softwareVersion: '0.1.0',
                middlewareBaseUrl: 'https://middleware.example.test',
                emitter: invoiceFixture()['emitter']
            ),
            clock: new FrozenClock(new DateTimeImmutable('2026-07-08T12:00:00-01:00'))
        );
        $eventId = $efatura->buildEventId('2026-07-08T12:00:00-01:00');
        $iud = $efatura->buildIud(
            '2026-07-08',
            DocumentType::ElectronicInvoice,
            1,
            '1234567890'
        );
        $xml = $efatura->buildEventXml($eventId, [
            'type' => EventType::FiscalDocumentCancellation,
            'issueDateTime' => '2026-07-08T12:00:00-01:00',
            'issueReasonDescription' => 'Documento emitido por engano.',
            'iuds' => [$iud],
        ]);

        self::assertTrue($efatura->validateXml($xml)['valid']);
        self::assertNotSame('', $efatura->buildEventZip([['eventId' => $eventId, 'xml' => $xml]]));
    }

    public function testRejeitaDataDeEventoNaoIso(): void
    {
        $this->expectException(ValidationException::class);

        (new EventValidator())->validate([
            'type' => EventType::FiscalDocumentCancellation,
            'issueDateTime' => 'amanhã',
            'issueReasonDescription' => 'Formato inválido.',
            'iuds' => [Iud::build(
                3,
                '2026-07-08',
                '100200300',
                '1',
                DocumentType::ElectronicInvoice,
                1,
                '1234567890'
            )],
        ]);
    }
}
