<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Opening {{ $portal['label'] }} — {{ $client_name }}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 28rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 1.5rem;
        }
        .logo {
            width: 2.5rem;
            height: 2.5rem;
            object-fit: contain;
        }
        .row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.125rem;
            margin: 0;
        }
        p {
            margin: 0.35rem 0 0;
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.45;
        }
        .field {
            margin-top: 1rem;
        }
        label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .copy-row {
            display: flex;
            gap: 0.5rem;
        }
        input {
            flex: 1;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0.55rem 0.65rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8125rem;
        }
        button, .btn {
            border: 0;
            border-radius: 0.5rem;
            padding: 0.55rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-copy {
            background: #eef2ff;
            color: #4338ca;
        }
        .btn-copy:hover { background: #e0e7ff; }
        .btn-primary {
            background: #4f46e5;
            color: #fff;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-primary:hover { background: #4338ca; }
        .hint {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f1f5f9;
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            color: #334155;
        }
        .status {
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: #059669;
            min-height: 1.25rem;
        }
    </style>
</head>
<body>
    @if($launch_mode === 'auto_submit' && !empty($fields))
    <form id="govPortalLogin" action="{{ $form_action }}" method="{{ strtoupper($form_method) }}" target="_blank" style="display:none;">
        @foreach($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>
    @endif

    <div class="card">
        <div class="row">
            <img src="{{ $portal['logo'] }}" alt="{{ $portal['label'] }}" class="logo">
            <div>
                <h1>{{ $portal['label'] }} — {{ $client_name }}</h1>
                <p>{{ $autofill_hint }}</p>
            </div>
        </div>

        @if($username !== '' || $password !== '')
        <div class="field">
            <label for="gov-user">User ID</label>
            <div class="copy-row">
                <input id="gov-user" type="text" readonly value="{{ $username }}">
                <button type="button" class="btn-copy" data-copy-target="gov-user">Copy</button>
            </div>
        </div>
        <div class="field">
            <label for="gov-pass">Password</label>
            <div class="copy-row">
                <input id="gov-pass" type="text" readonly value="{{ $password }}">
                <button type="button" class="btn-copy" data-copy-target="gov-pass">Copy</button>
            </div>
        </div>
        @endif

        <div class="hint" id="gov-hint">
            @if($launch_mode === 'auto_submit' && !empty($fields))
                Submitting login to {{ $portal['label'] }}…
            @else
                Opening {{ $portal['label'] }} portal in a new tab…
            @endif
        </div>
        <div class="status" id="gov-status"></div>

        <a href="{{ $login_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" id="gov-open-manual">
            Open {{ $portal['label'] }} manually
        </a>
    </div>

    <script>
        (function () {
            const loginUrl = @json($login_url);
            const launchMode = @json($launch_mode);
            const hasFields = @json(!empty($fields));
            const hint = document.getElementById('gov-hint');
            const status = document.getElementById('gov-status');

            function copyFromInput(id) {
                const input = document.getElementById(id);
                if (!input) return;
                input.select();
                input.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(input.value).then(function () {
                    status.textContent = 'Copied to clipboard.';
                }).catch(function () {
                    status.textContent = 'Select the field and press Ctrl+C.';
                });
            }

            document.querySelectorAll('[data-copy-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    copyFromInput(button.getAttribute('data-copy-target'));
                });
            });

            function openPortalTab() {
                window.open(loginUrl, '_blank', 'noopener,noreferrer');
            }

            if (launchMode === 'auto_submit' && hasFields) {
                const form = document.getElementById('govPortalLogin');
                if (form) {
                    setTimeout(function () {
                        form.submit();
                        hint.textContent = 'Login submitted in a new tab. If it did not work, open the portal manually and paste credentials.';
                    }, 400);
                }
            } else {
                openPortalTab();
                setTimeout(function () { copyFromInput('gov-user'); }, 600);
                hint.textContent = @json($autofill_hint);
            }
        })();
    </script>
</body>
</html>
