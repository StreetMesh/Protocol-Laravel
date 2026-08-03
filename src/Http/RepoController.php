<?php

namespace StreetMesh\Protocol\Laravel\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use StreetMesh\Protocol\AtUri;
use StreetMesh\Protocol\Dpop;
use StreetMesh\Protocol\Laravel\Permissions\Permission;
use StreetMesh\Protocol\Laravel\Permissions\Permissions;
use StreetMesh\Protocol\Laravel\Records\RecordStore;
use StreetMesh\Protocol\Scope;
use Throwable;

/**
 * Somebody else writing a record into a resident's own store.
 *
 * The end of the whole exercise. A venue asked, a resident agreed, and this is
 * where that agreement is spent — the venue writes the finished game into the
 * player's records, on the player's server, and then has nothing further to do
 * with it. The record is not the venue's copy of what happened; it is the
 * player's, and it outlives the venue.
 *
 * Three things are checked and none of them is "is this venue trustworthy":
 * whether the token is live, whether the key presenting it is the key it was
 * issued to, and whether what was granted covers what is being attempted.
 */
final class RepoController
{
    public function __construct(
        private readonly Permissions $permissions,
        private readonly RecordStore $records,
    ) {}

    public function create(Request $request): JsonResponse
    {
        try {
            $permission = $this->bearer($request);
        } catch (Throwable $refused) {
            return response()->json([
                'error' => 'invalid_token',
                'message' => $refused->getMessage(),
            ], 401);
        }

        $collection = (string) $request->input('collection');

        /*
         * The scope decides, not the venue's identity and not this server's
         * opinion of it. A venue granted `action=create` on chess games cannot
         * write anything else, however well it is thought of.
         */
        if (! Scope::permits($permission->scopes(), $collection, Scope::CREATE)) {
            return response()->json([
                'error' => 'insufficient_scope',
                'message' => "That permission does not cover creating a [{$collection}].",
                'scope' => (string) Scope::forRepo([$collection], [Scope::CREATE]),
            ], 403);
        }

        $value = $request->input('record');

        if (! is_array($value)) {
            return response()->json([
                'error' => 'invalid_request',
                'message' => 'A record has to be an object.',
            ], 400);
        }

        /*
         * Written into the granting resident's own store, and nowhere else. The
         * request does not get to name whose records these are: that was
         * decided when somebody approved this, and letting a parameter override
         * it would let one person's permission write into another's store.
         */
        $record = $this->records->put((string) $permission->did, $collection, $value);

        return response()->json([
            'uri' => (string) AtUri::make($record->did, $record->collection, $record->rkey),
            'cid' => $record->cid,
        ], 201);
    }

    /**
     * Whose permission is being presented, if anybody's.
     *
     * A token alone proves nothing here. It has to arrive with a proof from the
     * key it was issued to, which is what stops a copied token being worth
     * anything to whoever copied it.
     */
    private function bearer(Request $request): Permission
    {
        $header = (string) $request->header('Authorization');

        if (! str_starts_with($header, 'DPoP ')) {
            throw new RuntimeException('A token here is presented with a proof, not on its own.');
        }

        $token = substr($header, strlen('DPoP '));

        $thumbprint = Dpop::check(
            (string) $request->header('DPoP'),
            $request->method(),
            $request->url(),
            accessToken: $token,
        );

        return $this->permissions->holder($token, $thumbprint);
    }
}
