<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Authorization</title>
    @vite('resources/css/app.css')
</head>
<body class="passport-authorize">
<div
    class="flex flex-col items-center min-h-screen pt-6 text-white sm:justify-center sm:pt-0 bg-default-background">
    <div>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 440 110"><text transform="translate(44.5 71.1)" style="font-size:113.8284912109375px;font-family:ColonnaMT, Colonna MT;fill:white">tempus</text></svg>
    </div>

    <div
        class="w-full px-6 py-4 mt-6 overflow-hidden border shadow-md sm:max-w-md bg-card-background border-card-border sm:rounded-lg">

        <div class="card card-default">
            <div class="text-base text-muted">
                <!-- Introduction -->
                <p class="pb-4 text-center"><strong class="text-white">{{ $client->name }}</strong> is requesting permission
                    to access your
                    account.</p>

                <!-- Scope List -->
                @if (count($scopes) > 0)
                    <div class="pb-4">
                        <p><strong>This application will be able to:</strong></p>

                        <ul class="py-2 pl-5 list-disc">
                            @foreach ($scopes as $scope)
                                <li>{{ $scope->description }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col space-x-0 space-y-3 sm:flex-row sm:space-x-5 sm:space-y-0">
                    <!-- Authorize Button -->
                    <form method="post" class="flex-1" action="{{ route('passport.authorizations.approve') }}">
                        @csrf

                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button type="submit"
                                class="items-center w-full px-2 py-2 text-xs font-semibold text-center text-white transition duration-150 ease-in-out border rounded-md sm:px-3 bg-accent-300/10 border-accent-300/20 sm:text-sm hover:bg-accent-300/20 active:bg-accent-300/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Authorize
                        </button>
                    </form>

                    <!-- Cancel Button -->
                    <form method="post" class="flex-1" action="{{ route('passport.authorizations.deny') }}">
                        @csrf
                        @method('DELETE')

                        <input type="hidden" name="state" value="{{ $request->state }}">
                        <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                        <input type="hidden" name="auth_token" value="{{ $authToken }}">
                        <button
                            class="w-full text-center text-xs sm:text-sm px-2 sm:px-3 py-2 bg-button-secondary-background border border-button-secondary-border hover:bg-button-secondary-background-hover shadow-sm transition text-white rounded-lg font-medium items-center space-x-1.5 focus-visible:border-input-border-active focus:outline-none focus:ring-0 disabled:opacity-25 ease-in-out">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>
</body>
</html>
