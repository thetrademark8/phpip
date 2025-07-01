<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>Manage Status Information</DialogTitle>
        <DialogDescription>
          Update status-related dates and numbers for this matter
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Status Events Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Status Events</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-for="event in statusEvents" :key="event.id" class="flex items-center gap-4">
              <div class="flex-1">
                <Label :for="`event-${event.id}`">{{ event.event_name }}</Label>
              </div>
              <div class="w-48">
                <DatePicker
                  :id="`event-${event.id}`"
                  v-model="eventDates[event.id]"
                  placeholder="Select date"
                />
              </div>
            </div>
            <div v-if="statusEvents.length === 0" class="text-muted-foreground text-sm">
              No status events configured for this matter type
            </div>
          </CardContent>
        </Card>

        <!-- Status Numbers Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Status Numbers</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <FormField
              label="Filing Number"
              name="filing_num"
            >
              <Input
                v-model="form.filing_num"
                placeholder="Enter filing number"
              />
            </FormField>

            <FormField
              label="Publication Number"
              name="pub_num"
            >
              <Input
                v-model="form.pub_num"
                placeholder="Enter publication number"
              />
            </FormField>

            <FormField
              label="Registration/Grant Number"
              name="reg_num"
            >
              <Input
                v-model="form.reg_num"
                placeholder="Enter registration number"
              />
            </FormField>
          </CardContent>
        </Card>

        <!-- Additional Information -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Additional Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <FormField
              label="Expiry Date"
              name="expire_date"
            >
              <DatePicker
                v-model="form.expire_date"
                placeholder="Select expiry date"
              />
            </FormField>

            <FormField
              label="Term Adjustment"
              name="term_adjust"
              help-text="Days to add/subtract from the standard term"
            >
              <Input
                v-model.number="form.term_adjust"
                type="number"
                placeholder="0"
              />
            </FormField>

            <FormField label="Status">
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="dead"
                  v-model="form.dead"
                />
                <Label htmlFor="dead" class="font-normal">
                  Mark as inactive/dead
                </Label>
              </div>
            </FormField>
          </CardContent>
        </Card>

        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('update:open', false)">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            Save Changes
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'
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
import { Label } from '@/Components/ui/label'
import { Checkbox } from '@/Components/ui/checkbox'
import FormField from '@/Components/ui/form/FormField.vue'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matter: {
    type: Object,
    required: true
  },
  statusEvents: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:open', 'success'])

// Form for matter fields
const form = useForm({
  filing_num: props.matter.filing_num || '',
  pub_num: props.matter.pub_num || '',
  reg_num: props.matter.reg_num || '',
  expire_date: props.matter.expire_date || '',
  term_adjust: props.matter.term_adjust || 0,
  dead: props.matter.dead || false
})

// Track event dates separately
const eventDates = ref({})

// Initialize event dates
watch(() => props.statusEvents, (events) => {
  if (events) {
    events.forEach(event => {
      eventDates.value[event.id] = event.event_date || ''
    })
  }
}, { immediate: true })

function handleSubmit() {
  // First update the matter fields
  form.put(`/matter/${props.matter.id}`, {
    onSuccess: () => {
      // Then update any changed event dates
      const updates = []
      Object.entries(eventDates.value).forEach(([eventId, date]) => {
        const event = props.statusEvents.find(e => e.id == eventId)
        if (event && event.event_date !== date) {
          updates.push({ id: eventId, date })
        }
      })
      
      if (updates.length > 0) {
        // Update events one by one
        Promise.all(updates.map(update => 
          fetch(`/event/${update.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ event_date: update.date })
          })
        )).then(() => {
          emit('success')
          emit('update:open', false)
        })
      } else {
        emit('success')
        emit('update:open', false)
      }
    }
  })
}
</script>