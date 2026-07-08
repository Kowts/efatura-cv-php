<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use DateTimeImmutable;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Infrastructure\Clock\FrozenClock;
use Kowts\Efatura\Validation\DocumentValidator;
use Kowts\Efatura\Validation\IssueDateValidator;
use PHPUnit\Framework\TestCase;

final class FiscalRulesTest extends TestCase
{
    public function testRejeitaDocumentoOnlineForaDaJanelaTemporal(): void
    {
        $validator = new IssueDateValidator(
            new FrozenClock(new DateTimeImmutable('2026-07-08T12:00:00-01:00'))
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('24 horas');
        $validator->validate('2026-07-06', '10:00:00', EmissionMode::Online);
    }

    public function testAceitaContingenciaDentroDeSeteDias(): void
    {
        $validator = new IssueDateValidator(
            new FrozenClock(new DateTimeImmutable('2026-07-08T12:00:00-01:00'))
        );

        self::assertSame(
            '2026-07-02',
            $validator->validate('2026-07-02', '10:00:00', EmissionMode::Offline)->format('Y-m-d')
        );
    }

    public function testRejeitaImpostoIvaEmFacturaRempe(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('REMPE');

        (new DocumentValidator())->validate(invoiceFixture([
            'emitter' => ['fiscalFramework' => 'REMPE'],
        ]));
    }

    public function testExigeAdquirenteNoTalaoAcimaDoLimite(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('20 000');

        (new DocumentValidator())->validate(invoiceFixture([
            'type' => DocumentType::ElectronicSalesReceipt,
            'receiver' => null,
            'lines' => [[
                'price' => 20_000,
                'priceExtension' => 20_000,
                'netTotal' => 20_000,
                'taxes' => [['taxTypeCode' => 'IVA', 'taxPercentage' => 15, 'taxTotal' => 3_000]],
            ]],
            'totals' => [
                'priceExtensionTotalAmount' => 20_000,
                'netTotalAmount' => 20_000,
                'taxTotalAmount' => 3_000,
                'payableAmount' => 23_000,
            ],
        ]));
    }

    public function testRejeitaCampoIncompativelComTipo(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não é permitido');

        (new DocumentValidator())->validate(invoiceFixture([
            'type' => DocumentType::ElectronicSalesReceipt,
            'receiver' => null,
            'dueDate' => '2026-02-20',
        ]));
    }
}
