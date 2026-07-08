<?php

declare(strict_types=1);

namespace Kowts\Efatura\Domain;

use DateTimeImmutable;
use DateTimeInterface;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Identificador determinístico de um evento fiscal.
 */
final class EventId
{
    public static function build(
        int $repositoryCode,
        DateTimeInterface|string $issueDateTime,
        string $transmitterNif
    ): string {
        if ($repositoryCode < 1 || $repositoryCode > 3) {
            throw new ValidationException('repositoryCode', 'O código do repositório deve ser 1, 2 ou 3.');
        }

        EfaturaConfig::assertNif($transmitterNif, 'transmitterNif');
        $date = $issueDateTime instanceof DateTimeInterface
            ? $issueDateTime
            : new DateTimeImmutable($issueDateTime);

        return 'CV' . $repositoryCode . $date->format('ymdHis') . $transmitterNif;
    }

    public static function isValid(string $eventId): bool
    {
        if (preg_match('/^CV([123])(\d{2})(\d{2})(\d{2})(\d{6})([1-9]\d{8})$/', $eventId, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[3], (int) $parts[4], 2000 + (int) $parts[2])
            && (int) substr($parts[5], 0, 2) < 24
            && (int) substr($parts[5], 2, 2) < 60
            && (int) substr($parts[5], 4, 2) < 60;
    }
}
