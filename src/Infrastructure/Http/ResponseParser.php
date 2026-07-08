<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use DOMDocument;

/**
 * Interpreta respostas JSON e XML sem perder o corpo original.
 */
final class ResponseParser
{
    public static function parse(string $body, string $contentType = ''): mixed
    {
        if ($body === '') {
            return null;
        }
        if (str_contains(strtolower($contentType), 'json') || self::looksLikeJson($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        if (str_contains(strtolower($contentType), 'xml') || str_starts_with(ltrim($body), '<')) {
            $previous = libxml_use_internal_errors(true);
            try {
                $document = new DOMDocument();
                if ($document->loadXML($body, LIBXML_NONET | LIBXML_NOBLANKS)) {
                    return self::elementToArray($document->documentElement);
                }
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }

        return $body;
    }

    private static function looksLikeJson(string $body): bool
    {
        $first = substr(ltrim($body), 0, 1);
        return $first === '{' || $first === '[';
    }

    private static function elementToArray(?\DOMElement $element): mixed
    {
        if ($element === null) {
            return null;
        }
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[$child->localName][] = self::elementToArray($child);
            }
        }
        if ($children === []) {
            return trim($element->textContent);
        }
        $result = [];
        foreach ($children as $name => $values) {
            $result[$name] = count($values) === 1 ? $values[0] : $values;
        }
        return $result;
    }
}
