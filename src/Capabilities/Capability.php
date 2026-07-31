<?php

namespace StreetMesh\Protocol\Laravel\Capabilities;

/**
 * Something a server offers.
 *
 * Domicile and venue are capabilities rather than kinds of server. A server may
 * host residents, host things people do together, both, or neither, and nothing
 * downstream should branch on what kind of server it is talking to — only on
 * what that server offers.
 *
 * The awkward consequence, and the reason this exists: two capabilities
 * installed side by side both want the front page, both want to be in the
 * navigation, and both want to say what this server is. None of that can be
 * settled by either of them, because neither outranks the other. So each
 * declares what it provides and the application decides where it goes.
 */
interface Capability
{
    /**
     * What this capability is called, on the wire and in configuration.
     */
    public function name(): string;

    /**
     * How a DID document names the service this capability provides, so that a
     * stranger reading it learns what this server does without being told.
     */
    public function serviceType(): string;

    /**
     * Where a person starts, if the application sends them here.
     *
     * A route name rather than a path, because the path is the application's
     * business and a capability that hard-coded one would be claiming ground it
     * does not own.
     */
    public function home(): ?string;

    /**
     * What this capability contributes to a shell it does not control.
     *
     * @return array<int, array{label: string, route: string, icon?: string}>
     */
    public function navigation(): array;
}
