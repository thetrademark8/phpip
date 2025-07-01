<template>
  <DropdownMenuTrigger v-bind="$attrs" :as-child="asChild">
    <slot />
  </DropdownMenuTrigger>
</template>

<script setup>
import { DropdownMenuTrigger } from 'radix-vue'

defineProps({
  asChild: { type: Boolean, default: false },
})
</script>