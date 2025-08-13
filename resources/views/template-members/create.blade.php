{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<form id="createMemberForm">
  <fieldset>
    <legend>{{ __('New template') }}</legend>
    <table class="table table-sm">
      <tr>
        <td><label for="name" title="{{ $tableComments['class_id'] }} "><b>{{ __('Class') }}</b></label></td>
        <td >
          <input type='hidden' name='class_id'>
          <input type="text" class="form-control form-control-sm" data-ac="/template-class/autocomplete"  data-actarget="class_id">
        </td>
        <td><label for="description" title="{{ $tableComments['language'] }}"><b>{{ __('Language') }}</b></label></td>
        <td >
          <select name="language">
            @foreach ($languages as $code => $lang_name)
              <option value='{{ $code }}'  {{ $code == 'en'  ? 'selected' : ""}}>{{ $lang_name}}
            @endforeach
          </select>
        </td>
      </tr>
      <tr>
        <td><label for="style" title="{{ $tableComments['style'] }}">{{ __('Style') }}</label></td>
        <td>
          <input type="text" class="form-control form-control-sm"  name='style' data-ac="/template-style/autocomplete" data-actarget="style" autocomplete="off">
        </td>
        <td><label title="{{ $tableComments['format'] }}">{{ __('Format') }}</label></td>
        <td>
          <select name="format">
            <option value="TEXT" >{{ __('Text') }}</option>
            <option value="HTML" >{{ __('HTML') }}</option>
          </select>
        </td>
      </tr>
      <tr>
        <th><label title="{{ $tableComments['format'] }}">{{ __('Summary') }}</label></th>
        <td><input type="text" class="form-control" name="summary"></td>
        <td><label for="category" title="{{ $tableComments['category'] }}">{{ __('Category') }}</label></td>
        <td>
          <input type="text"  name='category' class="form-control form-control-sm" data-ac="/template-category/autocomplete" data-actarget="category" autocomplete="off">
        </td>
      </tr>
      <tr>
        <th><label title="{{ $tableComments['subject'] }}">{{ __('Subject') }}</label></th>
        <td colspan="3"><textarea class="form-control" name="subject"></textarea></td>
      </tr>
      <tr>
        <th><label title="{{ $tableComments['body'] }}">{{ __('Body') }}</label></th>
        <td colspan="3"><textarea class="form-control" name="body" rows="20"></textarea></td>
      </tr>
    </table>
  </fieldset>

  <button type="button" class="btn btn-danger" id="createMemberSubmit" data-redirect="/template-member">{{ __('Create template') }}</button><br>
</form>
