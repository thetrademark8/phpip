<template>
  <div class="min-h-screen flex flex-col justify-center items-center px-4 py-12 sm:px-6 lg:px-8 bg-gray-50">
    <div class="w-full max-w-md space-y-8">
      <!-- Company Logo -->
      <div v-if="$page.props.app.company_logo" class="text-center">
        <img 
          :src="`/${$page.props.app.company_logo}`" 
          :alt="$page.props.app.company_name || $page.props.app.name"
          class="mx-auto h-20 w-auto"
        >
      </div>
      
      <!-- App Name (if no logo) -->
      <div v-else class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">
          {{ $page.props.app.name }}
        </h1>
      </div>

      <!-- Main Content Slot -->
      <div class="mt-8">
        <slot />
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash" class="space-y-2">
        <div 
          v-if="$page.props.flash.success" 
          class="rounded-md bg-green-50 p-4"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800">
                {{ $page.props.flash.success }}
              </p>
            </div>
          </div>
        </div>

        <div 
          v-if="$page.props.flash.error" 
          class="rounded-md bg-red-50 p-4"
        >
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-red-800">
                {{ $page.props.flash.error }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Guest layout for authentication pages
// No additional logic needed for this simple layout
</script>