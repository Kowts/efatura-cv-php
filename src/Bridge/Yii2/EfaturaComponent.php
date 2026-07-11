<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Yii2;

use InvalidArgumentException;
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

    /**
     * Factory opcional para construir a fachada principal.
     *
     * Use quando a aplicação precisa injectar dependências persistentes, como
     * `PdoSequenceStore`, `PdoSubmissionRegistry` ou transportes HTTP próprios.
     *
     * @var null|callable(self):Efatura
     */
    public mixed $factory = null;

    private ?Efatura $client = null;

    public function getClient(): Efatura
    {
        if ($this->client === null) {
            $this->client = $this->createClient();
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

    private function createClient(): Efatura
    {
        if ($this->factory === null) {
            return EfaturaFactory::fromArray($this->config);
        }

        if (!is_callable($this->factory)) {
            throw new InvalidArgumentException('A factory Yii2 e-Fatura deve ser callable.');
        }

        $client = ($this->factory)($this);
        if (!$client instanceof Efatura) {
            throw new InvalidArgumentException('A factory Yii2 e-Fatura deve devolver uma instância de Efatura.');
        }

        return $client;
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
