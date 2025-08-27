<template>
  <Dialog v-model:open="dialogOpen" :max-width="maxWidth">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>
          <span v-if="operation === 'create'">{{ t('types.dialog.createTitle') }}</span>
          <span v-else-if="isEditMode">{{ t('types.dialog.editTitle') }}</span>
          <span v-else>{{ t('types.dialog.viewTitle') }}</span>
        </DialogTitle>
        <DialogDescription>
          <span v-if="operation === 'create'">{{ t('types.dialog.createDescription') }}</span>
          <span v-else-if="isEditMode">{{ t('types.dialog.editDescription') }}</span>
          <span v-else>{{ t('types.dialog.viewDescription') }}</span>
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
            {{ t('types.badge') }}
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
        <!-- Code (only for create) -->
        <div v-if="operation === 'create'" class="space-y-2">
          <Label for="type-code" class="mb-2">{{ t('types.fields.code') }}</Label>
          <Input
            id="type-code"
            v-model="form.code"
            :placeholder="t('types.placeholders.code')"
            maxlength="5"
            class="uppercase"
            required
          />
          <p class="text-sm text-muted-foreground">{{ t('types.hints.code') }}</p>
        </div>
        
        <!-- Type -->
        <div class="space-y-2">
          <Label for="type-type" class="mb-2">{{ t('types.fields.type') }}</Label>
          <Input
            id="type-type"
            v-model="form.type"
            :disabled="!isEditMode && operation !== 'create'"
            :placeholder="t('types.placeholders.type')"
            required
          />
        </div>
        
          <!-- Footer Actions -->
          <DialogFooter>
            <div class="flex justify-between w-full">
              <!-- Left side actions -->
              <div>
                <Button
                  v-if="isEditMode"
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
    :title="t('types.dialog.deleteTitle')"
    :description="t('types.dialog.deleteConfirmation', { code: type?.code })"
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
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: Boolean,
  typeId: String,
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
const type = ref(null)
const isEditMode = ref(false)
const operation = ref(props.operation)

// Form data using Inertia's useForm
const form = useForm({
  code: '',
  type: '',
})

// Dialog width
const maxWidth = computed(() => {
  return 'max-w-lg'
})

// Watch for dialog open
watch(() => props.open, async (newValue) => {
  if (newValue) {
    operation.value = props.operation
    if (props.operation === 'create') {
      isEditMode.value = true
      resetForm()
    } else if (props.typeId) {
      await fetchType()
      isEditMode.value = false
    }
  } else {
    // Reset state when closing
    type.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

// Fetch type data
async function fetchType() {
  loading.value = true
  try {
    const response = await fetch(route('type.show', props.typeId), {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Failed to fetch type')
    
    type.value = await response.json()
    populateForm()
  } catch (error) {
    console.error('Failed to fetch type:', error)
  } finally {
    loading.value = false
  }
}

// Populate form with type data
function populateForm() {
  if (type.value) {
    form.code = type.value.code
    form.type = translated(type.value.type) || ''
  }
}

// Reset form
function resetForm() {
  form.reset()
  type.value = null
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
    createType()
  } else if (isEditMode.value) {
    updateType()
  }
}

// Create type
function createType() {
  form.post(route('type.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Update type
function updateType() {
  form.put(route('type.update', props.typeId), {
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
  router.delete(route('type.destroy', props.typeId), {
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