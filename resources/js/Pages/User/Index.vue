<template>
  <MainLayout :title="$t('Users')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ $t('Users') }}</h1>
          <p class="text-muted-foreground">{{ $t('User information') }}</p>
        </div>
        <div class="flex gap-2">
          <Button @click="createUser" v-if="canWrite" size="sm">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('Create user') }}
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
              <UserFilters
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
        {{ t('users.foundCount', users.total || 0, { count: users.total || 0 }) }}
      </div>

      <!-- Data Table -->
      <Card>
        <CardContent class="p-0">
          <DataTable
            :columns="columns"
            :data="users.data || []"
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
        :pagination="users"
        @page-change="goToPage"
      />

      <!-- User Dialog -->
      <UserDialog
        v-model:open="dialogOpen"
        :user-id="selectedUserId"
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
import { Plus, ChevronDown, ChevronUp, X, AlertTriangle } from 'lucide-vue-next'
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
import UserFilters from '@/Components/user/UserFilters.vue'
import UserDialog from '@/Components/dialogs/UserDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  users: Object,
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
const selectedUserId = ref(null)
const dialogOperation = ref('view')
const isFiltersOpen = ref(true)

// Filters
const filters = ref({
  Name: props.filters?.Name || '',
  Role: props.filters?.Role || '',
  Username: props.filters?.Username || '',
  Company: props.filters?.Company || '',
})

// Sort state
const sort = ref(props.sort || 'name')
const direction = ref(props.direction || 'asc')

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== '' && value !== null)
})

const activeFilterBadges = computed(() => {
  const badges = []
  if (filters.value.Name) {
    badges.push({
      key: 'Name',
      label: t('Name'),
      value: filters.value.Name
    })
  }
  if (filters.value.Role) {
    badges.push({
      key: 'Role', 
      label: t('Role'),
      value: filters.value.Role
    })
  }
  if (filters.value.Username) {
    badges.push({
      key: 'Username',
      label: t('User name'),
      value: filters.value.Username
    })
  }
  if (filters.value.Company) {
    badges.push({
      key: 'Company',
      label: t('Company'),
      value: filters.value.Company
    })
  }
  return badges
})

// Columns
const columns = computed(() => [
  {
    accessorKey: 'name',
    header: t('Name'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => {
      const user = row.original
      return h('div', { 
        class: `cursor-pointer hover:text-primary flex items-center gap-2 ${user.warn ? 'text-destructive' : ''}`,
        onClick: () => showUser(user.id)
      }, [
        user.warn ? h(AlertTriangle, { class: 'h-4 w-4' }) : null,
        user.name
      ])
    },
  },
  {
    accessorKey: 'role',
    header: t('Role'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => translated(row.original.role_info?.name) || row.original.default_role || '-',
  },
  {
    accessorKey: 'login',
    header: t('User name'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => h('div', {
      class: 'font-mono'
    }, row.original.login),
  },
  {
    accessorKey: 'company',
    header: t('Company'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => row.original.company?.name || '-',
  },
  {
    accessorKey: 'email',
    header: t('Email'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => row.original.email || '-',
  },
  {
    accessorKey: 'language',
    header: t('Language'),
    meta: {
      sortable: true,
    },
    cell: ({ row }) => {
      const lang = row.original.language
      const languages = {
        'en_GB': 'English (British)',
        'en_US': 'English (American)', 
        'fr': 'Français',
        'de': 'Deutsch',
        'es': 'Español'
      }
      return languages[lang] || lang || '-'
    },
  },
])

// Methods
function showUser(id) {
  selectedUserId.value = id
  dialogOperation.value = 'view'
  dialogOpen.value = true
}

function createUser() {
  selectedUserId.value = null
  dialogOperation.value = 'create'
  dialogOpen.value = true
}

function handleFilterUpdate(newFilters) {
  filters.value = newFilters
  applyFilters()
}

function resetFilters() {
  filters.value = {
    Name: '',
    Role: '',
    Username: '',
    Company: '',
  }
  applyFilters()
}

function handleRemoveFilter(key) {
  filters.value[key] = ''
  applyFilters()
}

function applyFilters() {
  router.get(route('user.index'), {
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
  router.get(route('user.index'), {
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
  
  router.get(route('user.index'), {
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