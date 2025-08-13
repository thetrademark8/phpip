{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
<div id="tocopy">
@foreach ($description as $line) 
{{ $line }} <BR />
@endforeach
</div>
<div class="float-end">
  <button id="sumButton"  type="button" class="btn btn-primary float-center" data-bs-dismiss="modal">Copy</button>
</div>
