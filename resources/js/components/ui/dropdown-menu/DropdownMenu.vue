<template>
  <DropdownMenuRoot v-bind="forwarded">
    <slot />
  </DropdownMenuRoot>
</template>

<script setup>
import { DropdownMenuRoot, useForwardPropsEmits } from 'radix-vue'

const props = defineProps({
  defaultOpen: { type: Boolean, default: false },
  open: { type: Boolean, default: undefined },
  dir: { type: String, default: undefined },
  modal: { type: Boolean, default: true },
})

const emits = defineEmits(['update:open'])

const forwarded = useForwardPropsEmits(props, emits)
</script>