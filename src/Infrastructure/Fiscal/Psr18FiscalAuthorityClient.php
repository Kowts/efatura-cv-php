<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Fiscal;

use Kowts\Efatura\Contract\DocumentStatusClient;
use Kowts\Efatura\Contract\EmitterAuthorizationClient;
use Kowts\Efatura\Contract\SoftwareRegistryClient;
use Kowts\Efatura\Contract\TaxpayerRegistryClient;
use Kowts\Efatura\Fiscal\RegistryResult;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Cliente PSR-18 com rotas configuráveis para serviços PE/DNRE.
 *
 * Os valores predefinidos são convenções; confirme as rotas com o serviço
 * contratado antes de as usar em produção.
 */
final class Psr18FiscalAuthorityClient implements
    TaxpayerRegistryClient,
    SoftwareRegistryClient,
    EmitterAuthorizationClient,
    DocumentStatusClient
{
    /**
     * @param array{taxpayer?:string, software?:string, authorization?:string, document?:string} $routes
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requests,
        private readonly string $baseUrl,
        private readonly array $routes = []
    ) {
    }

    public function lookupTaxpayer(string $nif, ?string $accessToken = null): RegistryResult
    {
        return $this->get($this->route('taxpayer', '/v1/taxpayers/{nif}'), ['nif' => $nif], $accessToken);
    }

    public function lookupSoftware(string $code, ?string $accessToken = null): RegistryResult
    {
        return $this->get($this->route('software', '/v1/software/{code}'), ['code' => $code], $accessToken);
    }

    public function checkEmitterAuthorization(
        string $transmitterNif,
        string $emitterNif,
        ?string $accessToken = null
    ): RegistryResult {
        return $this->get(
            $this->route('authorization', '/v1/transmitters/{transmitter}/emitters/{emitter}'),
            ['transmitter' => $transmitterNif, 'emitter' => $emitterNif],
            $accessToken
        );
    }

    public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult
    {
        return $this->get($this->route('document', '/v1/dfe/{iud}'), ['iud' => $iud], $accessToken);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function get(string $route, array $parameters, ?string $accessToken): RegistryResult
    {
        foreach ($parameters as $name => $value) {
            $route = str_replace('{' . $name . '}', rawurlencode($value), $route);
        }
        $request = $this->requests->createRequest('GET', rtrim($this->baseUrl, '/') . '/' . ltrim($route, '/'))
            ->withHeader('Accept', 'application/json');
        if ($accessToken !== null && $accessToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $accessToken);
        }
        $response = $this->client->sendRequest($request);
        $body = json_decode((string) $response->getBody(), true);
        $data = is_array($body) ? $body : [];

        if ($response->getStatusCode() === 404) {
            return new RegistryResult(false, null, $data);
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return new RegistryResult(false, null, $data, [
                'A autoridade fiscal respondeu com o estado HTTP ' . $response->getStatusCode() . '.',
            ]);
        }
        $found = isset($data['found']) ? (bool) $data['found'] : true;
        $active = array_key_exists('active', $data) ? (bool) $data['active'] : null;

        return new RegistryResult($found, $active, $data);
    }

    private function route(string $name, string $default): string
    {
        return $this->routes[$name] ?? $default;
    }
}
