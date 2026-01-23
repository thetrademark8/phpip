<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
      <!-- Category -->
      <FormField
        :label="t('Category')"
        name="category_code"
        :error="form.errors.category_code"
        required
      >
        <TranslatedSelect
          v-model="form.category_code"
          :options="categoryOptions"
          :placeholder="t('Select category')"
        />
      </FormField>

      <!-- Country -->
      <FormField
        :label="t('Country')"
        name="country"
        :error="form.errors.country"
        required
      >
        <Combobox
          v-model="form.country"
          :options="countryOptions"
          :placeholder="t('Select country')"
          :search-placeholder="t('Search countries...')"
          :no-results-text="t('No country found.')"
        />
      </FormField>

      <!-- Reference -->
      <FormField
        :label="t('Reference')"
        name="caseref"
        :error="form.errors.caseref"
        required
      >
        <Input
          v-model="form.caseref"
          :placeholder="t('Matter reference')"
        />
      </FormField>

      <!-- Alternative Reference -->
      <FormField
        :label="t('Alternative Reference')"
        name="alt_ref"
        :error="form.errors.alt_ref"
      >
        <Input
          v-model="form.alt_ref"
          :placeholder="t('Alternative reference')"
        />
      </FormField>

      <!-- Type -->
      <FormField
        :label="t('Type')"
        name="type_code"
        :error="form.errors.type_code"
      >
        <TranslatedSelect
          v-model="form.type_code"
          :options="typeOptions"
          :placeholder="t('Select type')"
        />
      </FormField>

      <!-- Responsible -->
      <FormField
        :label="t('Responsible')"
        name="responsible"
        :error="form.errors.responsible"
      >
        <Combobox
          v-model="form.responsible"
          :options="userOptions"
          :placeholder="t('Select responsible user')"
          :search-placeholder="t('Search users...')"
          :no-results-text="t('No user found.')"
        />
      </FormField>

      <!-- Expiry Date -->
      <FormField
        :label="t('Expiry Date')"
        name="expire_date"
        :error="form.errors.expire_date"
      >
        <DatePicker
          v-model="form.expire_date"
          :placeholder="t('Select expiry date')"
        />
      </FormField>

      <!-- Notes -->
      <FormField
        :label="t('Notes')"
        name="notes"
        :error="form.errors.notes"
      >
        <Textarea
          v-model="form.notes"
          :placeholder="t('Additional notes...')"
          rows="3"
        />
      </FormField>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-between gap-3 mt-6 pt-4 border-t">
      <Button
        v-if="canDelete"
        type="button"
        variant="destructive"
        @click="showDeleteDialog = true"
        :disabled="form.processing || isDeleting"
      >
        <Trash2 class="mr-2 h-4 w-4" />
        {{ t('Delete') }}
      </Button>
      <div v-else></div>

      <div class="flex gap-3">
        <Button type="button" variant="outline" @click="handleCancel" :disabled="form.processing || isDeleting">
          {{ t('Cancel') }}
        </Button>
        <Button type="submit" :disabled="form.processing || isDeleting">
          <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
          {{ form.processing ? t('Saving...') : t('Save Changes') }}
        </Button>
      </div>
    </div>
  </form>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('Delete Matter')"
    :message="t('Are you sure you want to delete matter {uid}? This action cannot be undone and will delete all associated events, tasks, and documents.', { uid: matter.uid })"
    :confirm-text="t('Delete')"
    :cancel-text="t('Cancel')"
    type="destructive"
    @confirm="handleDelete"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Loader2, Trash2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/ui/form/FormField.vue'
import TranslatedSelect from '@/components/ui/form/TranslatedSelect.vue'
import { Combobox } from '@/components/ui/combobox'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'

const props = defineProps({
  matter: {
    type: Object,
    required: true
  },
  categoryOptions: {
    type: Array,
    default: () => []
  },
  typeOptions: {
    type: Array,
    default: () => []
  },
  userOptions: {
    type: Array,
    default: () => []
  },
  countryOptions: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['success', 'cancel', 'deleted'])

const page = usePage()

// Delete state
const showDeleteDialog = ref(false)
const isDeleting = ref(false)

// Check if user can delete (not CLI role)
const canDelete = computed(() => {
  const user = page.props.auth?.user
  return user?.role !== 'CLI'
})

const { t } = useI18n()

// Form setup
const form = useForm({
  category_code: props.matter.category_code || '',
  country: props.matter.country || '',
  caseref: props.matter.caseref || '',
  alt_ref: props.matter.alt_ref || '',
  type_code: props.matter.type_code || '',
  responsible: props.matter.responsible || '',
  expire_date: props.matter.expire_date || '',
  notes: props.matter.notes || ''
})

function handleSubmit() {
  form.put(`/matter/${props.matter.id}`, {
    onSuccess: () => {
      emit('success')
    }
  })
}

function handleCancel() {
  emit('cancel')
}

function handleDelete() {
  isDeleting.value = true

  router.delete(`/matter/${props.matter.id}`, {
    onSuccess: () => {
      emit('deleted')
      // Redirect to matters list after deletion
      router.visit('/matter')
    },
    onError: (errors) => {
      console.error('Delete matter error:', errors)
      isDeleting.value = false
    },
    onFinish: () => {
      showDeleteDialog.value = false
    }
  })
}
</script>
