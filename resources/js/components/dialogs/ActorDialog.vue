<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ operation === 'create' ? t('actors.modal.createTitle') : (actor?.name || 'Loading...') }}
        </DialogTitle>
        <DialogDescription>
          {{ operation === 'create' ? t('actors.modal.createDescription') : (isEditMode ? t('actors.modal.editDescription') : t('actors.modal.viewDescription')) }}
        </DialogDescription>
      </DialogHeader>

      <DialogSkeleton v-if="loading" :fields="8" />

      <div v-else-if="actor || operation === 'create'" class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <div class="flex items-center gap-2">
            <Badge v-if="actor?.warn || (operation === 'create' && actorForm.warn)" variant="destructive">
              <AlertTriangle class="mr-1 h-3 w-3" />
              {{ t('actors.fields.warn') }}
            </Badge>
            <Badge variant="secondary">
              {{ (actor?.phy_person || (operation === 'create' && actorForm.phy_person)) ? t('actors.types.physical') : t('actors.types.legal') }}
            </Badge>
          </div>
          <Button 
            @click="toggleEditMode" 
            variant="outline" 
            size="sm"
            v-if="canWrite && operation !== 'create'"
          >
            <Edit class="mr-2 h-4 w-4" />
            {{ isEditMode ? t('actors.modal.viewMode') : t('actors.modal.editMode') }}
          </Button>
        </div>

        <!-- Show restriction summary if any fields are restricted -->
        <div v-if="hasRestrictedFields" class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4 mb-6">
          <div class="flex items-start gap-2">
            <AlertCircle class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5" />
            <div>
              <p class="font-medium text-orange-800 dark:text-orange-200 text-sm">
                {{ t('Some actor fields are restricted') }}
              </p>
              <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                {{ t('Fields marked with a lock icon cannot be edited based on your user role and actor type. Contact an administrator if you need to modify these fields.') }}
              </p>
            </div>
          </div>
        </div>
        
        <!-- Tabbed Content -->
        <Tabs default-value="main" class="w-full">
          <TabsList class="grid w-full grid-cols-4">
            <TabsTrigger value="main">{{ t('actors.show.tabs.main') }}</TabsTrigger>
            <TabsTrigger value="contact">{{ t('actors.show.tabs.contact') }}</TabsTrigger>
            <TabsTrigger value="other">{{ t('actors.show.tabs.other') }}</TabsTrigger>
            <TabsTrigger value="usedin">{{ t('actors.show.tabs.usedin') }}</TabsTrigger>
          </TabsList>

          <!-- Main Tab -->
          <TabsContent value="main" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <Label>{{ t('actors.fields.name') }}</Label>
                  <div v-if="!isFieldEditable('name')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('name')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('name') }}
                      </div>
                    </div>
                  </div>
                </div>
                <Input
                  v-model="actorForm.name"
                  :disabled="!isFieldEditable('name') || (!isEditMode && operation !== 'create')"
                  :placeholder="t('actors.placeholders.name')"
                  :class="{
                    'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75': !isFieldEditable('name')
                  }"
                />
              </div>
              <div>
                <Label class="mb-2">{{ t('actors.fields.firstName') }}</Label>
                <Input
                  v-model="actorForm.first_name"
                  :disabled="!isEditMode && operation !== 'create'"
                  :placeholder="t('actors.placeholders.firstName')"
                />
              </div>
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.displayName') }}</Label>
              <Input
                v-model="actorForm.display_name"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.displayName')"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.address') }}</Label>
              <Textarea
                v-model="actorForm.address"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.address')"
                rows="3"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('actors.fields.country') }}</Label>
                <AutocompleteInput
                  v-model="actorForm.country"
                  v-model:display-model-value="countryDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="t('actors.placeholders.country')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
              <div>
                <Label class="mb-2">{{ t('actors.fields.nationality') }}</Label>
                <AutocompleteInput
                  v-model="actorForm.nationality"
                  v-model:display-model-value="nationalityDisplay"
                  endpoint="/country/autocomplete"
                  :placeholder="t('actors.placeholders.nationality')"
                  :disabled="!isEditMode && operation !== 'create'"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('actors.fields.language') }}</Label>
                <Input
                  v-model="actorForm.language"
                  :disabled="!isEditMode && operation !== 'create'"
                  placeholder="fr/en/de"
                />
              </div>
              <div>
                <Label class="mb-2">{{ t('actors.fields.function') }}</Label>
                <Input
                  v-model="actorForm.function"
                  :disabled="!isEditMode && operation !== 'create'"
                  :placeholder="t('actors.placeholders.function')"
                />
              </div>
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.employer') }}</Label>
              <AutocompleteInput
                v-model="actorForm.company_id"
                v-model:display-model-value="companyDisplay"
                endpoint="/actor/autocomplete"
                :placeholder="t('actors.placeholders.employer')"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>

            <div v-if="isEditMode" class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="phy_person"
                  v-model="actorForm.phy_person"
                />
                <Label htmlFor="phy_person">{{ t('actors.fields.physicalPerson') }}</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="small_entity"
                  v-model="actorForm.small_entity"
                />
                <Label htmlFor="small_entity">{{ t('actors.fields.smallEntity') }}</Label>
              </div>
            </div>
          </TabsContent>

          <!-- Contact Tab -->
          <TabsContent value="contact" class="space-y-4">
            <div>
              <Label class="mb-2">{{ t('actors.fields.addressMailing') }}</Label>
              <Textarea
                v-model="actorForm.address_mailing"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.addressMailing')"
                rows="3"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.countryMailing') }}</Label>
              <AutocompleteInput
                v-model="actorForm.country_mailing"
                v-model:display-model-value="countryMailingDisplay"
                endpoint="/country/autocomplete"
                :placeholder="t('actors.placeholders.countryMailing')"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.addressBilling') }}</Label>
              <Textarea
                v-model="actorForm.address_billing"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.addressBilling')"
                rows="3"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.countryBilling') }}</Label>
              <AutocompleteInput
                v-model="actorForm.country_billing"
                v-model:display-model-value="countryBillingDisplay"
                endpoint="/country/autocomplete"
                :placeholder="t('actors.placeholders.countryBilling')"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-2">{{ t('actors.fields.email') }}</Label>
                <Input
                  v-model="actorForm.email"
                  type="email"
                  :disabled="!isEditMode && operation !== 'create'"
                  :placeholder="t('actors.placeholders.email')"
                />
              </div>
              <div>
                <Label class="mb-2">{{ t('actors.fields.phone') }}</Label>
                <Input
                  v-model="actorForm.phone"
                  :disabled="!isEditMode && operation !== 'create'"
                  :placeholder="t('actors.placeholders.phone')"
                />
              </div>
            </div>
          </TabsContent>

          <!-- Other Tab -->
          <TabsContent value="other" class="space-y-4">
            <div>
              <Label class="mb-2">{{ t('actors.fields.userName') }}</Label>
              <Input
                v-model="actorForm.login"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.userName')"
              />
            </div>

            <div>
              <Label class="mb-2">
                {{ t('actors.fields.defaultRole') }}
                <span v-if="actorForm.login" class="text-sm text-muted-foreground ml-2">
                  {{ t('actors.fields.roleDisabledHint') }}
                </span>
              </Label>
              <AutocompleteInput
                v-model="actorForm.default_role"
                v-model:display-model-value="defaultRoleDisplay"
                endpoint="/role/autocomplete"
                :placeholder="t('actors.placeholders.defaultRole')"
                :disabled="!isEditMode && operation !== 'create' || !!actorForm.login"
              />
            </div>

            <div v-if="isEditMode" class="flex items-center space-x-2">
              <Checkbox
                id="warn"
                v-model="actorForm.warn"
              />
              <Label htmlFor="warn">{{ t('actors.fields.warn') }}</Label>
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.renewalDiscount') }}</Label>
              <Input
                v-model.number="actorForm.ren_discount"
                type="number"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.fields.discountHint')"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.legalForm') }}</Label>
              <Input
                v-model="actorForm.legal_form"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.legalForm')"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.registrationNo') }}</Label>
              <Input
                v-model="actorForm.registration_no"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.registrationNo')"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.vatNumber') }}</Label>
              <Input
                v-model="actorForm.VAT_number"
                :disabled="!isEditMode && operation !== 'create'"
                :placeholder="t('actors.placeholders.vatNumber')"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.parentCompany') }}</Label>
              <AutocompleteInput
                v-model="actorForm.parent_id"
                v-model:display-model-value="parentDisplay"
                endpoint="/actor/autocomplete"
                :placeholder="t('actors.placeholders.parentCompany')"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>

            <div>
              <Label class="mb-2">{{ t('actors.fields.workSite') }}</Label>
              <AutocompleteInput
                v-model="actorForm.site_id"
                v-model:display-model-value="siteDisplay"
                endpoint="/actor/autocomplete"
                :placeholder="t('actors.placeholders.workSite')"
                :disabled="!isEditMode && operation !== 'create'"
              />
            </div>
          </TabsContent>

          <!-- Used In Tab -->
          <TabsContent value="usedin" @update:selected="loadUsedInData">
            <div v-if="loadingUsedIn" class="flex justify-center py-8">
              <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
            <div v-else class="space-y-6">
              <!-- Matter Dependencies -->
              <div>
                <h3 class="font-semibold mb-3">{{ t('actors.show.matterDependencies') }}</h3>
                <div v-if="usedInData?.matter_dependencies?.length" class="space-y-2">
                  <div v-for="(matters, role) in groupedMatterDependencies" :key="role" class="border rounded-lg p-3">
                    <div class="font-medium mb-2">{{ role }}</div>
                    <div class="flex flex-wrap gap-2">
                      <Badge
                        v-for="matter in matters"
                        :key="matter.matter_id"
                        variant="secondary"
                        class="cursor-pointer"
                        @click="navigateToMatter(matter.matter_id)"
                      >
                        {{ matter.matter.uid }}
                      </Badge>
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted-foreground">
                  {{ t('actors.show.noDependencies') }}
                </div>
              </div>

              <!-- Inter-Actor Dependencies -->
              <div>
                <h3 class="font-semibold mb-3">{{ t('actors.show.interActorDependencies') }}</h3>
                <div v-if="usedInData?.other_dependencies?.length" class="space-y-2">
                  <div v-for="(actors, dependency) in groupedOtherDependencies" :key="dependency" class="border rounded-lg p-3">
                    <div class="font-medium mb-2">{{ dependency }}</div>
                    <div class="flex flex-wrap gap-2">
                      <Button
                        v-for="relatedActor in actors"
                        :key="relatedActor.id"
                        variant="link"
                        size="sm"
                        class="p-0 h-auto"
                        @click="$emit('show-actor', relatedActor.id)"
                      >
                        {{ relatedActor.Actor }}
                      </Button>
                    </div>
                  </div>
                </div>
                <div v-else class="text-muted-foreground">
                  {{ t('actors.show.noDependencies') }}
                </div>
              </div>
            </div>
          </TabsContent>
        </Tabs>
      </div>

      <DialogFooter>
        <div class="flex justify-between w-full">
          <Button
              v-if="canDelete && operation !== 'create'"
              @click="confirmDelete"
              variant="destructive"
          >
            <Trash2 class="mr-2 h-4 w-4" />
            {{ t('actors.show.delete') }}
          </Button>

          <div class="flex gap-2">
            <Button @click="$emit('update:open', false)" variant="outline">
              {{ t('actions.cancel') }}
            </Button>
            <Button
                v-if="isEditMode || operation === 'create'"
                @click="handleSubmit"
                :disabled="actorForm.processing"
            >
              <Loader2 v-if="actorForm.processing" class="mr-2 h-4 w-4 animate-spin" />
              {{ operation === 'create' ? t('actors.modal.create') : t('actors.modal.save') }}
            </Button>
          </div>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('actors.show.deleteTitle')"
    :message="t('actors.show.deleteDescription', { name: actor?.name })"
    :confirm-text="t('actors.show.confirmDelete')"
    :cancel-text="t('actions.cancel')"
    variant="destructive"
    @confirm="deleteActor"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { 
  Loader2,
  AlertTriangle,
  Edit,
  Trash2,
  Lock,
  AlertCircle
} from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'
import DialogSkeleton from '@/Components/ui/skeleton/DialogSkeleton.vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Label } from '@/Components/ui/label'
import { Input } from '@/Components/ui/input'
import { Textarea } from '@/Components/ui/textarea'
import { Checkbox } from '@/Components/ui/checkbox'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/Components/ui/tabs'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  actorId: {
    type: [Number, String, null],
    default: null
  },
  operation: {
    type: String,
    default: 'view',
    validator: (value) => ['view', 'edit', 'create'].includes(value)
  }
})

const emit = defineEmits(['update:open', 'show-actor', 'success'])

const { t } = useI18n()
const page = usePage()
const loading = ref(false)
const actor = ref(null)
const operation = ref(props.operation)
const isEditMode = ref(false)
const loadingUsedIn = ref(false)
const usedInData = ref(null)
const showDeleteDialog = ref(false)

// Form for create/edit operations
const actorForm = useForm({
  name: '',
  first_name: '',
  display_name: '',
  address: '',
  country: '',
  nationality: '',
  language: '',
  function: '',
  company_id: '',
  phy_person: true,
  small_entity: false,
  address_mailing: '',
  country_mailing: '',
  address_billing: '',
  country_billing: '',
  email: '',
  phone: '',
  login: '',
  default_role: '',
  warn: false,
  ren_discount: null,
  legal_form: '',
  registration_no: '',
  VAT_number: '',
  parent_id: '',
  site_id: ''
})

// Permissions composables
const isCreateMode = computed(() => operation.value === 'create')
const actorType = computed(() => {
  if (actor.value) {
    return actor.value.phy_person ? 'individual' : 'company'
  }
  return actorForm.phy_person ? 'individual' : 'company'
})

// Use simple permissions composable
const { role, isAdmin, canWrite, hasRole } = usePermissions()

// Simple permission logic - either can edit all fields or none
const canEdit = computed(() => canWrite.value)
const canDelete = computed(() => isAdmin.value)

// Ultra-simple field editing - all fields follow same rule
const isFieldEditable = (field) => canWrite.value

// Simple restriction message - same for all fields
const getTranslatedRestrictionReason = (fieldName) => {
  return t('You do not have permission to modify actor data')
}

const showRestrictionFeedback = (fieldName) => {
  const reason = getTranslatedRestrictionReason(fieldName)
  if (reason) {
    const notification = document.createElement('div')
    notification.className = 'fixed top-4 right-4 bg-orange-100 dark:bg-orange-900 border border-orange-200 dark:border-orange-800 text-orange-800 dark:text-orange-200 px-4 py-2 rounded-lg shadow-lg z-50 max-w-sm'
    notification.innerHTML = `
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <div>
          <p class="font-medium text-sm">${t('Field Restricted')}</p>
          <p class="text-xs mt-1">${reason}</p>
        </div>
      </div>
    `
    document.body.appendChild(notification)
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 5000)
  }
}

// Check for restricted fields
// Simple permission checks - either can edit everything or nothing
const hasRestrictedFields = computed(() => {
  return !canWrite.value
})

const hasEditableFields = computed(() => {
  return canWrite.value
})

// Display values for autocomplete fields
const countryDisplay = ref('')
const nationalityDisplay = ref('')
const companyDisplay = ref('')
const countryMailingDisplay = ref('')
const countryBillingDisplay = ref('')
const parentDisplay = ref('')
const siteDisplay = ref('')
const defaultRoleDisplay = ref('')


// Group matter dependencies by role
const groupedMatterDependencies = computed(() => {
  if (!usedInData.value?.matter_dependencies) return {}
  
  return usedInData.value.matter_dependencies.reduce((acc, item) => {
    const role = item.role.name
    if (!acc[role]) acc[role] = []
    acc[role].push(item)
    return acc
  }, {})
})

// Group other dependencies by type
const groupedOtherDependencies = computed(() => {
  if (!usedInData.value?.other_dependencies) return {}
  
  return usedInData.value.other_dependencies.reduce((acc, item) => {
    const dep = item.Dependency
    if (!acc[dep]) acc[dep] = []
    acc[dep].push(item)
    return acc
  }, {})
})

// Watch for actorId changes and fetch data
watch(() => props.actorId, (newId) => {
  if (newId && props.open) {
    fetchActorData(newId)
  }
}, { immediate: true })

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen) {
    operation.value = props.operation
    isEditMode.value = props.operation === 'create' || props.operation === 'edit'
    if (props.actorId && props.operation !== 'create') {
      fetchActorData(props.actorId)
    } else if (props.operation === 'create') {
      resetActor()
    }
  } else if (!isOpen) {
    // Reset state when modal closes
    actor.value = null
    isEditMode.value = false
    usedInData.value = null
  }
})

async function fetchActorData(id) {
  loading.value = true
  try {
    const response = await fetch(`/actor/${id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      actor.value = data.actor
      populateForm()
    }
  } catch (error) {
    console.error('Failed to load actor:', error)
  } finally {
    loading.value = false
  }
}

async function loadUsedInData() {
  if (usedInData.value || !actor.value) return // Already loaded or no actor
  
  loadingUsedIn.value = true
  try {
    const response = await fetch(`/actor/${actor.value.id}/usedin`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      usedInData.value = await response.json()
    }
  } catch (error) {
    console.error('Failed to load used in data:', error)
  } finally {
    loadingUsedIn.value = false
  }
}

function resetActor() {
  actorForm.reset()
  actor.value = {
    name: '',
    first_name: '',
    phy_person: true,
    small_entity: false,
    warn: false
  }
  
  // Clear display values for autocomplete fields
  countryDisplay.value = ''
  nationalityDisplay.value = ''
  companyDisplay.value = ''
  countryMailingDisplay.value = ''
  countryBillingDisplay.value = ''
  parentDisplay.value = ''
  siteDisplay.value = ''
  defaultRoleDisplay.value = ''
}

function populateForm() {
  if (!actor.value) return
  
  actorForm.name = actor.value.name || ''
  actorForm.first_name = actor.value.first_name || ''
  actorForm.display_name = actor.value.display_name || ''
  actorForm.address = actor.value.address || ''
  actorForm.country = actor.value.country || ''
  actorForm.nationality = actor.value.nationality || ''
  actorForm.language = actor.value.language || ''
  actorForm.function = actor.value.function || ''
  actorForm.company_id = actor.value.company_id || ''
  actorForm.phy_person = Boolean(actor.value.phy_person)
  actorForm.small_entity = Boolean(actor.value.small_entity)
  actorForm.address_mailing = actor.value.address_mailing || ''
  actorForm.country_mailing = actor.value.country_mailing || ''
  actorForm.address_billing = actor.value.address_billing || ''
  actorForm.country_billing = actor.value.country_billing || ''
  actorForm.email = actor.value.email || ''
  actorForm.phone = actor.value.phone || ''
  actorForm.login = actor.value.login || ''
  actorForm.default_role = actor.value.default_role || ''
  actorForm.warn = Boolean(actor.value.warn)
  actorForm.ren_discount = actor.value.ren_discount
  actorForm.legal_form = actor.value.legal_form || ''
  actorForm.registration_no = actor.value.registration_no || ''
  actorForm.VAT_number = actor.value.VAT_number || ''
  actorForm.parent_id = actor.value.parent_id || ''
  actorForm.site_id = actor.value.site_id || ''
  
  // Set display values for autocomplete fields
  countryDisplay.value = actor.value.countryInfo?.name || ''
  nationalityDisplay.value = actor.value.nationalityInfo?.name || ''
  companyDisplay.value = actor.value.company?.name || ''
  countryMailingDisplay.value = actor.value.country_mailingInfo?.name || ''
  countryBillingDisplay.value = actor.value.country_billingInfo?.name || ''
  parentDisplay.value = actor.value.parent?.name || ''
  siteDisplay.value = actor.value.site?.name || ''
  defaultRoleDisplay.value = actor.value.droleInfo?.name || ''
}

function toggleEditMode() {
  if (!isEditMode.value) {
    populateForm()
  }
  isEditMode.value = !isEditMode.value
}

function navigateToMatter(matterId) {
  router.visit(`/matter/${matterId}`)
}

function confirmDelete() {
  showDeleteDialog.value = true
}

function handleSubmit() {
  if (operation.value === 'create') {
    actorForm.post('/actor', {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  } else {
    actorForm.put(`/actor/${props.actorId}`, {
      onSuccess: () => {
        emit('success')
        emit('update:open', false)
      }
    })
  }
}

async function deleteActor() {
  try {
    await router.delete(`/actor/${actor.value.id}`, {
      onSuccess: () => {
        emit('update:open', false)
        emit('success')
      }
    })
  } catch (error) {
    console.error('Failed to delete actor:', error)
  }
}
</script>