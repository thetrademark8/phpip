{{-- Layout email pour récapitulatif des tâches urgentes --}}
@php app()->setLocale($language); @endphp
@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
<img src="{{ config('app.url') . '/images/logo-email.png' }}" alt="{{ config('app.name') }}" style="height: 40px; width: auto;">
{{ config('app.name') }} - {{ __('notifications.tasks.summary_title') }}
@endcomponent
@endslot

{{-- Body --}}
@if($totalTasks > 0)
{{-- Summary Box --}}
@component('mail::panel')
**{{ __('notifications.tasks.urgent_summary_title') }}:**

@if($overdueTasks->count() > 0)
🚨 {{ trans_choice('notifications.tasks.pluralization.overdue_task', $overdueTasks->count()) }}
@endif

@if($dueSoonTasks->count() > 0)
@if($overdueTasks->count() > 0) • @endif
⏰ {{ trans_choice('notifications.tasks.pluralization.due_soon_task', $dueSoonTasks->count()) }}
@endif

**{{ __('notifications.tasks.labels.total') }}:** {{ trans_choice('notifications.tasks.pluralization.task', $totalTasks) }}
@endcomponent

{{-- Overdue Tasks Section --}}
@if($overdueTasks->count() > 0)
## 🚨 {{ __('notifications.tasks.overdue_title') }} ({{ $overdueTasks->count() }})

@component('mail::table')
| {{ __('notifications.tasks.fields.matter') }} | {{ __('notifications.tasks.fields.category') }} | {{ __('notifications.tasks.fields.client') }} | {{ __('notifications.tasks.fields.task') }} | {{ __('notifications.tasks.fields.due_date') }} | {{ __('notifications.tasks.fields.responsible') }} |
|:-------------|:-------------|:-------------|:-------------|:-------------|:-------------|
@foreach($overdueTasks as $task)
| [{{ $task->matter->uid }}]({{ $phpip_url }}/matter/{{ $task->matter->id }}) | {{ $task->matter->category_code }} | {{ $task->matter->client->name ?? 'N/A' }} | **{{ $task->info->name ?? $task->code }}** @if($task->detail)<br><small>{{ $task->detail }}</small>@endif | {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }} | {{ $task->matter->responsible }} |
@endforeach
@endcomponent
@endif

{{-- Due Soon Tasks Section --}}
@if($dueSoonTasks->count() > 0)
## ⏰ {{ __('notifications.tasks.due_soon_title') }} ({{ $dueSoonTasks->count() }})

@component('mail::table')
| {{ __('notifications.tasks.fields.matter') }} | {{ __('notifications.tasks.fields.category') }} | {{ __('notifications.tasks.fields.client') }} | {{ __('notifications.tasks.fields.task') }} | {{ __('notifications.tasks.fields.due_date') }} | {{ __('notifications.tasks.fields.responsible') }} |
|:-------------|:-------------|:-------------|:-------------|:-------------|:-------------|
@foreach($dueSoonTasks->take(15) as $task)
| [{{ $task->matter->uid }}]({{ $phpip_url }}/matter/{{ $task->matter->id }}) | {{ $task->matter->category_code }} | {{ $task->matter->client->name ?? 'N/A' }} | **{{ $task->info->name ?? $task->code }}** @if($task->detail)<br><small>{{ $task->detail }}</small>@endif | {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }} | {{ $task->matter->responsible }} |
@endforeach
@if($dueSoonTasks->count() > 15)
| ... | ... | ... | *{{ trans_choice('notifications.tasks.pluralization.more_tasks', $dueSoonTasks->count() - 15) }}* | ... | ... |
@endif
@endcomponent
@endif

{{-- Statistics by Responsible --}}
@if($totalTasks > 0)
## 📊 {{ __('notifications.tasks.labels.breakdown_by_responsible') }}

@php
$tasksByResponsible = $tasks->groupBy('matter.responsible')->map(function($tasks, $responsible) {
    return [
        'responsible' => $responsible,
        'total' => $tasks->count(),
        'overdue' => $tasks->filter(fn($task) => $task->due_date < now())->count(),
        'due_soon' => $tasks->filter(fn($task) => $task->due_date >= now() && $task->due_date <= now()->addDays(7))->count(),
    ];
})->sortByDesc('overdue');
@endphp

@component('mail::table')
| {{ __('notifications.tasks.fields.responsible') }} | {{ __('notifications.tasks.labels.total') }} | {{ __('notifications.tasks.labels.overdue') }} | {{ __('notifications.tasks.labels.due_soon') }} |
|:-------------|:-------------|:-------------|:-------------|
@foreach($tasksByResponsible as $stats)
| **{{ $stats['responsible'] }}** | {{ $stats['total'] }} | @if($stats['overdue'] > 0) **{{ $stats['overdue'] }}** 🚨 @else 0 @endif | @if($stats['due_soon'] > 0) **{{ $stats['due_soon'] }}** ⏰ @else 0 @endif |
@endforeach
@endcomponent
@endif

{{-- Action Items --}}
@component('mail::panel', ['color' => 'info'])
**{{ __('notifications.tasks.labels.recommended_actions') }}:**

@if($overdueTasks->count() > 0)
- {{ __('notifications.tasks.actions.verify_notifications') }}
- {{ __('notifications.tasks.actions.follow_up_critical') }}
@endif
- {{ __('notifications.tasks.actions.monitor_workloads') }}
- {{ __('notifications.tasks.actions.click_references') }}
@endcomponent

@else
{{-- No urgent tasks --}}
@component('mail::panel', ['color' => 'success'])
✅ {{ __('notifications.tasks.messages.no_urgent_tasks') }}

{{ __('notifications.tasks.messages.all_tasks_current') }}
@endcomponent
@endif

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ __('notifications.tasks.footer.system_summary') }}

[{{ __('notifications.tasks.footer.access_phpip') }}]({{ $phpip_url }}) | 
{{ __('notifications.tasks.footer.generated_on') }} {{ now()->format('d/m/Y H:i') }}

---

{{ __('notifications.tasks.footer.note_individual') }}
@endcomponent
@endslot
@endcomponent