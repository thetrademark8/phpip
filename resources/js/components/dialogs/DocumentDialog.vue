<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ dialogTitle }}
        </DialogTitle>
        <DialogDescription v-if="operation !== 'view'">
          {{ operation === 'create' ? t('document.dialog.createDescription') : t('document.dialog.editDescription') }}
        </DialogDescription>
      </DialogHeader>

      <DialogSkeleton v-if="loading" :fields="3" />

      <div v-else-if="templateClass || operation === 'create'" class="space-y-4">
        <!-- Mode Toggle -->
        <div v-if="operation !== 'create' && canWrite" class="flex justify-end">
          <Button 
            @click="toggleEditMode" 
            variant="outline" 
            size="sm"
          >
            <Edit class="mr-2 h-4 w-4" />
            {{ isEditMode ? t('actions.view') : t('actions.edit') }}
          </Button>
        </div>

        <!-- Form Fields -->
        <div class="space-y-4">
          <FormField 
            :label="t('document.fields.name')" 
            :error="form.errors.name"
            :required="true"
          >
            <Input
              v-model="form.name"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('document.placeholders.name')"
            />
          </FormField>

          <FormField 
            :label="t('document.fields.notes')" 
            :error="form.errors.notes"
          >
            <Textarea
              v-model="form.notes"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('document.placeholders.notes')"
              rows="3"
            />
          </FormField>

          <FormField 
            :label="t('document.fields.defaultRole')" 
            :error="form.errors.default_role"
          >
            <AutocompleteInput
              v-model="form.default_role"
              v-model:display-model-value="roleDisplay"
              endpoint="/role/autocomplete"
              :placeholder="t('document.placeholders.defaultRole')"
              :disabled="!isEditMode && operation !== 'create'"
            />
          </FormField>

          <!-- Event Names (Read-only) -->
          <div v-if="templateClass?.event_names && templateClass.event_names.length > 0">
            <Label>{{ t('document.fields.linkedEvents') }}</Label>
            <div class="mt-2 space-y-1">
              <Badge 
                v-for="event in templateClass.event_names" 
                :key="event.code"
                variant="secondary"
                class="mr-2"
              >
                {{ event.name }}
              </Badge>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter>
        <div class="flex justify-between w-full">
          <Button
            v-if="canWrite && isEditMode && operation !== 'create'"
            @click="confirmDelete"
            variant="destructive"
          >
            <Trash2 class="mr-2 h-4 w-4" />
            {{ t('actions.delete') }}
          </Button>

          <div class="flex gap-2">
            <Button @click="$emit('update:open', false)" variant="outline">
              {{ t('actions.cancel') }}
            </Button>
            <Button
              v-if="isEditMode || operation === 'create'"
              @click="handleSubmit"
              :disabled="form.processing"
            >
              <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
              {{ operation === 'create' ? t('actions.create') : t('actions.save') }}
            </Button>
          </div>
        </div>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('document.dialog.deleteTitle')"
    :message="t('document.messages.deleteConfirm')"
    :confirm-text="t('actions.delete')"
    :cancel-text="t('actions.cancel')"
    variant="destructive"
    @confirm="deleteClass"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useTranslatedField } from '@/composables/useTranslation'
import {
  Loader2,
  Edit,
  Trash2
} from 'lucide-vue-next'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import DialogSkeleton from '@/components/ui/skeleton/DialogSkeleton.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import FormField from '@/components/ui/form/FormField.vue'
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  open: Boolean,
  operation: {
    type: String,
    default: 'view'
  },
  classId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()
const { translated } = useTranslatedField()
const page = usePage()

const loading = ref(false)
const isEditMode = ref(false)
const showDeleteDialog = ref(false)
const templateClass = ref(null)
const tableComments = ref({})

// Check permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

// Form for create/edit operations
const form = useForm({
  name: '',
  notes: '',
  default_role: ''
})

// Display value for role autocomplete
const roleDisplay = ref('')

// Dialog title based on operation and state
const dialogTitle = computed(() => {
  if (props.operation === 'create') {
    return t('document.dialog.createTitle')
  }
  if (loading.value) {
    return t('common.loading')
  }
  return templateClass.value?.name || t('document.dialog.viewTitle')
})

// Watch for classId changes and fetch data
watch(() => props.classId, (newId) => {
  if (newId && props.open) {
    fetchClassData(newId)
  }
}, { immediate: true })

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (props.operation === 'create') {
      resetClass()
      isEditMode.value = true
    } else if (props.classId) {
      fetchClassData(props.classId)
    }
  } else {
    isEditMode.value = false
  }
})

// Fetch class data from server
async function fetchClassData(classId) {
  loading.value = true
  try {
    const response = await fetch(`/document/${classId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      templateClass.value = data.templateClass
      tableComments.value = data.tableComments || {}
      populateForm()
    }
  } catch (error) {
    console.error('Failed to load template class:', error)
  } finally {
    loading.value = false
  }
}

// Reset class for create operation
function resetClass() {
  form.reset()
  templateClass.value = null
  roleDisplay.value = ''
}

// Populate form with class data
function populateForm() {
  if (templateClass.value) {
    form.name = templateClass.value.name || ''
    form.notes = templateClass.value.notes || ''
    form.default_role = templateClass.value.default_role || ''
    roleDisplay.value = translated(templateClass.value.role?.name) || ''
  }
}

// Toggle between view and edit modes
function toggleEditMode() {
  isEditMode.value = !isEditMode.value
  if (isEditMode.value) {
    populateForm()
  }
}

// Handle form submission
function handleSubmit() {
  if (props.operation === 'create') {
    form.post('/document', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    form.put(`/document/${props.classId}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  }
}

// Show delete confirmation
function confirmDelete() {
  showDeleteDialog.value = true
}

// Delete the class
function deleteClass() {
  router.delete(`/document/${props.classId}`, {
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    },
    onError: (error) => {
      console.error('Failed to delete class:', error)
    }
  })
}
</script>