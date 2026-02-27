@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@php($logoUrl = \App\Models\EmailSetting::logoUrl())
@if ($logoUrl)
    <img src="{{ $logoUrl }}" class="logo" alt="{{ config('app.name') }} Logo">
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
