<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? t('rules.dialog.createTitle') : (rule?.task_info?.name ? translated(rule.task_info.name) : t('rules.dialog.viewTitle')) }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditMode ? t('rules.dialog.editDescription') : t('rules.dialog.viewDescription') }}
        </DialogDescription>
      </DialogHeader>
      
      <DialogSkeleton v-if="loading" :fields="6" />
      
      <div v-else-if="rule || operation === 'create'" class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <div class="flex items-center gap-2">
            <Badge variant="secondary">
              {{ rule?.category ? translated(rule.category.category) : t('rules.fields.category') }}
            </Badge>
            <Badge v-if="rule?.active" variant="default">
              {{ t('rules.fields.active') }}
            </Badge>
            <Badge v-else-if="rule && !rule.active" variant="destructive">
              {{ t('rules.fields.inactive') }}
            </Badge>
          </div>
          <Button 
            @click="toggleEditMode" 
            variant="outline" 
            size="sm"
            v-if="canAdmin && operation !== 'create'"
          >
            <Edit class="mr-2 h-4 w-4" />
            {{ isEditMode ? t('actions.viewMode') : t('actions.editMode') }}
          </Button>
          <!-- Debug info -->
          <div v-if="false" class="text-xs text-gray-500">
            canAdmin: {{ canAdmin }}, operation: {{ operation }}, user: {{ page.props.auth?.user?.role }}
          </div>
        </div>

        <!-- Tabbed Content -->
        <Tabs defaultValue="main" class="w-full">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="main">{{ $t('rules.tabs.main') }}</TabsTrigger>
            <TabsTrigger value="conditions">{{ $t('rules.tabs.conditions') }}</TabsTrigger>
            <TabsTrigger value="cost">{{ $t('rules.tabs.cost') }}</TabsTrigger>
          </TabsList>
          
          <TabsContent value="main" class="space-y-4 mt-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('rules.fields.task') }}</Label>
                <AutocompleteInput
                  v-model="form.task"
                  v-model:display-model-value="taskDisplay"
                  endpoint="/event-name/autocomplete/1"
                  :placeholder="t('rules.placeholders.task')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.detail') }}</Label>
                <Input
                  v-model="form.detail"
                  :placeholder="t('rules.placeholders.detail')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.triggerEvent') }}</Label>
                <AutocompleteInput
                  v-model="form.trigger_event"
                  v-model:display-model-value="triggerDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="t('rules.placeholders.triggerEvent')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.category') }}</Label>
                <AutocompleteInput
                  v-model="form.for_category"
                  v-model:display-model-value="categoryDisplay"
                  endpoint="/category/autocomplete"
                  :placeholder="t('rules.placeholders.category')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.country') }}</Label>
                <AutocompleteInput
                  v-model="form.for_country"
                  v-model:display-model-value="countryDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="t('rules.placeholders.country')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.origin') }}</Label>
                <AutocompleteInput
                  v-model="form.for_origin"
                  v-model:display-model-value="originDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="t('rules.placeholders.origin')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.type') }}</Label>
                <AutocompleteInput
                  v-model="form.for_type"
                  v-model:display-model-value="typeDisplay"
                  endpoint="/type/autocomplete"
                  :placeholder="t('rules.placeholders.type')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="clear_task"
                  v-model="form.clear_task"
                />
                <Badge v-else-if="form.clear_task" variant="secondary" class="text-xs">
                  ✓ {{ t('rules.fields.clearTask') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ t('rules.fields.clearTask') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="clear_task" class="font-normal">
                  {{ t('rules.fields.clearTaskDescription') }}
                </Label>
              </div>
              
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="delete_task"
                  v-model="form.delete_task"
                />
                <Badge v-else-if="form.delete_task" variant="secondary" class="text-xs">
                  ✓ {{ t('rules.fields.deleteTask') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ t('rules.fields.deleteTask') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="delete_task" class="font-normal">
                  {{ t('rules.fields.deleteTaskDescription') }}
                </Label>
              </div>
              
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="active"
                  v-model="form.active"
                />
                <Badge v-else-if="form.active" variant="default" class="text-xs">
                  ✓ {{ t('rules.fields.active') }}
                </Badge>
                <Badge v-else variant="destructive" class="text-xs">
                  {{ t('rules.fields.inactive') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="active" class="font-normal">
                  {{ t('rules.fields.activeDescription') }}
                </Label>
              </div>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('rules.fields.notes') }}</Label>
              <Textarea
                v-model="form.notes"
                :placeholder="t('rules.placeholders.notes')"
                rows="3"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>
          </TabsContent>
          
          <TabsContent value="conditions" class="space-y-4 mt-4">
            <div class="grid grid-cols-3 gap-4">
              <div>
                <Label class="mb-2">{{ t('rules.fields.days') }}</Label>
                <Input
                  v-model.number="form.days"
                  type="number"
                  placeholder="0"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.months') }}</Label>
                <Input
                  v-model.number="form.months"
                  type="number"
                  placeholder="0"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.years') }}</Label>
                <Input
                  v-model.number="form.years"
                  type="number"
                  placeholder="0"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="end_of_month"
                  v-model="form.end_of_month"
                />
                <Badge v-else-if="form.end_of_month" variant="secondary" class="text-xs">
                  ✓ {{ t('rules.fields.endOfMonth') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ t('rules.fields.endOfMonth') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="end_of_month" class="font-normal">
                  {{ t('rules.fields.endOfMonthDescription') }}
                </Label>
              </div>
              
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="use_priority"
                  v-model="form.use_priority"
                />
                <Badge v-else-if="form.use_priority" variant="secondary" class="text-xs">
                  ✓ {{ t('rules.fields.usePriority') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ t('rules.fields.usePriority') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="use_priority" class="font-normal">
                  {{ t('rules.fields.usePriorityDescription') }}
                </Label>
              </div>
              
              <div class="flex items-center space-x-2">
                <Checkbox
                  v-if="isEditMode || operation === 'create'"
                  id="recurring"
                  v-model="form.recurring"
                />
                <Badge v-else-if="form.recurring" variant="secondary" class="text-xs">
                  ✓ {{ t('rules.fields.recurring') }}
                </Badge>
                <Badge v-else variant="outline" class="text-xs text-muted-foreground">
                  ✗ {{ t('rules.fields.recurring') }}
                </Badge>
                <Label v-if="isEditMode || operation === 'create'" htmlFor="recurring" class="font-normal">
                  {{ t('rules.fields.recurringDescription') }}
                </Label>
              </div>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
              <div>
                <Label class="mb-2">{{ t('rules.fields.conditionEvent') }}</Label>
                <AutocompleteInput
                  v-model="form.condition_event"
                  v-model:display-model-value="conditionDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="t('rules.placeholders.conditionEvent')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.abortOn') }}</Label>
                <AutocompleteInput
                  v-model="form.abort_on"
                  v-model:display-model-value="abortDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="t('rules.placeholders.abortOn')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.responsible') }}</Label>
                <AutocompleteInput
                  v-model="form.responsible"
                  v-model:display-model-value="responsibleDisplay"
                  endpoint="/user/autocomplete"
                  :placeholder="t('rules.placeholders.responsible')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('rules.fields.useBefore') }}</Label>
                <DatePicker
                  v-model="form.use_before"
                  :placeholder="t('rules.placeholders.useBefore')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.useAfter') }}</Label>
                <DatePicker
                  v-model="form.use_after"
                  :placeholder="t('rules.placeholders.useAfter')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>
          </TabsContent>
          
          <TabsContent value="cost" class="space-y-4 mt-4">
            <div class="grid grid-cols-3 gap-4">
              <div>
                <Label class="mb-2">{{ t('rules.fields.cost') }}</Label>
                <Input
                  v-model.number="form.cost"
                  type="number"
                  step="0.01"
                  :placeholder="t('rules.placeholders.cost')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.fee') }}</Label>
                <Input
                  v-model.number="form.fee"
                  type="number"
                  step="0.01"
                  :placeholder="t('rules.placeholders.fee')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              
              <div>
                <Label class="mb-2">{{ t('rules.fields.currency') }}</Label>
                <Input
                  v-model="form.currency"
                  maxlength="3"
                  :placeholder="t('rules.placeholders.currency')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>
        
      <DialogFooter>
        <Button @click="$emit('update:open', false)" variant="outline">
          {{ t('rules.dialog.cancel') }}
        </Button>
        <Button 
          v-if="canAdmin && isEditMode" 
          @click="confirmDelete" 
          variant="destructive"
        >
          <Trash2 class="mr-2 h-4 w-4" />
          {{ t('rules.dialog.delete') }}
        </Button>
        <Button
          v-if="isEditMode || operation === 'create'"
          @click="handleSubmit"
          :disabled="form.processing"
        >
          <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ operation === 'create' ? t('rules.dialog.create') : t('rules.dialog.save') }}
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('rules.dialog.deleteTitle')"
    :message="t('rules.dialog.deleteDescription', { name: translated(rule?.task_info?.name) || rule?.task })"
    :confirm-text="t('rules.dialog.confirmDelete')"
    :cancel-text="t('rules.dialog.cancel')"
    :type="'destructive'"
    @confirm="deleteRule"
  />
</template>

<script setup>
import { ref, watch, computed } from 'vue'
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
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import DialogSkeleton from '@/components/ui/skeleton/DialogSkeleton.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  ruleId: {
    type: [Number, String, null],
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
const rule = ref(null)
const comments = ref(null)
const operation = ref(props.operation)
const isEditMode = ref(false)
const showDeleteDialog = ref(false)

// Check permissions
const canAdmin = computed(() => page.props.auth.user?.role === 'DBA')

// Form
const form = useForm({
  task: '',
  detail: '',
  trigger_event: '',
  for_category: '',
  for_country: '',
  for_origin: '',
  for_type: '',
  clear_task: false,
  delete_task: false,
  active: true,
  notes: '',
  days: 0,
  months: 0,
  years: 0,
  end_of_month: false,
  use_priority: false,
  recurring: false,
  condition_event: '',
  abort_on: '',
  responsible: '',
  use_before: '',
  use_after: '',
  cost: null,
  fee: null,
  currency: ''
})

// Display values for autocomplete fields
const taskDisplay = ref('')
const triggerDisplay = ref('')
const categoryDisplay = ref('')
const countryDisplay = ref('')
const originDisplay = ref('')
const typeDisplay = ref('')
const conditionDisplay = ref('')
const abortDisplay = ref('')
const responsibleDisplay = ref('')

// Watch for ruleId changes and fetch data
watch(() => props.ruleId, (newId) => {
  if (newId && props.open) {
    fetchRuleData(newId)
  }
}, { immediate: true })

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    operation.value = props.operation
    isEditMode.value = props.operation === 'create' || props.operation === 'edit'
    if (props.ruleId && props.operation !== 'create') {
      fetchRuleData(props.ruleId)
    } else if (props.operation === 'create') {
      fetchComments()
      resetForm()
    }
  } else if (!isOpen) {
    // Reset state when modal closes
    rule.value = null
    comments.value = null
    isEditMode.value = false
  }
})

async function fetchRuleData(id) {
  loading.value = true
  try {
    const response = await fetch(`/rule/${id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      rule.value = data.rule
      comments.value = data.comments
      populateForm()
    }
  } catch (error) {
    console.error('Failed to load rule:', error)
  } finally {
    loading.value = false
  }
}

async function fetchComments() {
  loading.value = true
  try {
    const response = await fetch('/rule/create', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      comments.value = data.comments
    }
  } catch (error) {
    console.error('Failed to load comments:', error)
  } finally {
    loading.value = false
  }
}

function populateForm() {
  if (!rule.value) return
  
  // Populate form with rule data
  form.task = rule.value.task || ''
  form.detail = translated(rule.value.detail) || ''
  form.trigger_event = rule.value.trigger_event || ''
  form.for_category = rule.value.for_category || ''
  form.for_country = rule.value.for_country || ''
  form.for_origin = rule.value.for_origin || ''
  form.for_type = rule.value.for_type || ''
  form.clear_task = Boolean(rule.value.clear_task)
  form.delete_task = Boolean(rule.value.delete_task)
  form.active = Boolean(rule.value.active)
  form.notes = rule.value.notes || ''
  form.days = rule.value.days || 0
  form.months = rule.value.months || 0
  form.years = rule.value.years || 0
  form.end_of_month = Boolean(rule.value.end_of_month)
  form.use_priority = Boolean(rule.value.use_priority)
  form.recurring = Boolean(rule.value.recurring)
  form.condition_event = rule.value.condition_event || ''
  form.abort_on = rule.value.abort_on || ''
  form.responsible = rule.value.responsible || ''
  form.use_before = rule.value.use_before || ''
  form.use_after = rule.value.use_after || ''
  form.cost = rule.value.cost
  form.fee = rule.value.fee
  form.currency = rule.value.currency || ''
  
  // Set display values for autocomplete fields
  taskDisplay.value = translated(rule.value.task_info?.name) || ''
  triggerDisplay.value = translated(rule.value.trigger?.name) || ''
  categoryDisplay.value = translated(rule.value.category?.category) || ''
  countryDisplay.value = translated(rule.value.country?.name) || ''
  originDisplay.value = translated(rule.value.origin?.name) || ''
  typeDisplay.value = translated(rule.value.type?.type) || ''
  conditionDisplay.value = translated(rule.value.condition_event_info?.name) || ''
  abortDisplay.value = translated(rule.value.abort_on_info?.name) || ''
  responsibleDisplay.value = rule.value.responsible_info?.name || ''
}

function resetForm() {
  form.reset()
  taskDisplay.value = ''
  triggerDisplay.value = ''
  categoryDisplay.value = ''
  countryDisplay.value = ''
  originDisplay.value = ''
  typeDisplay.value = ''
  conditionDisplay.value = ''
  abortDisplay.value = ''
  responsibleDisplay.value = ''
}

function toggleEditMode() {
  isEditMode.value = !isEditMode.value
}

function handleSubmit() {
  if (operation.value === 'create') {
    form.post('/rule', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    form.put(`/rule/${props.ruleId}`, {
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

async function deleteRule() {
  try {
    await router.delete(`/rule/${props.ruleId}`, {
      onSuccess: () => {
        emit('update:open', false)
        emit('success')
      }
    })
  } catch (error) {
    console.error('Failed to delete rule:', error)
  }
}
</script>