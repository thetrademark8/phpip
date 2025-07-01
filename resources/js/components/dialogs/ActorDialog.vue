<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription>
          {{ dialogDescription }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="max-h-[70vh] overflow-y-auto">
        <ActorForm
          :actor="actor"
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
import ActorForm from '@/Components/forms/ActorForm.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  actor: {
    type: Object,
    default: null
  },
  matterId: {
    type: [String, Number],
    default: null
  },
  containerId: {
    type: [String, Number],
    default: null
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit', 'add-to-matter'].includes(value)
  }
})

const emit = defineEmits(['update:open', 'success'])

const dialogTitle = computed(() => {
  return props.actor ? 'Edit Actor' : 'Create Actor'
})

const dialogDescription = computed(() => {
  return props.actor 
    ? 'Update actor information and contact details'
    : 'Add a new actor (person or company) to the system'
})

const handleSuccess = (response) => {
  // Emit success to parent and let parent handle closing
  emit('success', response)
}
</script>