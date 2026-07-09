<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class SequenceClient implements ClientInterface
{
    public int $calls = 0;

    /**
     * @param list<ResponseInterface> $responses
     */
    public function __construct(private array $responses)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->responses[$this->calls] ?? null;
        $this->calls++;

        return $response ?? throw new RuntimeException('Não existe resposta configurada.');
    }
}
