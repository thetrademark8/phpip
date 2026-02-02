<template>
  <div>
    <DataTable
      :columns="columns"
      :data="paginatedRenewals"
      :loading="false"
      :selectable="permissions.canWrite"
      :show-pagination="false"
      :empty-message="t('dashboard.renewals.no_renewals_found')"
      :get-row-id="(row) => row.id"
      :get-row-class="getRowClass"
      @update:selected="handleSelection"
    />

    <!-- Pagination Controls -->
    <div v-if="renewals.length > perPage" class="flex items-center justify-between px-4 py-3 border-t">
      <p class="text-sm text-muted-foreground">
        {{ t('dashboard.pagination.showing', { from: paginationFrom, to: paginationTo, total: renewals.length }) }}
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
import { h, ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { format, parseISO, isPast, isBefore, addDays, formatDistanceToNow, isToday as isTodayDateFns } from 'date-fns'
import { fr, de, enUS } from 'date-fns/locale'
import { CalendarDays, Clock, AlertCircle, DollarSign, ChevronLeft, ChevronRight } from 'lucide-vue-next'
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
  renewals: {
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

const totalPages = computed(() => Math.ceil(props.renewals.length / props.perPage))

const paginatedRenewals = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  const end = start + props.perPage
  return props.renewals.slice(start, end)
})

const paginationFrom = computed(() => (currentPage.value - 1) * props.perPage + 1)
const paginationTo = computed(() => Math.min(currentPage.value * props.perPage, props.renewals.length))

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
    cell: ({ row }) => h('div', { class: 'flex flex-col gap-1' }, [
      h(Link, {
        href: `/matter/${row.original.matter_id}`,
        class: 'text-primary hover:underline text-sm font-medium'
      }, row.original.matter?.uid || `#${row.original.matter_id}`),
      h('span', { class: 'text-xs text-muted-foreground' }, `${t('dashboard.renewals.renewal_id')}: ${row.original.id}`)
    ]),
    meta: {
      headerClass: 'w-[130px]',
    }
  },
  {
    accessorKey: 'matter.titles',
    header: t('dashboard.renewals.title'),
    cell: ({ row }) => {
      const title = getMatterTitle(row.original.matter)
      if (!title) return h('span', { class: 'text-sm text-muted-foreground italic' }, '-')
      return h('span', { class: 'text-sm line-clamp-2' }, title)
    },
    meta: {
      headerClass: 'w-[200px]',
    }
  },
  {
    accessorKey: 'info.name',
    header: t('dashboard.renewals.header'),
    cell: ({ row }) => {
      const renewal = row.original
      const renewalName = translated(renewal.info?.name) || renewal.code || t('dashboard.renewals.renewal')
      const renewalDetail = translated(renewal.detail)

      return h('div', { class: 'space-y-2 py-1' }, [
        // Renewal name and badge
        h('div', { class: 'flex items-center gap-2' }, [
          h('span', { class: 'font-medium' }, renewalName),
          h(StatusBadge, { status: 'open', type: 'renewal' })
        ]),
        // Renewal detail if exists
        renewalDetail && h('p', { class: 'text-sm text-muted-foreground' }, renewalDetail),
        // Cost information
        renewal.cost && h('div', { class: 'flex items-center gap-1 text-sm' }, [
          h(DollarSign, { class: 'h-4 w-4 text-muted-foreground' }),
          h('span', { class: 'font-medium' }, formatCurrency(renewal.cost))
        ])
      ])
    }
  },
  {
    accessorKey: 'due_date',
    header: t('dashboard.renewals.due_date'),
    cell: ({ row }) => {
      const date = row.original.due_date
      if (!date) return h('span', { class: 'text-sm text-muted-foreground' }, t('dashboard.table.no_due_date'))
      
      const overdue = isOverdue(date)
      const dueSoon = isDueSoon(date) && !overdue
      const dueToday = isToday(date)
      
      const IconComponent = overdue ? AlertCircle : (dueSoon || dueToday) ? Clock : CalendarDays
      const dateColor = overdue ? 'text-destructive' : (dueSoon || dueToday) ? 'text-warning-foreground' : 'text-muted-foreground'
      
      return h('div', { class: 'flex flex-col gap-1' }, [
        h('div', { class: `flex items-center gap-1.5 ${dateColor}` }, [
          h(IconComponent, { class: 'h-4 w-4' }),
          h('span', { class: 'text-sm font-medium' }, formatDate(date))
        ]),
        h('span', { class: 'text-xs text-muted-foreground' }, getRelativeTime(date))
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

const formatCurrency = (amount) => {
  if (!amount) return ''
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const isOverdue = (date) => {
  if (!date) return false
  try {
    return isPast(parseISO(date))
  } catch {
    return false
  }
}

const isDueSoon = (date) => {
  if (!date) return false
  try {
    return isBefore(parseISO(date), addDays(new Date(), 7))
  } catch {
    return false
  }
}

const isToday = (date) => {
  if (!date) return false
  try {
    return isTodayDateFns(parseISO(date))
  } catch {
    return false
  }
}

const getRelativeTime = (date) => {
  if (!date) return ''
  try {
    return formatDistanceToNow(parseISO(date), { addSuffix: true, locale: getDateLocale() })
  } catch {
    return date
  }
}

const getRowClass = (row) => {
  const date = row.due_date
  if (!date) return ''
  
  if (isOverdue(date)) {
    return 'bg-destructive/5'
  } else if (isDueSoon(date)) {
    return 'bg-warning/5'
  }
  return ''
}
</script>