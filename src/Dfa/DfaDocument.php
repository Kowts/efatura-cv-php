<?php

declare(strict_types=1);

namespace Kowts\Efatura\Dfa;

/**
 * Documento fiscal auxiliar pronto para guardar ou enviar numa resposta HTTP.
 */
final class DfaDocument
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mimeType,
        public readonly string $filename
    ) {
    }
}
