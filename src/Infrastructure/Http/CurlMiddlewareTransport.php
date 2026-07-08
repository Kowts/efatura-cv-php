<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Kowts\Efatura\Contract\MiddlewareTransport;

/**
 * Transporte para o endpoint /v1/dfe de um middleware.
 */
final class CurlMiddlewareTransport implements MiddlewareTransport
{
    public function __construct(private readonly CurlClient $client = new CurlClient())
    {
    }

    public function submit(string $baseUrl, string $transmitterKey, string $zip): array
    {
        return $this->client->post(
            rtrim($baseUrl, '/') . '/v1/dfe',
            [
                'Content-Type' => 'application/zip',
                'cv-ef-mw-core-transmitter-key' => $transmitterKey,
            ],
            $zip
        );
    }
}
