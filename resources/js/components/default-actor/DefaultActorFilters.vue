<template>
  <div class="flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-[200px]">
      <Label for="actor" class="mb-2">{{ $t('defaultActors.fields.actor') }}</Label>
      <Input
        id="actor"
        :model-value="filters.Actor"
        @update:model-value="debouncedUpdate('Actor', $event)"
        :placeholder="$t('defaultActors.placeholders.actor')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="role" class="mb-2">{{ $t('defaultActors.fields.role') }}</Label>
      <Input
        id="role"
        :model-value="filters.Role"
        @update:model-value="debouncedUpdate('Role', $event)"
        :placeholder="$t('defaultActors.placeholders.role')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="country" class="mb-2">{{ $t('defaultActors.fields.country') }}</Label>
      <Input
        id="country"
        :model-value="filters.Country"
        @update:model-value="debouncedUpdate('Country', $event)"
        :placeholder="$t('defaultActors.placeholders.country')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="category" class="mb-2">{{ $t('defaultActors.fields.category') }}</Label>
      <Input
        id="category"
        :model-value="filters.Category"
        @update:model-value="debouncedUpdate('Category', $event)"
        :placeholder="$t('defaultActors.placeholders.category')"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label for="client" class="mb-2">{{ $t('defaultActors.fields.client') }}</Label>
      <Input
        id="client"
        :model-value="filters.Client"
        @update:model-value="debouncedUpdate('Client', $event)"
        :placeholder="$t('defaultActors.placeholders.client')"
      />
    </div>
  </div>
</template>

<script setup>
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

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