<template>
  <Dialog v-model:open="dialogOpen" :max-width="maxWidth">
    <DialogScrollContent>
      <DialogHeader>
        <DialogTitle>
          <span v-if="operation === 'create'">{{ t('roles.dialog.createTitle') }}</span>
          <span v-else-if="isEditMode">{{ t('roles.dialog.editTitle') }}</span>
          <span v-else>{{ t('roles.dialog.viewTitle') }}</span>
        </DialogTitle>
        <DialogDescription>
          <span v-if="operation === 'create'">{{ t('roles.dialog.createDescription') }}</span>
          <span v-else-if="isEditMode">{{ t('roles.dialog.editDescription') }}</span>
          <span v-else>{{ t('roles.dialog.viewDescription') }}</span>
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
            {{ t('roles.badge') }}
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
          <Label for="role-code" class="mb-2">{{ t('roles.fields.code') }}</Label>
          <Input
            id="role-code"
            v-model="form.code"
            :placeholder="t('roles.placeholders.code')"
            maxlength="5"
            class="uppercase"
            required
          />
          <p class="text-sm text-muted-foreground">{{ t('roles.hints.code') }}</p>
        </div>
        
        <!-- Name -->
        <div class="space-y-2">
          <Label for="role-name" class="mb-2">{{ t('roles.fields.name') }}</Label>
          <Input
            id="role-name"
            v-model="form.name"
            :disabled="!isEditMode && operation !== 'create'"
            :placeholder="t('roles.placeholders.name')"
            required
          />
        </div>
        
        <!-- Display Order -->
        <div class="space-y-2">
          <Label for="role-display-order" class="mb-2">{{ t('roles.fields.displayOrder') }}</Label>
          <Input
            id="role-display-order"
            v-model.number="form.display_order"
            type="number"
            :disabled="!isEditMode && operation !== 'create'"
            :placeholder="t('roles.placeholders.displayOrder')"
          />
          <p class="text-sm text-muted-foreground">{{ t('roles.hints.displayOrder') }}</p>
        </div>
        
        <!-- Notes -->
        <div class="space-y-2">
          <Label for="role-notes" class="mb-2">{{ t('roles.fields.notes') }}</Label>
          <Textarea
            id="role-notes"
            v-model="form.notes"
            :disabled="!isEditMode && operation !== 'create'"
            :placeholder="t('roles.placeholders.notes')"
            rows="3"
          />
        </div>
        
        <!-- Checkboxes Grid -->
        <div class="space-y-4">
          <Label class="text-base font-semibold">{{ t('roles.sections.displayOptions') }}</Label>
          <div class="grid grid-cols-2 gap-4">
            <!-- Shareable -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="role-shareable"
                v-model="form.shareable"
                :disabled="!isEditMode && operation !== 'create'"
              />
              <Label for="role-shareable" class="cursor-pointer">
                {{ t('roles.fields.shareable') }}
              </Label>
            </div>
            
            <!-- Show Reference -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="role-show-ref"
                v-model="form.show_ref"
                :disabled="!isEditMode && operation !== 'create'"
              />
              <Label for="role-show-ref" class="cursor-pointer">
                {{ t('roles.fields.showRef') }}
              </Label>
            </div>
            
            <!-- Show Company -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="role-show-company"
                v-model="form.show_company"
                :disabled="!isEditMode && operation !== 'create'"
              />
              <Label for="role-show-company" class="cursor-pointer">
                {{ t('roles.fields.showCompany') }}
              </Label>
            </div>
            
            <!-- Show Rate -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="role-show-rate"
                v-model="form.show_rate"
                :disabled="!isEditMode && operation !== 'create'"
              />
              <Label for="role-show-rate" class="cursor-pointer">
                {{ t('roles.fields.showRate') }}
              </Label>
            </div>
            
            <!-- Show Date -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="role-show-date"
                v-model="form.show_date"
                :disabled="!isEditMode && operation !== 'create'"
              />
              <Label for="role-show-date" class="cursor-pointer">
                {{ t('roles.fields.showDate') }}
              </Label>
            </div>
          </div>
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
    </DialogScrollContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="deleteDialogOpen"
    :title="t('roles.dialog.deleteTitle')"
    :description="t('roles.dialog.deleteConfirmation', { code: role?.code })"
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: Boolean,
  roleId: String,
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
const role = ref(null)
const isEditMode = ref(false)
const operation = ref(props.operation)

// Form data using Inertia's useForm
const form = useForm({
  code: '',
  name: '',
  display_order: null,
  notes: '',
  shareable: false,
  show_ref: false,
  show_company: false,
  show_rate: false,
  show_date: false,
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
    } else if (props.roleId) {
      await fetchRole()
      isEditMode.value = false
    }
  } else {
    // Reset state when closing
    role.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

// Fetch role data
async function fetchRole() {
  loading.value = true
  try {
    const response = await fetch(route('role.show', props.roleId), {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Failed to fetch role')
    
    role.value = await response.json()
    populateForm()
  } catch (error) {
    console.error('Failed to fetch role:', error)
  } finally {
    loading.value = false
  }
}

// Populate form with role data
function populateForm() {
  if (role.value) {
    form.code = role.value.code
    form.name = translated(role.value.name) || ''
    form.display_order = role.value.display_order
    form.notes = role.value.notes || ''
    form.shareable = role.value.shareable || false
    form.show_ref = role.value.show_ref || false
    form.show_company = role.value.show_company || false
    form.show_rate = role.value.show_rate || false
    form.show_date = role.value.show_date || false
  }
}

// Reset form
function resetForm() {
  form.reset()
  role.value = null
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
    createRole()
  } else if (isEditMode.value) {
    updateRole()
  }
}

// Create role
function createRole() {
  form.post(route('role.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Update role
function updateRole() {
  form.put(route('role.update', props.roleId), {
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
  router.delete(route('role.destroy', props.roleId), {
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