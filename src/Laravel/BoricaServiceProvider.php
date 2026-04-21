<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\ServiceProvider;

class BoricaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/borica.php', 'borica');

        $this->app->singleton(BoricaManager::class, function ($app) {
            return new BoricaManager($app['config']);
        });

        if (class_exists(\GuzzleHttp\Client::class)) {
            $this->app->bindIf(\Psr\Http\Client\ClientInterface::class, fn () => new \GuzzleHttp\Client());
        }
        if (class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            $this->app->bindIf(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new \GuzzleHttp\Psr7\HttpFactory());
            $this->app->bindIf(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new \GuzzleHttp\Psr7\HttpFactory());
        }
    }

    public function boot(): void
    {
        $this->excludeCallbackFromCsrf();
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/borica.php' => config_path('borica.php'),
            ], 'borica-config');

            $this->publishes([
                __DIR__ . '/routes/borica.php' => base_path('routes/borica.php'),
            ], 'borica-routes');

            $this->commands([
                Console\GenerateCertificateCommand::class,
                Console\StatusCheckCommand::class,
                Console\ImportCertificateCommand::class,
                Console\CheckCertificatesCommand::class,
            ]);
        }

        $this->loadRoutes();
    }

    private function excludeCallbackFromCsrf(): void
    {
        $prefix = $this->app['config']->get('borica.routes.prefix', 'borica');

        if (class_exists(VerifyCsrfToken::class)) {
            VerifyCsrfToken::except(["{$prefix}/callback"]);
        }
    }

    private function loadRoutes(): void
    {
        if (!$this->app['config']->get('borica.routes.enabled', true)) {
            return;
        }

        $prefix = $this->app['config']->get('borica.routes.prefix', 'borica');
        $middleware = $this->app['config']->get('borica.routes.middleware', ['web']);

        $this->app['router']
            ->prefix($prefix)
            ->middleware(array_merge($middleware, [\Ux2Dev\Borica\Laravel\Http\Middleware\VerifyBoricaSignature::class]))
            ->group(__DIR__ . '/routes/borica.php');
    }
}
