import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const DEFAULT_FILTERS = {
  summary: '',
  class: '',
  language: '',
  style: '',
  format: '',
  category: ''
}

export function useTemplateMemberFilters(initialFilters = {}) {
  const { t } = useI18n()
  
  const filters = ref({
    ...DEFAULT_FILTERS,
    ...initialFilters
  })

  const hasActiveFilters = computed(() => {
    return Object.entries(filters.value).some(([key, value]) => {
      return value !== '' && value !== null && value !== false && value !== 'all'
    })
  })

  const activeFilterBadges = computed(() => {
    const badges = []
    
    if (filters.value.summary) {
      badges.push({
        key: 'summary',
        label: t('templateMember.fields.summary'),
        value: filters.value.summary
      })
    }
    
    if (filters.value.class) {
      badges.push({
        key: 'class',
        label: t('templateMember.fields.class'),
        value: filters.value.class
      })
    }
    
    if (filters.value.language && filters.value.language !== 'all') {
      badges.push({
        key: 'language',
        label: t('templateMember.fields.language'),
        value: filters.value.language
      })
    }
    
    if (filters.value.style) {
      badges.push({
        key: 'style',
        label: t('templateMember.fields.style'),
        value: filters.value.style
      })
    }
    
    if (filters.value.format && filters.value.format !== 'all') {
      badges.push({
        key: 'format',
        label: t('templateMember.fields.format'),
        value: filters.value.format
      })
    }
    
    if (filters.value.category) {
      badges.push({
        key: 'category',
        label: t('templateMember.fields.category'),
        value: filters.value.category
      })
    }
    
    return badges
  })

  const clearFilters = () => {
    Object.assign(filters.value, DEFAULT_FILTERS)
  }

  const removeFilter = (key) => {
    // Reset to 'all' for select fields, empty string for text fields
    if (key === 'language' || key === 'format') {
      filters.value[key] = 'all'
    } else {
      filters.value[key] = ''
    }
  }

  const updateFiltersFromServer = (serverFilters) => {
    if (serverFilters) {
      Object.assign(filters.value, {
        ...DEFAULT_FILTERS,
        ...serverFilters
      })
    }
  }

  const getApiParams = () => {
    const params = {}
    
    Object.entries(filters.value).forEach(([key, value]) => {
      // Ignore 'all' value as it means no filter applied
      if (value !== '' && value !== null && value !== false && value !== 'all') {
        params[key] = value
      }
    })
    
    return params
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