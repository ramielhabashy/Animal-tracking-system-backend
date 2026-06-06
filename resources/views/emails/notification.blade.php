<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $platformName ?? config('app.name', 'Oasis Trace') }}</title>
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4ef; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4ef;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!-- Main Container -->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    <!-- Header / Logo -->
                    <tr>
                        <td align="center" style="padding-bottom: 32px;">
                            @if (!empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ $platformName }}" style="max-height: 64px; width: auto; display: block; margin: 0 auto;" />
                            @else
                                <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #002819; letter-spacing: -0.5px; font-family: 'Segoe UI', Roboto, sans-serif;">
                                    {{ $platformName }}
                                </h1>
                            @endif
                        </td>
                    </tr>

                    <!-- Card Body -->
                    <tr>
                        <td style="background: #ffffff; border-radius: 16px; padding: 0; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <!-- Gold accent top border -->
                                <tr>
                                    <td style="background: #D4AF37; height: 4px; border-radius: 16px 16px 0 0; font-size: 0; line-height: 0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding: 40px 40px 32px;">
                                        <!-- Greeting -->
                                        <p style="margin: 0 0 20px; font-size: 18px; font-weight: 700; color: #002819;">
                                            {{ $greeting }}
                                        </p>

                                        <!-- Body lines -->
                                        @foreach ($lines as $line)
                                            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7; color: #404943;">
                                                {{ $line }}
                                            </p>
                                        @endforeach

                                        <!-- Action Button -->
                                        @if ($actionUrl && $actionText)
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 28px 0 8px;">
                                                <tr>
                                                    <td align="center">
                                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td style="background: #002819; border-radius: 12px; text-align: center;">
                                                                    <a href="{{ $actionUrl }}" style="display: inline-block; padding: 14px 40px; font-size: 16px; font-weight: 700; color: #D4AF37; text-decoration: none; letter-spacing: 0.3px;">
                                                                        {{ $actionText }}
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 0;">
                            <p style="margin: 0 0 8px; font-size: 13px; color: #8c918d; line-height: 1.5;">
                                &copy; {{ date('Y') }} {{ $platformName }}. All rights reserved.
                            </p>
                            @if ($footerText)
                                <p style="margin: 0; font-size: 12px; color: #a0a5a1; line-height: 1.5;">
                                    {{ $footerText }}
                                </p>
                            @endif
                            <!-- Divider -->
                            <hr style="border: none; border-top: 1px solid #e0e2de; margin: 24px 0 0; width: 80px;">
                            <p style="margin: 12px 0 0; font-size: 11px; color: #b5b9b6;">
                                This is an automated message from {{ $platformName }}. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
