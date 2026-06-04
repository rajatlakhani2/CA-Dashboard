<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register your firm — CA Dashboard</title>
    @include('partials.head-assets')
</head>
<body class="h-full">
    <div class="flex min-h-full flex-col justify-center py-10 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-lg text-center">
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">SaaS — new firm</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-900">Create your CA firm workspace</h2>
            <p class="mt-2 text-sm text-slate-500">Your clients, tasks, and invoices stay private — other firms cannot see your data.</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
            <div class="bg-white py-8 px-6 shadow sm:rounded-xl sm:px-10">
                <form class="space-y-5" method="POST" action="{{ route('register.organization') }}">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-900">Firm / business name</label>
                        <input name="firm_name" type="text" required value="{{ old('firm_name') }}"
                            placeholder="Sharma & Associates"
                            class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-900">Workspace ID</label>
                        <p class="text-xs text-slate-500">Lowercase, hyphens only — used at login (e.g. sharma-ca)</p>
                        <input name="workspace" type="text" required value="{{ old('workspace') }}"
                            pattern="[a-z0-9]+(-[a-z0-9]+)*"
                            placeholder="sharma-ca"
                            class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm font-mono">
                    </div>

                    <hr class="border-slate-200">

                    <div>
                        <label class="block text-sm font-medium text-slate-900">Your name (partner / owner)</label>
                        <input name="admin_name" type="text" required value="{{ old('admin_name') }}"
                            class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-900">Your email</label>
                        <input name="admin_email" type="email" required value="{{ old('admin_email') }}"
                            class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 sm:text-sm">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-900">Password</label>
                            <input name="admin_password" type="password" required
                                class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-900">Confirm</label>
                            <input name="admin_password_confirmation" type="password" required
                                class="mt-1 block w-full rounded-md py-2 px-3 ring-1 ring-slate-300 sm:text-sm">
                        </div>
                    </div>

                    @if ($errors->any())
                    <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <button type="submit" class="w-full rounded-md bg-indigo-600 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">
                        Create workspace
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">
                    Already have a workspace?
                    <a href="{{ route('login') }}" class="font-semibold text-indigo-600">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
