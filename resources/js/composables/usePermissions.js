import { computed, readonly } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Simple permissions composable
 * 
 * Reads user permissions from page.props.auth.user
 * Authorization logic is handled by Laravel policies
 */
export function usePermissions() {
  const page = usePage()
  
  /**
   * Get current user from page props
   */
  const user = computed(() => page.props.auth?.user || null)
  
  /**
   * Get current user role code
   */
  const role = computed(() => user.value?.role || 'CLI')
  
  /**
   * Check if user can read data
   */
  const canRead = computed(() => user.value?.can_readonly || false)
  
  /**
   * Check if user can write data
   */
  const canWrite = computed(() => user.value?.can_readwrite || false)
  
  /**
   * Check if user is admin (DBA role)
   */
  const isAdmin = computed(() => role.value === 'DBA')
  
  /**
   * Check if user is client (CLI role)
   */
  const isClient = computed(() => role.value === 'CLI')
  
  /**
   * Check if user has specific role
   * 
   * @param {string} roleCode - Role code to check (DBA, DBRW, DBRO, CLI)
   * @returns {boolean} True if user has the specified role
   */
  const hasRole = (roleCode) => {
    return role.value === roleCode
  }

  return {
    // User state
    user: readonly(user),
    role: readonly(role),
    
    // Permission checks
    canRead: readonly(canRead),
    canWrite: readonly(canWrite),
    
    // Role checks
    isAdmin: readonly(isAdmin),
    isClient: readonly(isClient),
    hasRole
  }
}