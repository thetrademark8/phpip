<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-6">
      <!-- Show restriction summary if any fields are restricted -->
      <div v-if="hasRestrictedFields" class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
        <div class="flex items-start gap-2">
          <AlertCircle class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-medium text-orange-800 dark:text-orange-200 text-sm">
              {{ t('Some actor fields are restricted') }}
            </p>
            <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
              {{ t('Fields marked with a lock icon cannot be edited based on your user role and actor type. Contact an administrator if you need to modify these fields.') }}
            </p>
          </div>
        </div>
      </div>
      <!-- Basic Information -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold">Basic Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Name -->
          <FormField
            name="name"
            :error="form.errors.name"
            required
            help-text="Last name or company name"
          >
            <template #label>
              <div class="flex items-center gap-2">
                <span>Name</span>
                <div v-if="!isFieldEditable('name')" class="flex items-center gap-1">
                  <Lock class="h-3 w-3 text-muted-foreground" />
                  <div class="relative group">
                    <AlertCircle 
                      class="h-3 w-3 text-orange-500 cursor-help" 
                      @click="showRestrictionFeedback('name')"
                    />
                    <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                      {{ getTranslatedRestrictionReason('name') }}
                    </div>
                  </div>
                </div>
              </div>
            </template>
            <Input
              v-model="form.name"
              placeholder="NAME Firstname"
              :disabled="!isFieldEditable('name')"
              :class="{
                'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75': !isFieldEditable('name')
              }"
            />
          </FormField>

          <!-- First name -->
          <FormField
            name="first_name"
            :error="form.errors.first_name"
            help-text="For individuals only"
          >
            <template #label>
              <div class="flex items-center gap-2">
                <span>First name</span>
                <div v-if="!isFieldEditable('first_name')" class="flex items-center gap-1">
                  <Lock class="h-3 w-3 text-muted-foreground" />
                  <div class="relative group">
                    <AlertCircle 
                      class="h-3 w-3 text-orange-500 cursor-help" 
                      @click="showRestrictionFeedback('first_name')"
                    />
                    <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                      {{ getTranslatedRestrictionReason('first_name') }}
                    </div>
                  </div>
                </div>
              </div>
            </template>
            <Input
              v-model="form.first_name"
              placeholder="Optional"
              :disabled="!isFieldEditable('first_name')"
              :class="{
                'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75': !isFieldEditable('first_name')
              }"
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
            name="email"
            :error="form.errors.email"
          >
            <template #label>
              <div class="flex items-center gap-2">
                <span>Email</span>
                <div v-if="!isFieldEditable('email')" class="flex items-center gap-1">
                  <Lock class="h-3 w-3 text-muted-foreground" />
                  <div class="relative group">
                    <AlertCircle 
                      class="h-3 w-3 text-orange-500 cursor-help" 
                      @click="showRestrictionFeedback('email')"
                    />
                    <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                      {{ getTranslatedRestrictionReason('email') }}
                    </div>
                  </div>
                </div>
              </div>
            </template>
            <Input
              v-model="form.email"
              type="email"
              placeholder="email@example.com"
              :disabled="!isFieldEditable('email')"
              :class="{
                'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75': !isFieldEditable('email')
              }"
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
import { Loader2, Lock, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import { usePermissions } from '@/composables/usePermissions'
import { useI18n } from 'vue-i18n'

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

const { t } = useI18n()
const { canWrite, isAdmin, hasRole } = usePermissions()

// Simple permission logic - either can edit all fields or none
const isFieldEditable = (field) => canWrite.value

// Simple restriction message
const getTranslatedRestrictionReason = (fieldName) => {
  return t('You do not have permission to modify actor data')
}

const showRestrictionFeedback = (fieldName) => {
  const reason = getTranslatedRestrictionReason(fieldName)
  if (reason) {
    const notification = document.createElement('div')
    notification.className = 'fixed top-4 right-4 bg-orange-100 dark:bg-orange-900 border border-orange-200 dark:border-orange-800 text-orange-800 dark:text-orange-200 px-4 py-2 rounded-lg shadow-lg z-50 max-w-sm'
    notification.innerHTML = `
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <div>
          <p class="font-medium text-sm">${t('Field Restricted')}</p>
          <p class="text-xs mt-1">${reason}</p>
        </div>
      </div>
    `
    document.body.appendChild(notification)
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 5000)
  }
}

// Simple permission checks - either can edit everything or nothing
const hasRestrictedFields = computed(() => {
  return !canWrite.value
})

const hasEditableFields = computed(() => {
  return canWrite.value
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