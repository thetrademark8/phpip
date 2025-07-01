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
        <TaskForm
          :task="task"
          :event-id="eventId"
          :matter-id="matterId"
          :show-financial-fields="showFinancialFields"
          :default-assignee="defaultAssignee"
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
import TaskForm from '@/Components/forms/TaskForm.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  task: {
    type: Object,
    default: null
  },
  eventId: {
    type: Number,
    default: null
  },
  matterId: {
    type: Number,
    default: null
  },
  showFinancialFields: {
    type: Boolean,
    default: false
  },
  defaultAssignee: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:open', 'success'])

const dialogTitle = computed(() => {
  return props.task ? 'Edit Task' : 'Create Task'
})

const dialogDescription = computed(() => {
  if (props.task) {
    return 'Update task details and completion status'
  }
  
  if (props.eventId) {
    return 'Create a new task for this event'
  }
  
  if (props.matterId) {
    return 'Create a new task for this matter'
  }
  
  return 'Create a new task'
})

const handleSuccess = (response) => {
  // Emit success to parent and let parent handle closing
  emit('success', response)
}
</script>