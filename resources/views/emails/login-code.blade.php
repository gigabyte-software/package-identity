<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your sign-in code</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" style="max-width:480px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="padding:32px 32px 0 32px;">
                            <h1 style="margin:0 0 12px 0; font-size:22px; font-weight:700; color:#1a1a1a; line-height:1.3;">
                                Your verification code
                            </h1>
                            <p style="margin:0 0 24px 0; font-size:14px; color:#555555; line-height:1.6;">
                                @if(!empty($userName))
                                    Hi {{ $userName }}, we
                                @else
                                    We
                                @endif
                                received a sign-in request for your account.
                            </p>
                        </td>
                    </tr>

                    @if($loginUrl)
                    <tr>
                        <td align="center" style="padding:0 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" style="border-radius:6px; background-color:#2563eb;">
                                        <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block; padding:14px 48px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:6px; background-color:#2563eb;">
                                            Sign in
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 0 32px;" align="center">
                            <p style="margin:0; font-size:12px; color:#999999; text-transform:uppercase; letter-spacing:1px;">or enter this code</p>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td align="center" style="padding:16px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="background-color:#f4f5f7; border:1px solid #e5e7eb; border-radius:6px; padding:10px 24px;">
                                        <span style="font-family:'Courier New', Courier, monospace; font-size:28px; font-weight:700; letter-spacing:6px; color:#1a1a1a;">
                                            {{ $code }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:8px 0 0 0; font-size:12px; color:#999999;">
                                Expires in {{ config('identity.code_expiry_minutes', 10) }} minutes
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 32px 32px 32px;">
                            <p style="margin:0; font-size:12px; color:#999999; line-height:1.5; text-align:center;">
                                If you didn't request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
