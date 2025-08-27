<template>
  <div class="flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-[200px]">
      <Label for="code" class="mb-2">{{ $t('types.fields.code') }}</Label>
      <Input
          id="code"
          :model-value="filters.Code"
          @update:model-value="debouncedUpdate('Code', $event)"
          :placeholder="$t('types.placeholders.code')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="type" class="mb-2">{{ $t('types.fields.type') }}</Label>
      <Input
          id="type"
          :model-value="filters.Type"
          @update:model-value="debouncedUpdate('Type', $event)"
          :placeholder="$t('types.placeholders.type')"
      />
    </div>
  </div>
</template>

<script setup>
import {Input} from '@/components/ui/input'
import {Label} from '@/components/ui/label'

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