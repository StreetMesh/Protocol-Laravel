<?php

use Illuminate\Support\Facades\Route;
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
