<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain\Data;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Identificação fiscal imutável de uma entidade.
 */
final class TaxId
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $value
    ) {
        if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1 || trim($value) === '') {
            throw new ValidationException('taxId', 'A identificação fiscal é inválida.');
        }
        if ($countryCode === 'CV') {
            EfaturaConfig::assertNif($value);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            strtoupper(trim((string) ($data['countryCode'] ?? ''))),
            trim((string) ($data['value'] ?? ''))
        );
    }

    /**
     * @return array{countryCode:string, value:string}
     */
    public function toArray(): array
    {
        return ['countryCode' => $this->countryCode, 'value' => $this->value];
    }
}
