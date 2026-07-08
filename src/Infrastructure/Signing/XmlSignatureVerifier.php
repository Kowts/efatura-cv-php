<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Signing;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Verifica a assinatura RSA, referências e digests de um XML XAdES-BES.
 */
final class XmlSignatureVerifier
{
    private const DS = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * @return array{valid:bool, issues:list<string>, certificateFingerprint:?string}
     */
    public function verify(string $xml): array
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        if (!$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            return $this->failure('O XML assinado é inválido.');
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ds', self::DS);
        $signatureNodes = $xpath->query('//ds:Signature');
        $signedInfoNodes = $xpath->query('//ds:SignedInfo');
        $signature = $signatureNodes === false ? null : $signatureNodes->item(0);
        $signedInfo = $signedInfoNodes === false ? null : $signedInfoNodes->item(0);
        $signatureValue = trim((string) $xpath->evaluate('string(//ds:SignatureValue)'));
        $certificateBase64 = preg_replace('/\s+/', '', (string) $xpath->evaluate('string(//ds:X509Certificate)'));
        if (
            !$signature instanceof DOMElement
            || !$signedInfo instanceof DOMElement
            || $certificateBase64 === null
            || $certificateBase64 === ''
        ) {
            return $this->failure('A estrutura XMLDSig está incompleta.');
        }

        $certificateDer = base64_decode($certificateBase64, true);
        $signatureBytes = base64_decode($signatureValue, true);
        if ($certificateDer === false || $signatureBytes === false) {
            return $this->failure('O certificado ou o valor da assinatura não está em Base64 válido.');
        }
        $certificate = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certificateDer), 64, "\n")
            . "-----END CERTIFICATE-----\n";
        $issues = [];
        if (openssl_verify($signedInfo->C14N(false, false), $signatureBytes, $certificate, OPENSSL_ALGO_SHA256) !== 1) {
            $issues[] = 'A assinatura RSA-SHA256 não é válida.';
        }

        $references = $xpath->query('//ds:SignedInfo/ds:Reference');
        if ($references === false) {
            return $this->failure('Não foi possível interpretar as referências XMLDSig.');
        }
        foreach ($references as $reference) {
            if (!$reference instanceof DOMElement) {
                continue;
            }
            $uri = $reference->getAttribute('URI');
            $id = str_starts_with($uri, '#') ? substr($uri, 1) : $uri;
            $target = $this->findById($document, $id);
            if (!$target instanceof DOMElement) {
                $issues[] = "A referência {$uri} não foi encontrada.";
                continue;
            }
            $transforms = $xpath->query(
                './/ds:Transform[@Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"]',
                $reference
            );
            $hasEnvelopedTransform = $transforms !== false && $transforms->length > 0;
            if ($hasEnvelopedTransform) {
                $digestTarget = $target->cloneNode(true);
                if (!$digestTarget instanceof DOMElement) {
                    $issues[] = "A referência {$uri} não pôde ser processada.";
                    continue;
                }
                foreach (iterator_to_array($digestTarget->getElementsByTagNameNS(self::DS, 'Signature')) as $node) {
                    $node->parentNode?->removeChild($node);
                }
            } else {
                $digestTarget = $target;
            }
            $expected = trim((string) $xpath->evaluate('string(ds:DigestValue)', $reference));
            $actual = base64_encode(hash('sha256', $digestTarget->C14N(false, false), true));
            if (!hash_equals($expected, $actual)) {
                $issues[] = "O digest da referência {$uri} não corresponde ao conteúdo.";
            }
        }

        $resource = openssl_x509_read($certificate);

        return [
            'valid' => $issues === [],
            'issues' => $issues,
            'certificateFingerprint' => $resource === false
                ? null
                : (openssl_x509_fingerprint($resource, 'sha256') ?: null),
        ];
    }

    private function findById(DOMDocument $document, string $id): ?DOMElement
    {
        foreach ($document->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement && $element->getAttribute('Id') === $id) {
                return $element;
            }
        }

        return null;
    }

    /**
     * @return array{valid:false, issues:list<string>, certificateFingerprint:null}
     */
    private function failure(string $message): array
    {
        return ['valid' => false, 'issues' => [$message], 'certificateFingerprint' => null];
    }
}
