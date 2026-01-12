<template>
  <div class="email-history">
    <div v-if="loading" class="flex items-center justify-center py-8">
      <Loader2 class="h-6 w-6 animate-spin" />
    </div>

    <Table v-else-if="emails.length > 0">
      <TableHeader>
        <TableRow>
          <TableHead>{{ $t('email.date') }}</TableHead>
          <TableHead>{{ $t('email.recipient') }}</TableHead>
          <TableHead>{{ $t('email.subject') }}</TableHead>
          <TableHead>{{ $t('email.status') }}</TableHead>
          <TableHead class="w-[50px]"></TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow v-for="email in emails" :key="email.id">
          <TableCell class="text-sm">
            {{ formatDate(email.created_at) }}
          </TableCell>
          <TableCell>
            <div class="text-sm font-medium">{{ email.recipient_name }}</div>
            <div class="text-xs text-muted-foreground">{{ email.recipient_email }}</div>
          </TableCell>
          <TableCell class="max-w-[300px] truncate text-sm">
            {{ email.subject }}
          </TableCell>
          <TableCell>
            <Badge :variant="getStatusVariant(email.status)">
              {{ $t(`email.status.${email.status}`) }}
            </Badge>
          </TableCell>
          <TableCell>
            <Button
              variant="ghost"
              size="sm"
              class="h-7 w-7 p-0"
              @click="$emit('view', email)"
            >
              <Eye class="h-4 w-4" />
            </Button>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <div v-else class="text-center py-8 text-muted-foreground">
      {{ $t('email.noHistory') }}
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="flex justify-center mt-4">
      <Button
        variant="outline"
        size="sm"
        :disabled="pagination.current_page === 1"
        @click="$emit('page', pagination.current_page - 1)"
      >
        {{ $t('pagination.previous') }}
      </Button>
      <span class="mx-4 text-sm text-muted-foreground self-center">
        {{ pagination.current_page }} / {{ pagination.last_page }}
      </span>
      <Button
        variant="outline"
        size="sm"
        :disabled="pagination.current_page === pagination.last_page"
        @click="$emit('page', pagination.current_page + 1)"
      >
        {{ $t('pagination.next') }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Loader2, Eye } from 'lucide-vue-next'
import { useEmailFormatters } from '@/composables/useEmailFormatters'

defineProps({
  emails: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['view', 'page'])

// Use shared formatters
const { formatDate, getStatusVariant } = useEmailFormatters()
</script>
