<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — CA Dashboard</title>
    @include('partials.head-assets')
</head>

<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Multi-firm SaaS</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">Sign in to your workspace</h2>
            <p class="mt-2 text-sm text-slate-500">Each CA firm has its own isolated data. Use your firm workspace ID + email.</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form class="space-y-5" action="{{ route('login') }}" method="POST">
                    @csrf

                    <div>
                        <label for="workspace" class="block text-sm font-medium text-slate-900">Workspace ID</label>
                        <p class="text-xs text-slate-500 mt-0.5">From your firm admin (e.g. <code class="text-indigo-600">rla</code>, <code class="text-indigo-600">sharma-ca</code>)</p>
                        <input id="workspace" name="workspace" type="text" required autocomplete="organization"
                            value="{{ old('workspace', $workspace ?? '') }}"
                            placeholder="your-firm-id"
                            class="mt-2 block w-full rounded-md border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-900">Email</label>
                        <input id="email" name="email" type="email" required autocomplete="email"
                            value="{{ old('email') }}"
                            class="mt-2 block w-full rounded-md border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-900">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="mt-2 block w-full rounded-md border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                    </div>

                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" value="1" class="h-4 w-4 rounded text-indigo-600">
                        <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
                    </div>

                    @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <button type="submit" class="w-full rounded-md bg-indigo-600 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                        Sign in
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    New CA firm?
                    <a href="{{ route('register.organization') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Create a workspace</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
