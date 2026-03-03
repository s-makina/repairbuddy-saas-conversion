<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Confirmation</title>
  <style>
    body { margin: 0; padding: 0; background: #f7f7f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .container { max-width: 520px; margin: 0 auto; padding: 24px 16px; }
    .card { background: #ffffff; border-radius: 6px; padding: 24px; border-left: 4px solid #063e70; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .logo { font-size: 16px; font-weight: 700; color: #063e70; }
    .case-badge { background: #063e70; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 4px; }
    .body-content { font-size: 14px; color: #2c304d; line-height: 1.6; }
    .body-content a { color: #063e70; text-decoration: underline; }
    .body-content p { margin: 0 0 12px 0; }
    .button { display: inline-block; background: #fd6742; color: #ffffff; font-size: 12px; font-weight: 500; text-decoration: none; border-radius: 4px; padding: 8px 16px; margin-top: 12px; }
    .footer { text-align: center; padding: 16px 0; }
    .footer-text { font-size: 11px; color: #98a2b3; margin: 0; }
    .footer-link { color: #667085; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="header">
        <span class="logo">{{ $tenantName }}</span>
        <span class="case-badge">{{ $caseNumber }}</span>
      </div>
      <div class="body-content">
        {!! $renderedBody !!}
      </div>
      <a href="{{ $trackingUrl }}" class="button">Track Your Repair →</a>
    </div>
    <div class="footer">
      <p class="footer-text">© {{ date('Y') }} {{ $tenantName }} · <a href="#" class="footer-link">Unsubscribe</a></p>
    </div>
  </div>
</body>
</html>
