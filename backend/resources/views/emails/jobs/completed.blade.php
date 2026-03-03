<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Repair Complete</title>
  <style>
    body { margin: 0; padding: 0; background: #f7f7f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .container { max-width: 520px; margin: 0 auto; padding: 24px 16px; }
    .card { background: #ffffff; border-radius: 6px; padding: 24px; border-left: 4px solid #16a34a; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .logo { font-size: 16px; font-weight: 700; color: #063e70; }
    .status-badge { background: #16a34a; color: #fff; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 4px; }
    .title { font-size: 18px; font-weight: 600; color: #2c304d; margin: 0 0 4px 0; }
    .subtitle { font-size: 13px; color: #667085; margin: 0 0 16px 0; }
    .case-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #ededed; }
    .case-label { color: #667085; }
    .case-value { color: #063e70; font-weight: 600; }
    .case-value.green { color: #16a34a; }
    .cost-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; font-size: 12px; margin-bottom: 12px; }
    .cost-item { display: flex; justify-content: space-between; }
    .cost-label { color: #667085; }
    .cost-value { color: #2c304d; font-weight: 500; }
    .cost-total { grid-column: span 2; display: flex; justify-content: space-between; padding-top: 8px; border-top: 2px solid #063e70; margin-top: 4px; }
    .cost-total .cost-label { color: #2c304d; font-weight: 600; }
    .cost-total .cost-value { color: #063e70; font-size: 16px; font-weight: 700; }
    .pickup { font-size: 11px; color: #667085; margin: 12px 0; padding: 10px; background: #f7f7f7; border-radius: 4px; }
    .pickup strong { color: #2c304d; }
    .warranty { font-size: 11px; color: #166534; margin: 12px 0; padding: 10px; background: #e8f5e9; border-radius: 4px; }
    .buttons { margin-top: 12px; }
    .button { display: inline-block; font-size: 12px; font-weight: 500; text-decoration: none; border-radius: 4px; padding: 8px 16px; margin-right: 8px; }
    .button-primary { background: #063e70; color: #ffffff; }
    .button-secondary { background: #fd6742; color: #ffffff; }
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
        <span class="status-badge">Complete</span>
      </div>
      <h1 class="title">Your Device is Ready</h1>
      <p class="subtitle">Hi {{ $customerName }}, your {{ $device }} {{ $service }} is done. Ready for pickup!</p>
      <div class="case-row">
        <span><span class="case-label">Case: </span><span class="case-value">{{ $caseNumber }}</span></span>
        <span><span class="case-label">Completed: </span><span class="case-value green">{{ $completedDate }}</span></span>
      </div>
      <div class="cost-grid">
        @foreach($costBreakdown as $item)
        <div class="cost-item"><span class="cost-label">{{ $item['label'] }}</span><span class="cost-value">{{ $item['value'] }}</span></div>
        @endforeach
        <div class="cost-total"><span class="cost-label">Total</span><span class="cost-value">{{ $total }}</span></div>
      </div>
      <p class="pickup"><strong>Pickup:</strong> {{ $pickupLocation }} · {{ $pickupHours }} · {{ $pickupNote }}</p>
      <p class="warranty"><strong>{{ $warrantyText }}</strong></p>
      <div class="buttons">
        <a href="{{ $invoiceUrl }}" class="button button-primary">View Invoice →</a>
        <a href="{{ $feedbackUrl }}" class="button button-secondary">Leave Feedback</a>
      </div>
    </div>
    <div class="footer">
      <p class="footer-text">© {{ date('Y') }} {{ $tenantName }} · <a href="#" class="footer-link">Unsubscribe</a></p>
    </div>
  </div>
</body>
</html>
