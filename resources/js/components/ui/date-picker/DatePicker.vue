<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !modelValue && 'text-muted-foreground',
          buttonClass
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        <span>{{ displayValue }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <Calendar
        v-model="calendarValue"
        :placeholder="placeholderDate"
        :locale="locale"
        initial-focus
        @update:model-value="handleDateSelect"
      />
    </PopoverContent>
  </Popover>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { format, parse, isValid } from 'date-fns'
import { fr } from 'date-fns/locale'
import { CalendarDate } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

const props = defineProps({
  modelValue: {
    type: [String, Date],
    default: null
  },
  placeholder: {
    type: String,
    default: 'Select date'
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
  }
})

const emit = defineEmits(['update:modelValue'])

// Convert ISO string to Date object for the calendar
const selectedDate = ref(null)

// Parse incoming date
const parseIncomingDate = (value) => {
  if (!value) return null
  
  if (value instanceof Date) {
    return isValid(value) ? value : null
  }
  
  // Try to parse ISO format (YYYY-MM-DD)
  const isoDate = parse(value, 'yyyy-MM-dd', new Date())
  if (isValid(isoDate)) {
    return isoDate
  }
  
  // Try to parse display format
  const displayDate = parse(value, props.dateFormat, new Date())
  if (isValid(displayDate)) {
    return displayDate
  }
  
  return null
}

// Initialize selected date
watch(() => props.modelValue, (newValue) => {
  selectedDate.value = parseIncomingDate(newValue)
}, { immediate: true })

// Display value
const displayValue = computed(() => {
  if (!selectedDate.value || !isValid(selectedDate.value)) {
    return props.placeholder
  }
  
  const localeObj = props.locale === 'fr' ? fr : undefined
  return format(selectedDate.value, props.dateFormat, { locale: localeObj })
})

// Convert Date to CalendarDate for the Calendar component
const calendarValue = computed(() => {
  if (!selectedDate.value || !isValid(selectedDate.value)) return null
  
  return new CalendarDate(
    selectedDate.value.getFullYear(),
    selectedDate.value.getMonth() + 1, // CalendarDate uses 1-based months
    selectedDate.value.getDate()
  )
})

// Placeholder date for calendar
const placeholderDate = computed(() => {
  const date = selectedDate.value || new Date()
  return new CalendarDate(
    date.getFullYear(),
    date.getMonth() + 1, // CalendarDate uses 1-based months
    date.getDate()
  )
})

// Handle date selection
const handleDateSelect = (calendarDate) => {
  if (!calendarDate) {
    emit('update:modelValue', null)
    return
  }
  
  // CalendarDate has year, month (1-based), and day properties
  // Always emit in ISO format (YYYY-MM-DD)
  const year = calendarDate.year
  const month = String(calendarDate.month).padStart(2, '0')
  const day = String(calendarDate.day).padStart(2, '0')
  const isoDate = `${year}-${month}-${day}`
  
  emit('update:modelValue', isoDate)
  
  // Update internal selectedDate for display
  selectedDate.value = new Date(calendarDate.year, calendarDate.month - 1, calendarDate.day)
}
</script>