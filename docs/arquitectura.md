# Arquitectura

O pacote segue dependências orientadas para o domínio:

```mermaid
flowchart TD
    APP["Aplicação consumidora"]
    FACADE["Efatura<br/>Fachada pública"]
    DTO["DTOs imutáveis<br/>FiscalDocument e valores"]
    VALIDATION["Validação<br/>Regras fiscais locais"]
    DOMAIN["Domínio<br/>IUD, eventos e enums"]
    XML["XML<br/>DFE v11 e eventos"]
    DFA["DFA<br/>PDF e QR Code"]
    PACKAGING["Empacotamento<br/>ZIP Deflate"]
    CONTRACTS["Contratos<br/>Pontos de extensão"]
    INFRA["Infra-estrutura<br/>PDO, PSR-18, cURL, OpenSSL e libxml"]
    OFFICIAL["Artefactos oficiais<br/>XSD e-Fatura"]

    APP --> FACADE
    FACADE --> DTO
    FACADE --> VALIDATION
    FACADE --> XML
    FACADE --> DFA
    FACADE --> PACKAGING
    FACADE --> CONTRACTS
    DTO --> DOMAIN
    VALIDATION --> DOMAIN
    XML --> DOMAIN
    XML --> VALIDATION
    INFRA -. "implementa" .-> CONTRACTS
    INFRA --> OFFICIAL
```

## Directórios

- `src/Domain`: tipos fiscais, IUD e identificadores de eventos;
- `src/Domain/Data`: DTOs imutáveis usados na API tipada;
- `src/Validation`: regras locais aplicadas antes do XML;
- `src/Xml`: serialização compacta na ordem exigida pelo XSD;
- `src/Dfa`: representação gráfica do documento em PDF;
- `src/Contract`: pontos de extensão;
- `src/Infrastructure`: PDO, HTTP, XSD e assinatura;
- `src/Bridge`: integrações opcionais com Laravel e Symfony;
- `src/Packaging`: pacote ZIP;
- `resources/xsd`: artefactos oficiais;
- `tests`: testes de domínio, XSD, ZIP, persistência e criptografia.

Uma aplicação pode substituir qualquer transporte, armazenamento de sequência
ou assinador através dos contratos do construtor de `Efatura`.

## Decisões

Os documentos podem entrar como arrays normalizados ou como DTOs imutáveis.
A API por arrays facilita a integração com formulários, filas, ORM e APIs; os
DTOs dão garantias de tipo a aplicações que as pretendam. Nenhuma das opções
depende de um framework. Tipos com um conjunto fechado de valores usam `enum`.

Geração, validação, assinatura, empacotamento e envio são operações separadas.
Esta separação permite guardar cada artefacto e recuperar de falhas sem alterar
o número fiscal.

## Ciclo de emissão

```mermaid
sequenceDiagram
    participant A as Aplicação
    participant E as Efatura
    participant S as SequenceStore
    participant X as Validador XSD
    participant C as Certificado
    participant T as Transporte

    A->>E: validar documento
    E-->>A: FiscalDocument normalizado
    A->>E: criar IUD sequencial
    E->>S: reservar número atomicamente
    S-->>E: número fiscal
    A->>E: gerar XML
    E->>X: validar DFE v11
    X-->>E: resultado
    A->>E: assinar XML
    E->>C: XAdES-BES RSA-SHA256
    C-->>E: XML assinado
    A->>E: criar ZIP e submeter
    E->>T: POST do pacote
    T-->>A: SubmissionResult
```

A reserva do número acontece antes da comunicação externa. A aplicação deve
persistir o IUD, o XML assinado, o ZIP e a resposta para poder reconciliar uma
falha sem reutilizar a numeração.
