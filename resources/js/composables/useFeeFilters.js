import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

export function useFeeFilters(initialFilters = {}) {
  const { t } = useI18n()
  
  // Initialize filters with defaults
  const filters = ref({
    Country: '',
    Category: '',
    Origin: '',
    Qt: '',
    ...initialFilters
  })

  // Computed property for active filter badges
  const activeFilterBadges = computed(() => {
    const badges = []
    
    // Text filters
    const textFilters = {
      Country: t('Country'),
      Category: t('Category'),
      Origin: t('Origin'),
      Qt: t('Yr')
    }

    Object.entries(textFilters).forEach(([key, label]) => {
      if (filters.value[key] && filters.value[key] !== '') {
        badges.push({
          key,
          label,
          value: filters.value[key]
        })
      }
    })

    return badges
  })

  // Check if any filters are active
  const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => {
      return value !== '' && value !== null && value !== undefined
    })
  })

  // Clear all filters
  function clearFilters() {
    filters.value = {
      Country: '',
      Category: '',
      Origin: '',
      Qt: ''
    }
  }

  // Remove specific filter
  function removeFilter(key) {
    filters.value[key] = ''
  }

  // Update filters from server response
  function updateFiltersFromServer(serverFilters) {
    Object.assign(filters.value, serverFilters)
  }

  // Get API parameters for the request
  function getApiParams() {
    const params = {}
    
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) {
        params[key] = value
      }
    })
    
    return params
  }

  return {
    filters,
    activeFilterBadges,
    hasActiveFilters,
    clearFilters,
    removeFilter,
    updateFiltersFromServer,
    getApiParams
  }
}