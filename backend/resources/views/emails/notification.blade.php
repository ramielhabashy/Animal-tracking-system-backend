<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1a1c19; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #002819; font-size: 24px; margin: 0; }
        .card { background: #ffffff; border-radius: 16px; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .button { display: inline-block; padding: 14px 36px; background: #002819; color: #D4AF37 !important; text-decoration: none; border-radius: 12px; font-weight: bold; font-size: 16px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; color: #717973; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>{{ config('app.name', 'Oasis Trace') }}</h1>
        </div>
        <div class="card">
            <p style="color: #002819; font-size: 16px;">{{ $greeting }}</p>

            @foreach ($lines as $line)
                <p style="color: #404943; margin: 12px 0;">{{ $line }}</p>
            @endforeach

            @if ($actionUrl && $actionText)
                <div style="text-align: center;">
                    <a href="{{ $actionUrl }}" class="button">{{ $actionText }}</a>
                </div>
            @endif
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Oasis Trace') }}. All rights reserved.</p>
            @if ($footerText)
                <p>{{ $footerText }}</p>
            @endif
        </div>
    </div>
</body>
</html>
