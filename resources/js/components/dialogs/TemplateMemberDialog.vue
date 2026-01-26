<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>
          {{ dialogTitle }}
        </DialogTitle>
        <DialogDescription v-if="operation !== 'view'">
          {{ operation === 'create' ? t('templateMember.dialog.createDescription') : t('templateMember.dialog.editDescription') }}
        </DialogDescription>
      </DialogHeader>

      <DialogSkeleton v-if="loading" :fields="8" />

      <div v-else-if="templateMember || operation === 'create'" class="space-y-4">
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
          <!-- Template Class -->
          <FormField 
            :label="t('templateMember.fields.class')" 
            :error="form.errors.class_id"
            :required="true"
          >
            <AutocompleteInput
              v-model="form.class_id"
              v-model:display-model-value="classDisplay"
              endpoint="/template-class/autocomplete"
              :placeholder="t('templateMember.placeholders.class')"
              :disabled="!isEditMode && operation !== 'create'"
            />
          </FormField>

          <!-- Language -->
          <FormField 
            :label="t('templateMember.fields.language')" 
            :error="form.errors.language"
            :required="true"
          >
            <Select 
              v-model="form.language" 
              :disabled="!isEditMode && operation !== 'create'"
            >
              <SelectTrigger>
                <SelectValue :placeholder="t('templateMember.placeholders.language')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="en">English</SelectItem>
                <SelectItem value="fr">Français</SelectItem>
                <SelectItem value="de">Deutsch</SelectItem>
              </SelectContent>
            </Select>
          </FormField>

          <!-- Style -->
          <FormField 
            :label="t('templateMember.fields.style')" 
            :error="form.errors.style"
          >
            <Input
              v-model="form.style"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('templateMember.placeholders.style')"
            />
          </FormField>

          <!-- Category -->
          <FormField 
            :label="t('templateMember.fields.category')" 
            :error="form.errors.category"
          >
            <Input
              v-model="form.category"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('templateMember.placeholders.category')"
            />
          </FormField>

          <!-- Format -->
          <FormField 
            :label="t('templateMember.fields.format')" 
            :error="form.errors.format"
          >
            <Select 
              v-model="form.format" 
              :disabled="!isEditMode && operation !== 'create'"
            >
              <SelectTrigger>
                <SelectValue :placeholder="t('templateMember.placeholders.format')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="TEXT">TEXT</SelectItem>
                <SelectItem value="HTML">HTML</SelectItem>
              </SelectContent>
            </Select>
          </FormField>

          <!-- Summary -->
          <FormField 
            :label="t('templateMember.fields.summary')" 
            :error="form.errors.summary"
          >
            <Input
              v-model="form.summary"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('templateMember.placeholders.summary')"
            />
          </FormField>

          <!-- Subject -->
          <FormField 
            :label="t('templateMember.fields.subject')" 
            :error="form.errors.subject"
          >
            <Textarea
              v-model="form.subject"
              :disabled="!isEditMode && operation !== 'create'"
              :placeholder="t('templateMember.placeholders.subject')"
              rows="3"
            />
          </FormField>

          <!-- Body with Placeholders -->
          <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <!-- Placeholders Panel -->
            <div v-if="isEditMode || operation === 'create'" class="lg:col-span-1">
              <FormField :label="t('email.placeholders')">
                <PlaceholderPanel
                  :placeholders="placeholders"
                  @insert="insertPlaceholder"
                />
              </FormField>
            </div>

            <!-- Body Editor -->
            <div :class="isEditMode || operation === 'create' ? 'lg:col-span-3' : 'lg:col-span-4'">
              <FormField
                :label="t('templateMember.fields.body')"
                :error="form.errors.body"
              >
                <TipTapEditor
                  v-if="isEditMode || operation === 'create'"
                  ref="editorRef"
                  v-model="form.body"
                  :placeholder="t('templateMember.placeholders.body')"
                />
                <div
                  v-else
                  class="prose prose-sm max-w-none p-4 bg-muted/50 rounded-md min-h-[300px] border"
                  v-html="form.body || t('templateMember.placeholders.body')"
                />
              </FormField>
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
    :title="t('templateMember.dialog.deleteTitle')"
    :message="t('templateMember.messages.deleteConfirm')"
    :confirm-text="t('actions.delete')"
    :cancel-text="t('actions.cancel')"
    variant="destructive"
    @confirm="deleteMember"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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
import TipTapEditor from '@/components/ui/TipTapEditor.vue'
import PlaceholderPanel from '@/components/email/PlaceholderPanel.vue'
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import axios from 'axios'
import {Textarea} from "@/components/ui/textarea/index.js";

const props = defineProps({
  open: Boolean,
  operation: {
    type: String,
    default: 'view'
  },
  memberId: {
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
const templateMember = ref(null)
const editorRef = ref(null)
const placeholders = ref({})

// Check permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

// Form for create/edit operations
const form = useForm({
  class_id: '',
  language: '',
  style: '',
  category: '',
  format: '',
  summary: '',
  subject: '',
  body: ''
})

// Display value for class autocomplete
const classDisplay = ref('')

// Dialog title based on operation and state
const dialogTitle = computed(() => {
  if (props.operation === 'create') {
    return t('templateMember.dialog.createTitle')
  }
  if (loading.value) {
    return t('common.loading')
  }
  return templateMember.value?.summary || t('templateMember.dialog.viewTitle')
})

// Watch for memberId changes and fetch data
watch(() => props.memberId, (newId) => {
  if (newId && props.open) {
    fetchMemberData(newId)
  }
}, { immediate: true })

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (props.operation === 'create') {
      resetMember()
      isEditMode.value = true
    } else if (props.memberId) {
      fetchMemberData(props.memberId)
    }
  } else {
    isEditMode.value = false
  }
})

// Fetch member data from server
async function fetchMemberData(memberId) {
  loading.value = true
  try {
    const response = await fetch(`/template-member/${memberId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      templateMember.value = data.templateMember
      populateForm()
    }
  } catch (error) {
    console.error('Failed to load template member:', error)
  } finally {
    loading.value = false
  }
}

// Reset member for create operation
function resetMember() {
  form.reset()
  templateMember.value = null
  classDisplay.value = ''
}

// Populate form with member data
function populateForm() {
  if (templateMember.value) {
    form.class_id = templateMember.value.class_id || ''
    form.language = templateMember.value.language || ''
    form.style = templateMember.value.style || ''
    form.category = templateMember.value.category || ''
    form.format = templateMember.value.format || ''
    form.summary = templateMember.value.summary || ''
    form.subject = templateMember.value.subject || ''
    form.body = templateMember.value.body || ''
    classDisplay.value = translated(templateMember.value.template_class?.name) || ''
  }
}

// Fetch available placeholders
async function fetchPlaceholders() {
  try {
    const response = await axios.get('/template-member/placeholders')
    placeholders.value = response.data
  } catch (error) {
    console.error('Failed to load placeholders:', error)
  }
}

// Insert placeholder at cursor position in editor
function insertPlaceholder(placeholder) {
  if (editorRef.value) {
    editorRef.value.insertText(placeholder)
  }
}

// Fetch placeholders on mount
onMounted(() => {
  fetchPlaceholders()
})

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
    form.post('/template-member', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    form.put(`/template-member/${props.memberId}`, {
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

// Delete the member
function deleteMember() {
  router.delete(`/template-member/${props.memberId}`, {
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    },
    onError: (error) => {
      console.error('Failed to delete template member:', error)
    }
  })
}
</script>