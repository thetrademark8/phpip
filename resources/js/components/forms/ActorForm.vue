<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-6">
      <!-- Basic Information -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Basic Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Name -->
          <FormField
            label="Name"
            name="name"
            :error="form.errors.name"
            required
            help-text="Last name or company name"
          >
            <Input
              v-model="form.name"
              placeholder="NAME Firstname"
            />
          </FormField>

          <!-- First name -->
          <FormField
            label="First name"
            name="first_name"
            :error="form.errors.first_name"
            help-text="For individuals only"
          >
            <Input
              v-model="form.first_name"
              placeholder="Optional"
            />
          </FormField>

          <!-- Display name -->
          <FormField
            label="Display name"
            name="display_name"
            :error="form.errors.display_name"
            help-text="How the name appears in lists"
          >
            <Input
              v-model="form.display_name"
            />
          </FormField>

          <!-- Employer -->
          <FormField
            label="Employer"
            name="company_id"
            :error="form.errors.company_id"
            help-text="Parent company if applicable"
          >
            <AutocompleteInput
              v-model="form.company_id"
              endpoint="/actor/autocomplete"
              placeholder="Select employer"
              value-key="key"
              label-key="value"
            />
          </FormField>

          <!-- Default role -->
          <FormField
            label="Default role"
            name="default_role"
            :error="form.errors.role"
            help-text="Role automatically assigned in new matters"
          >
            <AutocompleteInput
              v-model="form.role"
              endpoint="/role/autocomplete"
              placeholder="Select default role"
              value-key="key"
              label-key="value"
            />
          </FormField>

          <!-- Function -->
          <FormField
            label="Function"
            name="function"
            :error="form.errors.function"
            help-text="Job title or function"
          >
            <Input
              v-model="form.function"
            />
          </FormField>
        </div>
      </div>

      <!-- Contact Details -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Contact Details</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Address -->
          <div class="md:col-span-2">
            <FormField
              label="Address"
              name="address"
              :error="form.errors.address"
            >
              <Textarea
                v-model="form.address"
                :rows="3"
                class="resize-none"
              />
            </FormField>
          </div>

          <!-- Country -->
          <FormField
            label="Country"
            name="country"
            :error="form.errors.country"
          >
            <AutocompleteInput
              v-model="form.country"
              endpoint="/country/autocomplete"
              placeholder="Select country"
              value-key="key"
              label-key="value"
            />
          </FormField>

          <!-- Email -->
          <FormField
            label="Email"
            name="email"
            :error="form.errors.email"
          >
            <Input
              v-model="form.email"
              type="email"
              placeholder="email@example.com"
            />
          </FormField>

          <!-- Phone -->
          <FormField
            label="Phone"
            name="phone"
            :error="form.errors.phone"
          >
            <Input
              v-model="form.phone"
              type="tel"
              placeholder="+1 234 567 8900"
            />
          </FormField>
        </div>
      </div>

    </div>
  </form>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  actor: {
    type: Object,
    default: null
  },
  onSuccess: {
    type: Function,
    default: null
  }
})

// Initialize form with actor data or defaults
const form = useForm({
  name: props.actor?.name || '',
  first_name: props.actor?.first_name || '',
  display_name: props.actor?.display_name || '',
  company_id: props.actor?.company_id || '',
  default_role: props.actor?.role || '',
  function: props.actor?.function || '',
  address: props.actor?.address || '',
  country: props.actor?.country || '',
  email: props.actor?.email || '',
  phone: props.actor?.phone || ''
})

// Watch for actor prop changes
watch(() => props.actor, (newActor) => {
  if (newActor) {
    Object.keys(form.data()).forEach(key => {
      if (newActor[key] !== undefined) {
        form[key] = newActor[key]
      }
    })
  }
}, { deep: true })

// Handle form submission
const handleSubmit = () => {
  if (props.actor) {
    // Update existing actor
    form.put(`/actor/${props.actor.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        if (props.onSuccess) {
          props.onSuccess(page)
        }
      }
    })
  } else {
    // Create new actor
    form.post('/actor', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        if (props.onSuccess) {
          props.onSuccess(page)
        }
      }
    })
  }
}

// Expose form state and methods to parent
defineExpose({
  form,
  handleSubmit
})
</script>