<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Http;

use Closure;
use InvalidArgumentException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Repete pedidos HTTP idempotentes com espera exponencial limitada.
 *
 * Os pedidos POST nunca são repetidos, pois um timeout não prova que a
 * autoridade fiscal deixou de os processar.
 */
final class RetryingPsr18Client implements ClientInterface
{
    private readonly Closure $sleeper;

    /**
     * @param list<int> $retryableStatuses
     * @param null|Closure(int):void $sleeper Recebe o atraso em milissegundos.
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly int $maxAttempts = 3,
        private readonly int $initialDelayMs = 250,
        private readonly int $maximumDelayMs = 2_000,
        private readonly array $retryableStatuses = [425, 429, 500, 502, 503, 504],
        ?Closure $sleeper = null
    ) {
        if ($maxAttempts < 1 || $initialDelayMs < 0 || $maximumDelayMs < $initialDelayMs) {
            throw new InvalidArgumentException('A política de repetição HTTP é inválida.');
        }
        $this->sleeper = $sleeper ?? static function (int $milliseconds): void {
            usleep($milliseconds * 1_000);
        };
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if (!$this->isIdempotent($request)) {
            return $this->client->sendRequest($request);
        }

        for ($attempt = 1;; $attempt++) {
            try {
                $response = $this->client->sendRequest($request);
            } catch (ClientExceptionInterface $exception) {
                if ($attempt >= $this->maxAttempts) {
                    throw $exception;
                }
                ($this->sleeper)($this->delay($attempt));
                continue;
            }

            if (
                $attempt >= $this->maxAttempts
                || !in_array($response->getStatusCode(), $this->retryableStatuses, true)
            ) {
                return $response;
            }
            ($this->sleeper)($this->retryAfter($response) ?? $this->delay($attempt));
        }
    }

    private function isIdempotent(RequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'], true);
    }

    private function delay(int $attempt): int
    {
        return min($this->maximumDelayMs, $this->initialDelayMs * (2 ** ($attempt - 1)));
    }

    private function retryAfter(ResponseInterface $response): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        return min($this->maximumDelayMs, (int) $value * 1_000);
    }
}
