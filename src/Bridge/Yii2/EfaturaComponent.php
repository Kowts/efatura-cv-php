<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Yii2;

use Kowts\Efatura\Efatura;
use Kowts\Efatura\EfaturaFactory;
use yii\base\Component;

/**
 * Componente Yii2 para expor a fachada e-Fatura através de `Yii::$app->efatura`.
 *
 * A instância principal é criada de forma preguiçosa a partir de `$config`, o que
 * permite reutilizar a configuração normal da biblioteca sem duplicar lógica fiscal
 * nos controladores, comandos ou serviços da aplicação Yii2.
 */
final class EfaturaComponent extends Component
{
    /**
     * Configuração aceite por `EfaturaFactory::fromArray()`.
     *
     * @var array<string, mixed>
     */
    public array $config = [];

    private ?Efatura $client = null;

    public function getClient(): Efatura
    {
        if ($this->client === null) {
            $this->client = EfaturaFactory::fromArray($this->config);
        }

        return $this->client;
    }

    public function getEfatura(): Efatura
    {
        return $this->getClient();
    }

    public function setClient(Efatura $client): void
    {
        $this->client = $client;
    }

    /**
     * Encaminha chamadas desconhecidas para a fachada principal.
     *
     * Isto permite usar `Yii::$app->efatura->buildDfeXml(...)` directamente, sem
     * perder o acesso explícito via `Yii::$app->efatura->client`.
     *
     * @param string $name
     * @param list<mixed> $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        $client = $this->getClient();
        if (method_exists($client, (string) $name)) {
            return $client->{$name}(...$params);
        }

        return parent::__call($name, $params);
    }
}
