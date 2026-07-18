<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use CURLFile;
use CurlHandle;
use Kowts\Efatura\Exception\EfaturaException;
use Kowts\Efatura\Http\SubmissionResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cliente HTTP interno, pequeno e substituível, baseado na extensão cURL.
 */
final class CurlClient
{
    public function __construct(
        private readonly int $timeout = 60,
        private readonly int $connectTimeout = 10,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @param array<string, string> $headers
     * @param string|array<string, CURLFile|string> $body
     */
    public function post(string $url, array $headers, string|array $body): SubmissionResult
    {
        $handle = curl_init($url);
        if (!$handle instanceof CurlHandle) {
            throw new EfaturaException('Não foi possível iniciar o cliente HTTP.');
        }

        $responseHeaders = [];
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_HTTPHEADER => array_map(
                static fn (string $key, string $value): string => "{$key}: {$value}",
                array_keys($headers),
                array_values($headers)
            ),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ]);

        try {
            $rawBody = curl_exec($handle);
            if ($rawBody === false) {
                throw new EfaturaException('Erro de comunicação HTTP: ' . curl_error($handle));
            }
            if (!is_string($rawBody)) {
                throw new EfaturaException('O cliente HTTP devolveu um corpo de resposta inesperado.');
            }
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);

            $this->logger->info('Submissão e-Fatura concluída.', [
                'host' => parse_url($url, PHP_URL_HOST),
                'status' => $status,
                'bytes' => strlen($rawBody),
            ]);

            return new SubmissionResult(
                $status >= 200 && $status < 300,
                $status,
                self::statusText($status),
                ResponseParser::parse($rawBody, $contentType),
                $rawBody,
                $responseHeaders
            );
        } finally {
            curl_close($handle);
        }
    }

    private static function statusText(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => '',
        };
    }
}
