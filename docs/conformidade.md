# Matriz de conformidade

| Regra | Estado | Implementação |
|---|---:|---|
| XML UTF-8 compacto | Implementado | `DfeXmlBuilder` |
| Nove tipos de DFE | Implementado | `DocumentType` e testes XSD |
| IUD de 45 caracteres e Luhn | Implementado | `Iud` |
| NIF CV com nove algarismos | Implementado | `EfaturaConfig`/validador |
| Entidades, moradas e contactos | Implementado | validador/gerador XML |
| Imposto `NA` com motivo | Implementado | `DocumentValidator` |
| Reconciliação de linhas e totais | Implementado | `DocumentValidator` |
| Percurso com dois ou mais locais | Implementado | `DocumentValidator` |
| Modos Online, Offline e Off | Implementado | `EmissionMode` |
| XSD oficial de 27-05-2024 | Implementado | `XsdValidator` |
| XAdES-BES RSA-SHA256 | Implementado | `XadesBesSigner` |
| ZIP Deflate `{IUD}.xml` | Implementado | `DfeZip` |
| Eventos FDC e UDN | Implementado | `EventXmlBuilder` |
| Pedido de autorização de autofacturação | Implementado | `SelfBillingAuthorizationClient` e `Psr18FiscalAuthorityClient` |
| Sequência transaccional | Implementado | `PdoSequenceStore`; SQLite, MySQL/MariaDB e PostgreSQL |
| Endpoint de middleware | Configurável | `/v1/dfe` por omissão; confirmar no ambiente homologado |
| Endpoint de plataforma | Configurável | `/v1/dfe` ou `/v1/dfes`, conforme o serviço contratado |
| Consulta externa de NIF/software | Implementação configurável | contratos e cliente PSR-18; as rotas dependem do serviço |
| DFA em PDF | Implementado | `PdfDfaRenderer`, QR Code, linhas, totais e contingência |
| Homologação oficial | Externa | exige credenciais e ambiente oficial |

## Limitações verificáveis

Os artefactos oficiais disponíveis não incluem vectores dourados para comparar
IUD, ZIP e assinatura. Os testes do pacote verificam regras, XSD, conteúdo ZIP
e validade criptográfica, mas não alegam equivalência oficial byte a byte.

Os endpoints e cabeçalhos de transporte devem ser confirmados com o fornecedor
do middleware e com a documentação vigente antes de produção.
