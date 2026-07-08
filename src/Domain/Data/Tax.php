<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Domain\TaxType;

/**
 * Imposto aplicado a uma linha.
 */
final class Tax
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly TaxType $type,
        public readonly ?float $percentage,
        public readonly ?float $total,
        public readonly ?string $exemptionReasonCode,
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
            TaxType::from((string) $data['taxTypeCode']),
            isset($data['taxPercentage']) ? (float) $data['taxPercentage'] : null,
            isset($data['taxTotal']) ? (float) $data['taxTotal'] : null,
            isset($data['taxExemptionReasonCode']) ? (string) $data['taxExemptionReasonCode'] : null,
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
