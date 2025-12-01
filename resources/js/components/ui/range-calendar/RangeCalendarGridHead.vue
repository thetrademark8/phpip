<script setup>
import { RangeCalendarGridHead } from "reka-ui";

const props = defineProps({
  asChild: { type: Boolean, required: false },
  as: { type: [String, Object, Function], required: false },
  class: { type: null, required: false },
});
</script>

<template>
  <RangeCalendarGridHead data-slot="range-calendar-grid-head" v-bind="props">
    <slot />
  </RangeCalendarGridHead>
</template>
