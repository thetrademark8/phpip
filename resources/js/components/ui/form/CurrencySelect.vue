<template>
  <Select v-model="localValue">
    <SelectTrigger :disabled="disabled">
      <SelectValue :placeholder="placeholder || t('Select currency')" />
    </SelectTrigger>
    <SelectContent>
      <div class="p-2">
        <Input 
          v-model="searchQuery"
          placeholder="Search currency..."
          class="h-8"
          @click.stop
        />
      </div>
      <SelectGroup>
        <SelectItem 
          v-for="currency in filteredCurrencies" 
          :key="currency.code"
          :value="currency.code"
        >
          {{ currency.code }} - {{ currency.name }}
        </SelectItem>
      </SelectGroup>
      <div v-if="filteredCurrencies.length === 0" class="p-2 text-center text-sm text-muted-foreground">
        No currencies found
      </div>
    </SelectContent>
  </Select>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Input } from '@/Components/ui/input'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

// Local state
const localValue = ref(props.modelValue)
const searchQuery = ref('')

// Common currencies list
const currencies = [
  { code: 'EUR', name: 'Euro' },
  { code: 'USD', name: 'US Dollar' },
  { code: 'GBP', name: 'British Pound' },
  { code: 'CHF', name: 'Swiss Franc' },
  { code: 'JPY', name: 'Japanese Yen' },
  { code: 'CAD', name: 'Canadian Dollar' },
  { code: 'AUD', name: 'Australian Dollar' },
  { code: 'CNY', name: 'Chinese Yuan' },
  { code: 'INR', name: 'Indian Rupee' },
  { code: 'KRW', name: 'South Korean Won' },
  { code: 'SEK', name: 'Swedish Krona' },
  { code: 'NOK', name: 'Norwegian Krone' },
  { code: 'DKK', name: 'Danish Krone' },
  { code: 'PLN', name: 'Polish Zloty' },
  { code: 'CZK', name: 'Czech Koruna' },
  { code: 'HUF', name: 'Hungarian Forint' },
  { code: 'RON', name: 'Romanian Leu' },
  { code: 'BGN', name: 'Bulgarian Lev' },
  { code: 'HRK', name: 'Croatian Kuna' },
  { code: 'RUB', name: 'Russian Ruble' },
  { code: 'TRY', name: 'Turkish Lira' },
  { code: 'BRL', name: 'Brazilian Real' },
  { code: 'MXN', name: 'Mexican Peso' },
  { code: 'ZAR', name: 'South African Rand' },
  { code: 'NZD', name: 'New Zealand Dollar' },
  { code: 'SGD', name: 'Singapore Dollar' },
  { code: 'HKD', name: 'Hong Kong Dollar' },
  { code: 'THB', name: 'Thai Baht' },
  { code: 'MYR', name: 'Malaysian Ringgit' },
  { code: 'IDR', name: 'Indonesian Rupiah' },
  { code: 'PHP', name: 'Philippine Peso' },
  { code: 'VND', name: 'Vietnamese Dong' },
  { code: 'AED', name: 'UAE Dirham' },
  { code: 'SAR', name: 'Saudi Riyal' },
  { code: 'ILS', name: 'Israeli Shekel' },
  { code: 'EGP', name: 'Egyptian Pound' },
  { code: 'NGN', name: 'Nigerian Naira' },
  { code: 'KES', name: 'Kenyan Shilling' },
  { code: 'MAD', name: 'Moroccan Dirham' },
  { code: 'ARS', name: 'Argentine Peso' },
  { code: 'CLP', name: 'Chilean Peso' },
  { code: 'COP', name: 'Colombian Peso' },
  { code: 'PEN', name: 'Peruvian Sol' },
]

// Filtered currencies based on search
const filteredCurrencies = computed(() => {
  if (!searchQuery.value) {
    return currencies
  }
  
  const query = searchQuery.value.toLowerCase()
  return currencies.filter(currency => 
    currency.code.toLowerCase().includes(query) ||
    currency.name.toLowerCase().includes(query)
  )
})

// Watch for model changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

// Watch for local changes
watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
  // Reset search when a value is selected
  searchQuery.value = ''
})
</script>