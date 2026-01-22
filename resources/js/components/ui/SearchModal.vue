<template>
  <Dialog v-model:open="isOpen">
    <DialogTrigger as-child>
      <slot name="trigger">
        <Button variant="ghost" size="sm" class="text-muted-foreground">
          <Search class="h-4 w-4 mr-2" />
          Search
        </Button>
      </slot>
    </DialogTrigger>
    <DialogScrollContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>Search Matters</DialogTitle>
        <DialogDescription>
          Search for matters by reference, title, client, or other criteria.
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-4 py-4">
        <!-- Search Input -->
        <div class="flex items-center gap-2">
          <Search class="h-4 w-4 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            placeholder="Search by reference, title, client..."
            class="flex-1"
            @keyup.enter="performSearch"
          />
        </div>
        
        <!-- Advanced Filters -->
        <div v-if="showAdvanced" class="space-y-3 pt-2 border-t">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <Label for="category">Category</Label>
              <Select v-model="filters.category">
                <SelectTrigger id="category">
                  <SelectValue placeholder="All categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All categories</SelectItem>
                  <SelectItem value="PAT">Patent</SelectItem>
                  <SelectItem value="TM">Trademark</SelectItem>
                  <SelectItem value="DES">Design</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label for="status">Status</Label>
              <Select v-model="filters.status">
                <SelectTrigger id="status">
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                  <SelectItem value="dead">Dead</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          
          <div>
            <Label for="responsible">Responsible</Label>
            <Input
              id="responsible"
              v-model="filters.responsible"
              placeholder="Filter by responsible person..."
            />
          </div>
        </div>
        
        <!-- Results -->
        <div v-if="results.length > 0" class="border rounded-lg max-h-[300px] overflow-y-auto">
          <div
            v-for="matter in results"
            :key="matter.id"
            class="p-3 border-b hover:bg-accent cursor-pointer"
            @click="selectMatter(matter)"
          >
            <div class="flex items-start justify-between">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ matter.uid }}</span>
                  <StatusBadge :status="matter.status || 'active'" type="matter" />
                </div>
                <p class="text-sm text-muted-foreground">{{ matter.title }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ matter.client?.name }} • {{ matter.category }}
                </p>
              </div>
              <ChevronRight class="h-4 w-4 text-muted-foreground mt-1" />
            </div>
          </div>
        </div>
        
        <!-- No Results -->
        <div v-else-if="hasSearched && results.length === 0" class="text-center py-8 text-muted-foreground">
          No matters found matching your search criteria.
        </div>
        
        <!-- Loading -->
        <SearchResultSkeleton v-if="loading" :count="3" />
      </div>
      
      <div class="flex items-center justify-between pt-4 border-t">
        <Button
          variant="ghost"
          size="sm"
          @click="showAdvanced = !showAdvanced"
        >
          <Filter class="h-4 w-4 mr-2" />
          {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Filters
        </Button>
        
        <div class="flex gap-2">
          <Button variant="outline" @click="isOpen = false">
            Cancel
          </Button>
          <Button @click="performSearch" :disabled="!searchQuery && !hasFilters">
            Search
          </Button>
        </div>
      </div>
    </DialogScrollContent>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Search, Filter, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import StatusBadge from '@/components/display/StatusBadge.vue'
import SearchResultSkeleton from '@/components/ui/skeleton/SearchResultSkeleton.vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

// Local state
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const searchQuery = ref('')
const showAdvanced = ref(false)
const loading = ref(false)
const hasSearched = ref(false)
const results = ref([])

const filters = ref({
  category: '',
  status: '',
  responsible: ''
})

const hasFilters = computed(() => {
  return filters.value.category || filters.value.status || filters.value.responsible
})

// Methods
const performSearch = async () => {
  if (!searchQuery.value && !hasFilters.value) return
  
  loading.value = true
  hasSearched.value = true
  
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('q', searchQuery.value)
    if (filters.value.category) params.append('category', filters.value.category)
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.responsible) params.append('responsible', filters.value.responsible)
    
    const response = await fetch(`/matter/search?${params}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      results.value = data.data || []
    }
  } catch (error) {
    console.error('Search failed:', error)
    results.value = []
  } finally {
    loading.value = false
  }
}

const selectMatter = (matter) => {
  isOpen.value = false
  router.visit(`/matter/${matter.id}`)
}

// Reset state when dialog closes
const resetSearch = () => {
  searchQuery.value = ''
  showAdvanced.value = false
  hasSearched.value = false
  results.value = []
  filters.value = {
    category: '',
    status: '',
    responsible: ''
  }
}

// Watch for dialog close
router.on('navigate', () => {
  if (isOpen.value) {
    isOpen.value = false
  }
})
</script>