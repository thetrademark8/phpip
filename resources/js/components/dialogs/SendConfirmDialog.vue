<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('email.confirmSend') }}</DialogTitle>
        <DialogDescription>
          {{ $t('email.confirmSendDescription') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <!-- Email Metadata Summary -->
        <EmailMetadata
          :recipients="recipients"
          :cc="cc"
          :bcc="bcc"
          :attachments="attachments"
        />

        <Separator />

        <!-- Subject -->
        <div>
          <Label class="text-muted-foreground text-xs">{{ $t('email.subject') }}</Label>
          <p class="font-medium mt-1">{{ subject }}</p>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Cancel') }}
        </Button>
        <Button @click="$emit('confirm')" :disabled="sending">
          <Send class="h-4 w-4 mr-2" />
          {{ sending ? $t('email.sending') : $t('email.send') }}
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>

<script setup>
import {
  Dialog,
  DialogScrollContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import EmailMetadata from '@/components/email/EmailMetadata.vue'
import { Send } from 'lucide-vue-next'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  recipients: {
    type: Array,
    default: () => [],
  },
  cc: {
    type: Array,
    default: () => [],
  },
  bcc: {
    type: Array,
    default: () => [],
  },
  attachments: {
    type: Array,
    default: () => [],
  },
  subject: {
    type: String,
    default: '',
  },
  sending: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:open', 'confirm'])
</script>
