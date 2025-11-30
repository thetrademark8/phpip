<template>
  <div class="flex w-full gap-2 items-center">
    <DatePicker
      :model-value="modelValue?.from"
      @update:model-value="updateFrom"
      :placeholder="t('From')"
      button-class="flex-1"
    />
    <span class="text-muted-foreground text-sm">→</span>
    <DatePicker
      :model-value="modelValue?.to"
      @update:model-value="updateTo"
      :placeholder="t('To')"
      button-class="flex-1"
    />
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import DatePicker from './DatePicker.vue'

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ from: null, to: null })
  }
})

const emit = defineEmits(['update:modelValue'])

function updateFrom(value) {
  emit('update:modelValue', {
    from: value,
    to: props.modelValue?.to || null
  })
}

function updateTo(value) {
  emit('update:modelValue', {
    from: props.modelValue?.from || null,
    to: value
  })
}
</script>
