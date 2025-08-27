<template>
  <MainLayout :title="t('Fees')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('Fees') }}</h1>
          <p class="text-muted-foreground">
            {{ t('Manage costs and fees') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="openCreateDialog" v-if="canWrite" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ t('Add a new line') }}
          </Button>
        </div>
      </div>

      <!-- Filters Card -->
      <Collapsible v-model:open="isFiltersOpen" @update:open="saveFilterState">
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
              <Button
                v-if="hasActiveFilters"
                @click="clearFilters"
                variant="ghost"
                size="sm"
              >
                Clear all
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <FeeFilters
                :filters="filters"
                @update:filters="handleFilterUpdate"
              />
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>

      <!-- Active Filters -->
      <div v-if="activeFilterBadges.length > 0" class="flex flex-wrap gap-2">
        <Badge
          v-for="badge in activeFilterBadges"
          :key="badge.key"
          variant="secondary"
          class="cursor-pointer"
          @click="handleRemoveFilter(badge.key)"
        >
          {{ badge.label }}: {{ badge.value }}
          <X class="ml-1 h-3 w-3" />
        </Badge>
      </div>

      <!-- Results count -->
      <div class="text-sm text-muted-foreground">
        {{ t('Found {count} fees', { count: fees.total || 0 }) }}
      </div>

      <!-- Custom Fee Table -->
      <Card>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <Table>
              <!-- Special Header -->
              <TableHead>
                <!-- Group Headers -->
                <TableRow class="text-primary-foreground">
                  <TableHeader class="w-1/4 text-center border-r border-primary-foreground/20"></TableHeader>
                  <TableHeader class="w-1/3 text-center border-r border-primary-foreground/20">
                    <div class="grid grid-cols-2">
                      <div class="py-2 border-r border-primary-foreground/20">{{ t('Standard') }}</div>
                      <div class="py-2">{{ t('Reduced') }}</div>
                    </div>
                  </TableHeader>
                  <TableHeader class="w-1/3 text-center border-r border-primary-foreground/20">
                    <div class="grid grid-cols-2">
                      <div class="py-2 border-r border-primary-foreground/20 bg-info/20">{{ t('Grace Standard') }}</div>
                      <div class="py-2">{{ t('Grace Reduced') }}</div>
                    </div>
                  </TableHeader>
                  <TableHeader class="w-auto text-center"></TableHeader>
                </TableRow>
                
                <!-- Sub Headers -->
                <TableRow class="text-primary-foreground">
                  <TableHeader>
                    <div class="grid grid-cols-4 gap-2 text-center">
                      <div>{{ t('Country') }}</div>
                      <div>{{ t('Category') }}</div>
                      <div>{{ t('Origin') }}</div>
                      <div>{{ t('Yr') }}</div>
                    </div>
                  </TableHeader>
                  <TableHeader class="border-x border-primary-foreground/20">
                    <div class="grid grid-cols-4 gap-1 text-center">
                      <div class="py-1">{{ t('Cost') }}</div>
                      <div class="py-1">{{ t('Fee') }}</div>
                      <div class="py-1">{{ t('Cost') }}</div>
                      <div class="py-1">{{ t('Fee') }}</div>
                    </div>
                  </TableHeader>
                  <TableHeader class="border-x border-primary-foreground/20">
                    <div class="grid grid-cols-4 gap-1 text-center">
                      <div class="py-1">{{ t('Cost') }}</div>
                      <div class="py-1">{{ t('Fee') }}</div>
                      <div class="py-1">{{ t('Cost') }}</div>
                      <div class="py-1">{{ t('Fee') }}</div>
                    </div>
                  </TableHeader>
                  <TableHeader class="text-center">{{ t('Currency') }}</TableHeader>
                </TableRow>
              </TableHead>
              
              <!-- Table Body -->
              <TableBody>
                <template v-if="loading">
                  <TableRow v-for="i in 10" :key="i">
                    <TableCell colspan="4">
                      <div class="flex space-x-4">
                        <Skeleton class="h-4 w-full" />
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
                <template v-else-if="fees.data?.length">
                  <TableRow 
                    v-for="fee in fees.data" 
                    :key="fee.id"
                    class="hover:bg-muted/50"
                  >
                    <!-- Basic Info -->
                    <TableCell>
                      <div class="grid grid-cols-4 gap-2 text-sm">
                        <div>{{ fee.for_country }}</div>
                        <div>{{ fee.for_category }}</div>
                        <div>{{ fee.for_origin }}</div>
                        <div>{{ fee.qt }}</div>
                      </div>
                    </TableCell>
                    
                    <!-- Standard/Reduced Costs and Fees -->
                    <TableCell>
                      <div class="grid grid-cols-4 gap-1">
                        <Input 
                          v-model="fee.cost" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          @blur="updateFee(fee, 'cost')"
                        />
                        <Input 
                          v-model="fee.fee" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          @blur="updateFee(fee, 'fee')"
                        />
                        <Input 
                          v-model="fee.cost_reduced" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'cost_reduced')"
                        />
                        <Input 
                          v-model="fee.fee_reduced" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'fee_reduced')"
                        />
                      </div>
                    </TableCell>
                    
                    <!-- Grace Costs and Fees -->
                    <TableCell>
                      <div class="grid grid-cols-4 gap-1">
                        <Input 
                          v-model="fee.cost_sup" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'cost_sup')"
                        />
                        <Input 
                          v-model="fee.fee_sup" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'fee_sup')"
                        />
                        <Input 
                          v-model="fee.cost_sup_reduced" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'cost_sup_reduced')"
                        />
                        <Input 
                          v-model="fee.fee_sup_reduced" 
                          class="text-right text-sm"
                          type="number"
                          step="0.01"
                          placeholder="0.00"
                          @blur="updateFee(fee, 'fee_sup_reduced')"
                        />
                      </div>
                    </TableCell>
                    
                    <!-- Currency -->
                    <TableCell>
                      <CurrencySelect 
                        v-model="fee.currency" 
                        placeholder="EUR"
                        class="w-24"
                        @update:model-value="updateFee(fee, 'currency')"
                      />
                    </TableCell>
                  </TableRow>
                </template>
                <TableRow v-else>
                  <TableCell colspan="4" class="text-center py-8">
                    {{ t('No fees found') }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="fees"
        @page-change="goToPage"
      />
    </div>

    <!-- Fee Dialog (for create/edit) -->
    <FeeDialog
      v-model:open="isFeeDialogOpen"
      :fee-id="selectedFeeId"
      :operation="dialogOperation"
      @success="handleFeeSuccess"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash-es'
import { 
  Plus,
  ChevronDown,
  ChevronUp,
  X
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import FeeFilters from '@/components/fee/FeeFilters.vue'
import FeeDialog from '@/components/fee/FeeDialog.vue'
import Pagination from '@/components/ui/Pagination.vue'
import CurrencySelect from '@/components/ui/form/CurrencySelect.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { useFeeFilters } from '@/composables/useFeeFilters'

const props = defineProps({
  fees: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const loading = ref(false)
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'for_country')
const sortDirection = ref(props.direction || 'asc')
const isFeeDialogOpen = ref(false)
const selectedFeeId = ref(null)
const dialogOperation = ref('create')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('fee-filters-collapsed')
  if (savedState !== null) {
    isFiltersOpen.value = savedState === 'false' ? false : true
  }
})

// Use the composable for filter management
const { 
  filters, 
  activeFilterBadges, 
  hasActiveFilters,
  clearFilters: clearAllFilters,
  removeFilter,
  updateFiltersFromServer,
  getApiParams
} = useFeeFilters(props.filters)

// Check permissions
const canWrite = computed(() => {
  const user = usePage().props.auth?.user
  return user?.can_write
})

// Create a debounced version of applyFilters
const debouncedApplyFilters = debounce(() => {
  applyFilters()
}, 300)

// Watch for filter changes
watch(filters, () => {
  debouncedApplyFilters()
}, { deep: true })

// Save filter state to localStorage
function saveFilterState(isOpen) {
  localStorage.setItem('fee-filters-collapsed', String(isOpen))
}

// Handle filter updates from the FeeFilters component
function handleFilterUpdate(newFilters) {
  Object.assign(filters.value, newFilters)
}

// Handle removing individual filter badges
function handleRemoveFilter(key) {
  removeFilter(key)
}

// Clear all filters
function clearFilters() {
  clearAllFilters()
}

function applyFilters() {
  loading.value = true
  
  const params = getApiParams()
  // Add sort parameters
  params.sort = sortField.value
  params.direction = sortDirection.value
  
  router.get('/fee', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function goToPage(page) {
  loading.value = true
  
  // Get current URL parameters
  const params = getApiParams()
  params.page = page
  // Include sort parameters
  params.sort = sortField.value
  params.direction = sortDirection.value
  
  router.get('/fee', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function openCreateDialog() {
  selectedFeeId.value = null
  dialogOperation.value = 'create'
  isFeeDialogOpen.value = true
}

function handleFeeSuccess() {
  isFeeDialogOpen.value = false
  router.reload({ only: ['fees'] })
}

function editFee(feeId) {
  selectedFeeId.value = feeId
  dialogOperation.value = 'edit'
  isFeeDialogOpen.value = true
}

// Debounced function to update fee
const debouncedUpdateFee = debounce((fee, field) => {
  // TODO: Implement inline editing logic
  router.put(`/fee/${fee.id}`, {
    [field]: fee[field]
  }, {
    preserveState: true,
    preserveScroll: true,
    onError: (errors) => {
      console.error('Error updating fee:', errors)
    }
  })
}, 500)

function updateFee(fee, field) {
  debouncedUpdateFee(fee, field)
}
</script>