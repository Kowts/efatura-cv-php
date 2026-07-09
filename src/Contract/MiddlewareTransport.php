<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Http\SubmissionResult;

/**
 * Envia pacotes DFE para um middleware e-Fatura.
 */
interface MiddlewareTransport
{
    public function submit(
        string $baseUrl,
        string $transmitterKey,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult;
}
