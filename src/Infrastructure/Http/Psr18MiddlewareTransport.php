<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Kowts\Efatura\Contract\MiddlewareTransport;
use Kowts\Efatura\Http\SubmissionResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Transporte middleware interoperável com qualquer cliente PSR-18.
 */
final class Psr18MiddlewareTransport implements MiddlewareTransport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requests,
        private readonly StreamFactoryInterface $streams,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    public function submit(
        string $baseUrl,
        string $transmitterKey,
        string $zip,
        string $endpointPath = '/v1/dfe'
    ): SubmissionResult {
        $request = $this->requests->createRequest('POST', rtrim($baseUrl, '/') . $endpointPath)
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('cv-ef-mw-core-transmitter-key', $transmitterKey)
            ->withBody($this->streams->createStream($zip));
        $response = $this->client->sendRequest($request);
        $rawBody = (string) $response->getBody();
        $this->logger->info('Submissão e-Fatura PSR-18 concluída.', [
            'status' => $response->getStatusCode(),
            'bytes' => strlen($rawBody),
        ]);
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }
        $contentType = $response->getHeaderLine('Content-Type');

        return new SubmissionResult(
            $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            ResponseParser::parse($rawBody, $contentType),
            $rawBody,
            $headers
        );
    }
}
