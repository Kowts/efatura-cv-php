<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Fiscal;

use Kowts\Efatura\Contract\DocumentStatusClient;
use Kowts\Efatura\Contract\EmitterAuthorizationClient;
use Kowts\Efatura\Contract\SelfBillingAuthorizationClient;
use Kowts\Efatura\Contract\SoftwareRegistryClient;
use Kowts\Efatura\Contract\TaxpayerRegistryClient;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\Decimal;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Fiscal\RegistryResult;
use Kowts\Efatura\Fiscal\SelfBillingAuthorizationResult;
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
    DocumentStatusClient,
    SelfBillingAuthorizationClient
{
    /**
     * @param array{taxpayer?:string, software?:string, authorization?:string, document?:string,
     *     selfBillingAuthorization?:string} $routes
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

    public function authorizeSelfBilling(
        string $sellerTaxId,
        DocumentType $documentType,
        string $mobilePhoneNumber,
        int|float|string $totalAmount,
        ?string $accessToken = null
    ): SelfBillingAuthorizationResult {
        $this->assertSelfBillingRequest($sellerTaxId, $documentType, $mobilePhoneNumber, $totalAmount);
        $payload = [
            'taxId' => $sellerTaxId,
            'documentTypeCode' => $documentType->code(),
            'mobilePhoneNumber' => trim($mobilePhoneNumber),
            'totalAmount' => Decimal::normalise($totalAmount, 'totalAmount'),
        ];

        $response = $this->postJson(
            $this->route('selfBillingAuthorization', '/v1/dfe/self-billing/authorize'),
            $payload,
            $accessToken
        );

        return SelfBillingAuthorizationResult::fromPlatformResponse($response);
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

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $route, array $payload, ?string $accessToken): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $request = $this->requests->createRequest('POST', rtrim($this->baseUrl, '/') . '/' . ltrim($route, '/'))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
        if ($accessToken !== null && $accessToken !== '') {
            $request = $request->withHeader('Authorization', 'Bearer ' . $accessToken);
        }
        $request->getBody()->write($body);

        $response = $this->client->sendRequest($request);
        $decoded = json_decode((string) $response->getBody(), true);
        $data = is_array($decoded) ? $decoded : [];

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $data['succeeded'] = false;
            $data['messages'] ??= [
                'A autoridade fiscal respondeu com o estado HTTP ' . $response->getStatusCode() . '.',
            ];
        }

        return $data;
    }

    private function assertSelfBillingRequest(
        string $sellerTaxId,
        DocumentType $documentType,
        string $mobilePhoneNumber,
        int|float|string $totalAmount
    ): void {
        EfaturaConfig::assertNif($sellerTaxId, 'sellerTaxId');
        if (!in_array($documentType->code(), [1, 2, 4, 5, 6, 8], true)) {
            throw new ValidationException(
                'documentType',
                'A autofacturação só aceita os tipos de DFE 1, 2, 4, 5, 6 e 8.',
                'self_billing.document_type_invalid'
            );
        }
        if (trim($mobilePhoneNumber) === '') {
            throw new ValidationException(
                'mobilePhoneNumber',
                'O telemóvel do vendedor é obrigatório para pedir autorização de autofacturação.',
                'self_billing.mobile_required'
            );
        }
        if (Decimal::from($totalAmount, 'totalAmount')->toScaledInteger(5) <= 0) {
            throw new ValidationException(
                'totalAmount',
                'O total a pagar do DFE deve ser superior a zero.',
                'self_billing.total_amount_invalid'
            );
        }
    }

    private function route(string $name, string $default): string
    {
        return $this->routes[$name] ?? $default;
    }
}
