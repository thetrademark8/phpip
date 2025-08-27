<template>
  <MainLayout :title="$t('Default actors')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ $t('Default actors') }}</h1>
          <p class="text-muted-foreground">{{ $t('defaultActors.title') }}</p>
        </div>
        <div class="flex gap-2">
          <Button @click="createDefaultActor" v-if="canWrite" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('defaultActors.new') }}
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
              <DefaultActorFilters
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
        {{ t('defaultActors.foundCount', defaultActors.total || 0, { count: defaultActors.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="columns"
            :data="defaultActors.data || []"
            :loading="loading"
            :show-pagination="false"
            :selectable="false"
            :page-size="15"
            :sort-field="sort"
            :sort-direction="direction"
            :get-row-id="(row) => row.id"
            @sort-change="handleSortChange"
          />
        </CardContent>
      </Card>

      <!-- Custom Pagination -->
      <Pagination
        :pagination="defaultActors"
        @page-change="goToPage"
      />

      <!-- Default Actor Dialog -->
      <DefaultActorDialog
        v-model:open="dialogOpen"
        :default-actor-id="selectedDefaultActorId"
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
import DataTable from '@/components/ui/DataTable.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import Pagination from '@/components/ui/Pagination.vue'
import DefaultActorFilters from '@/components/default-actor/DefaultActorFilters.vue'
import DefaultActorDialog from '@/components/dialogs/DefaultActorDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  defaultActors: Object,
  filters: Object,
  sort: String,
  direction: String,
})

const { t } = useI18n()
const page = usePage()
const { translated } = useTranslatedField()

// Permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.can_write
})

// State
const loading = ref(false)
const dialogOpen = ref(false)
const selectedDefaultActorId = ref(null)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)

// Filters
const filters = ref({
  Actor: props.filters?.Actor || '',
  Role: props.filters?.Role || '',
  Country: props.filters?.Country || '',
  Category: props.filters?.Category || '',
  Client: props.filters?.Client || '',
})

// Sort state
const sort = ref(props.sort || 'id')
const direction = ref(props.direction || 'asc')

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== '' && value !== null)
})

const activeFilterBadges = computed(() => {
  const badges = []
  if (filters.value.Actor) {
    badges.push({
      key: 'Actor',
      label: t('defaultActors.fields.actor'),
      value: filters.value.Actor
    })
  }
  if (filters.value.Role) {
    badges.push({
      key: 'Role', 
      label: t('defaultActors.fields.role'),
      value: filters.value.Role
    })
  }
  if (filters.value.Country) {
    badges.push({
      key: 'Country',
      label: t('defaultActors.fields.country'),
      value: filters.value.Country
    })
  }
  if (filters.value.Category) {
    badges.push({
      key: 'Category',
      label: t('defaultActors.fields.category'),
      value: filters.value.Category
    })
  }
  if (filters.value.Client) {
    badges.push({
      key: 'Client',
      label: t('defaultActors.fields.client'),
      value: filters.value.Client
    })
  }
  return badges
})

// Columns
const columns = computed(() => [
  {
    accessorKey: 'actor',
    header: t('defaultActors.fields.actor'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => h('div', {
      class: 'cursor-pointer hover:text-primary',
      onClick: () => showDefaultActor(row.original.id)
    }, row.original.actor?.name || '-'),
  },
  {
    accessorKey: 'role',
    header: t('defaultActors.fields.role'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => translated(row.original.roleInfo?.name) || '-',
  },
  {
    accessorKey: 'country',
    header: t('defaultActors.fields.country'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => row.original.country?.name || '-',
  },
  {
    accessorKey: 'category',
    header: t('defaultActors.fields.category'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => translated(row.original.category?.category) || '-',
  },
  {
    accessorKey: 'client',
    header: t('defaultActors.fields.client'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => row.original.client?.name || '-',
  },
])

// Methods
function showDefaultActor(id) {
  selectedDefaultActorId.value = id
  dialogOperation.value = 'view'
  dialogOpen.value = true
}

function createDefaultActor() {
  selectedDefaultActorId.value = null
  dialogOperation.value = 'create'
  dialogOpen.value = true
}

function handleFilterUpdate(newFilters) {
  filters.value = newFilters
  applyFilters()
}

function resetFilters() {
  filters.value = {
    Actor: '',
    Role: '',
    Country: '',
    Category: '',
    Client: '',
  }
  applyFilters()
}

function handleRemoveFilter(key) {
  filters.value[key] = ''
  applyFilters()
}

function applyFilters() {
  router.get(route('default_actor.index'), {
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
  router.get(route('default_actor.index'), {
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
  
  router.get(route('default_actor.index'), {
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