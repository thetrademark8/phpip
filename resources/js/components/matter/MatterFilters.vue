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

      <!-- Category -->
      <div class="space-y-2">
        <Label htmlFor="cat-filter">{{ t('matter.filters.labels.category') }}</Label>
        <AutocompleteInput
          id="cat-filter"
          :placeholder="t('matter.filters.placeholders.category')"
          :model-value="props.filters.Cat"
          @update:model-value="debouncedUpdate('Cat', $event)"
          endpoint="/category/autocomplete"
          value-key="key"
          label-key="key"
          class="uppercase"
        />
      </div>

      <!-- Status -->
      <div class="space-y-2">
        <Label htmlFor="status-filter">{{ t('matter.filters.labels.status') }}</Label>
        <AutocompleteInput
          id="status-filter"
          :placeholder="t('matter.filters.placeholders.status')"
          :model-value="props.filters.Status"
          @update:model-value="debouncedUpdate('Status', $event)"
          endpoint="/status-event/autocomplete"
          value-key="value"
          label-key="value"
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

      <!-- Applicant -->
      <div class="space-y-2">
        <Label htmlFor="applicant-filter">{{ t('matter.filters.labels.applicant') }}</Label>
        <AutocompleteInput
          id="applicant-filter"
          :placeholder="t('matter.filters.placeholders.applicant')"
          :model-value="props.filters.Applicant"
          @update:model-value="debouncedUpdate('Applicant', $event)"
          endpoint="/actor/autocomplete"
          value-key="value"
          label-key="value"
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

      <!-- Agent Reference -->
      <div class="space-y-2">
        <Label htmlFor="agtref-filter">{{ t('matter.filters.labels.agentReference') }}</Label>
        <Input
          id="agtref-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.agentReference')"
          :model-value="props.filters.AgtRef"
          @update:model-value="debouncedUpdate('AgtRef', $event)"
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

      <!-- Inventor -->
      <div class="space-y-2">
        <Label htmlFor="inventor-filter">{{ t('matter.filters.labels.inventor') }}</Label>
        <AutocompleteInput
          id="inventor-filter"
          :placeholder="t('matter.filters.placeholders.inventor')"
          :model-value="props.filters.Inventor1"
          @update:model-value="debouncedUpdate('Inventor1', $event)"
          endpoint="/actor/autocomplete"
          value-key="value"
          label-key="value"
        />
      </div>

      <!-- Status Date -->
      <div class="space-y-2">
        <Label htmlFor="status-date-filter">{{ t('matter.filters.labels.statusDate') }}</Label>
        <DatePicker
          id="status-date-filter"
          :model-value="props.filters.Status_date"
          @update:model-value="updateFilter('Status_date', $event)"
          :placeholder="t('matter.filters.placeholders.selectDate')"
        />
      </div>

      <!-- Filing Date -->
      <div class="space-y-2">
        <Label htmlFor="filed-filter">{{ t('matter.filters.labels.filingDate') }}</Label>
        <DatePicker
          id="filed-filter"
          :model-value="props.filters.Filed"
          @update:model-value="updateFilter('Filed', $event)"
          :placeholder="t('matter.filters.placeholders.selectDate')"
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

      <!-- Publication Date -->
      <div class="space-y-2">
        <Label htmlFor="pub-filter">{{ t('matter.filters.labels.publicationDate') }}</Label>
        <DatePicker
          id="pub-filter"
          :model-value="props.filters.Published"
          @update:model-value="updateFilter('Published', $event)"
          :placeholder="t('matter.filters.placeholders.selectDate')"
        />
      </div>

      <!-- Publication Number -->
      <div class="space-y-2">
        <Label htmlFor="pubno-filter">{{ t('matter.filters.labels.publicationNumber') }}</Label>
        <Input
          id="pubno-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.publicationNumber')"
          :model-value="props.filters.PubNo"
          @update:model-value="debouncedUpdate('PubNo', $event)"
        />
      </div>

      <!-- Grant Date -->
      <div class="space-y-2">
        <Label htmlFor="granted-filter">{{ t('matter.filters.labels.grantDate') }}</Label>
        <DatePicker
          id="granted-filter"
          :model-value="props.filters.Granted"
          @update:model-value="updateFilter('Granted', $event)"
          :placeholder="t('matter.filters.placeholders.selectDate')"
        />
      </div>

      <!-- Grant Number -->
      <div class="space-y-2">
        <Label htmlFor="grtno-filter">{{ t('matter.filters.labels.grantNumber') }}</Label>
        <Input
          id="grtno-filter"
          type="text"
          :placeholder="t('matter.filters.placeholders.grantNumber')"
          :model-value="props.filters.GrtNo"
          @update:model-value="debouncedUpdate('GrtNo', $event)"
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
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
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