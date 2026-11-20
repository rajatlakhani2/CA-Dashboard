@php
    $apkPath = public_path('downloads/ca-dashboard.apk');
    $hasApk = is_file($apkPath);
    $info = is_file(public_path('downloads/ca-dashboard-apk.json'))
        ? (json_decode((string) file_get_contents(public_path('downloads/ca-dashboard-apk.json')), true) ?: [])
        : [];
    $sizeMb = $hasApk ? number_format(filesize($apkPath) / 1048576, 1) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Get the CA Dashboard app</title>
    <meta name="theme-color" content="#1e293b">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/app-icons/icon-180.png">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #0f172a; }
        header { background: #1e293b; color: #fff; padding: 32px 20px 28px; text-align: center; }
        header img { width: 72px; height: 72px; border-radius: 18px; }
        header h1 { font-size: 22px; margin: 14px 0 6px; }
        header p { margin: 0; color: #cbd5e1; font-size: 15px; }
        main { max-width: 520px; margin: 0 auto; padding: 20px 16px 40px; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 16px; }
        .card h2 { font-size: 17px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 14px; line-height: 1.5; }
        .btn { display: block; text-align: center; text-decoration: none; background: #1e293b; color: #fff; font-weight: 600; font-size: 16px; padding: 14px; border-radius: 12px; margin: 14px 0 6px; }
        ol { padding-left: 20px; margin: 10px 0 0; }
        li { font-size: 14px; line-height: 1.55; color: #334155; margin-bottom: 6px; }
        .note { background: #fffbeb; color: #92400e; border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-top: 12px; }
        a.link { color: #2563eb; font-weight: 600; }
    </style>
</head>
<body>
    <header>
        <img src="/app-icons/icon-192.png" alt="">
        <h1>CA Dashboard on your phone</h1>
        <p>Your tasks, filings and clients – with alerts, fingerprint lock and document scanning.</p>
    </header>
    <main>
        <section class="card" id="android">
            <h2>Android phones</h2>
            @if($hasApk)
                <p class="muted">Version {{ $info['version'] ?? '1.0' }} · {{ $sizeMb }} MB</p>
                <a class="btn" href="/downloads/ca-dashboard.apk?v={{ $info['build'] ?? filemtime($apkPath) }}" download>Download the Android app</a>
                <ol>
                    <li>Tap <strong>Download</strong>, then open the file when it finishes.</li>
                    <li>If Android asks, allow your browser to <strong>install unknown apps</strong> (one time only).</li>
                    <li>Tap <strong>Install</strong>, open <strong>CA Dashboard</strong> and sign in with your usual workspace, email and password.</li>
                    <li>Allow notifications when asked, so you get task and due-date alerts.</li>
                </ol>
                <p class="note">You only install once. Screens and features update by themselves – no need to download again unless we tell you.</p>
            @else
                <p class="muted">The Android app is being prepared. Please check this page again shortly.</p>
            @endif
        </section>

        <section class="card" id="iphone">
            <h2>iPhone</h2>
            <p class="muted">Add CA Dashboard to your home screen – it opens full screen like an app.</p>
            <ol>
                <li>Open <strong>app.kuhu.org.in</strong> in <strong>Safari</strong>.</li>
                <li>Tap the <strong>Share</strong> button (square with an arrow).</li>
                <li>Choose <strong>Add to Home Screen</strong>, then <strong>Add</strong>.</li>
            </ol>
        </section>

        <p class="muted" style="text-align:center"><a class="link" href="{{ route('login') }}">Back to sign in</a></p>
    </main>
</body>
</html>
