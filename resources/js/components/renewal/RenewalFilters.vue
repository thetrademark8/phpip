<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- Client Name Filter -->
    <div class="space-y-2">
      <Label for="filter-name" class="mb-2">{{ $t('Client') }}</Label>
      <Input
        id="filter-name"
        :model-value="filters.Name"
        @update:model-value="debouncedUpdate('Name', $event)"
        :placeholder="$t('Client')"
        class="w-full"
      />
    </div>

    <!-- Title Filter -->
    <div class="space-y-2">
      <Label for="filter-title" class="mb-2">{{ $t('Title') }}</Label>
      <Input
        id="filter-title"
        :model-value="filters.Title"
        @update:model-value="debouncedUpdate('Title', $event)"
        :placeholder="$t('Title')"
        class="w-full"
      />
    </div>

    <!-- Matter (Case) Filter -->
    <div class="space-y-2">
      <Label for="filter-case" class="mb-2">{{ $t('Matter') }}</Label>
      <Input
        id="filter-case"
        :model-value="filters.Case"
        @update:model-value="debouncedUpdate('Case', $event)"
        :placeholder="$t('Matter')"
        class="w-full"
      />
    </div>

    <!-- Country Filter -->
    <div class="space-y-2">
      <Label for="filter-country" class="mb-2">{{ $t('Country') }}</Label>
      <Input
        id="filter-country"
        :model-value="filters.Country"
        @update:model-value="debouncedUpdate('Country', $event)"
        :placeholder="$t('Ctry')"
        class="w-full"
      />
    </div>

    <!-- Qt (Annuity) Filter -->
    <div class="space-y-2">
      <Label for="filter-qt" class="mb-2">{{ $t('Qt') }}</Label>
      <Input
        id="filter-qt"
        :model-value="filters.Qt"
        @update:model-value="debouncedUpdate('Qt', $event)"
        :placeholder="$t('Qt')"
        class="w-full"
      />
    </div>

    <!-- Grace Period Filter -->
    <div class="space-y-2 flex items-end">
      <div class="flex items-center space-x-2">
        <Checkbox
          id="filter-grace"
          :checked="filters.grace_period"
          @update:checked="handleGracePeriodChange"
        />
        <Label for="filter-grace" class="text-sm font-normal cursor-pointer">
          {{ $t('In grace period') }}
        </Label>
      </div>
    </div>

    <!-- From Date Filter -->
    <div class="space-y-2">
      <Label for="filter-fromdate" class="mb-2">{{ $t('From date') }}</Label>
      <DatePicker
        id="filter-fromdate"
        :model-value="filters.Fromdate"
        @update:model-value="handleDateChange('Fromdate', $event)"
        :placeholder="$t('From selected date')"
      />
    </div>

    <!-- Until Date Filter -->
    <div class="space-y-2">
      <Label for="filter-untildate" class="mb-2">{{ $t('Until date') }}</Label>
      <DatePicker
        id="filter-untildate"
        :model-value="filters.Untildate"
        @update:model-value="handleDateChange('Untildate', $event)"
        :placeholder="$t('Until selected date')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { debounce } from 'lodash-es'
import { useI18n } from 'vue-i18n'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

// Create debounced update function
const debouncedUpdate = debounce((key, value) => {
  const newFilters = { ...props.filters, [key]: value }
  emit('update:filters', newFilters)
}, 300)

// Handle checkbox change
function handleGracePeriodChange(checked) {
  const newFilters = { ...props.filters, grace_period: checked }
  emit('update:filters', newFilters)
}

// Handle date changes
function handleDateChange(key, value) {
  const formattedDate = value ? new Date(value).toISOString().split('T')[0] : ''
  const newFilters = { ...props.filters, [key]: formattedDate }
  emit('update:filters', newFilters)
}
</script>