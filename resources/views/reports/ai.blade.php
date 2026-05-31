<!DOCTYPE html>
<html dir="{{ $direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'AI Report' }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; padding: 40px; color: #1a1c19; }
        h1 { color: #002819; border-bottom: 2px solid #D4AF37; padding-bottom: 8px; }
        h2 { color: #06402B; margin-top: 24px; }
        p { line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th, td { border: 1px solid #c0c9c1; padding: 8px 12px; text-align: left; }
        th { background: #002819; color: white; }
        .footer { margin-top: 40px; font-size: 12px; color: #717973; text-align: center; border-top: 1px solid #c0c9c1; padding-top: 12px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; background: #e8e8e3; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'AI Generated Report' }}</h1>
    <p><em>Generated on {{ now()->format('F j, Y, g:i A') }}</em></p>
    <hr>
    {!! $content !!}
    <div class="footer">Powered by Oasis Trace — AI Assisted Report</div>
</body>
</html>
