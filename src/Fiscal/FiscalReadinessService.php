<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Contract\EmitterAuthorizationClient;
use Kowts\Efatura\Contract\SoftwareRegistryClient;
use Kowts\Efatura\Contract\TaxpayerRegistryClient;
use Kowts\Efatura\Domain\Data\FiscalDocument;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Agrega verificações externas necessárias antes de uma emissão real.
 */
final class FiscalReadinessService
{
    public function __construct(
        private readonly EfaturaConfig $config,
        private readonly TaxpayerRegistryClient $taxpayers,
        private readonly SoftwareRegistryClient $software,
        private readonly EmitterAuthorizationClient $authorizations
    ) {
    }

    /**
     * @return array{ready:bool, checks:array<string, RegistryResult>, issues:list<string>}
     */
    public function validate(FiscalDocument $document, ?string $accessToken = null): array
    {
        if ($document->emitter->taxId === null) {
            throw new ValidationException('emitter.taxId', 'O NIF do emitente é obrigatório.');
        }
        $emitterNif = $document->emitter->taxId->value;
        $checks = [
            'transmitter' => $this->taxpayers->lookupTaxpayer($this->config->transmitterNif, $accessToken),
            'emitter' => $this->taxpayers->lookupTaxpayer($emitterNif, $accessToken),
            'software' => $this->software->lookupSoftware($this->config->softwareCode, $accessToken),
            'authorization' => $this->authorizations->checkEmitterAuthorization(
                $this->config->transmitterNif,
                $emitterNif,
                $accessToken
            ),
        ];
        if ($document->receiver?->taxId?->countryCode === 'CV') {
            $checks['receiver'] = $this->taxpayers->lookupTaxpayer(
                $document->receiver->taxId->value,
                $accessToken
            );
        }
        $issues = [];
        foreach ($checks as $name => $result) {
            if (!$result->found || $result->active === false) {
                $issues[] = "A verificação {$name} não foi aprovada.";
            }
            foreach ($result->issues as $issue) {
                $issues[] = "{$name}: {$issue}";
            }
        }

        return ['ready' => $issues === [], 'checks' => $checks, 'issues' => $issues];
    }
}
