<template>
  <MainLayout :title="$t('Manage renewals')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ $t('Manage renewals') }}</h1>
          <div class="flex items-center gap-2 mt-2">
            <a 
              href="https://github.com/jjdejong/phpip/wiki/Renewal-Management" 
              target="_blank"
              class="text-primary hover:underline flex items-center gap-1"
              :title="$t('Help')"
            >
              <HelpCircle class="h-4 w-4" />
              {{ $t('Help') }}
            </a>
            <Link 
              href="/logs" 
              class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-secondary text-secondary-foreground hover:bg-secondary/90 h-9 px-4 py-2"
            >
              {{ $t('View logs') }}
            </Link>
          </div>
        </div>
        <div class="flex gap-2">
          <Button @click="clearFilters" variant="outline" size="sm">
            <RotateCcw class="mr-2 h-4 w-4" />
            {{ $t('Clear filters') }}
          </Button>
        </div>
      </div>

      <!-- Workflow Tabs -->
      <RenewalTabs 
        :active-step="currentStep" 
        :active-invoice-step="currentInvoiceStep"
        :config="config"
        @tab-change="handleTabChange"
      />

      <!-- Action Buttons for Current Tab -->
      <RenewalActions
        :step="currentStep"
        :invoice-step="currentInvoiceStep"
        :selected-count="selectedRenewals.length"
        :config="config"
        @action="handleAction"
      />

      <!-- Filters Card -->
      <Collapsible v-model:open="isFiltersOpen">
        <Card>
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CollapsibleTrigger as-child>
                  <Button variant="ghost" size="sm" class="p-0 h-auto">
                    <ChevronDown v-if="isFiltersOpen" class="h-4 w-4" />
                    <ChevronUp v-else class="h-4 w-4" />
                  </Button>
                </CollapsibleTrigger>
                <CardTitle class="text-base">{{ t('Filters') }}</CardTitle>
              </div>
              <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                  <Checkbox
                    id="my-renewals"
                    v-model:model-value="myRenewalsOnly"
                  />
                  <Label for="my-renewals" class="text-sm font-normal cursor-pointer">
                    {{ t('My renewals') }}
                  </Label>
                </div>
                <Button
                  v-if="hasActiveFilters"
                  @click="resetFilters"
                  variant="ghost"
                  size="sm"
                >
                  {{ t('Clear all') }}
                </Button>
              </div>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <RenewalFilters
                :filters="filters"
                @update:filters="handleFilterUpdate"
              />
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>

      <!-- Results count -->
      <div class="text-sm text-muted-foreground">
        <span v-if="renewals.data && renewals.data.length > 0">
          {{ t('Showing {from} to {to} of {total} results', {
            from: renewals.from || 1,
            to: renewals.to || renewals.data.length,
            total: renewals.total || renewals.data.length
          }) }}
        </span>
        <span v-else>
          {{ t('The list is empty') }}
        </span>
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <div class="w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
              <thead class="[&_tr]:border-b">
                <tr class="border-b transition-colors hover:bg-muted/50">
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-1/5">
                    {{ t('Client') }}
                  </th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-1/4">
                    {{ t('Title') }}
                  </th>
                  <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-24">
                    {{ t('Matter') }}
                  </th>
                  <th class="h-12 px-4 text-center align-middle font-medium text-muted-foreground">
                    <div class="flex items-center justify-center gap-4">
                      <span class="w-12">{{ t('Ctry') }}</span>
                      <span class="w-12">{{ t('Qt') }}</span>
                      <span class="w-16">{{ t('Grace') }}</span>
                      <span class="w-20 text-right">{{ t('Cost') }}</span>
                      <span class="w-20 text-right">{{ t('Fee') }}</span>
                    </div>
                  </th>
                  <th class="h-12 px-4 text-center align-middle font-medium text-muted-foreground w-32">
                    {{ t('Due Date') }}
                  </th>
                  <th class="h-12 px-4 text-center align-middle font-medium text-muted-foreground w-16">
                    <Checkbox
                      v-model:model-value="selectAll"
                      :aria-label="t('Select/unselect all')"
                    />
                  </th>
                </tr>
              </thead>
              <tbody class="[&_tr:last-child]:border-0">
                <tr v-if="loading" class="border-b transition-colors">
                  <td colspan="6" class="h-24 text-center">
                    <div class="flex items-center justify-center">
                      <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                  </td>
                </tr>
                <tr v-else-if="!renewals.data || renewals.data.length === 0" class="border-b transition-colors">
                  <td colspan="6" class="h-24 text-center text-muted-foreground">
                    {{ t('The list is empty') }}
                  </td>
                </tr>
                <tr
                  v-for="renewal in renewals.data"
                  :key="renewal.id"
                  class="border-b transition-colors hover:bg-muted/50"
                >
                  <td class="p-4 align-middle">
                    {{ renewal.client_name }}
                  </td>
                  <td class="p-4 align-middle">
                    <span :title="renewal.title || renewal.short_title">
                      {{ renewal.short_title }}
                    </span>
                  </td>
                  <td class="p-4 align-middle">
                    <Link 
                      :href="`/matter/${renewal.matter_id}`"
                      class="text-primary hover:underline"
                    >
                      {{ renewal.uid }}
                    </Link>
                  </td>
                  <td class="p-4 align-middle">
                    <div class="flex items-center justify-center gap-4">
                      <span class="w-12 text-center">{{ renewal.country }}</span>
                      <span class="w-12 text-center">{{ renewal.detail }}</span>
                      <span class="w-16 text-center">
                        <Hourglass v-if="renewal.grace_period" class="h-4 w-4 mx-auto" />
                      </span>
                      <span class="w-20 text-right">{{ renewal.cost }}</span>
                      <span class="w-20 text-right">{{ renewal.fee }}</span>
                    </div>
                  </td>
                  <td class="p-4 align-middle text-center">
                    <div class="flex items-center justify-center gap-1">
                      {{ formatDate(renewal.due_date) }}
                      <CheckCircle 
                        v-if="renewal.done" 
                        class="h-4 w-4 text-green-600"
                        :title="t('Done')"
                      />
                      <AlertTriangle 
                        v-else-if="isOverdue(renewal.due_date)" 
                        class="h-4 w-4 text-destructive"
                        :title="t('Overdue')"
                      />
                      <AlertTriangle 
                        v-else-if="isUrgent(renewal.due_date)" 
                        class="h-4 w-4 text-warning"
                        :title="t('Urgent')"
                      />
                    </div>
                  </td>
                  <td class="p-4 align-middle text-center">
                    <Checkbox
                      :model-value="selectedRenewals.includes(renewal.id)"
                      @update:model-value="(checked) => handleSelectRenewal(renewal.id, checked)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="renewals"
        @page-change="goToPage"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'
import { 
  ChevronDown, 
  ChevronUp, 
  RotateCcw, 
  HelpCircle, 
  Loader2,
  Hourglass,
  CheckCircle,
  AlertTriangle
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Checkbox } from '@/Components/ui/checkbox'
import { Label } from '@/Components/ui/label'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import Pagination from '@/Components/ui/Pagination.vue'
import RenewalFilters from '@/Components/renewal/RenewalFilters.vue'
import RenewalTabs from '@/Components/renewal/RenewalTabs.vue'
import RenewalActions from '@/Components/renewal/RenewalActions.vue'

const props = defineProps({
  renewals: Object,
  filters: Object,
  step: [String, Number],
  invoice_step: [String, Number],
  my_renewals: Boolean,
  config: Object,
})

const { t } = useI18n()
const page = usePage()

// State
const loading = ref(false)
const isFiltersOpen = ref(true)
const selectedRenewals = ref([])
const myRenewalsOnly = ref(props.my_renewals || false)

// Current workflow state
const currentStep = ref(props.step ? parseInt(props.step) : 0)
const currentInvoiceStep = ref(props.invoice_step ? parseInt(props.invoice_step) : null)

// Filters
const filters = ref({
  Name: props.filters?.Name || '',
  Title: props.filters?.Title || '',
  Case: props.filters?.Case || '',
  Country: props.filters?.Country || '',
  Qt: props.filters?.Qt || '',
  Fromdate: props.filters?.Fromdate || '',
  Untildate: props.filters?.Untildate || '',
  grace_period: props.filters?.grace_period || false,
})

// Computed
const hasActiveFilters = computed(() => {
  return Object.entries(filters.value).some(([key, value]) => {
    if (key === 'grace_period') return value === true
    return value !== '' && value !== null
  })
})

const selectAll = computed({
  get() {
    return props.renewals?.data?.length > 0 && 
           selectedRenewals.value.length === props.renewals.data.length
  },
  set(value) {
    if (value) {
      selectedRenewals.value = props.renewals?.data?.map(r => r.id) || []
    } else {
      selectedRenewals.value = []
    }
  }
})

// Helper function to clean empty parameters
function cleanParams(params) {
  const cleaned = {}
  
  for (const [key, value] of Object.entries(params)) {
    // Include non-empty strings
    if (typeof value === 'string' && value !== '') {
      cleaned[key] = value
    }
    // Include true boolean values (for grace_period)
    else if (typeof value === 'boolean' && value === true) {
      cleaned[key] = value
    }
    // Include numbers (including 0 for step values)
    else if (typeof value === 'number') {
      cleaned[key] = value
    }
    // Include non-null/undefined values that aren't empty strings or false
    else if (value !== null && value !== undefined && value !== '' && value !== false) {
      cleaned[key] = value
    }
  }
  
  return cleaned
}

// Methods
function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

function isOverdue(dueDate) {
  return new Date(dueDate) < new Date()
}

function isUrgent(dueDate) {
  const oneWeekFromNow = new Date()
  oneWeekFromNow.setDate(oneWeekFromNow.getDate() + 7)
  return new Date(dueDate) < oneWeekFromNow
}

function handleTabChange({ step, invoiceStep }) {
  // Set loading state immediately for better UX
  loading.value = true
  
  // Clear selections when changing tabs
  selectedRenewals.value = []
  
  let params = { ...filters.value }
  
  if (step !== null) {
    params.step = step
    currentStep.value = step
  }
  if (invoiceStep !== null) {
    params.invoice_step = invoiceStep
    currentInvoiceStep.value = invoiceStep
  }
  if (myRenewalsOnly.value) {
    params.my_renewals = 1
  }
  
  // Clean empty parameters before sending
  params = cleanParams(params)
  
  router.get(route('renewal.index'), params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleFilterUpdate(newFilters) {
  filters.value = newFilters
  applyFilters()
}


function clearFilters() {
  router.get(route('renewal.index'), {}, {
    preserveState: false,
    preserveScroll: true,
  })
}

function resetFilters() {
  filters.value = {
    Name: '',
    Title: '',
    Case: '',
    Country: '',
    Qt: '',
    Fromdate: '',
    Untildate: '',
    grace_period: false,
  }
  applyFilters()
}

function applyFilters() {
  let params = { ...filters.value }
  if (currentStep.value) params.step = currentStep.value
  if (currentInvoiceStep.value) params.invoice_step = currentInvoiceStep.value
  if (myRenewalsOnly.value) params.my_renewals = 1
  
  // Clean empty parameters before sending
  params = cleanParams(params)
  
  router.get(route('renewal.index'), params, {
    preserveState: true,
    preserveScroll: true,
  })
}

function goToPage(page) {
  let params = { 
    ...filters.value,
    page: page
  }
  if (currentStep.value) params.step = currentStep.value
  if (currentInvoiceStep.value) params.invoice_step = currentInvoiceStep.value
  if (myRenewalsOnly.value) params.my_renewals = 1
  
  // Clean empty parameters before sending
  params = cleanParams(params)
  
  router.get(route('renewal.index'), params, {
    preserveState: true,
    preserveScroll: true,
  })
}


function handleSelectRenewal(id, checked) {
  if (checked) {
    selectedRenewals.value.push(id)
  } else {
    selectedRenewals.value = selectedRenewals.value.filter(rid => rid !== id)
  }
}

function handleAction(action) {
  // Handle export action that doesn't require selection
  if (action === 'export') {
    window.location.href = route('renewal.export')
    return
  }
  
  if (selectedRenewals.value.length === 0) {
    return
  }
  
  loading.value = true
  
  // Handle special actions that need different routes
  let routeName = action
  let params = { task_ids: selectedRenewals.value }
  
  switch (action) {
    case 'firstcall':
      // POST /renewal/call/1
      router.post('/renewal/call/1', params, {
        preserveState: false,
        preserveScroll: true,
        onSuccess: (page) => {
          // Flash messages are handled by MainLayout watcher
          selectedRenewals.value = []
        },
        onError: (errors) => {
          if (errors.error) {
            toast.error(errors.error)
          } else {
            toast.error(t('An error occurred while sending the call email'))
          }
        },
        onFinish: () => {
          loading.value = false
        }
      })
      return
    case 'renewalsSent':
      // POST /renewal/call/0
      router.post('/renewal/call/0', params, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          selectedRenewals.value = []
        },
        onError: (errors) => {
          if (errors.error) {
            toast.error(errors.error)
          } else {
            toast.error(t('An error occurred while marking renewals as sent'))
          }
        },
        onFinish: () => {
          loading.value = false
        }
      })
      return
    case 'remindercall':
      routeName = 'renewal.reminder'
      break
    case 'lastcall':
      routeName = 'renewal.lastcall'
      break
    case 'invoice':
      // POST /renewal/invoice/1
      router.post('/renewal/invoice/1', params, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          selectedRenewals.value = []
        },
        onError: (errors) => {
          if (errors.error) {
            toast.error(errors.error)
          } else {
            toast.error(t('An error occurred while generating invoices'))
          }
        },
        onFinish: () => {
          loading.value = false
        }
      })
      return
    case 'renewalOrder':
      routeName = 'renewal.order'
      break
    default:
      routeName = `renewal.${action}`
  }
  
  router.post(route(routeName), params, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: (page) => {
      selectedRenewals.value = []
    },
    onError: (errors) => {
      if (errors.error) {
        toast.error(errors.error)
      } else {
        toast.error(t('An error occurred while processing your request'))
      }
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

// Watch for renewal data changes
watch(() => props.renewals, () => {
  selectedRenewals.value = []
})

// Watch for myRenewalsOnly changes
watch(myRenewalsOnly, () => {
  applyFilters()
})

// Watch for step changes from props
watch(() => props.step, (newStep) => {
  if (newStep !== undefined && newStep !== null) {
    currentStep.value = parseInt(newStep)
  }
})

// Watch for invoice_step changes from props
watch(() => props.invoice_step, (newInvoiceStep) => {
  if (newInvoiceStep !== undefined && newInvoiceStep !== null) {
    currentInvoiceStep.value = parseInt(newInvoiceStep)
  }
})
</script>