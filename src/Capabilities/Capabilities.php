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

    /**
     * @param  array<string, bool|null>  $wanted  what the operator has switched on
     */
    public function __construct(private readonly array $wanted = []) {}

    /**
     * Offered, unless the operator said otherwise.
     *
     * Installing a package is how a capability arrives, and for a server that
     * does one thing that is the whole of the configuration. Two servers built
     * from one codebase need more than that: Domiciles and Tabletop install the
     * same packages and are not the same server.
     *
     * So it is declared rather than inferred. Deducing it from something
     * adjacent — no hub configured, therefore not a venue — would turn a
     * forgotten line into a server that quietly stopped being what its operator
     * thought it was.
     */
    public function register(Capability $capability): void
    {
        if (($this->wanted[$capability->name()] ?? true) === false) {
            return;
        }

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
     * What a stranger sees at the root.
     *
     * Configured rather than inferred, because a server has one root and
     * guessing would mean the answer changed when a package was installed.
     * Falls back to whatever offers one, so a server with a single capability
     * needs no configuration at all.
     */
    public function frontPage(?string $preferred = null): ?string
    {
        if ($preferred !== null && $this->has($preferred)) {
            return $this->get($preferred)->frontPage();
        }

        foreach ($this->registered as $capability) {
            if ($capability->frontPage() !== null) {
                return $capability->frontPage();
            }
        }

        return null;
    }

    /**
     * Every panel on offer, optionally narrowed and ordered by the operator.
     *
     * An arrangement naming a widget nothing provides is skipped rather than
     * fatal: capabilities are installed and removed, and a server should not
     * fail to render a page because a package it no longer has is still listed
     * in a configuration file.
     *
     * @param  array<int, string>|null  $arrangement  widget names, in order
     * @return array<int, Widget>
     */
    public function widgets(?array $arrangement = null): array
    {
        $offered = [];

        foreach ($this->registered as $capability) {
            foreach ($capability->widgets() as $widget) {
                $offered[$widget->name()] = $widget;
            }
        }

        if ($arrangement === null) {
            return array_values($offered);
        }

        return array_values(array_filter(array_map(
            fn (string $name): ?Widget => $offered[$name] ?? null,
            $arrangement,
        )));
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
