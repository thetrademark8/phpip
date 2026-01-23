<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-6">
      <!-- Category -->
      <FormField
        :label="t('Category')"
        name="category_code"
        :error="form.errors.category_code"
        required
      >
        <TranslatedSelect
          v-model="form.category_code"
          :options="categoryOptions"
          :placeholder="t('Select category')"
          @update:model-value="handleCategoryChange"
        />
      </FormField>

      <!-- Pub Number (OPS mode only) -->
      <FormField
        v-if="operation === 'ops'"
        :label="t('Pub Number')"
        name="docnum"
        :error="form.errors.docnum"
        :help-text="t('Publication number help')"
      >
        <Input
          v-model="form.docnum"
          placeholder="CCNNNNNN"
        />
      </FormField>

      <!-- Client (OPS mode only) -->
      <FormField
        v-if="operation === 'ops'"
        :label="t('Client')"
        name="client_id"
        :error="form.errors.client_id"
      >
        <AutocompleteInput
          v-model="form.client_id"
          endpoint="/actor/autocomplete"
          :placeholder="t('Select client')"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Country (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        :label="t('Country')"
        name="country"
        :error="form.errors.country"
        required
      >
        <Combobox
          v-model="form.country"
          :options="countryOptions"
          :placeholder="t('Select country')"
          :search-placeholder="t('Search countries...')"
          :no-results-text="t('No country found.')"
        />
      </FormField>

      <!-- Origin (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        :label="t('Origin')"
        name="origin"
        :error="form.errors.origin"
      >
        <Combobox
          v-model="form.origin"
          :options="countryOptions"
          :placeholder="t('Select origin')"
          :search-placeholder="t('Search countries...')"
          :no-results-text="t('No country found.')"
        />
      </FormField>

      <!-- Type (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        :label="t('Type')"
        name="type_code"
        :error="form.errors.type_code"
      >
        <TranslatedSelect
          v-model="form.type_code"
          :options="typeOptions"
          :placeholder="t('Select type')"
        />
      </FormField>

      <!-- Caseref -->
      <FormField
        :label="t('Caseref')"
        name="caseref"
        :error="form.errors.caseref"
        required
      >
        <Input
          v-model="form.caseref"
          :readonly="operation === 'child'"
          :data-ac="operation !== 'child' ? '/matter/new-caseref' : undefined"
        />
      </FormField>

      <!-- Responsible -->
      <FormField
        :label="t('Responsible')"
        name="responsible"
        :error="form.errors.responsible"
        required
      >
        <Combobox
          v-model="form.responsible"
          :options="userOptions"
          :placeholder="t('Select responsible')"
          :search-placeholder="t('Search users...')"
          :no-results-text="t('No user found.')"
        />
      </FormField>

      <!-- Priority selection (child mode only) -->
      <div v-if="operation === 'child'" class="space-y-2">
        <Label>{{ t('Use original matter as') }}</Label>
        <RadioGroup v-model="priorityValue">
          <div class="flex items-center space-x-2">
            <RadioGroupItem value="1" id="priority-1" />
            <Label for="priority-1">{{ t('Priority application') }}</Label>
          </div>
          <div class="flex items-center space-x-2">
            <RadioGroupItem value="0" id="priority-0" />
            <Label for="priority-0">{{ t('Parent application') }}</Label>
          </div>
        </RadioGroup>
      </div>

      <!-- Submit button -->
      <div class="flex justify-end space-x-2">
        <Button
          v-if="onCancel"
          type="button"
          variant="outline"
          @click="onCancel"
          :disabled="form.processing"
        >
          {{ t('Cancel') }}
        </Button>
        <Button
          type="submit"
          :disabled="form.processing"
        >
          <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ submitLabel }}
        </Button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import FormField from '@/components/ui/form/FormField.vue'
import TranslatedSelect from '@/components/ui/form/TranslatedSelect.vue'
import { Combobox } from '@/components/ui/combobox'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  operation: {
    type: String,
    default: 'new',
    validator: (value) => ['new', 'child', 'ops'].includes(value)
  },
  parentMatter: {
    type: Object,
    default: null
  },
  category: {
    type: String,
    default: null
  },
  currentUser: {
    type: Object,
    default: null
  },
  onSuccess: {
    type: Function,
    default: null
  },
  onCancel: {
    type: Function,
    default: null
  },
  categoryOptions: {
    type: Array,
    default: () => []
  },
  typeOptions: {
    type: Array,
    default: () => []
  },
  userOptions: {
    type: Array,
    default: () => []
  },
  countryOptions: {
    type: Array,
    default: () => []
  }
})

const { t } = useI18n()

// Form setup
const form = useForm({
  operation: props.operation,
  category_code: props.parentMatter?.category_code || props.category || '',
  docnum: '',
  client_id: '',
  parent_id: props.parentMatter?.id || '',
  country: props.parentMatter?.country || '',
  origin: props.parentMatter?.origin || '',
  type_code: props.parentMatter?.type_code || '',
  caseref: props.parentMatter?.caseref || '',
  responsible: props.parentMatter?.responsible || props.currentUser?.login || '',
  priority: '1' // Default to priority application
})

// Priority radio group value
const priorityValue = computed({
  get: () => form.priority,
  set: (value) => form.priority = value
})

// Submit label
const submitLabel = computed(() => {
  if (form.processing) return t('Creating...')
  return t('Create')
})

// Handle category selection change
const handleCategoryChange = async (categoryCode) => {
  if (categoryCode && props.operation !== 'child') {
    const category = props.categoryOptions.find(c => c.value === categoryCode)
    if (category?.prefix) {
      try {
        const response = await fetch(`/matter/new-caseref?term=${category.prefix}`, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        if (response.ok) {
          const data = await response.json()
          if (data && data[0] && data[0].value) {
            form.caseref = data[0].value
          }
        }
      } catch (error) {
        console.error('Error fetching next caseref:', error)
      }
    }
  }
}

// Handle form submission
const handleSubmit = () => {
  const endpoint = props.operation === 'ops' ? '/matter/storeFamily' : '/matter'

  form.post(endpoint, {
    preserveScroll: true,
    onSuccess: (page) => {
      if (props.onSuccess) {
        props.onSuccess(page)
      }
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
    }
  })
}

// Initialize responsible if current user is provided
watch(() => props.currentUser, (user) => {
  if (user && !form.responsible) {
    form.responsible = user.login
  }
}, { immediate: true })

// Initialize parent matter values
watch(() => props.parentMatter, (matter) => {
  if (matter) {
    if (matter.country && !form.country) {
      form.country = matter.country
    }
    if (matter.origin && !form.origin) {
      form.origin = matter.origin
    }
    if (matter.type_code && !form.type_code) {
      form.type_code = matter.type_code
    }
  }
}, { immediate: true })

// Fetch caseref when category is pre-selected
onMounted(async () => {
  if (props.category && props.operation === 'new') {
    await handleCategoryChange(props.category)
  }
})
</script>
