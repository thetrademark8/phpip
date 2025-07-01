<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ mode === 'create' ? 'Add Event' : 'Edit Event' }}</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="space-y-2">
          <Label htmlFor="code">Event Type</Label>
          <AutocompleteInput
            id="code"
            v-model="form.code"
            v-model:display-model-value="eventDisplay"
            placeholder="Select event type..."
            endpoint="/event-name/autocomplete/0"
            value-key="key"
            label-key="value"
            :min-length="0"
            required
            @selected="(item) => { form.eventName = item.value }"
          />
        </div>
        <div class="space-y-2">
          <Label htmlFor="event_date">Event Date</Label>
          <DatePicker
            id="event_date"
            v-model="form.event_date"
            placeholder="Select date..."
            required
          />
        </div>
        <div class="space-y-2">
          <Label htmlFor="detail">Detail</Label>
          <Input
            id="detail"
            v-model="form.detail"
            placeholder="Event details..."
          />
        </div>
        <div class="space-y-2">
          <Label htmlFor="notes">Notes</Label>
          <Textarea
            id="notes"
            v-model="form.notes"
            placeholder="Additional notes..."
            rows="3"
          />
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('update:open', false)">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            {{ mode === 'create' ? 'Add Event' : 'Update Event' }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matterId: {
    type: [String, Number],
    required: true
  },
  event: {
    type: Object,
    default: null
  },
  mode: {
    type: String,
    default: 'create'
  }
})

const emit = defineEmits(['update:open', 'success'])

const eventDisplay = ref('')

const form = useForm({
  matter_id: props.matterId,
  code: '',
  eventName: '',
  event_date: '',
  detail: '',
  notes: ''
})

// Watch for event changes to populate form
watch(() => props.event, (newEvent) => {
  if (newEvent) {
    form.code = newEvent.code || ''
    form.event_date = newEvent.event_date || ''
    form.detail = newEvent.detail || ''
    form.notes = newEvent.notes || ''
    eventDisplay.value = newEvent.event_name || ''
  } else {
    form.reset()
    eventDisplay.value = ''
  }
}, { immediate: true })

function handleSubmit() {
  if (props.mode === 'create') {
    form.post(`/matter/${props.matterId}/events`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    form.put(`/event/${props.event.id}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  }
}
</script>