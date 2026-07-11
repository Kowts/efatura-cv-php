<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Yii2;

use InvalidArgumentException;
use yii\base\BootstrapInterface;

/**
 * Bootstrap opcional para registar o componente e-Fatura numa aplicação Yii2.
 */
final class EfaturaBootstrap implements BootstrapInterface
{
    public string $componentId = 'efatura';

    /**
     * Configuração aceite por `EfaturaComponent`.
     *
     * @var array<string, mixed>
     */
    public array $config = [];

    /**
     * @param mixed $app
     */
    public function bootstrap($app): void
    {
        if ($this->componentId === '') {
            throw new InvalidArgumentException('O identificador do componente e-Fatura não pode estar vazio.');
        }
        if (!is_object($app) || !is_callable([$app, 'has']) || !is_callable([$app, 'set'])) {
            throw new InvalidArgumentException('O bootstrap e-Fatura exige uma aplicação Yii2 compatível.');
        }
        $has = [$app, 'has'];
        if ($has($this->componentId)) {
            return;
        }

        $component = $this->config;
        $component['class'] ??= EfaturaComponent::class;
        $set = [$app, 'set'];
        $set($this->componentId, $component);
    }
}
