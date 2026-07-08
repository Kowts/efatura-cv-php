<?php

declare(strict_types=1);

namespace Kowts\Efatura\Validation;

use DateTimeImmutable;
use DateTimeZone;
use Kowts\Efatura\Contract\Clock;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Aplica as janelas temporais definidas para emissão online e em contingência.
 */
final class IssueDateValidator
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function validate(string $issueDate, ?string $issueTime, EmissionMode $mode): DateTimeImmutable
    {
        $issuedAt = $this->parse($issueDate, $issueTime);
        $now = $this->clock->now();

        if ($mode === EmissionMode::Online) {
            if ($issuedAt < $now->modify('-24 hours') || $issuedAt > $now->modify('+1 hour')) {
                throw new ValidationException(
                    'issueDate',
                    'Na emissão online, a data deve estar entre 24 horas antes e 1 hora depois da hora do SFECV.',
                    'issue_date.online_tolerance'
                );
            }

            return $issuedAt;
        }

        if ($issuedAt < $now->modify('-7 days')) {
            throw new ValidationException(
                'issueDate',
                'Na emissão em contingência, a data não pode ter mais de sete dias.',
                'issue_date.contingency_tolerance'
            );
        }

        return $issuedAt;
    }

    private function parse(string $date, ?string $time): DateTimeImmutable
    {
        $value = $date . 'T' . ($time ?? '00:00:00');
        $parsed = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s',
            $value,
            new DateTimeZone('Atlantic/Cape_Verde')
        );
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new ValidationException(
                'issueDate',
                'A data e a hora de emissão são inválidas.',
                'issue_date.invalid'
            );
        }

        return $parsed;
    }
}
