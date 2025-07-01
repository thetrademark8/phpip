import { createI18n } from 'vue-i18n'

// Function to create i18n instance with initial data
export function createI18nInstance(initialPage) {
  // Get locale and translations from initial page props or use defaults
  const locale = initialPage?.props?.locale || 'en'
  const translations = initialPage?.props?.translations || {}
  
  // Create messages object with locale as key
  const messages = {
    [locale]: translations
  }
  
  // Create i18n instance
  const i18n = createI18n({
    legacy: false, // Use Composition API
    locale: locale,
    fallbackLocale: 'en',
    messages: messages,
    globalInjection: true, // Inject $t in all components
    missingWarn: false, // Disable warnings for missing translations in production
    fallbackWarn: false,
  })
  
  return i18n
}

// Function to update locale and translations on an i18n instance
export function updateI18nLocale(i18n, newLocale, newTranslations) {
  if (!i18n || !newLocale) return
  
  i18n.global.locale.value = newLocale
  i18n.global.setLocaleMessage(newLocale, newTranslations)
}