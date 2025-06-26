<div class="{{ $showLabel ? 'mb-3' : '' }}">
    {{-- Label --}}
    @if($showLabel)
    <label for="{{ $id }}" class="form-label">
        {{ $getLabelText() }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    @endif
    
    {{-- Date Input Container --}}
    <div class="date-input-container position-relative">
        {{-- Visible date input for user display --}}
        <input @foreach($getInputAttributes() as $attr => $value){{ $attr }}="{{ $value }}" @endforeach>
        
        {{-- Hidden ISO format input for form submission --}}
        <input type="hidden" 
               name="{{ $name }}_iso" 
               id="{{ $id }}_iso"
               value="{{ $value }}">
        
        {{-- Calendar icon --}}
        <div class="date-input-icon position-absolute top-50 end-0 translate-middle-y pe-3 pointer-events-none">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
        </div>
    </div>
    
    {{-- Help text --}}
    @if($helpText)
        <div class="form-text text-muted">
            {{ $helpText }}
        </div>
    @endif
    
    {{-- Error display --}}
    @error($name)
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
    
    {{-- JavaScript configuration (only output once per component) --}}
    @once
        <script>
            window.datePickerConfig = @json($config);
        </script>
    @endonce
</div>

<style>
    .date-input-container {
        position: relative;
    }
    
    .date-input-icon {
        color: #6c757d;
        z-index: 5;
    }
    
    .date-picker-input {
        padding-right: 2.5rem;
    }
    
    .date-picker-input:focus + .date-input-icon {
        color: #0d6efd;
    }
    
    .pointer-events-none {
        pointer-events: none;
    }
</style>