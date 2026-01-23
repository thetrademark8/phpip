<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :disabled="disabled"
        class="w-full justify-between font-normal"
        :class="{ 'text-muted-foreground': !modelValue }"
      >
        <span class="truncate">
          {{ displayValue || placeholder }}
        </span>
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[--reka-popover-trigger-width] p-0" align="start">
      <div class="flex flex-col">
        <div class="flex items-center border-b px-3">
          <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            :placeholder="searchPlaceholder"
            class="flex h-10 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
          />
        </div>
        <div class="max-h-60 overflow-auto py-1">
          <div
            v-if="loading"
            class="py-6 text-center text-sm text-muted-foreground"
          >
            <Loader2 class="mx-auto h-4 w-4 animate-spin" />
          </div>
          <div
            v-else-if="filteredOptions.length === 0"
            class="py-6 text-center text-sm text-muted-foreground"
          >
            {{ noResultsText }}
          </div>
          <button
            v-for="option in filteredOptions"
            :key="option.value"
            type="button"
            class="relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
            @click="selectOption(option)"
          >
            <Check
              :class="cn('mr-2 h-4 w-4', modelValue === option.value ? 'opacity-100' : 'opacity-0')"
            />
            <span class="truncate">{{ getLabel(option) }}</span>
          </button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { Check, ChevronsUpDown, Search, Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select...' },
  searchPlaceholder: { type: String, default: 'Search...' },
  noResultsText: { type: String, default: 'No results found.' },
  disabled: { type: Boolean, default: false },
  searchEndpoint: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'selected'])

const { translated } = useTranslatedField()

const open = ref(false)
const searchQuery = ref('')
const searchInputRef = ref(null)
const loading = ref(false)
const apiOptions = ref([])

const allOptions = computed(() => {
  return props.searchEndpoint && apiOptions.value.length > 0 ? apiOptions.value : props.options
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return allOptions.value

  const query = searchQuery.value.toLowerCase()
  return allOptions.value.filter(option => {
    const label = getLabel(option).toLowerCase()
    const value = String(option.value).toLowerCase()
    return label.includes(query) || value.includes(query)
  })
})

const displayValue = computed(() => {
  const selected = allOptions.value.find(opt => opt.value === props.modelValue)
  return selected ? getLabel(selected) : ''
})

function getLabel(option) {
  if (option.label && typeof option.label === 'object') {
    return translated(option.label)
  }
  return option.label || option.value
}

function selectOption(option) {
  emit('update:modelValue', option.value)
  emit('selected', option)
  open.value = false
  searchQuery.value = ''
}

async function fetchOptions(search = '') {
  if (!props.searchEndpoint) return

  loading.value = true
  try {
    const url = new URL(props.searchEndpoint, window.location.origin)
    if (search) {
      url.searchParams.append('search', search)
    }

    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    })

    if (response.ok) {
      apiOptions.value = await response.json()
    }
  } catch (error) {
    console.error('Combobox fetch error:', error)
  } finally {
    loading.value = false
  }
}

watch(open, async (isOpen) => {
  if (isOpen) {
    searchQuery.value = ''
    if (props.searchEndpoint && apiOptions.value.length === 0) {
      await fetchOptions()
    }
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
})
</script>
