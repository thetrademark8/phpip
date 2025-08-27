<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('Mark Task as Done') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Set the completion date for this task.') }}
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <div class="space-y-2">
            <Label class="mb-2">{{ $t('Task') }}</Label>
            <div class="text-sm text-muted-foreground">
              {{ task?.info?.name || task?.code }}
              <div v-if="task?.detail" class="mt-1">{{ task.detail }}</div>
            </div>
          </div>
          
          <div class="space-y-2">
            <Label for="done_date" class="mb-2">{{ $t('Completion Date') }}</Label>
            <DateInput
              id="done_date"
              v-model="form.done_date"
              :error="form.errors.done_date"
              show-label=""
            />
          </div>
        </div>
        
        <DialogFooter class="mt-6">
          <Button
            type="button"
            variant="outline"
            @click="$emit('update:open', false)"
            :disabled="form.processing"
          >
            {{ $t('Cancel') }}
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
          >
            <template v-if="form.processing">
              <Loader2 class="mr-2 h-4 w-4 animate-spin" />
              {{ $t('Saving...') }}
            </template>
            <template v-else>
              <Check class="mr-2 h-4 w-4" />
              {{ $t('Mark as Done') }}
            </template>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { Check, Loader2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import DateInput from '@/components/ui/date-picker/DatePicker.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  task: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:open', 'success'])

// Form
const form = useForm({
  done: true,
  done_date: format(new Date(), 'yyyy-MM-dd')
})

// Watch for task changes
watch(() => props.task, (newTask) => {
  if (newTask) {
    // Set current date as default
    form.reset({
      done: true,
      done_date: format(new Date(), 'yyyy-MM-dd')
    })
  }
})

// Watch for dialog open state
watch(() => props.open, (isOpen) => {
  if (isOpen && props.task) {
    // Reset form with current date when dialog opens
    form.reset({
      done: true,
      done_date: format(new Date(), 'yyyy-MM-dd')
    })
  }
})

// Submit
function handleSubmit() {
  form.patch(`/task/${props.task.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    }
  })
}
</script>