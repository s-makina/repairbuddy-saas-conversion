<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Request</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f7f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2c304d;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 10px rgba(0,0,0,0.05); border: 1px solid #ededed;">
            <div style="background-color: #063e70; padding: 15px 20px; text-align: left;">
                <h1 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 700;">{{ $tenantName }}</h1>
            </div>
            <div style="padding: 20px;">
                <div style="display: table; width: 100%; margin-bottom: 20px; background: #f9fafb; padding: 12px; border-radius: 6px;">
                    <div style="display: table-row;">
                        <div style="display: table-cell; font-weight: 600; padding: 4px 0; width: 120px; font-size: 13px; color: #667085;">Job Number:</div>
                        <div style="display: table-cell; padding: 4px 0; font-size: 13px; color: #2c304d;"><strong>{{ $caseNumber }}</strong></div>
                    </div>
                    @if($signatureLabel)
                    <div style="display: table-row;">
                        <div style="display: table-cell; font-weight: 600; padding: 4px 0; width: 120px; font-size: 13px; color: #667085;">Document:</div>
                        <div style="display: table-cell; padding: 4px 0; font-size: 13px; color: #2c304d;">{{ $signatureLabel }}</div>
                    </div>
                    @endif
                </div>
                
                <div style="font-size: 15px; line-height: 1.5; margin-bottom: 25px; white-space: pre-wrap;">{{ $body }}</div>
                
                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; text-align: center;">
                    <a href="{{ $signatureUrl }}" style="display: inline-block; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; background-color: #063e70; color: #ffffff;">Sign Document Now</a>
                    <p style="font-size: 12px; color: #667085; margin-top: 15px;">This link will expire in 7 days.</p>
                </div>
            </div>
            <div style="padding: 15px 20px; text-align: center; font-size: 11px; color: #98a2b3; border-top: 1px solid #ededed;">
                &copy; {{ date('Y') }} {{ $tenantName }}.
            </div>
        </div>
    </div>
</body>
</html>
