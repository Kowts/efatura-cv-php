<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use Kowts\Efatura\Http\SubmissionResult;

/**
 * Envia pacotes directamente para a plataforma electrónica.
 */
interface PlatformTransport
{
    public function submit(
        string $baseUrl,
        string $accessToken,
        int $repositoryCode,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult;
}
