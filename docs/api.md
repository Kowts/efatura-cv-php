# Referência da API

## `Efatura`

### Documentos

- `document()`: cria um construtor fluente;
- `validateDocument(array $document)`: normaliza e valida regras locais;
- `nextDocumentNumber(string $date, DocumentType $type)`: reserva um número;
- `buildIud(...)`: gera um IUD com um número conhecido;
- `buildSequentialIud(...)`: reserva o número e gera o IUD;
- `buildDfeXml(string $iud, array $document, EmissionMode $mode)`: cria o XML;
- `validateXml(string $xml)`: valida com o XSD;
- `signXml(...)`: aplica XAdES-BES;
- `buildDfeZip(array $files)`: cria o pacote Deflate.

### Eventos

- `buildEventId(DateTimeInterface|string $date)`: cria o identificador;
- `buildEventXml(string $id, array $event, EmissionMode $mode)`: cria `FDC` ou `UDN`.

### Comunicação

- `submitDfeZip(string $zip)`: envia ao middleware configurado;
- `submitDfeZipToPlatform(...)`: envia directamente à plataforma;
- `dfaQrCodeUrl(string $iud)`: devolve o URL público do DFE.

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
