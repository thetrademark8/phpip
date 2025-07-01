<template>
  <div
    :class="cn(
      'rounded-lg border bg-card p-4 text-card-foreground shadow-sm',
      isSelected && 'ring-2 ring-primary',
      props.class
    )"
    @click="handleClick"
  >
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-lg">{{ matter.caseref }}</h3>
          <StatusBadge 
            v-if="matter.dead !== undefined"
            :status="matter.dead ? 'dead' : 'active'"
            type="matter"
          />
        </div>
        
        <p v-if="displayTitle" class="text-sm text-muted-foreground mt-1">
          {{ displayTitle }}
        </p>
        
        <div class="flex flex-wrap gap-4 mt-3 text-sm">
          <div v-if="matter.category">
            <span class="text-muted-foreground">Category:</span>
            <span class="ml-1 font-medium">{{ matter.category.name || matter.category }}</span>
          </div>
          
          <div v-if="matter.country">
            <span class="text-muted-foreground">Country:</span>
            <span class="ml-1 font-medium">{{ matter.country }}</span>
          </div>
          
          <div v-if="matter.responsible">
            <span class="text-muted-foreground">Responsible:</span>
            <span class="ml-1 font-medium">{{ matter.responsible }}</span>
          </div>
        </div>
        
        <div v-if="matter.client" class="mt-2 text-sm">
          <span class="text-muted-foreground">Client:</span>
          <span class="ml-1 font-medium">{{ clientName }}</span>
        </div>
      </div>
      
      <div v-if="showActions" class="ml-4">
        <slot name="actions" />
      </div>
    </div>
    
    <div v-if="showTasks && tasks.length > 0" class="mt-4 pt-4 border-t">
      <div class="text-sm font-medium mb-2">Upcoming Tasks</div>
      <div class="space-y-1">
        <div
          v-for="task in tasks.slice(0, 3)"
          :key="task.id"
          class="flex items-center justify-between text-sm"
        >
          <span class="text-muted-foreground">{{ task.info?.name || task.code }}</span>
          <span class="font-medium">{{ formatDate(task.due_date) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { format, parseISO } from 'date-fns'
import { cn } from '@/lib/utils'
import StatusBadge from './StatusBadge.vue'

const props = defineProps({
  matter: {
    type: Object,
    required: true
  },
  tasks: {
    type: Array,
    default: () => []
  },
  showActions: {
    type: Boolean,
    default: false
  },
  showTasks: {
    type: Boolean,
    default: false
  },
  isSelected: {
    type: Boolean,
    default: false
  },
  class: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['click'])

const displayTitle = computed(() => {
  if (props.matter.titles && props.matter.titles.length > 0) {
    return props.matter.titles[0].value
  }
  return props.matter.title || ''
})

const clientName = computed(() => {
  if (typeof props.matter.client === 'object' && props.matter.client) {
    return props.matter.client.name || props.matter.client.display_name
  }
  return props.matter.client
})

const formatDate = (date) => {
  if (!date) return ''
  try {
    const parsed = typeof date === 'string' ? parseISO(date) : date
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

const handleClick = () => {
  emit('click', props.matter)
}
</script>