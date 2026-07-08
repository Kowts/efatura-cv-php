<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

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
        public readonly float $priceExtension,
        public readonly float $net,
        public readonly float $tax,
        public readonly float $payable,
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
            (float) $data['priceExtensionTotalAmount'],
            (float) $data['netTotalAmount'],
            (float) $data['taxTotalAmount'],
            (float) $data['payableAmount'],
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
