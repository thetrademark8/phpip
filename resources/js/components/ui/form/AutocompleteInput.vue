<template>
  <div class="relative">
    <Input
      ref="inputRef"
      v-model="displayValue"
      v-bind="$attrs"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      @keydown="handleKeydown"
      :class="cn('w-full', inputClass)"
      :placeholder="placeholder"
      autocomplete="off"
    />
    
    <!-- Loading indicator -->
    <div 
      v-if="loading" 
      class="absolute right-2 top-1/2 -translate-y-1/2"
    >
      <Loader2 class="h-4 w-4 animate-spin text-muted-foreground" />
    </div>
    
    <!-- Dropdown -->
    <div
      v-if="showDropdown && filteredSuggestions.length > 0"
      class="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md"
    >
      <div class="max-h-60 overflow-auto py-1">
        <button
          v-for="(suggestion, index) in filteredSuggestions"
          :key="suggestion.key || suggestion.id || index"
          type="button"
          @mousedown.prevent="selectSuggestion(suggestion)"
          class="flex w-full items-center px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground focus:outline-none"
          :class="{
            'bg-accent text-accent-foreground': highlightedIndex === index
          }"
        >
          <span class="flex-1 text-left">
            {{ getItemLabel(suggestion) }}
          </span>
          <span v-if="suggestion.subtitle" class="ml-2 text-xs text-muted-foreground">
            {{ suggestion.subtitle }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import { Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Input } from '@/Components/ui/input'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  displayModelValue: {
    type: String,
    default: ''
  },
  endpoint: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Type to search...'
  },
  minLength: {
    type: Number,
    default: 1
  },
  debounceMs: {
    type: Number,
    default: 300
  },
  valueKey: {
    type: String,
    default: 'id'
  },
  labelKey: {
    type: String,
    default: 'label'
  },
  inputClass: {
    type: String,
    default: ''
  },
  // For maintaining display value when only ID is known
  initialDisplayValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'update:displayModelValue', 'selected'])

// State
const inputRef = ref(null)
const displayValue = ref(props.displayModelValue || props.initialDisplayValue || '')
const suggestions = ref([])
const filteredSuggestions = ref([])
const showDropdown = ref(false)
const highlightedIndex = ref(-1)
const loading = ref(false)
const selectedItem = ref(null)

// Watch for external value changes
watch(() => props.modelValue, (newValue) => {
  if (!newValue && !selectedItem.value) {
    displayValue.value = ''
  }
})

watch(() => props.displayModelValue, (newValue) => {
  if (newValue !== displayValue.value) {
    displayValue.value = newValue
  }
})

// Debounced search
const searchSuggestions = debounce(async (query) => {
  if (query.length < props.minLength) {
    suggestions.value = []
    filteredSuggestions.value = []
    return
  }

  loading.value = true
  
  try {
    // Use regular fetch for autocomplete endpoints
    const url = new URL(props.endpoint, window.location.origin)
    url.searchParams.append('term', query)
    
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (!response.ok) {
      throw new Error('Network response was not ok')
    }
    
    const data = await response.json()
    
    // The response is already an array of suggestions
    suggestions.value = Array.isArray(data) ? data : []
    filteredSuggestions.value = suggestions.value
    highlightedIndex.value = -1
    
  } catch (error) {
    console.error('Autocomplete error:', error)
    suggestions.value = []
    filteredSuggestions.value = []
  } finally {
    loading.value = false
  }
}, props.debounceMs)

// Handlers
const handleInput = (event) => {
  displayValue.value = event.target.value
  emit('update:displayModelValue', displayValue.value)
  
  // Clear the actual value if the display value doesn't match
  if (selectedItem.value && displayValue.value !== getItemLabel(selectedItem.value)) {
    selectedItem.value = null
    emit('update:modelValue', '')
  }
  
  searchSuggestions(displayValue.value)
  showDropdown.value = true
}

const handleFocus = () => {
  if (filteredSuggestions.value.length > 0) {
    showDropdown.value = true
  }
}

const handleBlur = () => {
  // Delay to allow click on suggestions
  setTimeout(() => {
    showDropdown.value = false
    
    // Revert to selected item if no new selection
    if (selectedItem.value && displayValue.value !== getItemLabel(selectedItem.value)) {
      displayValue.value = getItemLabel(selectedItem.value)
      emit('update:displayModelValue', displayValue.value)
    } else if (!selectedItem.value && displayValue.value && !props.modelValue) {
      // Clear if no valid selection and no initial value
      displayValue.value = ''
      emit('update:displayModelValue', '')
      emit('update:modelValue', '')
    } else if (!selectedItem.value && props.initialDisplayValue && displayValue.value !== props.initialDisplayValue) {
      // Revert to initial display value if provided
      displayValue.value = props.initialDisplayValue
      emit('update:displayModelValue', displayValue.value)
    }
  }, 200)
}

const handleKeydown = (event) => {
  if (!showDropdown.value || filteredSuggestions.value.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(
        highlightedIndex.value + 1,
        filteredSuggestions.value.length - 1
      )
      break
      
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
      break
      
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0) {
        selectSuggestion(filteredSuggestions.value[highlightedIndex.value])
      }
      break
      
    case 'Escape':
      showDropdown.value = false
      highlightedIndex.value = -1
      break
  }
}

const selectSuggestion = (suggestion) => {
  selectedItem.value = suggestion
  displayValue.value = getItemLabel(suggestion)
  emit('update:displayModelValue', displayValue.value)
  emit('update:modelValue', getItemValue(suggestion))
  emit('selected', suggestion)
  
  showDropdown.value = false
  highlightedIndex.value = -1
  
  // Focus next input if in a form
  nextTick(() => {
    const form = inputRef.value?.$el?.closest('form')
    if (form) {
      const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea'))
      const currentIndex = inputs.indexOf(inputRef.value.$el)
      const nextInput = inputs[currentIndex + 1]
      if (nextInput) {
        nextInput.focus()
      }
    }
  })
}

// Helpers
const getItemValue = (item) => {
  if (!item) return ''
  // First check for the configured valueKey, then fall back to 'key' which is the standard response format
  return item[props.valueKey] !== undefined ? item[props.valueKey] : item.key || ''
}

const getItemLabel = (item) => {
  if (!item) return ''
  // First check for the configured labelKey, then fall back to 'value' which is the standard response format
  return item[props.labelKey] !== undefined ? item[props.labelKey] : item.value || ''
}

// Expose clear method
const clear = () => {
  displayValue.value = ''
  selectedItem.value = null
  emit('update:displayModelValue', '')
  emit('update:modelValue', '')
}

defineExpose({ clear })
</script>