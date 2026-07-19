# Persistência PDO

Este guia descreve como usar persistência partilhada para sequências fiscais e
idempotência de submissão. Em produção, esta camada é obrigatória: o
armazenamento em memória só protege a instância PHP actual e não é seguro quando
existem múltiplos pedidos, workers, servidores ou processos concorrentes.

## Suporte por motor de base de dados

| Motor | Estado | Observações |
|---|---:|---|
| SQLite | Suportado e testado | Usa `ON CONFLICT ... RETURNING`. Adequado para testes, ferramentas locais e instalações pequenas. |
| MySQL | Suportado e testado | Usa `ON DUPLICATE KEY UPDATE` com `LAST_INSERT_ID(...)`. |
| MariaDB | Suportado pela via MySQL | O driver PDO é normalmente `mysql`; deve ser validado na versão usada pela aplicação. |
| PostgreSQL | Suportado e testado | Usa `ON CONFLICT ... DO UPDATE ... RETURNING`. |
| SQL Server | Não suportado ainda | O driver `sqlsrv` precisa de implementação e teste próprios. Não use o fallback genérico para produção. |

O código reconhece explicitamente os drivers PDO `sqlite`, `mysql` e `pgsql`.
Outros drivers caem num fallback transaccional genérico, mas esse fallback não
deve ser tratado como suporte oficial sem teste de concorrência no motor real.

## Responsabilidades da aplicação

A biblioteca reserva números e impede reenvios acidentais, mas a aplicação
consumidora continua responsável por:

- criar as tabelas por migrações controladas;
- injectar a mesma ligação PDO usada pelos processos de emissão;
- persistir factura, IUD, XML, XML assinado, ZIP, PDF, resposta e estado;
- reconciliar submissões em estado desconhecido antes de reenviar;
- fazer backup e recuperação da base de dados.

## Tabelas necessárias

`PdoSequenceStore` usa uma tabela de sequências:

```sql
CREATE TABLE efatura_sequences (
    scope_key VARCHAR(191) PRIMARY KEY,
    current_value INTEGER NOT NULL,
    updated_at VARCHAR(32) NOT NULL
);
```

`PdoSubmissionRegistry` usa uma tabela de idempotência:

```sql
CREATE TABLE efatura_submissions (
    digest CHAR(64) PRIMARY KEY,
    claimed_at VARCHAR(32) NOT NULL
);
```

Os métodos `createTable()` existem para instalação inicial e testes, mas em
aplicações reais deve converter estes esquemas em migrações do framework.
Não execute `createTable()` em cada pedido HTTP.

## Exemplo PHP puro

```php
use Kowts\Efatura\Efatura;
use Kowts\Efatura\Infrastructure\Sequence\PdoSequenceStore;
use Kowts\Efatura\Infrastructure\Submission\PdoSubmissionRegistry;

$pdo = new PDO(
    getenv('DATABASE_DSN') ?: '',
    getenv('DATABASE_USER') ?: null,
    getenv('DATABASE_PASSWORD') ?: null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);

$sequenceStore = new PdoSequenceStore($pdo, 'efatura_sequences');
$submissionRegistry = new PdoSubmissionRegistry($pdo, 'efatura_submissions');

$efatura = new Efatura(
    $config,
    sequenceStore: $sequenceStore,
    submissionRegistry: $submissionRegistry
);
```

## Sequências fiscais

A chave de sequência é composta por:

- NIF do transmissor;
- ano;
- LED;
- tipo de documento.

Isto significa que facturas, notas de crédito, documentos de transporte e outros
tipos documentais têm sequências independentes. Depois de reservado, um número
não deve ser reutilizado. Se houver erro posterior, mantenha o IUD gravado e
reconcilie o estado.

## Idempotência de submissão

`PdoSubmissionRegistry` grava o digest SHA-256 do ZIP submetido. Isto evita que o
mesmo pacote seja reenviado por acidente.

Só use `allowResubmission: true` depois de confirmar o estado fiscal e decidir
explicitamente que o reenvio é correcto.

## Transacções da aplicação

O número fiscal deve ser reservado no mesmo fluxo em que a factura passa para o
estado fiscal correspondente. Um padrão seguro é:

1. abrir transacção da aplicação;
2. bloquear ou validar a factura interna;
3. reservar IUD com `buildSequentialIud(...)`;
4. gravar IUD e estado interno;
5. confirmar transacção;
6. gerar XML, assinar, criar ZIP e persistir artefactos;
7. submeter;
8. gravar resposta ou estado desconhecido.

Evite reservar um novo IUD para corrigir falhas de rede ou validação. O número
já reservado faz parte do histórico fiscal da factura.

## SQL Server

SQL Server ainda não está implementado. O suporte correcto deve ser feito com um
ramo próprio para o driver `sqlsrv`, por exemplo usando locks explícitos como
`UPDLOCK`/`HOLDLOCK` ou uma estratégia equivalente validada em concorrência.

Antes de declarar suporte, o projecto deve incluir:

- implementação específica em `PdoSequenceStore`;
- implementação específica em `PdoSubmissionRegistry`, se necessário;
- job CI com SQL Server real;
- testes de concorrência e idempotência.
