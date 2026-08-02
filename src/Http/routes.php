<?php

use Illuminate\Support\Facades\Route;
use StreetMesh\Protocol\Laravel\Http\ClientController;
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

    /*
     * How this server introduces itself when it is the one asking.
     *
     * Not under .well-known, because its URL is not a convention to be looked
     * up — it *is* this venue's identifier, and it travels inside every request
     * for permission. A domicile fetches it because it was handed the address,
     * not because it knows where to look.
     */
    Route::get('client-metadata.json', [ClientController::class, 'metadata'])
        ->name('streetmesh.client');

    Route::get('jwks.json', [ClientController::class, 'keys'])
        ->name('streetmesh.jwks');
});

/*
 * There is deliberately no route here for the front page.
 *
 * The rule this package establishes — that nothing claims the root, because
 * Laravel replaces a route sharing a path rather than complaining — applies to
 * this package too, and a redirect registered here would either overwrite the
 * application's own root or be overwritten by it, silently, depending on boot
 * order.
 *
 * An application that wants its front door to lead somewhere says so itself:
 *
 *     Route::redirect('/', '/'.config('streetmesh.mount.domicile'));
 *
 * or, to follow whatever is installed rather than naming one:
 *
 *     Route::get('/', fn () => redirect()->route(
 *         app(Capabilities::class)->homeRoute(config('streetmesh.home'))
 *     ));
 */
