<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-sm">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription v-if="dialogDescription">
          {{ dialogDescription }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="max-h-[70vh] overflow-y-auto">
        <MatterEditForm
          v-if="operation === 'edit'"
          :matter="matter"
          @success="handleSuccess"
          @cancel="$emit('update:open', false)"
        />
        <MatterForm
          v-else
          :operation="operation"
          :parent-matter="matter"
          :child-id="childId"
          :ops-number="opsNumber"
          @success="handleSuccess"
          @cancel="$emit('update:open', false)"
        />
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import MatterForm from '@/Components/forms/MatterForm.vue'
import MatterEditForm from '@/Components/forms/MatterEditForm.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  operation: {
    type: String,
    default: 'new',
    validator: (value) => ['new', 'edit', 'child', 'ops'].includes(value)
  },
  matter: {
    type: Object,
    default: null
  },
  childId: {
    type: Number,
    default: null
  },
  opsNumber: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:open', 'success'])

const dialogTitle = computed(() => {
  switch (props.operation) {
    case 'new':
      return 'Create Matter'
    case 'edit':
      return 'Edit Matter'
    case 'child':
      return 'Create Child Matter'
    case 'ops':
      return 'Create Family from OPS'
    default:
      return 'Create Matter'
  }
})

const dialogDescription = computed(() => {
  switch (props.operation) {
    case 'child':
      return 'Create a new matter linked to the current one'
    case 'ops':
      return 'Fetch patent family information from OPS and create matters'
    default:
      return null
  }
})

const handleSuccess = (response) => {
  // Emit success to parent and let parent handle closing
  emit('success', response)
}
</script>