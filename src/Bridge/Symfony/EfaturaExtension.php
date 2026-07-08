<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Symfony;

use Kowts\Efatura\Efatura;
use Kowts\Efatura\EfaturaFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Carrega a configuração `efatura_cv` no contentor Symfony.
 */
final class EfaturaExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = array_replace_recursive(...array_merge([[]], $configs));
        $definition = new Definition(Efatura::class);
        $definition->setFactory([EfaturaFactory::class, 'fromArray']);
        $definition->setArguments([$config]);
        $definition->setPublic(true);
        $container->setDefinition(Efatura::class, $definition);
        $container->setAlias('efatura', Efatura::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'efatura_cv';
    }
}
