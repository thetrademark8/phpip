<template>
  <MainLayout :title="$t('Event Names Management')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('Event Names Management') }}</h1>
          <p class="text-muted-foreground">
            {{ t('Manage event names and their properties') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="openCreateDialog" v-if="canEdit" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ t('Create Event Name') }}
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
                {{ t('Clear All') }}
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <EventNameFilters
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
        {{ t('eventNames.foundCount', eventNames.total || 0, { count: eventNames.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="tableColumns"
            :data="eventNames.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="false"
            :page-size="21"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            :get-row-id="(row) => row.code"
            @row-click="handleRowClick"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="eventNames"
        @page-change="goToPage"
      />
    </div>

    <!-- Event Name View/Edit Modal -->
    <EventNameDialog
      v-model:open="isEventNameDialogOpen"
      :event-name-code="selectedEventNameCode"
      :operation="dialogOperation"
      @success="handleEventNameSuccess"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, h, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import debounce from 'lodash.debounce'
import { 
  Plus, 
  ChevronDown,
  ChevronUp,
  X
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/components/ui/DataTable.vue'
import EventNameFilters from '@/components/eventname/EventNameFilters.vue'
import Pagination from '@/components/ui/Pagination.vue'
import EventNameDialog from '@/components/eventname/EventNameDialog.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { useEventNameFilters } from '@/composables/useEventNameFilters'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  eventNames: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const { translated } = useTranslatedField()
const page = usePage()
const loading = ref(false)
const selectedEventNameCode = ref(null)
const isEventNameDialogOpen = ref(false)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'code')
const sortDirection = ref(props.direction || 'asc')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('eventname-filters-collapsed')
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
} = useEventNameFilters(props.filters)

// Check permissions
const canEdit = computed(() => page.props.auth.user?.can_write)

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
    accessorKey: 'code',
    header: t('Code'),
    meta: { 
      sortable: true,
      headerClass: 'w-[120px]'
    },
    cell: ({ row }) => {
      return h('div', { 
        class: 'text-primary hover:underline cursor-pointer font-mono text-sm',
        onClick: () => showEventName(row.original.code)
      }, row.original.code)
    },
  },
  {
    accessorKey: 'name',
    header: t('Name'),
    meta: { 
      sortable: true,
      cellClass: 'max-w-[200px]'
    },
    cell: ({ row }) => {
      const name = translated(row.original.name) || '-'
      return h('div', { 
        class: 'text-primary hover:underline cursor-pointer truncate',
        onClick: () => showEventName(row.original.code),
        title: name
      }, name)
    },
  },
  {
    accessorKey: 'is_task',
    header: t('Task'),
    meta: { 
      sortable: true,
      headerClass: 'w-[80px] text-center',
      cellClass: 'text-center'
    },
    cell: ({ row }) => {
      return h('div', {
        class: 'cursor-pointer',
        onClick: () => showEventName(row.original.code)
      }, [
        h(Badge, {
          variant: row.original.is_task ? 'default' : 'outline',
          class: 'text-xs'
        }, row.original.is_task ? '✓' : '✗')
      ])
    },
  },
  {
    accessorKey: 'status_event',
    header: t('Status'),
    meta: { 
      sortable: true,
      headerClass: 'w-[80px] text-center',
      cellClass: 'text-center'
    },
    cell: ({ row }) => {
      return h('div', {
        class: 'cursor-pointer',
        onClick: () => showEventName(row.original.code)
      }, [
        h(Badge, {
          variant: row.original.status_event ? 'default' : 'outline',
          class: 'text-xs'
        }, row.original.status_event ? '✓' : '✗')
      ])
    },
  },
  {
    accessorKey: 'country',
    header: t('Country'),
    meta: { 
      sortable: true,
      headerClass: 'w-[100px]',
      cellClass: 'text-sm'
    },
    cell: ({ row }) => {
      const country = row.original.countryInfo?.name || row.original.country || '-'
      return h('div', {
        class: 'cursor-pointer hover:text-primary transition-colors truncate',
        onClick: () => showEventName(row.original.code),
        title: country
      }, country)
    },
  },
  {
    accessorKey: 'category',
    header: t('Category'),
    meta: { 
      sortable: true,
      headerClass: 'w-[100px]',
      cellClass: 'text-sm'
    },
    cell: ({ row }) => {
      const category = translated(row.original.categoryInfo?.category) || row.original.category || '-'
      return h('div', {
        class: 'cursor-pointer hover:text-primary transition-colors truncate',
        onClick: () => showEventName(row.original.code),
        title: category
      }, category)
    },
  }
])

// Methods
function saveFilterState(isOpen) {
  localStorage.setItem('eventname-filters-collapsed', isOpen.toString())
}

function applyFilters() {
  loading.value = true
  
  const params = {
    ...getApiParams(),
    sort: sortField.value,
    direction: sortDirection.value
  }
  
  router.get('/eventname', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function clearFilters() {
  clearAllFilters()
  applyFilters()
}

function handleFilterUpdate(newFilters) {
  Object.assign(filters.value, newFilters)
}

function handleRemoveFilter(key) {
  removeFilter(key)
  applyFilters()
}

function handleSortChange({ field, direction }) {
  sortField.value = field
  sortDirection.value = direction
  
  loading.value = true
  
  const params = {
    ...getApiParams(),
    sort: field,
    direction: direction
  }
  
  router.get('/eventname', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function goToPage(page) {
  loading.value = true
  
  const params = {
    ...getApiParams(),
    sort: sortField.value,
    direction: sortDirection.value,
    page: page
  }
  
  router.get('/eventname', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleRowClick(eventName) {
  showEventName(eventName.code)
}

function showEventName(code) {
  selectedEventNameCode.value = code
  dialogOperation.value = 'view'
  isEventNameDialogOpen.value = true
}

function openCreateDialog() {
  selectedEventNameCode.value = null
  dialogOperation.value = 'create'
  isEventNameDialogOpen.value = true
}

function handleEventNameSuccess() {
  // Reload the current page to refresh the table data
  router.reload({ only: ['eventNames'] })
  isEventNameDialogOpen.value = false
}

// Initialize filter state from server
onMounted(() => {
  updateFiltersFromServer(props.filters)
})
</script>