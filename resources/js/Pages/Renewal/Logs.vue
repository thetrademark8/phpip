<template>
  <MainLayout :title="$t('Renewal logs')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ $t('Renewal logs') }}</h1>
          <p class="text-muted-foreground">
            {{ $t('View history and audit trail of renewal actions') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="clearFilters" variant="outline" size="sm">
            <RotateCcw class="mr-2 h-4 w-4" />
            {{ $t('Clear filters') }}
          </Button>
          <Button @click="router.visit('/renewal')" variant="outline" size="sm">
            <ArrowLeft class="mr-2 h-4 w-4" />
            {{ $t('Back to renewals') }}
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Task ID Filter -->
            <div>
              <Label for="task-id" class="text-sm font-medium">
                {{ $t('Task ID') }}
              </Label>
              <Input
                id="task-id"
                v-model="filters.task_id"
                type="number"
                :placeholder="$t('Enter task ID')"
                @input="debouncedFilter"
              />
            </div>

            <!-- Job ID Filter -->
            <div>
              <Label for="job-id" class="text-sm font-medium">
                {{ $t('Job ID') }}
              </Label>
              <Input
                id="job-id"
                v-model="filters.job_id"
                type="number"
                :placeholder="$t('Enter job ID')"
                @input="debouncedFilter"
              />
            </div>

            <!-- Creator Filter -->
            <div>
              <Label for="creator" class="text-sm font-medium">
                {{ $t('Creator') }}
              </Label>
              <Input
                id="creator"
                v-model="filters.creator"
                :placeholder="$t('Enter creator name')"
                @input="debouncedFilter"
              />
            </div>

            <!-- Date From Filter -->
            <div>
              <Label for="date-from" class="text-sm font-medium">
                {{ $t('From date') }}
              </Label>
              <Input
                id="date-from"
                v-model="filters.date_from"
                type="date"
                @change="applyFilters"
              />
            </div>

            <!-- Date To Filter -->
            <div>
              <Label for="date-to" class="text-sm font-medium">
                {{ $t('To date') }}
              </Label>
              <Input
                id="date-to"
                v-model="filters.date_to"
                type="date"
                @change="applyFilters"
              />
            </div>

            <!-- From Step Filter -->
            <div>
              <Label for="from-step" class="text-sm font-medium">
                {{ $t('From step') }}
              </Label>
              <Select v-model="filters.from_step" @update:modelValue="applyFilters">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('Any step')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">{{ $t('Any step') }}</SelectItem>
                  <SelectItem value="0">{{ $t('Open') }}</SelectItem>
                  <SelectItem value="1">{{ $t('Instructions sent') }}</SelectItem>
                  <SelectItem value="2">{{ $t('Reminder sent') }}</SelectItem>
                  <SelectItem value="3">{{ $t('Payment requested') }}</SelectItem>
                  <SelectItem value="4">{{ $t('Invoiced') }}</SelectItem>
                  <SelectItem value="5">{{ $t('Receipts issued') }}</SelectItem>
                  <SelectItem value="10">{{ $t('Closed') }}</SelectItem>
                  <SelectItem value="11">{{ $t('Abandoned') }}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- To Step Filter -->
            <div>
              <Label for="to-step" class="text-sm font-medium">
                {{ $t('To step') }}
              </Label>
              <Select v-model="filters.to_step" @update:modelValue="applyFilters">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('Any step')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">{{ $t('Any step') }}</SelectItem>
                  <SelectItem value="0">{{ $t('Open') }}</SelectItem>
                  <SelectItem value="1">{{ $t('Instructions sent') }}</SelectItem>
                  <SelectItem value="2">{{ $t('Reminder sent') }}</SelectItem>
                  <SelectItem value="3">{{ $t('Payment requested') }}</SelectItem>
                  <SelectItem value="4">{{ $t('Invoiced') }}</SelectItem>
                  <SelectItem value="5">{{ $t('Receipts issued') }}</SelectItem>
                  <SelectItem value="10">{{ $t('Closed') }}</SelectItem>
                  <SelectItem value="11">{{ $t('Abandoned') }}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Invoice Step Filter -->
            <div>
              <Label for="invoice-step" class="text-sm font-medium">
                {{ $t('Invoice step') }}
              </Label>
              <Select v-model="filters.from_invoice" @update:modelValue="applyFilters">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('Any invoice step')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">{{ $t('Any invoice step') }}</SelectItem>
                  <SelectItem value="0">{{ $t('Not invoiced') }}</SelectItem>
                  <SelectItem value="1">{{ $t('Invoice sent') }}</SelectItem>
                  <SelectItem value="2">{{ $t('Invoiced') }}</SelectItem>
                  <SelectItem value="3">{{ $t('Receipt issued') }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Results -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span>{{ $t('Logs') }}</span>
            <Badge variant="secondary">
              {{ logs.total }} {{ $t('entries') }}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{{ $t('Date/Time') }}</TableHead>
                  <TableHead>{{ $t('Task ID') }}</TableHead>
                  <TableHead>{{ $t('Job ID') }}</TableHead>
                  <TableHead>{{ $t('Creator') }}</TableHead>
                  <TableHead>{{ $t('Workflow change') }}</TableHead>
                  <TableHead>{{ $t('Invoice change') }}</TableHead>
                  <TableHead>{{ $t('Grace period') }}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="logs.data.length > 0">
                  <TableRow 
                    v-for="log in logs.data" 
                    :key="log.id"
                    class="hover:bg-muted/50"
                  >
                    <TableCell class="font-mono text-sm">
                      {{ formatDate(log.created_at) }}
                    </TableCell>
                    <TableCell>
                      <Link 
                        :href="`/renewal?task_id=${log.task_id}`"
                        class="text-primary hover:underline font-medium"
                      >
                        {{ log.task_id }}
                      </Link>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{{ log.job_id }}</Badge>
                    </TableCell>
                    <TableCell>{{ log.creator }}</TableCell>
                    <TableCell>
                      <div v-if="log.from_step !== null && log.to_step !== null" class="flex items-center gap-2">
                        <Badge :variant="getStepVariant(log.from_step)">
                          {{ getStepName(log.from_step) }}
                        </Badge>
                        <ArrowRight class="h-4 w-4 text-muted-foreground" />
                        <Badge :variant="getStepVariant(log.to_step)">
                          {{ getStepName(log.to_step) }}
                        </Badge>
                      </div>
                      <span v-else class="text-muted-foreground">-</span>
                    </TableCell>
                    <TableCell>
                      <div v-if="log.from_invoice !== null && log.to_invoice !== null" class="flex items-center gap-2">
                        <Badge variant="outline">
                          {{ getInvoiceStepName(log.from_invoice) }}
                        </Badge>
                        <ArrowRight class="h-4 w-4 text-muted-foreground" />
                        <Badge variant="outline">
                          {{ getInvoiceStepName(log.to_invoice) }}
                        </Badge>
                      </div>
                      <span v-else class="text-muted-foreground">-</span>
                    </TableCell>
                    <TableCell>
                      <div v-if="log.from_grace !== null && log.to_grace !== null" class="flex items-center gap-2">
                        <Badge variant="secondary">{{ log.from_grace }}</Badge>
                        <ArrowRight class="h-4 w-4 text-muted-foreground" />
                        <Badge variant="secondary">{{ log.to_grace }}</Badge>
                      </div>
                      <span v-else class="text-muted-foreground">-</span>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow>
                    <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                      {{ $t('No logs found') }}
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          {{ $t('Showing') }} {{ logs.from }} {{ $t('to') }} {{ logs.to }} {{ $t('of') }} {{ logs.total }} {{ $t('entries') }}
        </p>
        <Pagination
          :current-page="logs.current_page"
          :last-page="logs.last_page"
          :links="logs.links"
        />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import Pagination from '@/components/ui/Pagination.vue'
import { RotateCcw, ArrowLeft, ArrowRight } from 'lucide-vue-next'

const props = defineProps({
  logs: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const page = usePage()
const filters = reactive({ ...props.filters })

const debouncedFilter = debounce(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([key, value]) => value !== '' && value !== null)
  )
  
  router.get('/renewal/logs', cleanFilters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  router.get('/renewal/logs')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

const getStepName = (step) => {
  const steps = {
    0: 'Open',
    1: 'Instructions sent',
    2: 'Reminder sent', 
    3: 'Payment requested',
    4: 'Invoiced',
    5: 'Receipts issued',
    10: 'Closed',
    11: 'Abandoned'
  }
  return steps[step] || step
}

const getStepVariant = (step) => {
  if (step === 10) return 'default'
  if (step === 11) return 'destructive'
  if (step >= 4) return 'secondary'
  return 'outline'
}

const getInvoiceStepName = (step) => {
  const steps = {
    0: 'Not invoiced',
    1: 'Invoice sent',
    2: 'Invoiced', 
    3: 'Receipt issued'
  }
  return steps[step] || step
}
</script>