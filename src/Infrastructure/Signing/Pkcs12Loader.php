<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Signing;

use Kowts\Efatura\Exception\ValidationException;

/**
 * Extrai certificado, chave privada e cadeia adicional de um ficheiro PKCS#12/PFX.
 */
final class Pkcs12Loader
{
    /**
     * @return array{certificate:string, privateKey:string, extraCertificates:list<string>}
     */
    public function load(string $contents, string $password): array
    {
        $certificates = [];
        if (!openssl_pkcs12_read($contents, $certificates, $password)) {
            throw new ValidationException(
                'pkcs12',
                'Não foi possível abrir o PKCS#12; confirme o ficheiro e a palavra-passe.',
                'certificate.pkcs12_invalid'
            );
        }
        if (!isset($certificates['cert'], $certificates['pkey'])) {
            throw new ValidationException('pkcs12', 'O PKCS#12 não contém certificado e chave privada.');
        }
        $extra = $certificates['extracerts'] ?? [];
        $extra = is_array($extra) ? array_values(array_map('strval', $extra)) : [];

        return [
            'certificate' => (string) $certificates['cert'],
            'privateKey' => (string) $certificates['pkey'],
            'extraCertificates' => $extra,
        ];
    }
}
