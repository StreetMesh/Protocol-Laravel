<?php

namespace StreetMesh\Protocol\Laravel\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use StreetMesh\Protocol\Laravel\Capabilities\Capabilities;
use StreetMesh\Protocol\Laravel\Identity\DidDocument;
use StreetMesh\Protocol\Laravel\Identity\Identities;

/**
 * Answering "who are you?" to anybody who asks.
 *
 * Unauthenticated on purpose, and it has to be. A record is meant to be
 * checkable years later by somebody with no relationship to this server — if
 * finding out which key signed it required an account, the record would only be
 * as durable as the arrangement between two parties, which is the thing being
 * replaced.
 */
class IdentityController
{
    public function __construct(
        private readonly Identities $identities,
        private readonly Capabilities $capabilities,
    ) {}

    /**
     * This server's DID document.
     */
    public function document(): JsonResponse
    {
        $identity = $this->identities->forServer();

        /*
         * What this server does, taken from what is actually installed rather
         * than from a separate list — so the document and the application cannot
         * drift into disagreeing about it.
         */
        $types = array_map(
            fn ($capability): string => $capability->serviceType(),
            $this->capabilities->all(),
        );

        return response()->json(DidDocument::for(
            $identity,

            /*
             * `??` rather than config()'s default, which applies only when a
             * key is absent — and both of these are present and null whenever
             * their environment variables are unset, which is the ordinary
             * case. So the default was never reached, and this published
             * `https://` with no host after it: every venue walking the chain
             * to this server found nothing at the end of it.
             *
             * The identity's own handle is the last resort, because a server
             * that knows what it is called can say so even when nobody has
             * configured it.
             */
            (string) (config('streetmesh.origin') ?? 'https://'.(config('streetmesh.host') ?? $identity->handle)),

            $types === [] ? 'AtprotoPersonalDataServer' : $types,
        ));
    }

    /**
     * Which identity this hostname stands for.
     *
     * Plain text, as ATProtocol expects. The other half of handle resolution:
     * a document claims a name, and this is the name pointing back.
     */
    public function handle(): Response
    {
        return response($this->identities->forServer()->did)
            ->header('Content-Type', 'text/plain');
    }
}
