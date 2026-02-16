{{-- Layout email pour notifications de tâches urgentes --}}
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
@if($totalTasks > 0)
{{-- Summary Box --}}
@component('mail::panel')
**{{ __('notifications.tasks.labels.summary') }}:**
@if($overdueTasks->count() > 0)
🚨 {{ trans_choice('notifications.tasks.pluralization.overdue_task', $overdueTasks->count()) }}
@endif
@if($dueSoonTasks->count() > 0)
@if($overdueTasks->count() > 0) • @endif
⏰ {{ trans_choice('notifications.tasks.pluralization.due_soon_task', $dueSoonTasks->count()) }}
@endif
@endcomponent

{{-- Overdue Tasks --}}
@if($overdueTasks->count() > 0)
## 🚨 {{ __('notifications.tasks.overdue_title') }} ({{ $overdueTasks->count() }})

@component('mail::table')
| {{ __('notifications.tasks.fields.matter') }} | {{ __('notifications.tasks.fields.task') }} | {{ __('notifications.tasks.fields.due_date') }} | {{ __('notifications.tasks.fields.overdue_by') }} |
|:-------------|:-------------|:-------------|:-------------|
@foreach($overdueTasks as $task)
@php
$daysOverdue = (int) round(now()->diffInDays($task->due_date, false));
@endphp
| [{{ $task->matter->uid }}]({{ $phpip_url }}/matter/{{ $task->matter->id }}) @if($task->matter->alt_ref)<br><small>({{ $task->matter->alt_ref }})</small>@endif | **{{ $task->info->name ?? $task->code }}** @if($task->detail)<br><small>{{ $task->detail }}</small>@endif | {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }} | **{{ abs($daysOverdue) }} {{ trans_choice('notifications.tasks.pluralization.day', abs($daysOverdue)) }}** |
@endforeach
@endcomponent
@endif

{{-- Due Soon Tasks --}}
@if($dueSoonTasks->count() > 0)
## ⏰ {{ __('notifications.tasks.due_soon_title') }} ({{ $dueSoonTasks->count() }})

@component('mail::table')
| {{ __('notifications.tasks.fields.matter') }} | {{ __('notifications.tasks.fields.task') }} | {{ __('notifications.tasks.fields.due_date') }} | {{ __('notifications.tasks.status.days_remaining') }} |
|:-------------|:-------------|:-------------|:-------------|
@foreach($dueSoonTasks as $task)
@php
$daysRemaining = (int) round(now()->diffInDays($task->due_date, false));
@endphp
| [{{ $task->matter->uid }}]({{ $phpip_url }}/matter/{{ $task->matter->id }}) @if($task->matter->alt_ref)<br><small>({{ $task->matter->alt_ref }})</small>@endif | **{{ $task->info->name ?? $task->code }}** @if($task->detail)<br><small>{{ $task->detail }}</small>@endif | {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }} | **{{ $daysRemaining }} {{ trans_choice('notifications.tasks.pluralization.day', $daysRemaining) }}** |
@endforeach
@endcomponent
@endif

{{-- Action Items --}}
@component('mail::panel', ['color' => 'info'])
**{{ __('notifications.tasks.labels.recommended_actions') }}:**

@if($overdueTasks->count() > 0)
- {{ __('notifications.tasks.actions.process_overdue') }}
@endif
@if($dueSoonTasks->count() > 0)
- {{ __('notifications.tasks.actions.schedule_time') }}
@endif
- {{ __('notifications.tasks.actions.access_details') }}
- {{ __('notifications.tasks.actions.update_status') }}
@endcomponent

@else
{{-- No urgent tasks --}}
@component('mail::panel', ['color' => 'success'])
✅ {{ __('notifications.tasks.messages.perfect_no_urgent') }}
@endcomponent
@endif

@include('email.partials.signature')

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ __('notifications.tasks.footer.automated_notification') }}

[{{ __('notifications.tasks.footer.access_phpip') }}]({{ $phpip_url }}) |
{{ __('notifications.tasks.footer.generated_on') }} {{ now()->format('d/m/Y H:i') }}
@endcomponent
@endslot
@endcomponent