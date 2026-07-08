<?php

declare(strict_types=1);

namespace Kowts\Efatura\Tests;

use Kowts\Efatura\Bridge\Symfony\EfaturaExtension;
use Kowts\Efatura\Efatura;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SymfonyBridgeTest extends TestCase
{
    public function testRegistaEfaturaNoContentorSymfony(): void
    {
        $container = new ContainerBuilder();
        (new EfaturaExtension())->load([[
            'transmitter_nif' => '100200300',
            'transmitter_led' => '123',
            'software_code' => 'EFATURAPHP',
            'software_name' => 'e-Fatura PHP',
            'software_version' => '0.1.0',
            'middleware_base_url' => 'https://middleware.example.test',
        ]], $container);
        $container->compile();

        self::assertTrue($container->has(Efatura::class));
        self::assertInstanceOf(Efatura::class, $container->get(Efatura::class));
    }
}
