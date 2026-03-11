<template>
  <DataTable
    :data="sortedEvents"
    :columns="columns"
    :loading="false"
    :show-pagination="sortedEvents.length > 5"
    :page-size="5"
    :empty-message="$t('No events to display')"
  />
</template>

<script setup>
import { computed, ref, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { format, parseISO } from 'date-fns'
import { RotateCw, Trash2 } from 'lucide-vue-next'
import DataTable from '@/components/ui/DataTable.vue'
import { Button } from '@/components/ui/button'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  events: Array,
  canWrite: {
    type: Boolean,
    default: false
  }
})

const { t } = useI18n()
const { translated } = useTranslatedField()

const recalculatingEventId = ref(null)

const sortedEvents = computed(() =>
  [...(props.events || [])].sort((a, b) => {
    const dateA = a.event_date ? new Date(a.event_date) : new Date(0)
    const dateB = b.event_date ? new Date(b.event_date) : new Date(0)
    return dateB - dateA
  })
)

function formatDate(date) {
  if (!date) return t('No date')
  try {
    const parsed = typeof date === 'string' ? parseISO(date) : date
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

function recalculateTasks(event) {
  recalculatingEventId.value = event.id
  router.post(`/event/${event.id}/recalculate-tasks`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['matter'] })
    },
    onFinish: () => {
      recalculatingEventId.value = null
    }
  })
}

function removeEvent(event) {
  if (confirm(t('Are you sure you want to remove this event?'))) {
    router.delete(`/event/${event.id}`, {
      preserveScroll: true,
      onSuccess: () => router.reload({ only: ['matter'] })
    })
  }
}

const columns = [
  {
    id: 'event',
    accessorKey: 'code',
    header: t('Event'),
    cell: ({ row }) => {
      const event = row.original
      return h('span', { class: 'font-medium' },
        event.info?.name ? translated(event.info.name) : event.code
      )
    }
  },
  {
    id: 'event_date',
    accessorKey: 'event_date',
    header: t('Date'),
    cell: ({ row }) => formatDate(row.original.event_date)
  },
  {
    id: 'detail',
    accessorKey: 'detail',
    header: t('Number'),
    cell: ({ row }) => row.original.detail || ''
  },
]

if (props.canWrite) {
  columns.push({
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const event = row.original
      return h('div', { class: 'flex items-center gap-1 justify-end' }, [
        h(Button, {
          size: 'icon',
          variant: 'ghost',
          class: 'h-7 w-7',
          title: t('Recalculate tasks'),
          disabled: recalculatingEventId.value === event.id,
          onClick: (e) => { e.stopPropagation(); recalculateTasks(event) }
        }, () => h(RotateCw, {
          class: ['h-3.5 w-3.5', recalculatingEventId.value === event.id && 'animate-spin']
        })),
        h(Button, {
          size: 'icon',
          variant: 'ghost',
          class: 'h-7 w-7',
          onClick: (e) => { e.stopPropagation(); removeEvent(event) }
        }, () => h(Trash2, { class: 'h-3.5 w-3.5' }))
      ])
    }
  })
}
</script>
