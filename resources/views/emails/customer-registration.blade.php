{{--
    Shared layout for the invite-to-register emails (invite / approved / rejected).
    Vars: $title, $heading, $paragraphs (array of HTML-safe strings — escape user
    input before passing), $buttonUrl (nullable), $buttonLabel, $note (nullable).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — NRH Intelligence</title>
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
                        <h1 style="font-family:'Times New Roman',Georgia,serif; font-size:26px; font-weight:500; color:#1a1a1a; margin:0 0 16px; letter-spacing:-0.015em; line-height:1.2;">
                            {{ $heading }}
                        </h1>

                        @foreach($paragraphs as $paragraph)
                        <p style="font-size:14px; color:#444; margin:0 0 14px; line-height:1.6;">{!! $paragraph !!}</p>
                        @endforeach

                        @if($buttonUrl)
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                            <tr>
                                <td style="background:#0d4a36; border-radius:8px; box-shadow: inset 0 0 0 1px rgba(212,175,55,0.3);">
                                    <a href="{{ $buttonUrl }}"
                                       style="display:inline-block; padding:13px 26px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:600;">
                                        {{ $buttonLabel }} →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:12px; color:#999; margin:0 0 16px;">
                            Or copy and paste this link into your browser:
                        </p>
                        <p style="font-size:12px; color:#0d4a36; margin:0 0 24px; word-break:break-all; font-family:'Courier New',monospace; background:#f7f7f4; padding:10px 12px; border-radius:6px; border:1px solid #ebebe5;">
                            {{ $buttonUrl }}
                        </p>
                        @endif

                        @if($note)
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-top:1px solid #f0f0eb; padding-top:18px; margin-top:8px;">
                            <tr>
                                <td style="font-size:12px; color:#888; line-height:1.5;">{!! $note !!}</td>
                            </tr>
                        </table>
                        @endif
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:18px 36px 28px; background:#fafaf7; border-top:1px solid #f0f0eb; font-size:11px; color:#999;">
                        Sent by NRH Intelligence Sdn. Bhd.<br>
                        Questions? Reply to this email or contact <strong style="color:#666;">hello@nrhintelligence.com</strong>.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
