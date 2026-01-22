<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? $t('Create Event Name') : (eventName?.code ? eventName.code : $t('Event Name Details')) }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditMode ? $t('Edit event name details') : $t('View event name details') }}
        </DialogDescription>
      </DialogHeader>
      
      <DialogSkeleton v-if="loading" :fields="6" />
      
      <div v-else-if="eventName || operation === 'create'" class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <div class="flex items-center gap-2">
            <Badge v-if="eventName?.is_task" variant="default" class="text-xs">
              {{ $t('Task') }}
            </Badge>
            <Badge v-if="eventName?.status_event" variant="secondary" class="text-xs">
              {{ $t('Status') }}
            </Badge>
            <Badge v-if="eventName?.killer" variant="destructive" class="text-xs">
              {{ $t('Killer') }}
            </Badge>
          </div>
          <div class="flex items-center gap-2">
            <Button 
              @click="toggleEditMode" 
              variant="outline" 
              size="sm"
              v-if="canEdit && operation !== 'create'"
            >
              <Edit class="mr-2 h-4 w-4" />
              {{ isEditMode ? $t('View Mode') : $t('Edit Mode') }}
            </Button>
            <Button 
              v-if="canEdit && isEditMode && operation !== 'create'" 
              @click="confirmDelete" 
              variant="destructive"
              size="sm"
            >
              <Trash2 class="mr-2 h-4 w-4" />
              {{ $t('Delete') }}
            </Button>
          </div>
        </div>

        <!-- Tabbed Content -->
        <Tabs default-value="main" class="w-full">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="main">{{ $t('Main') }}</TabsTrigger>
            <TabsTrigger value="properties">{{ $t('Properties') }}</TabsTrigger>
            <TabsTrigger value="templates">{{ $t('Templates') }}</TabsTrigger>
          </TabsList>

          <!-- Main Tab -->
          <TabsContent value="main" class="space-y-4 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="code" class="mb-2">{{ $t('Code') }} *</Label>
                <Input
                  id="code"
                  v-model="form.code"
                  :placeholder="$t('Enter code (max 5 chars)')"
                  maxlength="5"
                  :disabled="(!isEditMode && operation !== 'create') || form.processing"
                  :class="{ 'border-destructive': form.errors.code }"
                />
                <p v-if="form.errors.code" class="text-sm text-destructive mt-1">
                  {{ form.errors.code }}
                </p>
              </div>
              
              <div>
                <Label for="name" class="mb-2">{{ $t('Name') }} *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  :placeholder="$t('Enter name')"
                  maxlength="45"
                  :disabled="(!isEditMode && operation !== 'create') || form.processing"
                  :class="{ 'border-destructive': form.errors.name }"
                />
                <p v-if="form.errors.name" class="text-sm text-destructive mt-1">
                  {{ form.errors.name }}
                </p>
              </div>
            </div>

            <div>
              <Label for="notes" class="mb-2">{{ $t('Notes') }}</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                :placeholder="$t('Enter notes (optional)')"
                rows="3"
                maxlength="160"
                :disabled="(!isEditMode && operation !== 'create') || form.processing"
                :class="{ 'border-destructive': form.errors.notes }"
              />
              <p v-if="form.errors.notes" class="text-sm text-destructive mt-1">
                {{ form.errors.notes }}
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="country" class="mb-2">{{ $t('Country') }}</Label>
                <AutocompleteInput
                  id="country"
                  v-model="form.country"
                  v-model:display-model-value="countryDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="$t('Select country')"
                  :disabled="(!isEditMode && operation !== 'create') || form.processing"
                />
              </div>
              
              <div>
                <Label for="category" class="mb-2">{{ $t('Category') }}</Label>
                <AutocompleteInput
                  id="category"
                  v-model="form.category"
                  v-model:display-model-value="categoryDisplay"
                  endpoint="/category/autocomplete"
                  :placeholder="$t('Select category')"
                  :disabled="(!isEditMode && operation !== 'create') || form.processing"
                />
              </div>
            </div>

            <div>
              <Label for="default_responsible" class="mb-2">{{ $t('Default Responsible') }}</Label>
              <AutocompleteInput
                id="default_responsible"
                v-model="form.default_responsible"
                v-model:display-model-value="responsibleDisplay"
                endpoint="/user/autocomplete"
                :placeholder="$t('Select responsible user')"
                :disabled="(!isEditMode && operation !== 'create') || form.processing"
              />
            </div>
          </TabsContent>

          <!-- Properties Tab -->
          <TabsContent value="properties" class="space-y-4 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="is_task"
                  v-model="form.is_task"
                  :disabled="form.processing"
                />
                <Badge v-else-if="eventName?.is_task" variant="default" class="text-xs">
                  ✓ {{ $t('Is Task Event') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ $t('Is Task Event') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="is_task" class="font-normal">
                  {{ $t('This is a task event') }}
                </Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="status_event"
                  v-model="form.status_event"
                  :disabled="form.processing"
                />
                <Badge v-else-if="eventName?.status_event" variant="default" class="text-xs">
                  ✓ {{ $t('Is Status Event') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ $t('Is Status Event') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="status_event" class="font-normal">
                  {{ $t('This is a status event') }}
                </Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="killer"
                  v-model="form.killer"
                  :disabled="form.processing"
                />
                <Badge v-else-if="eventName?.killer" variant="destructive" class="text-xs">
                  ✓ {{ $t('Is Killer Event') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ $t('Is Killer Event') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="killer" class="font-normal">
                  {{ $t('This is a killer event') }}
                </Label>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="use_matter_resp"
                  v-model="form.use_matter_resp"
                  :disabled="form.processing"
                />
                <Badge v-else-if="eventName?.use_matter_resp" variant="secondary" class="text-xs">
                  ✓ {{ $t('Use Matter Responsible') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ $t('Use Matter Responsible') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="use_matter_resp" class="font-normal">
                  {{ $t('Use matter responsible instead of default') }}
                </Label>
              </div>
            </div>
          </TabsContent>

          <!-- Templates Tab -->
          <TabsContent value="templates" class="mt-4">
            <TemplateLinks
              v-if="eventName"
              :event-name="eventName"
              :template-links="templateLinks"
              :can-edit="canEdit && isEditMode"
              @updated="handleTemplateUpdate"
            />
            <div v-else class="text-center py-8 text-muted-foreground">
              {{ $t('Template management is available after creating the event name') }}
            </div>
          </TabsContent>
        </Tabs>
      </div>
        
      <DialogFooter>
        <Button @click="$emit('update:open', false)" variant="outline" :disabled="form.processing">
          {{ $t('Cancel') }}
        </Button>
        <Button
          v-if="isEditMode || operation === 'create'"
          @click="handleSubmit"
          :disabled="form.processing || !canSubmit"
        >
          <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ operation === 'create' ? $t('Create') : $t('Save') }}
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="$t('Delete Event Name')"
    :message="$t('Are you sure you want to delete event name {code}? This action cannot be undone.', { code: eventName?.code })"
    :confirm-text="$t('Delete')"
    :cancel-text="$t('Cancel')"
    :type="'destructive'"
    @confirm="deleteEventName"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Loader2, Edit, Trash2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import TemplateLinks from '@/components/eventname/TemplateLinks.vue'
import DialogSkeleton from '@/components/ui/skeleton/DialogSkeleton.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  eventNameCode: {
    type: [String, null],
    default: null
  },
  operation: {
    type: String,
    default: 'view',
    validator: (value) => ['view', 'edit', 'create'].includes(value)
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()
const { translated } = useTranslatedField()
const page = usePage()
const loading = ref(false)
const eventName = ref(null)
const templateLinks = ref([])
const isEditMode = ref(false)
const showDeleteDialog = ref(false)

// Form
const form = useForm({
  code: '',
  name: '',
  notes: '',
  is_task: false,
  status_event: false,
  killer: false,
  use_matter_resp: false,
  country: '',
  category: '',
  default_responsible: ''
})

// Display values for autocomplete fields
const countryDisplay = ref('')
const categoryDisplay = ref('')
const responsibleDisplay = ref('')

// Computed
const canEdit = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

const canSubmit = computed(() => {
  return form.code && form.name && !form.processing
})

// Methods
const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value
}

const handleSubmit = () => {
  if (props.operation === 'create') {
    form.post('/eventname', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
        resetForm()
      }
    })
  } else {
    form.put(`/eventname/${props.eventNameCode}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
        // Refresh the eventName data
        fetchEventNameData(props.eventNameCode)
      }
    })
  }
}

const confirmDelete = () => {
  showDeleteDialog.value = true
}

const deleteEventName = async () => {
  try {
    await router.delete(`/eventname/${props.eventNameCode}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } catch (error) {
    console.error('Failed to delete event name:', error)
  }
}

const handleTemplateUpdate = () => {
  // Refresh template links
  fetchEventNameData(props.eventNameCode)
}

const resetForm = () => {
  form.reset()
  countryDisplay.value = ''
  categoryDisplay.value = ''
  responsibleDisplay.value = ''
}

const populateForm = () => {
  if (!eventName.value) return
  
  form.code = eventName.value.code || ''
  form.name = translated(eventName.value.name) || ''
  form.notes = eventName.value.notes || ''
  form.is_task = Boolean(eventName.value.is_task)
  form.status_event = Boolean(eventName.value.status_event)
  form.killer = Boolean(eventName.value.killer)
  form.use_matter_resp = Boolean(eventName.value.use_matter_resp)
  form.country = eventName.value.country || ''
  form.category = eventName.value.category || ''
  form.default_responsible = eventName.value.default_responsible || ''
  
  // Set display values for autocomplete fields
  countryDisplay.value = eventName.value.countryInfo?.name || ''
  categoryDisplay.value = translated(eventName.value.categoryInfo?.category) || ''
  responsibleDisplay.value = eventName.value.default_responsibleInfo?.name || ''
}

const fetchEventNameData = async (code) => {
  if (!code) return
  
  loading.value = true
  try {
    const response = await fetch(`/eventname/${code}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      eventName.value = data.eventName
      templateLinks.value = data.templateLinks || []
      populateForm()
    }
  } catch (error) {
    console.error('Failed to load event name:', error)
  } finally {
    loading.value = false
  }
}

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    isEditMode.value = props.operation === 'create' || props.operation === 'edit'
    if (props.eventNameCode && props.operation !== 'create') {
      fetchEventNameData(props.eventNameCode)
    } else if (props.operation === 'create') {
      resetForm()
    }
  } else if (!isOpen) {
    // Reset state when modal closes
    eventName.value = null
    templateLinks.value = []
    isEditMode.value = false
  }
})
</script>