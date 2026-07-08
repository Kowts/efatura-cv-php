<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use DOMDocument;
use DOMXPath;
use Kowts\Efatura\Infrastructure\Signing\XadesBesSigner;
use PHPUnit\Framework\TestCase;

final class XadesBesSignerTest extends TestCase
{
    public function testCriaAssinaturaRsaSha256Verificavel(): void
    {
        [$certificate, $privateKey] = $this->certificatePair();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Dfe xmlns="urn:cv:efatura:xsd:v1.0" Version="1.0" Id="TEST-ID" DocumentTypeCode="1"></Dfe>';

        $result = (new XadesBesSigner())->sign(
            $xml,
            $certificate,
            $privateKey,
            null,
            new \DateTimeImmutable('2026-02-08T10:30:00+00:00')
        );

        self::assertSame('XAdES-BES', $result['profile']);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($result['xml']));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $signedInfo = $xpath->query('//ds:SignedInfo')->item(0);
        $signatureValue = $xpath->evaluate('string(//ds:SignatureValue)');
        self::assertNotNull($signedInfo);
        self::assertSame(
            1,
            openssl_verify(
                $signedInfo->C14N(false, false),
                base64_decode($signatureValue, true),
                $certificate,
                OPENSSL_ALGO_SHA256
            )
        );

        $root = $document->documentElement;
        self::assertNotNull($root);
        $rootClone = $root->cloneNode(true);
        self::assertInstanceOf(\DOMElement::class, $rootClone);
        foreach (
            iterator_to_array($rootClone->getElementsByTagNameNS(
                'http://www.w3.org/2000/09/xmldsig#',
                'Signature'
            )) as $signature
        ) {
            $signature->parentNode?->removeChild($signature);
        }
        $digests = $xpath->query('//ds:DigestValue');
        self::assertSame(
            base64_encode(hash('sha256', $rootClone->C14N(false, false), true)),
            $digests->item(0)?->textContent
        );

        $xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');
        $signedProperties = $xpath->query('//xades:SignedProperties')->item(0);
        self::assertNotNull($signedProperties);
        self::assertSame(
            base64_encode(hash('sha256', $signedProperties->C14N(false, false), true)),
            $digests->item(1)?->textContent
        );
    }

    /**
     * @return array{string, string}
     */
    private function certificatePair(): array
    {
        $config = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR
            . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        if (is_file($config)) {
            $options['config'] = $config;
        }
        $key = openssl_pkey_new($options);
        self::assertNotFalse($key);
        $request = openssl_csr_new(
            ['countryName' => 'CV', 'organizationName' => 'Teste', 'commonName' => 'Teste e-Fatura CV'],
            $key,
            array_merge($options, ['digest_alg' => 'sha256'])
        );
        self::assertNotFalse($request);
        $certificate = openssl_csr_sign(
            $request,
            null,
            $key,
            30,
            array_merge($options, ['digest_alg' => 'sha256'])
        );
        self::assertNotFalse($certificate);
        self::assertTrue(openssl_x509_export($certificate, $certificatePem));
        self::assertTrue(openssl_pkey_export($key, $privateKeyPem, null, $options));

        return [$certificatePem, $privateKeyPem];
    }
}
