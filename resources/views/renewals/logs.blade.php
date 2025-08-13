{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@extends('layouts.app')

@section('content')
<legend class="alert alert-dark py-2 mb-1">
  {{ __('Renewal logs') }}
</legend>
<div class="row">
  <div class="col">
    <div class="card">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr id="filter" class="table-primary align-middle">
            <th><input class="form-control" data-source="/logs" name="Matter" placeholder="{{ __('Matter') }}"></th>
            <th><input class="form-control" data-source="/logs" name="Client" placeholder="{{ __('Client') }}"></th>
            <th><input class="form-control" data-source="/logs" name="Job" placeholder="{{ __('Job') }}"></th>
            <th><input class="form-control" data-source="/logs" name="User" placeholder="{{ __('User') }}"></th>
            <th>
              <x-date-input name="Fromdate" :value="Request::get('Fromdate')" class="form-control-sm" :inputAttributes="['id' => 'Fromdate', 'title' => __('From selected date')]" :showLabel="false" />
              <x-date-input name="Untildate" :value="Request::get('Untildate')" class="form-control-sm" :inputAttributes="['id' => 'Untildate', 'title' => __('Until selected date')]" :showLabel="false" />
            </th>
            <th>{{ __('Qt') }}</th>
            <th>{{ __('Steps') }}</th>
            <th>{{ __('Grace') }}</th>
            <th>{{ __('Invoicing') }}</th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach($logs as $log)
            <tr data-id="{{ $log->id }}" class="reveal-hidden">
              <td>
                @if( is_null($log->task))
                  {{ __('Task deleted') }}
                @else
                  <a href="/matter/{{ $log->task->matter->id }}">{{ $log->task->matter->uid }}</a>
                @endif
              </td>
              <td>
                {{ is_null($log->task) ? __('Task deleted') : $log->task->matter->client->name }}
              </td>
              <td>{{ $log->job_id }}</td>
              <td>{{ $log->creatorInfo->name }}</td>
              <td>{{ $log->created_at }}</td>
              <td>{{ is_null($log->task) ? '' : $log->task->detail }}</td>
              <td>
                {{ is_null($log->from_step) ? '' : $log->from_step ." -> ". $log->to_step }}
              </td>
              <td>
                {{ is_null($log->from_grace) ? '' : $log->from_grace ." -> ". $log->to_grace }}
              </td>
              <td>
                {{ is_null($log->from_invoice) ? '' : $log->from_invoice ." -> ". $log->to_invoice }}
              </td>
            </tr>
          @endforeach
          <tr>
            <td colspan="9">
              {{ $logs->links() }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/tables.js') }}" defer></script>
@endsection