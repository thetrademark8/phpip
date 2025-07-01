<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Add Title</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="space-y-2">
          <Label htmlFor="type">Type</Label>
          <AutocompleteInput
            id="type"
            v-model="form.type_code"
            placeholder="Select type..."
            endpoint="/classifier-type/autocomplete/1"
            value-key="key"
            label-key="value"
            :min-length="0"
            required
          />
        </div>
        <div class="space-y-2">
          <Label htmlFor="value">Value</Label>
          <Input
            id="value"
            v-model="form.value"
            placeholder="Enter title value..."
            required
          />
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="$emit('update:open', false)">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            Add Title
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matterId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['update:open', 'success'])

const form = useForm({
  matter_id: props.matterId,
  type_code: '',
  value: ''
})

function handleSubmit() {
  form.post(`/matter/${props.matterId}/classifiers`, {
    onSuccess: () => {
      emit('success')
      emit('update:open', false)
    }
  })
}
</script>