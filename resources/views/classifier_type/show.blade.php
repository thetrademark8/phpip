{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<div data-resource="/classifier_type/{{ $classifier_type->code }}">
	<table class="table">
		<tr>
			<th width="22%">{{ __('Code') }}</th>
			<td width="20%"><input class="noformat form-control" name="code" value="{{ $classifier_type->code }}"></td>
			<th><label title="{{ $tableComments['type'] }}">{{ __('Name') }}</label></th>
			<td><input class="form-control noformat" name="type" value="{{ $classifier_type->type }}"></td>
		</tr>
		<tr>
			<th><label title="{{ $tableComments['display_order'] }}">{{ __('Display order') }}</label></th>
			<td><input class="form-control noformat" type='text' name="display_order" value="{{ $classifier_type->display_order }}"></input>
			<th><label title="{{ $tableComments['for_category'] }}">{{ __('Category') }}</label></th>
			<td><input type="text" class="form-control noformat" data-ac="/category/autocomplete" name="for_category" value="{{ empty($classifier_type->for_category) ? '' : $classifier_type->category->category }}"></td>
		</tr>
		<tr>
      <th><label title="{{ $tableComments['main_display'] }}">{{ __('Main display') }}</label></th>
      <td><input type="checkbox" class="noformat" name="main_display" {{ $classifier_type->main_display ? 'checked' : ''  }}></td>
      <th><label title="{{ $tableComments['notes'] }}">{{ __('Notes') }}</label></th>
      <td><textarea class="form-control noformat" name="notes"> {{ $classifier_type->notes }}</textarea></td>
    </tr>
	</table>
	<button type="button" class="btn btn-danger" title="{{ __('Delete type') }}" id="deleteClassifierType" data-message="{{ __('type') }} {{$classifier_type->type }}" data-url='/classifier_type/{{ $classifier_type->code }}'>
		{{ __('Delete') }}
	</button>
</div>
