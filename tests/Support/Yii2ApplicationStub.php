<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests\Support;

final class Yii2ApplicationStub
{
    /**
     * @var array<string, mixed>
     */
    public array $components = [];

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->components);
    }

    /**
     * @param mixed $definition
     */
    public function set(string $id, mixed $definition): void
    {
        $this->components[$id] = $definition;
    }
}
