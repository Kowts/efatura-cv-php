<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Validation;

use DOMDocument;
use LibXMLError;
use Kowts\Efatura\Exception\EfaturaException;

/**
 * Valida XML com libxml e os XSD oficiais distribuídos com o pacote.
 */
final class XsdValidator
{
    public function __construct(
        private readonly ?string $schemaPath = null
    ) {
    }

    /**
     * @return array{valid:bool, errors:list<array{message:string, line:int, column:int, code:int}>}
     */
    public function validate(string $xml): array
    {
        $schema = $this->schemaPath ?? dirname(__DIR__, 3)
            . '/resources/xsd/efatura/2024-05-27/EnvelopedSignature.xsd';

        if (!is_file($schema)) {
            throw new EfaturaException("O ficheiro XSD não foi encontrado em {$schema}.");
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $document = new DOMDocument();
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
            $valid = $loaded && $document->schemaValidate($schema);
            $errors = array_map(
                static fn (LibXMLError $error): array => [
                    'message' => trim($error->message),
                    'line' => $error->line,
                    'column' => $error->column,
                    'code' => $error->code,
                ],
                libxml_get_errors()
            );

            return ['valid' => $valid, 'errors' => $errors];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
