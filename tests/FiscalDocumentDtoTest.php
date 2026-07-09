<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\Data\FiscalDocument;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Validation\DocumentValidator;
use PHPUnit\Framework\TestCase;

final class FiscalDocumentDtoTest extends TestCase
{
    public function testConverteDocumentoSemPerderDados(): void
    {
        $dto = FiscalDocument::fromArray(invoiceFixture(), new DocumentValidator());

        self::assertSame(DocumentType::ElectronicInvoice, $dto->type);
        self::assertSame('Emitente', $dto->emitter->name);
        self::assertSame('100200300', $dto->emitter->taxId?->value);
        self::assertSame('1000', (string) $dto->lines[0]->netTotal);
        self::assertSame('IVA', $dto->lines[0]->taxes[0]->type->value);
        self::assertSame('1150', (string) $dto->totals?->payable);
        self::assertSame('SERV-1', $dto->toArray()['lines'][0]['item']['emitterIdentification']);
    }
}
