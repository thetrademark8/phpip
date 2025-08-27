<template>
  <form @submit.prevent="handleSubmit">
    <div class="space-y-4">
      <!-- Category -->
      <FormField
        :label="t('Category')"
        name="category_code"
        :error="form.errors.category_code"
        required
      >
        <AutocompleteInput
          v-model="form.category_code"
          v-model:display-model-value="categoryDisplay"
          endpoint="/category/autocomplete"
          :placeholder="t('Select category')"
          :min-length="0"
          value-key="key"
          label-key="value"
          @selected="handleCategorySelect"
        />
      </FormField>

      <!-- Country -->
      <FormField
        :label="t('Country')"
        name="country"
        :error="form.errors.country"
        required
      >
        <AutocompleteInput
          v-model="form.country"
          v-model:display-model-value="countryDisplay"
          endpoint="/country/autocomplete"
          :placeholder="t('Select country')"
          :min-length="0"
          value-key="key"
          label-key="value"
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
        <AutocompleteInput
          v-model="form.type_code"
          v-model:display-model-value="typeDisplay"
          endpoint="/type/autocomplete"
          :placeholder="t('Select type')"
          value-key="key"
          label-key="value"
        />
      </FormField>

      <!-- Responsible -->
      <FormField
        :label="t('Responsible')"
        name="responsible"
        :error="form.errors.responsible"
      >
        <AutocompleteInput
          v-model="form.responsible"
          v-model:display-model-value="responsibleDisplay"
          endpoint="/user/autocomplete"
          :placeholder="t('Select responsible user')"
          value-key="login"
          label-key="name"
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
    <div class="flex justify-end gap-3 mt-6">
      <Button type="button" variant="outline" @click="handleCancel">
        {{ t('Cancel') }}
      </Button>
      <Button type="submit" :disabled="form.processing">
        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
        {{ form.processing ? t('Saving...') : t('Save Changes') }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/ui/form/FormField.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  matter: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['success', 'cancel'])

const { t } = useI18n()
const { translated } = useTranslatedField()

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

// Display values for autocomplete fields
// The category object has the translated 'category' field
const categoryDisplay = ref(props.matter.category?.category ? translated(props.matter.category.category) : '')
const countryDisplay = ref(props.matter.country_info?.name || '')
const typeDisplay = ref(props.matter.type?.type || '')
const responsibleDisplay = ref(props.matter.responsible || '')

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

function handleCategorySelect(category) {
  if (category) {
    categoryDisplay.value = category.value || category.category || ''
  }
}
</script>