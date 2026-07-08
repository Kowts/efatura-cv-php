<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

use Kowts\Efatura\Contract\DocumentStatusClient;
use Kowts\Efatura\Contract\EmitterAuthorizationClient;
use Kowts\Efatura\Contract\SoftwareRegistryClient;
use Kowts\Efatura\Contract\TaxpayerRegistryClient;
use Kowts\Efatura\Fiscal\RegistryResult;

final class StubFiscalRegistry implements
    TaxpayerRegistryClient,
    SoftwareRegistryClient,
    EmitterAuthorizationClient,
    DocumentStatusClient
{
    public function __construct(private readonly bool $approved = true)
    {
    }

    public function lookupTaxpayer(string $nif, ?string $accessToken = null): RegistryResult
    {
        return $this->result(['nif' => $nif]);
    }

    public function lookupSoftware(string $code, ?string $accessToken = null): RegistryResult
    {
        return $this->result(['code' => $code]);
    }

    public function checkEmitterAuthorization(
        string $transmitterNif,
        string $emitterNif,
        ?string $accessToken = null
    ): RegistryResult {
        return $this->result(['transmitter' => $transmitterNif, 'emitter' => $emitterNif]);
    }

    public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult
    {
        return $this->result(['iud' => $iud]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function result(array $data): RegistryResult
    {
        return new RegistryResult($this->approved, $this->approved, $data);
    }
}
