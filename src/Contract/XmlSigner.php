<?php

declare(strict_types=1);

namespace Kowts\Efatura\Contract;

use DateTimeInterface;

/**
 * Assina um documento XML e devolve os metadados da assinatura.
 */
interface XmlSigner
{
    /**
     * @return array{xml:string, algorithm:string, profile:string, certificateFingerprint:string}
     */
    public function sign(
        string $xml,
        string $certificate,
        string $privateKey,
        ?string $privateKeyPassword = null,
        ?DateTimeInterface $signingTime = null
    ): array;
}
