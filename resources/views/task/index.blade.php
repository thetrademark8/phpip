{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<table class="table table-striped table-sm mb-1" style="width: 100%; table-layout: fixed;">
  @foreach ($tasks as $task)
  <tr class="row g-0">
    <td class="col text-truncate py-0">
      <a href="/matter/{{ $task->matter->id }}/{{ $isrenewals ? 'renewals' : 'tasks' }}" data-bs-toggle="modal" data-bs-target="#ajaxModal" data-size="modal-lg" data-resource="/task/" title="{{ __('All tasks') }}">
        {{ $task->info->name }} {{ $task->detail }}
      </a>
    </td>
    <td class="col-2 py-0">
      <a href="/matter/{{ $task->matter->id }}">
        {{ $task->matter->uid }}
      </a>
    </td>
    <td class="col text-truncate py-0">
      {{ $task->matter->titles->first()->value }}
    </td>
    <td class="col-2 py-0 px-2">
      {{ \Carbon\Carbon::parse($task->due_date)->isoFormat('L') }}
      @if ($task->due_date < now())
      <div class="badge rounded-pill text-bg-danger" title="{{ __('Overdue') }}">&nbsp;</div>
      @elseif ($task->due_date < now()->addWeeks(2))
      <div class="badge rounded-pill text-bg-warning" title="{{ __('Urgent') }}">&nbsp;</div>
      @endif
    </td>
    @can('readwrite')
    <td class="col-1 py-0 px-3">
      <input id="{{ $task->id }}" class="clear-open-task" type="checkbox">
    </td>
    @endcan
  </tr>
  @endforeach
</table>
{{ $tasks->links() }}
