# Integração fiscal completa com Yii2

Este guia mostra uma integração Yii2 orientada a produção. A bridge incluída no
pacote expõe a fachada `Kowts\Efatura\Efatura` em `Yii::$app->efatura`, mas a
aplicação continua responsável por persistir facturas, IUD, XML, ZIP, PDF,
respostas fiscais e estados operacionais.

Para um arranque rápido, veja também [Integração com frameworks](frameworks.md).

## Instalação

Instale a biblioteca na aplicação Yii2:

```bash
composer require kowts/efatura-cv
```

Se a aplicação ainda não tiver Yii2 instalado no mesmo projecto Composer,
instale-o explicitamente:

```bash
composer require yiisoft/yii2
```

Para gerar o DFA em PDF com QR Code, instale também as dependências opcionais:

```bash
composer require dompdf/dompdf endroid/qr-code
```

A extensão PHP `gd` deve estar activa para renderizar o QR Code PNG usado no
PDF.

## Variáveis de ambiente

Use variáveis de ambiente ou o gestor de segredos da infraestrutura. Não guarde
certificados, chaves privadas ou tokens no repositório.

```dotenv
EFATURA_TRANSMITTER_NIF=100200300
EFATURA_TRANSMITTER_LED=001
EFATURA_TRANSMITTER_KEY=
EFATURA_SOFTWARE_CODE=MEUSOFT
EFATURA_SOFTWARE_NAME="Meu Software"
EFATURA_SOFTWARE_VERSION=1.0.0
EFATURA_ENVIRONMENT=TEST
EFATURA_MIDDLEWARE_URL=https://middleware.exemplo.cv
EFATURA_PLATFORM_URL=https://services.efatura.cv
EFATURA_EMITTER_NIF=100200300
EFATURA_EMITTER_NAME="Empresa, Lda."
EFATURA_EMITTER_ADDRESS="Palmarejo, Praia"
EFATURA_PKCS12_PATH=/run/secrets/efatura-certificado.p12
EFATURA_PKCS12_PASSWORD=
```

`EFATURA_MIDDLEWARE_URL` e `EFATURA_TRANSMITTER_KEY` só são necessários quando a
submissão é feita através de um middleware. Para submissão directa à plataforma,
use `submitDfeZipToPlatformResult(...)` com o token de acesso.

## Configuração base

Crie um ficheiro comum, por exemplo `config/efatura.php`, para evitar duplicar a
mesma configuração em `web.php` e `console.php`.

```php
<?php

declare(strict_types=1);

use Kowts\Efatura\Bridge\Yii2\EfaturaComponent;

return [
    'class' => EfaturaComponent::class,
    'config' => [
        'transmitter_nif' => getenv('EFATURA_TRANSMITTER_NIF'),
        'transmitter_led' => getenv('EFATURA_TRANSMITTER_LED') ?: '001',
        'transmitter_key' => getenv('EFATURA_TRANSMITTER_KEY') ?: null,
        'software_code' => getenv('EFATURA_SOFTWARE_CODE'),
        'software_name' => getenv('EFATURA_SOFTWARE_NAME'),
        'software_version' => getenv('EFATURA_SOFTWARE_VERSION') ?: '1.0.0',
        'middleware_base_url' => getenv('EFATURA_MIDDLEWARE_URL') ?: null,
        'platform_base_url' => getenv('EFATURA_PLATFORM_URL') ?: 'https://services.efatura.cv',
        'environment' => getenv('EFATURA_ENVIRONMENT') ?: 'TEST',
        'emitter' => [
            'taxId' => [
                'countryCode' => 'CV',
                'value' => getenv('EFATURA_EMITTER_NIF') ?: getenv('EFATURA_TRANSMITTER_NIF'),
            ],
            'name' => getenv('EFATURA_EMITTER_NAME'),
            'address' => [
                'countryCode' => 'CV',
                'addressDetail' => getenv('EFATURA_EMITTER_ADDRESS'),
            ],
        ],
    ],
];
```

Esta configuração é suficiente para desenvolvimento e testes simples. Em
produção, use a configuração com PDO persistente descrita mais abaixo.

## `web.php`

```php
<?php

return [
    'id' => 'minha-app',
    'basePath' => dirname(__DIR__),
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'efatura' => require __DIR__ . '/efatura.php',
    ],
];
```

Uso em controllers:

```php
$efatura = Yii::$app->efatura->client;

// Também funciona por encaminhamento directo:
$xml = Yii::$app->efatura->buildDfeXml($iud, $documento);
```

## `console.php`

Registe o mesmo componente na consola para migrações, comandos de reconciliação
e reprocessamento de submissões pendentes.

```php
<?php

return [
    'id' => 'minha-app-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\commands',
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'efatura' => require __DIR__ . '/efatura.php',
    ],
];
```

## Configuração de produção com PDO persistente

O ambiente `PRODUCTION` exige armazenamento persistente para sequências e
idempotência. O componente padrão cria uma instância simples a partir de arrays;
para produção, forneça uma `factory` personalizada que constrói a instância
`Efatura` com os stores persistentes.

Em `config/efatura.php`:

```php
<?php

declare(strict_types=1);

use Kowts\Efatura\Bridge\Yii2\EfaturaComponent;
use Kowts\Efatura\Config\EfaturaConfig;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;
use Yii;

return [
    'class' => EfaturaComponent::class,
    'config' => [
        'transmitter_nif' => getenv('EFATURA_TRANSMITTER_NIF'),
        'transmitter_led' => getenv('EFATURA_TRANSMITTER_LED') ?: '001',
        'transmitter_key' => getenv('EFATURA_TRANSMITTER_KEY') ?: null,
        'software_code' => getenv('EFATURA_SOFTWARE_CODE'),
        'software_name' => getenv('EFATURA_SOFTWARE_NAME'),
        'software_version' => getenv('EFATURA_SOFTWARE_VERSION') ?: '1.0.0',
        'middleware_base_url' => getenv('EFATURA_MIDDLEWARE_URL') ?: null,
        'platform_base_url' => getenv('EFATURA_PLATFORM_URL') ?: EfaturaConfig::DEFAULT_PLATFORM_URL,
        'environment' => getenv('EFATURA_ENVIRONMENT') ?: 'TEST',
        'emitter' => [
            'taxId' => [
                'countryCode' => 'CV',
                'value' => getenv('EFATURA_EMITTER_NIF') ?: getenv('EFATURA_TRANSMITTER_NIF'),
            ],
            'name' => getenv('EFATURA_EMITTER_NAME'),
            'address' => [
                'countryCode' => 'CV',
                'addressDetail' => getenv('EFATURA_EMITTER_ADDRESS'),
            ],
        ],
    ],
    'factory' => static function (EfaturaComponent $component): Efatura {
        $pdo = Yii::$app->db->pdo;
        $prefix = Yii::$app->db->tablePrefix;

        return new Efatura(
            EfaturaConfig::fromArray($component->config),
            sequenceStore: new PdoSequenceStore($pdo, $prefix . 'efatura_sequences'),
            submissionRegistry: new PdoSubmissionRegistry($pdo, $prefix . 'efatura_submissions'),
        );
    },
];
```

Isto mantém `Yii::$app->efatura` igual para a aplicação, mas troca os
armazenamentos em memória por tabelas partilhadas.

## Migrações

Não execute `PdoSequenceStore::createTable()` ou
`PdoSubmissionRegistry::createTable()` em cada pedido HTTP. Em Yii2, converta as
tabelas para migrações controladas.

Exemplo:

```php
<?php

use yii\db\Migration;

final class m260711_000001_create_efatura_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%efatura_sequences}}', [
            'scope_key' => $this->string(191)->notNull(),
            'current_value' => $this->integer()->notNull(),
            'updated_at' => $this->string(32)->notNull(),
        ]);
        $this->addPrimaryKey(
            'pk_efatura_sequences',
            '{{%efatura_sequences}}',
            'scope_key'
        );

        $this->createTable('{{%efatura_submissions}}', [
            'digest' => $this->char(64)->notNull(),
            'claimed_at' => $this->string(32)->notNull(),
        ]);
        $this->addPrimaryKey(
            'pk_efatura_submissions',
            '{{%efatura_submissions}}',
            'digest'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%efatura_submissions}}');
        $this->dropTable('{{%efatura_sequences}}');
    }
}
```

Se a aplicação usa prefixo de tabelas Yii2, configure os stores com os nomes
reais das tabelas:

```php
$sequenceStore = new PdoSequenceStore(Yii::$app->db->pdo, Yii::$app->db->tablePrefix . 'efatura_sequences');
$submissionRegistry = new PdoSubmissionRegistry(Yii::$app->db->pdo, Yii::$app->db->tablePrefix . 'efatura_submissions');
```

## `PdoSequenceStore`

`PdoSequenceStore` reserva números fiscais de forma atómica por:

- NIF do transmissor;
- ano;
- LED;
- tipo de documento.

Use `buildSequentialIud(...)` para reservar o próximo número e construir o IUD:

```php
use Kowts\Efatura\Domain\DocumentType;

$iud = Yii::$app->efatura->buildSequentialIud(
    $invoice->issue_date,
    DocumentType::ElectronicInvoice
);
```

Depois de reservado, o número não deve ser reutilizado. Se a gravação da factura
ou a submissão falhar, guarde o estado e reconcilie; não faça reset da sequência
para “aproveitar” o número.

## `PdoSubmissionRegistry`

`PdoSubmissionRegistry` impede reenvios acidentais do mesmo ZIP no mesmo canal.
Por omissão, `submitDfeZipResult($zip)` rejeita uma repetição com
`ValidationException`.

Só use `allowResubmission: true` depois de reconciliar o estado fiscal e decidir
explicitamente que o reenvio é seguro:

```php
$result = Yii::$app->efatura->submitDfeZipResult(
    $zip,
    allowResubmission: true
);
```

## Exemplo de `Invoice` ActiveRecord

Este exemplo assume uma tabela `invoice` com os campos mínimos:

- `id`;
- `customer_nif`;
- `customer_name`;
- `issue_date`;
- `issue_time`;
- `status`;
- `efatura_iud`;
- `efatura_xml`;
- `efatura_signed_xml`;
- `efatura_zip`;
- `efatura_pdf`;
- `efatura_response_json`;
- `efatura_reconciliation_status`;
- `efatura_error`.

Um modelo simplificado:

```php
<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string|null $customer_nif
 * @property string $customer_name
 * @property string $issue_date
 * @property string $issue_time
 * @property string $status
 * @property string|null $efatura_iud
 * @property string|null $efatura_xml
 * @property string|null $efatura_signed_xml
 * @property string|null $efatura_zip
 * @property string|null $efatura_pdf
 * @property string|null $efatura_response_json
 * @property string|null $efatura_reconciliation_status
 * @property string|null $efatura_error
 */
final class Invoice extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_UNKNOWN = 'unknown';
    public const STATUS_CONTINGENCY = 'contingency';

    public static function tableName(): string
    {
        return '{{%invoice}}';
    }
}
```

## Criar o documento fiscal

Mapeie o `Invoice` e as linhas comerciais para o array esperado pela biblioteca.
Mantenha este mapeamento num serviço da aplicação, não no controller.

```php
use app\models\Invoice;
use Kowts\Efatura\Domain\DocumentType;

/**
 * Converte a factura interna para a estrutura fiscal e-Fatura.
 *
 * @return array<string, mixed>
 */
function buildFiscalDocumentFromInvoice(Invoice $invoice): array
{
    return [
        'type' => DocumentType::ElectronicInvoice,
        'issueDate' => $invoice->issue_date,
        'issueTime' => $invoice->issue_time,
        'receiver' => [
            'taxId' => [
                'countryCode' => 'CV',
                'value' => $invoice->customer_nif,
            ],
            'name' => $invoice->customer_name,
        ],
        'lines' => array_map(
            static fn ($line): array => [
                'item' => [
                    'description' => $line->description,
                ],
                'quantity' => $line->quantity,
                'price' => $line->unit_price,
                'tax' => [
                    'type' => $line->tax_type,
                    'rate' => $line->tax_rate,
                ],
            ],
            $invoice->lines
        ),
    ];
}
```

Adapte os campos das linhas ao modelo real da aplicação. O objectivo é que a
lógica fiscal fique centralizada e testável.

## Gravar o IUD

Reserve o IUD uma vez e grave-o na factura antes de gerar os artefactos
seguintes.

```php
use Kowts\Efatura\Domain\DocumentType;

if ($invoice->efatura_iud === null) {
    $invoice->efatura_iud = Yii::$app->efatura->buildSequentialIud(
        $invoice->issue_date,
        DocumentType::ElectronicInvoice
    );
    $invoice->status = Invoice::STATUS_READY;
    $invoice->save(false, ['efatura_iud', 'status']);
}
```

Se algo falhar depois deste ponto, não gere outro IUD para a mesma factura.
Corrija o erro, reutilize o IUD já guardado e continue o fluxo.

## Gerar XML, assinar e criar ZIP

```php
$document = buildFiscalDocumentFromInvoice($invoice);

$xml = Yii::$app->efatura->buildDfeXml($invoice->efatura_iud, $document);

$credentials = Yii::$app->efatura->loadPkcs12(
    file_get_contents(getenv('EFATURA_PKCS12_PATH') ?: ''),
    getenv('EFATURA_PKCS12_PASSWORD') ?: ''
);

$signed = Yii::$app->efatura->signXml(
    $xml,
    $credentials['certificate'],
    $credentials['privateKey']
);

$zip = Yii::$app->efatura->buildDfeZip([
    [
        'iud' => $invoice->efatura_iud,
        'xml' => $signed['xml'],
    ],
]);

$invoice->efatura_xml = $xml;
$invoice->efatura_signed_xml = $signed['xml'];
$invoice->efatura_zip = $zip;
$invoice->save(false, ['efatura_xml', 'efatura_signed_xml', 'efatura_zip']);
```

Persistir antes da submissão é obrigatório do ponto de vista operacional: se a
rede falhar, a aplicação precisa do ZIP exacto para reconciliar o estado.

## Submissão

Submissão por middleware:

```php
use Kowts\Efatura\Exception\SubmissionUncertainException;

try {
    $result = Yii::$app->efatura->submitDfeZipResult($invoice->efatura_zip);

    $invoice->efatura_response_json = json_encode($result, JSON_THROW_ON_ERROR);
    $invoice->status = $result->ok
        ? Invoice::STATUS_ACCEPTED
        : Invoice::STATUS_REJECTED;
    $invoice->efatura_error = $result->ok ? null : $result->rawBody;
    $invoice->save(false, ['efatura_response_json', 'status', 'efatura_error']);
} catch (SubmissionUncertainException $exception) {
    $invoice->status = Invoice::STATUS_UNKNOWN;
    $invoice->efatura_error = $exception->getMessage();
    $invoice->save(false, ['status', 'efatura_error']);

    // Enfileire uma reconciliação por consola antes de qualquer reenvio.
}
```

Submissão directa à plataforma:

```php
$result = Yii::$app->efatura->submitDfeZipToPlatformResult(
    $invoice->efatura_zip,
    $accessToken
);
```

## Reconciliação

Quando a submissão fica em estado desconhecido, consulte a autoridade fiscal pelo
IUD antes de reenviar. A biblioteca fornece `SubmissionReconciler`; a aplicação
deve fornecer um cliente compatível com `DocumentStatusClient`, por exemplo
`Psr18FiscalAuthorityClient`.

```php
use Kowts\Efatura\Fiscal\ReconciliationStatus;
use Kowts\Efatura\Fiscal\SubmissionReconciler;
use Kowts\Efatura\Infrastructure\Fiscal\Psr18FiscalAuthorityClient;
use Kowts\Efatura\Infrastructure\Http\RetryingPsr18Client;

$http = new RetryingPsr18Client($psr18Client);
$client = new Psr18FiscalAuthorityClient($http, $requestFactory, getenv('EFATURA_PLATFORM_URL'));

$reconciliation = (new SubmissionReconciler($client))->reconcile(
    $invoice->efatura_iud,
    $accessToken
);

$invoice->efatura_reconciliation_status = $reconciliation->status->value;

if ($reconciliation->status === ReconciliationStatus::Confirmed) {
    $invoice->status = Invoice::STATUS_ACCEPTED;
} elseif ($reconciliation->status === ReconciliationStatus::NotFound) {
    // Só aqui deve ser avaliado um reenvio explícito.
    $invoice->status = Invoice::STATUS_UNKNOWN;
} else {
    $invoice->status = Invoice::STATUS_UNKNOWN;
}

$invoice->save(false, ['efatura_reconciliation_status', 'status']);
```

`RetryingPsr18Client` é adequado para consultas `GET`. A biblioteca não repete
`POST` automaticamente porque um timeout não prova que a plataforma deixou de
processar o documento.

## Contingência

Em falha de conectividade, use emissão em contingência apenas quando a política
fiscal e operacional da organização permitir. O XML deve ser gerado com modo de
emissão não online e a factura deve ficar marcada para submissão posterior.

```php
use Kowts\Efatura\Domain\EmissionMode;

$document = buildFiscalDocumentFromInvoice($invoice);
$document['contingency'] = [
    'reasonCode' => '1',
    'reasonDescription' => 'Indisponibilidade temporária de comunicação.',
];

$xml = Yii::$app->efatura->buildDfeXml(
    $invoice->efatura_iud,
    $document,
    EmissionMode::Offline
);

$invoice->efatura_xml = $xml;
$invoice->status = Invoice::STATUS_CONTINGENCY;
$invoice->save(false, ['efatura_xml', 'status']);
```

Depois de restabelecida a comunicação, assine, empacote e submeta o documento,
preservando o mesmo IUD e registando a reconciliação.

## Geração de PDF DFA

O PDF auxiliar deve ser guardado com a factura ou em armazenamento de objectos.
Para devolver numa resposta HTTP Yii2:

```php
$pdf = Yii::$app->efatura->renderDfa(
    $invoice->efatura_iud,
    buildFiscalDocumentFromInvoice($invoice),
    'CVE'
);

$invoice->efatura_pdf = $pdf->contents;
$invoice->save(false, ['efatura_pdf']);

return Yii::$app->response->sendContentAsFile(
    $pdf->contents,
    $pdf->filename,
    [
        'mimeType' => $pdf->mimeType,
        'inline' => true,
    ]
);
```

## Serviço completo de emissão

Um serviço de aplicação mantém o controller fino e facilita testes.

```php
<?php

namespace app\services;

use app\models\Invoice;
use Kowts\Efatura\Domain\DocumentType;
use Kowts\Efatura\Exception\SubmissionUncertainException;
use Yii;

final class EfaturaInvoiceIssuer
{
    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->efatura_iud === null) {
            $invoice->efatura_iud = Yii::$app->efatura->buildSequentialIud(
                $invoice->issue_date,
                DocumentType::ElectronicInvoice
            );
            $invoice->status = Invoice::STATUS_READY;
            $invoice->save(false, ['efatura_iud', 'status']);
        }

        $document = buildFiscalDocumentFromInvoice($invoice);
        $xml = Yii::$app->efatura->buildDfeXml($invoice->efatura_iud, $document);

        $credentials = Yii::$app->efatura->loadPkcs12(
            file_get_contents(getenv('EFATURA_PKCS12_PATH') ?: ''),
            getenv('EFATURA_PKCS12_PASSWORD') ?: ''
        );

        $signed = Yii::$app->efatura->signXml(
            $xml,
            $credentials['certificate'],
            $credentials['privateKey']
        );

        $zip = Yii::$app->efatura->buildDfeZip([
            ['iud' => $invoice->efatura_iud, 'xml' => $signed['xml']],
        ]);

        $pdf = Yii::$app->efatura->renderDfa($invoice->efatura_iud, $document);

        $invoice->efatura_xml = $xml;
        $invoice->efatura_signed_xml = $signed['xml'];
        $invoice->efatura_zip = $zip;
        $invoice->efatura_pdf = $pdf->contents;
        $invoice->save(false, [
            'efatura_xml',
            'efatura_signed_xml',
            'efatura_zip',
            'efatura_pdf',
        ]);

        try {
            $result = Yii::$app->efatura->submitDfeZipResult($zip);
            $invoice->efatura_response_json = json_encode($result, JSON_THROW_ON_ERROR);
            $invoice->status = $result->ok
                ? Invoice::STATUS_ACCEPTED
                : Invoice::STATUS_REJECTED;
            $invoice->efatura_error = $result->ok ? null : $result->rawBody;
        } catch (SubmissionUncertainException $exception) {
            $invoice->status = Invoice::STATUS_UNKNOWN;
            $invoice->efatura_error = $exception->getMessage();
        }

        $invoice->save(false, [
            'efatura_response_json',
            'status',
            'efatura_error',
        ]);

        return $invoice;
    }
}
```

Em produção, complemente este serviço com:

- fila ou comando de reconciliação para facturas em `unknown`;
- bloqueio por factura para evitar dupla emissão concorrente;
- política de arquivo para XML original, XML assinado, ZIP, PDF e resposta;
- logs sem certificados, palavras-passe, tokens ou documentos fiscais completos;
- alertas para rejeições, certificados próximos do fim de validade e
  reconciliações pendentes.
