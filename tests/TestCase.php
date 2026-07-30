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
        $app['config']->set('database.default', 'testing');
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
