# e-Fatura CV para PHP

[![Testes](https://github.com/Kowts/efatura-cv-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Kowts/efatura-cv-php/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-%5E8.1-777BB4.svg)](https://www.php.net/)
[![Licença](https://img.shields.io/badge/licen%C3%A7a-MIT-blue.svg)](LICENSE)

Biblioteca PHP independente de frameworks para validar documentos fiscais,
gerar XML DFE v11, validar com os XSD oficiais, assinar com XAdES-BES,
criar pacotes ZIP e comunicar com serviços e-Fatura de Cabo Verde.

> [!IMPORTANT]
> Este projecto não é oficial da DNRE. A utilização em produção exige
> homologação do software, credenciais válidas e testes nos ambientes oficiais.

## Funcionalidades

- nove tipos de documento: `FTE`, `FRE`, `TVE`, `RCE`, `NCE`, `NDE`, `DTE`, `DVE` e `NLE`;
- geração, validação e interpretação de IUD;
- validação de NIF, entidades, linhas, impostos e reconciliação de totais;
- emissão online e em contingência;
- geração de XML compacto DFE v11;
- eventos `FDC` e `UDN`;
- validação com os XSD oficiais de 27 de Maio de 2024;
- assinatura XAdES-BES com RSA-SHA256;
- validação de certificados e correspondência da chave privada;
- pacotes ZIP Deflate com nomes `{IUD}.xml`;
- sequências em memória ou transaccionais por PDO;
- submissão por middleware e directamente à plataforma;
- consultas fiscais externas através de contratos e PSR-18;
- DFA em PDF com QR Code;
- DTOs imutáveis e tipados, sem retirar a API por arrays;
- integrações opcionais com Laravel e Symfony;
- CLI para inspeccionar IUDs e validar XML;
- respostas JSON/XML normalizadas;
- nenhuma dependência de Laravel, Symfony ou outro framework.

## Requisitos

- PHP 8.1 ou superior;
- extensões `curl`, `dom`, `json`, `libxml`, `openssl` e `zip`.

## Instalação

```bash
composer require kowts/efatura-cv
```

Enquanto não existir uma versão estável no Packagist, pode usar o repositório
Git directamente:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Kowts/efatura-cv-php"
        }
    ],
    "require": {
        "kowts/efatura-cv": "dev-main"
    }
}
```

## Utilização rápida

```php
<?php

use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Domain\Environment;
use Kowts\Efatura\Efatura;

$efatura = new Efatura(new EfaturaConfig(
    transmitterNif: '123456789',
    transmitterLed: '001',
    softwareCode: 'MEUSOFT',
    softwareName: 'Meu Software',
    softwareVersion: '1.0.0',
    middlewareBaseUrl: 'https://middleware.exemplo.cv',
    transmitterKey: getenv('EFATURA_TRANSMITTER_KEY') ?: null,
    defaultSerie: 'SER-F',
    emitter: [
        'taxId' => ['countryCode' => 'CV', 'value' => '123456789'],
        'name' => 'Empresa, Lda.',
        'address' => ['countryCode' => 'CV', 'addressDetail' => 'Praia'],
        'contacts' => ['email' => 'facturacao@exemplo.cv', 'telephone' => '2600000'],
    ],
    environment: Environment::Test
));

$invoice = $efatura->document()
    ->type(DocumentType::ElectronicInvoice)
    ->issueDate('2026-07-08')
    ->issueTime('10:30:00')
    ->receiver([
        'taxId' => ['countryCode' => 'CV', 'value' => '987654321'],
        'name' => 'Cliente',
    ])
    ->line([
        'quantity' => ['value' => 1, 'unitCode' => 'UN'],
        'price' => 1000,
        'priceExtension' => 1000,
        'netTotal' => 1000,
        'taxes' => [[
            'taxTypeCode' => 'IVA',
            'taxPercentage' => 15,
            'taxTotal' => 150,
        ]],
        'item' => [
            'description' => 'Serviço',
            'emitterIdentification' => 'SERV-1',
        ],
    ])
    ->totals([
        'priceExtensionTotalAmount' => 1000,
        'netTotalAmount' => 1000,
        'taxTotalAmount' => 150,
        'payableAmount' => 1150,
    ])
    ->validate();

$number = $efatura->nextDocumentNumber($invoice['issueDate'], $invoice['type']);
$iud = $efatura->buildIud($invoice['issueDate'], $invoice['type'], $number);
$xml = $efatura->buildDfeXml($iud, $invoice);

$xsd = $efatura->validateXml($xml);
if (!$xsd['valid']) {
    throw new RuntimeException(json_encode($xsd['errors'], JSON_THROW_ON_ERROR));
}

$signed = $efatura->signXml(
    $xml,
    file_get_contents('/segredos/certificado.pem'),
    file_get_contents('/segredos/chave-privada.pem'),
    getenv('EFATURA_KEY_PASSWORD') ?: null
);

$zip = $efatura->buildDfeZip([['iud' => $iud, 'xml' => $signed['xml']]]);
$result = $efatura->submitDfeZip($zip);
```

## Sequências em produção

O armazenamento predefinido existe apenas em memória. Em produção, use uma
base de dados para impedir números duplicados entre processos:

```php
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;

$pdo = new PDO('mysql:host=localhost;dbname=faturacao', 'utilizador', 'senha');
$sequences = new PdoSequenceStore($pdo);
$sequences->createTable(); // Execute uma vez, ou converta para uma migração.

$efatura = new Efatura($config, $sequences);
```

A sequência é independente por NIF transmissor, ano, LED e tipo documental.
Guarde sempre o IUD, XML, ZIP e resposta de transporte. Uma falha de rede não
autoriza a reutilização ou substituição automática do número fiscal.

## Segurança

- nunca envie chaves, certificados, tokens ou credenciais para o navegador;
- não guarde segredos no repositório;
- valide o certificado antes da emissão;
- use TLS e armazenamento cifrado;
- registe respostas técnicas sem expor dados pessoais ou segredos;
- fixe uma versão exacta do pacote em sistemas de produção.

Consulte [Assinatura e certificados](docs/assinatura.md) e
[Segurança](SECURITY.md).

## Documentação

- [Arquitectura](docs/arquitectura.md)
- [Referência da API](docs/api.md)
- [Conformidade](docs/conformidade.md)
- [Assinatura e certificados](docs/assinatura.md)
- [Exemplo completo](examples/invoice.php)
- [Exemplos dos tipos documentais](examples/document-types.php)
- [Contribuir](CONTRIBUTING.md)

## Estado do projecto

O pacote está em desenvolvimento activo. Os XSD oficiais estão incluídos, mas
não foram publicados vectores oficiais para comparar byte a byte IUD, ZIP e
assinaturas. Os testes internos não devem ser confundidos com certificação
oficial.

## Créditos

O desenho foi inspirado por
[`@akira-io/efatura`](https://github.com/akira-io/node-efatura), distribuído
sob MIT/Apache-2.0. Os XSD incluídos são os artefactos do sistema e-Fatura de
Cabo Verde.

## Licença

[MIT](LICENSE) © 2026 Kowts.
