<template>
  <Dialog v-model:open="dialogOpen" :max-width="maxWidth">
    <DialogScrollContent>
      <DialogHeader>
        <div class="flex items-center justify-between">
          <div>
            <DialogTitle>
              <span v-if="operation === 'create'">{{ t('classifierTypes.dialog.createTitle') }}</span>
              <span v-else-if="isEditMode">{{ t('classifierTypes.dialog.editTitle') }}</span>
              <span v-else>{{ t('classifierTypes.dialog.viewTitle') }}</span>
            </DialogTitle>
            <DialogDescription>
              <span v-if="operation === 'create'">{{ t('classifierTypes.dialog.createDescription') }}</span>
              <span v-else-if="isEditMode">{{ t('classifierTypes.dialog.editDescription') }}</span>
              <span v-else>{{ t('classifierTypes.dialog.viewDescription') }}</span>
            </DialogDescription>
          </div>
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
      </DialogHeader>
      
      <!-- Loading state -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
      
      <div v-else class="space-y-6">
        <!-- Mode Badge -->
        <div class="flex items-center justify-between border-b pb-4">
          <Badge variant="secondary">
            {{ t('classifierTypes.badge') }}
          </Badge>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Code -->
          <div class="space-y-2">
            <Label for="classifier-type-code" class="mb-2">{{ t('classifierTypes.fields.code') }} *</Label>
            <Input
              id="classifier-type-code"
              v-model="form.code"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('classifierTypes.placeholders.code')"
              maxlength="5"
              required
            />
            <p v-if="form.errors.code" class="text-sm text-destructive">
              {{ form.errors.code }}
            </p>
          </div>
          
          <!-- Type -->
          <div class="space-y-2">
            <Label for="classifier-type-type" class="mb-2">{{ t('classifierTypes.fields.type') }} *</Label>
            <Input
              id="classifier-type-type"
              v-model="form.type"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('classifierTypes.placeholders.type')"
              maxlength="45"
              required
            />
            <p v-if="form.errors.type" class="text-sm text-destructive">
              {{ form.errors.type }}
            </p>
          </div>
          
          <!-- Display Order -->
          <div class="space-y-2">
            <Label for="classifier-type-display-order" class="mb-2">{{ t('classifierTypes.fields.displayOrder') }}</Label>
            <Input
              id="classifier-type-display-order"
              v-model="form.display_order"
              type="number"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('classifierTypes.placeholders.displayOrder')"
            />
          </div>
          
          <!-- Category -->
          <div class="space-y-2">
            <Label for="classifier-type-category" class="mb-2">{{ t('classifierTypes.fields.category') }}</Label>
            <AutocompleteInput
              id="classifier-type-category"
              v-model="form.for_category"
              v-model:display-model-value="categoryDisplay"
              endpoint="/category/autocomplete"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('classifierTypes.placeholders.category')"
            />
          </div>
          
          <!-- Main Display -->
          <div class="flex items-center space-x-2">
            <Checkbox
              id="classifier-type-main-display"
              v-model:checked="form.main_display"
              :disabled="!isEditMode && operation !== 'create'"
            />
            <Label for="classifier-type-main-display" class="mb-2">{{ t('classifierTypes.fields.mainDisplay') }}</Label>
          </div>
          
          <!-- Notes -->
          <div class="space-y-2">
            <Label for="classifier-type-notes" class="mb-2">{{ t('classifierTypes.fields.notes') }}</Label>
            <Textarea
              id="classifier-type-notes"
              v-model="form.notes"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('classifierTypes.placeholders.notes')"
              rows="3"
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
    </DialogScrollContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="deleteDialogOpen"
    :title="t('classifierTypes.dialog.deleteTitle')"
    :description="t('classifierTypes.dialog.deleteConfirmation')"
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
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: Boolean,
  classifierTypeId: [String, null],
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
const classifierType = ref(null)
const isEditMode = ref(false)
const operation = ref(props.operation)

// Display values for autocompletes
const categoryDisplay = ref('')

// Form data using Inertia's useForm
const form = useForm({
  code: '',
  type: '',
  display_order: null,
  for_category: '',
  main_display: false,
  notes: '',
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
    } else if (props.classifierTypeId) {
      await fetchClassifierType()
      isEditMode.value = false
    }
  } else {
    // Reset state when closing
    classifierType.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

// Fetch classifier type data
async function fetchClassifierType() {
  loading.value = true
  try {
    const response = await fetch(route('classifier_type.show', props.classifierTypeId), {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Failed to fetch classifier type')
    
    classifierType.value = await response.json()
    populateForm()
  } catch (error) {
    console.error('Failed to fetch classifier type:', error)
  } finally {
    loading.value = false
  }
}

// Populate form with classifier type data
function populateForm() {
  if (classifierType.value) {
    form.code = classifierType.value.code
    form.type = translated(classifierType.value.type) || classifierType.value.type
    form.display_order = classifierType.value.display_order
    form.for_category = classifierType.value.for_category
    form.main_display = Boolean(classifierType.value.main_display)
    form.notes = classifierType.value.notes || ''
    
    // Set display values
    categoryDisplay.value = translated(classifierType.value.category?.category) || ''
  }
}

// Reset form
function resetForm() {
  form.reset()
  classifierType.value = null
  categoryDisplay.value = ''
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
    createClassifierType()
  } else if (isEditMode.value) {
    updateClassifierType()
  }
}

// Create classifier type
function createClassifierType() {
  form.post(route('classifier_type.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Update classifier type
function updateClassifierType() {
  form.put(route('classifier_type.update', props.classifierTypeId), {
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
  router.delete(route('classifier_type.destroy', props.classifierTypeId), {
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