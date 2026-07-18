# Auditoria técnica do projecto

Este documento resume a auditoria técnica do repositório e serve como lista de
controlo para próximas versões. As prioridades foram classificadas pelo risco
para produção e pelo impacto na manutenção da biblioteca.

## Objectivo actual

O pacote fornece uma biblioteca Composer independente de frameworks para gerar,
validar, assinar, empacotar e submeter documentos fiscais electrónicos DFE v11
para Cabo Verde. O núcleo é framework-agnostic e as integrações Laravel, Symfony
e Yii2 são opcionais.

## Problemas e melhorias identificadas

### Crítica

Não foram encontrados problemas críticos nesta revisão. Em particular, não há
segredos versionados, `composer audit` não reportou vulnerabilidades conhecidas
e o CI cobre Linux, Windows, PHP 8.1 a 8.4, Yii2 real e bases de dados PDO.

### Alta

#### Persistência obrigatória em produção

- Impacto: o armazenamento em memória pode gerar números duplicados quando há
  múltiplos processos, workers ou servidores.
- Solução: usar `PdoSequenceStore` e `PdoSubmissionRegistry` em produção, via
  factory no framework ou construção manual da fachada.
- Estado: documentado no README e nos guias de produção/Yii2. Deve continuar a
  ser reforçado em exemplos novos.

#### Processo de homologação fora do código

- Impacto: os testes internos não substituem validação oficial, certificação do
  software ou testes em ambiente e-Fatura real.
- Solução: manter `NOTICE`, `SECURITY.md` e documentação de conformidade claros;
  registar evidências de submissão em ambiente oficial fora do repositório.
- Estado: documentado; sem alteração funcional necessária.

### Média

#### Distribuição e consistência de checkout

- Impacto: diferenças de line endings entre Windows e Linux podem causar ruído
  em diffs, falhas de estilo ou arquivos Composer inconsistentes.
- Solução: definir `.editorconfig` e `.gitattributes` com LF e regras de
  `export-ignore`.
- Estado: implementado nesta auditoria.

#### Configuração Laravel no directório raiz

- Impacto: `config/efatura.php` é adequado para Laravel, mas pode confundir
  utilizadores de PHP puro porque depende do helper `env()`.
- Solução: documentar no próprio ficheiro que é uma configuração publicável
  Laravel; nos outros contextos usar `EfaturaConfig` ou `EfaturaConfig::fromArray()`.
- Estado: implementado nesta auditoria.

#### TLS no transporte cURL

- Impacto: o cURL valida TLS por omissão, mas depender de valores implícitos é
  menos claro em auditorias de segurança.
- Solução: configurar explicitamente `CURLOPT_SSL_VERIFYPEER` e
  `CURLOPT_SSL_VERIFYHOST`.
- Estado: implementado nesta auditoria.

#### Dependências opcionais com versões major novas

- Impacto: `endroid/qr-code`, `illuminate/support` e `phpunit/phpunit` têm
  versões major mais recentes; actualizar sem planeamento pode quebrar suporte
  a PHP 8.1 ou consumidores existentes.
- Solução: avaliar numa versão menor futura com matriz específica e notas de
  compatibilidade.
- Estado: pendente por segurança de compatibilidade.

### Baixa

#### Docker para desenvolvimento local

- Impacto: novos contribuidores precisam instalar PHP/extensões localmente.
- Solução: adicionar `Dockerfile`/`compose.yml` apenas se houver necessidade de
  uniformizar contribuições locais; o CI já valida ambientes principais.
- Estado: recomendação futura, não essencial para a biblioteca.

#### Cobertura mínima ainda conservadora

- Impacto: o limiar actual protege contra regressão, mas não força crescimento
  forte da cobertura.
- Solução: aumentar gradualmente o mínimo depois de cobrir cenários fiscais
  prioritários, sobretudo submissão, certificados e edge cases de documentos.
- Estado: recomendação futura.

## Próximos passos recomendados

1. Criar testes específicos para combinações reais de certificados e cadeias de
   confiança quando existirem artefactos oficiais ou fixtures internas seguras.
2. Avaliar uma matriz experimental para próximas versões major de dependências
   opcionais sem abandonar PHP 8.1 antes de decisão explícita.
3. Considerar ambiente Docker de desenvolvimento apenas se novos contribuidores
   reportarem dificuldade em reproduzir o CI localmente.
4. Manter `docs/api-reference.md` gerado por `composer docs:api` sempre que a API
   pública mudar.
