<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Code Filter -->
    <div class="space-y-2">
      <Label
        for="filter-code"
        class="mb-2"
      >{{ $t('classifierTypes.fields.code') }}</Label>
      <Input
        id="filter-code"
        :model-value="filters.Code"
        :placeholder="$t('classifierTypes.placeholders.code')"
        class="w-full"
        @update:model-value="debouncedUpdate('Code', $event)"
      />
    </div>

    <!-- Type Filter -->
    <div class="space-y-2">
      <Label
        for="filter-type"
        class="mb-2"
      >{{ $t('classifierTypes.fields.type') }}</Label>
      <Input
        id="filter-type"
        :model-value="filters.Type"
        :placeholder="$t('classifierTypes.placeholders.type')"
        class="w-full"
        @update:model-value="debouncedUpdate('Type', $event)"
      />
    </div>

    <!-- Category Filter -->
    <div class="space-y-2">
      <Label
        for="filter-category"
        class="mb-2"
      >{{ $t('classifierTypes.fields.category') }}</Label>
      <Input
        id="filter-category"
        :model-value="filters.Category"
        :placeholder="$t('classifierTypes.placeholders.category')"
        class="w-full"
        @update:model-value="debouncedUpdate('Category', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { debounce } from 'lodash-es'
import { useI18n } from 'vue-i18n'
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

// Create debounced update function
const debouncedUpdate = debounce((key, value) => {
  const newFilters = { ...props.filters, [key]: value }
  emit('update:filters', newFilters)
}, 300)
</script>