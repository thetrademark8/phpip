import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {useI18n} from "vue-i18n";

/**
 * Extracts a translation from a translatable field based on locale
 * 
 * @param {string|object|null} field - The field that might contain translations
 * @param {string} locale - The locale to extract (defaults to current locale)
 * @returns {string} The translated value or the original string
 */
export function getTranslation(field, locale = null) {
  // If field is null or undefined, return empty string
  if (!field) {
    return ''
  }
  
  // If field is already a string, return it as-is (backward compatibility)
  if (typeof field === 'string') {
    return field
  }
  
  // If field is an object (translatable field), extract the translation
  if (typeof field === 'object' && field !== null) {
    // Use provided locale or get from page props
    const targetLocale = locale || usePage().props.locale || 'en'
    
    // Try to get the translation for the target locale
    if (field[targetLocale]) {
      return field[targetLocale]
    }
    
    // Fallback to English if target locale not found
    if (field.en) {
      return field.en
    }
    
    // Fallback to first available translation
    const firstKey = Object.keys(field)[0]
    if (firstKey) {
      return field[firstKey]
    }
  }
  
  // Return empty string as last resort
  return ''
}

/**
 * Composable for handling translatable fields
 * 
 * @returns {object} Object with translation helpers
 */
export function useTranslatedField() {
  // Get current locale from Inertia shared data
  const locale = computed(() => usePage().props.locale || 'en')
  
  const { t, locale } = useI18n()
  
  /**
   * Get translated value for a field
   */
  const translated = (field) => {
    return getTranslation(field, locale.value)
  }
  
  /**
   * Create a computed property for a translatable field
   */
  const translatedComputed = (fieldGetter) => {
    return computed(() => {
      const field = typeof fieldGetter === 'function' ? fieldGetter() : fieldGetter
      return getTranslation(field, locale.value)
    })
  }
  
  return {
    locale,
    translated,
    translatedComputed,
    getTranslation
  }
}