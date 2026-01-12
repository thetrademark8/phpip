<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-3xl max-h-[80vh]">
      <DialogHeader>
        <DialogTitle>{{ $t('email.preview') }}</DialogTitle>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>

      <div v-else class="space-y-4">
        <!-- Email Metadata -->
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
          <div class="font-medium text-lg">{{ preview?.subject }}</div>
        </div>

        <Separator />

        <!-- Email Body -->
        <div class="email-preview-body prose prose-sm max-w-none">
          <div v-html="preview?.body"></div>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Close') }}
        </Button>
        <Button @click="$emit('send')">
          <Send class="h-4 w-4 mr-2" />
          {{ $t('email.send') }}
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
  DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import EmailMetadata from '@/components/email/EmailMetadata.vue'
import { Loader2, Send } from 'lucide-vue-next'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  preview: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  // Metadata props
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
})

defineEmits(['update:open', 'send'])
</script>

<style>
.email-preview-body {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  min-height: 200px;
}
</style>
