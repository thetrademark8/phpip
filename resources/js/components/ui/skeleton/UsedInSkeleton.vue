<template>
  <div class="space-y-6">
    <!-- Matter Dependencies Section -->
    <div>
      <Skeleton class="h-6 w-48 mb-3" />
      <div class="space-y-2">
        <div v-for="i in 2" :key="`matter-${i}`" class="border rounded-lg p-3">
          <Skeleton class="h-5 w-24 mb-2" />
          <div class="flex flex-wrap gap-2">
            <Skeleton v-for="j in 4" :key="`badge-${i}-${j}`" class="h-6 w-20" />
          </div>
        </div>
      </div>
    </div>

    <!-- Inter-Actor Dependencies Section -->
    <div>
      <Skeleton class="h-6 w-64 mb-3" />
      <div class="space-y-2">
        <div v-for="i in 2" :key="`actor-${i}`" class="border rounded-lg p-3">
          <Skeleton class="h-5 w-32 mb-2" />
          <div class="flex flex-wrap gap-2">
            <Skeleton v-for="j in 3" :key="`link-${i}-${j}`" class="h-4 w-28" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import Skeleton from './Skeleton.vue'
</script>