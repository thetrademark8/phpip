@php
    $signature = \App\Models\EmailSetting::get('email_signature', '');
    $companyLogo = \App\Models\EmailSetting::get('email_logo', config('app.company_logo'));
@endphp
@if($signature || $companyLogo)
<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
    @if($companyLogo)
    @php
        $logoUrl = str_starts_with($companyLogo, 'http')
            ? $companyLogo
            : asset($companyLogo);
    @endphp
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
