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

        self::assertSame('1150', $document['totals']['payableAmount']);
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

    public function testReconciliaDecimaisSemErroBinario(): void
    {
        $baseLine = invoiceFixture()['lines'][0];
        $document = invoiceFixture([
            'lines' => [array_replace_recursive($baseLine, [
                'price' => '0.10',
                'priceExtension' => '0.10',
                'netTotal' => '0.10',
                'taxes' => [[
                    'taxTypeCode' => 'IVA',
                    'taxPercentage' => '20',
                    'taxTotal' => '0.02',
                ]],
            ]), array_replace_recursive($baseLine, [
                'price' => '0.20',
                'priceExtension' => '0.20',
                'netTotal' => '0.20',
                'taxes' => [[
                    'taxTypeCode' => 'IVA',
                    'taxPercentage' => '20',
                    'taxTotal' => '0.04',
                ]],
            ])],
            'totals' => [
                'priceExtensionTotalAmount' => '0.30',
                'netTotalAmount' => '0.30',
                'taxTotalAmount' => '0.06',
                'payableAmount' => '0.36',
            ],
        ]);

        $validated = (new DocumentValidator())->validate($document);

        self::assertSame('0.3', $validated['totals']['netTotalAmount']);
        self::assertSame('0.36', $validated['totals']['payableAmount']);
    }
}
