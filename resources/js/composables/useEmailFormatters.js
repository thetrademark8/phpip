/**
 * Composable for email formatting utilities
 * Eliminates duplication between EmailHistory and EmailViewDialog
 */
export function useEmailFormatters() {
  /**
   * Format a date for display in email context
   * @param {string|Date} date - The date to format
   * @returns {string} Formatted date string
   */
  const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  /**
   * Get the badge variant based on email status
   * @param {string} status - Email status (sent, failed, pending)
   * @returns {string} Badge variant name
   */
  const getStatusVariant = (status) => {
    const variants = {
      sent: 'default',
      failed: 'destructive',
      pending: 'secondary',
    }
    return variants[status] || 'outline'
  }

  /**
   * Get the text color class based on email status
   * @param {string} status - Email status
   * @returns {string} Tailwind color class
   */
  const getStatusColor = (status) => {
    const colors = {
      sent: 'text-green-600',
      failed: 'text-red-600',
      pending: 'text-yellow-600',
    }
    return colors[status] || 'text-muted-foreground'
  }

  return {
    formatDate,
    getStatusVariant,
    getStatusColor,
  }
}
