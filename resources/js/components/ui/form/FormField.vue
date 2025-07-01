<template>
  <div :class="cn('space-y-2', containerClass)">
    <!-- Label -->
    <Label 
      v-if="label"
      :for="id || name"
      :class="cn(
        error && 'text-destructive',
        labelClass
      )"
    >
      {{ label }}
      <span v-if="required" class="text-destructive ml-1">*</span>
    </Label>
    
    <!-- Field slot -->
    <div>
      <slot />
    </div>
    
    <!-- Error message -->
    <p 
      v-if="error"
      :class="cn('text-sm text-destructive', errorClass)"
    >
      {{ error }}
    </p>
    
    <!-- Help text -->
    <p 
      v-if="helpText && !error"
      :class="cn('text-sm text-muted-foreground', helpClass)"
    >
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { cn } from '@/lib/utils'
import { Label } from '@/Components/ui/label'

defineProps({
  label: {
    type: String,
    default: ''
  },
  name: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  helpText: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  containerClass: {
    type: String,
    default: ''
  },
  labelClass: {
    type: String,
    default: ''
  },
  errorClass: {
    type: String,
    default: ''
  },
  helpClass: {
    type: String,
    default: ''
  }
})
</script>