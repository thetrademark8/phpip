import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

export default function useCategoryFilters(initialFilters = {}) {
  const filters = ref({
    Code: initialFilters.Code || '',
    Category: initialFilters.Category || '',
  })

  // Debounced filter application
  const debouncedApply = debounce(() => {
    applyFilters()
  }, 500)

  // Watch for filter changes
  watch(filters, () => {
    debouncedApply()
  }, { deep: true })

  function applyFilters() {
    router.get(route('category.index'), {
      ...filters.value,
      page: 1,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }

  function resetFilters() {
    filters.value = {
      Code: '',
      Category: '',
    }
    applyFilters()
  }

  return {
    filters,
    applyFilters,
    resetFilters,
  }
}