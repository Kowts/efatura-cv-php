<?php

declare(strict_types=1);

namespace Kowts\Efatura\Xml;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Operações pequenas e previsíveis para serialização XML compacta.
 */
final class Xml
{
    public static function escape(string|int|float|bool $value): string
    {
        return htmlspecialchars(self::scalar($value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function element(string $name, string|int|float|bool|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        self::assertName($name);
        return "<{$name}>" . self::escape($value) . "</{$name}>";
    }

    public static function required(mixed $value, string $field): mixed
    {
        if ($value === null || $value === '') {
            throw new ValidationException($field, "O campo {$field} é obrigatório.", "{$field}.required");
        }

        return $value;
    }

    public static function assertName(string $name): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.-]*$/', $name) !== 1) {
            throw new ValidationException('xml.name', 'O nome do elemento XML é inválido.', 'xml.name_invalid');
        }
    }

    private static function scalar(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            $formatted = number_format(round($value, 5), 5, '.', '');
            return rtrim(rtrim($formatted, '0'), '.');
        }

        return (string) $value;
    }
}
