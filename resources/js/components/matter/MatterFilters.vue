<template>
  <div class="space-y-4">
    <!-- Toggles -->
    <div class="flex flex-wrap gap-4">

      <!-- Switches -->
      <div class="flex items-center space-x-6">
        <div class="flex items-center space-x-2">
          <Switch
            id="show-containers"
            :checked="props.filters.Ctnr === true || props.filters.Ctnr === 1"
            @update:checked="(value) => updateFilter('Ctnr', value)"
          />
          <Label htmlFor="show-containers" class="cursor-pointer">
            {{ t('matter.filters.showContainers') }}
          </Label>
        </div>

        <div v-if="$page.props.auth.user.role !== 'CLI'" class="flex items-center space-x-2">
          <Switch
            id="show-mine"
            :checked="props.filters.responsible === $page.props.auth.user.login"
            @update:checked="(value) => updateFilter('responsible', value ? $page.props.auth.user.login : '')"
          />
          <Label htmlFor="show-mine" class="cursor-pointer">
            {{ t('matter.filters.showMine') }}
          </Label>
        </div>

        <div class="flex items-center space-x-2">
          <Switch
            id="include-dead"
            :checked="props.filters.include_dead === true || props.filters.include_dead === 1"
            @update:checked="(value) => updateFilter('include_dead', value)"
          />
          <Label htmlFor="include-dead" class="cursor-pointer">
            {{ t('matter.filters.includeDead') }}
          </Label>
        </div>
      </div>
    </div>

    <!-- Search Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <!-- Category -->
      <div class="space-y-2">
        <Label htmlFor="cat-filter">{{ t('matter.filters.labels.category') }}</Label>
        <TranslatedSelect
          id="cat-filter"
          :placeholder="t('matter.filters.placeholders.category')"
          :model-value="props.filters.Cat"
          @update:model-value="updateFilter('Cat', $event)"
          :options="props.categoryOptions"
          :allow-clear="true"
        />
      </div>

      <!-- Title -->
      <div class="space-y-2">
        <Label htmlFor="title-filter">{{ t('matter.filters.labels.title') }}</Label>
        <Input
          id="title-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.title')"
          :model-value="props.filters.Title"
          @update:model-value="debouncedUpdate('Title', $event)"
        />
      </div>

      <!-- Client -->
      <div v-if="$page.props.auth.user.role !== 'CLI'" class="space-y-2">
        <Label htmlFor="client-filter">{{ t('matter.filters.labels.client') }}</Label>
        <AutocompleteInput
          id="client-filter"
          :placeholder="t('matter.filters.placeholders.client')"
          :model-value="props.filters.Client"
          @update:model-value="debouncedUpdate('Client', $event)"
          endpoint="/actor/autocomplete"
          value-key="value"
          label-key="value"
        />
      </div>

      <!-- Owner -->
      <div class="space-y-2">
        <Label htmlFor="owner-filter">{{ t('matter.filters.labels.owner') }}</Label>
        <AutocompleteInput
          id="owner-filter"
          :placeholder="t('matter.filters.placeholders.owner')"
          :model-value="props.filters.Owner"
          @update:model-value="debouncedUpdate('Owner', $event)"
          endpoint="/actor/autocomplete"
          value-key="value"
          label-key="value"
        />
      </div>

      <!-- Country -->
      <div class="space-y-2">
        <Label htmlFor="country-filter">{{ t('matter.filters.labels.country') }}</Label>
        <Combobox
          id="country-filter"
          :placeholder="t('matter.filters.placeholders.country')"
          :model-value="props.filters.country"
          @update:model-value="updateFilter('country', $event)"
          :options="props.countryOptions"
          :search-placeholder="t('Search countries...')"
          :empty-text="t('No country found.')"
          :allow-clear="true"
        />
      </div>

      <!-- Classes -->
      <div class="space-y-2">
        <Label htmlFor="classes-filter">{{ t('matter.filters.labels.classes') }}</Label>
        <Input
          id="classes-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.classes')"
          :model-value="props.filters.classes"
          @update:model-value="debouncedUpdate('classes', $event)"
        />
      </div>

      <!-- Status -->
      <div class="space-y-2">
        <Label htmlFor="status-filter">{{ t('matter.filters.labels.status') }}</Label>
        <Combobox
          id="status-filter"
          :placeholder="t('matter.filters.placeholders.status')"
          :model-value="props.filters.Status"
          @update:model-value="updateFilter('Status', $event)"
          :options="props.statusOptions"
          :search-placeholder="t('Search statuses...')"
          :empty-text="t('No status found.')"
          :allow-clear="true"
        />
      </div>

      <!-- Reference -->
      <div class="space-y-2">
        <Label htmlFor="ref-filter">{{ t('matter.filters.labels.reference') }}</Label>
        <Input
          id="ref-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.reference')"
          :model-value="props.filters.Ref"
          @update:model-value="debouncedUpdate('Ref', $event)"
        />
      </div>

      <!-- Filing Number -->
      <div class="space-y-2">
        <Label htmlFor="filno-filter">{{ t('matter.filters.labels.filingNumber') }}</Label>
        <Input
          id="filno-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.filingNumber')"
          :model-value="props.filters.FilNo"
          @update:model-value="debouncedUpdate('FilNo', $event)"
        />
      </div>

      <!-- Registration Number -->
      <div class="space-y-2">
        <Label htmlFor="registration-number-filter">{{ t('matter.filters.labels.registrationNumber') }}</Label>
        <Input
          id="registration-number-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.registrationNumber')"
          :model-value="props.filters.registration_number"
          @update:model-value="debouncedUpdate('registration_number', $event)"
        />
      </div>

      <!-- Filing Date -->
      <div class="space-y-2 relative">
        <Label htmlFor="filed-filter">{{ t('matter.filters.labels.filingDate') }}</Label>
        <DateRangeFilter
          :model-value="props.filters.Filed"
          @update:model-value="updateFilter('Filed', $event)"
        />
      </div>

      <!-- Publication Date -->
      <div class="space-y-2">
        <Label htmlFor="pub-filter">{{ t('matter.filters.labels.publicationDate') }}</Label>
        <DateRangeFilter
          :model-value="props.filters.Published"
          @update:model-value="updateFilter('Published', $event)"
        />
      </div>

      <!-- Registration Date -->
      <div class="space-y-2">
        <Label htmlFor="registration-date-filter">{{ t('matter.filters.labels.registrationDate') }}</Label>
        <DateRangeFilter
          :model-value="props.filters.registration_date"
          @update:model-value="updateFilter('registration_date', $event)"
        />
      </div>

      <!-- Client Reference -->
      <div class="space-y-2">
        <Label htmlFor="clref-filter">{{ t('matter.filters.labels.clientReference') }}</Label>
        <Input
          id="clref-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.clientReference')"
          :model-value="props.filters.ClRef"
          @update:model-value="debouncedUpdate('ClRef', $event)"
        />
      </div>

      <!-- Agent -->
      <div class="space-y-2">
        <Label htmlFor="agent-filter">{{ t('matter.filters.labels.agent') }}</Label>
        <Input
          id="agent-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.agent')"
          :model-value="props.filters.Agent"
          @update:model-value="debouncedUpdate('Agent', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { DateRangeFilter } from '@/components/ui/date-picker'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import TranslatedSelect from '@/components/ui/form/TranslatedSelect.vue'
import Combobox from '@/components/ui/combobox/Combobox.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  categoryOptions: {
    type: Array,
    default: () => [],
  },
  countryOptions: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()
const page = usePage()


// Debounce timer
let debounceTimer = null

function updateFilter(key, value) {
  // Emit updated filters with the new value
  emit('update:filters', { 
    ...props.filters, 
    [key]: value 
  })
}

function debouncedUpdate(key, value) {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    // Emit updated filters with the new value
    emit('update:filters', { 
      ...props.filters, 
      [key]: value 
    })
  }, 500)
}
</script>