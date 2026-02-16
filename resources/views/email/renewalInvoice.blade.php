<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
@php
    app()->setLocale($language);
@endphp
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $subject }}</title>
</head>
<body>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        tr:nth-child(even) {
            background-color: #dddddd;
        }
        .header {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .invoice-details {
            margin: 20px 0;
        }
    </style>
    
    <!-- Email Header -->
    <div class="header">
        <h2>{{ __('Invoice') }} {{ $invoice_number }}</h2>
    </div>
    
    <!-- Client Information -->
    <div class="invoice-details">
        <p><strong>{{ __('Client') }}:</strong> {{ $client['name'] }}</p>
        @if(isset($client['ref']))
        <p><strong>{{ __('Reference') }}:</strong> {{ $client['ref'] }}</p>
        @endif
        @if(isset($client['vat_number']))
        <p><strong>{{ __('VAT Number') }}:</strong> {{ $client['vat_number'] }}</p>
        @endif
    </div>

    <!-- Renewal Details -->
    <div class="invoice-details">
        <h3>{{ __('Renewal Details') }}</h3>
        <table class="inner-body" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th>{{ __('Case Reference') }}</th>
                    <th>{{ __('Matter Title') }}</th>
                    <th>{{ __('Due Date') }}</th>
                    <th>{{ __('Official Fee') }}</th>
                    <th>{{ __('Service Fee') }}</th>
                    <th>{{ __('Total') }} (€)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $renewal->caseref }}</td>
                    <td>{{ $renewal->matterTitle ?? '' }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($renewal->dueDate)->format('d/m/Y') }}</td>
                    <td style="text-align: right;">{{ number_format($renewal->cost ?? 0, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($renewal->fee ?? 0, 2) }}</td>
                    <td style="text-align: right;">{{ number_format(($renewal->cost ?? 0) + ($renewal->fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" style="text-align: right; font-weight: bold;">{{ __('Total Amount') }}:</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format(($renewal->cost ?? 0) + ($renewal->fee ?? 0), 2) }} €</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Payment Instructions -->
    <div class="invoice-details">
        <h3>{{ __('Payment Instructions') }}</h3>
        <p>{{ __('Please remit payment within 30 days of invoice date.') }}</p>
        <p>{{ __('Reference this invoice number when making payment') }}: <strong>{{ $invoice_number }}</strong></p>
    </div>

    <!-- Footer -->
    <p>{{ __('Thank you for your business.') }}</p>
    <p>{{ __('Best regards') }},</p>
    <p>{{ Auth::user()->name ?? config('app.name') }}</p>
    @include('email.partials.signature')
</body>
</html>