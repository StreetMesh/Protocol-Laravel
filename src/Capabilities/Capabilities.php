<?php

namespace StreetMesh\Protocol\Laravel\Capabilities;

use RuntimeException;

/**
 * What this server offers, collected from whatever is installed.
 *
 * The arbiter for a problem neither party can settle. A domicile package and a
 * venue package both want the front page and both belong in the navigation, and
 * neither outranks the other — so each registers what it provides here, and the
 * application decides what to do with the collection.
 *
 * It also answers the same question on the wire. A DID document has to say what
 * a server does; asking the same registry means the interface and the protocol
 * cannot drift into disagreeing about it, which they would if each kept its own
 * list.
 */
final class Capabilities
{
    /** @var array<string, Capability> */
    private array $registered = [];

    public function register(Capability $capability): void
    {
        $this->registered[$capability->name()] = $capability;
    }

    public function has(string $name): bool
    {
        return isset($this->registered[$name]);
    }

    public function get(string $name): Capability
    {
        return $this->registered[$name]
            ?? throw new RuntimeException("This server does not offer [{$name}].");
    }

    /**
     * @return array<string, Capability>
     */
    public function all(): array
    {
        return $this->registered;
    }

    /**
     * The names, as a discovery document publishes them.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->registered);
    }

    /**
     * Where somebody arriving at the front door should be sent.
     *
     * Configured rather than inferred. A server that is both a home and a
     * gathering place cannot have both as its front page, and guessing would
     * mean the answer changed when a package was installed.
     */
    public function homeRoute(?string $preferred = null): ?string
    {
        if ($preferred !== null && $this->has($preferred)) {
            return $this->get($preferred)->home();
        }

        foreach ($this->registered as $capability) {
            if ($capability->home() !== null) {
                return $capability->home();
            }
        }

        return null;
    }

    /**
     * Everything the installed capabilities want in the navigation, in the
     * order they were registered.
     *
     * @return array<int, array{label: string, route: string, icon?: string}>
     */
    public function navigation(): array
    {
        return array_merge(...array_map(
            fn (Capability $capability): array => $capability->navigation(),
            array_values($this->registered),
        ) ?: [[]]);
    }
}
