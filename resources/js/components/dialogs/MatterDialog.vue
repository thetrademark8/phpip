<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription v-if="dialogDescription">
          {{ dialogDescription }}
        </DialogDescription>
      </DialogHeader>
      
    <div>
        <MatterEditForm
          v-if="operation === 'edit'"
          :matter="matter"
          @success="handleSuccess"
          @cancel="$emit('update:open', false)"
        />
        <MatterForm
          v-else
          :operation="operation"
          :parent-matter="matter"
          :child-id="childId"
          :ops-number="opsNumber"
          :category="category"
          :current-user="currentUser"
          @success="handleSuccess"
          @cancel="$emit('update:open', false)"
        />
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import MatterForm from '@/components/forms/MatterForm.vue'
import MatterEditForm from '@/components/forms/MatterEditForm.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  operation: {
    type: String,
    default: 'new',
    validator: (value) => ['new', 'edit', 'child', 'ops'].includes(value)
  },
  matter: {
    type: Object,
    default: null
  },
  childId: {
    type: Number,
    default: null
  },
  opsNumber: {
    type: String,
    default: ''
  },
  category: {
    type: String,
    default: null
  },
  currentUser: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()

const dialogTitle = computed(() => {
  switch (props.operation) {
    case 'new':
      return t('Create Matter')
    case 'edit':
      return t('Edit Matter')
    case 'child':
      return t('Create Child Matter')
    case 'ops':
      return t('Create Family from OPS')
    default:
      return t('Create Matter')
  }
})

const dialogDescription = computed(() => {
  switch (props.operation) {
    case 'child':
      return t('Create a new matter linked to the current one')
    case 'ops':
      return t('Fetch patent family information from OPS and create matters')
    default:
      return null
  }
})

const handleSuccess = (response) => {
  // Emit success to parent and let parent handle closing
  emit('success', response)
}
</script>