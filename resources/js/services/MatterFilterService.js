import { router } from '@inertiajs/vue3'
import { FILTER_LABEL_MAP } from '@/constants/matter'

/**
 * Service for handling matter filtering operations
 */
export class MatterFilterService {
  /**
   * Apply filters and navigate to filtered results
   */
  static applyFilters(filters, sortField = null, sortDirection = null) {
    const options = {}
    if (sortField) {
      options.sort = sortField
      options.direction = sortDirection || 'asc'
    }
    const params = this.buildQueryParams(filters, options)
    
    return new Promise((resolve, reject) => {
      router.get('/matter', params, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => resolve(),
        onError: (errors) => reject(errors)
      })
    })
  }

  /**
   * Export matters with current filters
   */
  static exportMatters(filters) {
    const params = new URLSearchParams(this.buildQueryParams(filters))
    window.location.href = `/matter/export?${params.toString()}`
  }

  /**
   * Build query parameters from filters
   */
  static buildQueryParams(filters, options = {}) {
    const params = {}

    // Add filter parameters
    Object.entries(filters).forEach(([key, value]) => {
      if (this.shouldIncludeParam(key, value)) {
        // Handle date range objects specially
        if (typeof value === 'object' && value !== null && !Array.isArray(value) && (value.from || value.to)) {
          // Send as nested object for Laravel validation
          if (value.from) params[`${key}[from]`] = value.from
          if (value.to) params[`${key}[to]`] = value.to
        } else {
          params[key] = this.formatParamValue(key, value)
        }
      }
    })

    // Add options (sortkey, sortdir, etc.)
    Object.entries(options).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        params[key] = value
      }
    })

    return params
  }

  /**
   * Check if parameter should be included
   */
  static shouldIncludeParam(key, value) {
    // Skip empty values
    if (value === '' || value === null || value === undefined) {
      return false
    }

    // For date range objects, check if at least one date is set
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      return !!(value.from || value.to)
    }

    // For boolean fields, only include if true
    if (['Ctnr', 'include_dead'].includes(key)) {
      return value === true || value === 1 || value === '1'
    }

    // Skip false values for other fields
    if (value === false) {
      return false
    }

    return true
  }

  /**
   * Format parameter value for API
   */
  static formatParamValue(key, value) {
    // Convert booleans to 1/0 for backend compatibility
    if (typeof value === 'boolean') {
      return value ? 1 : 0
    }
    
    return value
  }

  /**
   * Clear all filters except persistent ones
   */
  static clearFilters(currentFilters) {
    const persistentKeys = ['tab', 'display_with', 'sortkey', 'sortdir']
    const clearedFilters = {}
    
    // Keep only persistent values
    persistentKeys.forEach(key => {
      if (currentFilters[key] !== undefined) {
        clearedFilters[key] = currentFilters[key]
      }
    })
    
    return this.applyFilters(clearedFilters)
  }

  /**
   * Remove a specific filter
   */
  static removeFilter(filters, keyToRemove) {
    const newFilters = { ...filters }
    
    if (['Ctnr', 'include_dead'].includes(keyToRemove)) {
      newFilters[keyToRemove] = false
    } else {
      delete newFilters[keyToRemove]
    }
    
    return this.applyFilters(newFilters)
  }

  /**
   * Get active filter badges for display
   */
  static getActiveFilterBadges(filters) {
    const badges = []
    const skipKeys = ['sortkey', 'sortdir', 'tab', 'display_with']
    
    Object.entries(filters).forEach(([key, value]) => {
      if (skipKeys.includes(key)) return
      if (!this.shouldIncludeParam(key, value)) return
      
      badges.push({
        key,
        label: this.getFilterLabel(key),
        value: this.getFilterDisplayValue(key, value)
      })
    })
    
    return badges
  }

  /**
   * Get human-readable label for filter key
   */
  static getFilterLabel(key) {
    // For now, use the static map. The component using this service
    // should handle translations when displaying the labels
    return FILTER_LABEL_MAP[key] || key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')
  }

  /**
   * Get display value for filter
   */
  static getFilterDisplayValue(key, value) {
    // For boolean fields, only show "Yes" if actually true
    if (['Ctnr', 'include_dead'].includes(key)) {
      return (value === true || value === 1 || value === '1') ? 'Yes' : 'No'
    }

    // For date range objects, display as "from → to"
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      const from = value.from ? this.formatDateDisplay(value.from) : '...'
      const to = value.to ? this.formatDateDisplay(value.to) : '...'
      return `${from} → ${to}`
    }

    // For other boolean-like values
    if (typeof value === 'boolean') {
      return value ? 'Yes' : 'No'
    }

    return String(value)
  }

  /**
   * Format date for display (YYYY-MM-DD to DD/MM/YYYY)
   */
  static formatDateDisplay(dateString) {
    if (!dateString) return ''
    const [year, month, day] = dateString.split('-')
    return `${day}/${month}/${year}`
  }

  /**
   * Check if there are active filters
   */
  static hasActiveFilters(filters) {
    const skipKeys = ['sortkey', 'sortdir', 'tab', 'display_with']
    
    return Object.entries(filters).some(([key, value]) => {
      if (skipKeys.includes(key)) return false
      return this.shouldIncludeParam(key, value)
    })
  }
}