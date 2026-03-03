<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking Confirmation</title>
</head>
<body style="margin: 0; padding: 0; background: #f7f7f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
  <div style="max-width: 520px; margin: 0 auto; padding: 24px 16px;">
    <div style="background: #ffffff; border-radius: 6px; padding: 24px; border-left: 4px solid #063e70;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
        <span style="font-size: 16px; font-weight: 700; color: #063e70;">{{ $tenantName }}</span>
        <span style="background: #063e70; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 4px;">{{ $caseNumber }}</span>
      </div>
      <div style="font-size: 14px; color: #2c304d; line-height: 1.6;">
        {!! $renderedBody !!}
      </div>
      <a href="{{ $trackingUrl }}" style="display: inline-block; background: #fd6742; color: #ffffff; font-size: 12px; font-weight: 500; text-decoration: none; border-radius: 4px; padding: 8px 16px; margin-top: 12px;">Track Your Repair →</a>
    </div>
    <div style="text-align: center; padding: 16px 0;">
      <p style="font-size: 11px; color: #98a2b3; margin: 0;">© {{ date('Y') }} {{ $tenantName }} · <a href="#" style="color: #667085; text-decoration: none;">Unsubscribe</a></p>
    </div>
  </div>
</body>
</html>
