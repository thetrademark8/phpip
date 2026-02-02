<template>
  <div>
    <DataTable
        :columns="columns"
        :data="paginatedTasks"
        :loading="false"
        :selectable="permissions.canWrite"
        :show-pagination="false"
        :empty-message="t('dashboard.tasks.no_tasks_found')"
        :get-row-id="(row) => row.id"
        :get-row-class="getRowClass"
        @update:selected="handleSelection"
    />

    <!-- Pagination Controls -->
    <div v-if="tasks.length > perPage" class="flex items-center justify-between px-4 py-3 border-t">
      <p class="text-sm text-muted-foreground">
        {{ t('dashboard.pagination.showing', { from: paginationFrom, to: paginationTo, total: tasks.length }) }}
      </p>
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          <ChevronLeft class="h-4 w-4" />
          {{ t('dashboard.pagination.previous') }}
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
        >
          {{ t('dashboard.pagination.next') }}
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {h, computed, ref} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {format, parseISO, isPast, isBefore, addDays, formatDistanceToNow} from 'date-fns'
import {fr, de, enUS} from 'date-fns/locale'
import {CalendarDays, Clock, AlertCircle, ChevronLeft, ChevronRight} from 'lucide-vue-next'
import DataTable from '@/components/ui/DataTable.vue'
import { Button } from '@/components/ui/button'
import StatusBadge from '@/components/display/StatusBadge.vue'
import { useTranslatedField } from '@/composables/useTranslation.js'

// Date-fns locale mapping
const dateFnsLocales = { fr, de, en: enUS }
const getDateLocale = () => {
  const pageLocale = usePage().props.locale || 'en'
  return dateFnsLocales[pageLocale] || enUS
}

const props = defineProps({
  tasks: {
    type: Array,
    default: () => []
  },
  permissions: {
    type: Object,
    required: true
  },
  perPage: {
    type: Number,
    default: 10
  }
})

const emit = defineEmits(['update:selected'])

const { t, translated } = useTranslatedField()

// Pagination state
const currentPage = ref(1)

const totalPages = computed(() => Math.ceil(props.tasks.length / props.perPage))

const paginatedTasks = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  const end = start + props.perPage
  return props.tasks.slice(start, end)
})

const paginationFrom = computed(() => (currentPage.value - 1) * props.perPage + 1)
const paginationTo = computed(() => Math.min(currentPage.value * props.perPage, props.tasks.length))

// Helper function to get matter title
const getMatterTitle = (matter) => {
  if (!matter?.titles || matter.titles.length === 0) return null
  return matter.titles[0]?.value || null
}

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
    accessorKey: 'matter.titles',
    header: t('dashboard.tasks.title'),
    cell: ({row}) => {
      const title = getMatterTitle(row.original.matter)
      if (!title) return h('span', {class: 'text-sm text-muted-foreground italic'}, '-')
      return h('span', {class: 'text-sm line-clamp-2'}, title)
    },
    meta: {
      headerClass: 'w-[200px]',
    }
  },
  {
    accessorKey: 'info.name',
    header: t('dashboard.tasks.description'),
    cell: ({row}) => {
      const task = row.original
      const taskName = translated(task.info?.name) || task.code
      const taskDetail = translated(task.detail)

      return h('div', {class: 'flex flex-col gap-1'}, [
        h('span', {class: 'text-sm font-medium'}, taskName),
        taskDetail && h('span', {class: 'text-xs text-muted-foreground line-clamp-1'}, taskDetail)
      ])
    },
    meta: {
      headerClass: 'w-[180px]',
    }
  },
  {
    accessorKey: 'status',
    header: t('dashboard.tasks.header'),
    cell: ({row}) => {
      const task = row.original
      const status = getTaskStatus(task)

      return h('div', {class: 'space-y-2 py-1'}, [
        // Status badge
        h('div', {class: 'flex items-center gap-2'}, [
          h(StatusBadge, {status, type: 'task'})
        ]),
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
    if (daysDiff < 0) return formatDistanceToNow(parsedDate, { addSuffix: true, locale: getDateLocale() })

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