{{-- Layout email pour notifications de rappel de renouvellement de dossiers --}}
@php app()->setLocale($language); @endphp
@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
<img src="{{ config('app.url') . '/images/logo-email.png' }}" alt="{{ config('app.name') }}" style="height: 40px; width: auto;">
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{-- Urgency Banner based on reminder type --}}
@php
$urgencyColor = match($reminderType) {
    'first' => 'info',
    'second' => 'warning',
    'last' => 'danger',
    default => 'info'
};
$urgencyIcon = match($reminderType) {
    'first' => 'i',
    'second' => '!',
    'last' => '!!',
    default => 'i'
};
@endphp

@component('mail::panel')
@if($reminderType === 'last')
**{{ __('notifications.renewal.labels.urgent_attention') }}**
@endif
**{{ __('notifications.renewal.labels.reminder_type.' . $reminderType) }}:** {{ trans_choice('notifications.renewal.pluralization.matter_expiring', $matters->count(), ['count' => $matters->count(), 'months' => $monthsRemaining]) }}
@endcomponent

## {{ __('notifications.renewal.title.' . $reminderType) }}

{{ __('notifications.renewal.intro.' . $reminderType, ['count' => $matters->count(), 'months' => $monthsRemaining]) }}

{{-- Matters Table --}}
@component('mail::table')
| {{ __('notifications.renewal.fields.matter') }} | {{ __('notifications.renewal.fields.title') }} | {{ __('notifications.renewal.fields.country') }} | {{ __('notifications.renewal.fields.client') }} | {{ __('notifications.renewal.fields.expire_date') }} |
|:-------------|:-------------|:-------------|:-------------|:-------------|
@foreach($matters as $matter)
| [{{ $matter->uid }}]({{ $phpip_url }}/matter/{{ $matter->id }}) @if($matter->alt_ref)<br><small>({{ $matter->alt_ref }})</small>@endif | {{ $matter->titles->first()?->value ?? '-' }} | {{ $matter->countryInfo?->name ?? $matter->country }} | {{ $matter->client?->name ?? '-' }} | **{{ \Carbon\Carbon::parse($matter->expire_date)->format('d/m/Y') }}** |
@endforeach
@endcomponent

{{-- Action Recommendations --}}
@component('mail::panel', ['color' => $urgencyColor])
**{{ __('notifications.renewal.labels.recommended_actions') }}:**

@if($reminderType === 'first')
- {{ __('notifications.renewal.actions.first.review') }}
- {{ __('notifications.renewal.actions.first.contact_client') }}
- {{ __('notifications.renewal.actions.first.prepare_docs') }}
@elseif($reminderType === 'second')
- {{ __('notifications.renewal.actions.second.follow_up') }}
- {{ __('notifications.renewal.actions.second.confirm_instructions') }}
- {{ __('notifications.renewal.actions.second.check_fees') }}
@else
- {{ __('notifications.renewal.actions.last.immediate_action') }}
- {{ __('notifications.renewal.actions.last.final_confirmation') }}
- {{ __('notifications.renewal.actions.last.escalate') }}
@endif
@endcomponent

{{-- View All Link --}}
@component('mail::button', ['url' => $phpip_url . '/matter?expire_date=' . now()->addMonths($monthsRemaining)->format('Y-m'), 'color' => $reminderType === 'last' ? 'error' : 'primary'])
{{ __('notifications.renewal.buttons.view_matters') }}
@endcomponent

@include('email.partials.signature')

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ __('notifications.renewal.footer.automated_notification') }}

[{{ __('notifications.renewal.footer.access_phpip') }}]({{ $phpip_url }}) |
{{ __('notifications.renewal.footer.generated_on') }} {{ now()->format('d/m/Y H:i') }}
@endcomponent
@endslot
@endcomponent
