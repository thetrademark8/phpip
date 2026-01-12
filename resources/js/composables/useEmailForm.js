import { reactive, computed, ref, watch } from 'vue'

/**
 * Composable for email form state management
 * Centralizes form state, validation, and dirty tracking
 */
export function useEmailForm(initialValues = {}) {
  const defaultValues = {
    recipient_ids: [],
    subject: '',
    body: '',
    cc: [],
    bcc: [],
    attachment_ids: [],
    template_id: null,
  }

  const form = reactive({
    ...defaultValues,
    ...initialValues,
  })

  // Store initial state for dirty tracking
  const initialState = ref(JSON.stringify(form))

  /**
   * Check if form has unsaved changes
   */
  const isDirty = computed(() => {
    return JSON.stringify(form) !== initialState.value
  })

  /**
   * Check if form is valid for sending
   */
  const canSend = computed(() => {
    const hasRecipients = form.recipient_ids.length > 0
    const hasSubject = form.subject?.trim().length > 0
    // Strip HTML tags to check for actual content
    const hasBody = form.body?.replace(/<[^>]*>/g, '').trim().length > 0

    return hasRecipients && hasSubject && hasBody
  })

  /**
   * Reset form to initial state
   */
  const reset = () => {
    Object.assign(form, JSON.parse(initialState.value))
  }

  /**
   * Reset form to default empty state
   */
  const clear = () => {
    Object.assign(form, defaultValues)
    initialState.value = JSON.stringify(form)
  }

  /**
   * Load template content into form
   * @param {Object|null} template - Template object with subject and body
   */
  const loadTemplate = (template) => {
    if (!template) {
      form.subject = ''
      form.body = ''
      form.template_id = null
      return
    }

    form.subject = template.subject || ''
    form.body = template.body || ''
    form.template_id = template.id
  }

  /**
   * Mark current state as "saved" (resets dirty tracking)
   */
  const markAsSaved = () => {
    initialState.value = JSON.stringify(form)
  }

  /**
   * Reset recipients and attachments after successful send
   */
  const resetAfterSend = () => {
    form.recipient_ids = []
    form.cc = []
    form.bcc = []
    form.attachment_ids = []
    markAsSaved()
  }

  return {
    form,
    isDirty,
    canSend,
    reset,
    clear,
    loadTemplate,
    markAsSaved,
    resetAfterSend,
  }
}
