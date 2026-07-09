<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Kowts\Efatura\Contract\MiddlewareTransport;
use Kowts\Efatura\Http\SubmissionResult;

/**
 * Transporte para um endpoint configurável de middleware.
 */
final class CurlMiddlewareTransport implements MiddlewareTransport
{
    public function __construct(private readonly CurlClient $client = new CurlClient())
    {
    }

    public function submit(
        string $baseUrl,
        string $transmitterKey,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult {
        return $this->client->post(
            rtrim($baseUrl, '/') . $endpointPath,
            [
                'Content-Type' => 'application/zip',
                'cv-ef-mw-core-transmitter-key' => $transmitterKey,
            ],
            $zip
        );
    }
}
