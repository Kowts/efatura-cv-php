<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

/**
 * Resultado normalizado da reconciliação de uma submissão.
 */
final class ReconciliationResult
{
    /**
     * @param array<string, mixed> $data
     * @param list<string> $issues
     */
    public function __construct(
        public readonly ReconciliationStatus $status,
        public readonly array $data = [],
        public readonly array $issues = []
    ) {
    }
}
