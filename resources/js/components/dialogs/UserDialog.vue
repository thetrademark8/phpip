<template>
  <Dialog v-model:open="dialogOpen" :max-width="maxWidth">
    <DialogContent>
      <DialogHeader>
        <div class="flex items-center justify-between">
          <div>
            <DialogTitle>
              <span v-if="operation === 'create'">{{ t('Create User') }}</span>
              <span v-else-if="isEditMode">{{ t('users.dialog.editTitle') }}</span>
              <span v-else>{{ t('User data') }}</span>
            </DialogTitle>
            <DialogDescription>
              <span v-if="operation === 'create'">{{ t('users.dialog.createDescription') }}</span>
              <span v-else-if="isEditMode">{{ t('users.dialog.editDescription') }}</span>
              <span v-else>{{ t('users.dialog.viewDescription') }}</span>
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
            {{ t('User') }}
          </Badge>
          <div v-if="user && user.warn" class="flex items-center gap-2 text-destructive">
            <AlertTriangle class="h-4 w-4" />
            <span class="text-sm">{{ t('users.warning') }}</span>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- User Info Section -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium">{{ t('User Info') }}</h3>
            
            <!-- Name -->
            <div class="space-y-2">
              <Label for="user-name" class="mb-2">{{ t('Name') }} *</Label>
              <Input
                id="user-name"
                v-model="form.name"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('NAME Firstname')"
                maxlength="100"
                required
              />
              <p v-if="form.errors.name" class="text-sm text-destructive">
                {{ form.errors.name }}
              </p>
            </div>
            
            <!-- Email -->
            <div class="space-y-2">
              <Label for="user-email" class="mb-2">{{ t('Email') }} *</Label>
              <Input
                id="user-email"
                v-model="form.email"
                type="email"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Email')"
                required
              />
              <p v-if="form.errors.email" class="text-sm text-destructive">
                {{ form.errors.email }}
              </p>
            </div>
            
            <!-- Phone -->
            <div class="space-y-2">
              <Label for="user-phone" class="mb-2">{{ t('Phone') }}</Label>
              <Input
                id="user-phone"
                v-model="form.phone"
                type="tel"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Phone')"
              />
            </div>
            
            <!-- Role -->
            <div class="space-y-2">
              <Label for="user-role" class="mb-2">{{ t('Role') }} *</Label>
              <AutocompleteInput
                id="user-role"
                v-model="form.default_role"
                v-model:display-model-value="roleDisplay"
                endpoint="/role/autocomplete"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Role')"
                required
              />
              <p v-if="form.errors.default_role" class="text-sm text-destructive">
                {{ form.errors.default_role }}
              </p>
            </div>
            
            <!-- Company -->
            <div class="space-y-2">
              <Label for="user-company" class="mb-2">{{ t('Company') }}</Label>
              <AutocompleteInput
                id="user-company"
                v-model="form.company_id"
                v-model:display-model-value="companyDisplay"
                endpoint="/actor/autocomplete"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Company')"
              />
            </div>
            
            <!-- Language -->
            <div class="space-y-2">
              <Label for="user-language" class="mb-2">{{ t('Language') }} *</Label>
              <Select v-model="form.language" :disabled="!isEditMode && operation !== 'create'">
                <SelectTrigger id="user-language">
                  <SelectValue :placeholder="t('Language')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="en_GB">English (British)</SelectItem>
                  <SelectItem value="en_US">English (American)</SelectItem>
                  <SelectItem value="fr">Français</SelectItem>
                  <SelectItem value="de">Deutsch</SelectItem>
                  <SelectItem value="es">Español</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <!-- Notes -->
            <div class="space-y-2">
              <Label for="user-notes" class="mb-2">{{ t('Notes') }}</Label>
              <Textarea
                id="user-notes"
                v-model="form.notes"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Notes')"
                rows="3"
              />
            </div>
          </div>

          <!-- Credentials Section -->
          <div class="space-y-4">
            <h3 class="text-lg font-medium">{{ t('Credentials') }}</h3>
            
            <!-- Username (Login) -->
            <div class="space-y-2">
              <Label for="user-login" class="mb-2">{{ t('User name') }} *</Label>
              <Input
                id="user-login"
                v-model="form.login"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('User name')"
                required
              />
              <p v-if="form.errors.login" class="text-sm text-destructive">
                {{ form.errors.login }}
              </p>
            </div>
            
            <!-- Password -->
            <div class="space-y-2">
              <Label for="user-password" class="mb-2">{{ t('Password') }} {{ operation !== 'create' ? '' : '*' }}</Label>
              <Input
                id="user-password"
                v-model="form.password"
                type="password"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="operation === 'create' ? t('Password') : t('Leave empty to keep password')"
                :required="operation === 'create'"
              />
              <p v-if="form.errors.password" class="text-sm text-destructive">
                {{ form.errors.password }}
              </p>
              <p v-if="(isEditMode || operation === 'create') && form.password" class="text-xs text-muted-foreground">
                {{ t('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.') }}
              </p>
            </div>
            
            <!-- Confirm Password -->
            <div v-if="form.password || operation === 'create'" class="space-y-2">
              <Label for="user-password-confirmation" class="mb-2">{{ t('Confirm Password') }} {{ operation !== 'create' ? '' : '*' }}</Label>
              <Input
                id="user-password-confirmation"
                v-model="form.password_confirmation"
                type="password"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('Confirm password')"
                :required="operation === 'create' || form.password"
              />
              <p v-if="form.errors.password_confirmation" class="text-sm text-destructive">
                {{ form.errors.password_confirmation }}
              </p>
            </div>
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
    :title="t('users.dialog.deleteTitle')"
    :message="t('users.dialog.deleteConfirmation')"
    :confirm-text="t('actions.delete')"
    confirm-variant="destructive"
    @confirm="handleDelete"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Edit, Trash2, AlertTriangle } from 'lucide-vue-next'
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
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Badge } from '@/Components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: Boolean,
  userId: [Number, String, null],
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
const user = ref(null)
const isEditMode = ref(false)
const operation = ref(props.operation)

// Display values for autocompletes
const roleDisplay = ref('')
const companyDisplay = ref('')

// Form data using Inertia's useForm
const form = useForm({
  name: '',
  email: '',
  phone: '',
  login: '',
  password: '',
  password_confirmation: '',
  default_role: '',
  company_id: null,
  language: 'en_GB',
  notes: '',
})

// Dialog width
const maxWidth = computed(() => {
  return 'max-w-3xl'
})

// Watch for dialog open
watch(() => props.open, async (newValue) => {
  if (newValue) {
    operation.value = props.operation
    if (props.operation === 'create') {
      isEditMode.value = true
      resetForm()
    } else if (props.userId) {
      await fetchUser()
      isEditMode.value = false
    }
  } else {
    // Reset state when closing
    user.value = null
    isEditMode.value = false
    form.reset()
    form.clearErrors()
  }
})

// Fetch user data
async function fetchUser() {
  loading.value = true
  try {
    const response = await fetch(route('user.show', props.userId), {
      headers: {
        'Accept': 'application/json',
      }
    })
    
    if (!response.ok) throw new Error('Failed to fetch user')
    
    user.value = await response.json()
    populateForm()
  } catch (error) {
    console.error('Failed to fetch user:', error)
  } finally {
    loading.value = false
  }
}

// Populate form with user data
function populateForm() {
  if (user.value) {
    form.name = user.value.name || ''
    form.email = user.value.email || ''
    form.phone = user.value.phone || ''
    form.login = user.value.login || ''
    form.default_role = user.value.default_role || ''
    form.company_id = user.value.company_id
    form.language = user.value.language || 'en_GB'
    form.notes = user.value.notes || ''
    
    // Clear password fields for security
    form.password = ''
    form.password_confirmation = ''
    
    // Set display values
    roleDisplay.value = translated(user.value.role_info?.name) || user.value.default_role || ''
    companyDisplay.value = user.value.company?.name || ''
  }
}

// Reset form
function resetForm() {
  form.reset()
  form.language = 'en_GB' // Set default language
  user.value = null
  roleDisplay.value = ''
  companyDisplay.value = ''
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
    createUser()
  } else if (isEditMode.value) {
    updateUser()
  }
}

// Create user
function createUser() {
  form.post(route('user.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      dialogOpen.value = false
      emit('success')
    }
  })
}

// Update user
function updateUser() {
  form.put(route('user.update', props.userId), {
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
  router.delete(route('user.destroy', props.userId), {
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