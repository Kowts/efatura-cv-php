<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

/**
 * Regista tentativas de submissão para evitar reenvios acidentais.
 */
interface SubmissionRegistry
{
    /**
     * Reserva um digest e devolve falso quando este já foi submetido.
     */
    public function claim(string $digest): bool;
}
