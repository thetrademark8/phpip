<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-4">
      <!-- Task Code/Type -->
      <FormField
        label="Task Type"
        name="code"
        :error="form.errors.code"
        required
      >
        <AutocompleteInput
          v-model="form.code"
          v-model:display-model-value="taskTypeDisplay"
          endpoint="/event-name/autocomplete/1"
          placeholder="Select task type"
          value-key="key"
          label-key="value"
          @selected="handleTaskTypeSelect"
        />
      </FormField>

      <!-- Due Date -->
      <FormField
        label="Due Date"
        name="due_date"
        :error="form.errors.due_date"
        required
      >
        <DatePicker
          v-model="form.due_date"
          placeholder="Select due date"
        />
      </FormField>

      <!-- Assigned To -->
      <FormField
        label="Assigned To"
        name="assigned_to"
        :error="form.errors.assigned_to"
      >
        <AutocompleteInput
          v-model="form.assigned_to"
          v-model:display-model-value="assignedToDisplay"
          endpoint="/user/autocomplete"
          placeholder="Select assignee"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Detail/Notes -->
      <FormField
        label="Detail"
        name="detail"
        :error="form.errors.detail"
      >
        <Textarea
          v-model="form.detail"
          placeholder="Task details or notes"
          :rows="3"
          class="resize-none"
        />
      </FormField>

      <!-- Status fields (for existing tasks) -->
      <div v-if="task" class="space-y-4">
        <!-- Done checkbox -->
        <div class="flex items-center space-x-2">
          <Checkbox
            v-model:checked="form.done"
            id="done"
            @update:checked="handleDoneChange"
          />
          <Label for="done">Task completed</Label>
        </div>

        <!-- Done Date (shown when done is checked) -->
        <FormField
          v-if="form.done"
          label="Completion Date"
          name="done_date"
          :error="form.errors.done_date"
        >
          <DatePicker
            v-model="form.done_date"
            placeholder="Select completion date"
          />
        </FormField>
      </div>

      <!-- Cost and Fee fields (for renewals) -->
      <div v-if="showFinancialFields" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <FormField
          label="Cost"
          name="cost"
          :error="form.errors.cost"
        >
          <Input
            v-model="form.cost"
            type="number"
            step="0.01"
            placeholder="0.00"
          />
        </FormField>

        <FormField
          label="Fee"
          name="fee"
          :error="form.errors.fee"
        >
          <Input
            v-model="form.fee"
            type="number"
            step="0.01"
            placeholder="0.00"
          />
        </FormField>

        <FormField
          label="Currency"
          name="currency"
          :error="form.errors.currency"
        >
          <Select v-model="form.currency">
            <SelectTrigger>
              <SelectValue placeholder="Select currency" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="USD">USD</SelectItem>
              <SelectItem value="EUR">EUR</SelectItem>
              <SelectItem value="GBP">GBP</SelectItem>
              <SelectItem value="JPY">JPY</SelectItem>
              <SelectItem value="CHF">CHF</SelectItem>
            </SelectContent>
          </Select>
        </FormField>
      </div>

      <!-- Submit buttons -->
      <div class="flex justify-end space-x-2">
        <Button
          v-if="onCancel"
          type="button"
          variant="outline"
          @click="onCancel"
          :disabled="form.processing"
        >
          Cancel
        </Button>
        <Button
          type="submit"
          :disabled="form.processing"
        >
          <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ task ? 'Update' : 'Create' }}
        </Button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import { Checkbox } from '@/Components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import { DatePicker } from '@/Components/ui/date-picker'

const props = defineProps({
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
  },
  onSuccess: {
    type: Function,
    default: null
  },
  onCancel: {
    type: Function,
    default: null
  }
})

// Initialize form
const form = useForm({
  code: props.task?.code || '',
  due_date: props.task?.due_date || '',
  assigned_to: props.task?.assigned_to || props.defaultAssignee || '',
  detail: props.task?.detail || '',
  done: props.task?.done || false,
  done_date: props.task?.done_date || '',
  cost: props.task?.cost || '',
  fee: props.task?.fee || '',
  currency: props.task?.currency || 'EUR',
  trigger_id: props.task?.trigger_id || props.eventId || '',
  matter_id: props.matterId || ''
})

// Display values
const taskTypeDisplay = ref(props.task?.info?.name || '')
const assignedToDisplay = ref('')

// Handle task type selection
const handleTaskTypeSelect = (taskType) => {
  if (taskType) {
    taskTypeDisplay.value = taskType.value
  }
}

// Handle done checkbox change
const handleDoneChange = (checked) => {
  if (checked && !form.done_date) {
    // Set today's date when marking as done
    form.done_date = format(new Date(), 'yyyy-MM-dd')
  } else if (!checked) {
    // Clear done date when unchecking
    form.done_date = ''
  }
}

// Handle form submission
const handleSubmit = () => {
  if (props.task) {
    // Update existing task
    form.put(`/task/${props.task.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        if (props.onSuccess) {
          props.onSuccess(page)
        }
      }
    })
  } else {
    // Create new task
    form.post('/task', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        if (props.onSuccess) {
          props.onSuccess(page)
        }
      }
    })
  }
}

// Watch for task changes
watch(() => props.task, (newTask) => {
  if (newTask) {
    Object.keys(form.data()).forEach(key => {
      if (newTask[key] !== undefined) {
        form[key] = newTask[key]
      }
    })
    // Update display values
    if (newTask.info) {
      taskTypeDisplay.value = newTask.info.name
    }
  }
}, { deep: true })
</script>