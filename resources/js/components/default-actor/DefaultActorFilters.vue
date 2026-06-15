<template>
  <div class="flex flex-wrap gap-4 items-end">
    <div class="flex-1 min-w-[200px]">
      <Label
        for="actor"
        class="mb-2"
      >{{ $t('defaultActors.fields.actor') }}</Label>
      <Input
        id="actor"
        :model-value="filters.Actor"
        :placeholder="$t('defaultActors.placeholders.actor')"
        @update:model-value="debouncedUpdate('Actor', $event)"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label
        for="role"
        class="mb-2"
      >{{ $t('defaultActors.fields.role') }}</Label>
      <Input
        id="role"
        :model-value="filters.Role"
        :placeholder="$t('defaultActors.placeholders.role')"
        @update:model-value="debouncedUpdate('Role', $event)"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label
        for="country"
        class="mb-2"
      >{{ $t('defaultActors.fields.country') }}</Label>
      <Input
        id="country"
        :model-value="filters.Country"
        :placeholder="$t('defaultActors.placeholders.country')"
        @update:model-value="debouncedUpdate('Country', $event)"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label
        for="category"
        class="mb-2"
      >{{ $t('defaultActors.fields.category') }}</Label>
      <Input
        id="category"
        :model-value="filters.Category"
        :placeholder="$t('defaultActors.placeholders.category')"
        @update:model-value="debouncedUpdate('Category', $event)"
      />
    </div>

    <div class="flex-1 min-w-[200px]">
      <Label
        for="client"
        class="mb-2"
      >{{ $t('defaultActors.fields.client') }}</Label>
      <Input
        id="client"
        :model-value="filters.Client"
        :placeholder="$t('defaultActors.placeholders.client')"
        @update:model-value="debouncedUpdate('Client', $event)"
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