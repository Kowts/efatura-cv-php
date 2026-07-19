# Referência automática da API

> Ficheiro gerado automaticamente. Não edite manualmente.
>
> Para regenerar: `composer docs:api`.

Esta referência lista símbolos públicos em `src/` e os métodos públicos
declarados em cada classe, interface, trait ou enum.

## Índice

- [`Kowts\Efatura\Bridge\Laravel\EfaturaServiceProvider`](#kowtsefaturabridgelaravelefaturaserviceprovider)
- [`Kowts\Efatura\Bridge\Symfony\EfaturaBundle`](#kowtsefaturabridgesymfonyefaturabundle)
- [`Kowts\Efatura\Bridge\Symfony\EfaturaExtension`](#kowtsefaturabridgesymfonyefaturaextension)
- [`Kowts\Efatura\Bridge\Yii2\EfaturaBootstrap`](#kowtsefaturabridgeyii2efaturabootstrap)
- [`Kowts\Efatura\Bridge\Yii2\EfaturaComponent`](#kowtsefaturabridgeyii2efaturacomponent)
- [`Kowts\Efatura\Builder\DocumentBuilder`](#kowtsefaturabuilderdocumentbuilder)
- [`Kowts\Efatura\Config\EfaturaConfig`](#kowtsefaturaconfigefaturaconfig)
- [`Kowts\Efatura\Contract\Clock`](#kowtsefaturacontractclock)
- [`Kowts\Efatura\Contract\DocumentStatusClient`](#kowtsefaturacontractdocumentstatusclient)
- [`Kowts\Efatura\Contract\EmitterAuthorizationClient`](#kowtsefaturacontractemitterauthorizationclient)
- [`Kowts\Efatura\Contract\MiddlewareTransport`](#kowtsefaturacontractmiddlewaretransport)
- [`Kowts\Efatura\Contract\PlatformTransport`](#kowtsefaturacontractplatformtransport)
- [`Kowts\Efatura\Contract\SelfBillingAuthorizationClient`](#kowtsefaturacontractselfbillingauthorizationclient)
- [`Kowts\Efatura\Contract\SequenceStore`](#kowtsefaturacontractsequencestore)
- [`Kowts\Efatura\Contract\SoftwareRegistryClient`](#kowtsefaturacontractsoftwareregistryclient)
- [`Kowts\Efatura\Contract\SubmissionRegistry`](#kowtsefaturacontractsubmissionregistry)
- [`Kowts\Efatura\Contract\TaxpayerRegistryClient`](#kowtsefaturacontracttaxpayerregistryclient)
- [`Kowts\Efatura\Contract\XmlSigner`](#kowtsefaturacontractxmlsigner)
- [`Kowts\Efatura\Dfa\DfaDocument`](#kowtsefaturadfadfadocument)
- [`Kowts\Efatura\Dfa\PdfDfaRenderer`](#kowtsefaturadfapdfdfarenderer)
- [`Kowts\Efatura\Domain\Data\Address`](#kowtsefaturadomaindataaddress)
- [`Kowts\Efatura\Domain\Data\DocumentLine`](#kowtsefaturadomaindatadocumentline)
- [`Kowts\Efatura\Domain\Data\DocumentTotals`](#kowtsefaturadomaindatadocumenttotals)
- [`Kowts\Efatura\Domain\Data\FiscalDocument`](#kowtsefaturadomaindatafiscaldocument)
- [`Kowts\Efatura\Domain\Data\Party`](#kowtsefaturadomaindataparty)
- [`Kowts\Efatura\Domain\Data\Tax`](#kowtsefaturadomaindatatax)
- [`Kowts\Efatura\Domain\Data\TaxId`](#kowtsefaturadomaindatataxid)
- [`Kowts\Efatura\Domain\Decimal`](#kowtsefaturadomaindecimal)
- [`Kowts\Efatura\Domain\DocumentType`](#kowtsefaturadomaindocumenttype)
- [`Kowts\Efatura\Domain\EmissionMode`](#kowtsefaturadomainemissionmode)
- [`Kowts\Efatura\Domain\Environment`](#kowtsefaturadomainenvironment)
- [`Kowts\Efatura\Domain\EventId`](#kowtsefaturadomaineventid)
- [`Kowts\Efatura\Domain\EventType`](#kowtsefaturadomaineventtype)
- [`Kowts\Efatura\Domain\Iud`](#kowtsefaturadomainiud)
- [`Kowts\Efatura\Domain\TaxType`](#kowtsefaturadomaintaxtype)
- [`Kowts\Efatura\Efatura`](#kowtsefaturaefatura)
- [`Kowts\Efatura\EfaturaFactory`](#kowtsefaturaefaturafactory)
- [`Kowts\Efatura\Exception\EfaturaException`](#kowtsefaturaexceptionefaturaexception)
- [`Kowts\Efatura\Exception\SubmissionUncertainException`](#kowtsefaturaexceptionsubmissionuncertainexception)
- [`Kowts\Efatura\Exception\ValidationException`](#kowtsefaturaexceptionvalidationexception)
- [`Kowts\Efatura\Fiscal\FiscalReadinessService`](#kowtsefaturafiscalfiscalreadinessservice)
- [`Kowts\Efatura\Fiscal\ReconciliationResult`](#kowtsefaturafiscalreconciliationresult)
- [`Kowts\Efatura\Fiscal\ReconciliationStatus`](#kowtsefaturafiscalreconciliationstatus)
- [`Kowts\Efatura\Fiscal\RegistryResult`](#kowtsefaturafiscalregistryresult)
- [`Kowts\Efatura\Fiscal\SelfBillingAuthorizationResult`](#kowtsefaturafiscalselfbillingauthorizationresult)
- [`Kowts\Efatura\Fiscal\SubmissionReconciler`](#kowtsefaturafiscalsubmissionreconciler)
- [`Kowts\Efatura\Http\SubmissionResult`](#kowtsefaturahttpsubmissionresult)
- [`Kowts\Efatura\Infrastructure\Clock\FrozenClock`](#kowtsefaturainfrastructureclockfrozenclock)
- [`Kowts\Efatura\Infrastructure\Clock\SystemClock`](#kowtsefaturainfrastructureclocksystemclock)
- [`Kowts\Efatura\Infrastructure\Fiscal\Psr18FiscalAuthorityClient`](#kowtsefaturainfrastructurefiscalpsr18fiscalauthorityclient)
- [`Kowts\Efatura\Infrastructure\Http\CurlClient`](#kowtsefaturainfrastructurehttpcurlclient)
- [`Kowts\Efatura\Infrastructure\Http\CurlMiddlewareTransport`](#kowtsefaturainfrastructurehttpcurlmiddlewaretransport)
- [`Kowts\Efatura\Infrastructure\Http\CurlPlatformTransport`](#kowtsefaturainfrastructurehttpcurlplatformtransport)
- [`Kowts\Efatura\Infrastructure\Http\InMemorySubmissionRegistry`](#kowtsefaturainfrastructurehttpinmemorysubmissionregistry)
- [`Kowts\Efatura\Infrastructure\Http\Psr18MiddlewareTransport`](#kowtsefaturainfrastructurehttppsr18middlewaretransport)
- [`Kowts\Efatura\Infrastructure\Http\Psr18PlatformTransport`](#kowtsefaturainfrastructurehttppsr18platformtransport)
- [`Kowts\Efatura\Infrastructure\Http\ResponseParser`](#kowtsefaturainfrastructurehttpresponseparser)
- [`Kowts\Efatura\Infrastructure\Http\RetryingPsr18Client`](#kowtsefaturainfrastructurehttpretryingpsr18client)
- [`Kowts\Efatura\Infrastructure\Sequence\InMemorySequenceStore`](#kowtsefaturainfrastructuresequenceinmemorysequencestore)
- [`Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore`](#kowtsefaturainfrastructuresequencepdosequencestore)
- [`Kowts\Efatura\Infrastructure\Signing\CertificateValidator`](#kowtsefaturainfrastructuresigningcertificatevalidator)
- [`Kowts\Efatura\Infrastructure\Signing\Pkcs12Loader`](#kowtsefaturainfrastructuresigningpkcs12loader)
- [`Kowts\Efatura\Infrastructure\Signing\XadesBesSigner`](#kowtsefaturainfrastructuresigningxadesbessigner)
- [`Kowts\Efatura\Infrastructure\Signing\XmlSignatureVerifier`](#kowtsefaturainfrastructuresigningxmlsignatureverifier)
- [`Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry`](#kowtsefaturainfrastructuresubmissionpdosubmissionregistry)
- [`Kowts\Efatura\Infrastructure\Validation\XsdValidator`](#kowtsefaturainfrastructurevalidationxsdvalidator)
- [`Kowts\Efatura\Packaging\DfeZip`](#kowtsefaturapackagingdfezip)
- [`Kowts\Efatura\Packaging\EventZip`](#kowtsefaturapackagingeventzip)
- [`Kowts\Efatura\Validation\DocumentValidator`](#kowtsefaturavalidationdocumentvalidator)
- [`Kowts\Efatura\Validation\EventValidator`](#kowtsefaturavalidationeventvalidator)
- [`Kowts\Efatura\Validation\IssueDateValidator`](#kowtsefaturavalidationissuedatevalidator)
- [`Kowts\Efatura\Xml\DfeXmlBuilder`](#kowtsefaturaxmldfexmlbuilder)
- [`Kowts\Efatura\Xml\EventXmlBuilder`](#kowtsefaturaxmleventxmlbuilder)
- [`Kowts\Efatura\Xml\Xml`](#kowtsefaturaxmlxml)

## `Kowts\Efatura\Bridge\Laravel\EfaturaServiceProvider`

- Tipo: Classe
- Ficheiro: `src/Bridge/Laravel/EfaturaServiceProvider.php`
- Resumo: Regista a fachada como singleton numa aplicação Laravel.

### Métodos públicos

#### `register()`

```php
public function register(): void
```

#### `boot()`

```php
public function boot(): void
```


## `Kowts\Efatura\Bridge\Symfony\EfaturaBundle`

- Tipo: Classe
- Ficheiro: `src/Bridge/Symfony/EfaturaBundle.php`
- Resumo: Bundle de integração automática com o contentor Symfony.

### Métodos públicos

#### `getContainerExtension()`

```php
public function getContainerExtension(): EfaturaExtension
```


## `Kowts\Efatura\Bridge\Symfony\EfaturaExtension`

- Tipo: Classe
- Ficheiro: `src/Bridge/Symfony/EfaturaExtension.php`
- Resumo: Carrega a configuração `efatura_cv` no contentor Symfony.

### Métodos públicos

#### `load()`

```php
public function load(array $configs, ContainerBuilder $container): void
```

#### `getAlias()`

```php
public function getAlias(): string
```


## `Kowts\Efatura\Bridge\Yii2\EfaturaBootstrap`

- Tipo: Classe
- Ficheiro: `src/Bridge/Yii2/EfaturaBootstrap.php`
- Resumo: Bootstrap opcional para registar o componente e-Fatura numa aplicação Yii2.

### Métodos públicos

#### `bootstrap()`

```php
public function bootstrap($app): void
```


## `Kowts\Efatura\Bridge\Yii2\EfaturaComponent`

- Tipo: Classe
- Ficheiro: `src/Bridge/Yii2/EfaturaComponent.php`
- Resumo: Componente Yii2 para expor a fachada e-Fatura através de `Yii::$app->efatura`.

### Métodos públicos

#### `getClient()`

```php
public function getClient(): Efatura
```

#### `getEfatura()`

```php
public function getEfatura(): Efatura
```

#### `setClient()`

```php
public function setClient(Efatura $client): void
```

#### `setFactory()`

```php
public function setFactory(callable $factory): void
```

Define uma factory opcional para construir a fachada principal.

#### `getFactory()`

```php
public function getFactory(): ?callable
```

#### `__call()`

```php
public function __call($name, $params)
```

Encaminha chamadas desconhecidas para a fachada principal.


## `Kowts\Efatura\Builder\DocumentBuilder`

- Tipo: Classe
- Ficheiro: `src/Builder/DocumentBuilder.php`
- Resumo: Construtor fluente para documentos fiscais.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly DocumentValidator $validator, private readonly array $defaults = [])
```

#### `type()`

```php
public function type(DocumentType $type): self
```

#### `issueDate()`

```php
public function issueDate(string $date): self
```

#### `issueTime()`

```php
public function issueTime(string $time): self
```

#### `emitter()`

```php
public function emitter(array $party): self
```

#### `receiver()`

```php
public function receiver(?array $party): self
```

#### `line()`

```php
public function line(array $line): self
```

#### `lines()`

```php
public function lines(array $lines): self
```

#### `totals()`

```php
public function totals(array $totals): self
```

#### `set()`

```php
public function set(string $field, mixed $value): self
```

Permite definir campos menos comuns sem alargar continuamente a API fluente.

#### `toArray()`

```php
public function toArray(): array
```

#### `validate()`

```php
public function validate(): array
```

#### `dto()`

```php
public function dto(): FiscalDocument
```


## `Kowts\Efatura\Config\EfaturaConfig`

- Tipo: Classe
- Ficheiro: `src/Config/EfaturaConfig.php`
- Resumo: Configuração imutável de uma instância e-Fatura.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $transmitterNif, public readonly string $transmitterLed, public readonly string $softwareCode, public readonly string $softwareName, public readonly string $softwareVersion, public readonly ?string $middlewareBaseUrl = null, public readonly ?string $transmitterKey = null, public readonly ?string $defaultSerie = null, public readonly ?array $emitter = null, public readonly string $platformBaseUrl = self::DEFAULT_PLATFORM_URL, public readonly string $dfaBaseUrl = self::DEFAULT_DFA_URL, public readonly Environment $environment = Environment::Test, public readonly string $middlewareDfePath = '/v1/dfe', public readonly string $platformDfePath = '/v1/dfe', public readonly string $middlewareEventPath = '/v1/event', public readonly string $platformEventPath = '/v1/event')
```

#### `fromArray()`

```php
public static function fromArray(array $config): self
```

Constrói a configuração imutável a partir da estrutura usada por frameworks e ficheiros de configuração.

#### `repositoryCode()`

```php
public function repositoryCode(): int
```

#### `middlewareBaseUrlOrFail()`

```php
public function middlewareBaseUrlOrFail(): string
```

#### `emitterOrFail()`

```php
public function emitterOrFail(): array
```

#### `assertNif()`

```php
public static function assertNif(string $nif, string $field = 'nif'): void
```


## `Kowts\Efatura\Contract\Clock`

- Tipo: Interface
- Ficheiro: `src/Contract/Clock.php`
- Resumo: Fonte de tempo substituível para regras fiscais e testes determinísticos.

### Métodos públicos

#### `now()`

```php
public function now(): DateTimeImmutable
```


## `Kowts\Efatura\Contract\DocumentStatusClient`

- Tipo: Interface
- Ficheiro: `src/Contract/DocumentStatusClient.php`

### Métodos públicos

#### `lookupDocument()`

```php
public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult
```


## `Kowts\Efatura\Contract\EmitterAuthorizationClient`

- Tipo: Interface
- Ficheiro: `src/Contract/EmitterAuthorizationClient.php`

### Métodos públicos

#### `checkEmitterAuthorization()`

```php
public function checkEmitterAuthorization(string $transmitterNif, string $emitterNif, ?string $accessToken = null): RegistryResult
```


## `Kowts\Efatura\Contract\MiddlewareTransport`

- Tipo: Interface
- Ficheiro: `src/Contract/MiddlewareTransport.php`
- Resumo: Envia pacotes DFE para um middleware e-Fatura.

### Métodos públicos

#### `submit()`

```php
public function submit(string $baseUrl, string $transmitterKey, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Contract\PlatformTransport`

- Tipo: Interface
- Ficheiro: `src/Contract/PlatformTransport.php`
- Resumo: Envia pacotes directamente para a plataforma electrónica.

### Métodos públicos

#### `submit()`

```php
public function submit(string $baseUrl, string $accessToken, int $repositoryCode, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Contract\SelfBillingAuthorizationClient`

- Tipo: Interface
- Ficheiro: `src/Contract/SelfBillingAuthorizationClient.php`
- Resumo: Cliente para pedir à PE/DNRE o código de autorização de autofacturação.

### Métodos públicos

#### `authorizeSelfBilling()`

```php
public function authorizeSelfBilling(string $sellerTaxId, DocumentType $documentType, string $mobilePhoneNumber, int|float|string $totalAmount, ?string $accessToken = null): SelfBillingAuthorizationResult
```


## `Kowts\Efatura\Contract\SequenceStore`

- Tipo: Interface
- Ficheiro: `src/Contract/SequenceStore.php`
- Resumo: Reserva números fiscais de forma atómica por NIF, ano, LED e tipo.

### Métodos públicos

#### `next()`

```php
public function next(string $nif, int $year, string $led, DocumentType $type): int
```

#### `current()`

```php
public function current(string $nif, int $year, string $led, DocumentType $type): ?int
```

#### `reset()`

```php
public function reset(string $nif, int $year, string $led, DocumentType $type): void
```


## `Kowts\Efatura\Contract\SoftwareRegistryClient`

- Tipo: Interface
- Ficheiro: `src/Contract/SoftwareRegistryClient.php`

### Métodos públicos

#### `lookupSoftware()`

```php
public function lookupSoftware(string $code, ?string $accessToken = null): RegistryResult
```


## `Kowts\Efatura\Contract\SubmissionRegistry`

- Tipo: Interface
- Ficheiro: `src/Contract/SubmissionRegistry.php`
- Resumo: Regista tentativas de submissão para evitar reenvios acidentais.

### Métodos públicos

#### `claim()`

```php
public function claim(string $digest): bool
```

Reserva um digest e devolve falso quando este já foi submetido.


## `Kowts\Efatura\Contract\TaxpayerRegistryClient`

- Tipo: Interface
- Ficheiro: `src/Contract/TaxpayerRegistryClient.php`

### Métodos públicos

#### `lookupTaxpayer()`

```php
public function lookupTaxpayer(string $nif, ?string $accessToken = null): RegistryResult
```


## `Kowts\Efatura\Contract\XmlSigner`

- Tipo: Interface
- Ficheiro: `src/Contract/XmlSigner.php`
- Resumo: Assina um documento XML e devolve os metadados da assinatura.

### Métodos públicos

#### `sign()`

```php
public function sign(string $xml, string $certificate, string $privateKey, ?string $privateKeyPassword = null, ?DateTimeInterface $signingTime = null): array
```


## `Kowts\Efatura\Dfa\DfaDocument`

- Tipo: Classe
- Ficheiro: `src/Dfa/DfaDocument.php`
- Resumo: Documento fiscal auxiliar pronto para guardar ou enviar numa resposta HTTP.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $contents, public readonly string $mimeType, public readonly string $filename)
```


## `Kowts\Efatura\Dfa\PdfDfaRenderer`

- Tipo: Classe
- Ficheiro: `src/Dfa/PdfDfaRenderer.php`
- Resumo: Renderiza um DFA A4 com resumo fiscal, contingência e QR Code.

### Métodos públicos

#### `render()`

```php
public function render(string $iud, FiscalDocument $document, string $qrCodeUrl, string $currency = 'CVE'): DfaDocument
```


## `Kowts\Efatura\Domain\Data\Address`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/Address.php`
- Resumo: Morada fiscal imutável, preservando todos os campos reconhecidos pelo XSD.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $countryCode, array $data = [])
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\DocumentLine`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/DocumentLine.php`
- Resumo: Linha fiscal imutável.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly Decimal $quantity, public readonly string $unitCode, public readonly ?Decimal $price, public readonly ?Decimal $priceExtension, public readonly ?Decimal $netTotal, public readonly array $taxes, public readonly array $item, array $data)
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\DocumentTotals`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/DocumentTotals.php`
- Resumo: Totais fiscais imutáveis.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly Decimal $priceExtension, public readonly Decimal $net, public readonly Decimal $tax, public readonly Decimal $payable, array $data)
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\FiscalDocument`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/FiscalDocument.php`
- Resumo: Documento fiscal imutável com acesso tipado e conversão sem perdas para array.

### Métodos públicos

#### `fromArray()`

```php
public static function fromArray(array $data, ?DocumentValidator $validator = null): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\Party`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/Party.php`
- Resumo: Entidade fiscal imutável.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly ?TaxId $taxId, public readonly ?string $name, public readonly ?Address $address = null, public readonly ?array $contacts = null, public readonly ?string $reference = null, public readonly ?string $fiscalFramework = null, array $data = [])
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\Tax`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/Tax.php`
- Resumo: Imposto aplicado a uma linha.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly TaxType $type, public readonly ?Decimal $percentage, public readonly ?Decimal $total, public readonly ?string $exemptionReasonCode, array $data)
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Data\TaxId`

- Tipo: Classe
- Ficheiro: `src/Domain/Data/TaxId.php`
- Resumo: Identificação fiscal imutável de uma entidade.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $countryCode, public readonly string $value)
```

#### `fromArray()`

```php
public static function fromArray(array $data): self
```

#### `toArray()`

```php
public function toArray(): array
```


## `Kowts\Efatura\Domain\Decimal`

- Tipo: Classe
- Ficheiro: `src/Domain/Decimal.php`
- Resumo: Valor decimal exacto, sem aritmética binária de ponto flutuante.

### Métodos públicos

#### `from()`

```php
public static function from(int|float|string $value, string $field = 'decimal'): self
```

#### `normalise()`

```php
public static function normalise(int|float|string $value, string $field = 'decimal'): string
```

#### `toScaledInteger()`

```php
public function toScaledInteger(int $scale): int
```

Converte o decimal numa unidade inteira, arredondando half-up.

#### `format()`

```php
public function format(int $scale = 2): string
```

#### `__toString()`

```php
public function __toString(): string
```


## `Kowts\Efatura\Domain\DocumentType`

- Tipo: Enum
- Ficheiro: `src/Domain/DocumentType.php`
- Resumo: Tipos de documento fiscal electrónico definidos pelo e-Fatura.

### Métodos públicos

#### `code()`

```php
public function code(): int
```

#### `iudCode()`

```php
public function iudCode(): string
```

#### `xmlElement()`

```php
public function xmlElement(): string
```

#### `fromCode()`

```php
public static function fromCode(int|string $code): self
```


## `Kowts\Efatura\Domain\EmissionMode`

- Tipo: Enum
- Ficheiro: `src/Domain/EmissionMode.php`
- Resumo: Modos de emissão previstos pelo e-Fatura.

### Métodos públicos

#### `code()`

```php
public function code(): int
```

#### `requiresContingency()`

```php
public function requiresContingency(): bool
```


## `Kowts\Efatura\Domain\Environment`

- Tipo: Enum
- Ficheiro: `src/Domain/Environment.php`
- Resumo: Ambientes de comunicação com a plataforma.

### Métodos públicos

#### `repositoryCode()`

```php
public function repositoryCode(): int
```


## `Kowts\Efatura\Domain\EventId`

- Tipo: Classe
- Ficheiro: `src/Domain/EventId.php`
- Resumo: Identificador determinístico de um evento fiscal.

### Métodos públicos

#### `build()`

```php
public static function build(int $repositoryCode, DateTimeInterface|string $issueDateTime, string $transmitterNif): string
```

#### `isValid()`

```php
public static function isValid(string $eventId): bool
```

#### `parse()`

```php
public static function parse(string $eventId): array
```


## `Kowts\Efatura\Domain\EventType`

- Tipo: Enum
- Ficheiro: `src/Domain/EventType.php`
- Resumo: Eventos fiscais suportados.

## `Kowts\Efatura\Domain\Iud`

- Tipo: Classe
- Ficheiro: `src/Domain/Iud.php`
- Resumo: Gera, valida e interpreta o Identificador Único de Documento (IUD).

### Métodos públicos

#### `build()`

```php
public static function build(int $repositoryCode, DateTimeInterface|string $issueDate, string $emitterNif, string $led, DocumentType $documentType, int|string $documentNumber, int|string|null $randomCode = null): string
```

#### `isValid()`

```php
public static function isValid(string $iud): bool
```

#### `parse()`

```php
public static function parse(string $iud): array
```

led:string, documentTypeCode:string, documentNumber:string, randomCode:string, checkDigit:string}

#### `luhnDigit()`

```php
public static function luhnDigit(string $payload): string
```


## `Kowts\Efatura\Domain\TaxType`

- Tipo: Enum
- Ficheiro: `src/Domain/TaxType.php`
- Resumo: Códigos de imposto aceites pelo formato DFE.

## `Kowts\Efatura\Efatura`

- Tipo: Classe
- Ficheiro: `src/Efatura.php`
- Resumo: Fachada principal e independente de frameworks.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly EfaturaConfig $config, private readonly SequenceStore $sequenceStore = new InMemorySequenceStore(), private readonly XmlSigner $xmlSigner = new XadesBesSigner(), private readonly MiddlewareTransport $middlewareTransport = new CurlMiddlewareTransport(), private readonly PlatformTransport $platformTransport = new CurlPlatformTransport(), private readonly XsdValidator $xsdValidator = new XsdValidator(), private readonly CertificateValidator $certificateValidator = new CertificateValidator(), private readonly Clock $clock = new SystemClock(), private readonly SubmissionRegistry $submissionRegistry = new InMemorySubmissionRegistry())
```

#### `document()`

```php
public function document(): DocumentBuilder
```

#### `validateDocument()`

```php
public function validateDocument(array $document): array
```

#### `documentFromArray()`

```php
public function documentFromArray(array $document): FiscalDocument
```

Cria um documento imutável e tipado a partir da API por arrays.

#### `nextDocumentNumber()`

```php
public function nextDocumentNumber(string $issueDate, DocumentType $type): int
```

#### `buildIud()`

```php
public function buildIud(DateTimeInterface|string $issueDate, DocumentType $type, int|string $documentNumber, int|string|null $randomCode = null): string
```

#### `buildSequentialIud()`

```php
public function buildSequentialIud(string $issueDate, DocumentType $type, int|string|null $randomCode = null): string
```

#### `buildEventId()`

```php
public function buildEventId(DateTimeInterface|string $issueDateTime): string
```

#### `buildDfeXml()`

```php
public function buildDfeXml(string $iud, array|FiscalDocument $document, EmissionMode $mode = EmissionMode::Online): string
```

#### `buildEventXml()`

```php
public function buildEventXml(string $eventId, array $event, EmissionMode $mode = EmissionMode::Online): string
```

#### `buildEventZip()`

```php
public function buildEventZip(array $files): string
```

#### `validateXml()`

```php
public function validateXml(string $xml): array
```

#### `signXml()`

```php
public function signXml(string $xml, string $certificate, string $privateKey, ?string $privateKeyPassword = null, ?DateTimeInterface $signingTime = null): array
```

#### `validateCertificate()`

```php
public function validateCertificate(string $certificate, ?string $privateKey = null, ?string $privateKeyPassword = null, ?string $trustStore = null): array
```

#### `verifyXmlSignature()`

```php
public function verifyXmlSignature(string $xml): array
```

#### `loadPkcs12()`

```php
public function loadPkcs12(string $contents, string $password): array
```

#### `buildDfeZip()`

```php
public function buildDfeZip(array $files): string
```

#### `submitDfeZip()`

```php
public function submitDfeZip(string $zip): array
```

#### `submitDfeZipResult()`

```php
public function submitDfeZipResult(string $zip, bool $allowResubmission = false): SubmissionResult
```

#### `submitEventZip()`

```php
public function submitEventZip(string $zip): array
```

#### `submitEventZipResult()`

```php
public function submitEventZipResult(string $zip, bool $allowResubmission = false): SubmissionResult
```

#### `submitEventZipToPlatformResult()`

```php
public function submitEventZipToPlatformResult(string $zip, string $accessToken, ?string $baseUrl = null, bool $allowResubmission = false): SubmissionResult
```

#### `submitDfeZipToPlatform()`

```php
public function submitDfeZipToPlatform(string $zip, string $accessToken, ?string $baseUrl = null): array
```

#### `submitDfeZipToPlatformResult()`

```php
public function submitDfeZipToPlatformResult(string $zip, string $accessToken, ?string $baseUrl = null, bool $allowResubmission = false): SubmissionResult
```

#### `dfaQrCodeUrl()`

```php
public function dfaQrCodeUrl(string $iud): string
```

#### `renderDfa()`

```php
public function renderDfa(string $iud, array|FiscalDocument $document, string $currency = 'CVE'): DfaDocument
```

#### `validateFiscalReadiness()`

```php
public function validateFiscalReadiness(array|FiscalDocument $document, TaxpayerRegistryClient $taxpayers, SoftwareRegistryClient $software, EmitterAuthorizationClient $authorizations, ?string $accessToken = null): array
```


## `Kowts\Efatura\EfaturaFactory`

- Tipo: Classe
- Ficheiro: `src/EfaturaFactory.php`
- Resumo: Converte configuração de frameworks e ficheiros em objectos da biblioteca.

### Métodos públicos

#### `fromArray()`

```php
public static function fromArray(array $config): Efatura
```


## `Kowts\Efatura\Exception\EfaturaException`

- Tipo: Classe
- Ficheiro: `src/Exception/EfaturaException.php`
- Resumo: Excepção base da biblioteca.

## `Kowts\Efatura\Exception\SubmissionUncertainException`

- Tipo: Classe
- Ficheiro: `src/Exception/SubmissionUncertainException.php`
- Resumo: Indica que não foi possível confirmar o resultado de uma submissão.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $channel, Throwable $previous)
```


## `Kowts\Efatura\Exception\ValidationException`

- Tipo: Classe
- Ficheiro: `src/Exception/ValidationException.php`
- Resumo: Representa uma violação de uma regra local ou fiscal.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly string $field, string $message, public readonly string $errorCode = 'validation.invalid')
```


## `Kowts\Efatura\Fiscal\FiscalReadinessService`

- Tipo: Classe
- Ficheiro: `src/Fiscal/FiscalReadinessService.php`
- Resumo: Agrega verificações externas necessárias antes de uma emissão real.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly EfaturaConfig $config, private readonly TaxpayerRegistryClient $taxpayers, private readonly SoftwareRegistryClient $software, private readonly EmitterAuthorizationClient $authorizations)
```

#### `validate()`

```php
public function validate(FiscalDocument $document, ?string $accessToken = null): array
```


## `Kowts\Efatura\Fiscal\ReconciliationResult`

- Tipo: Classe
- Ficheiro: `src/Fiscal/ReconciliationResult.php`
- Resumo: Resultado normalizado da reconciliação de uma submissão.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly ReconciliationStatus $status, public readonly array $data = [], public readonly array $issues = [])
```


## `Kowts\Efatura\Fiscal\ReconciliationStatus`

- Tipo: Enum
- Ficheiro: `src/Fiscal/ReconciliationStatus.php`
- Resumo: Estado obtido ao reconciliar uma submissão com a autoridade fiscal.

## `Kowts\Efatura\Fiscal\RegistryResult`

- Tipo: Classe
- Ficheiro: `src/Fiscal/RegistryResult.php`
- Resumo: Resultado normalizado de uma consulta a um registo fiscal.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly bool $found, public readonly ?bool $active, public readonly array $data = [], public readonly array $issues = [])
```


## `Kowts\Efatura\Fiscal\SelfBillingAuthorizationResult`

- Tipo: Classe
- Ficheiro: `src/Fiscal/SelfBillingAuthorizationResult.php`
- Resumo: Resultado normalizado do pedido de autorização para autofacturação.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly bool $succeeded, public readonly ?string $authorizationId = null, public readonly ?int $authorizationCodeExpirationSeconds = null, public readonly ?string $iud = null, public readonly ?string $serie = null, public readonly ?string $ledCode = null, public readonly ?int $documentNumber = null, public readonly array $messages = [], public readonly array $rawData = [])
```

#### `fromPlatformResponse()`

```php
public static function fromPlatformResponse(array $data): self
```


## `Kowts\Efatura\Fiscal\SubmissionReconciler`

- Tipo: Classe
- Ficheiro: `src/Fiscal/SubmissionReconciler.php`
- Resumo: Confirma o estado remoto de uma submissão antes de qualquer reenvio.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly DocumentStatusClient $client)
```

#### `reconcile()`

```php
public function reconcile(string $iud, ?string $accessToken = null): ReconciliationResult
```


## `Kowts\Efatura\Http\SubmissionResult`

- Tipo: Classe
- Ficheiro: `src/Http/SubmissionResult.php`
- Resumo: Resultado HTTP imutável de uma submissão fiscal.

### Métodos públicos

#### `__construct()`

```php
public function __construct(public readonly bool $ok, public readonly int $status, public readonly string $statusText, public readonly mixed $body, public readonly string $rawBody, public readonly array $headers = [])
```

#### `toArray()`

```php
public function toArray(): array
```

#### `jsonSerialize()`

```php
public function jsonSerialize(): array
```


## `Kowts\Efatura\Infrastructure\Clock\FrozenClock`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Clock/FrozenClock.php`
- Resumo: Relógio imutável útil em testes, importações e reprocessamentos controlados.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly DateTimeImmutable $dateTime)
```

#### `now()`

```php
public function now(): DateTimeImmutable
```


## `Kowts\Efatura\Infrastructure\Clock\SystemClock`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Clock/SystemClock.php`
- Resumo: Relógio do sistema no fuso horário de Cabo Verde.

### Métodos públicos

#### `now()`

```php
public function now(): DateTimeImmutable
```


## `Kowts\Efatura\Infrastructure\Fiscal\Psr18FiscalAuthorityClient`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Fiscal/Psr18FiscalAuthorityClient.php`
- Resumo: Cliente PSR-18 com rotas configuráveis para serviços PE/DNRE.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly ClientInterface $client, private readonly RequestFactoryInterface $requests, private readonly string $baseUrl, private readonly array $routes = [])
```

selfBillingAuthorization?:string} $routes

#### `lookupTaxpayer()`

```php
public function lookupTaxpayer(string $nif, ?string $accessToken = null): RegistryResult
```

#### `lookupSoftware()`

```php
public function lookupSoftware(string $code, ?string $accessToken = null): RegistryResult
```

#### `checkEmitterAuthorization()`

```php
public function checkEmitterAuthorization(string $transmitterNif, string $emitterNif, ?string $accessToken = null): RegistryResult
```

#### `lookupDocument()`

```php
public function lookupDocument(string $iud, ?string $accessToken = null): RegistryResult
```

#### `authorizeSelfBilling()`

```php
public function authorizeSelfBilling(string $sellerTaxId, DocumentType $documentType, string $mobilePhoneNumber, int|float|string $totalAmount, ?string $accessToken = null): SelfBillingAuthorizationResult
```


## `Kowts\Efatura\Infrastructure\Http\CurlClient`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/CurlClient.php`
- Resumo: Cliente HTTP interno, pequeno e substituível, baseado na extensão cURL.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly int $timeout = 60, private readonly int $connectTimeout = 10, private readonly LoggerInterface $logger = new NullLogger())
```

#### `post()`

```php
public function post(string $url, array $headers, string|array $body): SubmissionResult
```


## `Kowts\Efatura\Infrastructure\Http\CurlMiddlewareTransport`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/CurlMiddlewareTransport.php`
- Resumo: Transporte para um endpoint configurável de middleware.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly CurlClient $client = new CurlClient())
```

#### `submit()`

```php
public function submit(string $baseUrl, string $transmitterKey, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Infrastructure\Http\CurlPlatformTransport`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/CurlPlatformTransport.php`
- Resumo: Transporte multipart para submissão directa à plataforma.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly CurlClient $client = new CurlClient())
```

#### `submit()`

```php
public function submit(string $baseUrl, string $accessToken, int $repositoryCode, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Infrastructure\Http\InMemorySubmissionRegistry`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/InMemorySubmissionRegistry.php`
- Resumo: Protecção por processo; aplicações distribuídas devem injectar persistência partilhada.

### Métodos públicos

#### `claim()`

```php
public function claim(string $digest): bool
```


## `Kowts\Efatura\Infrastructure\Http\Psr18MiddlewareTransport`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/Psr18MiddlewareTransport.php`
- Resumo: Transporte middleware interoperável com qualquer cliente PSR-18.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly ClientInterface $client, private readonly RequestFactoryInterface $requests, private readonly StreamFactoryInterface $streams, private readonly LoggerInterface $logger = new NullLogger())
```

#### `submit()`

```php
public function submit(string $baseUrl, string $transmitterKey, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Infrastructure\Http\Psr18PlatformTransport`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/Psr18PlatformTransport.php`
- Resumo: Transporte PSR-18 multipart para a plataforma e-Fatura.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly ClientInterface $client, private readonly RequestFactoryInterface $requests, private readonly StreamFactoryInterface $streams)
```

#### `submit()`

```php
public function submit(string $baseUrl, string $accessToken, int $repositoryCode, string $zip, string $endpointPath = '/v1/dfe'): SubmissionResult
```


## `Kowts\Efatura\Infrastructure\Http\ResponseParser`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/ResponseParser.php`
- Resumo: Interpreta respostas JSON e XML sem perder o corpo original.

### Métodos públicos

#### `parse()`

```php
public static function parse(string $body, string $contentType = ''): mixed
```


## `Kowts\Efatura\Infrastructure\Http\RetryingPsr18Client`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Http/RetryingPsr18Client.php`
- Resumo: Repete pedidos HTTP idempotentes com espera exponencial limitada.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly ClientInterface $client, private readonly int $maxAttempts = 3, private readonly int $initialDelayMs = 250, private readonly int $maximumDelayMs = 2_000, private readonly array $retryableStatuses = [425, 429, 500, 502, 503, 504], ?Closure $sleeper = null)
```

#### `sendRequest()`

```php
public function sendRequest(RequestInterface $request): ResponseInterface
```


## `Kowts\Efatura\Infrastructure\Sequence\InMemorySequenceStore`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Sequence/InMemorySequenceStore.php`
- Resumo: Armazenamento destinado apenas a testes e processos de curta duração.

### Métodos públicos

#### `next()`

```php
public function next(string $nif, int $year, string $led, DocumentType $type): int
```

#### `current()`

```php
public function current(string $nif, int $year, string $led, DocumentType $type): ?int
```

#### `reset()`

```php
public function reset(string $nif, int $year, string $led, DocumentType $type): void
```


## `Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Sequence/PdoSequenceStore.php`
- Resumo: Sequências persistentes e atómicas para SQLite, MySQL/MariaDB e PostgreSQL.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly PDO $pdo, private readonly string $table = 'efatura_sequences')
```

#### `createTable()`

```php
public function createTable(): void
```


## `Kowts\Efatura\Infrastructure\Signing\CertificateValidator`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Signing/CertificateValidator.php`
- Resumo: Verifica o conteúdo, a validade temporal e a correspondência da chave privada.

### Métodos públicos

#### `validate()`

```php
public function validate(string $certificate, ?string $privateKey = null, ?string $privateKeyPassword = null, ?string $trustStore = null): array
```


## `Kowts\Efatura\Infrastructure\Signing\Pkcs12Loader`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Signing/Pkcs12Loader.php`
- Resumo: Extrai certificado, chave privada e cadeia adicional de um ficheiro PKCS#12/PFX.

### Métodos públicos

#### `load()`

```php
public function load(string $contents, string $password): array
```


## `Kowts\Efatura\Infrastructure\Signing\XadesBesSigner`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Signing/XadesBesSigner.php`
- Resumo: Assinador XAdES-BES enveloped com RSA-SHA256 e canonicalização C14N 1.0.

### Métodos públicos

#### `sign()`

```php
public function sign(string $xml, string $certificate, string $privateKey, ?string $privateKeyPassword = null, ?DateTimeInterface $signingTime = null): array
```


## `Kowts\Efatura\Infrastructure\Signing\XmlSignatureVerifier`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Signing/XmlSignatureVerifier.php`
- Resumo: Verifica a assinatura RSA, a estrutura XAdES e os digests de um XML.

### Métodos públicos

#### `verify()`

```php
public function verify(string $xml): array
```


## `Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Submission/PdoSubmissionRegistry.php`
- Resumo: Registo de idempotência persistente e atómico para SQLite, MySQL/MariaDB e PostgreSQL.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly PDO $pdo, private readonly string $table = 'efatura_submissions')
```

#### `createTable()`

```php
public function createTable(): void
```


## `Kowts\Efatura\Infrastructure\Validation\XsdValidator`

- Tipo: Classe
- Ficheiro: `src/Infrastructure/Validation/XsdValidator.php`
- Resumo: Valida XML com libxml e os XSD oficiais distribuídos com o pacote.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly ?string $schemaPath = null)
```

#### `validate()`

```php
public function validate(string $xml): array
```


## `Kowts\Efatura\Packaging\DfeZip`

- Tipo: Classe
- Ficheiro: `src/Packaging/DfeZip.php`
- Resumo: Empacota um ou mais DFE como {IUD}.xml com compressão Deflate.

### Métodos públicos

#### `build()`

```php
public function build(array $files): string
```


## `Kowts\Efatura\Packaging\EventZip`

- Tipo: Classe
- Ficheiro: `src/Packaging/EventZip.php`
- Resumo: Empacota eventos fiscais como {EventId}.xml com compressão Deflate.

### Métodos públicos

#### `build()`

```php
public function build(array $files): string
```


## `Kowts\Efatura\Validation\DocumentValidator`

- Tipo: Classe
- Ficheiro: `src/Validation/DocumentValidator.php`
- Resumo: Valida e normaliza um documento antes de este ser serializado para XML.

### Métodos públicos

#### `validate()`

```php
public function validate(array $document): array
```


## `Kowts\Efatura\Validation\EventValidator`

- Tipo: Classe
- Ficheiro: `src/Validation/EventValidator.php`
- Resumo: Valida eventos de anulação e inutilização antes da serialização.

### Métodos públicos

#### `validate()`

```php
public function validate(array $event): array
```


## `Kowts\Efatura\Validation\IssueDateValidator`

- Tipo: Classe
- Ficheiro: `src/Validation/IssueDateValidator.php`
- Resumo: Aplica as janelas temporais definidas para emissão online e em contingência.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly Clock $clock)
```

#### `validate()`

```php
public function validate(string $issueDate, ?string $issueTime, EmissionMode $mode): DateTimeImmutable
```


## `Kowts\Efatura\Xml\DfeXmlBuilder`

- Tipo: Classe
- Ficheiro: `src/Xml/DfeXmlBuilder.php`
- Resumo: Gera XML DFE v11 compacto, seguindo a ordem dos elementos dos XSD oficiais.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly EfaturaConfig $config, private readonly DocumentValidator $validator)
```

#### `build()`

```php
public function build(string $iud, array $document, EmissionMode $mode = EmissionMode::Online): string
```


## `Kowts\Efatura\Xml\EventXmlBuilder`

- Tipo: Classe
- Ficheiro: `src/Xml/EventXmlBuilder.php`
- Resumo: Gera eventos de anulação e inutilização de numeração.

### Métodos públicos

#### `__construct()`

```php
public function __construct(private readonly EfaturaConfig $config, private readonly EventValidator $validator = new EventValidator())
```

#### `build()`

```php
public function build(string $eventId, array $event, EmissionMode $mode = EmissionMode::Online): string
```


## `Kowts\Efatura\Xml\Xml`

- Tipo: Classe
- Ficheiro: `src/Xml/Xml.php`
- Resumo: Operações pequenas e previsíveis para serialização XML compacta.

### Métodos públicos

#### `escape()`

```php
public static function escape(string|int|float|bool $value): string
```

#### `element()`

```php
public static function element(string $name, string|int|float|bool|null $value): string
```
