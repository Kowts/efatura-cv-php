<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

/**
 * Resultado normalizado de uma consulta a um registo fiscal.
 */
final class RegistryResult
{
    /**
     * @param array<string, mixed> $data
     * @param list<string> $issues
     */
    public function __construct(
        public readonly bool $found,
        public readonly ?bool $active,
        public readonly array $data = [],
        public readonly array $issues = []
    ) {
    }
}
