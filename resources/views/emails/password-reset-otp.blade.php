<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f7f5; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f5f7f5; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px; background-color:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.06);">
                    <tr>
                        <td align="center" style="padding:36px 24px 20px; background-color:#f8faf8;">
                            @if(!empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="AniTech" style="height:64px; width:auto; display:block; margin:0 auto 16px;">
                            @endif
                            <div style="font-size:18px; font-weight:700; color:#111827;">AniTech</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 42px 40px;">
                            <h1 style="margin:0 0 24px; font-size:24px; line-height:1.3; color:#111827;">{{ $greeting }}</h1>

                            <p style="margin:0 0 22px; font-size:16px; line-height:1.7; color:#374151;">
                                {{ $intro }}
                            </p>

                            <p style="margin:0 0 22px; font-size:16px; line-height:1.7; color:#374151;">
                                Your one-time password (OTP) is:
                                <strong style="font-size:26px; color:#111827; letter-spacing:2px;">{{ $code }}</strong>
                            </p>

                            <p style="margin:0 0 30px; font-size:16px; line-height:1.7; color:#374151;">
                                This OTP will expire in 10 minutes.
                            </p>

                            <div style="margin:0 0 34px;">
                                <a href="{{ $actionUrl }}" style="display:inline-block; padding:14px 22px; background-color:#1f1f23; color:#ffffff; text-decoration:none; border-radius:8px; font-size:16px; font-weight:700;">
                                    {{ $actionLabel }}
                                </a>
                            </div>

                            <p style="margin:0; font-size:16px; line-height:1.7; color:#374151;">
                                If you did not request a password reset, no further action is required.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
