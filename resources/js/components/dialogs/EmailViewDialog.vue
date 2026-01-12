<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-3xl max-h-[80vh]">
      <DialogHeader>
        <DialogTitle>{{ $t('email.viewEmail') }}</DialogTitle>
      </DialogHeader>

      <div v-if="loading" class="flex items-center justify-center py-12">
        <Loader2 class="h-8 w-8 animate-spin" />
      </div>

      <div v-else-if="email" class="space-y-4">
        <!-- Email Metadata -->
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <Label class="text-muted-foreground text-xs">{{ $t('email.recipient') }}</Label>
            <div>{{ email.recipient_name }} &lt;{{ email.recipient_email }}&gt;</div>
          </div>
          <div>
            <Label class="text-muted-foreground text-xs">{{ $t('email.sentAt') }}</Label>
            <div>{{ formatDate(email.sent_at || email.created_at) }}</div>
          </div>
          <div v-if="email.cc?.length">
            <Label class="text-muted-foreground text-xs">CC</Label>
            <div>{{ email.cc.join(', ') }}</div>
          </div>
          <div v-if="email.bcc?.length">
            <Label class="text-muted-foreground text-xs">BCC</Label>
            <div>{{ email.bcc.join(', ') }}</div>
          </div>
        </div>

        <div>
          <Label class="text-muted-foreground text-xs">{{ $t('email.status') }}</Label>
          <div>
            <Badge :variant="getStatusVariant(email.status)">
              {{ $t(`email.status.${email.status}`) }}
            </Badge>
            <span v-if="email.error_message" class="ml-2 text-sm text-destructive">
              {{ email.error_message }}
            </span>
          </div>
        </div>

        <Separator />

        <div>
          <Label class="text-muted-foreground text-xs">{{ $t('email.subject') }}</Label>
          <div class="font-medium text-lg">{{ email.subject }}</div>
        </div>

        <Separator />

        <!-- Email Body -->
        <div class="email-view-body prose prose-sm max-w-none">
          <div v-html="email.body_html"></div>
        </div>

        <!-- Attachments -->
        <div v-if="attachments?.length > 0">
          <Separator />
          <Label class="text-muted-foreground text-xs">{{ $t('email.attachments') }}</Label>
          <div class="flex flex-wrap gap-2 mt-2">
            <Button
              v-for="attachment in attachments"
              :key="attachment.id"
              variant="outline"
              size="sm"
              @click="downloadAttachment(attachment)"
            >
              <FileIcon class="h-4 w-4 mr-1" />
              {{ attachment.original_name }}
            </Button>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Close') }}
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
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Loader2, FileIcon } from 'lucide-vue-next'
import { useEmailFormatters } from '@/composables/useEmailFormatters'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  email: {
    type: Object,
    default: null,
  },
  attachments: {
    type: Array,
    default: () => [],
  },
  matterId: {
    type: Number,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:open'])

// Use shared formatters
const { formatDate, getStatusVariant } = useEmailFormatters()

const downloadAttachment = (attachment) => {
  window.open(`/matter/${props.matterId}/attachments/${attachment.id}/download`, '_blank')
}
</script>

<style>
.email-view-body {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  min-height: 200px;
}
</style>
