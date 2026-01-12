<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $matter->uid ?? 'Email' }}</title>
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
        .email-body ul, .email-body ol {
            margin: 0 0 1em 0;
            padding-left: 20px;
        }
        .email-body a {
            color: #2563eb;
            text-decoration: none;
        }
        .email-body a:hover {
            text-decoration: underline;
        }
        .email-body table {
            border-collapse: collapse;
            width: 100%;
            margin: 1em 0;
        }
        .email-body table th,
        .email-body table td {
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            text-align: left;
        }
        .email-body table th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        .email-body img {
            max-width: 100%;
            height: auto;
        }
        .email-body blockquote {
            border-left: 4px solid #e5e7eb;
            margin: 1em 0;
            padding-left: 16px;
            color: #6b7280;
        }
        .email-body h1, .email-body h2, .email-body h3 {
            margin: 1.5em 0 0.5em 0;
            line-height: 1.3;
        }
        .email-body h1 { font-size: 1.5em; }
        .email-body h2 { font-size: 1.25em; }
        .email-body h3 { font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-body">
            {!! $body !!}
        </div>
    </div>
</body>
</html>
