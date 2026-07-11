<?php

declare(strict_types=1);

namespace yii\base;

/**
 * Stub mínimo de Yii2 para testar bridges opcionais sem instalar o framework.
 */
class Component
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            $this->{$name} = $value;
        }
        $this->init();
    }

    public function init(): void
    {
    }

    /**
     * @param list<mixed> $params
     */
    public function __call($name, $params)
    {
        throw new \BadMethodCallException('Calling unknown method: ' . static::class . "::{$name}()");
    }
}
