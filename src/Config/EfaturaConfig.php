<?php

declare(strict_types=1);

namespace Kowts\Efatura\Config;

use Kowts\Efatura\Domain\Environment;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Configuração imutável de uma instância e-Fatura.
 */
final class EfaturaConfig
{
    public const DEFAULT_DFA_URL = 'https://pe.efatura.cv/dfe/view';
    public const DEFAULT_PLATFORM_URL = 'https://services.efatura.cv';

    /**
     * @param array<string, mixed>|null $emitter Dados predefinidos do emitente.
     */
    public function __construct(
        public readonly string $transmitterNif,
        public readonly string $transmitterLed,
        public readonly string $softwareCode,
        public readonly string $softwareName,
        public readonly string $softwareVersion,
        public readonly string $middlewareBaseUrl,
        public readonly ?string $transmitterKey = null,
        public readonly ?string $defaultSerie = null,
        public readonly ?array $emitter = null,
        public readonly string $platformBaseUrl = self::DEFAULT_PLATFORM_URL,
        public readonly string $dfaBaseUrl = self::DEFAULT_DFA_URL,
        public readonly Environment $environment = Environment::Test,
        public readonly string $middlewareDfePath = '/v1/dfe',
        public readonly string $platformDfePath = '/v1/dfe'
    ) {
        self::assertNif($transmitterNif, 'transmitterNif');
        self::assertRequired($transmitterLed, 'transmitterLed');
        self::assertRequired($softwareCode, 'softwareCode');
        self::assertRequired($softwareName, 'softwareName');
        self::assertRequired($softwareVersion, 'softwareVersion');
        self::assertUrl($middlewareBaseUrl, 'middlewareBaseUrl');
        self::assertUrl($platformBaseUrl, 'platformBaseUrl');
        self::assertUrl($dfaBaseUrl, 'dfaBaseUrl');
        self::assertEndpointPath($middlewareDfePath, 'middlewareDfePath');
        self::assertEndpointPath($platformDfePath, 'platformDfePath');
    }

    public function repositoryCode(): int
    {
        return $this->environment->repositoryCode();
    }

    /**
     * @return array<string, mixed>
     */
    public function emitterOrFail(): array
    {
        if ($this->emitter === null) {
            throw new ValidationException('emitter', 'O emitente não foi configurado.', 'config.emitter_required');
        }

        $emitter = $this->emitter;
        $emitter['taxId'] ??= ['countryCode' => 'CV', 'value' => $this->transmitterNif];

        return $emitter;
    }

    public static function assertNif(string $nif, string $field = 'nif'): void
    {
        if (preg_match('/^[1-9][0-9]{8}$/', $nif) !== 1) {
            throw new ValidationException(
                $field,
                'O NIF cabo-verdiano deve ter nove algarismos e não pode começar por zero.',
                'tax_id.cv_invalid'
            );
        }
    }

    private static function assertRequired(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new ValidationException($field, "O campo {$field} é obrigatório.", 'config.required');
        }
    }

    private static function assertUrl(string $value, string $field): void
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException($field, "O campo {$field} deve conter um URL válido.", 'config.url_invalid');
        }
    }

    private static function assertEndpointPath(string $value, string $field): void
    {
        if (
            preg_match('#^/[A-Za-z0-9._~!$&\'()*+,;=:@%/-]+$#', $value) !== 1
            || str_contains($value, '//')
        ) {
            throw new ValidationException(
                $field,
                "O campo {$field} deve conter um caminho HTTP absoluto e sem query string.",
                'config.endpoint_path_invalid'
            );
        }
    }
}
