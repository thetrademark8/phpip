{{-- Layout email pour rappel de tâche individuelle --}}
@php app()->setLocale($language); @endphp
@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }} - {{ __('notifications.tasks.reminder_title') }}
@endcomponent
@endslot

{{-- Body --}}
{{-- Bannière d'urgence --}}
@if($urgencyLevel === 'overdue')
@component('mail::panel')
🚨 **{{ __('notifications.tasks.urgency.overdue') }}**

{{ __('notifications.tasks.messages.requires_immediate_attention') }}
@endcomponent
@elseif($urgencyLevel === 'critical')
@component('mail::panel')
🔴 **{{ __('notifications.tasks.urgency.critical') }}**

{{ __('notifications.tasks.messages.due_today_tomorrow') }}
@endcomponent
@elseif($urgencyLevel === 'urgent')
@component('mail::panel')
🟠 **{{ __('notifications.tasks.urgency.urgent') }}**

{{ __('notifications.tasks.messages.due_next_few_days') }}
@endcomponent
@endif

{{-- Task Details --}}
## {{ __('notifications.tasks.labels.task_details') }}

@component('mail::table')
| {{ __('notifications.tasks.fields.field') }} | {{ __('notifications.tasks.fields.value') }} |
|:-------------|:-------------|
| **{{ __('notifications.tasks.fields.matter') }}** | [{{ $task->matter->uid }}]({{ $phpip_url }}/matter/{{ $task->matter->id }}) @if($task->matter->alt_ref)<br><small>({{ $task->matter->alt_ref }})</small>@endif |
| **{{ __('notifications.tasks.fields.task') }}** | {{ $task->info->name ?? $task->code }} @if($task->detail)<br><small>{{ $task->detail }}</small>@endif |
| **{{ __('notifications.tasks.fields.due_date') }}** | {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }} |
@if($daysUntilDue < 0)
@php $absoluteDays = abs($daysUntilDue); @endphp
| **{{ __('notifications.tasks.fields.overdue_by') }}** | {{ trans_choice('notifications.tasks.pluralization.day', $absoluteDays) }} |
@elseif($daysUntilDue === 0)
| **{{ __('notifications.tasks.fields.status') }}** | {{ __('notifications.tasks.status.due_today') }} |
@elseif($daysUntilDue === 1)
| **{{ __('notifications.tasks.fields.status') }}** | {{ __('notifications.tasks.status.due_tomorrow') }} |
@else
| **{{ __('notifications.tasks.fields.time_remaining') }}** | {{ trans_choice('notifications.tasks.pluralization.day', $daysUntilDue) }} |
@endif
@if($task->matter->client)
| **{{ __('notifications.tasks.fields.client') }}** | {{ $task->matter->client->name }} |
@endif
@endcomponent

{{-- Action Button --}}
@component('mail::button', ['url' => $phpip_url . '/matter/' . $task->matter->id])
{{ __('View Matter') }}
@endcomponent

{{-- Context Information --}}
@if($task->matter->title || ($task->matter->classifiers && $task->matter->classifiers->count() > 0))
## {{ __('notifications.tasks.labels.matter_context') }}

@if($task->matter->title)
**{{ __('notifications.tasks.labels.title') }}:** {{ $task->matter->title }}
@endif

@if($task->matter->classifiers && $task->matter->classifiers->count() > 0)
**{{ __('notifications.tasks.labels.classifications') }}:**
@foreach($task->matter->classifiers->take(3) as $classifier)
- {{ $classifier->type_name }}: {{ $classifier->value }}
@endforeach
@endif
@endif

{{-- Next Steps --}}
@component('mail::panel')
**{{ __('notifications.tasks.labels.next_steps') }}:**

1. {{ __('notifications.tasks.actions.click_matter_link') }}
2. {{ __('notifications.tasks.actions.process_standard') }}
3. {{ __('notifications.tasks.actions.update_completion') }}
@if($urgencyLevel === 'overdue' || $urgencyLevel === 'critical')
4. {{ __('notifications.tasks.actions.contact_client') }}
@endif
@endcomponent

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ __('notifications.tasks.footer.automated_reminder') }}

[{{ __('notifications.tasks.footer.access_phpip') }}]({{ $phpip_url }}) | 
{{ __('notifications.tasks.footer.generated_on') }} {{ now()->format('d/m/Y H:i') }}
@endcomponent
@endslot
@endcomponent