<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div v-if="showFilters" class="flex gap-4 items-center">
      <div class="flex-1">
        <Input
          v-model="searchQuery"
          placeholder="Search tasks..."
          class="max-w-sm"
        />
      </div>
      
      <Select v-model="filterStatus">
        <SelectTrigger class="w-[180px]">
          <SelectValue placeholder="All statuses" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem value="pending">Pending</SelectItem>
          <SelectItem value="done">Done</SelectItem>
          <SelectItem value="overdue">Overdue</SelectItem>
        </SelectContent>
      </Select>
      
      <Button
        v-if="editable"
        @click="emit('add')"
      >
        <Plus class="h-4 w-4 mr-2" />
        Add Task
      </Button>
    </div>
    
    <!-- Task list -->
    <div class="border rounded-lg divide-y">
      <div
        v-for="task in filteredTasks"
        :key="task.id"
        :class="cn(
          'p-4 hover:bg-accent/50 transition-colors',
          task.done && 'opacity-60',
          isOverdue(task) && !task.done && 'bg-red-50'
        )"
      >
        <div class="flex items-start gap-4">
          <Checkbox
            v-if="editable"
            :checked="task.done"
            @update:checked="handleTaskToggle(task, $event)"
            class="mt-1"
          />
          
          <div class="flex-1 space-y-1">
            <div class="flex items-center gap-2">
              <span class="font-medium">{{ task.info?.name || task.code }}</span>
              <StatusBadge
                :status="getTaskStatus(task)"
                type="task"
              />
            </div>
            
            <div class="flex flex-wrap gap-4 text-sm">
              <div class="flex items-center gap-1">
                <Calendar class="h-3 w-3 text-muted-foreground" />
                <span class="text-muted-foreground">Due:</span>
                <EditableField
                  v-if="enableInlineEdit"
                  :model-value="task.due_date"
                  field="due_date"
                  type="date"
                  :url="updateUrl(task)"
                  :format="formatDate"
                  :value-class="cn(
                    'font-medium',
                    isOverdue(task) && !task.done ? 'text-red-600' : 'text-foreground'
                  )"
                  @saved="emit('update', { ...task, due_date: $event })"
                />
                <span v-else :class="cn(
                  'font-medium',
                  isOverdue(task) && !task.done ? 'text-red-600' : 'text-foreground'
                )">{{ formatDate(task.due_date) }}</span>
              </div>
              <div v-if="task.assigned_to || enableInlineEdit" class="flex items-center gap-1">
                <User class="h-3 w-3 text-muted-foreground" />
                <span class="text-muted-foreground">Assigned:</span>
                <EditableField
                  v-if="enableInlineEdit"
                  :model-value="task.assigned_to || ''"
                  field="assigned_to"
                  :url="updateUrl(task)"
                  placeholder="Unassigned"
                  value-class="font-medium text-foreground"
                  @saved="emit('update', { ...task, assigned_to: $event })"
                />
                <span v-else class="font-medium">{{ task.assigned_to || 'Unassigned' }}</span>
              </div>
              <div v-if="task.cost" class="flex items-center gap-1">
                <DollarSign class="h-3 w-3 text-muted-foreground" />
                <span class="text-muted-foreground">Cost:</span>
                <span class="font-medium">{{ formatCurrency(task.cost) }}</span>
              </div>
            </div>
            
            <p v-if="task.detail || enableInlineEdit" class="text-sm mt-2">
              <EditableField
                v-if="enableInlineEdit"
                :model-value="task.detail || ''"
                field="detail"
                :url="updateUrl(task)"
                placeholder="Add task details..."
                value-class="text-sm"
                @saved="emit('update', { ...task, detail: $event })"
              />
              <span v-else>{{ task.detail }}</span>
            </p>
            
            <div v-if="task.done && task.done_date" class="text-sm text-muted-foreground">
              Completed: {{ formatDate(task.done_date) }}
            </div>
          </div>
          
          <div v-if="editable" class="flex items-center gap-2">
            <Button
              size="icon"
              variant="ghost"
              @click="emit('edit', task)"
            >
              <Edit2 class="h-4 w-4" />
            </Button>
            <Button
              size="icon"
              variant="ghost"
              @click="emit('remove', task)"
            >
              <Trash2 class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
      
      <div v-if="filteredTasks.length === 0" class="p-8 text-center text-muted-foreground">
        No tasks found
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { format, parseISO, isPast } from 'date-fns'
import { Edit2, Trash2, Plus, Calendar, User, DollarSign } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import { Input } from '@/Components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import StatusBadge from './StatusBadge.vue'
import InlineEdit from '@/Components/ui/InlineEdit.vue'
import EditableField from '@/Components/ui/EditableField.vue'

const props = defineProps({
  tasks: {
    type: Array,
    required: true
  },
  editable: {
    type: Boolean,
    default: false
  },
  showFilters: {
    type: Boolean,
    default: true
  },
  enableInlineEdit: {
    type: Boolean,
    default: false
  },
  updateUrl: {
    type: Function,
    default: (task) => `/task/${task.id}`
  }
})

const emit = defineEmits(['edit', 'remove', 'add', 'toggle', 'update'])

// Local state
const searchQuery = ref('')
const filterStatus = ref('all')

// Computed
const filteredTasks = computed(() => {
  let filtered = [...props.tasks]
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(task => {
      const name = String(task.info?.name || task.code || '').toLowerCase()
      const detail = String(task.detail || '').toLowerCase()
      const assignee = String(task.assigned_to || '').toLowerCase()
      return name.includes(query) || detail.includes(query) || assignee.includes(query)
    })
  }
  
  // Apply status filter
  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(task => {
      switch (filterStatus.value) {
        case 'pending':
          return !task.done && !isOverdue(task)
        case 'done':
          return task.done
        case 'overdue':
          return !task.done && isOverdue(task)
        default:
          return true
      }
    })
  }
  
  // Sort by due date
  return filtered.sort((a, b) => {
    const dateA = a.due_date ? new Date(a.due_date) : new Date(9999, 11, 31)
    const dateB = b.due_date ? new Date(b.due_date) : new Date(9999, 11, 31)
    return dateA - dateB
  })
})

// Methods
const formatDate = (date) => {
  if (!date) return 'No date'
  try {
    const parsed = typeof date === 'string' ? parseISO(date) : date
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

const isOverdue = (task) => {
  if (task.done || !task.due_date) return false
  try {
    const dueDate = typeof task.due_date === 'string' ? parseISO(task.due_date) : task.due_date
    return isPast(dueDate)
  } catch {
    return false
  }
}

const getTaskStatus = (task) => {
  if (task.done) return 'done'
  if (isOverdue(task)) return 'overdue'
  return 'pending'
}

const handleTaskToggle = (task, checked) => {
  emit('toggle', { task, done: checked })
}

const formatCurrency = (amount) => {
  if (!amount) return ''
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}
</script>