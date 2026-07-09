# Changelog

Todas as alterações relevantes serão registadas neste ficheiro.

O formato segue [Keep a Changelog](https://keepachangelog.com/pt-PT/1.1.0/) e
o projecto adopta [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não publicado]

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

[Não publicado]: https://github.com/Kowts/efatura-cv-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Kowts/efatura-cv-php/releases/tag/v0.1.0
