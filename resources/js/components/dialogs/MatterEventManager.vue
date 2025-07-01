<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>Manage Events</DialogTitle>
        <DialogDescription>
          Add, edit, or remove events from this matter
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-6 max-h-[70vh] overflow-y-auto">
        <!-- Add Event Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Add Event</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleAddEvent" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <FormField
                  label="Event"
                  name="code"
                  :error="addForm.errors.code"
                  required
                >
                  <AutocompleteInput
                    v-model="addForm.code"
                    v-model:display-model-value="eventDisplay"
                    endpoint="/event-name/autocomplete"
                    placeholder="Select event"
                    :min-length="0"
                    value-key="key"
                    label-key="value"
                  />
                </FormField>

                <FormField
                  label="Date"
                  name="event_date"
                  :error="addForm.errors.event_date"
                  required
                >
                  <DatePicker
                    v-model="addForm.event_date"
                    placeholder="Select date"
                  />
                </FormField>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <FormField
                  label="Detail"
                  name="detail"
                  :error="addForm.errors.detail"
                >
                  <Input
                    v-model="addForm.detail"
                    placeholder="Event detail (optional)"
                  />
                </FormField>

                <FormField
                  label="Link"
                  name="link"
                  :error="addForm.errors.link"
                >
                  <Input
                    v-model="addForm.link"
                    placeholder="URL (optional)"
                  />
                </FormField>
              </div>

              <FormField
                label="Notes"
                name="notes"
                :error="addForm.errors.notes"
              >
                <Textarea
                  v-model="addForm.notes"
                  placeholder="Additional notes..."
                  rows="2"
                />
              </FormField>

              <Button type="submit" :disabled="addForm.processing">
                <CalendarPlus class="mr-2 h-4 w-4" />
                Add Event
              </Button>
            </form>
          </CardContent>
        </Card>

        <!-- Current Events Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Current Events</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="events && events.length > 0" class="space-y-2">
              <div
                v-for="event in sortedEvents"
                :key="event.id"
                class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
              >
                <div class="flex items-center gap-3 flex-1">
                  <component 
                    :is="getEventIcon(event)" 
                    class="h-5 w-5 text-muted-foreground flex-shrink-0"
                  />
                  <div class="flex-1">
                    <div class="font-medium">
                      {{ event.event_name }}
                      <span v-if="event.detail" class="text-muted-foreground">
                        - {{ event.detail }}
                      </span>
                    </div>
                    <div class="text-sm text-muted-foreground">
                      {{ formatDate(event.event_date) }}
                      <span v-if="event.alt_matter_id" class="ml-2">
                        (linked to {{ event.alt_matter?.uid }})
                      </span>
                    </div>
                    <div v-if="event.notes" class="text-sm text-muted-foreground mt-1">
                      {{ event.notes }}
                    </div>
                  </div>
                  <a
                    v-if="event.link"
                    :href="event.link"
                    target="_blank"
                    class="text-primary hover:underline"
                  >
                    <ExternalLink class="h-4 w-4" />
                  </a>
                </div>
                <div class="flex items-center gap-2 ml-4">
                  <Button
                    variant="ghost"
                    size="icon"
                    @click="editEvent(event)"
                  >
                    <Pencil class="h-4 w-4" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    @click="handleRemoveEvent(event)"
                    :disabled="removingEventId === event.id"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-muted-foreground">
              No events recorded for this matter
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          Close
        </Button>
      </DialogFooter>
    </DialogContent>

    <!-- Edit Event Dialog -->
    <EventDialog
      v-model:open="showEditDialog"
      :event="selectedEvent"
      :matter-id="matter.id"
      @success="handleEventUpdated"
    />
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { 
  CalendarPlus, 
  Trash2, 
  Pencil,
  ExternalLink,
  Calendar,
  FileText,
  CheckCircle,
  AlertCircle,
  Clock,
  Mail,
  DollarSign
} from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'
import EventDialog from '@/Components/dialogs/EventDialog.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matter: {
    type: Object,
    required: true
  },
  events: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:open', 'success'])

// State
const eventDisplay = ref('')
const removingEventId = ref(null)
const showEditDialog = ref(false)
const selectedEvent = ref(null)

// Forms
const addForm = useForm({
  matter_id: props.matter.id,
  code: '',
  event_date: '',
  detail: '',
  link: '',
  notes: ''
})

// Computed
const sortedEvents = computed(() => {
  return [...props.events].sort((a, b) => 
    new Date(b.event_date) - new Date(a.event_date)
  )
})

function getEventIcon(event) {
  const code = event.code?.toLowerCase() || ''
  
  if (code.includes('fil')) return FileText
  if (code.includes('pub')) return Calendar
  if (code.includes('gra') || code.includes('reg')) return CheckCircle
  if (code.includes('rej') || code.includes('ref')) return AlertCircle
  if (code.includes('deadline') || code.includes('due')) return Clock
  if (code.includes('mail') || code.includes('send')) return Mail
  if (code.includes('pay') || code.includes('fee')) return DollarSign
  
  return Calendar
}

function handleAddEvent() {
  addForm.post(`/matter/${props.matter.id}/events`, {
    onSuccess: () => {
      // Reset form
      addForm.reset()
      eventDisplay.value = ''
      
      // Reload matter data
      emit('success')
    }
  })
}

function editEvent(event) {
  selectedEvent.value = event
  showEditDialog.value = true
}

function handleEventUpdated() {
  showEditDialog.value = false
  selectedEvent.value = null
  emit('success')
}

function handleRemoveEvent(event) {
  if (confirm(`Remove event "${event.event_name}" from this matter?`)) {
    removingEventId.value = event.id
    
    router.delete(`/event/${event.id}`, {
      onSuccess: () => {
        removingEventId.value = null
        emit('success')
      },
      onError: () => {
        removingEventId.value = null
      }
    })
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}
</script>