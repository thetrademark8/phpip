{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@extends('layouts.app')

@section('content')
<legend class="alert alert-dark d-flex justify-content-between py-2 mb-1">
  {{ __('Classifier Types') }}
  <a href="classifier_type/create" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajaxModal" title="{{ __('Type') }}" data-resource="/classifier_type/">{{ __('Create Classifier Type') }}</a>
</legend>
<div class="row">
  <div class="col">
    <div class="card border-primary p-1" style="max-height: 640px;">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr id="filter" class="table-primary align-middle">
            <th><input class="form-control" data-source="/classifier_type" name="Code" placeholder="{{ __('Code') }}"></th>
            <th><input class="form-control" data-source="/classifier_type" name="Type" placeholder="{{ __('Type') }}"></th>
            <th>{{ __('Category') }}</th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($types as $type)
          <tr class="reveal-hidden" data-id="{{ $type->code }}">
            <td>
              <a href="/classifier_type/{{ $type->code }}" data-panel="ajaxPanel" title="{{ __('Type info') }}">
                {{ $type->code }}
              </a>
            </td>
            <td>{{ $type->type }}</td>
            <td>{{ is_null($type->category) ? '' :  $type->category->category }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-5">
    <div class="card border-info">
      <div class="card-header bg-info text-light">
        {{ __('Type information') }}
      </div>
      <div class="card-body p-2" id="ajaxPanel">
        <div class="alert alert-info" role="alert">
          {{ __('Click on type to view and edit details') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script src="{{ asset('js/tables.js') }}" defer></script>
@endsection
