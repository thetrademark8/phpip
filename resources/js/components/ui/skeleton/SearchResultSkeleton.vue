<template>
  <div v-for="i in count" :key="i" class="p-3 border-b">
    <div class="space-y-2">
      <div class="flex items-center gap-2">
        <Skeleton class="h-5 w-20" />
        <Skeleton class="h-5 w-16 rounded-full" />
      </div>
      <Skeleton class="h-4 w-3/4" />
      <Skeleton class="h-3 w-1/2" />
    </div>
  </div>
</template>

<script setup>
import Skeleton from './Skeleton.vue'

defineProps({
  count: {
    type: Number,
    default: 3
  }
})
</script>