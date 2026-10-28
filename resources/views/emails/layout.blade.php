<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('headline') — CineBook</title>
</head>

<body style="margin:0; padding:0; background:#f2f5fa; color:#172338; font-family:Arial, Helvetica, sans-serif;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">
        @yield('preheader')
    </span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f2f5fa;">
        <tr>
            <td align="center" style="padding:32px 14px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="width:100%; max-width:600px; border-radius:18px; overflow:hidden; background:#ffffff; box-shadow:0 12px 36px rgba(23,35,56,.10);">
                    <tr>
                        <td style="padding:22px 30px; background:#101c31;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-size:20px; font-weight:800; letter-spacing:1.6px; color:#ffffff;">
                                        CINE<span style="color:#76a9ff;">BOOK</span>
                                    </td>
                                    <td align="right" style="font-size:10px; font-weight:700; letter-spacing:1.6px; color:#aab8cf;">
                                        YOUR MOVIE NIGHT, SORTED
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 34px 30px; background:#142746;">
                            <p style="margin:0 0 10px; color:#8eb6ff; font-size:11px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase;">
                                @yield('eyebrow')
                            </p>
                            <h1 style="margin:0 0 12px; color:#ffffff; font-size:28px; line-height:1.2; letter-spacing:-.5px;">
                                @yield('headline')
                            </h1>
                            <p style="margin:0; color:#c8d4e7; font-size:15px; line-height:1.65;">
                                @yield('intro')
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px 34px; font-size:14px; line-height:1.65; color:#34445f;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 30px; background:#f5f7fb; border-top:1px solid #e3e9f2;">
                            <p style="margin:0 0 6px; color:#172338; font-size:13px; font-weight:700;">CineBook</p>
                            <p style="margin:0; color:#6b7890; font-size:12px; line-height:1.6;">
                                Need a hand? Reply to this email and our team will be happy to help.
                                <br>You're receiving this email because of activity on your CineBook account.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; color:#8692a6; font-size:11px;">© {{ date('Y') }} CineBook</p>
            </td>
        </tr>
    </table>
</body>

</html>
