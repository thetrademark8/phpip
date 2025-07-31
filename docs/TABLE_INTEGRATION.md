# Table Integration Guide

This guide documents the standard pattern for implementing tables in the phpIP application. All table implementations must follow this pattern for consistency.

## Overview

Tables in this application use a specific component structure that combines:
- **DataTable component** (based on TanStack Table) for the table itself
- **Separate Pagination component** for page navigation
- **Collapsible filters** in a Card component
- **Active filter badges** for visibility
- **Server-side operations** for sorting, filtering, and pagination

## Component Structure

The standard table page structure follows this hierarchy:

```
MainLayout
└── div.space-y-4
    ├── Header (title, description, action buttons)
    ├── Collapsible Filters Card
    ├── Active Filter Badges
    ├── Results Count
    ├── DataTable in Card
    └── Pagination Component
```

## Required Imports

```javascript
// Core Vue and Inertia
import { ref, computed, h } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

// UI Components
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/ui/DataTable.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'

// Icons
import { Plus, ChevronDown, ChevronUp, X } from 'lucide-vue-next'

// Your specific components
import YourFilters from '@/Components/your-module/YourFilters.vue'
import YourDialog from '@/Components/dialogs/YourDialog.vue'
```

## Template Structure

### 1. Header Section

```vue
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div>
    <h1 class="text-2xl font-bold tracking-tight">{{ t('module.index.heading') }}</h1>
    <p class="text-muted-foreground">{{ t('module.index.description') }}</p>
  </div>
  <div class="flex gap-2">
    <Button @click="createNew" v-if="canWrite" size="sm">
      <Plus class="mr-2 h-4 w-4" />
      {{ t('module.index.create') }}
    </Button>
  </div>
</div>
```

### 2. Filters Card (Collapsible)

```vue
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
        <YourFilters
          :filters="filters"
          @update:filters="handleFilterUpdate"
          @reset="resetFilters"
        />
      </CardContent>
    </CollapsibleContent>
  </Card>
</Collapsible>
```

### 3. Active Filter Badges

```vue
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
```

### 4. Results Count

```vue
<div class="text-sm text-muted-foreground">
  {{ t('module.foundCount', items.total || 0, { count: items.total || 0 }) }}
</div>
```

### 5. DataTable Component

**IMPORTANT**: Always set `:show-pagination="false"` and use the separate Pagination component.

```vue
<Card>
  <CardContent class="p-0">
    <DataTable
      :columns="columns"
      :data="items.data || []"
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
```

### 6. Pagination Component

```vue
<Pagination
  :pagination="items"
  @page-change="goToPage"
/>
```

## Column Definition Format

Columns must use the TanStack Table format with `accessorKey` and `header`:

```javascript
const columns = computed(() => [
  {
    accessorKey: 'field_name',     // Required: maps to data property
    header: t('Column Header'),     // Required: column header text
    meta: {                        // Optional: metadata
      sortable: true,              // Enable sorting
      headerClass: 'w-[120px]',    // Header styling
      cellClass: 'text-center'     // Cell styling
    },
    cell: ({ row }) => {           // Optional: custom cell renderer
      return h('div', {
        class: 'cursor-pointer hover:text-primary',
        onClick: () => showDetails(row.original.id)
      }, row.original.field_name)
    },
    enableSorting: true            // Enable column sorting
  }
])
```

**Common Mistakes**:
- ❌ Using `key` and `label` instead of `accessorKey` and `header`
- ❌ Putting `sortable` at the root level instead of in `meta`
- ❌ Not using the `cell` function for custom rendering

## State Management

```javascript
// Required state
const loading = ref(false)
const isFiltersOpen = ref(true)

// Filter state
const filters = ref({
  field1: props.filters?.field1 || '',
  field2: props.filters?.field2 || '',
})

// Sort state
const sort = ref(props.sort || 'default_field')
const direction = ref(props.direction || 'asc')

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(value => value !== '' && value !== null)
})

const activeFilterBadges = computed(() => {
  const badges = []
  if (filters.value.field1) {
    badges.push({
      key: 'field1',
      label: t('fields.field1'),
      value: filters.value.field1
    })
  }
  return badges
})
```

## Methods Implementation

### Filter Handling

```javascript
function handleFilterUpdate(newFilters) {
  filters.value = newFilters
  applyFilters()
}

function resetFilters() {
  filters.value = {
    field1: '',
    field2: '',
  }
  applyFilters()
}

function handleRemoveFilter(key) {
  filters.value[key] = ''
  applyFilters()
}

function applyFilters() {
  router.get(route('module.index'), {
    ...filters.value,
    page: 1,
    sort: sort.value,
    direction: direction.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
```

### Pagination

```javascript
function goToPage(page) {
  router.get(route('module.index'), {
    ...filters.value,
    page: page,
    sort: sort.value,
    direction: direction.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
```

### Sorting

```javascript
function handleSortChange({ field, direction: newDirection }) {
  sort.value = field
  direction.value = newDirection
  
  router.get(route('module.index'), {
    ...filters.value,
    sort: field,
    direction: newDirection,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
```

## Complete Example: Matter Index

See `/resources/js/Pages/Matter/Index.vue` for a complete implementation that includes:
- Multiple view modes (actor view, status view)
- Complex column definitions with custom rendering
- Filter state persistence
- Export functionality
- Row selection
- Custom row styling

## Key Principles

1. **Server-side Operations**: All filtering, sorting, and pagination happens server-side
2. **Consistent Structure**: Always follow the component hierarchy shown above
3. **Separate Pagination**: Never use DataTable's built-in pagination
4. **Proper Column Format**: Always use `accessorKey` and `header`
5. **State Management**: Keep filter, sort, and pagination state in sync with URL
6. **User Experience**: Show loading states, active filters, and result counts

## Common Pitfalls to Avoid

1. **Creating custom table implementations** - Always use the DataTable component
2. **Using DataTable pagination** - Always use the separate Pagination component
3. **Client-side filtering/sorting** - All operations should be server-side
4. **Wrong column format** - Must use TanStack Table format
5. **Missing Card wrapper** - DataTable should always be wrapped in Card/CardContent
6. **Not preserving state** - Always use `preserveState: true` in router navigation
7. **Forgetting translations** - All text should use i18n translations

## Controller Requirements

Your Laravel controller should:
- Support pagination with `paginate()`
- Handle sort parameters (`sort` and `direction`)
- Return filter parameters in the response
- Use Inertia::render() with proper data structure

Example:
```php
return Inertia::render('Module/Index', [
    'items' => $query->paginate(15),
    'filters' => $request->only(['field1', 'field2']),
    'sort' => $sort,
    'direction' => $direction,
]);
```