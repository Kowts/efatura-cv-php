<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

use Kowts\Efatura\Contract\DocumentStatusClient;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;
use Throwable;

/**
 * Confirma o estado remoto de uma submissão antes de qualquer reenvio.
 */
final class SubmissionReconciler
{
    public function __construct(private readonly DocumentStatusClient $client)
    {
    }

    public function reconcile(string $iud, ?string $accessToken = null): ReconciliationResult
    {
        if (!Iud::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD a reconciliar é inválido.');
        }

        try {
            $result = $this->client->lookupDocument($iud, $accessToken);
        } catch (Throwable) {
            return new ReconciliationResult(
                ReconciliationStatus::Indeterminate,
                issues: ['Não foi possível consultar o estado fiscal do documento.']
            );
        }

        if ($result->found) {
            return new ReconciliationResult(ReconciliationStatus::Confirmed, $result->data, $result->issues);
        }
        if ($result->issues !== []) {
            return new ReconciliationResult(ReconciliationStatus::Indeterminate, $result->data, $result->issues);
        }

        return new ReconciliationResult(ReconciliationStatus::NotFound, $result->data);
    }
}
