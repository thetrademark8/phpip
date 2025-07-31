<template>
  <div class="w-full">
    <!-- Table -->
    <div>
      <Table>
        <TableHead>
          <TableRow>
            <!-- Selection Checkbox Column -->
            <TableHeader v-if="selectable" class="w-[50px]">
              <Checkbox
                :checked="table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')"
                @update:checked="value => table.toggleAllPageRowsSelected(!!value)"
                aria-label="Select all"
              />
            </TableHeader>

            <!-- Data Columns -->
            <TableHeader
              v-for="header in table.getHeaderGroups()[0].headers"
              :key="header.id"
              :class="header.column.columnDef.meta?.headerClass"
            >
              <div 
                v-if="!header.isPlaceholder"
                :class="header.column.getCanSort() ? 'cursor-pointer select-none flex items-center gap-1' : ''"
                @click="header.column.getToggleSortingHandler()?.($event)"
              >
                {{ header.column.columnDef.header }}
                <template v-if="header.column.getCanSort()">
                  <ArrowUpDown v-if="!header.column.getIsSorted()" class="h-4 w-4" />
                  <ArrowUp v-else-if="header.column.getIsSorted() === 'asc'" class="h-4 w-4" />
                  <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="h-4 w-4" />
                </template>
              </div>
            </TableHeader>
          </TableRow>
        </TableHead>
        <TableBody>
          <template v-if="loading">
            <TableRowSkeleton 
              :rows="pageSize"
              :columns="table.getAllColumns().length"
              :show-checkbox="selectable"
              :column-widths="table.getAllColumns().map(col => col.columnDef.meta?.headerClass || '')"
            />
          </template>
          <template v-else-if="table.getRowModel().rows?.length">
            <TableRow 
              v-for="row in table.getRowModel().rows" 
              :key="row.id"
              :data-state="row.getIsSelected() && 'selected'"
              :class="cn('h-[60px]', props.getRowClass?.(row.original))"
            >
              <!-- Selection Checkbox Cell -->
              <TableCell v-if="selectable">
                <Checkbox
                  :checked="row.getIsSelected()"
                  @update:checked="value => row.toggleSelected(!!value)"
                  :aria-label="`Select row ${row.index + 1}`"
                />
              </TableCell>

              <!-- Data Cells -->
              <TableCell
                v-for="cell in row.getVisibleCells()"
                :key="cell.id"
                :class="cell.column.columnDef.meta?.cellClass"
              >
                <component
                  v-if="cell.column.columnDef.cell"
                  :is="cell.column.columnDef.cell"
                  v-bind="cell.getContext()"
                />
                <span v-else>{{ cell.getValue() }}</span>
              </TableCell>
            </TableRow>
          </template>
          <TableRow v-else class="h-[60px]">
            <TableCell 
              :colspan="table.getAllColumns().length + (selectable ? 1 : 0)" 
              class="text-center"
            >
              {{ emptyMessage }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && showPagination && table.getPageCount() > 1" class="flex items-center justify-between px-2 py-4">
      <div class="flex-1 text-sm text-muted-foreground">
        <template v-if="selectable">
          {{ table.getFilteredSelectedRowModel().rows.length }} of
          {{ table.getFilteredRowModel().rows.length }} row(s) selected
        </template>
      </div>
      
      <div class="flex items-center space-x-6 lg:space-x-8">
        <div class="flex items-center space-x-2">
          <p class="text-sm font-medium">Rows per page</p>
          <Select
            :model-value="`${table.getState().pagination.pageSize}`"
            @update:model-value="table.setPageSize(Number($event))"
          >
            <SelectTrigger class="h-8 w-[70px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="pageSize in pageSizeOptions" :key="pageSize" :value="`${pageSize}`">
                {{ pageSize }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div class="flex w-[100px] items-center justify-center text-sm font-medium">
          Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
        </div>
        
        <div class="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import {
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ArrowUp, ArrowDown, Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Checkbox } from '@/Components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'
import TableRowSkeleton from '@/Components/ui/skeleton/TableRowSkeleton.vue'
import { cn } from '@/lib/utils'

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  data: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  showPagination: {
    type: Boolean,
    default: true,
  },
  pageSize: {
    type: Number,
    default: 10,
  },
  pageSizeOptions: {
    type: Array,
    default: () => [10, 20, 30, 40, 50],
  },
  emptyMessage: {
    type: String,
    default: 'No results found.',
  },
  getRowId: {
    type: Function,
    default: undefined,
  },
  getRowClass: {
    type: Function,
    default: undefined,
  },
  sortField: {
    type: String,
    default: null,
  },
  sortDirection: {
    type: String,
    default: 'asc',
  },
})

const emit = defineEmits(['update:selected', 'sort-change'])

// Initialize sorting state based on props
const sorting = ref(
  props.sortField 
    ? [{
        id: props.sortField,
        desc: props.sortDirection === 'desc'
      }]
    : []
)

// Row selection state
const rowSelection = ref({})

// Create table instance with reactive data
const table = useVueTable({
  get data() { return props.data },
  get columns() { return props.columns },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  onSortingChange: updaterOrValue => {
    sorting.value = typeof updaterOrValue === 'function' 
      ? updaterOrValue(sorting.value) 
      : updaterOrValue
    
    // Emit sort change event to parent
    if (sorting.value.length > 0) {
      const sort = sorting.value[0]
      emit('sort-change', {
        field: sort.id,
        direction: sort.desc ? 'desc' : 'asc'
      })
    } else {
      emit('sort-change', {
        field: null,
        direction: 'asc'
      })
    }
  },
  onRowSelectionChange: updaterOrValue => {
    rowSelection.value = typeof updaterOrValue === 'function'
      ? updaterOrValue(rowSelection.value)
      : updaterOrValue
  },
  state: {
    get sorting() { return sorting.value },
    get rowSelection() { return rowSelection.value },
  },
  enableRowSelection: props.selectable,
  getRowId: props.getRowId,
  initialState: {
    pagination: {
      pageSize: props.pageSize,
    },
  },
})

// Emit selected rows when selection changes
watch(rowSelection, () => {
  if (props.selectable) {
    const selectedRows = table.getFilteredSelectedRowModel().rows
    emit('update:selected', selectedRows.map(row => row.original))
  }
})

// Update table data and reset selection when data changes
watch(() => props.data, (newData) => {
  console.log('DataTable data changed, updating table with:', newData?.length, 'rows')
  // Reset selection
  rowSelection.value = {}
  
  // Force table to update with new data
  table.setOptions(prev => ({
    ...prev,
    data: newData || []
  }))
}, { immediate: false })
</script>