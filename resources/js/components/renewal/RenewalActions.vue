<template>
  <div class="flex gap-2">
    <!-- First Call Actions -->
    <template v-if="step === 0 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'firstcall')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send call email') }}
      </Button>
      <Button
        @click="() => emit('action', 'renewalsSent')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Call sent manually') }}
      </Button>
    </template>

    <!-- Reminder Actions -->
    <template v-else-if="step === 2 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'remindercall')"
        :disabled="selectedCount === 0"
        variant="outline"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send reminder email') }}
      </Button>
      <Button
        @click="() => emit('action', 'lastcall')"
        :disabled="selectedCount === 0"
        variant="outline"
        :title="$t('Send reminder and enter grace period')"
      >
        <Mail class="mr-2 h-4 w-4" />
        {{ $t('Send last reminder email') }}
      </Button>
      <Button
        @click="() => emit('action', 'topay')"
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Instructions received to pay')"
      >
        <CreditCard class="mr-2 h-4 w-4" />
        {{ $t('Payment order received') }}
      </Button>
      <Button
        @click="() => emit('action', 'abandon')"
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Abandon instructions received')"
      >
        <XCircle class="mr-2 h-4 w-4" />
        {{ $t('Abandon') }}
      </Button>
      <Button
        @click="() => emit('action', 'lapsing')"
        :disabled="selectedCount === 0"
        variant="default"
        :title="$t('Office lapse communication received')"
      >
        <AlertTriangle class="mr-2 h-4 w-4" />
        {{ $t('Lapsed') }}
      </Button>
    </template>

    <!-- Payment Order Actions -->
    <template v-else-if="step === 4 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'renewalOrder')"
        :disabled="selectedCount === 0"
        variant="outline"
        :title="$t('Generate xml files for EP or FR')"
      >
        <Download class="mr-2 h-4 w-4" />
        {{ $t('Download XML order to pay') }}
      </Button>
      <Button
        @click="() => emit('action', 'done')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Paid') }}
      </Button>
    </template>

    <!-- Receipt Actions -->
    <template v-else-if="step === 6 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'receipt')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <FileText class="mr-2 h-4 w-4" />
        {{ $t('Official receipts received') }}
      </Button>
    </template>

    <!-- Receipts Received Actions -->
    <template v-else-if="step === 8 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'closing')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Receipts sent') }}
      </Button>
    </template>

    <!-- Abandoned Actions -->
    <template v-else-if="step === 12 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'lapsing')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <AlertTriangle class="mr-2 h-4 w-4" />
        {{ $t('Lapse') }}
      </Button>
    </template>

    <!-- Lapsed Actions -->
    <template v-else-if="step === 14 && invoiceStep === null">
      <Button
        @click="() => emit('action', 'closing')"
        :disabled="selectedCount === 0"
        variant="default"
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
        @click="() => emit('action', 'invoice')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <FileText class="mr-2 h-4 w-4" />
        {{ $t('Generate invoice') }}
      </Button>
      <Button
        @click="() => emit('action', 'export')"
        variant="outline"
      >
        <Download class="mr-2 h-4 w-4" />
        {{ $t('Export all') }}
      </Button>
      <Button
        @click="() => emit('action', 'renewalsInvoiced')"
        :disabled="selectedCount === 0"
        variant="default"
      >
        <CheckCircle class="mr-2 h-4 w-4" />
        {{ $t('Invoiced') }}
      </Button>
    </template>

    <!-- Invoiced Actions -->
    <template v-else-if="invoiceStep === 2">
      <Button
        @click="() => emit('action', 'paid')"
        :disabled="selectedCount === 0"
        variant="default"
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
import { Button } from '@/Components/ui/button'

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