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
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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

// Status class configurations (only styling, no text)
const statusClasses = {
  task: {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    done: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800'
  },
  matter: {
    active: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    inactive: 'bg-gray-100 text-gray-800',
    dead: 'bg-red-100 text-red-800'
  },
  renewal: {
    open: 'bg-blue-100 text-blue-800',
    instructed: 'bg-purple-100 text-purple-800',
    invoiced: 'bg-orange-100 text-orange-800',
    paid: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
}

const statusClass = computed(() => {
  return statusClasses[props.type]?.[props.status.toLowerCase()] || 'bg-gray-100 text-gray-800'
})

const displayText = computed(() => {
  if (props.customText) return props.customText

  // Use translation key based on type and status
  const translationKey = `status.${props.type}.${props.status.toLowerCase()}`
  return t(translationKey)
})
</script>