@php
    $signature = \App\Models\EmailSetting::get('email_signature', '');
    $logoUrl = \App\Models\EmailSetting::logoUrl();
@endphp
@if($signature || $logoUrl)
<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
    @if($logoUrl)
    <div style="margin-bottom: 10px;">
        <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" style="max-height: 60px; width: auto;">
    </div>
    @endif
    @if($signature)
    <div style="font-size: 13px; color: #555555; line-height: 1.5;">
        {!! $signature !!}
    </div>
    @endif
</div>
@endif
