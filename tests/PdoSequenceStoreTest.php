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

    public function testDuasLigacoesPartilhamSequenciaAtomica(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'efatura-sequence-');
        self::assertNotFalse($path);

        try {
            $first = new PdoSequenceStore(new PDO('sqlite:' . $path));
            $second = new PdoSequenceStore(new PDO('sqlite:' . $path));
            $first->createTable();

            $numbers = [];
            for ($index = 0; $index < 100; ++$index) {
                $store = $index % 2 === 0 ? $first : $second;
                $numbers[] = $store->next('100200300', 2026, '123', DocumentType::ElectronicInvoice);
            }

            self::assertSame(range(1, 100), $numbers);
            self::assertSame(
                100,
                $second->current('100200300', 2026, '123', DocumentType::ElectronicInvoice)
            );
        } finally {
            @unlink($path);
        }
    }
}
