<template>
  <div class="relative">
    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-border"></div>
    
    <div class="space-y-6">
      <div
        v-for="(event, index) in sortedEvents"
        :key="event.id"
        class="relative flex items-start"
      >
        <!-- Timeline dot -->
        <div
          :class="cn(
            'absolute left-4 w-2 h-2 rounded-full -translate-x-1/2',
            index === 0 ? 'bg-primary' : 'bg-muted-foreground'
          )"
          :style="{ top: '0.75rem' }"
        ></div>
        
        <!-- Event content -->
        <div class="ml-10 flex-1">
          <div
            :class="cn(
              'rounded-lg border p-4',
              selectedEventId === event.id && 'ring-2 ring-primary'
            )"
            @click="handleEventClick(event)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h4 class="font-semibold">{{ event.info?.name ? translated(event.info.name) : event.code }}</h4>
                <p class="text-sm text-muted-foreground mt-1">
                  <EditableField
                    v-if="enableInlineEdit"
                    :model-value="event.event_date"
                    field="event_date"
                    type="date"
                    :url="updateUrl(event)"
                    :format="formatDate"
                    value-class="text-sm text-muted-foreground"
                    @saved="emit('update', { ...event, event_date: $event })"
                  />
                  <span v-else>{{ formatDate(event.event_date) }}</span>
                </p>
                
                <div v-if="event.detail || enableInlineEdit" class="mt-2 text-sm">
                  <EditableField
                    v-if="enableInlineEdit"
                    :model-value="event.detail || ''"
                    field="detail"
                    :url="updateUrl(event)"
                    placeholder="Add event details..."
                    value-class="text-sm"
                    @saved="emit('update', { ...event, detail: $event })"
                  />
                  <span v-else>{{ event.detail }}</span>
                </div>
                
                <!-- Show associated tasks -->
                <div v-if="showTasks && event.tasks && event.tasks.length > 0" class="mt-3 space-y-2">
                  <div class="text-sm font-medium">Tasks:</div>
                  <div
                    v-for="task in event.tasks"
                    :key="task.id"
                    class="flex items-center justify-between text-sm pl-4 border-l-2"
                  >
                    <span>{{ task.info?.name ? translated(task.info.name) : task.code }}</span>
                    <StatusBadge
                      :status="task.done ? 'done' : 'pending'"
                      type="task"
                    />
                  </div>
                </div>
              </div>
              
              <div v-if="editable" class="ml-4 flex items-center gap-2">
                <Button
                  size="icon"
                  variant="ghost"
                  @click.stop="emit('edit', event)"
                >
                  <Edit2 class="h-4 w-4" />
                </Button>
                <Button
                  size="icon"
                  variant="ghost"
                  @click.stop="emit('remove', event)"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="events.length === 0" class="text-center py-8 text-muted-foreground">
      No events to display
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { format, parseISO } from 'date-fns'
import { Edit2, Trash2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/Components/ui/button'
import StatusBadge from './StatusBadge.vue'
import InlineEdit from '@/Components/ui/InlineEdit.vue'
import EditableField from '@/Components/ui/EditableField.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  events: {
    type: Array,
    required: true
  },
  showTasks: {
    type: Boolean,
    default: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  selectedEventId: {
    type: [String, Number],
    default: null
  },
  enableInlineEdit: {
    type: Boolean,
    default: false
  },
  updateUrl: {
    type: Function,
    default: (event) => `/event/${event.id}`
  }
})

const emit = defineEmits(['click', 'edit', 'remove', 'update'])

const { translated } = useTranslatedField()

// Sort events by date (newest first)
const sortedEvents = computed(() => {
  return [...props.events].sort((a, b) => {
    const dateA = a.event_date ? new Date(a.event_date) : new Date(0)
    const dateB = b.event_date ? new Date(b.event_date) : new Date(0)
    return dateB - dateA
  })
})

const formatDate = (date) => {
  if (!date) return 'No date'
  try {
    const parsed = typeof date === 'string' ? parseISO(date) : date
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

const handleEventClick = (event) => {
  emit('click', event)
}
</script>