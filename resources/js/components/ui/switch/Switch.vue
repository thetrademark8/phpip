<template>
  <SwitchRoot
    :id="id"
    :checked="checked"
    :defaultChecked="defaultChecked"
    :disabled="disabled"
    :required="required"
    :name="name"
    :value="value"
    @update:checked="handleCheckedChange"
    :class="cn(
      'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input',
      $attrs.class
    )"
  >
    <SwitchThumb
      :class="cn(
        'pointer-events-none block h-4 w-4 rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0'
      )"
    />
  </SwitchRoot>
</template>

<script setup>
import { SwitchRoot, SwitchThumb } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  id: String,
  checked: {
    type: Boolean,
    default: false,
  },
  defaultChecked: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  name: String,
  value: String,
})

const emit = defineEmits(['update:checked'])

function handleCheckedChange(value) {
  emit('update:checked', value)
}
</script>