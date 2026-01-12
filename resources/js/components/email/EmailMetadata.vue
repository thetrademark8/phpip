<template>
  <div class="space-y-3 text-sm">
    <!-- Recipients (To) -->
    <div v-if="recipients?.length">
      <Label class="text-muted-foreground text-xs">{{ $t('email.to') }}</Label>
      <div class="flex flex-wrap gap-1 mt-1">
        <Badge v-for="r in recipients" :key="r.id" variant="outline">
          {{ r.name }} &lt;{{ r.email }}&gt;
        </Badge>
      </div>
    </div>

    <!-- CC -->
    <div v-if="cc?.length">
      <Label class="text-muted-foreground text-xs">CC</Label>
      <div class="flex flex-wrap gap-1 mt-1">
        <Badge v-for="email in cc" :key="email" variant="secondary">
          {{ email }}
        </Badge>
      </div>
    </div>

    <!-- BCC -->
    <div v-if="bcc?.length">
      <Label class="text-muted-foreground text-xs">BCC</Label>
      <div class="flex flex-wrap gap-1 mt-1">
        <Badge v-for="email in bcc" :key="email" variant="secondary">
          {{ email }}
        </Badge>
      </div>
    </div>

    <!-- Attachments -->
    <div v-if="attachments?.length">
      <Label class="text-muted-foreground text-xs">{{ $t('email.attachments') }}</Label>
      <div class="flex flex-wrap gap-1 mt-1">
        <Badge v-for="a in attachments" :key="a.id" variant="outline" class="gap-1">
          <Paperclip class="h-3 w-3" />
          {{ a.original_name }}
        </Badge>
      </div>
    </div>

    <!-- Fallback: Nothing to show -->
    <div
      v-if="!recipients?.length && !cc?.length && !bcc?.length && !attachments?.length"
      class="text-muted-foreground text-xs"
    >
      {{ $t('email.noMetadata') }}
    </div>
  </div>
</template>

<script setup>
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Paperclip } from 'lucide-vue-next'

defineProps({
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
</script>
