<form id="createFeeForm">
  <fieldset>
    <table class="table table-sm">
      <tr>
        <td><label for="for_country" title="{{ $tableComments['for_country'] }}"><b>{{ __('Country') }}</b></label></td><td>
          <input type='hidden' name='for_country'>
          <input type="text" class="form-control form-control-sm" data-ac="/country/autocomplete" data-actarget="for_country" autocomplete="off">
        </td>
        <td><label for="for_category" title="{{ $tableComments['for_category'] }}"><b>{{ __('Category') }}</b></label></td>
        <td>
          <input type='hidden' name='for_category'>
          <input type="text" class="form-control form-control-sm" data-ac="/category/autocomplete" data-actarget="for_category" autocomplete="off"></td>
      </tr>
      <tr>

          <td><label for="for_origin" title="{{ $tableComments['for_origin'] }}"><b>{{ __('Origin') }}</b></label></td><td>
            <input type='hidden' name='for_origin'>
            <input type="text" class="form-control form-control-sm" data-ac="/country/autocomplete" data-actarget="for_origin" autocomplete="off">
          </td>
      </tr>
      <tr>
        <td><label for="use_before" title="{{ $tableComments['use_before'] }}"><b>{{ __('Use before') }}</b></label></td>
        <td><x-date-input name="use_before" class="form-control-sm" /></td>
        <td><label for="use_after" title="{{ $tableComments['use_after'] }}"><b>{{ __('Use after') }}</b></label></td>
        <td><x-date-input name="use_after" class="form-control-sm" /></td>
      </tr>
      <tr>
        <td><label for="qt" title="{{ $tableComments['qt'] }}"><b>{{ __('Qt') }}</b></label> {{ __('From') }}:</td>
        <td><input type="text" class="form-control form-control-sm" name="from_qt"></td>
        <td><label>{{ __('to') }}:</label></td>
        <td><input type="text" class="form-control form-control-sm" name="to_qt"></td>
      </tr>
    </table>
  </fieldset>
  <button type="button" id="createFeeSubmit" class="btn btn-primary">{{ __('Create lines') }}</button><br>
  <span id="zoneAlert" class="alert float-start"></span>
</form>
