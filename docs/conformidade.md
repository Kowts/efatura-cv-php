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
| Sequência transaccional | Implementado | `PdoSequenceStore` |
| Middleware `/v1/dfe` | Implementado | `CurlMiddlewareTransport` |
| Plataforma `/v1/dfe` | Implementado | `CurlPlatformTransport` |
| Consulta externa de NIF/software | Dependente do serviço | não existe contrato público estável documentado |
| DFA em PDF | Fora do núcleo | o pacote fornece o URL/QR; a apresentação pertence à aplicação |
| Homologação oficial | Externa | exige credenciais e ambiente oficial |

## Limitações verificáveis

Os artefactos oficiais disponíveis não incluem vectores dourados para comparar
IUD, ZIP e assinatura. Os testes do pacote verificam regras, XSD, conteúdo ZIP
e validade criptográfica, mas não alegam equivalência oficial byte a byte.

Os endpoints e cabeçalhos de transporte devem ser confirmados com o fornecedor
do middleware e com a documentação vigente antes de produção.
