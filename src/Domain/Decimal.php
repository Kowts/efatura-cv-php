<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Valor decimal exacto, sem aritmética binária de ponto flutuante.
 */
final class Decimal
{
    private const MAX_SCALE = 5;

    private function __construct(private readonly string $value)
    {
    }

    public static function from(int|float|string $value, string $field = 'decimal'): self
    {
        return new self(self::normalise($value, $field));
    }

    public static function normalise(int|float|string $value, string $field = 'decimal'): string
    {
        if (is_float($value)) {
            if (!is_finite($value)) {
                throw self::invalid($field);
            }
            $text = number_format($value, self::MAX_SCALE, '.', '');
        } else {
            $text = trim((string) $value);
        }

        if (preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/', $text, $parts) !== 1) {
            throw self::invalid($field);
        }
        $fraction = $parts[3] ?? '';
        if (strlen($fraction) > self::MAX_SCALE) {
            throw new ValidationException(
                $field,
                'O valor decimal não pode exceder cinco casas decimais.',
                'decimal.scale_exceeded'
            );
        }

        $integer = ltrim($parts[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($fraction, '0');
        $negative = $parts[1] === '-' && ($integer !== '0' || $fraction !== '');

        return ($negative ? '-' : '') . $integer . ($fraction === '' ? '' : '.' . $fraction);
    }

    /**
     * Converte o decimal numa unidade inteira, arredondando half-up.
     */
    public function toScaledInteger(int $scale): int
    {
        if ($scale < 0 || $scale > self::MAX_SCALE) {
            throw new \InvalidArgumentException('A escala decimal deve estar entre zero e cinco.');
        }

        $negative = str_starts_with($this->value, '-');
        $unsigned = $negative ? substr($this->value, 1) : $this->value;
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $kept = substr(str_pad($fraction, $scale, '0'), 0, $scale);
        $digits = ltrim($integer . $kept, '0');
        $digits = $digits === '' ? '0' : $digits;
        $next = $fraction[$scale] ?? '0';
        if ($next >= '5') {
            $digits = self::increment($digits);
        }
        if (
            strlen($digits) > strlen((string) PHP_INT_MAX)
            || (strlen($digits) === strlen((string) PHP_INT_MAX) && strcmp($digits, (string) PHP_INT_MAX) > 0)
        ) {
            throw new \OverflowException('O valor decimal excede a capacidade inteira da plataforma.');
        }

        $result = (int) $digits;
        return $negative ? -$result : $result;
    }

    public function format(int $scale = 2): string
    {
        $units = $this->toScaledInteger($scale);
        $negative = $units < 0;
        $digits = str_pad((string) abs($units), $scale + 1, '0', STR_PAD_LEFT);
        $integer = $scale === 0 ? $digits : substr($digits, 0, -$scale);
        $fraction = $scale === 0 ? '' : substr($digits, -$scale);

        return ($negative ? '-' : '') . $integer . ($fraction === '' ? '' : '.' . $fraction);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function increment(string $digits): string
    {
        $carry = 1;
        $result = '';
        for ($index = strlen($digits) - 1; $index >= 0; --$index) {
            $value = (int) $digits[$index] + $carry;
            $result = ($value % 10) . $result;
            $carry = intdiv($value, 10);
        }

        return ($carry === 1 ? '1' : '') . $result;
    }

    private static function invalid(string $field): ValidationException
    {
        return new ValidationException($field, 'O valor decimal é inválido.', 'decimal.invalid');
    }
}
