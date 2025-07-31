<template>
  <Dialog v-model:open="dialogOpen" :max-width="maxWidth">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>
          <span v-if="operation === 'create'">{{ t('defaultActors.dialog.createTitle') }}</span>
          <span v-else-if="isEditMode">{{ t('defaultActors.dialog.editTitle') }}</span>
          <span v-else>{{ t('defaultActors.dialog.viewTitle') }}</span>
        </DialogTitle>
        <DialogDescription>
          <span v-if="operation === 'create'">{{ t('defaultActors.dialog.createDescription') }}</span>
          <span v-else-if="isEditMode">{{ t('defaultActors.dialog.editDescription') }}</span>
          <span v-else>{{ t('defaultActors.dialog.viewDescription') }}</span>
        </DialogDescription>
      </DialogHeader>
      
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
      
      <div v-else class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <Badge variant="secondary">
            {{ t('defaultActors.badge') }}
          </Badge>
          <Button 
            @click="toggleEditMode" 
            variant="outline" 
            size="sm"
            v-if="canWrite && operation !== 'create'"
          >
            <Edit class="mr-2 h-4 w-4" />
            {{ isEditMode ? t('actions.viewMode') : t('actions.editMode') }}
          </Button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Actor -->
          <div class="space-y-2">
            <Label for="default-actor-actor" class="mb-2">{{ t('defaultActors.fields.actor') }} *</Label>
            <AutocompleteInput
              id="default-actor-actor"
              v-model="form.actor_id"
              v-model:display-model-value="actorDisplay"
              endpoint="/actor/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('defaultActors.placeholders.actor')"
              required
            />
            <p v-if="form.errors.actor_id" class="text-sm text-destructive">
              {{ form.errors.actor_id }}
            </p>
          </div>
          
          <!-- Role -->
          <div class="space-y-2">
            <Label for="default-actor-role" class="mb-2">{{ t('defaultActors.fields.role') }} *</Label>
            <AutocompleteInput
              id="default-actor-role"
              v-model="form.role"
              v-model:display-model-value="roleDisplay"
              endpoint="/role/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('defaultActors.placeholders.role')"
              required
            />
            <p v-if="form.errors.role" class="text-sm text-destructive">
              {{ form.errors.role }}
            </p>
          </div>
          
          <!-- Country -->
          <div class="space-y-2">
            <Label for="default-actor-country" class="mb-2">{{ t('defaultActors.fields.country') }}</Label>
            <AutocompleteInput
              id="default-actor-country"
              v-model="form.for_country"
              v-model:display-model-value="countryDisplay"
              endpoint="/country/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('defaultActors.placeholders.country')"
            />
          </div>
          
          <!-- Category -->
          <div class="space-y-2">
            <Label for="default-actor-category" class="mb-2">{{ t('defaultActors.fields.category') }}</Label>
            <AutocompleteInput
              id="default-actor-category"
              v-model="form.for_category"
              v-model:display-model-value="categoryDisplay"
              endpoint="/category/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('defaultActors.placeholders.category')"
            />
          </div>
          
          <!-- Client -->
          <div class="space-y-2">
            <Label for="default-actor-client" class="mb-2">{{ t('defaultActors.fields.client') }}</Label>
            <AutocompleteInput
              id="default-actor-client"
              v-model="form.for_client"
              v-model:display-model-value="clientDisplay"
              endpoint="/actor/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('defaultActors.placeholders.client')"
            />
          </div>
          
          <!-- Footer Actions -->
          <DialogFooter>
            <div class="flex justify-between w-full">
              <!-- Left side actions -->
              <div>
                <Button
                  v-if="isEditMode && operation !== 'create'"
                  @click="confirmDelete"
                  type="button"
                  variant="destructive"
                >
                  <Trash2 class="mr-2 h-4 w-4" />
                  {{ t('actions.delete') }}
                </Button>
              </div>
              
              <!-- Right side actions -->
              <div class="flex gap-2">
                <Button
                  @click="dialogOpen = false"
                  type="button"
                  variant="outline"
                >
                  {{ t('actions.cancel') }}
                </Button>
                
                <Button
                  v-if="isEditMode || operation === 'create'"
                  type="submit"
                  :disabled="form.processing"
                >
                  <template v-if="form.processing">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    {{ t('common.saving') }}
                  </template>
                  <template v-else>
                    {{ t('actions.save') }}
                  </template>
                </Button>
              </div>
            </div>
          </DialogFooter>
        </form>
      </div>
    </DialogContent>
  </Dialog>
  
  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="deleteDialogOpen"
    :title="t('defaultActors.dialog.deleteTitle')"
    :description="t('defaultActors.dialog.deleteConfirmation')"
    :confirm-text="t('actions.delete')"
    confirm-variant="destructive"
    @confirm="handleDelete"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Edit, Trash2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: Boolean,
  defaultActorId: [Number, String, null],
  operation: {
    type: String,
    default: 'view',
    validator: (value) => ['view', 'create', 'edit'].includes(value)
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()
const page = usePage()
const { translated } = useTranslatedField()

// Permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

// Dialog state
const dialogOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

// Component state
const loading = ref(false)
const deleteDialogOpen = ref(false)
const defaultActor = ref(null)
const isEditMode = ref(false)
const operation = ref(props.operation)

// Display values for autocompletes
const actorDisplay = ref('')
const roleDisplay = ref('')
const countryDisplay = ref('')
const categoryDisplay = ref('')
const clientDisplay = ref('')

// Form data using Inertia's useForm
const form = useForm({
  actor_id: null,
  role: '',
  for_country: '',
  for_category: '',
  for_client: null,
})

// Dialog width
const maxWidth = computed(() => {
  return 'max-w-2xl'
})

// Watch for dialog open
watch(() => props.open, async (newValue) => {
  if (newValue) {
    operation.value = props.operation
    if (props.operation === 'create') {
      isEditMode.value = true
      resetForm()
    } else if (props.defaultActorId) {
      await fetchDefaultActor()
      isEditMode.value = false
    }
  } else {
    // Reset state when closing
    defaultActor.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

// Fetch default actor data
async function fetchDefaultActor() {
  loading.value = true
  try {
    const response = await fetch(route('default_actor.show', props.defaultActorId), {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Failed to fetch default actor')
    
    defaultActor.value = await response.json()
    populateForm()
  } catch (error) {
    console.error('Failed to fetch default actor:', error)
  } finally {
    loading.value = false
  }
}

// Populate form with default actor data
function populateForm() {
  if (defaultActor.value) {
    form.actor_id = defaultActor.value.actor_id
    form.role = defaultActor.value.role
    form.for_country = defaultActor.value.for_country
    form.for_category = defaultActor.value.for_category
    form.for_client = defaultActor.value.for_client
    
    // Set display values
    actorDisplay.value = defaultActor.value.actor?.name || ''
    roleDisplay.value = translated(defaultActor.value.roleInfo?.name) || ''
    countryDisplay.value = defaultActor.value.country?.name || ''
    categoryDisplay.value = translated(defaultActor.value.category?.category) || ''
    clientDisplay.value = defaultActor.value.client?.name || ''
  }
}

// Reset form
function resetForm() {
  form.reset()
  defaultActor.value = null
  actorDisplay.value = ''
  roleDisplay.value = ''
  countryDisplay.value = ''
  categoryDisplay.value = ''
  clientDisplay.value = ''
}

// Toggle edit mode
function toggleEditMode() {
  if (!isEditMode.value) {
    populateForm()
  }
  isEditMode.value = !isEditMode.value
}

// Handle form submission
function handleSubmit() {
  if (props.operation === 'create') {
    createDefaultActor()
  } else if (isEditMode.value) {
    updateDefaultActor()
  }
}

// Create default actor
function createDefaultActor() {
  form.post(route('default_actor.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Update default actor
function updateDefaultActor() {
  form.put(route('default_actor.update', props.defaultActorId), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Delete confirmation
function confirmDelete() {
  deleteDialogOpen.value = true
}

// Handle delete
function handleDelete() {
  router.delete(route('default_actor.destroy', props.defaultActorId), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      deleteDialogOpen.value = false
      dialogOpen.value = false
      emit('success')
    }
  })
}
</script>