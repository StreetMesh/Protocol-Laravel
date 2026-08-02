<?php

namespace StreetMesh\Protocol\Laravel\Http;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use StreetMesh\Protocol\ClientMetadata;
use StreetMesh\Protocol\Jwk;
use StreetMesh\Protocol\Laravel\Identity\Identities;
use StreetMesh\Protocol\P256;

/**
 * The two documents a venue publishes so a stranger's server can trust it.
 *
 * Between them they are the whole of client registration. A domicile that has
 * never heard of this venue reads the first to learn what it is, and the second
 * to check what it signed — and nothing is exchanged, agreed or stored on either
 * side beforehand.
 *
 * Both are public and both must be, which is worth stating plainly next to
 * anything that publishes a key: what goes out here is the half that verifies,
 * never the half that signs.
 */
final class ClientController
{
    public function metadata(Identities $identities): JsonResponse
    {
        $server = $identities->forServer();
        $host = 'https://'.$server->handle;

        return response()->json(ClientMetadata::forVenue(
            clientId: route('streetmesh.client'),
            clientName: (string) config('app.name'),
            clientUri: $host,
            /*
             * `??` rather than config()'s second argument, which is returned
             * only when the key is absent. The key is present and null whenever
             * the environment variable behind it is unset, which is the ordinary
             * case — so the default here would never have been reached.
             */
            redirectUris: [(string) (config('streetmesh.oauth.redirect') ?? $host.'/visit/callback')],
            jwksUri: route('streetmesh.jwks'),
            scopes: (array) config('streetmesh.oauth.scopes', []),
        )->toArray());
    }

    /**
     * The public halves of what this venue signs with.
     *
     * Named by the fragment the identity already uses for its signing key, so
     * the same key has one name here and in the DID document rather than two.
     */
    public function keys(Identities $identities): JsonResponse
    {
        $key = $identities->forServer()->key();

        if (! $key instanceof P256) {
            throw new RuntimeException(
                'This server signs on '.$key->curve().', which JOSE has no agreed spelling for here. '
                .'A client key must be P-256.'
            );
        }

        return response()->json(ClientMetadata::keySet(['atproto' => Jwk::forP256($key)]));
    }
}
