<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate Notification</title>
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
            background-color: #063e70;
            padding: 15px 20px;
            text-align: left;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .body {
            padding: 20px;
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
            width: 120px;
            font-size: 13px;
            color: #667085;
        }
        .info-value {
            display: table-cell;
            padding: 4px 0;
            font-size: 13px;
            color: #2c304d;
        }
        .dynamic-content {
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 25px;
            white-space: pre-wrap;
        }
        .actions {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            display: flex;
            gap: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
        }
        .btn-approve {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-reject {
            background-color: #ffffff;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        .footer {
            padding: 15px 20px;
            text-align: center;
            font-size: 11px;
            color: #98a2b3;
            border-top: 1px solid #ededed;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            <div class="header">
                <h1>{{ $tenantName }}</h1>
            </div>
            <div class="body">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Estimate Number:</div>
                        <div class="info-value"><strong>{{ $caseNumber }}</strong></div>
                    </div>
                </div>
                
                <div class="dynamic-content">{{ $body }}</div>
                
                @if($approveUrl || $rejectUrl)
                <div class="actions">
                    @if($approveUrl)
                        <a href="{{ $approveUrl }}" class="btn btn-approve">Approve Estimate</a>
                    @endif
                    @if($rejectUrl)
                        <a href="{{ $rejectUrl }}" class="btn btn-reject">Reject</a>
                    @endif
                </div>
                @endif
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ $tenantName }}.
            </div>
        </div>
    </div>
</body>
</html>
