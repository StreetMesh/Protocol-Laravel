<?php

namespace StreetMesh\Protocol\Laravel\Tests;

use PHPUnit\Framework\TestCase as Plain;
use StreetMesh\Protocol\Laravel\Capabilities\Capabilities;
use StreetMesh\Protocol\Laravel\Capabilities\Capability;

/**
 * What a server offers, and the operator's say in it.
 *
 * Installing a package is how a capability arrives, and for a server that does
 * one thing that is the whole of the configuration. These are the tests for the
 * case that is not: two servers built from one codebase, installing the same
 * packages, which are not the same server.
 */
final class CapabilitiesTest extends Plain
{
    public function test_what_is_installed_is_offered(): void
    {
        $capabilities = new Capabilities;
        $capabilities->register($this->capability('venue'));

        $this->assertTrue($capabilities->has('venue'));
    }

    /**
     * Unset is not off. A server that says nothing offers what it installed,
     * which is what a development machine and every existing install expect.
     */
    public function test_saying_nothing_leaves_it_on(): void
    {
        $capabilities = new Capabilities(['domicile' => null]);
        $capabilities->register($this->capability('domicile'));

        $this->assertTrue($capabilities->has('domicile'));
    }

    public function test_an_operator_can_switch_one_off(): void
    {
        $capabilities = new Capabilities(['venue' => false]);
        $capabilities->register($this->capability('venue'));

        $this->assertFalse($capabilities->has('venue'));
    }

    /**
     * The arrangement this exists for: one codebase, two servers.
     */
    public function test_one_off_leaves_the_others_alone(): void
    {
        $capabilities = new Capabilities(['venue' => false]);
        $capabilities->register($this->capability('venue'));
        $capabilities->register($this->capability('domicile'));

        $this->assertFalse($capabilities->has('venue'));
        $this->assertTrue($capabilities->has('domicile'));
    }

    /**
     * A switch named for a capability nobody here has heard of works the same
     * way, because the name comes from the capability rather than from a list.
     */
    public function test_a_capability_this_package_never_heard_of_has_a_switch_too(): void
    {
        $capabilities = new Capabilities(['shop' => false]);
        $capabilities->register($this->capability('shop'));

        $this->assertFalse($capabilities->has('shop'));
    }

    private function capability(string $name): Capability
    {
        return new class($name) implements Capability
        {
            public function __construct(private readonly string $name) {}

            public function name(): string
            {
                return $this->name;
            }

            public function serviceType(): string
            {
                return 'Test';
            }

            public function frontPage(): ?string
            {
                return null;
            }

            public function frontAction(): ?array
            {
                return null;
            }

            public function whoever(): ?array
            {
                return null;
            }

            public function widgets(): array
            {
                return [];
            }

            public function navigation(): array
            {
                return [];
            }
        };
    }
}
