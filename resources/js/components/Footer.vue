<template>
  <footer class="border-t bg-background">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <p class="text-center text-sm text-muted-foreground">
        © {{ new Date().getFullYear() }} {{ $page.props.app.company_name || $page.props.app.name }}. 
        All rights reserved.
      </p>
    </div>
  </footer>
</template>

<script setup>
// Footer component
</script>