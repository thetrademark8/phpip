<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle class="text-base">{{ $t('Recent Activity') }}</CardTitle>
        <Button
          size="sm"
          variant="ghost"
          @click="loadMoreActivities"
          :disabled="loading || !hasMore"
        >
          <RefreshCw :class="['h-3 w-3', loading && 'animate-spin']" />
        </Button>
      </div>
    </CardHeader>
    <CardContent class="p-0">
      <div v-if="activities.length > 0" class="divide-y">
        <div
          v-for="activity in activities"
          :key="`${activity.type}-${activity.id}-${activity.date}`"
          class="px-4 py-3 hover:bg-muted/50 transition-colors"
        >
          <div class="flex gap-3">
            <div class="flex-shrink-0 mt-0.5">
              <div :class="[
                'w-8 h-8 rounded-full flex items-center justify-center',
                getActivityColor(activity.type)
              ]">
                <component :is="getActivityIcon(activity.type)" class="h-4 w-4" />
              </div>
            </div>
            <div class="flex-1 space-y-1">
              <p class="text-sm">
                <span class="font-medium">{{ activity.user || $t('System') }}</span>
                {{ activity.description }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ formatRelativeTime(activity.date) }}
                <span v-if="activity.details" class="ml-2">
                  • {{ activity.details }}
                </span>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="p-8 text-center text-muted-foreground">
        {{ $t('No recent activity') }}
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import { 
  RefreshCw,
  FileText,
  UserPlus,
  Calendar,
  CheckCircle,
  AlertCircle,
  Edit,
  Trash,
  Clock,
  Tag
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  matterId: {
    type: [String, Number],
    required: true
  },
  events: {
    type: Array,
    default: () => []
  },
  tasks: {
    type: Array,
    default: () => []
  }
})

const { t } = useI18n()

// State
const activities = ref([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)

// Activity type configurations
const activityConfig = {
  event_created: {
    icon: Calendar,
    color: 'bg-blue-100 text-blue-600'
  },
  event_updated: {
    icon: Edit,
    color: 'bg-yellow-100 text-yellow-600'
  },
  event_deleted: {
    icon: Trash,
    color: 'bg-red-100 text-red-600'
  },
  task_created: {
    icon: Clock,
    color: 'bg-purple-100 text-purple-600'
  },
  task_completed: {
    icon: CheckCircle,
    color: 'bg-green-100 text-green-600'
  },
  task_overdue: {
    icon: AlertCircle,
    color: 'bg-red-100 text-red-600'
  },
  actor_added: {
    icon: UserPlus,
    color: 'bg-indigo-100 text-indigo-600'
  },
  actor_removed: {
    icon: UserPlus,
    color: 'bg-red-100 text-red-600'
  },
  title_added: {
    icon: Tag,
    color: 'bg-cyan-100 text-cyan-600'
  },
  matter_updated: {
    icon: FileText,
    color: 'bg-gray-100 text-gray-600'
  }
}

function getActivityIcon(type) {
  return activityConfig[type]?.icon || FileText
}

function getActivityColor(type) {
  return activityConfig[type]?.color || 'bg-gray-100 text-gray-600'
}

function formatRelativeTime(date) {
  if (!date) return ''
  return formatDistanceToNow(new Date(date), { addSuffix: true })
}

function buildActivitiesFromData() {
  const recentActivities = []
  
  // Add recent events as activities
  if (props.events) {
    props.events
      .filter(event => event.event_date)
      .slice(0, 5)
      .forEach(event => {
        recentActivities.push({
          id: event.id,
          type: 'event_created',
          user: event.creator?.name,
          description: t('added event "{name}"', { name: event.event_name }),
          details: event.detail,
          date: event.created_at || event.event_date
        })
      })
  }
  
  // Add completed tasks as activities
  if (props.tasks) {
    props.tasks
      .filter(task => task.done)
      .slice(0, 5)
      .forEach(task => {
        recentActivities.push({
          id: task.id,
          type: 'task_completed',
          user: task.done_by?.name,
          description: t('completed task "{name}"', { name: task.name }),
          details: task.detail,
          date: task.done_date
        })
      })
  }
  
  // Sort by date descending
  recentActivities.sort((a, b) => new Date(b.date) - new Date(a.date))
  
  activities.value = recentActivities.slice(0, 10)
}

async function loadMoreActivities() {
  // TODO: Implement API call to load more activities
  // For now, just show what we have from the current data
  loading.value = true
  setTimeout(() => {
    loading.value = false
    hasMore.value = false
  }, 500)
}

onMounted(() => {
  buildActivitiesFromData()
})
</script>