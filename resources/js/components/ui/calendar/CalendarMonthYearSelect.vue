<script setup>
import { computed } from 'vue'
import { CalendarDate } from '@internationalized/date'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps({
  placeholder: { type: Object, required: true },
  locale: { type: String, default: 'en' },
  minYear: { type: Number, default: () => new Date().getFullYear() - 100 },
  maxYear: { type: Number, default: () => new Date().getFullYear() + 10 },
})

const emit = defineEmits(['update:placeholder'])

// Current month/year from placeholder as strings for Select
const currentMonth = computed(() => String(props.placeholder?.month ?? 1))
const currentYear = computed(() => String(props.placeholder?.year ?? new Date().getFullYear()))

// Month names based on locale
const monthNames = computed(() => {
  const formatter = new Intl.DateTimeFormat(props.locale || 'en', { month: 'long' })
  return Array.from({ length: 12 }, (_, i) => ({
    value: String(i + 1),
    label: formatter.format(new Date(2024, i, 1)),
  }))
})

// Year range (descending)
const years = computed(() => {
  const result = []
  for (let y = props.maxYear; y >= props.minYear; y--) {
    result.push(String(y))
  }
  return result
})

function onMonthChange(month) {
  emit('update:placeholder', new CalendarDate(
    props.placeholder.year,
    parseInt(month, 10),
    1
  ))
}

function onYearChange(year) {
  emit('update:placeholder', new CalendarDate(
    parseInt(year, 10),
    props.placeholder.month,
    1
  ))
}
</script>

<template>
  <div class="flex items-center gap-1">
    <Select :model-value="currentMonth" @update:model-value="onMonthChange">
      <SelectTrigger class="h-7 w-auto gap-1 border-none p-2 font-medium hover:bg-accent focus:ring-0">
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="m in monthNames" :key="m.value" :value="m.value">
          {{ m.label }}
        </SelectItem>
      </SelectContent>
    </Select>

    <Select :model-value="currentYear" @update:model-value="onYearChange">
      <SelectTrigger class="h-7 w-auto gap-1 border-none p-2 font-medium hover:bg-accent focus:ring-0">
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="y in years" :key="y" :value="y">
          {{ y }}
        </SelectItem>
      </SelectContent>
    </Select>
  </div>
</template>
