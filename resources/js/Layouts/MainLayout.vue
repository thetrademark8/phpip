<template>
  <div class="min-h-screen bg-background flex flex-col items-stretch justify-start">
    <Navigation 
      @openCreateMatter="handleOpenCreateMatter"
      @openCreateFromOPS="handleOpenCreateFromOPS"
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
      @success="handleMatterCreated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Navigation from '@/Components/Navigation.vue'
import Footer from '@/Components/Footer.vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/Components/ui/sonner'
import 'vue-sonner/style.css' // vue-sonner v2 requires this import
import MatterDialog from '@/Components/dialogs/MatterDialog.vue'

// Matter Dialog state
const matterDialogOpen = ref(false)
const matterOperation = ref('new')
const selectedCategory = ref(null)

// Handle opening the create matter dialog
const handleOpenCreateMatter = () => {
  matterOperation.value = 'new'
  selectedCategory.value = null
  matterDialogOpen.value = true
}

// Handle opening the OPS create dialog
const handleOpenCreateFromOPS = () => {
  matterOperation.value = 'ops'
  selectedCategory.value = null
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
const handleOpenCreateMatterWithCategory = (event) => {
  matterOperation.value = 'new'
  selectedCategory.value = event.detail.category
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