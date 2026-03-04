<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate Notification</title>
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
                        <div style="display: table-cell; font-weight: 600; padding: 4px 0; width: 120px; font-size: 13px; color: #667085;">Estimate Number:</div>
                        <div style="display: table-cell; padding: 4px 0; font-size: 13px; color: #2c304d;"><strong>{{ $caseNumber }}</strong></div>
                    </div>
                </div>
                
                <div style="font-size: 15px; line-height: 1.5; margin-bottom: 25px; white-space: pre-wrap;">{{ $body }}</div>

                @if(!empty($items))
                <div style="margin-top: 30px; margin-bottom: 30px;">
                    <h2 style="font-size: 15px; margin-bottom: 15px; color: #111827; border-bottom: 1px solid #f3f4f6; padding-bottom: 8px;">Estimate Details</h2>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="text-align: left; color: #6b7280; border-bottom: 1px solid #f3f4f6;">
                                <th style="padding: 10px 0; font-weight: 600;">Item</th>
                                <th style="padding: 10px 0; font-weight: 600; text-align: center;">Qty</th>
                                <th style="padding: 10px 0; font-weight: 600; text-align: right;">Price</th>
                                <th style="padding: 10px 0; font-weight: 600; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr style="border-bottom: 1px solid #f9fafb;">
                                <td style="padding: 12px 0; color: #374151;">{{ $item['name'] }}</td>
                                <td style="padding: 12px 0; color: #374151; text-align: center;">{{ $item['qty'] }}</td>
                                <td style="padding: 12px 0; color: #374151; text-align: right;">{{ number_format($item['unit_price'], 2) }}</td>
                                <td style="padding: 12px 0; color: #111827; text-align: right; font-weight: 500;">{{ number_format($item['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="margin-top: 15px; margin-left: auto; width: 200px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 4px 0; color: #6b7280; font-size: 13px;">Subtotal:</td>
                                <td style="padding: 4px 0; text-align: right; color: #6b7280; font-size: 13px;">{{ $currency }} {{ number_format($subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 4px 0; color: #6b7280; font-size: 13px;">Tax:</td>
                                <td style="padding: 4px 0; text-align: right; color: #6b7280; font-size: 13px;">{{ $currency }} {{ number_format($taxTotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; margin-top: 4px; border-top: 1px solid #f3f4f6; color: #111827; font-weight: 700; font-size: 14px;">Total:</td>
                                <td style="padding: 8px 0; margin-top: 4px; border-top: 1px solid #f3f4f6; text-align: right; color: #111827; font-weight: 700; font-size: 14px;">{{ $currency }} {{ number_format($grandTotal, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif
                
                @if($approveUrl || $rejectUrl)
                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                    @if($approveUrl)
                        <a href="{{ $approveUrl }}" style="display: inline-block; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; background-color: #10b981; color: #ffffff; margin-right: 8px;">Approve Estimate</a>
                    @endif
                    @if($rejectUrl)
                        <a href="{{ $rejectUrl }}" style="display: inline-block; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; background-color: #ffffff; color: #6b7280; border: 1px solid #d1d5db;">Reject</a>
                    @endif
                </div>
                @endif
            </div>
            <div style="padding: 15px 20px; text-align: center; font-size: 11px; color: #98a2b3; border-top: 1px solid #ededed;">
                &copy; {{ date('Y') }} {{ $tenantName }}.
            </div>
        </div>
    </div>
</body>
</html>
