<template>
  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <Label for="name">{{ t('document.fields.name') }}</Label>
      <Input
        id="name"
        v-model="localFilters.name"
        :placeholder="t('document.filters.namePlaceholder')"
      />
    </div>
    <div>
      <Label for="notes">{{ t('document.fields.notes') }}</Label>
      <Input
        id="notes"
        v-model="localFilters.notes"
        :placeholder="t('document.filters.notesPlaceholder')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

const localFilters = ref({
  name: props.filters.name || '',
  notes: props.filters.notes || ''
})

watch(localFilters, (newFilters) => {
  emit('update:filters', { ...newFilters })
}, { deep: true })

watch(() => props.filters, (newFilters) => {
  localFilters.value = {
    name: newFilters.name || '',
    notes: newFilters.notes || ''
  }
}, { deep: true })
</script>