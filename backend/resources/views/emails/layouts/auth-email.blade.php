<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
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
            box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #ededed;
        }
        .header {
            background-color: {{ $headerColor ?? '#063e70' }};
            padding: 15px 20px;
            text-align: left;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .header img {
            max-height: 32px;
            width: auto;
        }
        .body {
            padding: 20px;
        }
        .greeting {
            font-size: 15px;
            margin-bottom: 16px;
            color: #2c304d;
        }
        .message {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #475467;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            background: #f9fafb;
            padding: 12px;
            border-radius: 6px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: 600;
            padding: 4px 0;
            width: 100px;
            font-size: 13px;
            color: #667085;
        }
        .info-value {
            display: table-cell;
            padding: 4px 0;
            font-size: 13px;
            color: #2c304d;
        }
        .otp-code {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 4px;
            text-align: center;
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            color: #063e70;
            font-family: 'Courier New', monospace;
        }
        .cta-container {
            text-align: left;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            background-color: {{ $buttonColor ?? '#fd6742' }};
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #063e70;
        }
        .btn-accent {
            background-color: #fd6742;
        }
        .subtext {
            font-size: 13px;
            color: #667085;
            margin-top: 16px;
        }
        .footer {
            padding: 15px 20px;
            text-align: center;
            font-size: 11px;
            color: #98a2b3;
            border-top: 1px solid #ededed;
        }
        .footer a {
            color: #98a2b3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                @isset($tenantLogoUrl)
                    <img src="{{ $tenantLogoUrl }}" alt="{{ $tenantName ?? 'RepairBuddy' }}" />
                @else
                    <h1>{{ $tenantName ?? 'RepairBuddy' }}</h1>
                @endisset
            </div>
            <div class="body">
                @isset($greeting)
                    <div class="greeting">{{ $greeting }}</div>
                @endisset

                @isset($infoGrid)
                    <div class="info-grid">
                        @foreach($infoGrid as $label => $value)
                            <div class="info-row">
                                <div class="info-label">{{ $label }}:</div>
                                <div class="info-value"><strong>{{ $value }}</strong></div>
                            </div>
                        @endforeach
                    </div>
                @endisset

                @isset($otpCode)
                    <div class="otp-code">{{ $otpCode }}</div>
                @endisset

                @isset($actionUrl)
                    <div class="cta-container">
                        <a href="{{ $actionUrl }}" class="btn {{ $buttonStyle ?? '' }}">{{ $actionText }}</a>
                    </div>
                @endisset

                @isset($subtext)
                    <div class="subtext">{{ $subtext }}</div>
                @endisset

                @yield('content')
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ $tenantName ?? 'RepairBuddy' }}.
                @isset($footerLink)
                    <a href="{{ $footerLink['url'] }}">{{ $footerLink['text'] }}</a>
                @endisset
            </div>
        </div>
    </div>
</body>
</html>
