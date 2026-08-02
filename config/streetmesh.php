<?php

return [

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
     | `redirect` is where a person lands coming back from their own server
     | having approved something. It must be one of the addresses the published
     | document lists, or their server will refuse to send them there.
     |
     | `scopes` are asked for in addition to `atproto`, which is always included
     | and is the claim to be following this profile at all. Anything ATProtocol
     | does not define is an extension of ours and has to be named as one — a
     | scope invented locally is a word no other server on the network knows.
     |
     */

    'oauth' => [
        'redirect' => env('STREETMESH_OAUTH_REDIRECT'),
        'scopes' => [],
    ],

    'network' => [
        'timeout' => 10,
        'cache_seconds' => 300,
    ],

];
