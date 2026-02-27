@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php($logoPath = \App\Models\EmailSetting::get('email_logo', config('app.company_logo')))
@if ($logoPath)
    @php
        // Ensure we have an absolute URL for email clients
        $logoUrl = str_starts_with($logoPath, 'http')
            ? $logoPath
            : asset($logoPath);
    @endphp
    <img src="{{ $logoUrl }}" class="logo" alt="{{ config('app.name') }} Logo">
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
