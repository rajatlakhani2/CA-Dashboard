<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Support\Branding::DEFAULT_NAME }}</title>
    <script>
        window.location.href = "/login";
    </script>
</head>

<body class="antialiased">
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif;">
        <p>Redirecting to login...</p>
    </div>
</body>

</html>