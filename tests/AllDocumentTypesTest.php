<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AllDocumentTypesTest extends TestCase
{
    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('documentTypes')]
    public function testGeraXmlValidoParaTodosOsTipos(
        DocumentType $type,
        int $number,
        array $overrides
    ): void {
        $efatura = $this->efatura();
        $document = invoiceFixture(array_replace(['type' => $type], $overrides));
        $iud = $efatura->buildIud('2026-02-08', $type, $number, '1234567890');
        $xml = $efatura->buildDfeXml($iud, $document);
        $result = $efatura->validateXml($xml);

        self::assertTrue($result['valid'], implode("\n", array_column($result['errors'], 'message')));
    }

    /**
     * @return iterable<string, array{DocumentType, int, array<string, mixed>}>
     */
    public static function documentTypes(): iterable
    {
        $reference = ['references' => [['fiscalDocument' => ['value' => '1/2026/ABC/1', 'isOldDocument' => true]]]];
        yield 'FTE' => [DocumentType::ElectronicInvoice, 1, []];
        yield 'FRE' => [DocumentType::ElectronicInvoiceReceipt, 2, []];
        yield 'TVE' => [DocumentType::ElectronicSalesReceipt, 3, ['receiver' => null]];
        yield 'RCE' => [DocumentType::ElectronicReceipt, 4, ['receiptTypeCode' => '1']];
        yield 'NCE' => [DocumentType::ElectronicCreditNote, 5, $reference + ['issueReasonCode' => '2']];
        yield 'NDE' => [DocumentType::ElectronicDebitNote, 6, $reference + ['issueReasonCode' => '2']];
        yield 'DTE' => [
            DocumentType::ElectronicTransportDocument,
            7,
            [
                'receiver' => null,
                'transportDocumentTypeCode' => '1',
                'transportServiceProviderParty' => invoiceFixture()['emitter'],
                'transportRoute' => [
                    'locations' => [[
                        'address' => ['countryCode' => 'CV', 'addressDetail' => 'Origem'],
                        'duration' => ['startDate' => '2026-02-08', 'startTime' => '10:30:00'],
                        'transportModeCode' => '1',
                    ], [
                        'address' => ['countryCode' => 'CV', 'addressDetail' => 'Destino'],
                        'duration' => ['startDate' => '2026-02-08', 'startTime' => '11:30:00'],
                        'transportModeCode' => '1',
                    ]],
                ],
            ],
        ];
        yield 'DVE' => [
            DocumentType::ElectronicReturnNote,
            8,
            $reference + ['receiver' => null, 'issueReasonCode' => '0'],
        ];
        yield 'NLE' => [DocumentType::ElectronicEntryNote, 9, []];
    }

    private function efatura(): Efatura
    {
        return new Efatura(new EfaturaConfig(
            transmitterNif: '100200300',
            transmitterLed: '123',
            softwareCode: 'EFATURAPHP',
            softwareName: 'e-Fatura PHP',
            softwareVersion: '0.1.0',
            middlewareBaseUrl: 'https://middleware.example.test',
            defaultSerie: 'SER-F',
            emitter: invoiceFixture()['emitter']
        ), clock: new FrozenClock(new \DateTimeImmutable('2026-02-08T12:00:00-01:00')));
    }
}
