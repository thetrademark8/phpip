<template>
  <MainLayout :title="t('templateMember.index.title')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('templateMember.index.heading') }}</h1>
          <p class="text-muted-foreground">
            {{ t('templateMember.index.description') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="handleCreate" v-if="canWrite" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ t('templateMember.index.create') }}
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
                <CardTitle class="text-base">{{ t('templateMember.index.filters') }}</CardTitle>
              </div>
              <Button
                v-if="hasActiveFilters"
                @click="clearFilters"
                variant="ghost"
                size="sm"
              >
                {{ t('templateMember.index.clearAll') }}
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <TemplateMemberFilters
                :filters="filters"
                :languages="languages"
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
        {{ t('templateMember.index.foundCount', template_members.total || 0, { count: template_members.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="tableColumns"
            :data="template_members.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="false"
            :page-size="15"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            :get-row-id="(row) => row.id"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="template_members"
        @page-change="goToPage"
      />
    </div>

    <!-- TemplateMember Dialog (unified for create/view/edit) -->
    <TemplateMemberDialog
      v-model:open="dialogOpen"
      :member-id="selectedMemberId"
      :operation="operation"
      @success="refreshData"
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
  ChevronDown,
  ChevronUp,
  X
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/components/ui/DataTable.vue'
import TemplateMemberFilters from '@/components/template-member/TemplateMemberFilters.vue'
import TemplateMemberDialog from '@/components/dialogs/TemplateMemberDialog.vue'
import Pagination from '@/components/ui/Pagination.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { useTemplateMemberFilters } from '@/composables/useTemplateMemberFilters'

const props = defineProps({
  template_members: Object,
  filters: Object,
  sort: String,
  direction: String,
  languages: Array,
})

const { t } = useI18n()
const page = usePage()
const loading = ref(false)
const selectedMemberId = ref(null)
const dialogOpen = ref(false)
const operation = ref('view')
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'summary')
const sortDirection = ref(props.direction || 'asc')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('templateMember-filters-collapsed')
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
} = useTemplateMemberFilters(props.filters)

// Check permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
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

// Define table columns
const tableColumns = computed(() => [
  {
    accessorKey: 'summary',
    header: t('templateMember.columns.summary'),
    cell: ({ row }) => h('div', { 
      class: 'font-medium cursor-pointer hover:underline text-primary max-w-[300px] truncate',
      onClick: () => showMember(row.original.id),
      title: row.original.summary
    }, row.original.summary),
    enableSorting: true,
  },
  {
    accessorKey: 'class.name',
    header: t('templateMember.columns.class'),
    cell: ({ row }) => h('div', { 
      class: 'text-muted-foreground'
    }, row.original.class?.name || '—'),
    enableSorting: true,
  },
  {
    accessorKey: 'language',
    header: t('templateMember.columns.language'),
    cell: ({ row }) => h('div', { 
      class: 'text-center'
    }, row.original.language || '—'),
    enableSorting: true,
  },
  {
    accessorKey: 'style',
    header: t('templateMember.columns.style'),
    cell: ({ row }) => h('div', { 
      class: 'text-muted-foreground'
    }, row.original.style || '—'),
    enableSorting: true,
  },
  {
    accessorKey: 'format',
    header: t('templateMember.columns.format'),
    cell: ({ row }) => h('div', { 
      class: 'text-muted-foreground'
    }, row.original.format || '—'),
    enableSorting: true,
  },
  {
    accessorKey: 'category',
    header: t('templateMember.columns.category'),
    cell: ({ row }) => h('div', { 
      class: 'text-muted-foreground'
    }, row.original.category || '—'),
    enableSorting: true,
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
  localStorage.setItem('templateMember-filters-collapsed', String(isOpen))
}

// Handle filter updates from the TemplateMemberFilters component
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
  
  router.get('/template-member', params, {
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
  
  router.get('/template-member', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleCreate() {
  selectedMemberId.value = null
  operation.value = 'create'
  dialogOpen.value = true
}

function showMember(memberId) {
  selectedMemberId.value = memberId
  operation.value = 'view'
  dialogOpen.value = true
}

function handleSortChange({ field, direction }) {
  sortField.value = field || 'summary'
  sortDirection.value = direction
  
  // Reset to first page when sorting changes
  const params = getApiParams()
  params.sort = sortField.value
  params.direction = sortDirection.value
  params.page = 1
  
  loading.value = true
  router.get('/template-member', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function refreshData() {
  dialogOpen.value = false
  router.reload({ only: ['template_members'] })
}
</script>