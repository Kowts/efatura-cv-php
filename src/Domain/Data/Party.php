<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Entidade fiscal imutável.
 */
final class Party
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $contacts
     */
    public function __construct(
        public readonly ?TaxId $taxId,
        public readonly ?string $name,
        public readonly ?Address $address = null,
        public readonly ?array $contacts = null,
        public readonly ?string $reference = null,
        public readonly ?string $fiscalFramework = null,
        array $data = []
    ) {
        if ($reference === null && ($taxId === null || $name === null || trim($name) === '')) {
            throw new ValidationException('party', 'A entidade exige uma referência ou identificação fiscal e nome.');
        }
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $reference = isset($data['reference']) ? trim((string) $data['reference']) : null;
        $taxId = isset($data['taxId']) && is_array($data['taxId']) ? TaxId::fromArray($data['taxId']) : null;
        $address = isset($data['address']) && is_array($data['address']) ? Address::fromArray($data['address']) : null;
        $contacts = isset($data['contacts']) && is_array($data['contacts']) ? $data['contacts'] : null;

        return new self(
            $taxId,
            isset($data['name']) ? (string) $data['name'] : null,
            $address,
            $contacts,
            $reference,
            isset($data['fiscalFramework']) ? (string) $data['fiscalFramework'] : null,
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
