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
