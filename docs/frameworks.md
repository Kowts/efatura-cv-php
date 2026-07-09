# Integração com frameworks

As integrações são opcionais. O núcleo continua independente de Laravel e
Symfony.

## Laravel

Instale o pacote e publique a configuração:

```bash
composer require kowts/efatura-cv
php artisan vendor:publish --tag=efatura-config
```

Defina, no mínimo, as variáveis seguintes:

```dotenv
EFATURA_TRANSMITTER_NIF=100200300
EFATURA_TRANSMITTER_LED=001
EFATURA_TRANSMITTER_KEY=
EFATURA_SOFTWARE_CODE=MEUSOFT
EFATURA_SOFTWARE_NAME="Meu Software"
EFATURA_SOFTWARE_VERSION=1.0.0
# Necessário apenas quando a aplicação envia através de um middleware.
EFATURA_MIDDLEWARE_URL=https://middleware.exemplo.cv
EFATURA_MIDDLEWARE_DFE_PATH=/v1/dfe
EFATURA_PLATFORM_DFE_PATH=/v1/dfe
EFATURA_ENVIRONMENT=TEST
EFATURA_EMITTER_NIF=100200300
EFATURA_EMITTER_NAME="Empresa, Lda."
EFATURA_EMITTER_ADDRESS=Praia
```

`EFATURA_MIDDLEWARE_URL` e `EFATURA_TRANSMITTER_KEY` são opcionais para
aplicações que submetem directamente à plataforma. O pacote usa a descoberta
automática do Service Provider. Injecte
`Kowts\Efatura\Efatura` no construtor do controller ou serviço.

Para usar PDO, PSR-18 ou armazenamento próprio, substitua o binding no
contentor da aplicação por uma fábrica que construa `Efatura` com as
implementações pretendidas.

## Symfony

Registe o bundle:

```php
// config/bundles.php
return [
    Kowts\Efatura\Bridge\Symfony\EfaturaBundle::class => ['all' => true],
];
```

Configure o pacote:

```yaml
# config/packages/efatura_cv.yaml
efatura_cv:
  transmitter_nif: '%env(EFATURA_TRANSMITTER_NIF)%'
  transmitter_led: '001'
  transmitter_key: '%env(EFATURA_TRANSMITTER_KEY)%'
  software_code: '%env(EFATURA_SOFTWARE_CODE)%'
  software_name: '%env(EFATURA_SOFTWARE_NAME)%'
  software_version: '1.0.0'
  # Omitir quando a aplicação usa apenas a plataforma directa.
  middleware_base_url: '%env(EFATURA_MIDDLEWARE_URL)%'
  environment: 'TEST'
  emitter:
    taxId:
      countryCode: 'CV'
      value: '%env(EFATURA_TRANSMITTER_NIF)%'
    name: '%env(EFATURA_EMITTER_NAME)%'
    address:
      countryCode: 'CV'
      addressDetail: '%env(EFATURA_EMITTER_ADDRESS)%'
```

O serviço fica disponível pelo tipo `Kowts\Efatura\Efatura` e pelo alias
`efatura`.
