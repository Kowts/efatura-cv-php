<?php

declare(strict_types=1);

namespace Kowts\Efatura;

use DateTimeInterface;
use Kowts\Efatura\Builder\DocumentBuilder;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Contract\MiddlewareTransport;
use Kowts\Efatura\Contract\PlatformTransport;
use Kowts\Efatura\Contract\SequenceStore;
use Kowts\Efatura\Contract\XmlSigner;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Domain\EventId;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Infrastructure\Http\CurlMiddlewareTransport;
use Kowts\Efatura\Infrastructure\Http\CurlPlatformTransport;
use Kowts\Efatura\Infrastructure\Sequence\InMemorySequenceStore;
use Kowts\Efatura\Infrastructure\Signing\CertificateValidator;
use Kowts\Efatura\Infrastructure\Signing\XadesBesSigner;
use Kowts\Efatura\Infrastructure\Validation\XsdValidator;
use Kowts\Efatura\Packaging\DfeZip;
use Kowts\Efatura\Validation\DocumentValidator;
use Kowts\Efatura\Xml\DfeXmlBuilder;
use Kowts\Efatura\Xml\EventXmlBuilder;

/**
 * Fachada principal e independente de frameworks.
 */
final class Efatura
{
    private readonly DocumentValidator $documentValidator;
    private readonly DfeXmlBuilder $dfeXmlBuilder;
    private readonly EventXmlBuilder $eventXmlBuilder;

    public function __construct(
        public readonly EfaturaConfig $config,
        private readonly SequenceStore $sequenceStore = new InMemorySequenceStore(),
        private readonly XmlSigner $xmlSigner = new XadesBesSigner(),
        private readonly MiddlewareTransport $middlewareTransport = new CurlMiddlewareTransport(),
        private readonly PlatformTransport $platformTransport = new CurlPlatformTransport(),
        private readonly XsdValidator $xsdValidator = new XsdValidator(),
        private readonly CertificateValidator $certificateValidator = new CertificateValidator()
    ) {
        $this->documentValidator = new DocumentValidator();
        $this->dfeXmlBuilder = new DfeXmlBuilder($config, $this->documentValidator);
        $this->eventXmlBuilder = new EventXmlBuilder($config);
    }

    public function document(): DocumentBuilder
    {
        $defaults = $this->config->emitter === null ? [] : ['emitter' => $this->config->emitterOrFail()];
        return new DocumentBuilder($this->documentValidator, $defaults);
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    public function validateDocument(array $document): array
    {
        if (!isset($document['emitter']) && $this->config->emitter !== null) {
            $document['emitter'] = $this->config->emitterOrFail();
        }
        return $this->documentValidator->validate($document);
    }

    public function nextDocumentNumber(string $issueDate, DocumentType $type): int
    {
        $year = (int) substr($issueDate, 0, 4);
        if ($year < 2000 || $year > 9999) {
            throw new ValidationException('issueDate', 'A data de emissão é inválida.');
        }
        return $this->sequenceStore->next(
            $this->config->transmitterNif,
            $year,
            $this->config->transmitterLed,
            $type
        );
    }

    public function buildIud(
        DateTimeInterface|string $issueDate,
        DocumentType $type,
        int|string $documentNumber,
        int|string|null $randomCode = null
    ): string {
        return Iud::build(
            $this->config->repositoryCode(),
            $issueDate,
            $this->config->transmitterNif,
            $this->config->transmitterLed,
            $type,
            $documentNumber,
            $randomCode
        );
    }

    public function buildSequentialIud(
        string $issueDate,
        DocumentType $type,
        int|string|null $randomCode = null
    ): string {
        return $this->buildIud($issueDate, $type, $this->nextDocumentNumber($issueDate, $type), $randomCode);
    }

    public function buildEventId(DateTimeInterface|string $issueDateTime): string
    {
        return EventId::build(
            $this->config->repositoryCode(),
            $issueDateTime,
            $this->config->transmitterNif
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    public function buildDfeXml(string $iud, array $document, EmissionMode $mode = EmissionMode::Online): string
    {
        if (!isset($document['emitter']) && $this->config->emitter !== null) {
            $document['emitter'] = $this->config->emitterOrFail();
        }
        return $this->dfeXmlBuilder->build($iud, $document, $mode);
    }

    /**
     * @param array<string, mixed> $event
     */
    public function buildEventXml(string $eventId, array $event, EmissionMode $mode = EmissionMode::Online): string
    {
        return $this->eventXmlBuilder->build($eventId, $event, $mode);
    }

    /**
     * @return array{valid:bool, errors:list<array{message:string, line:int, column:int, code:int}>}
     */
    public function validateXml(string $xml): array
    {
        return $this->xsdValidator->validate($xml);
    }

    /**
     * @return array{xml:string, algorithm:string, profile:string, certificateFingerprint:string}
     */
    public function signXml(
        string $xml,
        string $certificate,
        string $privateKey,
        ?string $privateKeyPassword = null,
        ?DateTimeInterface $signingTime = null
    ): array {
        return $this->xmlSigner->sign($xml, $certificate, $privateKey, $privateKeyPassword, $signingTime);
    }

    /**
     * @return array{valid:bool, fingerprint:?string, validFrom:?string, validTo:?string, issues:list<string>}
     */
    public function validateCertificate(
        string $certificate,
        ?string $privateKey = null,
        ?string $privateKeyPassword = null
    ): array {
        return $this->certificateValidator->validate($certificate, $privateKey, $privateKeyPassword);
    }

    /**
     * @param list<array{iud:string, xml:string}> $files
     */
    public function buildDfeZip(array $files): string
    {
        return (new DfeZip())->build($files);
    }

    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function submitDfeZip(string $zip): array
    {
        if ($this->config->transmitterKey === null || $this->config->transmitterKey === '') {
            throw new ValidationException(
                'transmitterKey',
                'A chave do transmissor é obrigatória para a submissão ao middleware.',
                'middleware.transmitter_key_required'
            );
        }
        return $this->middlewareTransport->submit(
            $this->config->middlewareBaseUrl,
            $this->config->transmitterKey,
            $zip
        );
    }

    /**
     * @return array{ok:bool, status:int, statusText:string, body:mixed, rawBody:string, headers:array<string, string>}
     */
    public function submitDfeZipToPlatform(string $zip, string $accessToken, ?string $baseUrl = null): array
    {
        return $this->platformTransport->submit(
            $baseUrl ?? $this->config->platformBaseUrl,
            $accessToken,
            $this->config->repositoryCode(),
            $zip
        );
    }

    public function dfaQrCodeUrl(string $iud): string
    {
        if (!Iud::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD é inválido.');
        }
        return rtrim($this->config->dfaBaseUrl, '/') . '/' . rawurlencode($iud);
    }
}
