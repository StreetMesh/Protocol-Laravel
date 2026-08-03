<?php

namespace StreetMesh\Protocol\Laravel\Http;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use StreetMesh\Protocol\Laravel\Identity\Identity;
use StreetMesh\Protocol\Laravel\Permissions\Permissions;

/**
 * The one screen in this whole exchange that a person actually looks at.
 *
 * Everything else here is two servers talking. This is where somebody is told
 * what a venue is asking for and decides, and it is the only place the answer
 * can come from — a domicile that could grant permission without asking would
 * not be a domicile in the sense this project means.
 *
 * The view is deliberately plain and deliberately overridable. What a resident
 * sees belongs to whatever interface package is installed; what cannot be
 * overridden is that they are asked at all.
 */
final class ConsentController
{
    public function __construct(private readonly Permissions $permissions) {}

    public function show(Request $request): View
    {
        $permission = $this->permissions->pending((string) $request->query('request_uri'));

        /** @var view-string $consent */
        $consent = 'streetmesh::consent';

        return view($consent, [
            'permission' => $permission,

            /*
             * The client identifier is a URL, and its host is the only part of
             * it a person can reasonably judge. Shown as the venue's name
             * rather than the whole address, which nobody reads.
             */
            'venue' => parse_url($permission->client_id, PHP_URL_HOST),

            'scopes' => $permission->scopes(),
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        $permission = $this->permissions->pending((string) $request->input('request_uri'));

        $did = $this->didOf($request);

        if ($request->input('answer') !== 'yes') {
            /*
             * A refusal goes back to the venue as a refusal rather than as a
             * silence, because a venue left waiting cannot tell "no" from "the
             * browser closed" and would keep the seat open for either.
             */
            return redirect()->away($this->back($permission->redirect_uri, [
                'error' => 'access_denied',
                'state' => $permission->state,
            ]));
        }

        $code = $this->permissions->approve($permission, $did);

        return redirect()->away($this->back($permission->redirect_uri, [
            'code' => $code,
            'state' => $permission->state,

            /*
             * Naming ourselves in the redirect lets the venue confirm the
             * answer came from the server it asked, rather than from whoever
             * got to the callback first.
             */
            'iss' => rtrim(url('/'), '/'),
        ]));
    }

    /**
     * Whose answer this is.
     *
     * A permission is granted over a person's records, so it is their identity
     * that matters and not their login. A signed-in account with no identity
     * cannot grant anything, and saying so is better than granting it under the
     * server's own name.
     */
    private function didOf(Request $request): string
    {
        $user = $request->user();

        $identity = $user === null
            ? null
            : Identity::query()
                ->where('owner_type', $user->getMorphClass())
                ->where('owner_id', $user->getKey())
                ->where('is_server', false)
                ->first();

        return $identity->did ?? throw new RuntimeException(
            'Whoever is signed in has no identity of their own, so there is nothing to grant permission over.'
        );
    }

    /**
     * @param  array<string, string|null>  $answer
     */
    private function back(string $redirect, array $answer): string
    {
        return $redirect.(str_contains($redirect, '?') ? '&' : '?')
            .http_build_query(array_filter($answer, fn (?string $value): bool => $value !== null));
    }
}
