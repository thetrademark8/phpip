<template>
  <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <!-- Total Active Matters -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">
          {{ $t('dashboard.metrics.active_matters') }}
        </CardTitle>
        <FileText class="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ metrics.totalActiveMatters }}</div>
        <p class="text-xs text-muted-foreground">
          <span :class="[
            'inline-flex items-center',
            metrics.activeMattersChange >= 0 ? 'text-green-600' : 'text-red-600'
          ]">
            <TrendingUp v-if="metrics.activeMattersChange >= 0" class="h-3 w-3 mr-1" />
            <TrendingDown v-else class="h-3 w-3 mr-1" />
            {{ Math.abs(metrics.activeMattersChange) }}%
          </span>
          {{ $t('dashboard.metrics.from_last_month') }}
        </p>
      </CardContent>
    </Card>

    <!-- Overdue Tasks -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">
          {{ $t('dashboard.metrics.overdue_tasks') }}
        </CardTitle>
        <AlertCircle class="h-4 w-4 text-destructive" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ metrics.overdueTasks }}</div>
        <p class="text-xs text-muted-foreground">
          {{ $t('dashboard.metrics.requires_attention') }}
        </p>
      </CardContent>
    </Card>

    <!-- Upcoming Renewals -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">
          {{ $t('dashboard.metrics.upcoming_renewals') }}
        </CardTitle>
        <Calendar class="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ metrics.upcomingRenewals }}</div>
        <p class="text-xs text-muted-foreground">
          {{ $t('dashboard.metrics.next_30_days') }}
        </p>
      </CardContent>
    </Card>

    <!-- Matters Created This Year -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle class="text-sm font-medium">
          {{ $t('dashboard.metrics.matters_created_this_year') }}
        </CardTitle>
        <FolderPlus class="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ metrics.mattersCreatedThisYear }}</div>
        <p class="text-xs text-muted-foreground">
          {{ $t('dashboard.metrics.since_january') }}
        </p>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { FileText, AlertCircle, Calendar, FolderPlus, TrendingUp, TrendingDown } from 'lucide-vue-next'

const props = defineProps({
  metrics: {
    type: Object,
    required: true,
    default: () => ({
      totalActiveMatters: 0,
      activeMattersChange: 0,
      overdueTasks: 0,
      upcomingRenewals: 0,
      mattersCreatedThisYear: 0
    })
  }
})
</script>