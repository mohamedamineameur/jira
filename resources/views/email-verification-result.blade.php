<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Email verification' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.08);
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
        .expired { background: #fef3c7; color: #92400e; }

        h1 {
            margin: 0 0 12px 0;
            font-size: 28px;
        }

        p {
            margin: 0;
            line-height: 1.6;
            color: #374151;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <span class="badge {{ $status ?? 'error' }}">{{ strtoupper($status ?? 'error') }}</span>
        <h1>{{ $title ?? 'Email verification' }}</h1>
        <p>{{ $message ?? 'An unknown error occurred while verifying your email.' }}</p>
    </div>
</div>
</body>
</html>
