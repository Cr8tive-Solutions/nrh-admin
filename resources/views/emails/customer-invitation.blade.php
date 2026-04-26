<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate your account — NRH Intelligence</title>
</head>
<body style="margin:0; padding:0; background:#f4f4f1; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,sans-serif; color:#1a1a1a; line-height:1.5;">

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f4f1; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px; background:#ffffff; border:1px solid #e5e5e0; border-radius:12px; overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td style="padding:32px 36px 24px; border-bottom:1px solid #f0f0eb;">
                        <div style="font-family:'Times New Roman',Georgia,serif; font-size:18px; font-weight:600; color:#0d4a36; letter-spacing:-0.005em;">
                            NRH <em style="color:#a8842f;">Intelligence</em>
                        </div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:32px 36px;">
                        <h1 style="font-family:'Times New Roman',Georgia,serif; font-size:26px; font-weight:500; color:#1a1a1a; margin:0 0 8px; letter-spacing:-0.015em; line-height:1.2;">
                            Welcome, {{ $user->name }}.
                        </h1>
                        <p style="font-size:14px; color:#666; margin:0 0 20px; line-height:1.55;">
                            An account has been created for you at <strong style="color:#1a1a1a;">{{ $customer->name }}</strong> on the NRH Intelligence client portal.
                        </p>

                        <p style="font-size:14px; color:#1a1a1a; margin:0 0 8px;">To activate your account and set a password, click the button below:</p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                            <tr>
                                <td style="background:#0d4a36; border-radius:8px; box-shadow: inset 0 0 0 1px rgba(212,175,55,0.3);">
                                    <a href="{{ $invitationUrl }}"
                                       style="display:inline-block; padding:13px 26px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600;">
                                        Activate my account →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:12px; color:#999; margin:0 0 16px;">
                            Or copy and paste this link into your browser:
                        </p>
                        <p style="font-size:12px; color:#0d4a36; margin:0 0 24px; word-break:break-all; font-family:'Courier New',monospace; background:#f7f7f4; padding:10px 12px; border-radius:6px; border:1px solid #ebebe5;">
                            {{ $invitationUrl }}
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #f0f0eb; padding-top:18px; margin-top:8px;">
                            <tr>
                                <td style="font-size:12px; color:#888; line-height:1.5;">
                                    <strong style="color:#666;">Security info</strong><br>
                                    This link expires on <strong>{{ $expiresAt->format('d M Y, H:i') }}</strong> ({{ $expiresAt->diffForHumans() }}).
                                    If you didn't expect this invitation or weren't expecting an account at <strong>{{ $customer->name }}</strong>, you can safely ignore this email — the link will expire automatically.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:18px 36px 28px; background:#fafaf7; border-top:1px solid #f0f0eb; font-size:11px; color:#999;">
                        Sent by NRH Intelligence Sdn. Bhd. — internal staff portal at NRH Admin.<br>
                        For account help, contact your administrator at <strong style="color:#666;">{{ $customer->name }}</strong>.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
