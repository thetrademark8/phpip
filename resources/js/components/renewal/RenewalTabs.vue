<template>
  <Tabs :default-value="activeTab" class="w-full">
    <TabsList class="grid w-full grid-flow-col auto-cols-fr">
      <TabsTrigger 
        value="first-call"
        @click="handleTabClick(0, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(0, null) }"
      >
        {{ $t('First call') }}
      </TabsTrigger>
      <TabsTrigger 
        value="reminder"
        @click="handleTabClick(2, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(2, null) }"
      >
        {{ $t('Reminder') }}
      </TabsTrigger>
      <TabsTrigger 
        value="payment"
        @click="handleTabClick(4, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(4, null) }"
      >
        {{ $t('Payment') }}
      </TabsTrigger>
      <TabsTrigger 
        v-if="config.receipt_tabs"
        value="receipts"
        @click="handleTabClick(6, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(6, null) }"
      >
        {{ $t('Receipts') }}
      </TabsTrigger>
      <TabsTrigger 
        v-if="config.receipt_tabs"
        value="receipts-received"
        @click="handleTabClick(8, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(8, null) }"
      >
        {{ $t('Receipts received') }}
      </TabsTrigger>
      <TabsTrigger 
        value="abandoned"
        @click="handleTabClick(12, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(12, null) }"
      >
        {{ $t('Abandoned') }}
      </TabsTrigger>
      <TabsTrigger 
        value="lapsed"
        @click="handleTabClick(14, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(14, null) }"
      >
        {{ $t('Lapsed') }}
      </TabsTrigger>
      <TabsTrigger 
        value="closed"
        @click="handleTabClick(10, null)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(10, null) }"
      >
        {{ $t('Closed') }}
      </TabsTrigger>
      <TabsTrigger 
        value="invoicing"
        @click="handleTabClick(null, 1)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(null, 1) }"
      >
        {{ $t('Invoicing') }}
      </TabsTrigger>
      <TabsTrigger 
        value="invoiced"
        @click="handleTabClick(null, 2)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(null, 2) }"
      >
        {{ $t('Invoiced') }}
      </TabsTrigger>
      <TabsTrigger 
        value="invoices-paid"
        @click="handleTabClick(null, 3)"
        :class="{ 'data-[state=active]:bg-primary data-[state=active]:text-primary-foreground': isActiveTab(null, 3) }"
      >
        {{ $t('Invoices paid') }}
      </TabsTrigger>
    </TabsList>
  </Tabs>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs'

const props = defineProps({
  activeStep: {
    type: [Number, String, null],
    default: 0
  },
  activeInvoiceStep: {
    type: [Number, String, null],
    default: null
  },
  config: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['tab-change'])

const { t } = useI18n()

// Computed active tab based on step and invoice step
const activeTab = computed(() => {
  if (props.activeInvoiceStep !== null) {
    switch (parseInt(props.activeInvoiceStep)) {
      case 1: return 'invoicing'
      case 2: return 'invoiced'
      case 3: return 'invoices-paid'
    }
  }
  
  switch (parseInt(props.activeStep)) {
    case 0: return 'first-call'
    case 2: return 'reminder'
    case 4: return 'payment'
    case 6: return 'receipts'
    case 8: return 'receipts-received'
    case 10: return 'closed'
    case 12: return 'abandoned'
    case 14: return 'lapsed'
    default: return 'first-call'
  }
})

// Check if a tab is active
function isActiveTab(step, invoiceStep) {
  if (invoiceStep !== null) {
    return props.activeInvoiceStep === invoiceStep && props.activeStep === null
  }
  return props.activeStep === step && props.activeInvoiceStep === null
}

// Handle tab click
function handleTabClick(step, invoiceStep) {
  emit('tab-change', { step, invoiceStep })
}
</script>