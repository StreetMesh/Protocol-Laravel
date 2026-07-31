<?php

namespace StreetMesh\Protocol\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StreetMesh\Protocol\Laravel\ProtocolServiceProvider;
use StreetMesh\Protocol\Laravel\Records\Record;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ProtocolServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Keys are encrypted at rest, so a server without an application key
        // cannot hold an identity at all. Worth failing loudly in tests rather
        // than discovering it on a first deploy.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('streetmesh.host', 'games.test');
        $app['config']->set('streetmesh.collections', [
            'com.streetmesh.games.chess' => Record::PUBLIC,
            'com.streetmesh.messages.direct' => Record::PRIVATE,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
