<template>
  <div class="editable-field">
    <InlineEdit
      v-model="localValue"
      :type="type"
      :options="options"
      :placeholder="placeholder"
      :editable="editable && !form.processing"
      :loading="form.processing"
      :format="format"
      :value-class="valueClass"
      :input-class="inputClass"
      :save-on-blur="saveOnBlur"
      @save="handleSave"
    >
      <template v-if="$slots.default" #default="{ value }">
        <slot :value="value" />
      </template>
    </InlineEdit>
    
    <!-- Error message -->
    <p v-if="form.errors[field]" class="text-xs text-destructive mt-0.5">
      {{ form.errors[field] }}
    </p>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InlineEdit from './InlineEdit.vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Date, null],
    required: true
  },
  field: {
    type: String,
    required: true
  },
  url: {
    type: String,
    required: true
  },
  method: {
    type: String,
    default: 'patch'
  },
  type: {
    type: String,
    default: 'text'
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Click to edit'
  },
  editable: {
    type: Boolean,
    default: true
  },
  format: {
    type: Function,
    default: null
  },
  valueClass: {
    type: String,
    default: ''
  },
  inputClass: {
    type: String,
    default: ''
  },
  saveOnBlur: {
    type: Boolean,
    default: true
  },
  additionalData: {
    type: Object,
    default: () => ({})
  },
  onSuccess: {
    type: Function,
    default: null
  },
  onError: {
    type: Function,
    default: null
  },
  preserveScroll: {
    type: Boolean,
    default: true
  },
  preserveState: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'saved', 'error'])

// Create form with initial value
const form = useForm({
  [props.field]: props.modelValue,
  ...props.additionalData
})

// Local value for InlineEdit
const localValue = ref(props.modelValue)

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
  form[props.field] = newValue
})

// Watch for additional data changes
watch(() => props.additionalData, (newData) => {
  Object.assign(form, newData)
}, { deep: true })

// Handle save
const handleSave = async (value) => {
  // Update form data
  form[props.field] = value
  
  // Clear previous errors
  form.clearErrors(props.field)
  
  // Submit based on method
  const submitMethod = form[props.method] || form.patch
  
  submitMethod(props.url, {
    preserveScroll: props.preserveScroll,
    preserveState: props.preserveState,
    onSuccess: (page) => {
      emit('update:modelValue', value)
      emit('saved', value)
      if (props.onSuccess) {
        props.onSuccess(page)
      }
    },
    onError: (errors) => {
      // Revert local value on error
      localValue.value = props.modelValue
      emit('error', errors)
      if (props.onError) {
        props.onError(errors)
      }
    }
  })
}
</script>