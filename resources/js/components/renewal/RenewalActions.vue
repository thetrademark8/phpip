<template>
  <div class="flex gap-2">
    <!-- First Call Actions -->
    <template v-if="step === 0 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'firstcall')"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send call email') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'renewalsSent')"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Call sent manually') }}
      </Button>
    </template>

    <!-- Reminder Actions -->
    <template v-else-if="step === 2 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="outline"
        @click="() => emit('action', 'remindercall')"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send reminder email') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="outline"
        :title="$t('Send reminder and enter grace period')"
        @click="() => emit('action', 'lastcall')"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send last reminder email') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Instructions received to pay')"
        @click="() => emit('action', 'topay')"
      >
        <CreditCard class="mr-2 h-4 w-4" />
        {{ $t('Payment order received') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Abandon instructions received')"
        @click="() => emit('action', 'abandon')"
      >
        <XCircle class="mr-2 h-4 w-4" />
        {{ $t('Abandon') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Office lapse communication received')"
        @click="() => emit('action', 'lapsing')"
      >
        <AlertTriangle class="mr-2 h-4 w-4" />
        {{ $t('Lapsed') }}
      </Button>
    </template>

    <!-- Payment Order Actions -->
    <template v-else-if="step === 4 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="outline"
        :title="$t('Generate xml files for EP or FR')"
        @click="() => emit('action', 'renewalOrder')"
      >
        <Download class="mr-2 h-4 w-4" />
        {{ $t('Download XML order to pay') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'done')"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Paid') }}
      </Button>
    </template>

    <!-- Receipt Actions -->
    <template v-else-if="step === 6 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'receipt')"
      >
        <FileText class="mr-2 h-4 w-4" />
        {{ $t('Official receipts received') }}
      </Button>
    </template>

    <!-- Receipts Received Actions -->
    <template v-else-if="step === 8 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'closing')"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Receipts sent') }}
      </Button>
    </template>

    <!-- Abandoned Actions -->
    <template v-else-if="step === 12 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'lapsing')"
      >
        <AlertTriangle class="mr-2 h-4 w-4" />
        {{ $t('Lapse') }}
      </Button>
    </template>

    <!-- Lapsed Actions -->
    <template v-else-if="step === 14 && invoiceStep === null">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'closing')"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Lapse communication sent') }}
      </Button>
    </template>

    <!-- Closed Actions -->
    <template v-else-if="step === 10 && invoiceStep === null">
      <!-- No reopen action available in the current system -->
    </template>

    <!-- Invoicing Actions -->
    <template v-else-if="invoiceStep === 1">
      <Button
        v-if="config.invoice_backend === 'dolibarr'"
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'invoice')"
      >
        <FileText class="mr-2 h-4 w-4" />
        {{ $t('Generate invoice') }}
      </Button>
      <Button
        variant="outline"
        @click="() => emit('action', 'export')"
      >
        <Download class="mr-2 h-4 w-4" />
        {{ $t('Export all') }}
      </Button>
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'renewalsInvoiced')"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Invoiced') }}
      </Button>
    </template>

    <!-- Invoiced Actions -->
    <template v-else-if="invoiceStep === 2">
      <Button
        :disabled="selectedCount === 0"
        variant="default"
        @click="() => emit('action', 'paid')"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Mark invoices as paid') }}
      </Button>
    </template>

    <!-- Invoices Paid Actions -->
    <template v-else-if="invoiceStep === 3">
      <!-- No action buttons per Blade view - disabled button only -->
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { 
  Mail, 
  CreditCard, 
  FileText, 
  CheckCircle, 
  XCircle, 
  AlertTriangle, 
  RotateCcw, 
  Download 
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps({
  step: {
    type: [Number, String, null],
    default: null
  },
  invoiceStep: {
    type: [Number, String, null],
    default: null
  },
  selectedCount: {
    type: Number,
    default: 0
  },
  config: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['action'])

const { t } = useI18n()
</script>