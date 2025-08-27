<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ $t('New Child Matter') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Create a child matter from') }} {{ parentMatter.uid }}
        </DialogDescription>
      </DialogHeader>
      
      <MatterForm
        operation="child"
        :parent-matter="parentMatter"
        :current-user="currentUser"
        :on-success="handleSuccess"
        :on-cancel="() => isOpen = false"
      />
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import MatterForm from '@/components/forms/MatterForm.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  parentMatter: {
    type: Object,
    required: true
  },
  currentUser: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const handleSuccess = () => {
  // Close dialog
  isOpen.value = false
  
  // Emit success event
  emit('success')
  
  // Inertia will handle the redirect automatically via to_route
}
</script>