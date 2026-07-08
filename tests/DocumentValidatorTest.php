<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Validation\DocumentValidator;
use PHPUnit\Framework\TestCase;

final class DocumentValidatorTest extends TestCase
{
    public function testValidaDocumentoCoerente(): void
    {
        $document = (new DocumentValidator())->validate(invoiceFixture());

        self::assertSame(1150.0, $document['totals']['payableAmount']);
        self::assertSame('IVA', $document['lines'][0]['taxes'][0]['taxTypeCode']);
    }

    public function testRejeitaTotaisDiferentesDasLinhas(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('não correspondem');

        (new DocumentValidator())->validate(invoiceFixture([
            'totals' => ['payableAmount' => 1200],
        ]));
    }

    public function testExigeMotivoParaImpostoNaoAplicavel(): void
    {
        $this->expectException(ValidationException::class);

        (new DocumentValidator())->validate(invoiceFixture([
            'lines' => [[
                'taxes' => [['taxTypeCode' => 'NA']],
            ]],
        ]));
    }
}
