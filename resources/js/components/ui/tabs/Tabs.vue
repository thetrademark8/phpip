<template>
  <div>
    <slot />
  </div>
</template>

<script setup>
import { provide, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  defaultValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const activeTab = ref(props.modelValue || props.defaultValue)

watch(() => props.modelValue, (newValue) => {
  activeTab.value = newValue
})

watch(activeTab, (newValue) => {
  emit('update:modelValue', newValue)
})

const setActiveTab = (value) => {
  activeTab.value = value
}

provide('tabs', {
  activeTab,
  setActiveTab
})
</script>