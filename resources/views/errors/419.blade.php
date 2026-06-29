<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired — {{ \App\Support\Branding::dashboardName() }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { max-width: 420px; width: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
        h1 { font-size: 1.25rem; color: #0f172a; margin: 0 0 0.5rem; }
        p { font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0 0 1.25rem; }
        a { display: inline-block; background: linear-gradient(135deg, #2563eb, #0d9488); color: #fff; font-weight: 600; font-size: 0.875rem; padding: 0.65rem 1.25rem; border-radius: 10px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Session expired</h1>
        <p>Your sign-in page was open too long, or cookies were blocked. Refresh and sign in again — this is normal after a few hours of idle time.</p>
        <a href="{{ route('login') }}">Go to sign in</a>
    </div>
    <script>setTimeout(function () { window.location.replace(@json(route('login'))); }, 2500);</script>
</body>
</html>
