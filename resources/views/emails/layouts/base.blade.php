<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $platformName ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;color:#111827;font-family:Arial,Helvetica,sans-serif;">
    <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
        {{ $preheader ?? '' }}
    </span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;margin:0;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px 18px;border-bottom:1px solid #eef0f2;">
                            <img src="{{ $logoUrl }}" alt="{{ $platformName ?? config('app.name') }}" width="150" style="display:block;max-width:150px;height:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:#111827;font-size:15px;line-height:1.65;">
                            <style>
                                .button {
                                    display: inline-block;
                                    padding: 12px 18px;
                                    border-radius: 6px;
                                    background: #111827;
                                    color: #ffffff !important;
                                    font-weight: 700;
                                    text-decoration: none;
                                }
                            </style>
                            {!! $content !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;background:#f9fafb;border-top:1px solid #eef0f2;color:#6b7280;font-size:13px;line-height:1.55;">
                            <p style="margin:0 0 8px;">Need help? Visit <a href="{{ $supportUrl }}" style="color:#111827;font-weight:700;">support</a>.</p>
                            <p style="margin:0;">Manage email preferences from <a href="{{ $preferencesUrl }}" style="color:#111827;font-weight:700;">notification settings</a>.</p>
                            @if (! empty($unsubscribeUrl))
                                <p style="margin:8px 0 0;">Marketing email? <a href="{{ $unsubscribeUrl }}" style="color:#111827;font-weight:700;">Unsubscribe</a>.</p>
                            @endif
                            <p style="margin:14px 0 0;">&copy; {{ now()->year }} {{ $platformName ?? config('app.name') }}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
