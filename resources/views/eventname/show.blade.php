{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<div data-resource="/eventname/{{ $eventname->code }}" class="reload-part">
	<table class="table table-hover table-sm">
		<tr>
			<th width="20%">{{ __('Code') }}</th>
			<td><input class="noformat form-control" name="code" value="{{ $eventname->code }}"></td>
			<td><input type="checkbox" class="noformat" name="is_task" {{ $eventname->is_task ? 'checked' : '' }}></td>
			<th title="{{ $tableComments['is_task'] }}">{{ __('Is Task') }}</th>
		</tr>
		<tr>
			<th>{{ __('Name') }}</th>
			<td><input class="form-control noformat" name="name" value="{{ $eventname->name }}"></td>
			<td><input type="checkbox" class="noformat" name="status_event" {{ $eventname->status_event ? 'checked' : '' }}></td>
			<th title="{{ $tableComments['status_event'] }}">{{ __('Is Status') }}</th>
		</tr>
		<tr>
			<th title="{{ $tableComments['category'] }}">{{ __('Category') }}</th>
			<td><input type="text" class="form-control noformat" data-ac="/category/autocomplete" name="category" value="{{ empty($eventname->categoryInfo) ? '' : $eventname->categoryInfo->category }}"></td>
			<td><input type="checkbox" class="noformat" name="killer" {{ $eventname->killer ? 'checked' : '' }}></td>
			<th title="{{ $tableComments['killer'] }}">{{ __('Is Killer') }}</th>
		</tr>
		<tr>
			<th title="{{ $tableComments['country'] }}">{{ __('Country') }}</th>
			<td><input type="text" class="form-control noformat" name="country" data-ac="/country/autocomplete" value="{{ empty($eventname->countryInfo) ? '' : $eventname->countryInfo->name }}"></td>
			<td colspan="2"></td>
		</tr>
		<tr>
			<th title="{{ $tableComments['default_responsible'] }}">{{ __('Default Responsible') }}</th>
			<td><input type="text" class="form-control noformat" data-ac="/user/autocomplete" name="default_responsible" value="{{ empty($eventname->default_responsibleInfo) ? "" : $eventname->default_responsibleInfo->name }}"></td>
			<td><input type="checkbox" class="noformat" name="use_matter_resp" {{ $eventname->use_matter_resp ? 'checked' : '' }}></td>
			<th title="{{ $tableComments['use_matter_resp'] }}">{{ __('Use Matter Responsible') }}</th>
		</tr>
		<tr>
			<th>{{ __('Notes') }}</th>
			<td colspan="3"><textarea class="form-control form-control-sm noformat" name="notes">{{ $eventname->notes }}</textarea>
		</tr>
		<tr>
			<th colspan="3">{{ __('Linked templates') }}</th>
			<td>
				<a data-bs-toggle="collapse" class="text-info ms-2" href="#addEventRow" id="addEventTempalte" title="{{ __('Add template') }}">
					<svg width="14" height="14" fill="currentColor" style="pointer-events: none"><use xlink:href="#plus-circle-fill"/></svg>
				</a>
			</td>
		</tr>
    <tr id="addEventRow" class="collapse">
      <td colspan="4">
        <form id="addTemplateForm" class="form-inline">
          <input type="hidden" name="event_name_code" value="{{ $eventname->code }}">
          <div class="input-group">
            <input type="hidden" name="template_class_id" value="">
            <input type="text" class="form-control form-control-sm" name="className" placeholder="{{ __('Class') }}" data-ac="/template-class/autocomplete" data-actarget="template_class_id">
            <button type="button" class="btn btn-primary btn-sm" id="addEventTemplateSubmit">&check;</button>
            <button type="reset" class="btn btn-outline-primary btn-sm">&times;</button>
          </div>
        </form>
      </td>
    </tr>
		@foreach ($links as $link)
		<tr class="reveal-hidden" data-resource="/event-class/{{ $link->id }}">
			<td	title="{{ $link->class->description }}" colspan="3">
				{{ $link->class->name}}
			</td>
			<td>
        <a href="#" class="hidden-action text-danger" id="deleteTemplate" title="{{ __('Delete template link') }}">
			<svg width="14" height="14" fill="currentColor" style="pointer-events: none"><use xlink:href="#trash-fill"></use></svg>
		</a>
      </td>

		</tr>
		@endforeach
	</table>
	<button type="button" class="btn btn-danger" title="{{ __('Delete event name') }}" id="deleteEName" data-message="{{ __('event name') }} {{ $eventname->name  }}" data-url='/eventname/{{ $eventname->code }}'>
		{{ __('Delete') }}
	</button>
</div>
