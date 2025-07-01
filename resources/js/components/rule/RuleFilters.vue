<template>
  <div class="space-y-4">
    <!-- Search Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <!-- Task -->
      <div class="space-y-2">
        <Label htmlFor="task-filter">{{ t('rules.filters.labels.task') }}</Label>
        <AutocompleteInput
          id="task-filter"
          :placeholder="t('rules.filters.placeholders.task')"
          :model-value="props.filters.Task"
          @update:model-value="debouncedUpdate('Task', $event)"
          endpoint="/event-name/autocomplete/1"
          value-key="code"
          label-key="value"
        />
      </div>

      <!-- Detail -->
      <div class="space-y-2">
        <Label htmlFor="detail-filter">{{ t('rules.filters.labels.detail') }}</Label>
        <Input
          id="detail-filter"
          type="text"
          :placeholder="t('rules.filters.placeholders.detail')"
          :model-value="props.filters.Detail"
          @update:model-value="debouncedUpdate('Detail', $event)"
        />
      </div>

      <!-- Trigger Event -->
      <div class="space-y-2">
        <Label htmlFor="trigger-filter">{{ t('rules.filters.labels.trigger') }}</Label>
        <AutocompleteInput
          id="trigger-filter"
          :placeholder="t('rules.filters.placeholders.trigger')"
          :model-value="props.filters.Trigger"
          @update:model-value="debouncedUpdate('Trigger', $event)"
          endpoint="/event-name/autocomplete/0"
          value-key="code"
          label-key="value"
        />
      </div>

      <!-- Category -->
      <div class="space-y-2">
        <Label htmlFor="category-filter">{{ t('rules.filters.labels.category') }}</Label>
        <AutocompleteInput
          id="category-filter"
          :placeholder="t('rules.filters.placeholders.category')"
          :model-value="props.filters.Category"
          @update:model-value="debouncedUpdate('Category', $event)"
          endpoint="/category/autocomplete"
          value-key="code"
          label-key="value"
        />
      </div>

      <!-- Country -->
      <div class="space-y-2">
        <Label htmlFor="country-filter">{{ t('rules.filters.labels.country') }}</Label>
        <AutocompleteInput
          id="country-filter"
          :placeholder="t('rules.filters.placeholders.country')"
          :model-value="props.filters.Country"
          @update:model-value="debouncedUpdate('Country', $event)"
          endpoint="/country/autocomplete"
          value-key="iso"
          label-key="name"
        />
      </div>

      <!-- Origin -->
      <div class="space-y-2">
        <Label htmlFor="origin-filter">{{ t('rules.filters.labels.origin') }}</Label>
        <AutocompleteInput
          id="origin-filter"
          :placeholder="t('rules.filters.placeholders.origin')"
          :model-value="props.filters.Origin"
          @update:model-value="debouncedUpdate('Origin', $event)"
          endpoint="/country/autocomplete"
          value-key="iso"
          label-key="name"
        />
      </div>

      <!-- Type -->
      <div class="space-y-2">
        <Label htmlFor="type-filter">{{ t('rules.filters.labels.type') }}</Label>
        <AutocompleteInput
          id="type-filter"
          :placeholder="t('rules.filters.placeholders.type')"
          :model-value="props.filters.Type"
          @update:model-value="debouncedUpdate('Type', $event)"
          endpoint="/type/autocomplete"
          value-key="code"
          label-key="value"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash-es'
import { Label } from '@/Components/ui/label'
import { Input } from '@/Components/ui/input'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

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