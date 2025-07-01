<template>
  <div class="min-h-screen bg-background flex flex-col items-stretch justify-start">
    <Navigation />
    <main class="flex-1 p-4">
      <slot />
    </main>
    <Footer />
    <Toaster />
  </div>
</template>

<script setup>
import Navigation from '@/Components/Navigation.vue'
import Footer from '@/Components/Footer.vue'
import { Toaster } from 'vue-sonner'
</script>