<template>
  <DropdownMenuPortal :to="to">
    <DropdownMenuContent
      v-bind="{ ...$attrs, ...forwarded }"
      :class="cn(
        'z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        props.class
      )"
    >
      <slot />
    </DropdownMenuContent>
  </DropdownMenuPortal>
</template>

<script setup>
import { DropdownMenuContent, DropdownMenuPortal, useForwardPropsEmits } from 'radix-vue'
import { cn } from '@/lib/utils'

defineOptions({
  inheritAttrs: false,
})

const props = defineProps({
  to: { type: [String, Object], default: 'body' },
  align: { type: String, default: undefined },
  alignOffset: { type: Number, default: undefined },
  avoidCollisions: { type: Boolean, default: undefined },
  collisionBoundary: { type: [Object, Array, null], default: undefined },
  collisionPadding: { type: [Number, Object], default: 0 },
  forceMount: { type: Boolean, default: undefined },
  hideWhenDetached: { type: Boolean, default: undefined },
  loop: { type: Boolean, default: undefined },
  prioritizePosition: { type: Boolean, default: undefined },
  side: { type: String, default: undefined },
  sideOffset: { type: Number, default: 4 },
  sticky: { type: String, default: undefined },
  updatePositionStrategy: { type: String, default: undefined },
  class: { type: String, default: undefined },
  asChild: { type: Boolean, default: false },
})

const emits = defineEmits(['escapeKeyDown', 'pointerDownOutside', 'focusOutside', 'interactOutside', 'closeAutoFocus'])

const forwarded = useForwardPropsEmits(props, emits)
</script>