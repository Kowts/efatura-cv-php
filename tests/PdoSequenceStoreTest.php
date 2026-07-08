<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use PDO;
use PHPUnit\Framework\TestCase;

final class PdoSequenceStoreTest extends TestCase
{
    public function testPersisteEReiniciaSequencia(): void
    {
        $store = new PdoSequenceStore(new PDO('sqlite::memory:'));
        $store->createTable();

        self::assertSame(1, $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        self::assertSame(2, $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        self::assertSame(2, $store->current('100200300', 2026, '123', DocumentType::ElectronicInvoice));
        $store->reset('100200300', 2026, '123', DocumentType::ElectronicInvoice);
        self::assertNull($store->current('100200300', 2026, '123', DocumentType::ElectronicInvoice));
    }
}
