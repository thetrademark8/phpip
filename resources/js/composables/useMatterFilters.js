import { ref, computed } from 'vue'
import { FILTER_FIELDS, DEFAULT_FILTERS } from '@/constants/matter'
import { MatterFilterService } from '@/services/MatterFilterService'

export function useMatterFilters(initialFilters = {}) {
  // Initialize filters with all fields to ensure reactivity
  const filters = ref({
    ...DEFAULT_FILTERS,
    ...initialFilters,
    // Ensure boolean conversion
    Ctnr: initialFilters.Ctnr === true || initialFilters.Ctnr === 1 || initialFilters.Ctnr === '1',
    include_dead: initialFilters.include_dead === true || initialFilters.include_dead === 1 || initialFilters.include_dead === '1',
  })

  // Check if there are any active filters
  const hasActiveFilters = computed(() => {
    return MatterFilterService.hasActiveFilters(filters.value)
  })

  // Get active filter badges for display
  const activeFilterBadges = computed(() => {
    return MatterFilterService.getActiveFilterBadges(filters.value)
  })

  // Clear all filters except persistent ones
  const clearFilters = () => {
    const display_with = filters.value.display_with || ''
    const sortkey = filters.value.sortkey || 'id'
    const sortdir = filters.value.sortdir || 'desc'
    
    // Reset to default values
    Object.assign(filters.value, {
      ...DEFAULT_FILTERS,
      display_with,
      sortkey,
      sortdir
    })
    
    return filters.value
  }

  // Date filter keys
  const dateFilterKeys = ['Filed', 'Published', 'registration_date']

  // Remove a specific filter
  const removeFilter = (key) => {
    if (FILTER_FIELDS.boolean.includes(key)) {
      filters.value[key] = false
    } else if (dateFilterKeys.includes(key)) {
      filters.value[key] = { from: null, to: null }
    } else {
      filters.value[key] = ''
    }
  }

  // Update filters from server response with proper type conversion
  const updateFiltersFromServer = (serverFilters) => {
    Object.keys(serverFilters).forEach(key => {
      if (key === 'Ctnr' || key === 'include_dead') {
        // Convert boolean fields from server (0/1/'0'/'1') to boolean
        filters.value[key] = serverFilters[key] === true || 
                            serverFilters[key] === 1 || 
                            serverFilters[key] === '1'
      } else if (serverFilters[key] !== undefined) {
        filters.value[key] = serverFilters[key]
      }
    })
  }

  // Get filters formatted for API request
  const getApiParams = () => {
    return MatterFilterService.buildQueryParams(filters.value)
  }

  return {
    filters,
    hasActiveFilters,
    activeFilterBadges,
    clearFilters,
    removeFilter,
    updateFiltersFromServer,
    getApiParams
  }
}