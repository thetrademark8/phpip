<template>
  <div v-for="i in count" :key="i" class="px-4 py-3">
    <div class="flex gap-3">
      <Skeleton class="h-8 w-8 rounded-full flex-shrink-0" />
      <div class="flex-1 space-y-2">
        <Skeleton class="h-4 w-3/4" />
        <Skeleton class="h-3 w-1/2" />
      </div>
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