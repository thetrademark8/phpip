<template>
  <MainLayout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="rounded-lg border bg-card p-6 shadow-sm">
        <h1 class="text-3xl font-bold">Inertia + Vue + shadcn-vue Test Page</h1>
        <p class="mt-4 text-muted-foreground">
          If you can see this page, the setup is working correctly!
        </p>
        <div class="mt-6 space-y-2">
          <p>User: {{ $page.props.auth.user?.name || 'Not logged in' }}</p>
          <p>App Name: {{ $page.props.app.name }}</p>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
</script>