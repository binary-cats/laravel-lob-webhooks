<?php

namespace BinaryCats\LobWebhooks\Tests;

use BinaryCats\LobWebhooks\LobWebhooksServiceProvider;
use CreateWebhookCallsTable;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config(['lob-webhooks.signing_secret' => 'secret']);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub';

        (new CreateWebhookCallsTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LobWebhooksServiceProvider::class,
        ];
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }

    /**
     * Compile lob.com siangure.
     *
     * @param  array       $payload
     * @param  int         $timestamp
     * @param  string|null $configKey
     * @return string
     */
    protected function determineLobSignature(array $payload, $timestamp, string $configKey = null): string
    {
        $secret = ($configKey) ?
            config("lob-webhooks.signing_secret_{$configKey}") :
            config('lob-webhooks.signing_secret');

        $token = implode('.', [
            $timestamp,
            json_encode($payload),
        ]);

        return hash_hmac('sha256', $token, $secret);
    }
}
