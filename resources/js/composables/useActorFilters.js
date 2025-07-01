import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ActorFilterService } from '@/services/ActorFilterService'

export function useActorFilters(initialFilters = {}) {
  const { t } = useI18n()
  
  // Initialize filters with defaults
  const filters = ref({
    Name: '',
    first_name: '',
    display_name: '',
    company: '',
    email: '',
    phone: '',
    country: '',
    default_role: '',
    selector: undefined,
    phy_person: false,
    warn: false,
    has_login: false,
    ...initialFilters
  })

  // Computed property for active filter badges
  const activeFilterBadges = computed(() => {
    const badges = []
    
    // Text filters
    const textFilters = {
      Name: t('actors.filters.labels.name'),
      first_name: t('actors.filters.labels.firstName'),
      display_name: t('actors.filters.labels.displayName'),
      company: t('actors.filters.labels.company'),
      email: t('actors.filters.labels.email'),
      phone: t('actors.filters.labels.phone'),
      country: t('actors.filters.labels.country'),
      default_role: t('actors.filters.labels.defaultRole')
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

    // Selector filter
    if (filters.value.selector) {
      const selectorLabels = {
        phy_p: t('actors.filters.types.physical'),
        leg_p: t('actors.filters.types.legal'),
        warn: t('actors.filters.types.warn')
      }
      
      badges.push({
        key: 'selector',
        label: t('actors.filters.labels.type'),
        value: selectorLabels[filters.value.selector] || filters.value.selector
      })
    }

    // Boolean filters
    if (filters.value.phy_person) {
      badges.push({
        key: 'phy_person',
        label: t('actors.filters.physicalPersonOnly'),
        value: t('actors.filters.enabled')
      })
    }

    if (filters.value.warn) {
      badges.push({
        key: 'warn',
        label: t('actors.filters.warnedOnly'),
        value: t('actors.filters.enabled')
      })
    }

    if (filters.value.has_login) {
      badges.push({
        key: 'has_login',
        label: t('actors.filters.usersOnly'),
        value: t('actors.filters.enabled')
      })
    }

    return badges
  })

  // Check if any filters are active
  const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => {
      if (typeof value === 'boolean') {
        return value === true
      }
      return value !== '' && value !== null && value !== undefined
    })
  })

  // Clear all filters
  function clearFilters() {
    filters.value = {
      Name: '',
      first_name: '',
      display_name: '',
      company: '',
      email: '',
      phone: '',
      country: '',
      default_role: '',
      selector: undefined,
      phy_person: false,
      warn: false,
      has_login: false
    }
  }

  // Remove specific filter
  function removeFilter(key) {
    if (typeof filters.value[key] === 'boolean') {
      filters.value[key] = false
    } else {
      filters.value[key] = ''
    }
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
        if (typeof value === 'boolean' && value === true) {
          params[key] = 1
        } else if (typeof value !== 'boolean') {
          params[key] = value
        }
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