<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Who this server is
     |--------------------------------------------------------------------------
     |
     | The name strangers reach this server by. Under `did:web` it decides the
     | server's own identifier, so it has to be the real one rather than a local
     | alias — and it is read when an identity is first made, so changing it
     | afterwards renames nothing that already exists.
     |
     | `origin` is the same server as a full address, and is what this server
     | publishes in its DID document as the place to reach it. Derived from the
     | host when it is not set, which is right for every server that serves
     | itself on https and its own name.
     |
     */

    'host' => env('STREETMESH_HOST'),

    'origin' => env('STREETMESH_ORIGIN'),

    /*
     | P-256 unless something is very unusual. `did:plc` permits only secp256k1
     | and P-256, and P-256 is the one PHP handles without an extension.
     */

    'curve' => env('STREETMESH_CURVE', 'p256'),

    /*
     |--------------------------------------------------------------------------
     | The front page
     |--------------------------------------------------------------------------
     |
     | What a stranger sees at the root, before signing in. A server has one
     | root, so if it offers more than one capability it has to say which one
     | greets people. Null follows whatever is installed, which is enough for a
     | server offering only one thing.
     |
     */

    'front_page' => env('STREETMESH_FRONT_PAGE'),

    /*
     |--------------------------------------------------------------------------
     | The home page
     |--------------------------------------------------------------------------
     |
     | What somebody signed in sees. The one surface where two installed
     | capabilities genuinely overlap, so it is a collection of panels rather
     | than a page either of them owns.
     |
     | Null shows everything on offer, in the order capabilities were
     | registered. Naming them instead is how an operator decides what their
     | server is for — and a name nothing provides is skipped rather than fatal,
     | so removing a package does not break a page.
     |
     */

    'home_page' => null,

    /*
     |--------------------------------------------------------------------------
     | Asking somebody else's server for permission
     |--------------------------------------------------------------------------
     |
     | A venue publishes what it is at a URL, and that URL is its identifier —
     | there is nothing to register anywhere and no secret to share. These are
     | the parts of that document an operator gets to decide.
     |
     | `redirect_route` names the route that receives somebody coming back from
     | their own server having approved something. A name rather than an address
     | because the same value has to be published here and sent with every
     | authorization request, and their server refuses if the two disagree — so
     | there is one of it, and moving the route cannot break it.
     |
     | `redirect` overrides that with an absolute URL, for an operator whose
     | venue sits behind something that rewrites addresses.
     |
     | `scopes` are asked for in addition to `atproto`, which is always included
     | and is the claim to be following this profile at all. Anything ATProtocol
     | does not define is an extension of ours and has to be named as one — a
     | scope invented locally is a word no other server on the network knows.
     |
     */

    'oauth' => [
        'redirect_route' => 'venue.callback',
        'redirect' => env('STREETMESH_OAUTH_REDIRECT'),
        'scopes' => [],
    ],

    'network' => [
        'timeout' => 10,
        'cache_seconds' => 300,
    ],

];
