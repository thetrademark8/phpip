<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
    <div class="flex flex-col space-y-2">
      <Label for="country-filter">{{ t('Country') }}</Label>
      <AutocompleteInput
        id="country-filter"
        v-model="localFilters.Country"
        :endpoint="'/country/autocomplete'"
        :placeholder="t('Country')"
      />
    </div>
    
    <div class="flex flex-col space-y-2">
      <Label for="category-filter">{{ t('Category') }}</Label>
      <AutocompleteInput
        id="category-filter"
        v-model="localFilters.Category"
        :endpoint="'/category/autocomplete'"
        :placeholder="t('Category')"
      />
    </div>
    
    <div class="flex flex-col space-y-2">
      <Label for="origin-filter">{{ t('Origin') }}</Label>
      <AutocompleteInput
        id="origin-filter"
        v-model="localFilters.Origin"
        :endpoint="'/country/autocomplete'"
        :placeholder="t('Origin')"
      />
    </div>
    
    <div class="flex flex-col space-y-2">
      <Label for="qt-filter">{{ t('Yr') }}</Label>
      <Input
        id="qt-filter"
        v-model="localFilters.Qt"
        type="number"
        :placeholder="t('Yr')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

// Local reactive copy of filters
const localFilters = ref({ ...props.filters })

// Watch for changes and emit to parent
watch(localFilters, (newFilters) => {
  emit('update:filters', { ...newFilters })
}, { deep: true })

// Watch for external filter changes (like clearing)
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })
</script>