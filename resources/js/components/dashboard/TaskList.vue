<template>
  <DataTable
      :columns="columns"
      :data="tasks"
      :loading="false"
      :selectable="permissions.canWrite"
      :show-pagination="false"
      :empty-message="t('dashboard.tasks.no_tasks_found')"
      :get-row-id="(row) => row.id"
      :get-row-class="getRowClass"
      @update:selected="handleSelection"
  />
</template>

<script setup>
import {h} from 'vue'
import {Link} from '@inertiajs/vue3'
import {format, parseISO, isPast, isBefore, addDays, formatDistanceToNow} from 'date-fns'
import {CalendarDays, Clock, AlertCircle} from 'lucide-vue-next'
import DataTable from '@/Components/ui/DataTable.vue'
import StatusBadge from '@/Components/display/StatusBadge.vue'
import { useI18n } from 'vue-i18n'
import {useTranslations} from "@/composables/useTranslations.js";

const props = defineProps({
  tasks: {
    type: Array,
    default: () => []
  },
  permissions: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:selected'])

const { t } = useTranslations()

// Table columns definition
const columns = [
  {
    accessorKey: 'matter.uid',
    header: t('dashboard.table.matter'),
    cell: ({row}) => h('div', {class: 'flex flex-col gap-1'}, [
      h(Link, {
        href: route('matter.show', {matter: row.original.matter.id}),
        class: 'text-primary hover:underline text-sm font-medium'
      }, row.original.matter?.uid || `#${row.original.matter_id}`),
      h('span', {class: 'text-xs text-muted-foreground'}, `${t('dashboard.table.id')}: ${row.original.id}`)
    ]),
    meta: {
      headerClass: 'w-[120px]',
    }
  },
  {
    accessorKey: 'info.name',
    header: t('dashboard.tasks.header'),
    cell: ({row}) => {
      const task = row.original
      const status = getTaskStatus(task)

      return h('div', {class: 'space-y-2 py-1'}, [
        // Task name and status badge
        h('div', {class: 'flex items-center gap-2'}, [
          h('span', {class: 'font-medium'}, task.info?.name || task.code),
          h(StatusBadge, {status, type: 'task'})
        ]),
        // Task detail if exists
        task.detail && h('p', {class: 'text-sm text-muted-foreground'}, task.detail),
        // Assigned to
        task.assigned_to && h('div', {class: 'flex items-center gap-1 text-xs text-muted-foreground'}, [
          h('span', `${t('dashboard.tasks.assigned_to')}:`),
          h('span', {class: 'font-medium'}, task.assigned_to)
        ])
      ])
    }
  },
  {
    accessorKey: 'due_date',
    header: t('dashboard.tasks.due_date'),
    cell: ({row}) => {
      const date = row.original.due_date
      if (!date) return h('span', {class: 'text-sm text-muted-foreground'}, t('dashboard.table.no_due_date'))

      const overdue = isOverdue(date)
      const dueSoon = isDueSoon(date) && !overdue
      const dueToday = isToday(date)

      const IconComponent = overdue ? AlertCircle : (dueSoon || dueToday) ? Clock : CalendarDays
      const dateColor = overdue ? 'text-destructive' : (dueSoon || dueToday) ? 'text-warning-foreground' : 'text-muted-foreground'

      return h('div', {class: 'flex flex-col gap-1'}, [
        h('div', {class: `flex items-center gap-1.5 ${dateColor}`}, [
          h(IconComponent, {class: 'h-4 w-4'}),
          h('span', {class: 'text-sm font-medium'}, formatDate(date))
        ]),
        h('span', {class: 'text-xs text-muted-foreground'}, getRelativeTime(date))
      ])
    },
    meta: {
      headerClass: 'w-[150px]',
    }
  }
]

// Handle selection updates
const handleSelection = (selectedRows) => {
  emit('update:selected', selectedRows.map(row => row.id))
}

// Date formatting utilities
const formatDate = (date) => {
  if (!date) return ''
  try {
    return format(parseISO(date), 'dd/MM/yyyy')
  } catch {
    return date
  }
}

const getRelativeTime = (date) => {
  if (!date) return ''
  try {
    const parsedDate = parseISO(date)
    const now = new Date()
    const daysDiff = Math.floor((parsedDate - now) / (1000 * 60 * 60 * 24))

    if (daysDiff === 0) return t('dashboard.table.due_today')
    if (daysDiff === 1) return t('dashboard.table.due_tomorrow')
    if (daysDiff === -1) return t('dashboard.table.due_yesterday')
    if (daysDiff > 0 && daysDiff <= 7) return t('dashboard.table.due_in_days', daysDiff, { days: daysDiff })
    if (daysDiff < 0) return formatDistanceToNow(parsedDate, {addSuffix: true})

    return ''
  } catch {
    return ''
  }
}

const isOverdue = (date) => {
  if (!date) return false
  try {
    const parsedDate = parseISO(date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return parsedDate < today
  } catch {
    return false
  }
}

const isDueSoon = (date) => {
  if (!date) return false
  try {
    const parsedDate = parseISO(date)
    const today = new Date()
    const weekFromNow = addDays(today, 7)
    return parsedDate >= today && parsedDate <= weekFromNow
  } catch {
    return false
  }
}

const isToday = (date) => {
  if (!date) return false
  try {
    const parsedDate = parseISO(date)
    const today = new Date()
    return parsedDate.toDateString() === today.toDateString()
  } catch {
    return false
  }
}

const getTaskStatus = (task) => {
  if (task.done) return 'done'
  if (task.due_date && isOverdue(task.due_date)) return 'overdue'
  return 'pending'
}

const getRowClass = (task) => {
  const classes = []

  if (task.done) {
    classes.push('opacity-60')
  } else if (task.due_date && isOverdue(task.due_date)) {
    classes.push('bg-destructive/5 hover:bg-destructive/10')
  } else if (task.due_date && isDueSoon(task.due_date)) {
    classes.push('bg-warning/5 hover:bg-warning/10')
  } else if (task.due_date && isToday(task.due_date)) {
    classes.push('bg-warning/10 hover:bg-warning/15')
  }

  return classes.join(' ')
}
</script>