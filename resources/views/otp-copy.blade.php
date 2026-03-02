<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copy OTP Code – Agilify</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1f1c2c">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="manifest" href="/site.webmanifest">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Poppins, Arial, Helvetica, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1f1c2c;
            background-image: linear-gradient(135deg, #1f1c2c, #928dab);
            color: #ffffff;
        }
        .card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 24px;
            padding: 48px 40px;
            text-align: center;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 8px 40px rgba(0,0,0,0.4);
        }
        .brand {
            font-size: 15px;
            font-weight: 700;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        .label {
            font-size: 12px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #d1d5db;
            margin-bottom: 12px;
        }
        .code {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 0.18em;
            color: #ffffff;
            margin-bottom: 32px;
            cursor: pointer;
            user-select: all;
        }
        .btn {
            display: inline-block;
            padding: 14px 36px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            border: none;
            cursor: pointer;
            background: linear-gradient(90deg, #00f5ff, #ff00c8);
            transition: opacity 0.2s, transform 0.15s;
            width: 100%;
        }
        .btn:hover { opacity: 0.9; transform: scale(1.02); }
        .status {
            margin-top: 20px;
            font-size: 14px;
            color: #e5e7eb;
            min-height: 22px;
            transition: color 0.3s;
        }
        .status.success { color: #4ade80; }
        .status.error   { color: #f87171; }
    </style>
</head>
<body>
    <div class="card">
        <p class="brand">Agilify</p>
        <p class="label">One-Time Password</p>
        <p class="code" id="otp-code" title="Click to copy">{{ $code }}</p>
        <button class="btn" id="copy-btn" onclick="copyCode()">Copy code</button>
        <p class="status" id="status"></p>
    </div>

    <script>
        const code = @json($code);

        async function copyCode() {
            const statusEl = document.getElementById('status');
            try {
                await navigator.clipboard.writeText(code);
                statusEl.textContent = '✓ Code copied to clipboard!';
                statusEl.className = 'status success';
            } catch {
                const el = document.getElementById('otp-code');
                const range = document.createRange();
                range.selectNode(el);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                statusEl.textContent = 'Select the code above and copy it manually.';
                statusEl.className = 'status error';
            }
        }

        document.addEventListener('DOMContentLoaded', () => copyCode());
    </script>
</body>
</html>
