<?php

declare(strict_types=1);

namespace Kowts\Efatura;

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\Environment;

/**
 * Converte configuração de frameworks e ficheiros em objectos da biblioteca.
 */
final class EfaturaFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): Efatura
    {
        $environment = $config['environment'] ?? Environment::Test;
        if (!$environment instanceof Environment) {
            $environment = Environment::from(strtoupper((string) $environment));
        }

        return new Efatura(new EfaturaConfig(
            transmitterNif: (string) ($config['transmitter_nif'] ?? ''),
            transmitterLed: (string) ($config['transmitter_led'] ?? ''),
            softwareCode: (string) ($config['software_code'] ?? ''),
            softwareName: (string) ($config['software_name'] ?? ''),
            softwareVersion: (string) ($config['software_version'] ?? ''),
            middlewareBaseUrl: isset($config['middleware_base_url'])
                ? (string) $config['middleware_base_url']
                : null,
            transmitterKey: isset($config['transmitter_key']) ? (string) $config['transmitter_key'] : null,
            defaultSerie: isset($config['default_serie']) ? (string) $config['default_serie'] : null,
            emitter: is_array($config['emitter'] ?? null) ? $config['emitter'] : null,
            platformBaseUrl: (string) ($config['platform_base_url'] ?? EfaturaConfig::DEFAULT_PLATFORM_URL),
            dfaBaseUrl: (string) ($config['dfa_base_url'] ?? EfaturaConfig::DEFAULT_DFA_URL),
            environment: $environment,
            middlewareDfePath: (string) ($config['middleware_dfe_path'] ?? '/v1/dfe'),
            platformDfePath: (string) ($config['platform_dfe_path'] ?? '/v1/dfe'),
            middlewareEventPath: (string) ($config['middleware_event_path'] ?? '/v1/event'),
            platformEventPath: (string) ($config['platform_event_path'] ?? '/v1/event')
        ));
    }
}
