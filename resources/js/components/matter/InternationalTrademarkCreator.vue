<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>{{ t('Create National Trademark Matters') }}</DialogTitle>
        <DialogDescription>
          {{ t('Create national phase entries from international trademark') }} {{ matter.uid }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6">
        <!-- Validation Status -->
        <div v-if="validationData" class="space-y-2">
          <div v-if="!validationData.valid" class="bg-destructive/10 border border-destructive rounded-lg p-4">
            <h4 class="font-medium text-destructive mb-2">{{ t('Validation Errors') }}</h4>
            <ul class="list-disc list-inside text-sm text-destructive">
              <li v-for="error in validationData.errors" :key="error">{{ error }}</li>
            </ul>
          </div>

          <div v-if="validationData.warnings && validationData.warnings.length" class="bg-warning/10 border border-warning rounded-lg p-4">
            <h4 class="font-medium text-warning mb-2">{{ t('Warnings') }}</h4>
            <ul class="list-disc list-inside text-sm text-warning">
              <li v-for="warning in validationData.warnings" :key="warning">{{ warning }}</li>
            </ul>
          </div>
        </div>

        <!-- Country Selection -->
        <div v-if="validationData?.valid">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">{{ t('Select Target Countries') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <!-- Search and Multi-Select -->
                <FormField :label="t('Countries')">
                  <div class="space-y-3">
                    <Input
                      v-model="countrySearch"
                      :placeholder="t('Search countries...')"
                      class="w-full"
                    />
                    
                    <!-- Available Countries Grid -->
                    <div class="grid gap-2 max-h-48 overflow-y-auto border rounded-lg p-3">
                      <label
                        v-for="country in filteredCountries"
                        :key="country.iso"
                        class="flex items-center space-x-2 cursor-pointer hover:bg-muted/50 p-2 rounded"
                      >
                        <Checkbox
                          :checked="selectedCountries.includes(country.iso)"
                          @update:model-value="toggleCountry(country.iso)"
                        />
                        <span class="text-sm">
                          {{ country.name }} ({{ country.iso }})
                        </span>
                        <Badge v-if="existingMatters[country.iso]" variant="secondary" class="ml-auto">
                          {{ t('Exists') }}
                        </Badge>
                      </label>
                    </div>
                  </div>
                </FormField>

                <!-- Quick Selection Buttons -->
                <div class="flex gap-2 flex-wrap">
                  <Button variant="outline" size="sm" @click="selectCommonCountries">
                    {{ t('Select Common (US, EP, CN, JP)') }}
                  </Button>
                  <Button variant="outline" size="sm" @click="selectAll">
                    {{ t('Select All Available') }}
                  </Button>
                  <Button variant="outline" size="sm" @click="clearSelection">
                    {{ t('Clear Selection') }}
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Preview Section -->
        <div v-if="selectedCountries.length > 0">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">{{ t('Preview - Matters to Create') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <!-- Summary -->
                <div class="grid grid-cols-2 gap-4 text-center">
                  <div class="bg-primary/10 rounded-lg p-4">
                    <div class="text-2xl font-bold text-primary">{{ estimation?.to_create || 0 }}</div>
                    <div class="text-sm text-muted-foreground">{{ t('To Create') }}</div>
                  </div>
                  <div class="bg-warning/10 rounded-lg p-4">
                    <div class="text-2xl font-bold text-warning">{{ estimation?.to_skip || 0 }}</div>
                    <div class="text-sm text-muted-foreground">{{ t('To Skip') }}</div>
                  </div>
                </div>

                <!-- Matter List Preview -->
                <div class="space-y-2">
                  <h4 class="font-medium">{{ t('New matters will be created') }}:</h4>
                  <div class="grid gap-2">
                    <div
                      v-for="countryIso in selectedCountries"
                      :key="countryIso"
                      class="flex items-center justify-between p-2 border rounded"
                    >
                      <span class="font-mono text-sm">
                        {{ matter.caseref }}{{ countryIso }}
                      </span>
                      <Badge v-if="existingMatters[countryIso]" variant="outline">
                        {{ t('Skip') }}
                      </Badge>
                      <Badge v-else variant="default">
                        {{ t('Create') }}
                      </Badge>
                    </div>
                  </div>
                </div>

                <!-- Data to Copy Options -->
                <div>
                  <h4 class="font-medium mb-2">{{ t('Data to copy from') }} {{ matter.uid }}:</h4>
                  <div class="grid gap-3">
                    <label class="flex items-center space-x-2">
                      <Checkbox v-model="copyOptions.actors" />
                      <span class="text-sm">{{ t('Actors (Applicants, Agents)') }}</span>
                    </label>
                    <label class="flex items-center space-x-2">
                      <Checkbox v-model="copyOptions.classifiers" />
                      <span class="text-sm">{{ t('Classifications (NICE, Titles)') }}</span>
                    </label>
                    <label class="flex items-center space-x-2">
                      <Checkbox v-model="copyOptions.events" />
                      <span class="text-sm">{{ t('Events (Filing, Registration)') }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Progress Section -->
        <div v-if="isCreating">
          <Card>
            <CardContent class="pt-6">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span>{{ t('Creating national trademark matters...') }}</span>
                  <span>{{ creationProgress }}/{{ selectedCountries.length }}</span>
                </div>
                <Progress :value="(creationProgress / selectedCountries.length) * 100" />
                <div v-if="currentCountry" class="text-sm text-muted-foreground">
                  {{ t('Currently creating') }}: {{ getCountryName(currentCountry) }}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Results Section -->
        <div v-if="creationResults">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">{{ t('Creation Results') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <!-- Success Summary -->
                <div v-if="creationResults.created.length" class="space-y-2">
                  <h4 class="font-medium text-success">{{ t('Successfully Created') }} ({{ creationResults.created.length }})</h4>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div
                      v-for="created in creationResults.created"
                      :key="created.matter_id"
                      class="flex items-center space-x-2 p-2 bg-success/10 rounded"
                    >
                      <CheckCircle class="h-4 w-4 text-success" />
                      <Link :href="`/matter/${created.matter_id}`" class="text-sm font-mono hover:underline">
                        {{ created.uid }}
                      </Link>
                    </div>
                  </div>
                </div>

                <!-- Skipped Summary -->
                <div v-if="creationResults.skipped.length" class="space-y-2">
                  <h4 class="font-medium text-warning">{{ t('Skipped') }} ({{ creationResults.skipped.length }})</h4>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <div
                      v-for="skipped in creationResults.skipped"
                      :key="skipped.country"
                      class="flex items-center space-x-2 p-2 bg-warning/10 rounded"
                    >
                      <AlertCircle class="h-4 w-4 text-warning" />
                      <span class="text-sm">{{ getCountryName(skipped.country) }}</span>
                      <span class="text-xs text-muted-foreground">{{ skipped.reason }}</span>
                    </div>
                  </div>
                </div>

                <!-- Errors Summary -->
                <div v-if="creationResults.errors.length" class="space-y-2">
                  <h4 class="font-medium text-destructive">{{ t('Errors') }} ({{ creationResults.errors.length }})</h4>
                  <div class="space-y-2">
                    <div
                      v-for="error in creationResults.errors"
                      :key="error.country"
                      class="flex items-start space-x-2 p-2 bg-destructive/10 rounded"
                    >
                      <XCircle class="h-4 w-4 text-destructive mt-0.5" />
                      <div>
                        <div class="text-sm font-medium">{{ getCountryName(error.country) }}</div>
                        <div class="text-xs text-muted-foreground">{{ error.error }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="closeDialog" :disabled="isCreating">
          {{ t('Close') }}
        </Button>
        <Button
          v-if="!creationResults"
          @click="createNationalMatters"
          :disabled="!canCreate || isCreating"
          class="min-w-32"
        >
          <Loader2 v-if="isCreating" class="mr-2 h-4 w-4 animate-spin" />
          {{ isCreating ? t('Creating...') : t('Create {count} Matter(s)', { count: selectedCountries.length }) }}
        </Button>
        <Button
          v-if="creationResults"
          @click="viewFamily"
          variant="default"
        >
          {{ t('View Family') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { CheckCircle, AlertCircle, XCircle, Loader2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Checkbox } from '@/Components/ui/checkbox'
import { Badge } from '@/Components/ui/badge'
import { Progress } from '@/Components/ui/progress'
import FormField from '@/Components/ui/form/FormField.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matter: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()

// State
const loading = ref(false)
const availableCountries = ref([])
const existingMatters = ref({})
const validationData = ref(null)
const estimation = ref(null)
const countrySearch = ref('')
const selectedCountries = ref([])
const isCreating = ref(false)
const creationProgress = ref(0)
const currentCountry = ref('')
const creationResults = ref(null)

// Copy options
const copyOptions = ref({
  actors: true,
  classifiers: true,
  events: true
})

// Computed
const filteredCountries = computed(() => {
  if (!countrySearch.value) return availableCountries.value
  
  const search = countrySearch.value.toLowerCase()
  return availableCountries.value.filter(country => 
    country.name.toLowerCase().includes(search) ||
    country.iso.toLowerCase().includes(search)
  )
})

const canCreate = computed(() => {
  return selectedCountries.value.length > 0 && 
         validationData.value?.valid && 
         !isCreating.value &&
         !creationResults.value
})

// Methods
function toggleCountry(countryIso) {
  const index = selectedCountries.value.indexOf(countryIso)
  if (index > -1) {
    selectedCountries.value.splice(index, 1)
  } else {
    selectedCountries.value.push(countryIso)
  }
}

function selectCommonCountries() {
  const common = ['US', 'EP', 'CN', 'JP']
  selectedCountries.value = [
    ...new Set([
      ...selectedCountries.value,
      ...common.filter(iso => 
        availableCountries.value.some(c => c.iso === iso) &&
        !existingMatters.value[iso]
      )
    ])
  ]
}

function selectAll() {
  selectedCountries.value = availableCountries.value
    .filter(country => !existingMatters.value[country.iso])
    .map(country => country.iso)
}

function clearSelection() {
  selectedCountries.value = []
}

function getCountryName(iso) {
  const country = availableCountries.value.find(c => c.iso === iso)
  return country ? country.name : iso
}

async function loadCountryData() {
  loading.value = true
  try {
    const response = await fetch(`/matter/${props.matter.id}/international-countries`)
    const data = await response.json()
    
    validationData.value = data.validation
    availableCountries.value = data.available_countries || []
    
    // Convert existing matters to object for quick lookup
    const existing = data.existing_matters || []
    existingMatters.value = Object.fromEntries(
      existing.map(matter => [matter.country, matter])
    )
    
    estimation.value = data.estimation

  } catch (error) {
    console.error('Failed to load country data:', error)
  } finally {
    loading.value = false
  }
}

async function createNationalMatters() {
  if (!canCreate.value) return

  isCreating.value = true
  creationProgress.value = 0
  currentCountry.value = ''

  try {
    const response = await fetch(`/matter/${props.matter.id}/create-national`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        parent_id: props.matter.id,
        countries: selectedCountries.value,
        copy_options: copyOptions.value
      })
    })

    const data = await response.json()

    if (data.success) {
      creationResults.value = data.results
      emit('success')
    } else {
      throw new Error(data.message || 'Creation failed')
    }

  } catch (error) {
    console.error('Failed to create national matters:', error)
    // Show error to user (could add toast notification here)
  } finally {
    isCreating.value = false
    currentCountry.value = ''
  }
}

function viewFamily() {
  router.visit(`/matter?Ref=${props.matter.caseref}`)
}

function closeDialog() {
  emit('update:open', false)
  // Reset state when closing
  selectedCountries.value = []
  creationResults.value = null
  countrySearch.value = ''
}

// Watch for dialog open to load data
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    loadCountryData()
  }
})

// Update estimation when selection changes
watch(selectedCountries, (newSelection) => {
  if (newSelection.length > 0 && availableCountries.value.length > 0) {
    const existing = Object.keys(existingMatters.value)
    const toCreate = newSelection.filter(iso => !existing.includes(iso))
    const toSkip = newSelection.filter(iso => existing.includes(iso))
    
    estimation.value = {
      total_requested: newSelection.length,
      to_create: toCreate.length,
      to_skip: toSkip.length,
      estimated_time_seconds: toCreate.length * 3
    }
  }
})

onMounted(() => {
  if (props.open) {
    loadCountryData()
  }
})
</script>