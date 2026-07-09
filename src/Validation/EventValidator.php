<?php

declare(strict_types=1);

namespace Kowts\Efatura\Validation;

use DateTimeImmutable;
use DateTimeZone;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EventType;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Valida eventos de anulação e inutilização antes da serialização.
 */
final class EventValidator
{
    /**
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function validate(array $event): array
    {
        $type = $event['type'] ?? $event['eventTypeCode'] ?? null;
        if (is_string($type)) {
            $type = EventType::tryFrom(strtoupper(trim($type)));
        }
        if (!$type instanceof EventType) {
            throw new ValidationException('event.type', 'O tipo de evento deve ser FDC ou UDN.', 'event.type_invalid');
        }

        $issueDateTime = trim((string) ($event['issueDateTime'] ?? ''));
        $date = $this->parseDateTime($issueDateTime);
        if ($date === null) {
            throw new ValidationException(
                'event.issueDateTime',
                'A data e hora do evento devem estar em formato ISO válido.',
                'event.issue_date_time_invalid'
            );
        }
        if ($issueDateTime === '' || $date->format('Y') < '2020') {
            throw new ValidationException('event.issueDateTime', 'A data e hora do evento são inválidas.');
        }

        $reason = trim((string) ($event['issueReasonDescription'] ?? ''));
        if ($reason === '' || mb_strlen($reason) > 300) {
            throw new ValidationException(
                'event.issueReasonDescription',
                'O motivo do evento é obrigatório e não pode exceder 300 caracteres.',
                'event.reason_invalid'
            );
        }

        $iuds = $event['iuds'] ?? $event['iud'] ?? [];
        $iuds = is_string($iuds) ? [$iuds] : $iuds;
        if (!is_array($iuds)) {
            throw new ValidationException('event.iuds', 'A lista de IUDs é inválida.');
        }
        foreach ($iuds as $index => $iud) {
            if (!is_string($iud) || !Iud::isValid($iud)) {
                throw new ValidationException("event.iuds.{$index}", 'O IUD do evento é inválido.', 'event.iud_invalid');
            }
        }

        $range = $event['range'] ?? null;
        if ($iuds !== [] && $range !== null) {
            throw new ValidationException(
                'event.range',
                'O evento deve indicar IUDs ou um intervalo, nunca ambos.',
                'event.target_conflict'
            );
        }
        if ($type === EventType::FiscalDocumentCancellation && $iuds === []) {
            throw new ValidationException('event.iuds', 'Um evento FDC exige pelo menos um IUD.', 'event.iuds_required');
        }
        if ($type === EventType::UnusedDocumentNumber) {
            $range = $this->validateRange($range);
        } elseif ($range !== null) {
            throw new ValidationException('event.range', 'Um evento FDC não aceita intervalo documental.');
        }

        $event['type'] = $type;
        $event['issueDateTime'] = $issueDateTime;
        $event['issueReasonDescription'] = $reason;
        $event['iuds'] = array_values($iuds);
        $event['range'] = $range;

        return $event;
    }

    private function parseDateTime(string $value): ?DateTimeImmutable
    {
        $formats = str_ends_with($value, 'Z') || preg_match('/[+-]\d{2}:\d{2}$/', $value) === 1
            ? ['!Y-m-d\TH:i:sP']
            : ['!Y-m-d\TH:i:s'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat(
                $format,
                $value,
                new DateTimeZone('Atlantic/Cape_Verde')
            );
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRange(mixed $range): array
    {
        if (!is_array($range)) {
            throw new ValidationException('event.range', 'Um evento UDN exige um intervalo documental.');
        }
        $led = trim((string) ($range['ledCode'] ?? ''));
        $serie = trim((string) ($range['serie'] ?? ''));
        $start = filter_var($range['documentNumberStart'] ?? null, FILTER_VALIDATE_INT);
        $end = filter_var($range['documentNumberEnd'] ?? null, FILTER_VALIDATE_INT);
        if (preg_match('/^\d{1,5}$/', $led) !== 1 || preg_match('/^[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*$/', $serie) !== 1) {
            throw new ValidationException('event.range', 'O LED ou a série do intervalo são inválidos.');
        }
        if ($start === false || $end === false || $start < 1 || $end > 999_999_999 || $end < $start) {
            throw new ValidationException(
                'event.range.documentNumberEnd',
                'O intervalo de números documentais é inválido.',
                'event.range_invalid'
            );
        }

        $documentType = $range['documentType'] ?? $range['documentTypeCode'] ?? null;
        if (!$documentType instanceof DocumentType) {
            $documentType = is_string($documentType) && DocumentType::tryFrom($documentType) !== null
                ? DocumentType::from($documentType)
                : DocumentType::fromCode((int) $documentType);
        }
        $year = $range['year'] ?? null;
        if ($year !== null && preg_match('/^20[2-9]\d$/', (string) $year) !== 1) {
            throw new ValidationException('event.range.year', 'O ano do intervalo é inválido.');
        }

        $range['ledCode'] = $led;
        $range['serie'] = $serie;
        $range['documentType'] = $documentType;
        $range['documentNumberStart'] = $start;
        $range['documentNumberEnd'] = $end;

        return $range;
    }
}
