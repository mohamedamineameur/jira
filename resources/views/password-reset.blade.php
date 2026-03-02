<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Password reset' }}</title>
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1f1c2c, #928dab);
            color: white;
            min-height: 100vh;
        }

        .modern-nav {
            position: sticky;
            top: 0;
            backdrop-filter: blur(12px);
            background: rgba(255,255,255,0.08);
            padding: 15px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modern-nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
            margin: 0;
            padding: 0;
        }

        .modern-nav a {
            text-decoration: none;
            color: white;
            transition: 0.3s;
        }

        .modern-nav a:hover {
            color: #0Request URL
http://localhost:8001/api/organizations?per_page=50
Request Method
GET
Status Code
403 Forbidden
Remote Address
127.0.0.1:8001
Referrer Policy
strict-origin-when-cross-origin
0f5ff;
        }

        .hero {
            padding: 72px 8% 40px;
            text-align: center;
        }
        Request URL
http://localhost:8001/api/organizations?per_page=50
Request Method
GET
Status Code
403 Forbidden
Remote Address
127.0.0.1:8001
Referrer Policy
strict-origin-when-cross-origin

        .hero h2 {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .hero p {
            opacity: 0.85;
            margin-bottom: 24px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
            gap: 30px;
            padding: 0 8% 60px;
            max-width: 980px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(15px);
            padding: 40px 30px;
            border-radius: 20px;
            transition: 0.4s;
        }

        .card:hover {
            transform: translateY(-10px);
            background: rgba(255,255,255,0.12);
        }

        .input {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            background: rgba(255,255,255,0.08);
            color: white;
            font-size: 15px;
            padding: 12px 14px;
            margin-top: 6px;
            margin-bottom: 16px;
            box-sizing: border-box;
        }

        .label {
            display: block;
            font-size: 14px;
            opacity: 0.9;
        }

        .btn {
            padding: 15px 35px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            background: linear-gradient(90deg, #00f5ff, #ff00c8);
            color: white;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }

        .status {
            display: inline-block;
            margin-bottom: 16px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            background: rgba(255,255,255,0.15);
        }

        .modern-footer {
            text-align: center;
            padding: 30px;
            background: rgba(0,0,0,0.2);
            margin-top: 50px;
        }

        @media(max-width: 768px){
            .hero h2 {
                font-size: 2.2rem;
            }
            .modern-nav ul {
                display: none;
            }
        }
    </style>
</head>
<body>
<nav class="modern-nav">
    <strong>Agilify</strong>
    <ul>
        <li><a href="#">Security</a></li>
        <li><a href="#">Support</a></li>
    </ul>
</nav>

<section class="hero">
    <span class="status">{{ strtoupper($status ?? 'error') }}</span>
    <h2>{{ $title ?? 'Password reset' }}</h2>
    <p>{{ $message ?? 'Unable to process password reset.' }}</p>
</section>

<section class="cards">
    @if(($status ?? '') === 'form')
        <div class="card">
            <h3 style="margin-top: 0;">Choose a new password</h3>
            <form method="POST" action="{{ route('password.reset.submit', ['user' => $user]) }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label class="label" for="password">New password</label>
                <input class="input" id="password" name="password" type="password" required>

                <label class="label" for="password_confirmation">Confirm password</label>
                <input class="input" id="password_confirmation" name="password_confirmation" type="password" required>

                <button class="btn" type="submit">Reset Password</button>
            </form>
        </div>
    @else
        <div class="card">
            <h3 style="margin-top: 0;">What happened?</h3>
            <p>{{ $message ?? 'Unable to process password reset.' }}</p>
        </div>
    @endif
</section>

<footer class="modern-footer">
    Agilify
</footer>
</body>
</html>
