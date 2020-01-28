<?php

namespace BinaryCats\LobWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LobWebhooksServiceProvider extends ServiceProvider
{
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lob-webhooks.php' => config_path('lob-webhooks.php'),
            ], 'config');
        }

        Route::macro('lobWebhooks', function ($url) {
            return Route::post($url, '\BinaryCats\LobWebhooks\LobWebhooksController');
        });
    }

    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lob-webhooks.php', 'lob-webhooks');
    }
}
