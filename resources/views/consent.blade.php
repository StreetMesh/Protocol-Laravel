{{--
    Plain on purpose, and meant to be replaced.

    This package has no business deciding what a domicile looks like, so this is
    the least that can honestly be shown: who is asking, what for, and two
    answers of equal weight. An interface package overrides it by registering a
    `streetmesh` view namespace of its own.

    What must survive any replacement: the venue is named, the request is
    described in words rather than in scope strings, and refusing is exactly as
    easy as agreeing.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Permission') }}</title>
        <style>
            body { font: 16px/1.6 system-ui, sans-serif; margin: 0; display: grid; place-items: center; min-height: 100vh; background: #14181a; color: #f4f4f5; }
            main { max-width: 32rem; padding: 2rem; }
            h1 { font-size: 1.25rem; font-weight: 600; }
            .venue { color: #00ff99; }
            ul { padding-left: 1.2rem; }
            form { display: flex; gap: .75rem; margin-top: 2rem; }
            button { font: inherit; padding: .6rem 1.2rem; border-radius: 6px; border: 1px solid #3f3f46; background: transparent; color: inherit; cursor: pointer; }
            button.yes { background: #00ff99; border-color: #00ff99; color: #14181a; font-weight: 600; }
        </style>
    </head>
    <body>
        <main>
            <h1><span class="venue">{{ $venue }}</span> {{ __('would like permission') }}</h1>

            <p>{{ __('It is asking to:') }}</p>

            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ __('streetmesh::permission.'.$scope) }}</li>
                @endforeach
            </ul>

            <p>{{ __('You can take this back at any time.') }}</p>

            <form method="POST" action="{{ route('streetmesh.oauth.approve') }}">
                @csrf
                <input type="hidden" name="request_uri" value="{{ $permission->request_uri }}">

                <button type="submit" name="answer" value="yes" class="yes">{{ __('Allow') }}</button>
                <button type="submit" name="answer" value="no">{{ __('Not now') }}</button>
            </form>
        </main>
    </body>
</html>
