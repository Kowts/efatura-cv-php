<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Kowts\Efatura\Contract\PlatformTransport;
use Kowts\Efatura\Http\SubmissionResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Transporte PSR-18 multipart para a plataforma e-Fatura.
 */
final class Psr18PlatformTransport implements PlatformTransport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requests,
        private readonly StreamFactoryInterface $streams
    ) {
    }

    public function submit(
        string $baseUrl,
        string $accessToken,
        int $repositoryCode,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult {
        $boundary = 'efatura-' . bin2hex(random_bytes(16));
        $body = "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\"dfe.zip\"\r\n"
            . "Content-Type: application/octet-stream\r\n\r\n"
            . $zip . "\r\n--{$boundary}--\r\n";
        $request = $this->requests->createRequest('POST', rtrim($baseUrl, '/') . $endpointPath)
            ->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->withHeader('cv-ef-repository-code', (string) $repositoryCode)
            ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary)
            ->withBody($this->streams->createStream($body));
        $response = $this->client->sendRequest($request);
        $rawBody = (string) $response->getBody();
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

        return new SubmissionResult(
            $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            ResponseParser::parse($rawBody, $response->getHeaderLine('Content-Type')),
            $rawBody,
            $headers
        );
    }
}
