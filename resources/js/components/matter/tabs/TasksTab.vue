<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ $t('Tasks') }}</CardTitle>
    </CardHeader>
    <CardContent>
      <DataTable
        :data="tasks"
        :columns="columns"
        :loading="false"
        :show-pagination="tasks.length > 10"
        :page-size="10"
        :empty-message="$t('No tasks found')"
      />
    </CardContent>
  </Card>

  <TaskDialog
    v-model:open="showEditTaskDialog"
    :matter-id="matterId"
    :task="selectedTask"
    operation="edit"
    @success="handleTaskUpdate"
  />
  
  <TaskCompletionDialog
    v-model:open="showCompletionDialog"
    :task="selectedTask"
    @success="handleTaskUpdate"
  />
</template>

<script setup>
import { ref, h } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Edit2, Check, Trash2, Calendar, User } from 'lucide-vue-next'
import { format, parseISO, isPast } from 'date-fns'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import DataTable from '@/Components/ui/DataTable.vue'
import TaskDialog from '@/Components/dialogs/TaskDialog.vue'
import TaskCompletionDialog from '@/Components/dialogs/TaskCompletionDialog.vue'
import StatusBadge from '@/Components/display/StatusBadge.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  tasks: Array,
  matterId: [String, Number],
  enableInlineEdit: Boolean
})

const { t } = useI18n()
const { translated } = useTranslatedField()

const showEditTaskDialog = ref(false)
const showCompletionDialog = ref(false)
const selectedTask = ref(null)

// Helper functions
function formatDate(date) {
  if (!date) return t('No date')
  try {
    const parsed = typeof date === 'string' ? parseISO(date) : date
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

function isOverdue(task) {
  if (task.done || !task.due_date) return false
  try {
    const dueDate = typeof task.due_date === 'string' ? parseISO(task.due_date) : task.due_date
    return isPast(dueDate)
  } catch {
    return false
  }
}

function getTaskStatus(task) {
  if (task.done) return 'done'
  if (isOverdue(task)) return 'overdue'
  return 'pending'
}

// Define columns for DataTable
const columns = [
  {
    id: 'task',
    accessorKey: 'code',
    header: t('Task'),
    cell: ({ row }) => {
      const task = row.original
      return h('div', { class: 'space-y-1' }, [
        h('div', { class: 'font-medium' }, task.info?.name ? translated(task.info.name) : task.code),
        task.detail && h('div', { class: 'text-sm text-muted-foreground' }, translated(task.detail))
      ])
    }
  },
  {
    id: 'due_date',
    accessorKey: 'due_date',
    header: t('Due date'),
    cell: ({ row }) => {
      const task = row.original
      const overdue = isOverdue(task)
      return h('div', { class: 'flex items-center gap-1' }, [
        h(Calendar, { class: 'h-3 w-3 text-muted-foreground' }),
        h('span', { 
          class: overdue && !task.done ? 'text-red-600 font-medium' : ''
        }, formatDate(task.due_date))
      ])
    }
  },
  {
    id: 'assigned_to',
    accessorKey: 'assigned_to',
    header: t('Assigned to'),
    cell: ({ row }) => {
      const task = row.original
      return h('div', { class: 'flex items-center gap-1' }, [
        h(User, { class: 'h-3 w-3 text-muted-foreground' }),
        h('span', {}, task.assigned_to || 'Unassigned')
      ])
    }
  },
  {
    id: 'status',
    header: t('Status'),
    cell: ({ row }) => h(StatusBadge, {
      status: getTaskStatus(row.original),
      type: 'task'
    })
  }
]

// Add actions column if editing is enabled
if (props.enableInlineEdit) {
  columns.push({
    id: 'actions',
    header: t('Actions'),
    cell: ({ row }) => {
      const task = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        h(Button, {
          size: 'icon',
          variant: 'ghost',
          onClick: () => {
            selectedTask.value = task
            showEditTaskDialog.value = true
          }
        }, () => h(Edit2, { class: 'h-4 w-4' })),
        !task.done && h(Button, {
          size: 'icon',
          variant: 'ghost',
          onClick: () => {
            selectedTask.value = task
            showCompletionDialog.value = true
          }
        }, () => h(Check, { class: 'h-4 w-4' })),
        h(Button, {
          size: 'icon',
          variant: 'ghost',
          onClick: () => handleDeleteTask(task)
        }, () => h(Trash2, { class: 'h-4 w-4' }))
      ])
    }
  })
}

function handleTaskUpdate() {
  showEditTaskDialog.value = false
  showCompletionDialog.value = false
  router.reload({ only: ['matter'] })
}


function handleDeleteTask(task) {
  if (confirm(t('Are you sure you want to delete this task?'))) {
    router.delete(`/task/${task.id}`, {
      onSuccess: () => {
        router.reload({ only: ['matter'] })
      }
    })
  }
}
</script>