<template>
  <DateRangePicker
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :placeholder="placeholder || t('Select date range')"
    :number-of-months="1"
    button-class="w-full"
  />
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import DateRangePicker from './DateRangePicker.vue'

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ from: null, to: null })
  },
  placeholder: {
    type: String,
    default: null
  }
})

defineEmits(['update:modelValue'])
</script>
