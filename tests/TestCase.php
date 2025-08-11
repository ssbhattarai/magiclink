<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Ssbhattarai\MagicLink\MagicLinkServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            MagicLinkServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load routes
        $app['router']->group([
            'namespace' => 'Ssbhattarai\MagicLink\Http\Controllers',
        ], function ($router) {
            require __DIR__.'/../routes/web.php';
        });
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }
}
