<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Results info on the left -->
    <div v-if="showResultsInfo" class="text-sm text-muted-foreground">
      {{ $t('pagination.showingResults', { 
        from: fromResult, 
        to: toResult, 
        total: totalResults 
      }) }}
    </div>
    <!-- Empty div to push pagination to the right when no results info -->
    <div v-else></div>
    
    <!-- Pagination controls on the right -->
    <nav class="flex items-center space-x-2" role="navigation" aria-label="Pagination">
      <!-- Previous Button -->
      <Button
        variant="outline"
        size="sm"
        :disabled="current <= 1"
        @click="$emit('page-change', current - 1)"
      >
        <ChevronLeft class="h-4 w-4" />
        {{ $t('pagination.previous') }}
      </Button>

      <!-- Page Numbers -->
      <div class="flex items-center space-x-1">
        <template v-for="page in visiblePages" :key="page">
          <Button
            v-if="page !== '...'"
            variant="outline"
            size="sm"
            :class="{
              'bg-primary text-primary-foreground': page === current,
              'hover:bg-muted': page !== current
            }"
            @click="$emit('page-change', page)"
          >
            {{ page }}
          </Button>
          <span v-else class="px-2 py-1 text-muted-foreground">...</span>
        </template>
      </div>

      <!-- Next Button -->
      <Button
        variant="outline"
        size="sm"
        :disabled="current >= last"
        @click="$emit('page-change', current + 1)"
      >
        {{ $t('pagination.next') }}
        <ChevronRight class="h-4 w-4" />
      </Button>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'

const props = defineProps({
  // Support both individual props and pagination object
  currentPage: {
    type: Number,
    default: null
  },
  lastPage: {
    type: Number,
    default: null
  },
  // Pagination object support (Laravel pagination)
  pagination: {
    type: Object,
    default: null
  },
  // For showing results info
  from: {
    type: Number,
    default: null
  },
  to: {
    type: Number,
    default: null
  },
  total: {
    type: Number,
    default: null
  },
  links: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['page-change'])

const { t } = useI18n()

// Computed properties to support both patterns
const current = computed(() => {
  if (props.currentPage !== null) return props.currentPage
  if (props.pagination?.current_page) return props.pagination.current_page
  return 1
})

const last = computed(() => {
  if (props.lastPage !== null) return props.lastPage
  if (props.pagination?.last_page) return props.pagination.last_page
  return 1
})

const fromResult = computed(() => {
  if (props.from !== null) return props.from
  if (props.pagination?.from) return props.pagination.from
  return 1
})

const toResult = computed(() => {
  if (props.to !== null) return props.to
  if (props.pagination?.to) return props.pagination.to
  return 1
})

const totalResults = computed(() => {
  if (props.total !== null) return props.total
  if (props.pagination?.total) return props.pagination.total
  return 0
})

const showResultsInfo = computed(() => {
  return totalResults.value > 0
})

// Generate visible page numbers with ellipsis
const visiblePages = computed(() => {
  const pages = []
  const delta = 2 // Number of pages to show around current page
  
  // Always show first page
  pages.push(1)
  
  // Show ellipsis if needed
  if (current.value - delta > 2) {
    pages.push('...')
  }
  
  // Show pages around current page
  const start = Math.max(2, current.value - delta)
  const end = Math.min(last.value - 1, current.value + delta)
  
  for (let i = start; i <= end; i++) {
    if (!pages.includes(i)) {
      pages.push(i)
    }
  }
  
  // Show ellipsis if needed
  if (current.value + delta < last.value - 1) {
    if (!pages.includes('...')) {
      pages.push('...')
    }
  }
  
  // Always show last page (if more than 1 page)
  if (last.value > 1 && !pages.includes(last.value)) {
    pages.push(last.value)
  }
  
  return pages.filter((page, index, arr) => {
    // Remove duplicate ellipsis
    return !(page === '...' && arr[index - 1] === '...')
  })
})
</script>