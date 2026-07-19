# Changelog

Todas as alterações relevantes serão registadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-PT/1.1.0/) e
o projecto adopta [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

### Adicionado

- auditoria técnica do projecto em `docs/auditoria.md`.
- guia de persistência PDO em `docs/persistencia-pdo.md`.

### Alterado

- política de checkout e arquivo do pacote documentada por `.editorconfig` e
  `.gitattributes`;
- configuração Laravel passa a indicar explicitamente que depende do helper
  `env()` do framework.
- documentação de persistência passa a declarar explicitamente SQLite,
  MySQL/MariaDB e PostgreSQL como motores suportados e SQL Server como não
  suportado sem implementação específica.

### Segurança

- transporte cURL passa a declarar explicitamente a validação TLS.

## [0.3.0] - 2026-07-11

### Adicionado

- `EfaturaComponent` para Yii2 passa a aceitar `factory` personalizada para
  construir `Efatura` com dependências persistentes;
- `EfaturaConfig::fromArray()` para reutilizar a mesma configuração de
  frameworks ao construir instâncias manuais;
- guia fiscal completo para Yii2 com `web.php`, `console.php`, migrações,
  `PdoSequenceStore`, `PdoSubmissionRegistry`, emissão, IUD, submissão,
  reconciliação, contingência, PDF e exemplo com `ActiveRecord`;
- referência automática da API em `docs/api-reference.md`, gerada por
  `composer docs:api`;
- validação da referência automática no CI com `composer docs:api:check`;
- job CI específico com Yii2 real para confirmar compatibilidade da bridge;
- job CI de dependências mínimas e recentes para detectar incompatibilidades
  com os limites suportados pelo Composer;
- testes da factory Yii2 com framework real, garantindo que a factory é
  executada apenas uma vez;
- badges de cobertura mínima, PHPStan, Aikido package health e estado beta;
- templates de issues para bugs de integração e questões fiscais/conformidade;
- imagem de topo e fluxo Mermaid de emissão com decisões em `docs/arquitectura.md`.

### Alterado

- documentação Yii2 passa a destacar que o armazenamento em memória é apenas
  para desenvolvimento e testes;
- `EfaturaFactory::fromArray()` reutiliza `EfaturaConfig::fromArray()`;
- `EfaturaComponent` passa a expor `setFactory()`/`getFactory()` em vez de
  depender de propriedade pública `mixed`;
- README e NOTICE deixam de referenciar projectos externos, mantendo a
  identidade própria do pacote.

### Corrigido

- compatibilidade com dependências mínimas suportadas, incluindo PHPStan 1.x,
  Dompdf 2.x e PSR HTTP Message 1.x;
- query PostgreSQL de `PdoSequenceStore` deixou de usar referência ambígua à
  coluna `current_value`;
- documentação e badges do README foram ajustados para evitar links ou sintaxe
  ambígua.

## [0.2.1] - 2026-07-11

### Alterado

- keywords Composer/Packagist alargadas para melhorar a descoberta por
  `efatura-cv`, documentos fiscais, XAdES-BES e integrações Laravel, Symfony e Yii2.

## [0.2.0] - 2026-07-10

### Adicionado

- integração opcional com Yii2 através de `EfaturaComponent` e `EfaturaBootstrap`;
- documentação de configuração para usar `Yii::$app->efatura`;
- stubs de desenvolvimento para validar a bridge Yii2 sem tornar `yiisoft/yii2`
  uma dependência obrigatória da biblioteca.

## [0.1.0] - 2026-07-09

### Adicionado

- núcleo inicial para os nove documentos DFE;
- IUD, eventos, validação fiscal, XML v11 e XSD;
- ciclo de eventos com validação, XML, ZIP e submissão;
- assinatura XAdES-BES, certificados e ZIP;
- sequências e idempotência em memória ou PDO;
- transportes cURL para middleware e plataforma;
- DTOs imutáveis, PSR-18/PSR-3, repetição segura e reconciliação fiscal;
- DFA em PDF com QR Code;
- integrações CLI, Laravel e Symfony;
- documentação e integração contínua em Linux e Windows;
- testes de persistência em SQLite, MySQL e PostgreSQL.

### Alterado

- cálculos fiscais passam a usar representação decimal exacta;
- endpoints DFE e de eventos podem ser configurados independentemente;
- o middleware deixa de ser obrigatório no uso directo da plataforma.

### Segurança

- verificação XMLDSig/XAdES reforçada contra referências ambíguas e wrapping;
- coerência semântica do IUD confirmada no XML e nos pacotes ZIP;
- falhas de transporte são tratadas como submissões de estado incerto.

[Não publicado]: https://github.com/Kowts/efatura-cv-php/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/Kowts/efatura-cv-php/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/Kowts/efatura-cv-php/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/Kowts/efatura-cv-php/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/Kowts/efatura-cv-php/releases/tag/v0.1.0
