<?php

namespace StreetMesh\Protocol\Laravel;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use StreetMesh\Protocol\Handle;
use StreetMesh\Protocol\Laravel\Attestations\Attestations;
use StreetMesh\Protocol\Laravel\Http\LaravelNetwork;
use StreetMesh\Protocol\Laravel\Identity\DidResolver;
use StreetMesh\Protocol\Laravel\Records\Collections;
use StreetMesh\Protocol\Laravel\Records\RecordStore;
use StreetMesh\Protocol\Network;
use StreetMesh\Protocol\PlcDirectory;

class ProtocolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/streetmesh.php', 'streetmesh');

        $this->app->singleton(Network::class, fn (): Network => new LaravelNetwork(
            timeoutSeconds: (int) config('streetmesh.network.timeout', 10),
            cacheSeconds: (int) config('streetmesh.network.cache_seconds', 300),
        ));

        $this->app->singleton(Collections::class, fn (): Collections => new Collections(
            (array) config('streetmesh.collections', []),
        ));

        $this->app->singleton(RecordStore::class);

        $this->app->singleton(PlcDirectory::class, fn ($app): PlcDirectory => new PlcDirectory(
            $app->make(Network::class),
        ));

        $this->app->singleton(Handle::class, fn ($app): Handle => new Handle(
            $app->make(Network::class),
        ));

        $this->app->singleton(DidResolver::class);
        $this->app->singleton(Attestations::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/streetmesh.php' => config_path('streetmesh.php'),
        ], 'streetmesh-config');

        $this->protectSignedDocuments();
    }

    /**
     * Stop the framework tidying anything that carries a signature.
     *
     * Laravel blanks and trims request input as a kindness to HTML forms.
     * Applied to a signed document it is corruption: a signature covers bytes,
     * so turning an empty string into null changes what is being verified and
     * the check fails for a document that was never wrong. The failure looks
     * exactly like forgery, and it is data-dependent, so it appears
     * intermittent.
     *
     * This is the guarantee that most justifies the package existing. Every
     * implementor would otherwise have to know about it, and would find out the
     * same way — by losing two days.
     */
    private function protectSignedDocuments(): void
    {
        $carriesSignature = static fn (Request $request): bool => $request->is(
            ...(array) config('streetmesh.signed_paths', [
                'xrpc/*',
                'records', '*/records',
                'did.json', '*/did.json',
                '.well-known/*', '*/.well-known/*',
            ]),
        );

        ConvertEmptyStringsToNull::skipWhen($carriesSignature);
        TrimStrings::skipWhen($carriesSignature);
    }
}
