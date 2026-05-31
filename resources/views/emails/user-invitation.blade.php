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
        .details { background: #f4f4ef; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .details dt { font-weight: bold; color: #002819; margin-top: 10px; }
        .details dt:first-child { margin-top: 0; }
        .details dd { margin: 4px 0 0 0; color: #404943; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🐪 Oasis Trace</h1>
        </div>
        <div class="card">
            <h2 style="color: #002819; margin-top: 0;">You're Invited!</h2>
            <p>You have been invited to join <strong>Oasis Trace</strong> — the livestock tracking platform.</p>

            <div class="details">
                <dl>
                    <dt>Role</dt>
                    <dd>{{ $role }}</dd>
                    <dt>Email</dt>
                    <dd>{{ $invitation->email }}</dd>
                </dl>
            </div>

            <p>Click the button below to accept the invitation and set up your account:</p>

            <div style="text-align: center;">
                <a href="{{ $acceptUrl }}" class="button">Accept Invitation</a>
            </div>

            <p style="color: #717973; font-size: 14px; margin-top: 30px;">
                This invitation link will expire in {{ $invitation->expires_at->diffForHumans() }}.
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Oasis Trace. All rights reserved.</p>
            <p>If you did not expect this invitation, you can safely ignore this email.</p>
        </div>
    </div>
</body>
</html>
