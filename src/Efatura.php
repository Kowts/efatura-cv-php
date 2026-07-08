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
use Kowts\Efatura\Contract\Clock;
use Kowts\Efatura\Contract\SubmissionRegistry;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Data\FiscalDocument;
use Kowts\Efatura\Domain\EmissionMode;
use Kowts\Efatura\Domain\EventId;
use Kowts\Efatura\Domain\Iud;
use Kowts\Efatura\Exception\ValidationException;
use Kowts\Efatura\Infrastructure\Http\CurlMiddlewareTransport;
use Kowts\Efatura\Infrastructure\Http\CurlPlatformTransport;
use Kowts\Efatura\Infrastructure\Http\InMemorySubmissionRegistry;
use Kowts\Efatura\Infrastructure\Clock\SystemClock;
use Kowts\Efatura\Infrastructure\Sequence\InMemorySequenceStore;
use Kowts\Efatura\Infrastructure\Signing\CertificateValidator;
use Kowts\Efatura\Infrastructure\Signing\Pkcs12Loader;
use Kowts\Efatura\Infrastructure\Signing\XmlSignatureVerifier;
use Kowts\Efatura\Infrastructure\Signing\XadesBesSigner;
use Kowts\Efatura\Infrastructure\Validation\XsdValidator;
use Kowts\Efatura\Packaging\DfeZip;
use Kowts\Efatura\Http\SubmissionResult;
use Kowts\Efatura\Fiscal\FiscalReadinessService;
use Kowts\Efatura\Contract\TaxpayerRegistryClient;
use Kowts\Efatura\Contract\SoftwareRegistryClient;
use Kowts\Efatura\Contract\EmitterAuthorizationClient;
use Kowts\Efatura\Dfa\DfaDocument;
use Kowts\Efatura\Dfa\PdfDfaRenderer;
use Kowts\Efatura\Validation\DocumentValidator;
use Kowts\Efatura\Validation\IssueDateValidator;
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
        private readonly CertificateValidator $certificateValidator = new CertificateValidator(),
        private readonly Clock $clock = new SystemClock(),
        private readonly SubmissionRegistry $submissionRegistry = new InMemorySubmissionRegistry()
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

    /**
     * Cria um documento imutável e tipado a partir da API por arrays.
     *
     * @param array<string, mixed> $document
     */
    public function documentFromArray(array $document): FiscalDocument
    {
        if (!isset($document['emitter']) && $this->config->emitter !== null) {
            $document['emitter'] = $this->config->emitterOrFail();
        }

        return FiscalDocument::fromArray($document, $this->documentValidator);
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
    public function buildDfeXml(
        string $iud,
        array|FiscalDocument $document,
        EmissionMode $mode = EmissionMode::Online
    ): string {
        $document = $document instanceof FiscalDocument ? $document->toArray() : $document;
        if (!isset($document['emitter']) && $this->config->emitter !== null) {
            $document['emitter'] = $this->config->emitterOrFail();
        }
        $validated = $this->documentValidator->validate($document);
        (new IssueDateValidator($this->clock))->validate(
            (string) $validated['issueDate'],
            isset($validated['issueTime']) ? (string) $validated['issueTime'] : null,
            $mode
        );

        return $this->dfeXmlBuilder->build($iud, $validated, $mode);
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
        ?string $privateKeyPassword = null,
        ?string $trustStore = null
    ): array {
        return $this->certificateValidator->validate(
            $certificate,
            $privateKey,
            $privateKeyPassword,
            $trustStore
        );
    }

    /**
     * @return array{valid:bool, issues:list<string>, certificateFingerprint:?string}
     */
    public function verifyXmlSignature(string $xml): array
    {
        return (new XmlSignatureVerifier())->verify($xml);
    }

    /**
     * @return array{certificate:string, privateKey:string, extraCertificates:list<string>}
     */
    public function loadPkcs12(string $contents, string $password): array
    {
        return (new Pkcs12Loader())->load($contents, $password);
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
        return $this->submitDfeZipResult($zip)->toArray();
    }

    public function submitDfeZipResult(string $zip, bool $allowResubmission = false): SubmissionResult
    {
        if ($this->config->transmitterKey === null || $this->config->transmitterKey === '') {
            throw new ValidationException(
                'transmitterKey',
                'A chave do transmissor é obrigatória para a submissão ao middleware.',
                'middleware.transmitter_key_required'
            );
        }
        $this->claimSubmission('middleware', $zip, $allowResubmission);

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
        return $this->submitDfeZipToPlatformResult($zip, $accessToken, $baseUrl)->toArray();
    }

    public function submitDfeZipToPlatformResult(
        string $zip,
        string $accessToken,
        ?string $baseUrl = null,
        bool $allowResubmission = false
    ): SubmissionResult {
        $this->claimSubmission('platform', $zip, $allowResubmission);

        return $this->platformTransport->submit(
            $baseUrl ?? $this->config->platformBaseUrl,
            $accessToken,
            $this->config->repositoryCode(),
            $zip
        );
    }

    private function claimSubmission(string $channel, string $zip, bool $allowResubmission): void
    {
        if ($allowResubmission) {
            return;
        }
        $digest = hash('sha256', $channel . "\0" . $zip);
        if (!$this->submissionRegistry->claim($digest)) {
            throw new ValidationException(
                'zip',
                'Este pacote já foi submetido por esta instância. Confirme explicitamente o reenvio.',
                'submission.duplicate'
            );
        }
    }

    public function dfaQrCodeUrl(string $iud): string
    {
        if (!Iud::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD é inválido.');
        }
        return rtrim($this->config->dfaBaseUrl, '/') . '/' . rawurlencode($iud);
    }

    /**
     * @param array<string, mixed>|FiscalDocument $document
     */
    public function renderDfa(
        string $iud,
        array|FiscalDocument $document,
        string $currency = 'CVE'
    ): DfaDocument {
        if (!Iud::isValid($iud)) {
            throw new ValidationException('iud', 'O IUD é inválido.');
        }
        $dto = $document instanceof FiscalDocument ? $document : $this->documentFromArray($document);

        return (new PdfDfaRenderer())->render(
            $iud,
            $dto,
            $this->dfaQrCodeUrl($iud),
            $currency
        );
    }

    /**
     * @param array<string, mixed>|FiscalDocument $document
     * @return array{ready:bool, checks:array<string, \Kowts\Efatura\Fiscal\RegistryResult>, issues:list<string>}
     */
    public function validateFiscalReadiness(
        array|FiscalDocument $document,
        TaxpayerRegistryClient $taxpayers,
        SoftwareRegistryClient $software,
        EmitterAuthorizationClient $authorizations,
        ?string $accessToken = null
    ): array {
        $dto = $document instanceof FiscalDocument ? $document : $this->documentFromArray($document);

        return (new FiscalReadinessService(
            $this->config,
            $taxpayers,
            $software,
            $authorizations
        ))->validate($dto, $accessToken);
    }
}
