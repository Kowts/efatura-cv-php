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
- `src/Bridge`: integrações opcionais com Laravel, Symfony e Yii2;
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

## Fluxo de emissão

```mermaid
flowchart TD
    APP["Aplicação PHP<br/>Laravel · Symfony · Yii2 · PHP puro"]

    subgraph PREP["1. Preparação fiscal"]
        CONFIG["Configuração<br/>NIF, LED, software e ambiente"]
        MODE{"Emissão<br/>online?"}
        CONT["Contingência<br/>IUC, motivo e data"]
        SEQ["Sequência persistente<br/>número fiscal atómico"]
        IUD["IUD válido<br/>45 caracteres + controlo"]
    end

    subgraph DFE["2. Documento electrónico"]
        DOC["DTO ou array<br/>linhas, impostos e totais"]
        RULES{"Regras fiscais<br/>coerentes?"}
        XML["XML DFE v11<br/>namespace oficial"]
        XSD{"XML válido<br/>no XSD oficial?"}
        FIX["Corrigir dados<br/>antes de emitir"]
    end

    subgraph SEC["3. Segurança e pacote"]
        CERT["Certificado digital<br/>PEM ou PKCS#12"]
        CERTOK{"Certificado<br/>válido?"}
        SIGN["Assinatura<br/>XAdES-BES RSA-SHA256"]
        SIGOK{"Assinatura<br/>verificada?"}
        ZIP["ZIP Deflate<br/>{IUD}.xml"]
    end

    subgraph SEND["4. Entrega e acompanhamento"]
        ROUTE{"Canal de<br/>submissão"}
        MID["Middleware<br/>chave do transmissor"]
        PLATFORM["Plataforma e-Fatura<br/>OAuth + repositório"]
        RESULT["Resposta normalizada<br/>JSON/XML"]
        ACCEPTED{"Aceite pela<br/>autoridade?"}
        UNCERTAIN["Estado incerto<br/>consultar antes de reenviar"]
        RECON["Idempotência<br/>e reconciliação"]
        STORE["Guardar IUD, XML,<br/>ZIP e resposta"]
    end

    APP --> CONFIG --> MODE
    MODE -- "Sim" --> SEQ
    MODE -- "Não / offline" --> CONT --> SEQ
    SEQ --> IUD --> DOC --> RULES
    RULES -- "Não" --> FIX --> DOC
    RULES -- "Sim" --> XML --> XSD
    XSD -- "Não" --> FIX
    XSD -- "Sim" --> CERTOK
    CERT --> CERTOK
    CERTOK -- "Não" --> FIX
    CERTOK -- "Sim" --> SIGN --> SIGOK
    SIGOK -- "Não" --> FIX
    SIGOK -- "Sim" --> ZIP --> ROUTE
    ROUTE -- "Middleware" --> MID --> RESULT
    ROUTE -- "Plataforma" --> PLATFORM --> RESULT
    RESULT --> ACCEPTED
    ACCEPTED -- "Sim" --> STORE
    ACCEPTED -- "Não" --> RECON
    ACCEPTED -- "Sem resposta" --> UNCERTAIN --> RECON
    RECON --> STORE

    classDef app fill:#0b3b75,stroke:#60a5fa,color:#ffffff,stroke-width:2px;
    classDef prep fill:#dbeafe,stroke:#2563eb,color:#0f172a;
    classDef dfe fill:#ffedd5,stroke:#f97316,color:#111827;
    classDef sec fill:#dcfce7,stroke:#16a34a,color:#052e16;
    classDef send fill:#ede9fe,stroke:#7c3aed,color:#1e1b4b;
    classDef decision fill:#1f2937,stroke:#facc15,color:#ffffff,stroke-width:2px;
    classDef warn fill:#fee2e2,stroke:#dc2626,color:#7f1d1d,stroke-width:2px;
    classDef done fill:#ccfbf1,stroke:#0f766e,color:#042f2e,stroke-width:2px;

    class APP app;
    class CONFIG,CONT,SEQ,IUD prep;
    class DOC,XML dfe;
    class CERT,SIGN,ZIP sec;
    class MID,PLATFORM,RESULT,RECON,UNCERTAIN send;
    class MODE,RULES,XSD,CERTOK,SIGOK,ROUTE,ACCEPTED decision;
    class FIX warn;
    class STORE done;
```

A reserva do número acontece antes da comunicação externa. A aplicação deve
persistir o IUD, o XML assinado, o ZIP e a resposta para poder reconciliar uma
falha sem reutilizar a numeração.
