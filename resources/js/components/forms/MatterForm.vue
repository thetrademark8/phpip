<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-6">
      <!-- Category -->
      <FormField
        label="Category"
        name="category_code"
        :error="form.errors.category_code"
        required
      >
        <AutocompleteInput
          v-model="form.category_code"
          v-model:display-model-value="categoryDisplay"
          endpoint="/category/autocomplete"
          placeholder="Select category"
          :min-length="0"
          value-key="key"
          label-key="value"
          @selected="handleCategorySelect"
        />
      </FormField>

      <!-- Pub Number (OPS mode only) -->
      <FormField
        v-if="operation === 'ops'"
        label="Pub Number"
        name="docnum"
        :error="form.errors.docnum"
        help-text="Publication number prefixed with the country code and optionally suffixed with the kind code. No spaces nor non-alphanumeric characters."
      >
        <Input
          v-model="form.docnum"
          placeholder="CCNNNNNN"
        />
      </FormField>

      <!-- Client (OPS mode only) -->
      <FormField
        v-if="operation === 'ops'"
        label="Client"
        name="client_id"
        :error="form.errors.client_id"
      >
        <AutocompleteInput
          v-model="form.client_id"
          endpoint="/actor/autocomplete"
          placeholder="Select client"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Country (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        label="Country"
        name="country"
        :error="form.errors.country"
        required
      >
        <AutocompleteInput
          v-model="form.country"
          v-model:display-model-value="countryDisplay"
          endpoint="/country/autocomplete"
          :placeholder="parentMatter?.countryInfo?.name || 'Select country'"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Origin (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        label="Origin"
        name="origin"
        :error="form.errors.origin"
      >
        <AutocompleteInput
          v-model="form.origin"
          v-model:display-model-value="originDisplay"
          endpoint="/country/autocomplete"
          :placeholder="parentMatter?.originInfo?.name || 'Select origin'"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Type (non-OPS mode) -->
      <FormField
        v-if="operation !== 'ops'"
        label="Type"
        name="type_code"
        :error="form.errors.type_code"
      >
        <AutocompleteInput
          v-model="form.type_code"
          endpoint="/type/autocomplete"
          :placeholder="parentMatter?.type?.type || 'Select type'"
          :min-length="0"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Caseref -->
      <FormField
        label="Caseref"
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
        label="Responsible"
        name="responsible"
        :error="form.errors.responsible"
        required
      >
        <AutocompleteInput
          v-model="form.responsible"
          v-model:display-model-value="responsibleDisplay"
          endpoint="/user/autocomplete"
          :placeholder="parentMatter?.responsible || currentUser?.name || 'Select responsible'"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Priority selection (child mode only) -->
      <div v-if="operation === 'child'" class="space-y-2">
        <Label>Use original matter as</Label>
        <RadioGroup v-model="priorityValue">
          <div class="flex items-center space-x-2">
            <RadioGroupItem value="1" id="priority-1" />
            <Label for="priority-1">Priority application</Label>
          </div>
          <div class="flex items-center space-x-2">
            <RadioGroupItem value="0" id="priority-0" />
            <Label for="priority-0">Parent application</Label>
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
          Cancel
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
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

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
    type: Object,
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
  }
})

// Form setup
const form = useForm({
  operation: props.operation,
  category_code: props.parentMatter?.category_code || props.category?.code || '',
  docnum: '',
  client_id: '',
  parent_id: props.parentMatter?.id || '',
  country: props.parentMatter?.country || '',
  origin: props.parentMatter?.origin || '',
  type_code: props.parentMatter?.type_code || '',
  caseref: props.parentMatter?.caseref || props.category?.next_caseref || '',
  responsible: props.parentMatter?.responsible || props.currentUser?.login || '',
  priority: '1' // Default to priority application
})

// Display values for autocomplete fields
const categoryDisplay = ref(props.category?.name || props.parentMatter?.category?.category || '')
const countryDisplay = ref('')
const originDisplay = ref('')
const responsibleDisplay = ref('')

// Priority radio group value
const priorityValue = computed({
  get: () => form.priority,
  set: (value) => form.priority = value
})

// Submit label
const submitLabel = computed(() => {
  if (form.processing) return 'Creating...'
  return 'Create'
})

// Handle category selection
const handleCategorySelect = async (category) => {
  if (category && props.operation !== 'child') {
    // Fetch the next caseref based on the category prefix
    if (category.prefix) {
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
    preserveState: true,
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

// Initialize responsible display value
watch(() => props.currentUser, (user) => {
  if (user && !responsibleDisplay.value) {
    responsibleDisplay.value = user.name
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
</script>