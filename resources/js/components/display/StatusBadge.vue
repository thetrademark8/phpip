<template>
  <span
    :class="cn(
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
      statusClass
    )"
  >
    {{ displayText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'task' // 'task', 'matter', 'renewal'
  },
  customText: {
    type: String,
    default: ''
  }
})

// Status configurations
const statusConfigs = {
  task: {
    pending: { text: 'Pending', class: 'bg-yellow-100 text-yellow-800' },
    in_progress: { text: 'In Progress', class: 'bg-blue-100 text-blue-800' },
    completed: { text: 'Completed', class: 'bg-green-100 text-green-800' },
    done: { text: 'Done', class: 'bg-green-100 text-green-800' },
    overdue: { text: 'Overdue', class: 'bg-red-100 text-red-800' }
  },
  matter: {
    active: { text: 'Active', class: 'bg-green-100 text-green-800' },
    pending: { text: 'Pending', class: 'bg-yellow-100 text-yellow-800' },
    inactive: { text: 'Inactive', class: 'bg-gray-100 text-gray-800' },
    dead: { text: 'Dead', class: 'bg-red-100 text-red-800' }
  },
  renewal: {
    open: { text: 'Open', class: 'bg-blue-100 text-blue-800' },
    instructed: { text: 'Instructed', class: 'bg-purple-100 text-purple-800' },
    invoiced: { text: 'Invoiced', class: 'bg-orange-100 text-orange-800' },
    paid: { text: 'Paid', class: 'bg-green-100 text-green-800' },
    closed: { text: 'Closed', class: 'bg-gray-100 text-gray-800' }
  }
}

const statusClass = computed(() => {
  const config = statusConfigs[props.type]?.[props.status.toLowerCase()]
  return config?.class || 'bg-gray-100 text-gray-800'
})

const displayText = computed(() => {
  if (props.customText) return props.customText
  
  const config = statusConfigs[props.type]?.[props.status.toLowerCase()]
  return config?.text || props.status
})
</script>