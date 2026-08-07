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
     * What a stranger sees if the server puts this capability at its root.
     *
     * A view rather than a route, because there is only one root and only the
     * application can say which capability gets it. Null means this capability
     * has nothing to say to somebody who has not arrived yet.
     */
    public function frontPage(): ?string;

    /**
     * The way in, offered on the front page beneath whatever that page says.
     *
     * Declared here because it is not the same door everywhere and the
     * application cannot know which one it is. A domicile signs somebody in: it
     * holds accounts, and the person arriving has one. A venue holds no
     * accounts at all — somebody arrives with an address from another server —
     * so sending them to a login form is offering them a key to a lock that
     * does not exist.
     *
     * Null for a capability with no front door of its own.
     *
     * @return null|array{label: string, route: string}
     */
    public function frontAction(): ?array;

    /**
     * Panels this capability offers for a signed-in person's home page.
     *
     * Offered rather than placed. The home page is the one surface where two
     * installed capabilities genuinely overlap, and the operator arranges it.
     *
     * @return array<int, Widget>
     */
    public function widgets(): array;

    /**
     * What this capability contributes to a shell it does not control.
     *
     * @return array<int, array{label: string, route: string, icon?: string}>
     */
    public function navigation(): array;
}
