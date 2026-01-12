<template>
  <div class="space-y-2">
    <Label v-if="label" class="text-xs">{{ label }}</Label>
    <div class="flex gap-2">
      <Input
        v-model="inputValue"
        :placeholder="placeholder"
        class="flex-1"
        @keydown.enter.prevent="addEmail"
        @blur="handleBlur"
      />
      <Button
        variant="outline"
        size="sm"
        @click="addEmail"
        :disabled="!isValidInput"
        :title="$t('email.addEmail')"
      >
        <Plus class="h-4 w-4" />
      </Button>
    </div>

    <!-- Validation hint -->
    <p v-if="inputValue && !isValidInput" class="text-xs text-destructive">
      {{ $t('email.invalidEmail') }}
    </p>

    <!-- Email badges -->
    <div v-if="modelValue.length" class="flex flex-wrap gap-1">
      <Badge
        v-for="(email, idx) in modelValue"
        :key="idx"
        variant="secondary"
        class="cursor-pointer group hover:bg-secondary/80"
        @click="removeEmail(idx)"
      >
        {{ email }}
        <X class="h-3 w-3 ml-1 opacity-60 group-hover:opacity-100" />
      </Badge>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Plus, X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const inputValue = ref('')

// Email validation regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const isValidInput = computed(() => {
  const email = inputValue.value.trim()
  return email.length > 0 && emailRegex.test(email)
})

const addEmail = () => {
  const email = inputValue.value.trim()
  if (!email || !isValidInput.value) return

  // Avoid duplicates
  if (props.modelValue.includes(email)) {
    inputValue.value = ''
    return
  }

  emit('update:modelValue', [...props.modelValue, email])
  inputValue.value = ''
}

const removeEmail = (idx) => {
  const newValue = [...props.modelValue]
  newValue.splice(idx, 1)
  emit('update:modelValue', newValue)
}

const handleBlur = () => {
  // Auto-add valid email on blur if input is not empty
  if (isValidInput.value) {
    addEmail()
  }
}
</script>
