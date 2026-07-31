<?php

namespace StreetMesh\Protocol\Laravel\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
    public function __construct(private readonly Identities $identities) {}

    /**
     * This server's DID document.
     */
    public function document(): JsonResponse
    {
        $identity = $this->identities->forServer();

        return response()->json(DidDocument::for(
            $identity,
            (string) config('streetmesh.origin', 'https://'.config('streetmesh.host')),
            config('streetmesh.venue') ? 'StreetMeshVenue' : 'AtprotoPersonalDataServer',
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
