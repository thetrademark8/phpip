export class ActorFilterService {
  /**
   * Clean filter values by removing empty strings and null values
   * @param {Object} filters 
   * @returns {Object}
   */
  static cleanFilters(filters) {
    const cleaned = {}
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) {
        if (typeof value === 'boolean' && value === true) {
          cleaned[key] = 1
        } else if (typeof value !== 'boolean') {
          cleaned[key] = value
        }
      }
    })
    
    return cleaned
  }

  /**
   * Convert filter object to URL query parameters
   * @param {Object} filters 
   * @returns {URLSearchParams}
   */
  static toQueryParams(filters) {
    const cleaned = this.cleanFilters(filters)
    return new URLSearchParams(cleaned)
  }

  /**
   * Build filter query for the backend
   * @param {Object} filters 
   * @returns {Object}
   */
  static buildQuery(filters) {
    const query = {}
    
    // Text search filters
    const textFields = ['Name', 'first_name', 'display_name', 'company', 'email', 'phone', 'country', 'default_role']
    textFields.forEach(field => {
      if (filters[field] && filters[field].trim() !== '') {
        query[field] = filters[field].trim()
      }
    })

    // Selector filter (phy_p, leg_p, warn)
    if (filters.selector && filters.selector !== 'all') {
      query.selector = filters.selector
    }

    // Boolean filters
    if (filters.phy_person === true) {
      query.phy_person = 1
    }
    
    if (filters.warn === true) {
      query.warn = 1
    }
    
    if (filters.has_login === true) {
      query.has_login = 1
    }

    return query
  }

  /**
   * Get default filter values
   * @returns {Object}
   */
  static getDefaults() {
    return {
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

  /**
   * Validate filter values
   * @param {Object} filters 
   * @returns {Object} { isValid: boolean, errors: string[] }
   */
  static validate(filters) {
    const errors = []
    
    // Email validation
    if (filters.email && filters.email.trim() !== '') {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(filters.email)) {
        errors.push('Invalid email format')
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  /**
   * Merge filters with defaults
   * @param {Object} filters 
   * @returns {Object}
   */
  static mergeWithDefaults(filters) {
    return {
      ...this.getDefaults(),
      ...filters
    }
  }
}