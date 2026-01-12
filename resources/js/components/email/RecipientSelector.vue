<template>
  <div class="recipient-selector space-y-2">
    <div class="space-y-2">
      <div
        v-for="recipient in recipients"
        :key="recipient.id"
        class="flex items-center space-x-2"
      >
        <Checkbox
          :id="`recipient-${recipient.id}`"
          v-model="checkedState[recipient.id]"
        />
        <label
          :for="`recipient-${recipient.id}`"
          class="flex-1 text-sm cursor-pointer flex items-center justify-between"
        >
          <span class="flex flex-col">
            <span class="font-medium">{{ recipient.name }}</span>
            <span class="text-muted-foreground text-xs">{{ recipient.email }}</span>
          </span>
          <Badge variant="outline" class="text-xs">{{ recipient.role }}</Badge>
        </label>
      </div>
    </div>

    <div v-if="recipients.length === 0" class="text-sm text-muted-foreground py-2">
      {{ $t('email.noRecipients') }}
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  recipients: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:modelValue'])

// Internal state: { recipientId: boolean }
const checkedState = reactive({})

// Initialize checked state from recipients and modelValue
const initializeState = () => {
  props.recipients.forEach(r => {
    checkedState[r.id] = props.modelValue.includes(r.id)
  })
}

// Watch for external modelValue changes
watch(() => props.modelValue, () => {
  initializeState()
}, { immediate: true })

// Watch for recipients changes (in case list updates)
watch(() => props.recipients, () => {
  initializeState()
})

// Emit selected IDs when checkedState changes
watch(checkedState, (newState) => {
  const selectedIds = Object.entries(newState)
    .filter(([_, isChecked]) => isChecked)
    .map(([id]) => parseInt(id, 10))
  emit('update:modelValue', selectedIds)
}, { deep: true })
</script>
