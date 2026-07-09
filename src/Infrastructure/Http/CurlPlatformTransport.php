<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use CURLFile;
use Kowts\Efatura\Contract\PlatformTransport;
use Kowts\Efatura\Exception\EfaturaException;
use Kowts\Efatura\Http\SubmissionResult;

/**
 * Transporte multipart para submissão directa à plataforma.
 */
final class CurlPlatformTransport implements PlatformTransport
{
    public function __construct(private readonly CurlClient $client = new CurlClient())
    {
    }

    public function submit(
        string $baseUrl,
        string $accessToken,
        int $repositoryCode,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult {
        $path = tempnam(sys_get_temp_dir(), 'efatura-platform-');
        if ($path === false || file_put_contents($path, $zip) === false) {
            throw new EfaturaException('Não foi possível preparar o ZIP para envio.');
        }

        try {
            return $this->client->post(
                rtrim($baseUrl, '/') . $endpointPath,
                [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'cv-ef-repository-code' => (string) $repositoryCode,
                ],
                ['file' => new CURLFile($path, 'application/octet-stream', 'dfe.zip')]
            );
        } finally {
            @unlink($path);
        }
    }
}
