<template>
  <MainLayout :title="t('matter.index.title')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('matter.index.heading') }}</h1>
          <p class="text-muted-foreground">
            {{ t('matter.index.description') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="exportMatters" variant="outline" size="sm">
            <Download class="mr-2 h-4 w-4" />
            {{ t('matter.index.export') }}
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
                <CardTitle class="text-base">{{ t('matter.index.filters') }}</CardTitle>
              </div>
              <Button
                v-if="hasActiveFilters"
                @click="clearFilters"
                variant="ghost"
                size="sm"
              >
                {{ t('matter.index.clearAll') }}
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <MatterFilters
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
        {{ t('matter.index.foundCount', matters.total || 0, { count: matters.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="tableColumns"
            :data="matters.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="canWrite"
            :page-size="25"
            :get-row-id="(row) => row.id"
            :get-row-class="getRowClass"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            @update:selected="handleSelection"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="matters"
        @page-change="goToPage"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, h, onMounted, watch } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { format, parseISO, formatDistanceToNow } from 'date-fns'
import { debounce } from 'lodash-es'
import { useI18n } from 'vue-i18n'
import { 
  Download, 
  X, 
  ExternalLink,
  FileText,
  Hash,
  ChevronDown,
  ChevronUp
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/components/ui/DataTable.vue'
import MatterFilters from '@/components/matter/MatterFilters.vue'
import StatusBadge from '@/components/display/StatusBadge.vue'
import Pagination from '@/components/ui/Pagination.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { useMatterFilters } from '@/composables/useMatterFilters'
import { MatterFilterService } from '@/services/MatterFilterService'
import { CATEGORY_VARIANTS } from '@/constants/matter'
import { getTranslation } from '@/composables/useTranslation'

const props = defineProps({
  matters: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const page = usePage()
const loading = ref(false)
const selectedRows = ref([])
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'caseref')
const sortDirection = ref(props.direction || 'asc')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('matter-filters-collapsed')
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
} = useMatterFilters(props.filters)


const canWrite = computed(() => page.props.auth.user?.can_write)

// Create a debounced version of applyFilters
const debouncedApplyFilters = debounce(() => {
  applyFilters()
}, 300)

// Watch for filter changes and apply them automatically
watch(filters, () => {
  debouncedApplyFilters()
}, { deep: true })

// Handler for filter updates from MatterFilters component
function handleFilterUpdate(newFilters) {
  // Replace the entire filters object to trigger Vue's reactivity
  filters.value = { ...newFilters }
  // The watcher will then trigger applyFilters via debouncedApplyFilters
}

// Initialize filters from props on mount
onMounted(() => {
  if (props.filters) {
    updateFiltersFromServer(props.filters)
  }
})

// Table columns in the specified order
const tableColumns = [
  {
    accessorKey: 'Ref',
    header: t('matter.columns.reference'),
    cell: ({ row }) => h('div', { class: 'flex items-center gap-2' }, [
      h(Link, {
        href: `/matter/${row.original.id}`,
        class: 'text-primary hover:underline font-medium'
      }, row.original.Ref),
      row.original.Alt_Ref && h('span', { class: 'text-xs text-muted-foreground' }, `(${row.original.Alt_Ref})`)
    ]),
    enableSorting: true,
  },
  {
    accessorKey: 'Cat',
    header: t('matter.columns.category'),
    cell: ({ row }) => h(Badge, {
      variant: getCategoryVariant(row.original.Cat)
    }, row.original.Cat),
    meta: { headerClass: 'w-[80px]' },
  },
  {
    accessorKey: 'country_name',
    header: t('matter.columns.country'),
    cell: ({ row }) => h('div', { class: 'max-w-[120px] truncate', title: row.original.country_name }, row.original.country_name),
    enableSorting: true,
  },
  {
    accessorKey: 'Title',
    header: t('matter.columns.title'),
    cell: ({ row }) => h('div', {
      class: 'max-w-[250px] truncate',
      title: row.original.Title
    }, row.original.Title || row.original.Title2),
  },
  {
    accessorKey: 'image_id',
    header: t('matter.columns.logo'),
    cell: ({ row }) => row.original.image_id ? h('img', {
      src: `/classifier/${row.original.image_id}/img`,
      alt: 'Logo',
      class: 'h-8 w-auto object-contain',
      loading: 'lazy'
    }) : null,
    meta: { headerClass: 'w-[60px]' },
  },
  {
    accessorKey: 'classes',
    header: t('matter.columns.classes'),
    cell: ({ row }) => h('div', { class: 'max-w-[150px] truncate', title: row.original.classes }, row.original.classes),
  },
  {
    accessorKey: 'Client',
    header: t('matter.columns.client'),
    cell: ({ row }) => h('div', { class: 'max-w-[180px] truncate', title: row.original.Client }, row.original.Client),
    enableSorting: true,
  },
  {
    accessorKey: 'Owner',
    header: t('matter.columns.owner'),
    cell: ({ row }) => h('div', { class: 'max-w-[180px] truncate', title: row.original.Owner }, row.original.Owner),
    enableSorting: true,
  },
  {
    accessorKey: 'Status',
    header: t('matter.columns.status'),
    cell: ({ row }) => row.original.Status ? h(StatusBadge, {
      status: row.original.Status.split('|')[0],
      type: 'matter'
    }) : null,
    enableSorting: true,
  },
  {
    accessorKey: 'Filed',
    header: t('matter.columns.filedDate'),
    cell: ({ row }) => formatDate(row.original.Filed),
    enableSorting: true,
    meta: { headerClass: 'w-[100px]' },
  },
  {
    accessorKey: 'FilNo',
    header: t('matter.columns.filedNumber'),
    cell: ({ row }) => row.original.FilNo && h('div', { class: 'flex items-center gap-1' }, [
      h(FileText, { class: 'h-4 w-4 text-muted-foreground' }),
      h('span', row.original.FilNo)
    ]),
  },
  {
    accessorKey: 'registration_date',
    header: t('matter.columns.registrationDate'),
    cell: ({ row }) => {
      const regDate = row.original.Granted || row.original.Registration_DP
      return regDate ? formatDate(regDate) : null
    },
    enableSorting: true,
    meta: { headerClass: 'w-[100px]' },
  },
  {
    accessorKey: 'renewal_due',
    header: t('matter.columns.renewalDue'),
    cell: ({ row }) => row.original.renewal_due ? formatDate(row.original.renewal_due) : null,
    enableSorting: true,
    meta: { headerClass: 'w-[100px]' },
  }
]

function getCategoryVariant(category) {
  return CATEGORY_VARIANTS[category] || 'default'
}

function formatDate(date) {
  if (!date) return ''
  try {
    const parsed = parseISO(date)
    const daysDiff = Math.abs(new Date() - parsed) / (1000 * 60 * 60 * 24)
    
    if (daysDiff < 30) {
      return formatDistanceToNow(parsed, { addSuffix: true })
    }
    return format(parsed, 'dd/MM/yyyy')
  } catch {
    return date
  }
}

function getRowClass(row) {
  if (row.dead) return 'opacity-50'
  return ''
}

function handleSelection(selected) {
  selectedRows.value = selected
}

function handleRemoveFilter(key) {
  loading.value = true
  
  // Use the composable's removeFilter method instead
  removeFilter(key)
  
  // Apply the updated filters
  applyFilters()
}

function applyFilters() {
  loading.value = true
  
  MatterFilterService.applyFilters(filters.value, sortField.value, sortDirection.value)
    .finally(() => { loading.value = false })
}

function clearFilters() {
  loading.value = true
  
  MatterFilterService.clearFilters(filters.value)
    .finally(() => { loading.value = false })
}

function saveFilterState(open) {
  localStorage.setItem('matter-filters-collapsed', open ? 'true' : 'false')
}

function exportMatters() {
  MatterFilterService.exportMatters(filters.value)
}

function goToPage(page) {
  loading.value = true
  
  // Get current URL parameters
  const params = getApiParams()
  params.page = page
  params.sort = sortField.value
  params.direction = sortDirection.value
  
  router.get('/matter', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleSortChange({ field, direction }) {
  sortField.value = field || 'caseref'
  sortDirection.value = direction
  
  // Reset to first page when sorting changes
  const params = getApiParams()
  params.sort = sortField.value
  params.direction = sortDirection.value
  params.page = 1
  
  loading.value = true
  router.get('/matter', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>