<template>
  <button
    type="button"
    :class="cn(
      'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      isSelected
        ? 'bg-background text-foreground shadow-sm'
        : 'hover:bg-accent hover:text-accent-foreground',
      props.size === 'sm' && 'h-8 px-2 text-xs',
      props.size === 'lg' && 'h-11 px-5',
      $attrs.class
    )"
    @click="handleClick"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed, inject } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  value: {
    type: [String, Number],
    required: true,
  },
  size: {
    type: String,
    default: 'default',
  },
})

const context = inject('toggle-group', {})

const isSelected = computed(() => {
  if (context.type === 'single') {
    return context.modelValue === props.value
  } else {
    return context.modelValue?.includes(props.value) || false
  }
})

function handleClick() {
  context.updateModelValue?.(props.value)
}
</script>