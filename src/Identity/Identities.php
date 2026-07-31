<?php

namespace StreetMesh\Protocol\Laravel\Identity;

use RuntimeException;
use StreetMesh\Protocol\Did;
use StreetMesh\Protocol\Ed25519;
use StreetMesh\Protocol\P256;
use StreetMesh\Protocol\SigningKey;

/**
 * Bringing identities into being.
 *
 * Two methods, and the choice is consequential rather than cosmetic. `did:web`
 * needs nothing but a hostname you already control, which makes it right for
 * development and for a server content to be found where it lives. `did:plc`
 * costs a public entry in a directory and buys two things `did:web` cannot
 * offer at all: an identifier that survives its subject moving, and a dated
 * history of keys so that something signed years ago can still be checked.
 *
 * Minting a `did:plc` publishes to shared infrastructure and cannot be undone,
 * so it is never a side effect of anything here — it is asked for explicitly.
 */
final class Identities
{
    public function __construct(
        private readonly string $host,
        private readonly string $defaultCurve = 'p256',
    ) {}

    /**
     * This server's own identity, made once and then found.
     *
     * A venue signs attestations with it and a domicile answers for itself with
     * it, so nothing federated works until it exists — which is why it is
     * created on demand rather than being a step somebody can forget.
     */
    public function forServer(): Identity
    {
        $existing = Identity::query()->where('is_server', true)->first();

        if ($existing !== null) {
            return $existing;
        }

        $key = $this->generate();

        return Identity::create([
            'did' => (string) Did::forHost($this->host),
            'handle' => $this->host,
            'signing_key' => $this->store($key),
            'signing_curve' => $key->curve(),
            'is_server' => true,
        ]);
    }

    /**
     * An identity for somebody this server hosts.
     *
     * The rotation key is generated and handed back rather than kept, because
     * what it is for is leaving. Whether the caller stores it here as well is a
     * decision with consequences: a resident whose only rotation key lives on
     * this server can move only with this server's cooperation.
     *
     * @return array{identity: Identity, rotationKey: SigningKey}
     */
    public function forResident(string $handle, ?string $baseUrl = null): array
    {
        if (Identity::query()->where('handle', $handle)->exists()) {
            throw new RuntimeException("[{$handle}] is already taken here.");
        }

        $signing = $this->generate();
        $rotation = $this->generate();

        $identity = Identity::create([
            'did' => (string) ($baseUrl === null
                ? Did::forHost($handle)
                : Did::forSubject($baseUrl)),
            'handle' => $handle,
            'signing_key' => $this->store($signing),
            'signing_curve' => $signing->curve(),

            /*
             * Not stored. Holding the only copy of what lets somebody leave
             * would make leaving a favour, so it goes to them and this server
             * keeps no way to move them itself.
             */
            'rotation_key' => null,
            'is_server' => false,
        ]);

        return ['identity' => $identity, 'rotationKey' => $rotation];
    }

    public function byDid(string $did): ?Identity
    {
        return Identity::query()->where('did', $did)->first();
    }

    public function byHandle(string $handle): ?Identity
    {
        return Identity::query()->where('handle', strtolower(ltrim(trim($handle), '@')))->first();
    }

    private function generate(): SigningKey
    {
        return match ($this->defaultCurve) {
            /*
             * P-256 by default, because it is the only curve that works both for
             * did:web now and did:plc later — an identity minted on Ed25519
             * could never move to the method that makes it portable.
             */
            'p256' => P256::generate(),
            'ed25519' => Ed25519::generate(),
            default => throw new RuntimeException("This server cannot make [{$this->defaultCurve}] keys."),
        };
    }

    private function store(SigningKey $key): string
    {
        return match (true) {
            $key instanceof P256 => base64_encode($key->publicKey()).':'.$key->secretKey(),
            $key instanceof Ed25519 => $key->publicKey().':'.$key->secretKey(),
            default => throw new RuntimeException('That key cannot be stored.'),
        };
    }
}
