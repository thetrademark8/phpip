{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@inject('sharePoint', 'App\Services\SharePointService')

<table class="table table-hover table-sm">
  <thead class="table-light">
    <tr>
      <th>
        {{ __('Event') }}
        @can('readwrite')
        <a data-bs-toggle="collapse" class="text-info ms-2" href="#addEventRow" id="addEvent" title="{{ __('Add event') }}">
          <svg width="14" height="14" fill="currentColor" style="pointer-events: none"><use xlink:href="#plus-circle-fill"/></svg>
        </a>
        @endcan
      </th>
      <th>{{ __('Date') }}</th>
      <th>{{ __('Number') }}</th>
      <th>{{ __('Notes') }}</th>
      <th>{{ __('Refers to') }}</th>
      @can('readonly')
      <th>{{ __('Email') }}</th>
      @endcan
    </tr>
    <tr id="addEventRow" class="collapse">
      <td colspan="5">
        <form id="addEventForm">
          <input type="hidden" name="matter_id" value="{{ $matter->id }}">
          <div class="input-group">
            <input type="hidden" name="code">
            <input type="text" class="form-control form-control-sm" name="eventName" placeholder="{{ __('Event') }}" data-ac="/event-name/autocomplete/0?category={{ $matter->category_code }}" data-actarget="code">
            <x-date-input name="event_date" class="form-control-sm" placeholder="{{ __('Date (xx/xx/yyyy)') }}" :showLabel="false" />
            <input type="text" class="form-control form-control-sm" name="detail" placeholder="{{ __('Detail') }}">
            <input type="text" class="form-control form-control-sm" name="notes" placeholder="{{ __('Notes') }}">
            <input type="hidden" name="alt_matter_id">
            <input type="text" class="form-control form-control-sm"  placeholder="{{ __('Refers to') }}" data-ac="/matter/autocomplete" data-actarget="alt_matter_id">
            <button type="button" class="btn btn-primary btn-sm" id="addEventSubmit">&check;</button>
            <button type="reset" class="btn btn-outline-primary btn-sm">&times;</button>
          </div>
        </form>
      </td>
    </tr>
  </thead>
  <tbody id="eventList">
    @foreach ( $events as $event )
    <tr data-resource="/event/{{ $event->id }}">
      <td>
        @php
            $sharePointLink = null;
            if ($sharePoint->isEnabled() && 
                array_key_exists($event->code, config('services.sharepoint.event_codes'))) {
                $sharePointLink = $sharePoint->findFolderLink(
                    $matter->caseref,
                    $matter->suffix,
                    config('services.sharepoint.event_codes')[$event->code] . $event->detail
                );
            }
        @endphp
        @if($sharePointLink)
            <a href="{{ $sharePointLink }}" target="_blank">
                {{ $event->info->name }}
            </a>
        @else
            {{ $event->info->name }}
        @endif
      </td>
      <td><x-date-input name="event_date" :value="$event->event_date" :showLabel="false" /></td>
      <td><input type="text" class="form-control noformat" size="16" name="detail" value="{{ $event->detail }}"></td>
      <td><input type="text" class="form-control noformat" name="notes" value="{{ $event->notes }}"></td>
      <td><input type="text" class="form-control noformat" size="10" name="alt_matter_id" data-ac="/matter/autocomplete" value="{{ $event->altMatter ? $event->altMatter->uid : '' }}"></td>
      @can('readonly')
      <td>
            @if (count(App\Models\EventName::where('code',$event->code)->first()->templates) != 0)
            <button class="chooseTemplate button btn-info" data-url="/document/select/{{ $matter->id }}?EventName={{ $event->code }}&Event={{ $event->id }}" >&#9993;</button>
            @endif
      </td>
      @endcan
    </tr>
    @endforeach
  </tbody>
</table>
<a class="badge text-bg-primary float-end" href="https://github.com/jjdejong/phpip/wiki/Events,-Deadlines-and-Tasks#events" target="_blank">?</a>
<div id="templateSelect">
</div>
