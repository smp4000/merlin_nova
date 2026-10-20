<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $mailTitle ?? 'Merlin Nova · E-Mail-Adresse bestätigen' }}</title>
    <style>
        body { margin: 0; padding: 0; }
        table { border-collapse: collapse; }
        a:focus-visible { outline: 3px solid #007e8c; outline-offset: 4px; }
        @media only screen and (max-width: 600px) {
            .email-outer { padding: 20px 12px !important; }
            .email-content { padding: 28px 22px !important; }
            .email-title { font-size: 26px !important; line-height: 34px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#eff7fa;color:#101a42;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{{ $preheader ?? 'Bestätigen Sie Ihre E-Mail-Adresse. Ihr kostenloser Test startet erst danach.' }}</div>
    {{-- Tabellen und Inlinefarben tragen das Layout auch ohne Media Queries.
         Die Outlook-Hülle begrenzt die Breite ohne Unterstützung von max-width. --}}
    <table role="presentation" width="100%" style="width:100%;background-color:#eff7fa;">
        <tr><td class="email-outer" align="center" style="padding:40px 20px;">
            <!--[if mso]><table role="presentation" width="600" align="center"><tr><td><![endif]-->
            <table role="presentation" width="100%" style="width:100%;max-width:600px;table-layout:fixed;">
                <tr><td style="padding:0 0 24px;">
                    <table role="presentation"><tr>
                        <td width="40" height="40" align="center" valign="middle" bgcolor="#007e8c" style="width:40px;height:40px;background-color:#007e8c;border-radius:7px;color:#ffffff;font-size:26px;font-weight:bold;" aria-hidden="true">M</td>
                        <td style="padding-left:12px;color:#101a42;font-size:23px;line-height:30px;font-weight:bold;">Merlin Nova</td>
                    </tr></table>
                </td></tr>
                <tr><td height="5" bgcolor="#007e8c" style="height:5px;background-color:#007e8c;font-size:1px;line-height:5px;">&nbsp;</td></tr>
                <tr><td class="email-content" bgcolor="#ffffff" style="padding:36px 36px 32px;background-color:#ffffff;border:1px solid #dce5ed;border-top:0;">
                    <p style="margin:0 0 14px;color:#006571;font-size:12px;line-height:18px;letter-spacing:1px;font-weight:bold;">{{ $eyebrow ?? 'IHR START MIT MERLIN NOVA' }}</p>
                    <h1 class="email-title" style="margin:0 0 20px;color:#101a42;font-size:30px;line-height:38px;font-weight:bold;">{{ $greeting }}</h1>
                    @foreach ($introLines as $line)<p style="margin:0 0 24px;color:#354963;font-size:16px;line-height:26px;">{{ $line }}</p>@endforeach
                    <table role="presentation" style="margin:0 0 26px;max-width:100%;"><tr>
                        <td bgcolor="#007e8c" align="center" style="background-color:#007e8c;border-radius:6px;mso-padding-alt:15px 22px;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:15px 22px;mso-padding-alt:0;color:#ffffff;text-decoration:none;font-size:16px;line-height:22px;font-weight:bold;">{{ $actionText }}</a>
                        </td>
                    </tr></table>
                    <table role="presentation" width="100%" style="width:100%;background-color:#eff7fa;border-left:3px solid #007e8c;"><tr><td style="padding:16px 18px;">
                        <p style="margin:0;color:#354963;font-size:14px;line-height:23px;">{{ $outroLines[0] }}</p>
                    </td></tr></table>
                    <p style="margin:24px 0 18px;color:#596a89;font-size:14px;line-height:23px;">{{ $outroLines[1] }}</p>
                    <p style="margin:0;color:#354963;font-size:15px;line-height:24px;">Viele Grüße<br><strong style="color:#101a42;">Ihr Merlin-Nova-Team</strong></p>
                    <table role="presentation" width="100%" style="width:100%;table-layout:fixed;margin-top:28px;border-top:1px solid #dce5ed;"><tr><td style="padding-top:20px;">
                        <p style="margin:0 0 10px;color:#596a89;font-size:12px;line-height:20px;">Falls die Schaltfläche nicht funktioniert, kopieren Sie diesen vollständigen Link in die Adresszeile Ihres Browsers:</p>
                        <a href="{{ $actionUrl }}" style="color:#006571;text-decoration:underline;font-size:12px;line-height:20px;word-break:break-all;overflow-wrap:anywhere;">{{ $actionUrl }}</a>
                    </td></tr></table>
                </td></tr>
                <tr><td align="center" style="padding:22px 12px 0;color:#596a89;font-size:12px;line-height:20px;">Merlin Nova · Ihre Stationen. Ihr Team. Alles im Blick.</td></tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td></tr>
    </table>
</body>
</html>
