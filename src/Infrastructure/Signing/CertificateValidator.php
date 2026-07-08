<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Signing;

use DateTimeImmutable;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Verifica o conteúdo, a validade temporal e a correspondência da chave privada.
 */
final class CertificateValidator
{
    /**
     * @return array{valid:bool, fingerprint:?string, validFrom:?string, validTo:?string, issues:list<string>}
     */
    public function validate(
        string $certificate,
        ?string $privateKey = null,
        ?string $privateKeyPassword = null,
        ?string $trustStore = null
    ): array {
        $resource = openssl_x509_read($certificate);
        if ($resource === false) {
            throw new ValidationException('certificate', 'O certificado X.509 é inválido.', 'certificate.invalid');
        }

        $parsed = openssl_x509_parse($resource);
        if ($parsed === false) {
            throw new ValidationException('certificate', 'Não foi possível interpretar o certificado.');
        }

        $issues = [];
        $now = time();
        $validFrom = isset($parsed['validFrom_time_t']) ? (int) $parsed['validFrom_time_t'] : null;
        $validTo = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : null;
        if ($validFrom !== null && $now < $validFrom) {
            $issues[] = 'O certificado ainda não é válido.';
        }
        if ($validTo !== null && $now > $validTo) {
            $issues[] = 'O certificado expirou.';
        }
        if ($privateKey !== null && !openssl_x509_check_private_key($resource, $privateKey)) {
            $key = openssl_pkey_get_private($privateKey, $privateKeyPassword ?? '');
            if ($key === false || !openssl_x509_check_private_key($resource, $key)) {
                $issues[] = 'A chave privada não corresponde ao certificado.';
            }
        }
        $keyUsage = (string) ($parsed['extensions']['keyUsage'] ?? '');
        if ($keyUsage !== '' && stripos($keyUsage, 'digital signature') === false) {
            $issues[] = 'O certificado não permite assinatura digital no campo Key Usage.';
        }
        if ($trustStore !== null) {
            if (!is_file($trustStore)) {
                $issues[] = 'O ficheiro da cadeia de confiança não existe.';
            } elseif (openssl_x509_checkpurpose($resource, X509_PURPOSE_ANY, [$trustStore]) !== true) {
                $issues[] = 'A cadeia de confiança do certificado não pôde ser validada.';
            }
        }

        return [
            'valid' => $issues === [],
            'fingerprint' => openssl_x509_fingerprint($resource, 'sha256') ?: null,
            'validFrom' => $validFrom === null ? null : (new DateTimeImmutable("@{$validFrom}"))->format(DATE_ATOM),
            'validTo' => $validTo === null ? null : (new DateTimeImmutable("@{$validTo}"))->format(DATE_ATOM),
            'issues' => $issues,
        ];
    }
}
