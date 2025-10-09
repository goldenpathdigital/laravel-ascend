<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Providers;

use GoldenPathDigital\LaravelAscend\Console\RegisterCommand;
use GoldenPathDigital\LaravelAscend\Console\ServeCommand;
use GoldenPathDigital\LaravelAscend\Server\AscendServer;
use Illuminate\Support\ServiceProvider;

class AscendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ascend.php',
            'ascend',
        );

        // Register AscendServer as singleton for facade support
        $this->app->singleton(AscendServer::class, function ($app) {
            $knowledgeBasePath = config('ascend.knowledge_base.path');
            return AscendServer::createDefault($knowledgeBasePath);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ServeCommand::class,
                RegisterCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/ascend.php' => config_path('ascend.php'),
            ], 'ascend-config');
        }
    }
}
