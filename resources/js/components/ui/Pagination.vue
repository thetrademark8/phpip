<template>
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Results Info -->
    <div v-if="showInfo" class="text-sm text-muted-foreground order-2 sm:order-1">
      {{ t('pagination.showing') }} {{ pagination.from || 0 }} {{ t('pagination.to') }} {{ pagination.to || 0 }} {{ t('pagination.of') }} {{ pagination.total || 0 }} {{ t('pagination.results') }}
    </div>

    <!-- Pagination Controls -->
    <div class="flex items-center gap-1 order-1 sm:order-2">
      <!-- Mobile Simplified View -->
      <div class="flex sm:hidden items-center gap-2">
        <Button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="!pagination.prev_page_url"
          variant="outline"
          size="sm"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>
        
        <span class="text-sm px-3">
          {{ pagination.current_page }}/{{ pagination.last_page }}
        </span>
        
        <Button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="!pagination.next_page_url"
          variant="outline"
          size="sm"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>

      <!-- Desktop Full View -->
      <div class="hidden sm:flex items-center gap-1">
        <!-- First Page -->
        <Button
          v-if="pagination.current_page > 2"
          @click="goToPage(1)"
          variant="outline"
          size="sm"
          :title="t('pagination.first')"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>

        <!-- Previous Page -->
        <Button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="!pagination.prev_page_url"
          variant="outline"
          size="sm"
          :title="t('pagination.previous')"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>

        <!-- Page Numbers -->
        <template v-for="(page, index) in visiblePages" :key="index">
          <Button
            v-if="page !== '...'"
            @click="goToPage(page)"
            :variant="page === pagination.current_page ? 'default' : 'outline'"
            size="sm"
            class="min-w-[40px]"
            :aria-current="page === pagination.current_page ? 'page' : undefined"
          >
            {{ page }}
          </Button>
          <span v-else class="px-2 text-muted-foreground">...</span>
        </template>

        <!-- Next Page -->
        <Button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="!pagination.next_page_url"
          variant="outline"
          size="sm"
          :title="t('pagination.next')"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>

        <!-- Last Page -->
        <Button
          v-if="pagination.current_page < pagination.last_page - 1"
          @click="goToPage(pagination.last_page)"
          variant="outline"
          size="sm"
          :title="t('pagination.last')"
        >
          <ChevronsRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  ChevronLeft, 
  ChevronRight,
  ChevronsLeft,
  ChevronsRight
} from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'

const props = defineProps({
  pagination: {
    type: Object,
    required: true,
  },
  showInfo: {
    type: Boolean,
    default: true,
  },
  maxVisibleButtons: {
    type: Number,
    default: 7,
  },
})

const emit = defineEmits(['page-change'])

const { t } = useI18n()

// Calculate which page numbers to show
const visiblePages = computed(() => {
  const current = props.pagination.current_page
  const last = props.pagination.last_page
  const max = props.maxVisibleButtons
  
  if (last <= max) {
    // Show all pages if total pages is less than max
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  
  const pages = []
  const sideButtons = Math.floor((max - 3) / 2) // Reserve space for current page and ellipsis
  
  // Always show first page
  pages.push(1)
  
  // Calculate start and end of visible pages around current page
  let start = Math.max(2, current - sideButtons)
  let end = Math.min(last - 1, current + sideButtons)
  
  // Adjust if we're near the beginning or end
  if (current <= sideButtons + 2) {
    end = Math.min(last - 1, max - 2)
  } else if (current >= last - sideButtons - 1) {
    start = Math.max(2, last - max + 3)
  }
  
  // Add ellipsis before visible pages if needed
  if (start > 2) {
    pages.push('...')
  }
  
  // Add visible page numbers
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  // Add ellipsis after visible pages if needed
  if (end < last - 1) {
    pages.push('...')
  }
  
  // Always show last page
  if (last > 1) {
    pages.push(last)
  }
  
  return pages
})

function goToPage(page) {
  if (page < 1 || page > props.pagination.last_page) return
  if (page === props.pagination.current_page) return
  
  emit('page-change', page)
}
</script>