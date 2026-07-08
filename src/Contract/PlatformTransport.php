<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

/**
 * Envia pacotes directamente para a plataforma electrónica.
 */
interface PlatformTransport
{
    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function submit(string $baseUrl, string $accessToken, int $repositoryCode, string $zip): array;
}
