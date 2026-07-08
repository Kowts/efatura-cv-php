# Contribuir

Obrigado por ajudar a melhorar o ecossistema PHP de Cabo Verde.

## Preparação

```bash
composer install
composer check
```

Use PHP 8.1 ou superior. Código, comentários, mensagens e documentação devem
usar português europeu (PT-PT). Use termos ingleses apenas quando forem nomes
oficiais da API, do XML ou da tecnologia.

## Regras

- escreva testes para alterações de comportamento;
- valide XML novo com os XSD incluídos;
- não inclua certificados, chaves, NIF reais ou credenciais;
- documente decisões fiscais com a origem e versão da especificação;
- não apresente fixtures internas como vectores oficiais;
- mantenha a API independente de frameworks;
- siga PSR-12 e acrescente PHPDoc quando o tipo não for evidente em PHP.

Abra uma issue antes de alterações incompatíveis ou de novas dependências.
