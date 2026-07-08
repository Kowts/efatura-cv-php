<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use DOMDocument;
use DOMElement;
use Kowts\Efatura\Contract\XmlSigner;
use Kowts\Efatura\Exception\ValidationException;

/**
 * Assinador XAdES-BES enveloped com RSA-SHA256 e canonicalização C14N 1.0.
 */
final class XadesBesSigner implements XmlSigner
{
    private const DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const XADES = 'http://uri.etsi.org/01903/v1.3.2#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const DIGEST = 'http://www.w3.org/2001/04/xmlenc#sha256';
    private const RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    public function sign(
        string $xml,
        string $certificate,
        string $privateKey,
        ?string $privateKeyPassword = null,
        ?DateTimeInterface $signingTime = null
    ): array {
        $certificateResource = openssl_x509_read($certificate);
        $privateKeyResource = openssl_pkey_get_private($privateKey, $privateKeyPassword ?? '');
        if ($certificateResource === false) {
            throw new ValidationException('certificate', 'O certificado X.509 é inválido.', 'signing.certificate_invalid');
        }
        if ($privateKeyResource === false || !openssl_x509_check_private_key($certificateResource, $privateKeyResource)) {
            throw new ValidationException(
                'privateKey',
                'A chave privada é inválida ou não corresponde ao certificado.',
                'signing.private_key_invalid'
            );
        }

        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS) || !$document->documentElement instanceof DOMElement) {
            throw new ValidationException('xml', 'O XML a assinar é inválido.', 'signing.xml_invalid');
        }
        $root = $document->documentElement;
        $rootId = $root->getAttribute('Id');
        if ($rootId === '') {
            throw new ValidationException('xml.Id', 'O elemento raiz deve ter o atributo Id.', 'signing.root_id_required');
        }

        $signatureId = 'EmitterPartySignatureId';
        $dataReferenceId = 'DataReferenceId';
        $signedPropertiesId = 'SignedPropertiesId';
        $certificateDer = $this->certificateDer($certificate);
        $parsed = openssl_x509_parse($certificateResource);
        if ($parsed === false) {
            throw new ValidationException('certificate', 'Não foi possível interpretar o certificado.');
        }

        $signature = $document->createElementNS(self::DS, 'ds:Signature');
        $signature->setAttribute('Id', $signatureId);
        $signedInfo = $document->createElementNS(self::DS, 'ds:SignedInfo');
        $signature->appendChild($signedInfo);
        $signatureValue = $document->createElementNS(self::DS, 'ds:SignatureValue');
        $signature->appendChild($signatureValue);
        $signature->appendChild($this->keyInfo($document, $certificateDer));

        $object = $document->createElementNS(self::DS, 'ds:Object');
        $qualifying = $document->createElementNS(self::XADES, 'xades:QualifyingProperties');
        $qualifying->setAttribute('Target', '#' . $signatureId);
        $signedProperties = $this->signedProperties(
            $document,
            $certificateDer,
            $parsed,
            $signedPropertiesId,
            $dataReferenceId,
            $signingTime ?? new DateTimeImmutable()
        );
        $qualifying->appendChild($signedProperties);
        $object->appendChild($qualifying);
        $signature->appendChild($object);
        $root->appendChild($signature);

        // O digest do DFE ignora a própria assinatura por se tratar de uma assinatura enveloped.
        $rootClone = $root->cloneNode(true);
        if (!$rootClone instanceof DOMElement) {
            throw new ValidationException('xml', 'Não foi possível preparar o XML para assinatura.');
        }
        foreach (iterator_to_array($rootClone->getElementsByTagNameNS(self::DS, 'Signature')) as $node) {
            $node->parentNode?->removeChild($node);
        }
        $dataDigest = base64_encode(hash('sha256', $rootClone->C14N(false, false), true));
        $propertiesDigest = base64_encode(hash('sha256', $signedProperties->C14N(false, false), true));

        $signedInfo->appendChild($this->algorithmElement($document, 'CanonicalizationMethod', self::C14N));
        $signedInfo->appendChild($this->algorithmElement($document, 'SignatureMethod', self::RSA_SHA256));
        $signedInfo->appendChild(
            $this->reference($document, '#' . $rootId, $dataDigest, $dataReferenceId, true)
        );
        $signedInfo->appendChild(
            $this->reference(
                $document,
                '#' . $signedPropertiesId,
                $propertiesDigest,
                null,
                false,
                'http://uri.etsi.org/01903#SignedProperties'
            )
        );

        $canonicalSignedInfo = $signedInfo->C14N(false, false);
        $signatureBytes = '';
        if (!openssl_sign($canonicalSignedInfo, $signatureBytes, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new ValidationException('signature', 'Não foi possível criar a assinatura RSA-SHA256.');
        }
        $signatureValue->nodeValue = base64_encode($signatureBytes);

        return [
            'xml' => $document->saveXML() ?: '',
            'algorithm' => 'RSA-SHA256',
            'profile' => 'XAdES-BES',
            'certificateFingerprint' => openssl_x509_fingerprint($certificateResource, 'sha256') ?: '',
        ];
    }

    private function keyInfo(DOMDocument $document, string $certificateDer): DOMElement
    {
        $keyInfo = $document->createElementNS(self::DS, 'ds:KeyInfo');
        $x509Data = $document->createElementNS(self::DS, 'ds:X509Data');
        $x509Data->appendChild(
            $document->createElementNS(self::DS, 'ds:X509Certificate', base64_encode($certificateDer))
        );
        $keyInfo->appendChild($x509Data);
        return $keyInfo;
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function signedProperties(
        DOMDocument $document,
        string $certificateDer,
        array $parsed,
        string $id,
        string $dataReferenceId,
        DateTimeInterface $signingTime
    ): DOMElement {
        $properties = $document->createElementNS(self::XADES, 'xades:SignedProperties');
        $properties->setAttribute('Id', $id);
        $signatureProperties = $document->createElementNS(self::XADES, 'xades:SignedSignatureProperties');
        $signatureProperties->appendChild(
            $document->createElementNS(self::XADES, 'xades:SigningTime', $signingTime->format('Y-m-d\TH:i:sP'))
        );

        $signingCertificate = $document->createElementNS(self::XADES, 'xades:SigningCertificate');
        $cert = $document->createElementNS(self::XADES, 'xades:Cert');
        $certDigest = $document->createElementNS(self::XADES, 'xades:CertDigest');
        $certDigest->appendChild($this->algorithmElement($document, 'DigestMethod', self::DIGEST));
        $certDigest->appendChild(
            $document->createElementNS(
                self::DS,
                'ds:DigestValue',
                base64_encode(hash('sha256', $certificateDer, true))
            )
        );
        $cert->appendChild($certDigest);
        $issuerSerial = $document->createElementNS(self::XADES, 'xades:IssuerSerial');
        $issuerSerial->appendChild(
            $document->createElementNS(self::DS, 'ds:X509IssuerName', $this->distinguishedName($parsed['issuer'] ?? []))
        );
        $issuerSerial->appendChild(
            $document->createElementNS(self::DS, 'ds:X509SerialNumber', $this->serialNumber($parsed))
        );
        $cert->appendChild($issuerSerial);
        $signingCertificate->appendChild($cert);
        $signatureProperties->appendChild($signingCertificate);
        $properties->appendChild($signatureProperties);

        $dataProperties = $document->createElementNS(self::XADES, 'xades:SignedDataObjectProperties');
        $format = $document->createElementNS(self::XADES, 'xades:DataObjectFormat');
        $format->setAttribute('ObjectReference', '#' . $dataReferenceId);
        $format->appendChild($document->createElementNS(self::XADES, 'xades:MimeType', 'text/xml'));
        $dataProperties->appendChild($format);
        $properties->appendChild($dataProperties);

        return $properties;
    }

    private function reference(
        DOMDocument $document,
        string $uri,
        string $digest,
        ?string $id,
        bool $enveloped,
        ?string $type = null
    ): DOMElement {
        $reference = $document->createElementNS(self::DS, 'ds:Reference');
        if ($id !== null) {
            $reference->setAttribute('Id', $id);
        }
        $reference->setAttribute('URI', $uri);
        if ($type !== null) {
            $reference->setAttribute('Type', $type);
        }
        $transforms = $document->createElementNS(self::DS, 'ds:Transforms');
        if ($enveloped) {
            $transforms->appendChild($this->algorithmElement($document, 'Transform', self::ENVELOPED));
        }
        $transforms->appendChild($this->algorithmElement($document, 'Transform', self::C14N));
        $reference->appendChild($transforms);
        $reference->appendChild($this->algorithmElement($document, 'DigestMethod', self::DIGEST));
        $reference->appendChild($document->createElementNS(self::DS, 'ds:DigestValue', $digest));
        return $reference;
    }

    private function algorithmElement(DOMDocument $document, string $name, string $algorithm): DOMElement
    {
        $element = $document->createElementNS(self::DS, 'ds:' . $name);
        $element->setAttribute('Algorithm', $algorithm);
        return $element;
    }

    private function certificateDer(string $certificate): string
    {
        $body = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $certificate);
        $der = $body === null ? false : base64_decode($body, true);
        if ($der === false) {
            throw new ValidationException('certificate', 'O certificado PEM é inválido.');
        }
        return $der;
    }

    /**
     * @param mixed $issuer
     */
    private function distinguishedName(mixed $issuer): string
    {
        if (!is_array($issuer)) {
            return (string) $issuer;
        }
        $parts = [];
        foreach ($issuer as $key => $value) {
            $parts[] = $key . '=' . (is_array($value) ? implode('+', $value) : $value);
        }
        return implode(',', array_reverse($parts));
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function serialNumber(array $parsed): string
    {
        if (isset($parsed['serialNumber']) && ctype_digit((string) $parsed['serialNumber'])) {
            return (string) $parsed['serialNumber'];
        }
        $hex = preg_replace('/[^0-9A-F]/i', '', (string) ($parsed['serialNumberHex'] ?? '0')) ?: '0';
        $decimal = '0';
        foreach (str_split($hex) as $digit) {
            $decimal = $this->decimalMultiplyAdd($decimal, 16, intval($digit, 16));
        }
        return $decimal;
    }

    private function decimalMultiplyAdd(string $number, int $multiplier, int $addition): string
    {
        $carry = $addition;
        $result = '';
        for ($i = strlen($number) - 1; $i >= 0; --$i) {
            $value = ((int) $number[$i] * $multiplier) + $carry;
            $result = ($value % 10) . $result;
            $carry = intdiv($value, 10);
        }
        while ($carry > 0) {
            $result = ($carry % 10) . $result;
            $carry = intdiv($carry, 10);
        }
        return ltrim($result, '0') ?: '0';
    }
}
