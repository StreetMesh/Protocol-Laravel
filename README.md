# StreetMesh Protocol for Laravel

The StreetMesh protocol, bound to the framework.

```sh
composer require streetmesh/protocol-laravel
```

The protocol itself lives in
[`streetmesh/protocol`](https://github.com/StreetMesh/Protocol-PHP), which knows
nothing about Laravel and takes bytes in and gives bytes out. That package knows
*how* to verify a signature; this one knows *when*, and gives the other one the
application's HTTP client, cache and database to work through.

## What it provides

**A record store shaped like a repository.** Records are addressed by subject,
collection and key rather than by a row id, written once, and named by the hash
of what they say. Listing somebody's history in the order it happened is an
index scan rather than a sort, because the key sorts by time.

**Bindings.** The framework-free layer defines what it needs from the outside
world in a two-method interface and ships a plain cURL implementation so it
works alone. Here that is replaced by the application's own HTTP client, with
its timeouts and — the part that matters — its cache. Identity documents are
fetched constantly and change rarely.

**One guarantee that is easy to lose and expensive to lose.** Laravel blanks and
trims request input as a kindness to HTML forms. Applied to a signed document it
is corruption: a signature covers bytes, so turning an empty string into null
changes what is verified and the check fails for a document that was never
wrong. The failure looks exactly like forgery and is data-dependent, so it
appears intermittent. This package turns that off for the paths that carry
signatures, so no implementor has to know.

## Records

```php
use StreetMesh\Protocol\Laravel\Records\RecordStore;

$record = app(RecordStore::class)->put(
    did: 'did:plc:z72i7hdynmk6r22z27h6tvur',
    collection: 'com.streetmesh.games.chess',
    value: ['result' => 'win', 'venue' => 'did:web:games.example'],
);

(string) $record->address();   // at://did:plc:…/com.streetmesh.games.chess/3mq…
```

Notice what you cannot pass: a key, a content hash, or a visibility. Keys are
minted, hashes computed, and visibility looked up from the collection — so none
of the three can be got wrong by a caller, because no caller gets to say.

### Visibility belongs to the collection

```php
'collections' => [
    'com.streetmesh.games.chess'   => Record::PUBLIC,
    'com.streetmesh.messages.direct' => Record::PRIVATE,
],
```

A chess result is public because chess results are public. Deciding it per
record would mean there is an input somewhere saying whether *this one* is
private, and an input is something that can be wrong, forged, or flipped by a
bug in a form. Publishing cannot be undone — a record replicated out of a public
collection cannot be recalled — so the failure worth designing against is a
private thing becoming public, and the way to prevent it is to leave nothing to
flip.

A collection nobody has declared cannot be written to at all. That friction is
deliberate: adding a kind of record should be a decision made once in
configuration, not a consequence of a mistyped name.

### Records are written once

A correction is a new record that says what it corrects. Not tidiness — if a
record could change after being cited, then an address would name whatever is
there now rather than what was cited, and every signature over it and every copy
of it elsewhere would drift apart silently.

## Attestations

A venue signs what happened; a domicile checks it before keeping it. Neither
side trusts the other, and neither has to still exist for the statement to stay
checkable.

```php
use StreetMesh\Protocol\Laravel\Attestations\Attestations;

$attestation = app(Attestations::class)->verify($compact, receivedAt: now());

$attestation->issuer;                    // did:plc:… — who said it
$attestation->claim('result');           // what they said
$attestation->checkedAgainstHistory();   // and how confident you get to be
```

`receivedAt` should be when this server saw the document, not when the document
says it was issued. The second is asserted by the party being checked and can be
backdated; the first is asserted by the party doing the checking and cannot.

### `checkedAgainstHistory()` is not decoration

Asked which key an identity was using at a past moment, `did:plc` answers from
its audit log and `did:web` cannot answer at all — it publishes a document and
no history, so the best available is the key in use now.

Verifying a year-old record against a key that might have been rotated last week
is a different act from verifying it against the key that was demonstrably
current when it was signed. Returning a bare key would make those two
indistinguishable at the call site, which is precisely where the difference
matters.

## Portability

`RecordStore::exportFor()` returns everything of somebody's, in order. A person
can leave with what is theirs whether or not this server ever speaks the wider
protocol, which is most of what portability means and needs no repository
implementation to be real.

## Tests

```sh
composer test
```

## License

MIT.
