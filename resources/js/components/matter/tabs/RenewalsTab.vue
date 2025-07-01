<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>
          Renewals
          <Badge v-if="renewals.length" variant="warning" class="ml-2">
            {{ renewals.length }} due
          </Badge>
        </CardTitle>
        <Link
          :href="`/matter/${matterId}/renewals`"
          class="text-sm text-muted-foreground hover:text-foreground"
        >
          View all renewals
        </Link>
      </div>
    </CardHeader>
    <CardContent>
      <div v-if="renewals.length" class="space-y-3">
        <div
          v-for="renewal in renewals"
          :key="renewal.id"
          class="flex items-center justify-between p-3 border rounded-lg"
        >
          <div>
            <div class="font-medium">{{ renewal.detail }}</div>
            <div class="text-sm text-muted-foreground">
              Due: {{ formatDate(renewal.due_date) }}
            </div>
          </div>
          <div class="text-right">
            <div v-if="renewal.cost" class="font-medium">
              {{ formatCurrency(renewal.cost) }}
            </div>
            <StatusBadge
              :status="getRenewalStatus(renewal)"
              type="task"
            />
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-muted-foreground">
        No renewals due
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { format, isPast, addDays } from 'date-fns'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import StatusBadge from '@/Components/display/StatusBadge.vue'

const props = defineProps({
  renewals: Array,
  matterId: [String, Number]
})

function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

function getRenewalStatus(renewal) {
  if (renewal.done) return 'done'
  const dueDate = new Date(renewal.due_date)
  if (isPast(dueDate)) return 'overdue'
  if (isPast(addDays(new Date(), 30))) return 'warning'
  return 'open'
}
</script>