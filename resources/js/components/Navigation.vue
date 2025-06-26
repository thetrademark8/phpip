<template>
  <nav class="border-b bg-background">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-16 items-center justify-between">
        <div class="flex items-center">
          <Link :href="route('home')" class="flex items-center">
            <img 
              v-if="$page.props.app.company_logo" 
              :src="`/${$page.props.app.company_logo}`"
              :alt="$page.props.app.company_name || $page.props.app.name"
              class="h-10 w-auto"
            >
            <span v-else class="text-xl font-semibold">
              {{ $page.props.app.name }}
            </span>
          </Link>
        </div>
        
        <div class="flex items-center space-x-4">
          <div v-if="$page.props.auth.user">
            <span class="text-sm text-muted-foreground">
              {{ $page.props.auth.user.name }}
            </span>
            <Link 
              :href="route('logout')" 
              method="post" 
              as="button"
              class="ml-4 text-sm text-muted-foreground hover:text-foreground"
            >
              {{ __('Logout') }}
            </Link>
          </div>
          <div v-else>
            <Link 
              :href="route('login')" 
              class="text-sm text-muted-foreground hover:text-foreground"
            >
              {{ __('Login') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const __ = (key) => {
  // Simple translation function placeholder
  return key
}
</script>