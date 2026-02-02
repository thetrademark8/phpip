<template>
  <div class="min-h-screen bg-background flex flex-col items-stretch justify-start">
    <Navigation
      @openCreateMatter="handleOpenCreateMatter"
    />
    <main class="flex-1 p-4">
      <slot />
    </main>
    <Footer />
    <Toaster />

    <!-- Matter Create Dialog -->
    <MatterDialog
      v-model:open="matterDialogOpen"
      :operation="matterOperation"
      :category="selectedCategory"
      :current-user="$page.props.auth.user"
      :category-options="formOptions.categoryOptions"
      :type-options="formOptions.typeOptions"
      :user-options="formOptions.userOptions"
      :country-options="formOptions.countryOptions"
      @success="handleMatterCreated"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Navigation from '@/components/Navigation.vue'
import Footer from '@/components/Footer.vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css' // vue-sonner v2 requires this import
import MatterDialog from '@/components/dialogs/MatterDialog.vue'

// Matter Dialog state
const matterDialogOpen = ref(false)
const matterOperation = ref('new')
const selectedCategory = ref(null)
const formOptions = reactive({
  categoryOptions: [],
  typeOptions: [],
  userOptions: [],
  countryOptions: [],
})

// Load form options
const loadFormOptions = async () => {
  if (formOptions.categoryOptions.length > 0) return // Already loaded

  try {
    const response = await fetch('/matter/form-options', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    if (response.ok) {
      const data = await response.json()
      formOptions.categoryOptions = data.categoryOptions
      formOptions.typeOptions = data.typeOptions
      formOptions.userOptions = data.userOptions
      formOptions.countryOptions = data.countryOptions
    }
  } catch (error) {
    console.error('Failed to load form options:', error)
  }
}

// Handle opening the create matter dialog
const handleOpenCreateMatter = async () => {
  matterOperation.value = 'new'
  selectedCategory.value = null
  await loadFormOptions()
  matterDialogOpen.value = true
}

// Handle successful matter creation
const handleMatterCreated = () => {
  matterDialogOpen.value = false
  selectedCategory.value = null
  // Navigate to the newly created matter or refresh current page
  router.reload()
}

// Handle opening create matter with category
const handleOpenCreateMatterWithCategory = async (event) => {
  matterOperation.value = 'new'
  selectedCategory.value = event.detail.category
  await loadFormOptions()
  matterDialogOpen.value = true
}

// Listen for custom events from child components
onMounted(() => {
  window.addEventListener('openCreateMatterWithCategory', handleOpenCreateMatterWithCategory)
})

onUnmounted(() => {
  window.removeEventListener('openCreateMatterWithCategory', handleOpenCreateMatterWithCategory)
})

// Get page instance
const page = usePage()

// Watch for flash messages and display toasts
watch(
  () => page.props.flash,
  (flash) => {
    console.log(flash)
    if (flash?.success) {
      toast.success(flash.success)
    }
    if (flash?.error) {
      toast.error(flash.error)
    }
    if (flash?.warning) {
      toast.warning(flash.warning)
    }
    if (flash?.info) {
      toast.info(flash.info)
    }
  },
  { immediate: true, deep: true }
)
</script>