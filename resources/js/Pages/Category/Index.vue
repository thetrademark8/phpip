<template>
  <MainLayout :title="$t('Categories')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ $t('Categories') }}</h1>
          <p class="text-muted-foreground">{{ $t('Manage matter categories') }}</p>
        </div>
        <div class="flex gap-2">
          <Button @click="createCategory" v-if="canWrite" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('New Category') }}
          </Button>
        </div>
      </div>

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
              <Button
                v-if="hasActiveFilters"
                @click="resetFilters"
                variant="ghost"
                size="sm"
              >
                {{ t('Clear all') }}
              </Button>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent>
              <CategoryFilters
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
        {{ t('categories.foundCount', categories.total || 0, { count: categories.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="columns"
            :data="categories.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="false"
            :page-size="15"
            :sort-field="sort"
            :sort-direction="direction"
            :get-row-id="(row) => row.code"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="categories"
        @page-change="goToPage"
      />

      <!-- Category Dialog -->
      <CategoryDialog
        v-model:open="dialogOpen"
        :category-id="selectedCategoryId"
        :operation="dialogOperation"
        @success="handleSuccess"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Plus, ChevronDown, ChevronUp, X } from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import { Button } from '@/Components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'
import Pagination from '@/Components/ui/Pagination.vue'
import CategoryFilters from '@/Components/category/CategoryFilters.vue'
import CategoryDialog from '@/Components/dialogs/CategoryDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  categories: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const page = usePage()
const { translated } = useTranslatedField()

// Permissions
const canWrite = computed(() => page.props.auth.user?.can_write)

// State
const loading = ref(false)
const dialogOpen = ref(false)
const selectedCategoryId = ref(null)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)

// Filters
const filters = ref({
  Code: props.filters?.Code || '',
  Category: props.filters?.Category || '',
})

// Sort state
const sort = ref(props.sort || 'code')
const direction = ref(props.direction || 'asc')

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== '' && value !== null)
})

const activeFilterBadges = computed(() => {
  const badges = []
  if (filters.value.Code) {
    badges.push({
      key: 'Code',
      label: t('categories.fields.code'),
      value: filters.value.Code
    })
  }
  if (filters.value.Category) {
    badges.push({
      key: 'Category', 
      label: t('categories.fields.category'),
      value: filters.value.Category
    })
  }
  return badges
})

// Columns
const columns = computed(() => [
  {
    accessorKey: 'code',
    header: t('categories.fields.code'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => h('div', { 
      class: 'font-mono cursor-pointer hover:text-primary',
      onClick: () => showCategory(row.original.code)
    }, row.original.code),
  },
  {
    accessorKey: 'category',
    header: t('categories.fields.category'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => h('div', {
      class: 'cursor-pointer hover:text-primary',
      onClick: () => showCategory(row.original.code)
    }, translated(row.original.category)),
  },
  {
    accessorKey: 'display_with',
    header: t('categories.fields.displayWith'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => {
      if (!row.original.display_with_info) return '-'
      return h('div', translated(row.original.display_with_info.category))
    },
  },
  {
    accessorKey: 'creator',
    header: t('common.creator'),
    meta: {
      sortable: false,
    },
    cell: ({ row }) => row.original.creator || '-',
  },
  {
    accessorKey: 'updater',
    header: t('common.updater'),
    meta: {
      sortable: false,
    },
    cell: ({ row }) => row.original.updater || '-',
  },
])

// Methods
function showCategory(code) {
  selectedCategoryId.value = code
  dialogOperation.value = 'view'
  dialogOpen.value = true
}

function createCategory() {
  selectedCategoryId.value = null
  dialogOperation.value = 'create'
  dialogOpen.value = true
}

function handleFilterUpdate(newFilters) {
  filters.value = newFilters
  applyFilters()
}

function resetFilters() {
  filters.value = {
    Code: '',
    Category: '',
  }
  applyFilters()
}

function handleRemoveFilter(key) {
  filters.value[key] = ''
  applyFilters()
}

function applyFilters() {
  router.get(route('category.index'), {
    ...filters.value,
    page: 1,
    sort: sort.value,
    direction: direction.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

function goToPage(page) {
  router.get(route('category.index'), {
    ...filters.value,
    page: page,
    sort: sort.value,
    direction: direction.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

function handleSortChange({ field, direction: newDirection }) {
  sort.value = field
  direction.value = newDirection
  
  router.get(route('category.index'), {
    ...filters.value,
    sort: field,
    direction: newDirection,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}

function handleSuccess() {
  router.reload({ preserveScroll: true })
}
</script>