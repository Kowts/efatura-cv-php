<?php

declare(strict_types=1);

namespace Kowts\Efatura\Fiscal;

/**
 * Resultado normalizado do pedido de autorização para autofacturação.
 *
 * O código recebido por SMS/email pelo vendedor deve ser recolhido pelo
 * software e indicado no bloco SelfBilling do DFE juntamente com authorizationId.
 */
final class SelfBillingAuthorizationResult
{
    /**
     * @param list<string> $messages
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        public readonly bool $succeeded,
        public readonly ?string $authorizationId = null,
        public readonly ?int $authorizationCodeExpirationSeconds = null,
        public readonly ?string $iud = null,
        public readonly ?string $serie = null,
        public readonly ?string $ledCode = null,
        public readonly ?int $documentNumber = null,
        public readonly array $messages = [],
        public readonly array $rawData = []
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromPlatformResponse(array $data): self
    {
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

        return new self(
            succeeded: (bool) ($data['succeeded'] ?? false),
            authorizationId: self::stringOrNull($payload['authorizationId'] ?? null),
            authorizationCodeExpirationSeconds: self::intOrNull(
                $payload['authorizationCodeExpirationSeconds'] ?? null
            ),
            iud: self::stringOrNull($payload['iud'] ?? null),
            serie: self::stringOrNull($payload['serie'] ?? null),
            ledCode: self::stringOrNull($payload['ledCode'] ?? null),
            documentNumber: self::intOrNull($payload['documentNumber'] ?? null),
            messages: self::messages($data['messages'] ?? []),
            rawData: $data
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @return list<string>
     */
    private static function messages(mixed $messages): array
    {
        if (!is_array($messages)) {
            return [];
        }

        $result = [];
        foreach ($messages as $message) {
            if (is_scalar($message)) {
                $result[] = (string) $message;
            } elseif (is_array($message)) {
                $text = $message['message'] ?? $message['text'] ?? $message['description'] ?? null;
                if (is_scalar($text)) {
                    $result[] = (string) $text;
                }
            }
        }

        return $result;
    }
}
