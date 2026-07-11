# Lançamentos

O projecto adopta Versionamento Semântico. A versão não é escrita em
`composer.json`: Composer e Packagist obtêm-na a partir da tag Git.

## Preparar uma versão

1. Confirme que `main` está sincronizada e a CI está verde.
2. Actualize `CHANGELOG.md`, movendo as alterações para a nova versão.
3. Execute `composer validate --strict` e `composer check`.
4. Crie uma tag anotada no formato `vX.Y.Z`.
5. Envie a tag para o GitHub.

Exemplo para a versão actual:

```bash
git tag -a v0.3.0 -m "Release v0.3.0"
git push origin main
git push origin v0.3.0
```

O workflow de release volta a validar o projecto e publica:

- arquivo Composer sem dependências de desenvolvimento;
- SBOM no formato CycloneDX JSON;
- checksums SHA-256;
- atestação de proveniência do arquivo;
- release GitHub com notas geradas.

## Verificar um arquivo

```bash
sha256sum --check SHA256SUMS
gh attestation verify efatura-cv-v0.3.0.zip \
  --repo Kowts/efatura-cv-php
```

Substitua o nome do arquivo pela versão descarregada.

## Packagist

O pacote público usa o nome `kowts/efatura-cv`. Na primeira publicação, envie
o URL `https://github.com/Kowts/efatura-cv-php` no formulário **Submit** do
Packagist. Depois, active a integração GitHub para que novas tags sejam
sincronizadas automaticamente.

Não defina manualmente `version` em `composer.json`; isso pode divergir da tag.
