<?php

namespace StreetMesh\Protocol\Laravel\Records;

use InvalidArgumentException;

/**
 * Which kinds of record this server knows about, and who may see each.
 *
 * Visibility belongs to the kind of record, not to the record. A chess result is
 * public because chess results are public; a message is private because messages
 * are private. Deciding it per record would mean there is an input somewhere
 * that says whether this one is private, and an input is something that can be
 * wrong, forged, or flipped by a bug in a form.
 *
 * So there is no such input. A record's visibility is looked up from its
 * collection at the moment it is written, and a collection nobody has declared
 * cannot be written to at all. That last part is deliberate friction: adding a
 * kind of record to a server should be a decision somebody makes once, in
 * configuration, rather than a consequence of a typo in a collection name.
 *
 * The asymmetry matters. Publishing cannot be undone — a record replicated out
 * of a public collection cannot be recalled — so the failure that must not
 * happen is a private thing becoming public, and the way to prevent it is to
 * make sure no code path takes visibility as an argument.
 */
final class Collections
{
    /**
     * @param  array<string, string>  $declared  collection NSID => visibility
     */
    public function __construct(private readonly array $declared = []) {}

    public function knows(string $collection): bool
    {
        return isset($this->declared[$collection]);
    }

    public function visibilityOf(string $collection): string
    {
        $visibility = $this->declared[$collection] ?? throw new InvalidArgumentException(
            "This server has not declared the collection [{$collection}]. Declare it in config/streetmesh.php "
            .'with the visibility its records should have, which is a decision rather than a default.'
        );

        return match ($visibility) {
            Record::PUBLIC, Record::PRIVATE => $visibility,
            default => throw new InvalidArgumentException(
                "Collection [{$collection}] is declared [{$visibility}], which is neither public nor private."
            ),
        };
    }

    public function isPublic(string $collection): bool
    {
        return $this->visibilityOf($collection) === Record::PUBLIC;
    }

    /**
     * Every collection a stranger is allowed to read, which is what a listing
     * of somebody's repository may contain.
     *
     * @return array<int, string>
     */
    public function public(): array
    {
        return array_keys(array_filter(
            $this->declared,
            fn (string $visibility): bool => $visibility === Record::PUBLIC,
        ));
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->declared;
    }
}
