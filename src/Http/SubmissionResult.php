<?php

declare(strict_types=1);

namespace Kowts\Efatura\Http;

use JsonSerializable;

/**
 * Resultado HTTP imutável de uma submissão fiscal.
 */
final class SubmissionResult implements JsonSerializable
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly bool $ok,
        public readonly int $status,
        public readonly string $statusText,
        public readonly mixed $body,
        public readonly string $rawBody,
        public readonly array $headers = []
    ) {
    }

    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'status' => $this->status,
            'statusText' => $this->statusText,
            'body' => $this->body,
            'rawBody' => $this->rawBody,
            'headers' => $this->headers,
        ];
    }

    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
