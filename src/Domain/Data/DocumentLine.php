<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

/**
 * Linha fiscal imutável.
 */
final class DocumentLine
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param list<Tax> $taxes
     * @param array<string, mixed> $item
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly float $quantity,
        public readonly string $unitCode,
        public readonly ?float $price,
        public readonly ?float $priceExtension,
        public readonly ?float $netTotal,
        public readonly array $taxes,
        public readonly array $item,
        array $data
    ) {
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $quantity */
        $quantity = $data['quantity'];
        $taxes = array_values(array_map(
            static fn (array $tax): Tax => Tax::fromArray($tax),
            $data['taxes']
        ));

        return new self(
            (float) $quantity['value'],
            (string) $quantity['unitCode'],
            isset($data['price']) ? (float) $data['price'] : null,
            isset($data['priceExtension']) ? (float) $data['priceExtension'] : null,
            isset($data['netTotal']) ? (float) $data['netTotal'] : null,
            $taxes,
            $data['item'],
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
