{{-- DEPRECATED: This Blade view is deprecated and will be removed.
Please use the corresponding Vue.js component instead.
Date deprecated: 2025-08-13 --}}
@php
$mdeps = $matter_dependencies->groupBy('role');
$adeps = $other_dependencies->groupBy('Dependency');
@endphp

<p class="fw-bolder my-2">{{ __('Matter Dependencies (only the first few are shown)') }}</p>
@forelse($mdeps as $role => $rmdeps)
  <div class="card m-1">
    <div class="card-header p-0">
      <b>{{ $role }}</b>
    </div>
    <div class="card-body p-1 align-middle">
      @foreach($rmdeps as $mal)
        <a class="badge text-bg-primary" href="/matter/{{$mal->matter_id}}" target="_blank">{{ $mal->matter->uid }}</a>
      @endforeach
    </div>
  </div>
@empty
  {{ __('No dependencies') }}
@endforelse
<p class="fw-bolder my-2">{{ __('Inter-Actor Dependencies') }}</p>
@forelse($adeps as $dep => $aadeps)
  <div class="card m-1">
    <div class="card-header p-0">
      <b>{{ $dep }}</b>
    </div>
    <div class="card-body p-1">
      @foreach($aadeps as $other)
        <a href="/actor/{{$other->id}}" target="_blank">{{ $other->Actor }}</a>
      @endforeach
    </div>
  </div>
@empty
  {{ __('No dependencies') }}
@endforelse
