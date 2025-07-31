<template>
  <MainLayout :title="t('rules.index.title')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('rules.index.heading') }}</h1>
          <p class="text-muted-foreground">
            {{ t('rules.index.description') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="openCreateDialog" v-if="canAdmin" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ t('rules.index.create') }}
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
                <CardTitle class="text-base">{{ t('rules.index.filters') }}</CardTitle>
              </div>
              <Button
                v-if="hasActiveFilters"
                @click="clearFilters"
                variant="ghost"
                size="sm"
              >
                {{ t('rules.index.clearAll') }}
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <RuleFilters
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
        {{ t('rules.index.foundCount', rules.total || 0, { count: rules.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="tableColumns"
            :data="rules.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="false"
            :page-size="15"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            :get-row-id="(row) => row.id"
            @row-click="handleRowClick"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="rules"
        @page-change="goToPage"
      />
    </div>

    <!-- Rule View/Edit Modal -->
    <RuleDialog
      v-model:open="isRuleDialogOpen"
      :rule-id="selectedRuleId"
      :operation="dialogOperation"
      @success="handleRuleSuccess"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, h, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash-es'
import { 
  Plus, 
  Check,
  X,
  ChevronDown,
  ChevronUp
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import RuleFilters from '@/Components/rule/RuleFilters.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import RuleDialog from '@/Components/dialogs/RuleDialog.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import { useRuleFilters } from '@/composables/useRuleFilters'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  rules: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const { translated } = useTranslatedField()
const page = usePage()
const loading = ref(false)
const selectedRuleId = ref(null)
const isRuleDialogOpen = ref(false)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'task')
const sortDirection = ref(props.direction || 'asc')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('rule-filters-collapsed')
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
} = useRuleFilters(props.filters)

// Check permissions
const canAdmin = computed(() => page.props.auth.user?.is_admin)

// Create a debounced version of applyFilters
const debouncedApplyFilters = debounce(() => {
  applyFilters()
}, 300)

// Watch for filter changes
watch(filters, () => {
  debouncedApplyFilters()
}, { deep: true })

// Define table columns
const tableColumns = computed(() => [
  {
    accessorKey: 'task',
    header: t('rules.columns.task'),
    cell: ({ row }) => {
      const taskName = translated(row.original.task_info?.name) || row.original.task
      return h('div', { 
        class: 'text-primary hover:underline cursor-pointer',
        onClick: () => showRule(row.original.id)
      }, taskName)
    },
    enableSorting: true,
    meta: { headerClass: 'w-[20%]' },
  },
  {
    accessorKey: 'detail',
    header: t('rules.columns.detail'),
    cell: ({ row }) => translated(row.original.detail) || '',
    enableSorting: true,
    meta: { headerClass: 'w-[15%]' },
  },
  {
    accessorKey: 'trigger_event',
    header: t('rules.columns.trigger'),
    cell: ({ row }) => translated(row.original.trigger?.name) || row.original.trigger_event,
    enableSorting: true,
    meta: { headerClass: 'w-[20%]' },
  },
  {
    accessorKey: 'for_category',
    header: t('rules.columns.category'),
    cell: ({ row }) => translated(row.original.category?.category) || '',
    enableSorting: true,
    meta: { headerClass: 'w-[15%]' },
  },
  {
    accessorKey: 'for_country',
    header: t('rules.columns.country'),
    cell: ({ row }) => row.original.for_country || '',
    enableSorting: true,
    meta: { headerClass: 'w-[7%]' },
  },
  {
    accessorKey: 'for_origin',
    header: t('rules.columns.origin'),
    cell: ({ row }) => row.original.for_origin || '',
    enableSorting: true,
    meta: { headerClass: 'w-[7%]' },
  },
  {
    accessorKey: 'for_type',
    header: t('rules.columns.type'),
    cell: ({ row }) => row.original.type?.type || '',
    enableSorting: true,
    meta: { headerClass: 'w-[9%]' },
  },
  {
    accessorKey: 'clear_task',
    header: 'C',
    headerTitle: t('rules.columns.clearTask'),
    cell: ({ row }) => row.original.clear_task ? h(Check, { class: 'h-4 w-4 mx-auto text-green-600' }) : '',
    enableSorting: false,
    meta: { headerClass: 'w-[3%] text-center' },
  },
  {
    accessorKey: 'delete_task',
    header: 'D',
    headerTitle: t('rules.columns.deleteTask'),
    cell: ({ row }) => row.original.delete_task ? h(Check, { class: 'h-4 w-4 mx-auto text-red-600' }) : '',
    enableSorting: false,
    meta: { headerClass: 'w-[3%] text-center' },
  },
])

// Initialize filters from props on mount
onMounted(() => {
  if (props.filters) {
    updateFiltersFromServer(props.filters)
  }
})

// Save filter state to localStorage
function saveFilterState(isOpen) {
  localStorage.setItem('rule-filters-collapsed', String(isOpen))
}

// Handle filter updates from the RuleFilters component
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
  
  router.get('/rule', params, {
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
  
  router.get('/rule', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleRowClick(row) {
  selectedRuleId.value = row.id
  dialogOperation.value = 'view'
  isRuleDialogOpen.value = true
}

function showRule(ruleId) {
  selectedRuleId.value = ruleId
  dialogOperation.value = 'view'
  isRuleDialogOpen.value = true
}

function openCreateDialog() {
  selectedRuleId.value = null
  dialogOperation.value = 'create'
  isRuleDialogOpen.value = true
}

function handleSortChange({ field, direction }) {
  sortField.value = field || 'task'
  sortDirection.value = direction
  
  // Reset to first page when sorting changes
  const params = getApiParams()
  params.sort = sortField.value
  params.direction = sortDirection.value
  params.page = 1
  
  loading.value = true
  router.get('/rule', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleRuleSuccess() {
  isRuleDialogOpen.value = false
  router.reload({ only: ['rules'] })
}
</script>