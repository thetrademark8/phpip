<template>
  <DataTable
    :columns="columns"
    :data="renewals"
    :loading="false"
    :selectable="permissions.canWrite"
    :show-pagination="false"
    :empty-message="t('dashboard.renewals.no_renewals_found')"
    :get-row-id="(row) => row.id"
    :get-row-class="getRowClass"
    @update:selected="handleSelection"
  />
</template>

<script setup>
import { h } from 'vue'
import { Link } from '@inertiajs/vue3'
import { format, parseISO, isPast, isBefore, addDays, formatDistanceToNow, isToday as isTodayDateFns } from 'date-fns'
import { CalendarDays, Clock, AlertCircle, DollarSign } from 'lucide-vue-next'
import DataTable from '@/components/ui/DataTable.vue'
import StatusBadge from '@/components/display/StatusBadge.vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  renewals: {
    type: Array,
    default: () => []
  },
  permissions: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:selected'])

const { t } = useI18n()

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
    accessorKey: 'info.name',
    header: t('dashboard.renewals.header'),
    cell: ({ row }) => {
      const renewal = row.original
      
      return h('div', { class: 'space-y-2 py-1' }, [
        // Renewal name and badge
        h('div', { class: 'flex items-center gap-2' }, [
          h('span', { class: 'font-medium' }, renewal.info?.name || renewal.code || t('dashboard.renewals.renewal')),
          h(StatusBadge, { status: 'open', type: 'renewal' })
        ]),
        // Renewal detail if exists
        renewal.detail && h('p', { class: 'text-sm text-muted-foreground' }, renewal.detail),
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
    return formatDistanceToNow(parseISO(date), { addSuffix: true })
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