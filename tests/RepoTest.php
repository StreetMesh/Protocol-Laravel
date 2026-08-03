<?php

namespace StreetMesh\Protocol\Laravel\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use StreetMesh\Protocol\ClientAssertion;
use StreetMesh\Protocol\ClientMetadata;
use StreetMesh\Protocol\Dpop;
use StreetMesh\Protocol\Jwk;
use StreetMesh\Protocol\Laravel\Permissions\Permissions;
use StreetMesh\Protocol\Laravel\Permissions\Spent;
use StreetMesh\Protocol\Laravel\Records\Record;
use StreetMesh\Protocol\Network;
use StreetMesh\Protocol\P256;
use StreetMesh\Protocol\Pkce;
use StreetMesh\Protocol\Scope;

/**
 * The end of the exercise: a venue writes a record into somebody else's store.
 *
 * Everything before this was arranging permission. This is spending it — and
 * the record that results belongs to the resident rather than to the venue that
 * wrote it, which is the whole claim the project makes.
 */
class RepoTest extends TestCase
{
    private const VENUE = 'https://games.test/client-metadata.json';

    private const CHESS = 'com.streetmesh.games.chess';

    private const ALICE = 'did:plc:alice';

    private P256 $venueKey;

    private P256 $sessionKey;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->venueKey = P256::generate();
        $this->sessionKey = P256::generate();

        $network = (new FakeNetwork)
            ->serve(self::VENUE, [
                'client_id' => self::VENUE,
                'redirect_uris' => ['https://games.test/visit/callback'],
                'jwks_uri' => 'https://games.test/jwks.json',
            ])
            ->serve('https://games.test/jwks.json', ClientMetadata::keySet([
                'atproto' => Jwk::forP256($this->venueKey),
            ]));

        $this->app->instance(Network::class, $network);

        $this->token = $this->grant('atproto '.Scope::forRepo([self::CHESS], [Scope::CREATE]));
    }

    /**
     * A permission, arranged the long way, because a token conjured directly
     * would not prove the thing being tested.
     */
    private function grant(string $scope): string
    {
        $permissions = new Permissions($this->app->make(Network::class), $this->app->make(Spent::class));
        $issuer = 'https://games.test';
        $pkce = Pkce::generate();
        $thumbprint = Jwk::forP256($this->sessionKey)->thumbprint();

        $pushed = $permissions->push([
            'client_id' => self::VENUE,
            'redirect_uri' => 'https://games.test/visit/callback',
            'scope' => $scope,
            'code_challenge' => $pkce->challenge(),
            'code_challenge_method' => 'S256',
            'client_assertion' => ClientAssertion::for(self::VENUE, $issuer, $this->venueKey),
        ], $issuer, $thumbprint);

        $code = $permissions->approve($permissions->pending((string) $pushed->request_uri), self::ALICE);

        return $permissions->redeem([
            'client_id' => self::VENUE,
            'code' => $code,
            'code_verifier' => $pkce->verifier,
            'client_assertion' => ClientAssertion::for(self::VENUE, $issuer, $this->venueKey),
        ], $issuer, $thumbprint)['access'];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return TestResponse<JsonResponse>
     */
    private function write(array $body, ?string $token = null, ?P256 $key = null): TestResponse
    {
        $token ??= $this->token;
        $key ??= $this->sessionKey;
        $url = url('/xrpc/com.atproto.repo.createRecord');

        return $this->postJson($url, $body, [
            'Authorization' => 'DPoP '.$token,
            'DPoP' => Dpop::proof($key, 'POST', $url, accessToken: $token),
        ]);
    }

    public function test_a_venue_writes_a_finished_game_into_the_players_own_store(): void
    {
        $response = $this->write([
            'collection' => self::CHESS,
            'record' => ['result' => 'win', 'seat' => 'white', 'pgn' => ''],
        ]);

        $response->assertCreated();

        $record = Record::query()->firstOrFail();

        $this->assertSame(self::ALICE, $record->did, 'the record belongs to the player, not the venue');
        $this->assertSame(self::CHESS, $record->collection);
        $this->assertSame('win', $record->value['result']);
        $this->assertSame($record->cid, $response->json('cid'));
        $this->assertStringStartsWith('at://'.self::ALICE, (string) $response->json('uri'));
    }

    /**
     * An empty string inside a signed record used to become null on the way in,
     * and the signature would then fail to verify for a record that was never
     * wrong. Worth a test on the one route where a stranger posts a record.
     */
    public function test_an_empty_field_survives_being_posted(): void
    {
        $this->write([
            'collection' => self::CHESS,
            'record' => ['result' => 'win', 'pgn' => ''],
        ])->assertCreated();

        $this->assertSame('', Record::query()->firstOrFail()->value['pgn']);
    }

    /**
     * The scope decides, not the venue's identity and not this server's opinion
     * of it.
     */
    public function test_a_collection_the_permission_does_not_cover_is_refused(): void
    {
        $this->write([
            'collection' => 'com.streetmesh.messages.direct',
            'record' => ['body' => 'hello'],
        ])->assertForbidden()->assertJsonPath('error', 'insufficient_scope');

        $this->assertSame(0, Record::query()->count());
    }

    /**
     * What the resident was actually shown, kept as it was asked for — a scope
     * that drifted between the consent screen and the stored permission would
     * mean somebody agreed to one thing and granted another.
     */
    public function test_the_permission_holds_exactly_what_was_asked_for(): void
    {
        $permission = $this->app->make(Permissions::class)
            ->holder($this->token, Jwk::forP256($this->sessionKey)->thumbprint());

        $this->assertSame(
            ['atproto', 'repo:'.self::CHESS.'?action=create'],
            $permission->scopes(),
        );
    }

    /**
     * The heart of it. A token that worked for whoever held it would be a
     * password, and copying it would be theft of the account.
     */
    public function test_a_token_presented_by_another_key_is_refused(): void
    {
        $this->write(['collection' => self::CHESS, 'record' => ['result' => 'win']], key: P256::generate())
            ->assertUnauthorized();

        $this->assertSame(0, Record::query()->count());
    }

    public function test_a_token_presented_without_a_proof_is_refused(): void
    {
        $url = url('/xrpc/com.atproto.repo.createRecord');

        $this->postJson($url, ['collection' => self::CHESS, 'record' => ['result' => 'win']], [
            'Authorization' => 'DPoP '.$this->token,
        ])->assertUnauthorized();
    }

    /**
     * A proof is bound to the token it accompanies, so one made for a different
     * token cannot be presented alongside this one.
     */
    public function test_a_proof_made_for_a_different_token_is_refused(): void
    {
        $url = url('/xrpc/com.atproto.repo.createRecord');

        $this->postJson($url, ['collection' => self::CHESS, 'record' => ['result' => 'win']], [
            'Authorization' => 'DPoP '.$this->token,
            'DPoP' => Dpop::proof($this->sessionKey, 'POST', $url, accessToken: 'some-other-token'),
        ])->assertUnauthorized();
    }

    public function test_nothing_is_written_without_permission_at_all(): void
    {
        $this->postJson(url('/xrpc/com.atproto.repo.createRecord'), [
            'collection' => self::CHESS,
            'record' => ['result' => 'win'],
        ])->assertUnauthorized();

        $this->assertSame(0, Record::query()->count());
    }

    /**
     * Withdrawal has to refuse rather than lapse, and this is the route where
     * that actually matters.
     */
    public function test_writing_stops_the_moment_permission_is_withdrawn(): void
    {
        $permissions = $this->app->make(Permissions::class);

        $this->write(['collection' => self::CHESS, 'record' => ['result' => 'win']])->assertCreated();

        $permissions->withdraw(
            $permissions->holder($this->token, Jwk::forP256($this->sessionKey)->thumbprint())
        );

        $this->write(['collection' => self::CHESS, 'record' => ['result' => 'win']])->assertUnauthorized();

        $this->assertSame(1, Record::query()->count(), 'only the one written while it was allowed');
    }
}
