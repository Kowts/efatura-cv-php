<?php

declare(strict_types=1);

namespace Kowts\Efatura\Infrastructure\Signing;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Verifica a assinatura RSA, a estrutura XAdES e os digests de um XML.
 *
 * A confiança na cadeia ICP-CV é uma operação separada, realizada por
 * CertificateValidator com um trust store explicitamente configurado.
 */
final class XmlSignatureVerifier
{
    private const DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const XADES = 'http://uri.etsi.org/01903/v1.3.2#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const DIGEST = 'http://www.w3.org/2001/04/xmlenc#sha256';
    private const RSA_SHA256 = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const SIGNED_PROPERTIES = 'http://uri.etsi.org/01903#SignedProperties';

    /**
     * @return array{valid:bool, issues:list<string>, certificateFingerprint:?string}
     */
    public function verify(string $xml): array
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        if (
            !$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)
            || !$document->documentElement instanceof DOMElement
        ) {
            return $this->failure('O XML assinado é inválido.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ds', self::DS);
        $xpath->registerNamespace('xades', self::XADES);
        $issues = $this->duplicateIdIssues($document);

        $signatureNodes = $xpath->query('//ds:Signature');
        if ($signatureNodes === false || $signatureNodes->length !== 1) {
            return $this->failure('O XML deve conter exactamente uma assinatura XMLDSig.');
        }
        $signature = $signatureNodes->item(0);
        if (!$signature instanceof DOMElement || $signature->parentNode !== $document->documentElement) {
            return $this->failure('A assinatura XMLDSig deve ser filha directa do elemento raiz.');
        }

        $signedInfo = $this->singleElement($xpath, './ds:SignedInfo', $signature);
        $signatureValueElement = $this->singleElement($xpath, './ds:SignatureValue', $signature);
        $certificateElement = $this->singleElement($xpath, './ds:KeyInfo/ds:X509Data/ds:X509Certificate', $signature);
        if (
            !$signedInfo instanceof DOMElement
            || !$signatureValueElement instanceof DOMElement
            || !$certificateElement instanceof DOMElement
        ) {
            return $this->failure('A estrutura XMLDSig está incompleta ou contém elementos duplicados.');
        }

        $issues = array_merge($issues, $this->algorithmIssues($xpath, $signedInfo));
        $certificateBase64 = preg_replace('/\s+/', '', $certificateElement->textContent);
        $certificateDer = $certificateBase64 === null ? false : base64_decode($certificateBase64, true);
        $signatureBytes = base64_decode(trim($signatureValueElement->textContent), true);
        if ($certificateDer === false || $signatureBytes === false) {
            return $this->failure('O certificado ou o valor da assinatura não está em Base64 válido.');
        }
        $certificate = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($certificateDer), 64, "\n")
            . "-----END CERTIFICATE-----\n";
        if (openssl_verify($signedInfo->C14N(false, false), $signatureBytes, $certificate, OPENSSL_ALGO_SHA256) !== 1) {
            $issues[] = 'A assinatura RSA-SHA256 não é válida.';
        }

        $root = $document->documentElement;
        $rootId = $root->getAttribute('Id');
        if ($rootId === '') {
            $issues[] = 'O elemento raiz assinado não possui Id.';
        }
        $signatureId = $signature->getAttribute('Id');
        if ($signatureId === '') {
            $issues[] = 'A assinatura XMLDSig não possui Id.';
        }

        $references = $xpath->query('./ds:Reference', $signedInfo);
        if ($references === false || $references->length !== 2) {
            $issues[] = 'SignedInfo deve conter exactamente duas referências.';
        } else {
            $rootReferenceFound = false;
            $propertiesReferenceFound = false;
            foreach ($references as $reference) {
                if (!$reference instanceof DOMElement) {
                    continue;
                }
                $uri = $reference->getAttribute('URI');
                $type = $reference->getAttribute('Type');
                if ($uri === '#' . $rootId && $type === '') {
                    $rootReferenceFound = true;
                    $issues = array_merge(
                        $issues,
                        $this->verifyReference($document, $xpath, $reference, true)
                    );
                    continue;
                }
                if ($type === self::SIGNED_PROPERTIES) {
                    $propertiesReferenceFound = true;
                    $issues = array_merge(
                        $issues,
                        $this->verifySignedPropertiesReference(
                            $document,
                            $xpath,
                            $signature,
                            $reference,
                            $signatureId,
                            $certificateDer
                        )
                    );
                    continue;
                }
                $issues[] = "A referência {$uri} não é permitida neste perfil XAdES.";
            }
            if (!$rootReferenceFound) {
                $issues[] = 'SignedInfo não referencia o elemento raiz do documento.';
            }
            if (!$propertiesReferenceFound) {
                $issues[] = 'SignedInfo não referencia as propriedades assinadas XAdES.';
            }
        }

        $resource = openssl_x509_read($certificate);

        return [
            'valid' => $issues === [],
            'issues' => array_values(array_unique($issues)),
            'certificateFingerprint' => $resource === false
                ? null
                : (openssl_x509_fingerprint($resource, 'sha256') ?: null),
        ];
    }

    /**
     * @return list<string>
     */
    private function algorithmIssues(DOMXPath $xpath, DOMElement $signedInfo): array
    {
        $issues = [];
        $canonicalisation = (string) $xpath->evaluate(
            'string(./ds:CanonicalizationMethod/@Algorithm)',
            $signedInfo
        );
        $signature = (string) $xpath->evaluate('string(./ds:SignatureMethod/@Algorithm)', $signedInfo);
        if ($canonicalisation !== self::C14N) {
            $issues[] = 'O algoritmo de canonicalização não é permitido.';
        }
        if ($signature !== self::RSA_SHA256) {
            $issues[] = 'O algoritmo de assinatura deve ser RSA-SHA256.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function verifyReference(
        DOMDocument $document,
        DOMXPath $xpath,
        DOMElement $reference,
        bool $rootReference
    ): array {
        $issues = [];
        $uri = $reference->getAttribute('URI');
        if (!str_starts_with($uri, '#') || strlen($uri) === 1) {
            return ["A referência {$uri} não é uma referência interna válida."];
        }
        $target = $this->findUniqueById($document, substr($uri, 1));
        if (!$target instanceof DOMElement) {
            return ["A referência {$uri} não foi encontrada de forma unívoca."];
        }

        $digestAlgorithm = (string) $xpath->evaluate('string(./ds:DigestMethod/@Algorithm)', $reference);
        if ($digestAlgorithm !== self::DIGEST) {
            $issues[] = "A referência {$uri} não usa SHA-256.";
        }
        $transformNodes = $xpath->query('./ds:Transforms/ds:Transform', $reference);
        $transforms = [];
        if ($transformNodes !== false) {
            foreach ($transformNodes as $transform) {
                if ($transform instanceof DOMElement) {
                    $transforms[] = $transform->getAttribute('Algorithm');
                }
            }
        }
        $expectedTransforms = $rootReference ? [self::ENVELOPED, self::C14N] : [self::C14N];
        if ($transforms !== $expectedTransforms) {
            $issues[] = "As transformações da referência {$uri} não são permitidas.";
        }

        $digestTarget = $target;
        if ($rootReference) {
            $clone = $target->cloneNode(true);
            if (!$clone instanceof DOMElement) {
                return ["A referência {$uri} não pôde ser processada."];
            }
            foreach (iterator_to_array($clone->getElementsByTagNameNS(self::DS, 'Signature')) as $node) {
                $node->parentNode?->removeChild($node);
            }
            $digestTarget = $clone;
        }
        $expected = trim((string) $xpath->evaluate('string(./ds:DigestValue)', $reference));
        $actual = base64_encode(hash('sha256', $digestTarget->C14N(false, false), true));
        if ($expected === '' || !hash_equals($expected, $actual)) {
            $issues[] = "O digest da referência {$uri} não corresponde ao conteúdo.";
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function verifySignedPropertiesReference(
        DOMDocument $document,
        DOMXPath $xpath,
        DOMElement $signature,
        DOMElement $reference,
        string $signatureId,
        string $certificateDer
    ): array {
        $issues = $this->verifyReference($document, $xpath, $reference, false);
        $uri = $reference->getAttribute('URI');
        $target = str_starts_with($uri, '#')
            ? $this->findUniqueById($document, substr($uri, 1))
            : null;
        if (
            !$target instanceof DOMElement
            || $target->namespaceURI !== self::XADES
            || $target->localName !== 'SignedProperties'
            || !$this->isDescendantOf($target, $signature)
        ) {
            $issues[] = 'A referência XAdES não aponta para SignedProperties da assinatura.';
            return $issues;
        }

        $qualifying = $target->parentNode;
        if (
            !$qualifying instanceof DOMElement
            || $qualifying->localName !== 'QualifyingProperties'
            || $qualifying->namespaceURI !== self::XADES
            || $qualifying->getAttribute('Target') !== '#' . $signatureId
        ) {
            $issues[] = 'QualifyingProperties não referencia a assinatura XMLDSig.';
        }

        $certAlgorithm = (string) $xpath->evaluate(
            'string(.//xades:SigningCertificate/xades:Cert/xades:CertDigest/ds:DigestMethod/@Algorithm)',
            $target
        );
        $certDigest = trim((string) $xpath->evaluate(
            'string(.//xades:SigningCertificate/xades:Cert/xades:CertDigest/ds:DigestValue)',
            $target
        ));
        $expectedCertDigest = base64_encode(hash('sha256', $certificateDer, true));
        if ($certAlgorithm !== self::DIGEST || !hash_equals($expectedCertDigest, $certDigest)) {
            $issues[] = 'O digest do certificado em SignedProperties não corresponde ao certificado incorporado.';
        }

        return $issues;
    }

    private function singleElement(DOMXPath $xpath, string $expression, DOMNode $context): ?DOMElement
    {
        $nodes = $xpath->query($expression, $context);
        if ($nodes === false || $nodes->length !== 1) {
            return null;
        }
        $element = $nodes->item(0);

        return $element instanceof DOMElement ? $element : null;
    }

    /**
     * @return list<string>
     */
    private function duplicateIdIssues(DOMDocument $document): array
    {
        $seen = [];
        $duplicates = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement || !$element->hasAttribute('Id')) {
                continue;
            }
            $id = $element->getAttribute('Id');
            if ($id === '' || isset($seen[$id])) {
                $duplicates[$id] = true;
            }
            $seen[$id] = true;
        }

        return array_map(
            static fn (string $id): string => "O Id XML '{$id}' não é único e válido.",
            array_keys($duplicates)
        );
    }

    private function findUniqueById(DOMDocument $document, string $id): ?DOMElement
    {
        $found = null;
        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement || $element->getAttribute('Id') !== $id) {
                continue;
            }
            if ($found !== null) {
                return null;
            }
            $found = $element;
        }

        return $found;
    }

    private function isDescendantOf(DOMNode $node, DOMNode $ancestor): bool
    {
        for ($parent = $node->parentNode; $parent !== null; $parent = $parent->parentNode) {
            if ($parent === $ancestor) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{valid:false, issues:list<string>, certificateFingerprint:null}
     */
    private function failure(string $message): array
    {
        return ['valid' => false, 'issues' => [$message], 'certificateFingerprint' => null];
    }
}
