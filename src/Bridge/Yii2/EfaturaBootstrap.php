<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Yii2;

use InvalidArgumentException;
use Kowts\Efatura\Efatura;
use yii\base\BootstrapInterface;

/**
 * Bootstrap opcional para registar o componente e-Fatura numa aplicação Yii2.
 */
final class EfaturaBootstrap implements BootstrapInterface
{
    public string $componentId = 'efatura';
    public bool $registerContainer = true;

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
        if (!$has($this->componentId)) {
            $component = $this->config;
            $component['class'] ??= EfaturaComponent::class;
            $set = [$app, 'set'];
            $set($this->componentId, $component);
        }

        $this->registerInContainer($app);
    }

    private function registerInContainer(object $app): void
    {
        if (
            !$this->registerContainer
            || !class_exists(\Yii::class)
            || !isset(\Yii::$container)
            || !is_object(\Yii::$container)
            || !method_exists(\Yii::$container, 'setSingleton')
            || !is_callable([$app, 'get'])
        ) {
            return;
        }

        $componentId = $this->componentId;
        \Yii::$container->setSingleton(Efatura::class, static function () use ($app, $componentId): Efatura {
            $component = $app->get($componentId);
            if (!$component instanceof EfaturaComponent) {
                throw new InvalidArgumentException('O componente e-Fatura registado na aplicação Yii2 é inválido.');
            }

            return $component->getClient();
        });
    }
}
