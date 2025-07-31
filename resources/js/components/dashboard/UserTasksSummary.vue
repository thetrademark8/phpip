<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ $t('dashboard.users_tasks.title') }}</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div 
        v-for="task in tasksCount.filter(t => t.no_of_tasks > 0)" 
        :key="task.login"
        class="flex items-center justify-between p-3 rounded-lg hover:bg-accent transition-colors"
      >
        <div class="flex items-center gap-3">
          <Avatar class="h-8 w-8">
            <AvatarImage :src="getAvatarUrl(task.login)" :alt="task.login" />
            <AvatarFallback>{{ getInitials(task.login) }}</AvatarFallback>
          </Avatar>
          <div>
            <Link 
              :href="`/home?user_dashboard=${task.login}`"
              class="font-medium hover:underline"
            >
              {{ task.login }}
            </Link>
            <p class="text-sm text-muted-foreground">
              {{ task.no_of_tasks }} {{ $t('dashboard.users_tasks.open_tasks') }}
            </p>
          </div>
        </div>
        <div class="text-right">
          <Badge 
            :variant="getBadgeVariant(task.urgent_date)"
            class="mb-1"
          >
            {{ $t(getBadgeText(task.urgent_date)) }}
          </Badge>
          <p class="text-xs text-muted-foreground">
            {{ formatDate(task.urgent_date) }}
          </p>
        </div>
      </div>
      <div v-if="tasksCount.filter(t => t.no_of_tasks > 0).length === 0" class="text-center py-8 text-muted-foreground">
        {{ $t('dashboard.users_tasks.no_tasks') }}
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar'
import { Badge } from '@/Components/ui/badge'
import { format, parseISO, isPast, isBefore, addWeeks, addDays } from 'date-fns'

const props = defineProps({
  tasksCount: {
    type: Array,
    required: true
  }
})

const formatDate = (date) => {
  if (!date) return ''
  try {
    return format(parseISO(date), 'dd/MM/yyyy')
  } catch {
    return date
  }
}

const isOverdue = (date) => {
  if (!date) return false
  try {
    return isPast(parseISO(date))
  } catch {
    return false
  }
}

const isUpcoming = (date) => {
  if (!date) return false
  try {
    const dueDate = parseISO(date)
    const oneWeekFromNow = addWeeks(new Date(), 1)
    return isBefore(dueDate, oneWeekFromNow)
  } catch {
    return false
  }
}

const isUrgent = (date) => {
  if (!date) return false
  try {
    const dueDate = parseISO(date)
    const threeDaysFromNow = addDays(new Date(), 3)
    return isBefore(dueDate, threeDaysFromNow) && !isPast(dueDate)
  } catch {
    return false
  }
}

const getBadgeVariant = (date) => {
  if (isOverdue(date)) return 'destructive'
  if (isUrgent(date)) return 'warning'
  if (isUpcoming(date)) return 'secondary'
  return 'outline'
}

const getBadgeText = (date) => {
  if (isOverdue(date)) return 'dashboard.users_tasks.overdue'
  if (isUrgent(date)) return 'dashboard.users_tasks.urgent'
  if (isUpcoming(date)) return 'dashboard.users_tasks.upcoming'
  return 'dashboard.users_tasks.on_track'
}

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getAvatarUrl = (login) => {
  // You can implement a real avatar URL logic here
  // For now, using a placeholder service
  return `https://api.dicebear.com/7.x/initials/svg?seed=${login}`
}
</script>