<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Iud;
use PHPUnit\Framework\TestCase;

final class IudTest extends TestCase
{
    public function testGeraVectorInternoConhecido(): void
    {
        $iud = Iud::build(
            3,
            '2026-02-08',
            '100200300',
            '123',
            DocumentType::ElectronicInvoice,
            1,
            '1234567890'
        );

        self::assertSame('CV3260208100200300001230100000000112345678909', $iud);
        self::assertTrue(Iud::isValid($iud));
        self::assertSame('000000001', Iud::parse($iud)['documentNumber']);
    }

    public function testRejeitaDigitoDeControloAlterado(): void
    {
        self::assertFalse(Iud::isValid('CV3260208100200300001230100000000112345678908'));
    }

    public function testRejeitaComponentesInvalidosMesmoComLuhnValido(): void
    {
        $invalidRepository = '9' . substr('326020810020030000123010000000011234567890', 1);
        $invalidDate = '3' . '269931' . substr('326020810020030000123010000000011234567890', 7);

        self::assertFalse(Iud::isValid('CV' . $invalidRepository . Iud::luhnDigit($invalidRepository)));
        self::assertFalse(Iud::isValid('CV' . $invalidDate . Iud::luhnDigit($invalidDate)));
    }

    public function testRejeitaAnoQueNaoPodeSerRepresentadoSemAmbiguidade(): void
    {
        $this->expectException(\Kowts\Efatura\Exception\ValidationException::class);

        Iud::build(
            3,
            '2126-02-08',
            '100200300',
            '123',
            DocumentType::ElectronicInvoice,
            1,
            '1234567890'
        );
    }
}
