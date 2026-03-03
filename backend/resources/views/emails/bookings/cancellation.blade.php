<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancellation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #2c304d;
        }
        .wrapper {
            width: 100%;
            padding: 20px 0;
        }
        .content {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 10px rgba(0,0,0,0.05);
            border: 1px solid #ededed;
        }
        .header {
            background-color: #667085;
            padding: 15px 20px;
            text-align: left;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }
        .body {
            padding: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #fff1f2;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #fee2e2;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: 600;
            padding: 3px 0;
            width: 100px;
            font-size: 12px;
            color: #991b1b;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
            font-size: 12px;
            color: #b91c1c;
        }
        .message {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 20px;
            color: #475467;
        }
        .cta-container {
            text-align: left;
        }
        .btn {
            display: inline-block;
            background-color: #063e70;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
        }
        .footer {
            padding: 10px 20px;
            text-align: center;
            font-size: 10px;
            color: #98a2b3;
            border-top: 1px solid #ededed;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <h1>Booking Cancelled</h1>
            </div>
            <div class="body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Case Number:</div>
                        <div class="info-value"><strong>{{ $caseNumber ?? 'N/A' }}</strong></div>
                    </div>
                    @if(isset($customerDeviceLabel))
                    <div class="info-row">
                        <div class="info-label">Device:</div>
                        <div class="info-value">{{ $customerDeviceLabel }}</div>
                    </div>
                    @endif
                </div>
                
                <div class="message">
                    Your booking has been cancelled. If you believe this is a mistake or wish to reschedule, please view your booking details below.
                </div>
                
                @if(isset($trackingUrl))
                <div class="cta-container">
                    <a href="{{ $trackingUrl }}" class="btn">View Details</a>
                </div>
                @endif
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} RepairBuddy. <a href="#" style="color: #98a2b3;">Support</a>
            </div>
        </div>
    </div>
</body>
</html>
