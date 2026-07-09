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

    /**
     * @return array{country:string, repositoryCode:int, issueDateTime:string, transmitterNif:string}
     */
    public static function parse(string $eventId): array
    {
        if (!self::isValid($eventId)) {
            throw new ValidationException('eventId', 'O identificador do evento é inválido.', 'event.id_invalid');
        }

        return [
            'country' => substr($eventId, 0, 2),
            'repositoryCode' => (int) substr($eventId, 2, 1),
            'issueDateTime' => '20' . substr($eventId, 3, 2) . '-' . substr($eventId, 5, 2) . '-'
                . substr($eventId, 7, 2) . 'T' . substr($eventId, 9, 2) . ':' . substr($eventId, 11, 2)
                . ':' . substr($eventId, 13, 2),
            'transmitterNif' => substr($eventId, 15, 9),
        ];
    }
}
