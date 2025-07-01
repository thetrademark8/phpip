<template>
  <Card class="border-info">
    <CardHeader class="bg-info text-white p-2">
      <CardTitle class="text-lg">Users tasks</CardTitle>
    </CardHeader>
    <CardContent class="p-0 max-h-[350px] overflow-auto">
      <table class="w-full">
        <thead class="sticky top-0 bg-background">
          <tr class="border-b">
            <th class="text-left p-2"></th>
            <th class="text-left p-2">Open</th>
            <th class="text-left p-2">Hottest</th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="task in tasksCount.filter(t => t.no_of_tasks > 0)" 
            :key="task.login"
            class="border-b hover:bg-accent"
          >
            <td class="p-2">
              <Link 
                :href="`/home?user_dashboard=${task.login}`"
                class="text-primary hover:underline"
              >
                {{ task.login }}
              </Link>
            </td>
            <td class="p-2">{{ task.no_of_tasks }}</td>
            <td 
              class="p-2"
              :class="{
                'bg-destructive/20 text-destructive': isOverdue(task.urgent_date),
                'bg-warning/20 text-warning-foreground': isUpcoming(task.urgent_date) && !isOverdue(task.urgent_date)
              }"
            >
              {{ formatDate(task.urgent_date) }}
            </td>
          </tr>
        </tbody>
      </table>
    </CardContent>
  </Card>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { format, parseISO, isPast, isBefore, addWeeks } from 'date-fns'

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
    const twoWeeksFromNow = addWeeks(new Date(), 2)
    return isBefore(dueDate, twoWeeksFromNow)
  } catch {
    return false
  }
}
</script>