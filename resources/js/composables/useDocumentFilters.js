import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

export function useDocumentFilters(initialFilters = {}) {
  const { t } = useI18n()
  
  const filters = ref({
    name: initialFilters.name || '',
    notes: initialFilters.notes || '',
    ...initialFilters
  })

  const activeFilterBadges = computed(() => {
    const badges = []
    
    if (filters.value.name && filters.value.name.trim()) {
      badges.push({
        key: 'name',
        label: t('document.fields.name'),
        value: filters.value.name
      })
    }
    
    if (filters.value.notes && filters.value.notes.trim()) {
      badges.push({
        key: 'notes',
        label: t('document.fields.notes'),
        value: filters.value.notes
      })
    }
    
    return badges
  })

  const hasActiveFilters = computed(() => {
    return activeFilterBadges.value.length > 0
  })

  function clearFilters() {
    filters.value = {
      name: '',
      notes: ''
    }
  }

  function removeFilter(key) {
    if (key === 'name') {
      filters.value.name = ''
    } else if (key === 'notes') {
      filters.value.notes = ''
    }
  }

  function updateFiltersFromServer(serverFilters) {
    filters.value = {
      name: serverFilters.name || '',
      notes: serverFilters.notes || '',
      ...serverFilters
    }
  }

  function getApiParams() {
    const params = {}
    
    if (filters.value.name && filters.value.name.trim()) {
      params.name = filters.value.name.trim()
    }
    
    if (filters.value.notes && filters.value.notes.trim()) {
      params.notes = filters.value.notes.trim()
    }
    
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