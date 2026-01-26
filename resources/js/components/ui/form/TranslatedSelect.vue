<template>
  <Select v-model="internalValue" :disabled="disabled">
    <SelectTrigger class="w-full">
      <SelectValue :placeholder="placeholder" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem
        v-if="allowClear"
        value=""
        class="text-muted-foreground"
      >
        {{ clearLabel }}
      </SelectItem>
      <SelectItem
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ getTranslatedLabel(option) }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup>
import { computed } from 'vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Select...' },
  disabled: { type: Boolean, default: false },
  allowClear: { type: Boolean, default: false },
  clearLabel: { type: String, default: '-- Clear --' },
})

const emit = defineEmits(['update:modelValue'])

const { translated } = useTranslatedField()

const internalValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

function getTranslatedLabel(option) {
  if (option.label && typeof option.label === 'object') {
    return translated(option.label)
  }
  return option.label || option.value
}
</script>
