<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $heroTitle ?? 'Notification' }}</title>
    <style>
        body, table, td, p, a {
            font-family: Poppins, Arial, Helvetica, sans-serif;
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#1f1c2c; background-image:linear-gradient(135deg, #1f1c2c, #928dab); color:#ffffff;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#1f1c2c; background-image:linear-gradient(135deg, #1f1c2c, #928dab);">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="width:100%; max-width:640px;">
                <tr>
                    <td style="background:rgba(255,255,255,0.08); padding:14px 20px; border-radius:14px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="font-size:18px; font-weight:700; color:#ffffff;">
                                    {{ 'Agilify' }}
                                </td>
                                <td align="right" style="font-size:13px; color:#ffffff;">
                                    @if(!empty($navItems))
                                        @foreach($navItems as $item)
                                            <a href="{{ $item['url'] ?? '#' }}" style="color:#ffffff; text-decoration:none;">{{ $item['label'] ?? 'Link' }}</a>@if(!$loop->last) <span style="color:#d1d5db;">|</span> @endif
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td height="16" style="line-height:16px; font-size:16px;">&nbsp;</td>
                </tr>

                <tr>
                    <td align="center" style="padding:22px 16px 10px 16px;">
                        <h2 style="margin:0 0 16px 0; font-size:38px; line-height:1.2; color:#ffffff;">
                            {{ $heroTitle ?? 'Hello!' }}
                        </h2>
                        @if(!empty($heroText))
                            <p style="margin:0 0 28px 0; font-size:16px; line-height:1.5; color:#f3f4f6;">
                                {{ $heroText }}
                            </p>
                        @endif
                        @if(!empty($otpCode))
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:420px; margin:0 auto 16px auto;">
                                <tr>
                                    <td align="center" style="background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.24); border-radius:18px; padding:18px 12px;">
                                        <p style="margin:0 0 8px 0; font-size:12px; letter-spacing:0.12em; text-transform:uppercase; color:#d1d5db;">One-Time Password</p>
                                        <p style="margin:0; font-size:44px; line-height:1; letter-spacing:0.16em; font-weight:800; color:#ffffff;">
                                            {{ $otpCode }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 12px auto;">
                                <tr>
                                    <td align="center" style="border-radius:999px; background:#7c3aed; background-image:linear-gradient(90deg, #00f5ff, #ff00c8);">
                                        <span style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:700; color:#ffffff;">
                                            {{ $otpCopyText ?? 'Copy code' }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 20px 0; font-size:13px; color:#e5e7eb;">
                                {{ $otpCopyHint ?? 'Tip: press and hold the code to copy it.' }}
                            </p>
                        @endif
                        @if(!empty($buttonText) && !empty($buttonUrl))
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="border-radius:999px; background:#7c3aed; background-image:linear-gradient(90deg, #00f5ff, #ff00c8);">
                                        <a href="{{ $buttonUrl }}" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            {{ $buttonText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>

                @if(!empty($cards))
                    <tr>
                        <td style="padding:18px 8px 6px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                @foreach($cards as $card)
                                    <tr>
                                        <td style="background:rgba(255,255,255,0.10); border-radius:16px; padding:22px 20px;">
                                            <h3 style="margin:0 0 10px 0; font-size:20px; color:#ffffff;">
                                                {{ $card['title'] ?? 'Info' }}
                                            </h3>
                                            <p style="margin:0; font-size:15px; line-height:1.5; color:#e5e7eb;">
                                                {{ $card['text'] ?? '' }}
                                            </p>
                                        </td>
                                    </tr>
                                    @if(!$loop->last)
                                        <tr>
                                            <td height="14" style="line-height:14px; font-size:14px;">&nbsp;</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td height="16" style="line-height:16px; font-size:16px;">&nbsp;</td>
                </tr>

                <tr>
                    <td align="center" style="background:rgba(0,0,0,0.25); border-radius:14px; padding:20px; font-size:13px; color:#e5e7eb;">
                        {{ $footerText ?? 'Agilify' }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
