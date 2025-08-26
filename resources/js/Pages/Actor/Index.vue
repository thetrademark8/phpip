<template>
  <MainLayout :title="t('actors.index.title')">
    <div class="space-y-4">
      <!-- Header with actions -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ t('actors.index.heading') }}</h1>
          <p class="text-muted-foreground">
            {{ t('actors.index.description') }}
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="openCreateDialog" v-if="canCreateActor" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ t('actors.index.create') }}
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
              <ActorFilters
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
        {{ t('actors.index.foundCount', actors.total || 0, { count: actors.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="tableColumns"
            :data="actors.data || []"
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
        :pagination="actors"
        @page-change="goToPage"
      />
    </div>

    <!-- Actor Dialog (unified for create/view/edit) -->
    <ActorDialog
      v-model:open="isActorDialogOpen"
      :actor-id="selectedActorId"
      :operation="dialogOperation"
      @show-actor="showActor"
      @success="handleActorSuccess"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, h, onMounted, watch, shallowRef } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash-es'
import { 
  Plus,
  ChevronDown,
  ChevronUp,
  Loader2,
  AlertTriangle,
  X
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import ActorDialog from '@/Components/dialogs/ActorDialog.vue'
import ActorFilters from '@/Components/actor/ActorFilters.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import { useActorFilters } from '@/composables/useActorFilters'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  actors: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const loading = ref(false)
const selectedActorId = ref(null)
const isActorDialogOpen = ref(false)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)
const sortField = ref(props.sort || 'name')
const sortDirection = ref(props.direction || 'asc')

// Load saved filter state from localStorage
onMounted(() => {
  const savedState = localStorage.getItem('actor-filters-collapsed')
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
} = useActorFilters(props.filters)

// Use simple permissions composable
const { role, isAdmin, isClient, canWrite, canRead, hasRole } = usePermissions()

// Simple action permissions
const canCreateActor = canWrite.value
const canViewActor = canRead.value
const canEditActor = canWrite.value
const canDeleteActor = isAdmin.value

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
    accessorKey: 'name',
    header: t('actors.columns.name'),
    cell: ({ row }) => {
      const canView = canViewActor
      return h('div', { 
        class: [
          'font-medium',
          canView ? 'cursor-pointer hover:underline text-primary' : 'text-muted-foreground cursor-not-allowed',
          row.original.warn && canView ? 'text-destructive' : ''
        ],
        onClick: canView ? () => showActor(row.original.id) : null
      }, [
        row.original.name,
        row.original.warn && canView && h(AlertTriangle, { class: 'inline ml-1 h-4 w-4' })
      ])
    },
  },
  {
    accessorKey: 'first_name',
    header: t('actors.columns.firstName'),
  },
  {
    accessorKey: 'display_name',
    header: t('actors.columns.displayName'),
  },
  {
    accessorKey: 'company.name',
    header: t('actors.columns.company'),
    cell: ({ row }) => row.original.company?.name || '',
  },
  {
    accessorKey: 'phy_person',
    header: t('actors.columns.type'),
    cell: ({ row }) => row.original.phy_person 
      ? t('actors.types.physical') 
      : t('actors.types.legal'),
  },
])

// Save filter state to localStorage
function saveFilterState(isOpen) {
  localStorage.setItem('actor-filters-collapsed', String(isOpen))
}

// Handle filter updates from the ActorFilters component
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
  
  router.get('/actor', params, {
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
  
  router.get('/actor', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function showActor(actorId) {
  // Find the actor to check permissions
  const actor = props.actors.data?.find(a => a.id === actorId)
  if (!actor || !canViewActor) {
    console.warn('Access denied to view actor')
    return
  }
  
  selectedActorId.value = actorId
  dialogOperation.value = 'view'
  isActorDialogOpen.value = true
}

function openCreateDialog() {
  selectedActorId.value = null
  dialogOperation.value = 'create'
  isActorDialogOpen.value = true
}

function handleSortChange({ field, direction }) {
  sortField.value = field || 'name'
  sortDirection.value = direction
  
  // Reset to first page when sorting changes
  const params = getApiParams()
  params.sort = sortField.value
  params.direction = sortDirection.value
  params.page = 1
  
  loading.value = true
  router.get('/actor', params, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleActorSuccess() {
  isActorDialogOpen.value = false
  router.reload({ only: ['actors'] })
}
</script>