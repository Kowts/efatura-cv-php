# Assinatura e certificados

O assinador produz uma assinatura enveloped XAdES-BES com:

- canonicalização XML C14N 1.0;
- digest SHA-256;
- assinatura RSA-SHA256;
- referência ao DFE pelo atributo `Id`;
- referência a `SignedProperties`;
- hora de assinatura;
- digest, emissor e número de série do certificado;
- certificado X.509 incorporado.

## Formato

O certificado e a chave devem estar em PEM. Uma chave cifrada exige a palavra-
passe no quarto argumento de `signXml`.

```php
$check = $efatura->validateCertificate($certificate, $privateKey, $password);
if (!$check['valid']) {
    throw new RuntimeException(implode('; ', $check['issues']));
}

$signed = $efatura->signXml($xml, $certificate, $privateKey, $password);
```

Nunca coloque palavras-passe ou chaves privadas no código, `.env` versionado,
logs, respostas HTTP ou aplicações de navegador.

## Validação externa

A validade criptográfica local não confirma que a cadeia de confiança, o
titular, a finalidade do certificado ou o software são aceites pela DNRE.
Essas verificações pertencem ao processo oficial de homologação.
