<template>
  <DataTable
      :data="renewals"
      :columns="columns"
      :loading="false"
      :show-pagination="false"
      :empty-message="$t('No renewals due')"
  />
</template>

<script setup>
import {Link} from '@inertiajs/vue3'
import {format, isPast, addDays} from 'date-fns'
import {h} from 'vue'
import {useI18n} from 'vue-i18n'
import {Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card'
import {Badge} from '@/components/ui/badge'
import DataTable from '@/components/ui/DataTable.vue'

const props = defineProps({
  renewals: Array,
  matterId: [String, Number]
})

const {t} = useI18n()

function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}

function getRenewalStatus(renewal) {
  if (renewal.done) return 'done'
  const dueDate = new Date(renewal.due_date)
  if (isPast(dueDate)) return 'overdue'
  if (isPast(addDays(dueDate, -30))) return 'warning'
  return 'open'
}

// Define columns for DataTable
const columns = [
  {
    id: 'detail',
    accessorKey: 'detail',
    header: t('Renewal'),
    cell: ({row}) => {
      const renewal = row.original
      return h('div', {class: 'space-y-1'}, [
        h('div', {class: 'font-medium'}, renewal.detail || `Year ${renewal.recur_years || '?'}`),
        h('div', {class: 'text-sm text-muted-foreground'},
            renewal.info?.name || t('Renewal')
        )
      ])
    }
  },
  {
    id: 'due_date',
    accessorKey: 'due_date',
    header: t('Due date'),
    cell: ({row}) => {
      const renewal = row.original
      const status = getRenewalStatus(renewal)
      const classes = {
        'overdue': 'text-red-600 font-medium',
        'warning': 'text-yellow-600 font-medium',
        'done': 'text-muted-foreground line-through',
        'open': ''
      }
      return h('span', {class: classes[status]}, formatDate(renewal.due_date))
    }
  },
]
</script>