<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !hasValue && 'text-muted-foreground',
          buttonClass
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        <span>{{ displayValue }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <RangeCalendar
        v-model="calendarValue"
        :placeholder="placeholderDate"
        :locale="locale"
        :number-of-months="numberOfMonths"
        initial-focus
        @update:model-value="handleRangeSelect"
      />
    </PopoverContent>
  </Popover>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { format, parse, isValid } from 'date-fns'
import { fr, de, enUS } from 'date-fns/locale'
import { CalendarDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { RangeCalendar } from '@/components/ui/range-calendar'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ from: null, to: null })
  },
  placeholder: {
    type: String,
    default: 'Select date range'
  },
  dateFormat: {
    type: String,
    default: 'dd/MM/yyyy'
  },
  locale: {
    type: String,
    default: 'fr'
  },
  buttonClass: {
    type: String,
    default: ''
  },
  numberOfMonths: {
    type: Number,
    default: 2
  }
})

const emit = defineEmits(['update:modelValue'])

// Internal state for the calendar
const selectedRange = ref({ start: null, end: null })

// Get locale object for date-fns
const getLocaleObj = () => {
  switch (props.locale) {
    case 'fr': return fr
    case 'de': return de
    default: return enUS
  }
}

// Parse ISO date string to Date object
const parseIsoDate = (value) => {
  if (!value) return null

  if (value instanceof Date) {
    return isValid(value) ? value : null
  }

  const isoDate = parse(value, 'yyyy-MM-dd', new Date())
  if (isValid(isoDate)) {
    return isoDate
  }

  return null
}

// Convert Date to CalendarDate for reka-ui
const dateToCalendarDate = (date) => {
  if (!date || !isValid(date)) return null
  return new CalendarDate(
    date.getFullYear(),
    date.getMonth() + 1,
    date.getDate()
  )
}

// Convert CalendarDate to ISO string
const calendarDateToIso = (calendarDate) => {
  if (!calendarDate) return null
  const year = calendarDate.year
  const month = String(calendarDate.month).padStart(2, '0')
  const day = String(calendarDate.day).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Initialize from props
watch(() => props.modelValue, (newValue) => {
  const fromDate = parseIsoDate(newValue?.from)
  const toDate = parseIsoDate(newValue?.to)

  selectedRange.value = {
    start: fromDate,
    end: toDate
  }
}, { immediate: true, deep: true })

// Check if there's a complete range selected
const hasValue = computed(() => {
  return selectedRange.value.start && selectedRange.value.end
})

// Display value for the button
const displayValue = computed(() => {
  // Only show range if both dates are selected
  if (!selectedRange.value.start || !selectedRange.value.end) {
    return props.placeholder
  }

  const localeObj = getLocaleObj()
  const startStr = format(selectedRange.value.start, props.dateFormat, { locale: localeObj })
  const endStr = format(selectedRange.value.end, props.dateFormat, { locale: localeObj })

  return `${startStr} → ${endStr}`
})

// Calendar value (for RangeCalendar component)
const calendarValue = computed({
  get() {
    const start = dateToCalendarDate(selectedRange.value.start)
    const end = dateToCalendarDate(selectedRange.value.end)

    if (!start && !end) return undefined

    return { start, end }
  },
  set(val) {
    // This is handled by handleRangeSelect
  }
})

// Placeholder date for calendar initial display
const placeholderDate = computed(() => {
  const date = selectedRange.value.start || new Date()
  return new CalendarDate(
    date.getFullYear(),
    date.getMonth() + 1,
    date.getDate()
  )
})

// Handle range selection from calendar
const handleRangeSelect = (range) => {
  if (!range) {
    selectedRange.value = { start: null, end: null }
    emit('update:modelValue', { from: null, to: null })
    return
  }

  // Update internal state
  if (range.start) {
    selectedRange.value.start = new Date(range.start.year, range.start.month - 1, range.start.day)
  }
  if (range.end) {
    selectedRange.value.end = new Date(range.end.year, range.end.month - 1, range.end.day)
  }

  // Emit ISO format
  emit('update:modelValue', {
    from: calendarDateToIso(range.start),
    to: calendarDateToIso(range.end)
  })
}
</script>
