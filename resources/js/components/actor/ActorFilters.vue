<template>
  <div class="space-y-4">
    <!-- Boolean Toggle Switches -->
    <div class="flex flex-wrap gap-4">
      <div class="flex items-center space-x-2">
        <Switch
          id="physical-only"
          :checked="props.filters.phy_person === true || props.filters.phy_person === 1"
          @update:checked="(value) => updateFilter('phy_person', value)"
        />
        <Label htmlFor="physical-only" class="cursor-pointer">
          {{ t('actors.filters.physicalPersonOnly') }}
        </Label>
      </div>

      <div class="flex items-center space-x-2">
        <Switch
          id="warned-only"
          :checked="props.filters.warn === true || props.filters.warn === 1"
          @update:checked="(value) => updateFilter('warn', value)"
        />
        <Label htmlFor="warned-only" class="cursor-pointer">
          {{ t('actors.filters.warnedOnly') }}
        </Label>
      </div>

      <div class="flex items-center space-x-2">
        <Switch
          id="users-only"
          :checked="props.filters.has_login === true || props.filters.has_login === 1"
          @update:checked="(value) => updateFilter('has_login', value)"
        />
        <Label htmlFor="users-only" class="cursor-pointer">
          {{ t('actors.filters.usersOnly') }}
        </Label>
      </div>
    </div>

    <!-- Search Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <!-- Name -->
      <div class="space-y-2">
        <Label htmlFor="name-filter">{{ t('actors.filters.labels.name') }}</Label>
        <Input
          id="name-filter"
          type="text"
          :placeholder="t('actors.filters.placeholders.name')"
          :model-value="props.filters.Name"
          @update:model-value="debouncedUpdate('Name', $event)"
        />
      </div>

      <!-- First Name -->
      <div class="space-y-2">
        <Label htmlFor="first-name-filter">{{ t('actors.filters.labels.firstName') }}</Label>
        <Input
          id="first-name-filter"
          type="text"
          :placeholder="t('actors.filters.placeholders.firstName')"
          :model-value="props.filters.first_name"
          @update:model-value="debouncedUpdate('first_name', $event)"
        />
      </div>

      <!-- Display Name -->
      <div class="space-y-2">
        <Label htmlFor="display-name-filter">{{ t('actors.filters.labels.displayName') }}</Label>
        <Input
          id="display-name-filter"
          type="text"
          :placeholder="t('actors.filters.placeholders.displayName')"
          :model-value="props.filters.display_name"
          @update:model-value="debouncedUpdate('display_name', $event)"
        />
      </div>

      <!-- Company/Employer -->
      <div class="space-y-2">
        <Label htmlFor="company-filter">{{ t('actors.filters.labels.company') }}</Label>
        <AutocompleteInput
          id="company-filter"
          :placeholder="t('actors.filters.placeholders.company')"
          :model-value="props.filters.company"
          @update:model-value="debouncedUpdate('company', $event)"
          endpoint="/actor/autocomplete"
          value-key="name"
          label-key="name"
        />
      </div>

      <!-- Email -->
      <div class="space-y-2">
        <Label htmlFor="email-filter">{{ t('actors.filters.labels.email') }}</Label>
        <Input
          id="email-filter"
          type="email"
          :placeholder="t('actors.filters.placeholders.email')"
          :model-value="props.filters.email"
          @update:model-value="debouncedUpdate('email', $event)"
        />
      </div>

      <!-- Phone -->
      <div class="space-y-2">
        <Label htmlFor="phone-filter">{{ t('actors.filters.labels.phone') }}</Label>
        <Input
          id="phone-filter"
          type="text"
          :placeholder="t('actors.filters.placeholders.phone')"
          :model-value="props.filters.phone"
          @update:model-value="debouncedUpdate('phone', $event)"
        />
      </div>

      <!-- Country -->
      <div class="space-y-2">
        <Label htmlFor="country-filter">{{ t('actors.filters.labels.country') }}</Label>
        <AutocompleteInput
          id="country-filter"
          :placeholder="t('actors.filters.placeholders.country')"
          :model-value="props.filters.country"
          @update:model-value="debouncedUpdate('country', $event)"
          endpoint="/country/autocomplete"
          value-key="iso"
          label-key="name"
        />
      </div>

      <!-- Default Role -->
      <div class="space-y-2">
        <Label htmlFor="role-filter">{{ t('actors.filters.labels.defaultRole') }}</Label>
        <AutocompleteInput
          id="role-filter"
          :placeholder="t('actors.filters.placeholders.defaultRole')"
          :model-value="props.filters.role"
          @update:model-value="debouncedUpdate('default_role', $event)"
          endpoint="/role/autocomplete"
          value-key="name"
          label-key="name"
        />
      </div>

      <!-- Type Selector -->
      <div class="space-y-2">
        <Label htmlFor="type-filter">{{ t('actors.filters.labels.type') }}</Label>
        <Select
          id="type-filter"
          :model-value="props.filters.selector || undefined"
          @update:model-value="updateFilter('selector', $event === 'all' ? undefined : $event)"
        >
          <SelectTrigger>
            <SelectValue :placeholder="t('actors.filters.placeholders.type')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{{ t('actors.filters.types.all') }}</SelectItem>
            <SelectItem value="phy_p">{{ t('actors.filters.types.physical') }}</SelectItem>
            <SelectItem value="leg_p">{{ t('actors.filters.types.legal') }}</SelectItem>
            <SelectItem value="warn">{{ t('actors.filters.types.warn') }}</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { debounce } from 'lodash-es'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

// Create a debounced update function
const debouncedUpdate = debounce((key, value) => {
  updateFilter(key, value)
}, 300)

function updateFilter(key, value) {
  // Clean up empty values
  const cleanValue = value === '' || value === null || value === undefined ? undefined : value
  
  emit('update:filters', { 
    ...props.filters, 
    [key]: cleanValue 
  })
}
</script>