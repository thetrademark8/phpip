import { useI18n } from 'vue-i18n'
import { router } from '@inertiajs/vue3'

export function useTranslations() {
  const { t, locale } = useI18n()
  
  // Change locale and reload page to get new translations
  const changeLocale = (newLocale) => {
    // Send request to update user's language preference
    router.put('/profile/locale', { locale: newLocale }, {
      preserveState: false,
      onSuccess: () => {
        // Page will reload with new translations
      }
    })
  }
  
  return {
    t,
    locale,
    changeLocale
  }
}