<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- Name Filter -->
    <div class="space-y-2">
      <Label for="filter-name" class="mb-2">{{ $t('Name') }}</Label>
      <Input
        id="filter-name"
        :model-value="filters.Name"
        @update:model-value="debouncedUpdate('Name', $event)"
        :placeholder="$t('NAME Firstname')"
        class="w-full"
      />
    </div>

    <!-- Role Filter -->
    <div class="space-y-2">
      <Label for="filter-role" class="mb-2">{{ $t('Role') }}</Label>
      <Input
        id="filter-role"
        :model-value="filters.Role"
        @update:model-value="debouncedUpdate('Role', $event)"
        :placeholder="$t('Role')"
        class="w-full"
      />
    </div>

    <!-- Username Filter -->
    <div class="space-y-2">
      <Label for="filter-username" class="mb-2">{{ $t('User name') }}</Label>
      <Input
        id="filter-username"
        :model-value="filters.Username"
        @update:model-value="debouncedUpdate('Username', $event)"
        :placeholder="$t('User name')"
        class="w-full"
      />
    </div>

    <!-- Company Filter -->
    <div class="space-y-2">
      <Label for="filter-company" class="mb-2">{{ $t('Company') }}</Label>
      <Input
        id="filter-company"
        :model-value="filters.Company"
        @update:model-value="debouncedUpdate('Company', $event)"
        :placeholder="$t('Company')"
        class="w-full"
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
</script>