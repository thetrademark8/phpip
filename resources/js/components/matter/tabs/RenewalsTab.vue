<template>
  <div>
    <!-- Action bar -->
    <div v-if="canWrite" class="flex items-center gap-2 mb-2">
      <Button
        variant="secondary"
        size="sm"
        @click="showConfirmDialog = true"
        :disabled="selectedIds.length === 0"
      >
        {{ t('Clear selected on') }}
      </Button>
      <DatePicker
        v-model="clearDate"
        :placeholder="t('Select date')"
        button-class="w-auto"
      />
    </div>

    <DataTable
      :data="renewals"
      :columns="columns"
      :loading="false"
      :show-pagination="false"
      :selectable="canWrite"
      :get-row-id="(row) => row.id"
      :empty-message="$t('No renewals due')"
      @update:selected="selectedIds = $event.map(r => r.id)"
    />

    <!-- Confirm dialog -->
    <ConfirmDialog
      :open="showConfirmDialog"
      @update:open="showConfirmDialog = $event"
      :title="t('Confirm Renewal Completion')"
      :description="t('This action will mark the selected renewals as completed.')"
      :message="t('Are you sure you want to mark {count} renewal(s) as completed on {date}?', { count: selectedIds.length, date: clearDate })"
      :confirm-text="t('Complete Renewals')"
      :cancel-text="t('Cancel')"
      type="default"
      @confirm="clearSelected"
    />
  </div>
</template>

<script setup>
import {ref} from 'vue'
import {router, usePage} from '@inertiajs/vue3'
import {format, isPast, addDays} from 'date-fns'
import {h} from 'vue'
import {useI18n} from 'vue-i18n'
import DataTable from '@/components/ui/DataTable.vue'
import {Button} from '@/components/ui/button'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import {useTranslatedField} from '@/composables/useTranslation'

const props = defineProps({
  renewals: Array,
  matterId: [String, Number],
  canWrite: {
    type: Boolean,
    default: false
  }
})

const {t} = useI18n()
const {translated} = useTranslatedField()

// Selection & clearing state
const selectedIds = ref([])
const clearDate = ref(new Date().toISOString().split('T')[0])
const showConfirmDialog = ref(false)

async function clearSelected() {
  showConfirmDialog.value = false

  try {
    const response = await fetch('/matter/clear-tasks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': usePage().props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        task_ids: selectedIds.value,
        done_date: clearDate.value
      })
    })

    const data = await response.json()

    if (data.success) {
      selectedIds.value = []
      router.reload()
    }
  } catch (error) {
    console.error('Error clearing renewals:', error)
  }
}

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
        h('div', {class: 'font-medium'}, translated(renewal.detail) || t('Renewal')),
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
