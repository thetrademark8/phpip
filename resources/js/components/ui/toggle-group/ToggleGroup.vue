<template>
  <div
    :class="cn(
      'inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground',
      $attrs.class
    )"
  >
    <slot />
  </div>
</template>

<script setup>
import { provide } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: undefined,
  },
  type: {
    type: String,
    validator: (value) => ['single', 'multiple'].includes(value),
    default: 'single',
  },
})

const emit = defineEmits(['update:modelValue'])

// Provide reactive context to children
provide('toggle-group', {
  get modelValue() { return props.modelValue },
  type: props.type,
  updateModelValue: (value) => {
    if (props.type === 'single') {
      emit('update:modelValue', value)
    } else {
      // Multiple selection logic
      const current = props.modelValue || []
      const newValue = current.includes(value)
        ? current.filter(v => v !== value)
        : [...current, value]
      emit('update:modelValue', newValue)
    }
  },
})
</script>