<?php

namespace StreetMesh\Protocol\Laravel\Tests;

use StreetMesh\Protocol\Network;

/**
 * The network, as a fixture.
 *
 * The reason Network is an interface: every identity behaviour worth testing is
 * about what happens when somebody else's server says a particular thing, and
 * none of it should need a server, a fixture directory or an internet
 * connection to be true.
 */
final class FakeNetwork implements Network
{
    /** @var array<string, string> */
    private array $documents = [];

    /** @var array<string, array<int, string>> */
    private array $records = [];

    public function serve(string $url, mixed $body): self
    {
        $this->documents[$url] = is_string($body) ? $body : (string) json_encode($body);

        return $this;
    }

    /**
     * @param  array<int, string>  $values
     */
    public function serveTxt(string $name, array $values): self
    {
        $this->records[$name] = $values;

        return $this;
    }

    public function get(string $url): ?string
    {
        return $this->documents[$url] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function txt(string $name): array
    {
        return $this->records[$name] ?? [];
    }

    /**
     * Accepted without recording anything.
     *
     * Nothing that resolves an identity writes, so no test here needs a
     * submitted operation to go anywhere. A test that does should record it
     * rather than widen this.
     *
     * @param  array<string, string>  $headers
     * @return array{status: int, body: string}
     */
    public function post(string $url, string $body, array $headers = []): array
    {
        return ['status' => 200, 'body' => ''];
    }
}
