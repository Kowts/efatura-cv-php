<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Morada fiscal imutável, preservando todos os campos reconhecidos pelo XSD.
 */
final class Address
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $countryCode,
        array $data = []
    ) {
        if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            throw new ValidationException('address.countryCode', 'O código do país da morada é inválido.');
        }
        $data['countryCode'] = $countryCode;
        $this->data = $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(strtoupper((string) ($data['countryCode'] ?? 'CV')), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
