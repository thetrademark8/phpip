{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@extends('layouts.app')

@section('content')
<legend class="alert alert-dark d-flex justify-content-between py-2 mb-1">
  {{ __('Categories') }}
  <a href="category/create" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajaxModal" title="{{ __('Category') }}" data-resource="/category/">{{ __('Create Category') }}</a>
</legend>
<div class="row">
  <div class="col">
    <div class="card border-primary p-1" style="max-height: 640px;">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr id="filter" class="table-primary align-middle">
            <th><input class="form-control" data-source="/category" name="Code" placeholder="{{ __('Code') }}"></th>
            <th><input class="form-control" data-source="/category" name="Category" placeholder="{{ __('Category') }}"></th>
            <th colspan="2">{{ __('Display with') }}</th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($categories as $category)
          <tr class="reveal-hidden" data-id="{{ $category->code }}">
            <td>
              <a href="/category/{{ $category->code }}" data-panel="ajaxPanel" title="{{ __('Category info') }}">
                {{ $category->code }}
              </a>
            </td>
            <td>{{ $category->category }}</td>
            <td>{{ $category->displayWithInfo->category }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  <div class="col-5">
    <div class="card border-info">
      <div class="card-header bg-info text-light">
        {{ __('Category information') }}
      </div>
      <div class="card-body p-2" id="ajaxPanel">
        <div class="alert alert-info" role="alert">
          {{ __('Click on category to view and edit details') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script src="{{ asset('js/tables.js') }}" defer></script>
@endsection
