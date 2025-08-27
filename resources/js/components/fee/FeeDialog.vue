<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? t('fees.dialog.createTitle') : t('fees.dialog.editTitle') }}
        </DialogTitle>
        <DialogDescription>
          {{ operation === 'create' ? t('fees.dialog.createDescription') : t('fees.dialog.editDescription') }}
        </DialogDescription>
      </DialogHeader>

      <DialogSkeleton v-if="loading" :fields="operation === 'create' ? 6 : 12" />

      <div v-else-if="fee || operation === 'create'" class="space-y-6">
        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Basic Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="mb-2">{{ t('fees.fields.country') }} *</Label>
              <AutocompleteInput
                v-model="form.country"
                v-model:display-model-value="countryDisplay"
                endpoint="/country/autocomplete"
                :placeholder="t('fees.placeholders.country')"
                value-key="iso"
                label-key="name"
                required
              />
              <p v-if="form.errors.country" class="text-sm text-destructive mt-1">
                {{ form.errors.country }}
              </p>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('fees.fields.category') }} *</Label>
              <AutocompleteInput
                v-model="form.category"
                v-model:display-model-value="categoryDisplay"
                endpoint="/category/autocomplete"
                :placeholder="t('fees.placeholders.category')"
                value-key="id"
                label-key="name"
                required
              />
              <p v-if="form.errors.category" class="text-sm text-destructive mt-1">
                {{ form.errors.category }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="mb-2">{{ t('fees.fields.origin') }}</Label>
              <AutocompleteInput
                v-model="form.origin"
                v-model:display-model-value="originDisplay"
                endpoint="/country/autocomplete"
                value-key="iso"
                label-key="name"
                :placeholder="t('fees.placeholders.origin')"
              />
              <p v-if="form.errors.origin" class="text-sm text-destructive mt-1">
                {{ form.errors.origin }}
              </p>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('fees.fields.currency') }} *</Label>
              <CurrencySelect
                v-model="form.currency"
                :placeholder="t('fees.placeholders.currency')"
              />
              <p v-if="form.errors.currency" class="text-sm text-destructive mt-1">
                {{ form.errors.currency }}
              </p>
            </div>
          </div>

          <!-- Date Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="mb-2">{{ t('fees.fields.use_after') }}</Label>
              <DatePicker
                v-model="form.use_after"
                :placeholder="t('fees.placeholders.use_after')"
              />
              <p v-if="form.errors.use_after" class="text-sm text-destructive mt-1">
                {{ form.errors.use_after }}
              </p>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('fees.fields.use_before') }}</Label>
              <DatePicker
                v-model="form.use_before"
                :placeholder="t('fees.placeholders.use_before')"
              />
              <p v-if="form.errors.use_before" class="text-sm text-destructive mt-1">
                {{ form.errors.use_before }}
              </p>
            </div>
          </div>

          <!-- Quantity Range (Creation Mode Only) -->
          <div v-if="operation === 'create'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label class="mb-2">{{ t('fees.fields.from_qt') }} *</Label>
              <Input
                v-model.number="form.from_qt"
                type="number"
                :placeholder="t('fees.placeholders.from_qt')"
                min="1"
                required
              />
              <p v-if="form.errors.from_qt" class="text-sm text-destructive mt-1">
                {{ form.errors.from_qt }}
              </p>
            </div>
            
            <div>
              <Label class="mb-2">{{ t('fees.fields.to_qt') }} *</Label>
              <Input
                v-model.number="form.to_qt"
                type="number"
                :placeholder="t('fees.placeholders.to_qt')"
                min="1"
                required
              />
              <p v-if="form.errors.to_qt" class="text-sm text-destructive mt-1">
                {{ form.errors.to_qt }}
              </p>
            </div>
          </div>

          <!-- Cost Fields (Edit Mode Only) -->
          <div v-if="operation === 'edit'" class="space-y-4">
            <h3 class="text-lg font-semibold">{{ t('fees.sections.costs') }}</h3>
            
            <!-- Cost & Fee Groups -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('fees.fields.cost') }}</Label>
                <Input
                  v-model.number="form.cost"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.cost')"
                />
                <p v-if="form.errors.cost" class="text-sm text-destructive mt-1">
                  {{ form.errors.cost }}
                </p>
              </div>
              
              <div>
                <Label class="mb-2">{{ t('fees.fields.fee') }}</Label>
                <Input
                  v-model.number="form.fee"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.fee')"
                />
                <p v-if="form.errors.fee" class="text-sm text-destructive mt-1">
                  {{ form.errors.fee }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('fees.fields.cost_reduced') }}</Label>
                <Input
                  v-model.number="form.cost_reduced"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.cost_reduced')"
                />
                <p v-if="form.errors.cost_reduced" class="text-sm text-destructive mt-1">
                  {{ form.errors.cost_reduced }}
                </p>
              </div>
              
              <div>
                <Label class="mb-2">{{ t('fees.fields.fee_reduced') }}</Label>
                <Input
                  v-model.number="form.fee_reduced"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.fee_reduced')"
                />
                <p v-if="form.errors.fee_reduced" class="text-sm text-destructive mt-1">
                  {{ form.errors.fee_reduced }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('fees.fields.cost_discounted') }}</Label>
                <Input
                  v-model.number="form.cost_discounted"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.cost_discounted')"
                />
                <p v-if="form.errors.cost_discounted" class="text-sm text-destructive mt-1">
                  {{ form.errors.cost_discounted }}
                </p>
              </div>
              
              <div>
                <Label class="mb-2">{{ t('fees.fields.fee_discounted') }}</Label>
                <Input
                  v-model.number="form.fee_discounted"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.fee_discounted')"
                />
                <p v-if="form.errors.fee_discounted" class="text-sm text-destructive mt-1">
                  {{ form.errors.fee_discounted }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('fees.fields.cost_micro') }}</Label>
                <Input
                  v-model.number="form.cost_micro"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.cost_micro')"
                />
                <p v-if="form.errors.cost_micro" class="text-sm text-destructive mt-1">
                  {{ form.errors.cost_micro }}
                </p>
              </div>
              
              <div>
                <Label class="mb-2">{{ t('fees.fields.fee_micro') }}</Label>
                <Input
                  v-model.number="form.fee_micro"
                  type="number"
                  step="0.01"
                  :placeholder="t('fees.placeholders.fee_micro')"
                />
                <p v-if="form.errors.fee_micro" class="text-sm text-destructive mt-1">
                  {{ form.errors.fee_micro }}
                </p>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div v-if="fee && operation === 'edit'" class="pt-4 border-t space-y-2 text-sm text-muted-foreground">
            <div>
              <strong>{{ t('fees.fields.qt') }}:</strong> {{ fee.qt }}
            </div>
            <div v-if="fee.creator">
              {{ t('common.createdBy') }}: {{ fee.creator }}
              <span v-if="fee.created_at"> {{ t('common.on') }} {{ formatDate(fee.created_at) }}</span>
            </div>
            <div v-if="fee.updater">
              {{ t('common.updatedBy') }}: {{ fee.updater }}
              <span v-if="fee.updated_at"> {{ t('common.on') }} {{ formatDate(fee.updated_at) }}</span>
            </div>
          </div>
        </form>
      </div>

      <DialogFooter>
        <div class="flex justify-between w-full">
          <Button
            v-if="operation === 'edit' && canWrite"
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
              @click="handleSubmit"
              :disabled="form.processing"
            >
              <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
              {{ operation === 'create' ? t('actions.create') : t('actions.save') }}
            </Button>
          </div>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('fees.dialog.deleteTitle')"
    :description="t('fees.dialog.deleteDescription')"
    :message="t('fees.dialog.deleteDescription')"
    :confirm-text="t('actions.delete')"
    variant="destructive"
    @confirm="handleDelete"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Trash2, Loader2 } from 'lucide-vue-next'
import { format } from 'date-fns'
import axios from 'axios'
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
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import CurrencySelect from '@/components/ui/form/CurrencySelect.vue'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import DialogSkeleton from '@/components/ui/skeleton/DialogSkeleton.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  feeId: {
    type: [Number, String, null],
    default: null
  },
  operation: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()
const page = usePage()

// States
const loading = ref(false)
const fee = ref(null)
const showDeleteDialog = ref(false)

// Display values for autocomplete fields
const countryDisplay = ref('')
const categoryDisplay = ref('')
const originDisplay = ref('')

// Permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

// Form
const form = useForm({
  country: '',
  category: '',
  origin: '',
  currency: 'EUR',
  use_after: '',
  use_before: '',
  // Creation fields
  from_qt: 1,
  to_qt: 1,
  // Edit fields
  qt: null,
  cost: null,
  fee: null,
  cost_reduced: null,
  fee_reduced: null,
  cost_discounted: null,
  fee_discounted: null,
  cost_micro: null,
  fee_micro: null
})

// Watch for dialog open/close
watch(() => props.open, async (newValue) => {
  if (newValue) {
    if (props.operation === 'create') {
      form.reset()
      clearDisplayValues()
    } else if (props.feeId) {
      await fetchFee()
    }
  } else {
    // Reset state when closing
    fee.value = null
    form.reset()
    form.clearErrors()
    clearDisplayValues()
  }
})

watch(() => props.feeId, async (newId) => {
  if (newId && props.open && props.operation === 'edit') {
    await fetchFee()
  }
})

// Methods
async function fetchFee() {
  loading.value = true
  try {
    const response = await axios.get(`/fee/${props.feeId}`)
    fee.value = response.data.fee || response.data
    populateForm()
  } catch (error) {
    console.error('Error fetching fee:', error)
  } finally {
    loading.value = false
  }
}

function populateForm() {
  if (fee.value) {
    // Map backend field names to frontend
    form.country = fee.value.for_country || ''
    form.category = fee.value.for_category || ''
    form.origin = fee.value.for_origin || ''
    form.currency = fee.value.currency || 'EUR'
    form.use_after = fee.value.use_after || ''
    form.use_before = fee.value.use_before || ''
    form.qt = fee.value.qt || null
    form.cost = fee.value.cost
    form.fee = fee.value.fee
    form.cost_reduced = fee.value.cost_reduced
    form.fee_reduced = fee.value.fee_reduced
    form.cost_discounted = fee.value.cost_sup
    form.fee_discounted = fee.value.fee_sup
    form.cost_micro = fee.value.cost_sup_reduced
    form.fee_micro = fee.value.fee_sup_reduced

    // Set display values for autocomplete fields
    countryDisplay.value = fee.value.country?.name || ''
    categoryDisplay.value = fee.value.category?.category || ''
    originDisplay.value = fee.value.origin?.name || ''
  }
}

function clearDisplayValues() {
  countryDisplay.value = ''
  categoryDisplay.value = ''
  originDisplay.value = ''
}

function handleSubmit() {
  if (props.operation === 'create') {
    // Map frontend field names to backend expected names
    const data = {
      for_category: form.category,
      for_country: form.country,
      for_origin: form.origin,
      currency: form.currency,
      use_after: form.use_after,
      use_before: form.use_before,
      from_qt: form.from_qt,
      to_qt: form.to_qt
    }
    
    form.transform(() => data).post('/fee', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      },
      onError: (errors) => {
        console.error('Create fee errors:', errors)
      }
    })
  } else {
    // For edit, also map the field names
    const data = {
      for_category: form.category,
      for_country: form.country,
      for_origin: form.origin,
      currency: form.currency,
      use_after: form.use_after,
      use_before: form.use_before,
      qt: form.qt,
      cost: form.cost,
      fee: form.fee,
      cost_reduced: form.cost_reduced,
      fee_reduced: form.fee_reduced,
      cost_sup: form.cost_discounted,
      fee_sup: form.fee_discounted,
      cost_sup_reduced: form.cost_micro,
      fee_sup_reduced: form.fee_micro
    }
    
    form.transform(() => data).put(`/fee/${props.feeId}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      },
      onError: (errors) => {
        console.error('Update fee errors:', errors)
      }
    })
  }
}

function confirmDelete() {
  showDeleteDialog.value = true
}

function handleDelete() {
  form.delete(`/fee/${props.feeId}`, {
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    },
    onError: (errors) => {
      console.error('Delete fee errors:', errors)
    }
  })
}

function formatDate(date) {
  return format(new Date(date), 'MMM d, yyyy')
}
</script>