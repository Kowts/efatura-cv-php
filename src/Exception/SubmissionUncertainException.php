<?php

declare(strict_types=1);

namespace Kowts\Efatura\Exception;

use Throwable;

/**
 * Indica que não foi possível confirmar o resultado de uma submissão.
 */
final class SubmissionUncertainException extends EfaturaException
{
    public function __construct(
        public readonly string $channel,
        Throwable $previous
    ) {
        parent::__construct(
            'O resultado da submissão é incerto. Consulte o estado fiscal pelo IUD antes de reenviar.',
            0,
            $previous
        );
    }
}
