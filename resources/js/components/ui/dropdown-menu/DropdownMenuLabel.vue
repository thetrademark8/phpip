<template>
  <DropdownMenuLabel
    :class="cn('px-2 py-1.5 text-sm font-semibold', props.class)"
  >
    <slot />
  </DropdownMenuLabel>
</template>

<script setup>
import { DropdownMenuLabel } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  class: { type: String, default: undefined },
  asChild: { type: Boolean, default: false },
})
</script>