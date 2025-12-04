<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-2xl">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? t('categories.dialog.createTitle') : (category?.code || t('categories.dialog.viewTitle')) }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditMode ? t('categories.dialog.editDescription') : t('categories.dialog.viewDescription') }}
        </DialogDescription>
      </DialogHeader>
      
      <DialogSkeleton v-if="loading" :fields="4" />
      
      <div v-else-if="category || operation === 'create'" class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <Badge variant="secondary">
            {{ t('categories.badge') }}
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
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="mb-2">{{ t('categories.fields.code') }} *</Label>
              <Input
                v-model="form.code"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('categories.placeholders.code')"
                maxlength="5"
                required
              />
              <p v-if="form.errors.code" class="text-sm text-destructive mt-1">
                {{ form.errors.code }}
              </p>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('categories.fields.category') }} *</Label>
              <Input
                v-model="form.category"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('categories.placeholders.category')"
                maxlength="45"
                required
              />
              <p v-if="form.errors.category" class="text-sm text-destructive mt-1">
                {{ form.errors.category }}
              </p>
            </div>
          </div>

          <div>
            <Label class="mb-2">{{ t('categories.fields.displayWith') }} *</Label>
            <AutocompleteInput
              v-model="form.display_with"
              v-model:display-model-value="displayWithDisplay"
              endpoint="/category/autocomplete"
              :placeholder="t('categories.placeholders.displayWith')"
              :disabled="!isEditMode && operation !== 'create'"
              :allow-free-text="true"
              required
            />
            <p class="text-xs text-muted-foreground mt-1">
              {{ operation === 'create' ? t('categories.help.displayWithCreate') : t('categories.help.displayWith') }}
            </p>
            <p v-if="form.errors.display_with" class="text-sm text-destructive mt-1">
              {{ form.errors.display_with }}
            </p>
          </div>

          <!-- Metadata -->
          <div v-if="category && operation !== 'create'" class="pt-4 border-t space-y-2 text-sm text-muted-foreground">
            <div v-if="category.creator">
              {{ t('common.createdBy') }}: {{ category.creator }}
              <span v-if="category.created_at"> {{ t('common.on') }} {{ formatDate(category.created_at) }}</span>
            </div>
            <div v-if="category.updater">
              {{ t('common.updatedBy') }}: {{ category.updater }}
              <span v-if="category.updated_at"> {{ t('common.on') }} {{ formatDate(category.updated_at) }}</span>
            </div>
          </div>
        </form>
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
          
          <div v-else></div>
          
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
    :title="t('categories.dialog.deleteTitle')"
    :description="t('categories.dialog.deleteDescription')"
    :confirm-text="t('actions.delete')"
    variant="destructive"
    @confirm="handleDelete"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Edit, Trash2, Loader2 } from 'lucide-vue-next'
import axios from 'axios'
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
import { Badge } from '@/components/ui/badge'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import DialogSkeleton from '@/components/ui/skeleton/DialogSkeleton.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import { format } from 'date-fns'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  categoryId: {
    type: String,
    default: null
  },
  operation: {
    type: String,
    default: 'view'
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()
const page = usePage()
const { translated } = useTranslatedField()

// States
const loading = ref(false)
const category = ref(null)
const isEditMode = ref(false)
const showDeleteDialog = ref(false)
const displayWithDisplay = ref('')

// Permissions
const canWrite = computed(() => page.props.auth.user?.can_write)

// Form
const form = useForm({
  code: '',
  category: '',
  display_with: '',
})

// Watch for dialog open/close and category changes
watch(() => props.open, async (newValue) => {
  if (newValue) {
    if (props.operation === 'create') {
      isEditMode.value = true
      form.reset()
      displayWithDisplay.value = ''
    } else if (props.categoryId) {
      await fetchCategory()
    }
  } else {
    // Reset state when closing
    category.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

watch(() => props.categoryId, async (newId) => {
  if (newId && props.open && props.operation !== 'create') {
    await fetchCategory()
  }
})

// Methods
async function fetchCategory() {
  loading.value = true
  try {
    const response = await axios.get(`/category/${props.categoryId}`)
    category.value = response.data.category
    populateForm()
    isEditMode.value = false
  } catch (error) {
    console.error('Error fetching category:', error)
  } finally {
    loading.value = false
  }
}

function populateForm() {
  if (category.value) {
    form.code = category.value.code || ''
    form.category = translated(category.value.category) || ''
    form.display_with = category.value.display_with || ''
    displayWithDisplay.value = category.value.display_with_info ? 
      translated(category.value.display_with_info.category) : ''
  }
}

function toggleEditMode() {
  isEditMode.value = !isEditMode.value
  if (isEditMode.value) {
    populateForm()
  }
}

function handleSubmit() {
  if (props.operation === 'create') {
    form.post('/category', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    form.put(`/category/${category.value.code}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  }
}

function confirmDelete() {
  showDeleteDialog.value = true
}

function handleDelete() {
  form.delete(`/category/${category.value.code}`, {
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    }
  })
}

function formatDate(date) {
  return format(new Date(date), 'MMM d, yyyy')
}
</script>