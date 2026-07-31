<?php

use Illuminate\Support\Facades\Route;
use StreetMesh\Protocol\Laravel\Capabilities\Capabilities;
use StreetMesh\Protocol\Laravel\Http\IdentityController;

/*
 * Everything a stranger may ask without introducing themselves.
 *
 * Deliberately outside the web middleware group: the caller is another server
 * rather than a browser, so there is no session to start and no CSRF token to
 * check, and putting these behind browser protections only breaks them.
 */
Route::middleware('streetmesh')->group(function (): void {
    Route::get('.well-known/did.json', [IdentityController::class, 'document'])
        ->name('streetmesh.did');

    Route::get('.well-known/atproto-did', [IdentityController::class, 'handle'])
        ->name('streetmesh.handle');
});

/*
 * The front door, if the application wants one.
 *
 * Registered only when asked for, and only when nothing else has taken the
 * root — a silent replacement is exactly what this arrangement exists to avoid,
 * and doing it here would be committing the offence while preventing it.
 */
if (($home = config('streetmesh.home')) !== null && ! Route::has('streetmesh.root')) {
    Route::middleware('web')->get('/', function () use ($home) {
        $route = app(Capabilities::class)->homeRoute($home);

        abort_unless($route !== null, 404);

        return redirect()->route($route);
    })->name('streetmesh.root');
}
