<template>
  <div class="flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-[200px]">
      <Label for="code" class="mb-2">{{ $t('roles.fields.code') }}</Label>
      <Input
          id="code"
          :model-value="filters.Code"
          @update:model-value="debouncedUpdate('Code', $event)"
          :placeholder="$t('roles.placeholders.code')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="name" class="mb-2">{{ $t('roles.fields.name') }}</Label>
      <Input
          id="name"
          :model-value="filters.Name"
          @update:model-value="debouncedUpdate('Name', $event)"
          :placeholder="$t('roles.placeholders.name')"
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