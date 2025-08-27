<template>
  <div 
    class="inline-edit-wrapper group relative inline-flex items-center gap-1"
    @mouseenter="hovering = true"
    @mouseleave="hovering = false"
  >
    <!-- View mode -->
    <div v-if="!isEditing" class="inline-flex items-center gap-1">
      <slot :value="displayValue">
        <span :class="cn('inline-block', valueClass)">
          {{ displayValue || placeholder }}
        </span>
      </slot>
      
      <Button
        v-if="editable && (hovering || alwaysShowEdit)"
        size="icon"
        variant="ghost"
        class="h-4 w-4 p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
        @click="startEdit"
      >
        <Edit2 class="h-3 w-3" />
      </Button>
    </div>

    <!-- Edit mode -->
    <div v-else class="inline-flex items-center gap-1">
      <!-- Text input -->
      <Input
        v-if="type === 'text'"
        ref="inputRef"
        v-model="editValue"
        :placeholder="placeholder"
        :class="cn('h-7 px-2', inputClass)"
        @keydown.enter="save"
        @keydown.escape="cancel"
        @blur="handleBlur"
      />

      <!-- Select input -->
      <Select
        v-else-if="type === 'select'"
        v-model="editValue"
        @update:modelValue="save"
      >
        <SelectTrigger :class="cn('h-7', inputClass)">
          <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem
            v-for="option in options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>

      <!-- Date input -->
      <DatePicker
        v-else-if="type === 'date'"
        v-model="editValue"
        :placeholder="placeholder"
        :class="cn('h-7', inputClass)"
        @update:modelValue="save"
      />

      <!-- Action buttons -->
      <div v-if="type !== 'select' && type !== 'date'" class="inline-flex gap-0.5">
        <Button
          size="icon"
          variant="ghost"
          class="h-6 w-6 p-0.5"
          :disabled="loading"
          @click="save"
        >
          <Check class="h-3.5 w-3.5 text-green-600" />
        </Button>
        <Button
          size="icon"
          variant="ghost"
          class="h-6 w-6 p-0.5"
          :disabled="loading"
          @click="cancel"
        >
          <X class="h-3.5 w-3.5 text-destructive" />
        </Button>
      </div>

      <!-- Loading indicator -->
      <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Edit2, Check, X, Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { DatePicker } from '@/components/ui/date-picker'

const props = defineProps({
  modelValue: {
    type: [String, Number, Date, null],
    default: ''
  },
  type: {
    type: String,
    default: 'text',
    validator: (value) => ['text', 'select', 'date'].includes(value)
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
  alwaysShowEdit: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
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
  }
})

const emit = defineEmits(['update:modelValue', 'save', 'cancel'])

// Local state
const isEditing = ref(false)
const editValue = ref('')
const hovering = ref(false)
const inputRef = ref(null)

// Computed
const displayValue = computed(() => {
  if (props.format && props.modelValue) {
    return props.format(props.modelValue)
  }
  
  if (props.type === 'select' && props.options.length > 0) {
    const option = props.options.find(opt => opt.value === props.modelValue)
    return option ? option.label : props.modelValue
  }
  
  return props.modelValue || ''
})

// Methods
const startEdit = async () => {
  if (!props.editable) return
  
  isEditing.value = true
  editValue.value = props.modelValue || ''
  
  await nextTick()
  inputRef.value?.focus()
  inputRef.value?.select()
}

const save = async () => {
  if (editValue.value === props.modelValue) {
    cancel()
    return
  }
  
  emit('update:modelValue', editValue.value)
  emit('save', editValue.value)
  
  // Don't close edit mode if parent is handling async save
  if (!props.loading) {
    isEditing.value = false
  }
}

const cancel = () => {
  isEditing.value = false
  editValue.value = props.modelValue || ''
  emit('cancel')
}

const handleBlur = () => {
  if (props.saveOnBlur) {
    // Small delay to allow click on save/cancel buttons
    setTimeout(() => {
      if (isEditing.value) {
        save()
      }
    }, 200)
  }
}

// Watch for external loading state changes
watch(() => props.loading, (newVal) => {
  if (!newVal && isEditing.value) {
    isEditing.value = false
  }
})

// Expose methods
defineExpose({
  startEdit,
  cancel
})
</script>

<style scoped>
.inline-edit-wrapper {
  min-height: 1.75rem;
}
</style>