<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Client Portal')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full font-sans text-slate-800">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 py-4">
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Client portal</p>
            <h1 class="text-lg font-bold text-slate-900">@yield('portal_client', 'Your account')</h1>
        </div>
    </header>
    <main class="max-w-3xl mx-auto px-4 py-8">
        @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="max-w-3xl mx-auto px-4 py-6 text-center text-xs text-slate-400">
        Secure link · Do not share publicly · Questions? Contact your CA firm.
    </footer>
</body>
</html>
