<?php

use StreetMesh\Protocol\Laravel\Records\Record;

return [

    /*
     |--------------------------------------------------------------------------
     | Collections
     |--------------------------------------------------------------------------
     |
     | The kinds of record this server will hold, and who may see each. A
     | collection that is not listed here cannot be written to at all, which
     | makes adding a kind of record a decision somebody makes once rather than
     | a consequence of a mistyped name.
     |
     | Visibility belongs to the collection rather than to the record, so that
     | no code path anywhere takes it as an argument. Publishing cannot be
     | undone — a record replicated out of a public collection cannot be
     | recalled — so the failure worth designing against is a private thing
     | becoming public, and the way to prevent it is to leave nothing to flip.
     |
     */

    'collections' => [
        'com.streetmesh.games.chess' => Record::PUBLIC,
    ],

    /*
     |--------------------------------------------------------------------------
     | Network
     |--------------------------------------------------------------------------
     |
     | Identity documents are fetched constantly and change rarely, so they are
     | cached. Keep the interval short: a DID document is how a key rotation
     | becomes visible, and a stale one means trusting a key that was retired.
     |
     */

    /*
     |--------------------------------------------------------------------------
     | This server
     |--------------------------------------------------------------------------
     |
     | The host decides this server's own identifier under did:web, so it has to
     | be the name strangers actually reach it by rather than a local alias.
     |
     | P-256 by default because it is the only curve that works both for did:web
     | now and did:plc later — an identity minted on Ed25519 could never move to
     | the method that makes it portable.
     |
     */

    'host' => env('STREETMESH_HOST', 'localhost'),
    'venue' => env('STREETMESH_VENUE', false),
    'curve' => env('STREETMESH_CURVE', 'p256'),

    /*
     |--------------------------------------------------------------------------
     | Where capabilities live
     |--------------------------------------------------------------------------
     |
     | A server offering one capability can give it the front page. A server
     | offering two cannot give it to both, and neither package can settle which
     | — so the application says, here.
     |
     | Leave a prefix empty to mount at the root. Exactly one may be empty; two
     | capabilities at the same path is a collision the router resolves by
     | whichever loaded first, which is not a decision anybody made.
     |
     */

    'mount' => [
        'domicile' => env('STREETMESH_MOUNT_DOMICILE', ''),
        'venue' => env('STREETMESH_MOUNT_VENUE', ''),
    ],

    'network' => [
        'timeout' => 10,
        'cache_seconds' => 300,
    ],

];
