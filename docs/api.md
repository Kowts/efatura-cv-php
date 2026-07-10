# Referência da API

## `Efatura`

### Documentos

- `document()`: cria um construtor fluente;
- `validateDocument(array $document)`: normaliza e valida regras locais;
- `documentFromArray(array $document)`: cria um `FiscalDocument` imutável;
- `nextDocumentNumber(string $date, DocumentType $type)`: reserva um número;
- `buildIud(...)`: gera um IUD com um número conhecido;
- `buildSequentialIud(...)`: reserva o número e gera o IUD;
- `buildDfeXml(string $iud, array|FiscalDocument $document, EmissionMode $mode)`: cria o XML;
- `validateXml(string $xml)`: valida com o XSD;
- `signXml(...)`: aplica XAdES-BES;
- `verifyXmlSignature(string $xml)`: verifica a assinatura e os digests;
- `loadPkcs12(string $contents, string $password)`: extrai certificado e chave;
- `buildDfeZip(array $files)`: cria o pacote Deflate.

### Eventos

- `buildEventId(DateTimeInterface|string $date)`: cria o identificador;
- `buildEventXml(string $id, array $event, EmissionMode $mode)`: cria `FDC` ou `UDN`.
- `buildEventZip(array $files)`: cria o pacote ZIP de eventos;
- `submitEventZipResult(...)`: submete eventos ao middleware;
- `submitEventZipToPlatformResult(...)`: submete eventos directamente à plataforma.

### Comunicação

- `submitDfeZip(string $zip)`: envia ao middleware configurado;
- `submitDfeZipResult(string $zip, bool $allowResubmission)`: versão tipada;
- `submitDfeZipToPlatform(...)`: envia directamente à plataforma;
- `submitDfeZipToPlatformResult(...)`: devolve `SubmissionResult`;
- `dfaQrCodeUrl(string $iud)`: devolve o URL público do DFE.

### DFA e prontidão

- `renderDfa(...)`: devolve um `DfaDocument` com o PDF e metadados;
- `validateFiscalReadiness(...)`: verifica contribuinte, software e autorização
  através dos contratos de consulta fiscal.

### Autofacturação

O manual técnico v11.0 exige que o comprador peça previamente uma autorização
ao vendedor através do endpoint `/v1/dfe/self-billing/authorize`, enviando:

- `taxId`: NIF CV do vendedor;
- `documentTypeCode`: tipo de DFE permitido para autofacturação (`1`, `2`, `4`,
  `5`, `6` ou `8`);
- `mobilePhoneNumber`: telemóvel do vendedor;
- `totalAmount`: total a pagar do DFE.

Use `Contract\SelfBillingAuthorizationClient` ou a implementação
`Infrastructure\Fiscal\Psr18FiscalAuthorityClient::authorizeSelfBilling(...)`.
O resultado devolve, quando disponível, `authorizationId`, tempo de expiração,
`iud`, `serie`, `ledCode` e `documentNumber`. O código recebido pelo vendedor
por SMS/email deve depois ser indicado no bloco `selfBilling` do documento:

```php
$authorization = $fiscalClient->authorizeSelfBilling(
    sellerTaxId: '900800700',
    documentType: DocumentType::ElectronicInvoice,
    mobilePhoneNumber: '9911122',
    totalAmount: '1150.00',
    accessToken: $token
);

$document['selfBilling'] = [
    'authorizationId' => $authorization->authorizationId,
    'authorizationCode' => $codigoRecebidoPeloVendedor,
];
```

### Valores decimais

Preços, quantidades, impostos e totais são normalizados sem aritmética binária
de ponto flutuante. A API por arrays devolve representações decimais em string;
os DTOs expõem `Domain\Decimal`, que permite conversão para string e formatação
através de `format()`.

### Certificados

- `validateCertificate(...)`: verifica validade e correspondência da chave;
- `signXml(...)`: assina com certificado e chave PEM.

## Erros

Erros de regras fiscais lançam `ValidationException`, com:

- `field`: caminho do campo;
- `errorCode`: código estável para interfaces;
- `message`: mensagem em português.

Falhas operacionais lançam `EfaturaException`.

## Extensão

Implemente `SequenceStore`, `XmlSigner`, `MiddlewareTransport` ou
`PlatformTransport` e injecte a implementação no construtor da fachada.

Também podem ser substituídos `Clock`, `SubmissionRegistry` e os clientes de
consulta fiscal. Para HTTP compatível com frameworks, use os transportes PSR-18
em `Infrastructure\Http`.

## Linha de comandos

```bash
vendor/bin/efatura iud:validate <IUD>
vendor/bin/efatura iud:parse <IUD>
vendor/bin/efatura xml:validate <ficheiro.xml>
```
