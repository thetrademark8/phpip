<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $matter->uid ?? 'Status change' }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-body {
            margin: 20px 0;
        }
        .email-body p {
            margin: 0 0 1em 0;
        }
        .status {
            font-weight: 600;
        }
        .status-old {
            color: #6b7280;
            text-decoration: line-through;
        }
        .status-new {
            color: #2563eb;
        }
        .button {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 18px;
            background-color: #2563eb;
            color: #ffffff;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-body">
            <p>The status of matter <strong>{{ $matter->uid }}</strong> has changed.</p>
            <p>
                <span class="status status-old">{{ $oldStatus }}</span>
                &rarr;
                <span class="status status-new">{{ $newStatus }}</span>
            </p>
            <p>
                <a class="button" href="{{ $phpip_url }}">View matter</a>
            </p>
        </div>
    </div>
</body>
</html>
