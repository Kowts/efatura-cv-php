<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Iud;
use PHPUnit\Framework\TestCase;

final class IudPropertyTest extends TestCase
{
    public function testGerarAnalisarEValidarSaoOperacoesConsistentes(): void
    {
        mt_srand(20260708);
        $types = DocumentType::cases();

        for ($index = 0; $index < 500; ++$index) {
            $type = $types[$index % count($types)];
            $number = mt_rand(1, 999_999_999);
            $random = str_pad((string) mt_rand(0, 999_999_999), 10, '0', STR_PAD_LEFT);
            $iud = Iud::build(3, '2026-07-08', '100200300', (string) mt_rand(1, 99_999), $type, $number, $random);
            $parsed = Iud::parse($iud);

            self::assertTrue(Iud::isValid($iud));
            self::assertSame($type->iudCode(), $parsed['documentTypeCode']);
            self::assertSame(str_pad((string) $number, 9, '0', STR_PAD_LEFT), $parsed['documentNumber']);

            $changedDigit = substr($iud, -1) === '9' ? '0' : (string) ((int) substr($iud, -1) + 1);
            self::assertFalse(Iud::isValid(substr($iud, 0, -1) . $changedDigit));
        }
    }
}
