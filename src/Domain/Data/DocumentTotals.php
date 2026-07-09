<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Domain\Decimal;

/**
 * Totais fiscais imutáveis.
 */
final class DocumentTotals
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly Decimal $priceExtension,
        public readonly Decimal $net,
        public readonly Decimal $tax,
        public readonly Decimal $payable,
        array $data
    ) {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Decimal::from($data['priceExtensionTotalAmount']),
            Decimal::from($data['netTotalAmount']),
            Decimal::from($data['taxTotalAmount']),
            Decimal::from($data['payableAmount']),
            $data
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
