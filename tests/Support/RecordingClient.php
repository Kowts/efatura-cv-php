<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RecordingClient implements ClientInterface
{
    public ?RequestInterface $request = null;

    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        return $this->response;
    }
}
