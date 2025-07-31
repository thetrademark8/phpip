<template>
  <div class="flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-[200px]">
      <Label for="code" class="mb-2">{{ $t('categories.fields.code') }}</Label>
      <Input
          id="code"
          :model-value="filters.Code"
          @update:model-value="debouncedUpdate('Code', $event)"
          :placeholder="$t('categories.placeholders.code')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="category" class="mb-2">{{ $t('categories.fields.category') }}</Label>
      <Input
          id="category"
          :model-value="filters.Category"
          @update:model-value="debouncedUpdate('Category', $event)"
          :placeholder="$t('categories.placeholders.category')"
      />
    </div>
  </div>
</template>

<script setup>
import {Input} from '@/Components/ui/input'
import {Label} from '@/Components/ui/label'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:filters'])

// Debounce timer
let debounceTimer = null

function debouncedUpdate(key, value) {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('update:filters', { 
      ...props.filters, 
      [key]: value 
    })
  }, 500)
}
</script>