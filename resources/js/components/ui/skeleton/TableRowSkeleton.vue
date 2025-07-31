<template>
  <TableRow v-for="i in rows" :key="i" class="h-[60px]">
    <TableCell v-if="showCheckbox" class="h-[60px]">
      <div class="flex items-center justify-center">
        <Skeleton class="h-5 w-5" />
      </div>
    </TableCell>
    <TableCell v-for="(_, j) in columns" :key="j" class="h-[60px]" :class="columnWidths[j]">
      <Skeleton class="h-5 w-full" />
    </TableCell>
  </TableRow>
</template>

<script setup>
import { TableCell, TableRow } from '@/Components/ui/table'
import Skeleton from './Skeleton.vue'

defineProps({
  rows: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 4
  },
  showCheckbox: {
    type: Boolean,
    default: false
  },
  columnWidths: {
    type: Array,
    default: () => []
  }
})
</script>