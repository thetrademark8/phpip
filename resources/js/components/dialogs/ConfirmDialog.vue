<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ title || 'Confirm Action' }}</DialogTitle>
        <DialogDescription v-if="description">
          {{ description }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="py-4">
        <p class="text-sm text-muted-foreground">
          {{ message }}
        </p>
      </div>

      <DialogFooter class="flex-col sm:flex-row">
        <Button
          variant="outline"
          @click="$emit('update:open', false)"
          :disabled="loading"
        >
          {{ cancelText || 'Cancel' }}
        </Button>
        <Button
          :variant="type === 'destructive' ? 'destructive' : 'default'"
          @click="handleConfirm"
          :disabled="loading"
        >
          <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ confirmText || 'Confirm' }}
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  },
  type: {
    type: String,
    default: 'default', // 'default' | 'destructive'
    validator: (value) => ['default', 'destructive'].includes(value)
  }
})

const emit = defineEmits(['confirm', 'cancel', 'update:open'])

const loading = ref(false)

const handleConfirm = async () => {
  loading.value = true
  try {
    // Emit confirm and let parent handle the action
    emit('confirm')
  } finally {
    loading.value = false
  }
}
</script>