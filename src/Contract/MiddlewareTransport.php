<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

/**
 * Envia pacotes DFE para um middleware e-Fatura.
 */
interface MiddlewareTransport
{
    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function submit(string $baseUrl, string $transmitterKey, string $zip): array;
}
