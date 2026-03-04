<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate Update</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f7f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2c304d;">
    <div style="width: 100%; padding: 20px 0;">
        <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 10px rgba(0,0,0,0.05); border: 1px solid #ededed;">
            <div style="background-color: #dc2626; padding: 15px 20px; text-align: left;">
                <h1 style="color: #ffffff; margin: 0; font-size: 18px; font-weight: 700;">{{ $tenantName }}</h1>
            </div>
            <div style="padding: 20px;">
                <div style="display: table; width: 100%; margin-bottom: 20px; background: #f9fafb; padding: 12px; border-radius: 6px;">
                    <div style="display: table-row;">
                        <div style="display: table-cell; font-weight: 600; padding: 4px 0; width: 120px; font-size: 13px; color: #667085;">Estimate Number:</div>
                        <div style="display: table-cell; padding: 4px 0; font-size: 13px; color: #2c304d;"><strong>{{ $caseNumber }}</strong></div>
                    </div>
                    @if($estimateTitle && $estimateTitle !== $caseNumber)
                    <div style="display: table-row;">
                        <div style="display: table-cell; font-weight: 600; padding: 4px 0; width: 120px; font-size: 13px; color: #667085;">Description:</div>
                        <div style="display: table-cell; padding: 4px 0; font-size: 13px; color: #2c304d;">{{ $estimateTitle }}</div>
                    </div>
                    @endif
                </div>

                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                    <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: 600; color: #991b1b;">
                        Your estimate request has been declined
                    </p>
                    <p style="margin: 0; font-size: 14px; color: #7f1d1d;">
                        Unfortunately, we are unable to proceed with this estimate at this time.
                    </p>
                </div>

                @if($rejectionReason)
                <div style="background-color: #f9fafb; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                    <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: 0.5px;">Reason Provided:</p>
                    <p style="margin: 0; font-size: 14px; color: #4b5563; line-height: 1.6; white-space: pre-wrap;">{{ $rejectionReason }}</p>
                </div>
                @endif

                <div style="font-size: 14px; line-height: 1.6; color: #4b5563; margin-bottom: 20px;">
                    <p style="margin: 0 0 12px 0;">
                        If you have any questions or would like to discuss alternative options, please don't hesitate to contact us.
                    </p>
                    @if($tenantEmail)
                    <p style="margin: 0;">
                        Email us at: <a href="mailto:{{ $tenantEmail }}" style="color: #2563eb; text-decoration: none;">{{ $tenantEmail }}</a>
                    </p>
                    @endif
                </div>

                <div style="border-top: 1px solid #f3f4f6; padding-top: 16px; margin-top: 20px;">
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">
                        Thank you for considering our services.
                    </p>
                    <p style="margin: 8px 0 0 0; font-size: 14px; font-weight: 600; color: #2c304d;">
                        {{ $tenantName }}
                    </p>
                </div>
            </div>
        </div>
        <p style="text-align: center; font-size: 12px; color: #9ca3af; margin-top: 20px;">
            This email was sent regarding estimate #{{ $caseNumber }}
        </p>
    </div>
</body>
</html>
