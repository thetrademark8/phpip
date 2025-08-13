{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<div data-resource="/template-member/{{ $templateMember->id }}">
  <table class="table">
    <tr>
      <th>{{ __('Class') }}</th>
      <td><input type="text" class="form-control noformat" name="class_id" data-ac="/template-class/autocomplete" value="{{ is_null($templateMember->class) ? "" : $templateMember->class->name }}"></td>
      <th><label title="{{ $tableComments['language'] }}">{{ __('Language') }}</label></th>
      <td>
        <select name="language" class="noformat">
          @foreach ($languages as $code => $lang_name)
            <option value='{{ $code }}'  {{ $templateMember->language == $code ? 'selected' : ""}}>{{ $lang_name}}
          @endforeach
        </select>
      <th><label title="{{ $tableComments['style'] }}">{{ __('Style') }}</label></th>
      <td><input type="text" class="form-control noformat" name="style" data-ac="/template-style/autocomplete" value="{{ $templateMember->style }}"></td>
      <th><label title="{{ $tableComments['category'] }}">{{ __('Category') }}</label></th>
      <td ><input type="text" class="form-control noformat" name="category" data-ac="/template-category/autocomplete" value="{{ $templateMember->category }}"></td>
      <th><label title="{{ $tableComments['format'] }}">{{ __('Format') }}</label></th>
      <td>
        <select name="format" class="noformat">
          <option value="TEXT" {{ $templateMember->format == "TEXT" ? 'selected' : ""}}>{{ __('Text') }}</option>
          <option value="HTML" {{ $templateMember->format == "HTML" ? 'selected' : ""}}>{{ __('HTML') }}</option>
        </select>
      </td>
    </tr>
    <tr>
        <th><label title="{{ $tableComments['summary'] }}">{{ __('Summary') }}</label></th>
        <td colspan="9"><input type="text" class="form-control noformat" name="summary" value="{{ $templateMember->summary }}"></td>
    </tr>
    <tr>
      <th><label title="{{ $tableComments['subject'] }}">{{ __('Subject') }}</label></th>
      <td colspan="9"><textarea class="form-control noformat" name="subject" rows="3">{{ $templateMember->subject }}</textarea></td>
    </tr>
    <tr>
      <th><label title="{{ $tableComments['body'] }}">{{ __('Body') }}</label></th>
      <td colspan="9"><textarea class="form-control noformat" name="body" rows="20">{{ $templateMember->body }}</textarea></td>
    </tr>
  </table>
  <button type="button" class="btn btn-danger" title="{{ __('Delete template') }}" id="deleteMember" data-message="{{ __('the template') }} {{ $templateMember->class->name  }}" data-url='/template-member/{{ $templateMember->id }}'>
    {{ __('Delete') }}
  </button>
</div>
