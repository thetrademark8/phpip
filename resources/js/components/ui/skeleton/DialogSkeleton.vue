<template>
  <div class="space-y-6">
    <!-- Header skeleton -->
    <div class="space-y-2">
      <Skeleton class="h-6 w-48" />
      <Skeleton class="h-4 w-64" />
    </div>

    <!-- Form fields skeleton -->
    <div class="space-y-4">
      <div v-for="i in fields" :key="i" class="space-y-2">
        <Skeleton class="h-4 w-24" />
        <Skeleton class="h-10 w-full" />
      </div>
    </div>

    <!-- Footer skeleton -->
    <div class="flex justify-end gap-2 pt-4">
      <Skeleton class="h-10 w-20" />
      <Skeleton class="h-10 w-20" />
    </div>
  </div>
</template>

<script setup>
import { Skeleton } from '@/components/ui/skeleton'

defineProps({
  fields: {
    type: Number,
    default: 4
  }
})
</script>