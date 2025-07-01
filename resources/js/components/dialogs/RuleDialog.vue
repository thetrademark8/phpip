<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? $t('rules.dialog.createTitle') : $t('rules.dialog.editTitle') }}
        </DialogTitle>
        <DialogDescription>
          {{ operation === 'create' ? $t('rules.dialog.createDescription') : $t('rules.dialog.editDescription') }}
        </DialogDescription>
      </DialogHeader>
      
      <div v-if="loading" class="flex items-center justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>
      
      <form v-else @submit.prevent="handleSubmit" class="space-y-4">
        <Tabs defaultValue="main" class="w-full">
          <TabsList class="grid w-full grid-cols-3">
            <TabsTrigger value="main">{{ $t('rules.tabs.main') }}</TabsTrigger>
            <TabsTrigger value="conditions">{{ $t('rules.tabs.conditions') }}</TabsTrigger>
            <TabsTrigger value="cost">{{ $t('rules.tabs.cost') }}</TabsTrigger>
          </TabsList>
          
          <TabsContent value="main" class="space-y-4 mt-4">
            <div class="grid grid-cols-2 gap-4">
              <FormField
                :label="$t('rules.fields.task')"
                name="task"
                :required="true"
                :help-text="comments?.task"
              >
                <AutocompleteInput
                  v-model="form.task"
                  v-model:display-model-value="taskDisplay"
                  endpoint="/event-name/autocomplete/1"
                  :placeholder="$t('rules.placeholders.task')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.detail')"
                name="detail"
                :help-text="comments?.detail"
              >
                <Input
                  v-model="form.detail"
                  :placeholder="$t('rules.placeholders.detail')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.triggerEvent')"
                name="trigger_event"
                :required="true"
                :help-text="comments?.trigger_event"
              >
                <AutocompleteInput
                  v-model="form.trigger_event"
                  v-model:display-model-value="triggerDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="$t('rules.placeholders.triggerEvent')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.category')"
                name="for_category"
                :required="true"
                :help-text="comments?.for_category"
              >
                <AutocompleteInput
                  v-model="form.for_category"
                  v-model:display-model-value="categoryDisplay"
                  endpoint="/category/autocomplete"
                  :placeholder="$t('rules.placeholders.category')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.country')"
                name="for_country"
                :help-text="comments?.for_country"
              >
                <AutocompleteInput
                  v-model="form.for_country"
                  v-model:display-model-value="countryDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="$t('rules.placeholders.country')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.origin')"
                name="for_origin"
                :help-text="comments?.for_origin"
              >
                <AutocompleteInput
                  v-model="form.for_origin"
                  v-model:display-model-value="originDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="$t('rules.placeholders.origin')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.type')"
                name="for_type"
                :help-text="comments?.for_type"
              >
                <AutocompleteInput
                  v-model="form.for_type"
                  v-model:display-model-value="typeDisplay"
                  endpoint="/type/autocomplete"
                  :placeholder="$t('rules.placeholders.type')"
                  :disabled="operation === 'view'"
                />
              </FormField>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
              <FormField :label="$t('rules.fields.clearTask')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="clear_task"
                    v-model="form.clear_task"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="clear_task" class="font-normal">
                    {{ $t('rules.fields.clearTaskDescription') }}
                  </Label>
                </div>
              </FormField>
              
              <FormField :label="$t('rules.fields.deleteTask')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="delete_task"
                    v-model="form.delete_task"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="delete_task" class="font-normal">
                    {{ $t('rules.fields.deleteTaskDescription') }}
                  </Label>
                </div>
              </FormField>
              
              <FormField :label="$t('rules.fields.active')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="active"
                    v-model="form.active"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="active" class="font-normal">
                    {{ $t('rules.fields.activeDescription') }}
                  </Label>
                </div>
              </FormField>
            </div>
            
            <FormField
              :label="$t('rules.fields.notes')"
              name="notes"
              :help-text="comments?.notes"
            >
              <Textarea
                v-model="form.notes"
                :placeholder="$t('rules.placeholders.notes')"
                :disabled="operation === 'view'"
                rows="3"
              />
            </FormField>
          </TabsContent>
          
          <TabsContent value="conditions" class="space-y-4 mt-4">
            <div class="grid grid-cols-3 gap-4">
              <FormField
                :label="$t('rules.fields.days')"
                name="days"
                :help-text="comments?.days"
              >
                <Input
                  v-model.number="form.days"
                  type="number"
                  :placeholder="'0'"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.months')"
                name="months"
                :help-text="comments?.months"
              >
                <Input
                  v-model.number="form.months"
                  type="number"
                  :placeholder="'0'"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.years')"
                name="years"
                :help-text="comments?.years"
              >
                <Input
                  v-model.number="form.years"
                  type="number"
                  :placeholder="'0'"
                  :disabled="operation === 'view'"
                />
              </FormField>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <FormField :label="$t('rules.fields.endOfMonth')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="end_of_month"
                    v-model="form.end_of_month"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="end_of_month" class="font-normal">
                    {{ $t('rules.fields.endOfMonthDescription') }}
                  </Label>
                </div>
              </FormField>
              
              <FormField :label="$t('rules.fields.usePriority')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="use_priority"
                    v-model="form.use_priority"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="use_priority" class="font-normal">
                    {{ $t('rules.fields.usePriorityDescription') }}
                  </Label>
                </div>
              </FormField>
              
              <FormField :label="$t('rules.fields.recurring')">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="recurring"
                    v-model="form.recurring"
                    :disabled="operation === 'view'"
                  />
                  <Label htmlFor="recurring" class="font-normal">
                    {{ $t('rules.fields.recurringDescription') }}
                  </Label>
                </div>
              </FormField>
            </div>
            
            <div class="grid grid-cols-1 gap-4">
              <FormField
                :label="$t('rules.fields.conditionEvent')"
                name="condition_event"
                :help-text="comments?.condition_event"
              >
                <AutocompleteInput
                  v-model="form.condition_event"
                  v-model:display-model-value="conditionDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="$t('rules.placeholders.conditionEvent')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.abortOn')"
                name="abort_on"
                :help-text="comments?.abort_on"
              >
                <AutocompleteInput
                  v-model="form.abort_on"
                  v-model:display-model-value="abortDisplay"
                  endpoint="/event-name/autocomplete/0"
                  :placeholder="$t('rules.placeholders.abortOn')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.responsible')"
                name="responsible"
                :help-text="comments?.responsible"
              >
                <AutocompleteInput
                  v-model="form.responsible"
                  v-model:display-model-value="responsibleDisplay"
                  endpoint="/user/autocomplete"
                  :placeholder="$t('rules.placeholders.responsible')"
                  :disabled="operation === 'view'"
                />
              </FormField>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <FormField
                :label="$t('rules.fields.useBefore')"
                name="use_before"
                :help-text="comments?.use_before"
              >
                <DatePicker
                  v-model="form.use_before"
                  :placeholder="$t('rules.placeholders.useBefore')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.useAfter')"
                name="use_after"
                :help-text="comments?.use_after"
              >
                <DatePicker
                  v-model="form.use_after"
                  :placeholder="$t('rules.placeholders.useAfter')"
                  :disabled="operation === 'view'"
                />
              </FormField>
            </div>
          </TabsContent>
          
          <TabsContent value="cost" class="space-y-4 mt-4">
            <div class="grid grid-cols-1 gap-4">
              <FormField
                :label="$t('rules.fields.cost')"
                name="cost"
                :help-text="comments?.cost"
              >
                <Input
                  v-model.number="form.cost"
                  type="number"
                  step="0.01"
                  :placeholder="$t('rules.placeholders.cost')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.fee')"
                name="fee"
                :help-text="comments?.fee"
              >
                <Input
                  v-model.number="form.fee"
                  type="number"
                  step="0.01"
                  :placeholder="$t('rules.placeholders.fee')"
                  :disabled="operation === 'view'"
                />
              </FormField>
              
              <FormField
                :label="$t('rules.fields.currency')"
                name="currency"
                :help-text="comments?.currency"
              >
                <Input
                  v-model="form.currency"
                  maxlength="3"
                  :placeholder="$t('rules.placeholders.currency')"
                  :disabled="operation === 'view'"
                />
              </FormField>
            </div>
          </TabsContent>
        </Tabs>
        
        <DialogFooter>
          <Button
            v-if="operation === 'view' && canAdmin"
            type="button"
            variant="outline"
            @click="operation = 'edit'"
          >
            <Edit2 class="mr-2 h-4 w-4" />
            {{ $t('rules.dialog.edit') }}
          </Button>
          <Button
            v-if="operation === 'edit' && canAdmin"
            type="button"
            variant="destructive"
            @click="handleDelete"
          >
            <Trash2 class="mr-2 h-4 w-4" />
            {{ $t('rules.dialog.delete') }}
          </Button>
          <Button type="button" variant="outline" @click="$emit('update:open', false)">
            {{ $t('rules.dialog.cancel') }}
          </Button>
          <Button
            v-if="operation !== 'view'"
            type="submit"
            :disabled="form.processing"
          >
            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
            {{ operation === 'create' ? $t('rules.dialog.create') : $t('rules.dialog.save') }}
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Loader2, Edit2, Trash2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/Components/ui/tabs'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Label } from '@/Components/ui/label'
import { Checkbox } from '@/Components/ui/checkbox'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'

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
const page = usePage()
const loading = ref(false)
const rule = ref(null)
const comments = ref(null)
const operation = ref(props.operation)

// Check permissions
const canAdmin = computed(() => page.props.auth.user.default_role === 'DBA')

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

// Watch for dialog open/close
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    operation.value = props.operation
    if (props.ruleId && props.operation !== 'create') {
      loadRule()
    } else if (props.operation === 'create') {
      loadComments()
      resetForm()
    }
  }
})

async function loadRule() {
  loading.value = true
  try {
    const response = await fetch(`/rule/${props.ruleId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    const data = await response.json()
    rule.value = data.rule
    comments.value = data.comments
    
    // Populate form
    form.task = rule.value.task || ''
    form.detail = rule.value.detail || ''
    form.trigger_event = rule.value.trigger_event || ''
    form.for_category = rule.value.for_category || ''
    form.for_country = rule.value.for_country || ''
    form.for_origin = rule.value.for_origin || ''
    form.for_type = rule.value.for_type || ''
    form.clear_task = rule.value.clear_task || false
    form.delete_task = rule.value.delete_task || false
    form.active = rule.value.active || false
    form.notes = rule.value.notes || ''
    form.days = rule.value.days || 0
    form.months = rule.value.months || 0
    form.years = rule.value.years || 0
    form.end_of_month = rule.value.end_of_month || false
    form.use_priority = rule.value.use_priority || false
    form.recurring = rule.value.recurring || false
    form.condition_event = rule.value.condition_event || ''
    form.abort_on = rule.value.abort_on || ''
    form.responsible = rule.value.responsible || ''
    form.use_before = rule.value.use_before || ''
    form.use_after = rule.value.use_after || ''
    form.cost = rule.value.cost
    form.fee = rule.value.fee
    form.currency = rule.value.currency || ''
    
    // Set display values
    taskDisplay.value = rule.value.task_info?.name || ''
    triggerDisplay.value = rule.value.trigger?.name || ''
    categoryDisplay.value = rule.value.category?.category || ''
    countryDisplay.value = rule.value.country?.name || ''
    originDisplay.value = rule.value.origin?.name || ''
    typeDisplay.value = rule.value.type?.type || ''
    conditionDisplay.value = rule.value.condition_event_info?.name || ''
    abortDisplay.value = rule.value.abort_on_info?.name || ''
    responsibleDisplay.value = rule.value.responsible_info?.name || ''
  } catch (error) {
    console.error('Error loading rule:', error)
  } finally {
    loading.value = false
  }
}

async function loadComments() {
  try {
    const response = await fetch('/rule/create', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Inertia': 'true'
      }
    })
    const data = await response.json()
    comments.value = data.props.comments
  } catch (error) {
    console.error('Error loading comments:', error)
  }
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

function handleDelete() {
  if (confirm(t('rules.dialog.confirmDelete'))) {
    form.delete(`/rule/${props.ruleId}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  }
}
</script>