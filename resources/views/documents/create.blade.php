{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<form id="createClassForm">
  <fieldset>
    <legend>{{ __('New class') }}</legend>
    <table class="table table-sm">
      <tr>
        <td><label for="name" title="{{ $tableComments['name'] }} "><b>{{ __('Name') }}</b></label></td>
        <td >
          <input type="text" class="form-control form-control-sm" name="name">
        </td>
        <td><label for="notes" title="{{ $tableComments['notes'] }}">{{ __('Notes') }}</label></td>
        <td><input id="notes" class="form-control form-control-sm" name="notes"></td>
      </tr>
      <tr>
        <td><label title="{{ $tableComments['default_role'] }}">{{ __('Default role') }}</label></td>
        <td>
          <input type='hidden' name='default_role'>
          <input type="text" class="form-control form-control-sm" data-ac="/role/autocomplete" data-actarget="default_role" autocomplete="off">
        </td>
      </tr>
    </table>
  </fieldset>

  <button type="button" class="btn btn-danger" id="createClassSubmit" data-redirect="/documents">{{ __('Create class') }}</button><br>
</form>
