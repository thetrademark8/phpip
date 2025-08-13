{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@extends('layouts.app')

@section('content')
<legend class="alert alert-dark d-flex justify-content-between py-2 mb-1">
  {{ __('Email Templates') }}
  <a href="template-member/create" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajaxModal" title="{{ __('Document member') }}" data-source="/template-member" data-resource="/template-member/create/">{{ __('Create a new member of templates') }}</a>
</legend>
<div class="row">
  <div class="col">
    <div class="card border-primary p-1">
      <table class="table table-striped table-hover table-sm">
        <thead>
          <tr id="filter" class="table-primary align-middle">
            <th><input class="form-control" data-source="/template-member" name="summary" placeholder="{{ __('Summary') }}"></th>
            <th><input class="form-control" data-source="/template-member" name="class" placeholder="{{ __('Class') }}"></th>
            <th><input class="form-control" data-source="/template-member" name="language" placeholder="{{ __('Language') }}"></th>
            <th><input class="form-control" data-source="/template-member" name="style" placeholder="{{ __('Style') }}"></th>
            <th><input class="form-control" data-source="/template-member" name="format" placeholder="{{ __('Format') }}"></th>
            <th><input class="form-control" data-source="/template-member" name="category" placeholder="{{ __('Category') }}"></th>
          </tr>
        </thead>
        <tbody id="tableList">
          @foreach ($template_members as $member)
          <tr data-id="{{ $member->id }}" class="reveal-hidden">
            <td>
              <a href="/template-member/{{ $member->id }}" data-panel="ajaxPanel" title="{{ __('Class data') }}">
                {{ $member->summary }}
              </a>
            </td>
            <td>{{ $member->class->name }}</td>
            <td>{{ $member->language }}</td>
            <td>{{ $member->style }}</td>
            <td>{{ $member->format }}</td>
            <td>{{ $member->category }}</td>
          </tr>
          @endforeach
          <tr>
            <td colspan="5">
              {{ $template_members->links() }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card border-info">
      <div class="card-header bg-info text-light">
        {{ __('Template member information') }}
      </div>
      <div class="card-body p-2" id="ajaxPanel">
        <div class="alert alert-info" role="alert">
          {{ __('Click on member to view and edit details') }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/tables.js') }}" defer></script>
@endsection
