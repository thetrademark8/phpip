<template>
  <div class="space-y-4">
    <!-- Search Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Code -->
      <div class="space-y-2">
        <Label htmlFor="code-filter">{{ t('Code') }}</Label>
        <Input
          id="code-filter"
          type="text"
          :placeholder="t('Filter by code...')"
          :model-value="props.filters.Code"
          @update:model-value="debouncedUpdate('Code', $event)"
        />
      </div>

      <!-- Name -->
      <div class="space-y-2">
        <Label htmlFor="name-filter">{{ t('Name') }}</Label>
        <Input
          id="name-filter"
          type="text"
          :placeholder="t('Filter by name...')"
          :model-value="props.filters.Name"
          @update:model-value="debouncedUpdate('Name', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import debounce from 'lodash.debounce'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

// Create a debounced update function
const debouncedUpdate = debounce((key, value) => {
  updateFilter(key, value)
}, 300)

function updateFilter(key, value) {
  // Clean up empty values
  const cleanValue = value === '' || value === null || value === undefined ? undefined : value
  
  emit('update:filters', { 
    ...props.filters, 
    [key]: cleanValue 
  })
}
</script>