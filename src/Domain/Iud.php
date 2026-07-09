<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

use DateTimeImmutable;
use DateTimeInterface;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Gera, valida e interpreta o Identificador Único de Documento (IUD).
 */
final class Iud
{
    public const LENGTH = 45;

    public static function build(
        int $repositoryCode,
        DateTimeInterface|string $issueDate,
        string $emitterNif,
        string $led,
        DocumentType $documentType,
        int|string $documentNumber,
        int|string|null $randomCode = null
    ): string {
        if ($repositoryCode < 1 || $repositoryCode > 3) {
            throw new ValidationException('repositoryCode', 'O código do repositório deve ser 1, 2 ou 3.');
        }

        EfaturaConfig::assertNif($emitterNif, 'emitterNif');

        $date = self::normaliseDate($issueDate);
        $year = (int) $date->format('Y');
        if ($year < 2000 || $year > 2099) {
            throw new ValidationException(
                'issueDate',
                'A data do IUD deve estar compreendida entre 2000 e 2099.',
                'iud.issue_date_out_of_range'
            );
        }
        $payload = (string) $repositoryCode
            . $date->format('ymd')
            . $emitterNif
            . self::fixedDigits($led, 5, 'led')
            . $documentType->iudCode()
            . self::fixedDigits($documentNumber, 9, 'documentNumber')
            . self::fixedDigits($randomCode ?? random_int(0, 9_999_999_999), 10, 'randomCode');

        return 'CV' . $payload . self::luhnDigit($payload);
    }

    public static function isValid(string $iud): bool
    {
        if (
            preg_match(
                '/^CV([1-3])(\d{2})(\d{2})(\d{2})([1-9]\d{8})(\d{5})(\d{2})(\d{9})(\d{10})(\d)$/',
                $iud,
                $parts
            ) !== 1
        ) {
            return false;
        }

        if (!checkdate((int) $parts[3], (int) $parts[4], 2000 + (int) $parts[2])) {
            return false;
        }

        try {
            DocumentType::fromCode($parts[7]);
        } catch (ValidationException) {
            return false;
        }

        return self::luhnIsValid(substr($iud, 2));
    }

    /**
     * @return array{country:string, repositoryCode:int, issueDate:string, emitterNif:string,
     *     led:string, documentTypeCode:string, documentNumber:string, randomCode:string, checkDigit:string}
     */
    public static function parse(string $iud): array
    {
        if (!self::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD é inválido.', 'iud.invalid');
        }

        return [
            'country' => substr($iud, 0, 2),
            'repositoryCode' => (int) substr($iud, 2, 1),
            'issueDate' => '20' . substr($iud, 3, 2) . '-' . substr($iud, 5, 2) . '-' . substr($iud, 7, 2),
            'emitterNif' => substr($iud, 9, 9),
            'led' => substr($iud, 18, 5),
            'documentTypeCode' => substr($iud, 23, 2),
            'documentNumber' => substr($iud, 25, 9),
            'randomCode' => substr($iud, 34, 10),
            'checkDigit' => substr($iud, 44, 1),
        ];
    }

    public static function luhnDigit(string $payload): string
    {
        if (preg_match('/^[0-9]+$/', $payload) !== 1) {
            throw new ValidationException('payload', 'O valor do cálculo Luhn deve conter apenas algarismos.');
        }

        $sum = 0;
        $parity = (strlen($payload) + 1) % 2;

        foreach (str_split($payload) as $index => $character) {
            $digit = (int) $character;

            if ($index % 2 === $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    private static function luhnIsValid(string $value): bool
    {
        return hash_equals(substr($value, -1), self::luhnDigit(substr($value, 0, -1)));
    }

    private static function normaliseDate(DateTimeInterface|string $value): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new ValidationException('issueDate', 'A data de emissão deve usar o formato AAAA-MM-DD.');
        }

        return $date;
    }

    private static function fixedDigits(int|string $value, int $length, string $field): string
    {
        $digits = trim((string) $value);

        if (preg_match('/^[0-9]+$/', $digits) !== 1 || strlen($digits) > $length) {
            throw new ValidationException(
                $field,
                "O campo {$field} deve conter, no máximo, {$length} algarismos.",
                "iud.{$field}_invalid"
            );
        }

        return str_pad($digits, $length, '0', STR_PAD_LEFT);
    }
}
