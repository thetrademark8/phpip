<template>
  <Dialog :open="true" @close="$emit('cancel')">
    <DialogContent class="sm:max-w-md">
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
          @click="$emit('cancel')"
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
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'

const props = defineProps({
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

const emit = defineEmits(['confirm', 'cancel'])

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