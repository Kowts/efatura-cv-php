<?php

declare(strict_types=1);

namespace Kowts\Efatura\Bridge\Laravel;

use Illuminate\Support\ServiceProvider;
use Kowts\Efatura\Efatura;
use Kowts\Efatura\EfaturaFactory;

/**
 * Regista a fachada como singleton numa aplicação Laravel.
 */
final class EfaturaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 3) . '/config/efatura.php', 'efatura');
        $this->app->singleton(Efatura::class, static function ($app): Efatura {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('efatura', []);
            return EfaturaFactory::fromArray($config);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 3) . '/config/efatura.php'
                => (string) $this->app->make('path.config') . DIRECTORY_SEPARATOR . 'efatura.php',
        ], 'efatura-config');
    }
}
