<template>
  <MainLayout :title="actor.name">
    <div class="container mx-auto px-4 py-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">{{ actor.name }}</h1>
        <p class="text-muted-foreground">{{ t('actors.show.editDetails') }}</p>
      </div>

      <Tabs default-value="main" class="w-full" @update:model-value="handleTabChange">
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="main">{{ t('actors.show.tabs.main') }}</TabsTrigger>
          <TabsTrigger value="contact">{{ t('actors.show.tabs.contact') }}</TabsTrigger>
          <TabsTrigger value="other">{{ t('actors.show.tabs.other') }}</TabsTrigger>
          <TabsTrigger value="usedin">{{ t('actors.show.tabs.usedin') }}</TabsTrigger>
        </TabsList>

        <!-- Main Tab -->
        <TabsContent value="main">
          <Card>
            <CardContent class="pt-6">
              <!-- Show restriction summary if any fields are restricted -->
              <div v-if="hasRestrictedFieldsInActor" class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4 mb-6">
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
              
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <Label htmlFor="name">{{ t('actors.fields.name') }}</Label>
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
                    <EditableField
                      id="name"
                      :value="actor.name"
                      :update-url="`/actor/${actor.id}`"
                      field-name="name"
                      :disabled="!isFieldEditable('name')"
                      :class="getFieldClasses('name')"
                      @update="handleUpdate"
                    />
                  </div>
                  <div>
                    <div class="flex items-center gap-2 mb-2">
                      <Label htmlFor="first_name">{{ t('actors.fields.firstName') }}</Label>
                      <div v-if="!isFieldEditable('first_name')" class="flex items-center gap-1">
                        <Lock class="h-3 w-3 text-muted-foreground" />
                        <div class="relative group">
                          <AlertCircle 
                            class="h-3 w-3 text-orange-500 cursor-help" 
                            @click="showRestrictionFeedback('first_name')"
                          />
                          <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            {{ getTranslatedRestrictionReason('first_name') }}
                          </div>
                        </div>
                      </div>
                    </div>
                    <EditableField
                      id="first_name"
                      :value="actor.first_name"
                      :update-url="`/actor/${actor.id}`"
                      field-name="first_name"
                      :placeholder="'-'"
                      :disabled="!isFieldEditable('first_name')"
                      :class="getFieldClasses('first_name')"
                      @update="handleUpdate"
                    />
                  </div>
                </div>

                <div>
                  <Label htmlFor="display_name">{{ t('actors.fields.displayName') }}</Label>
                  <EditableField
                    id="display_name"
                    :value="actor.display_name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="display_name"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="address">{{ t('actors.fields.address') }}</Label>
                  <EditableField
                    id="address"
                    :value="actor.address"
                    :update-url="`/actor/${actor.id}`"
                    field-name="address"
                    type="textarea"
                    @update="handleUpdate"
                  />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="country">{{ t('actors.fields.country') }}</Label>
                    <EditableField
                      id="country"
                      :value="actor.countryInfo?.name"
                      :update-url="`/actor/${actor.id}`"
                      field-name="country"
                      type="autocomplete"
                      autocomplete-url="/country/autocomplete"
                      :placeholder="'-'"
                      @update="handleUpdate"
                    />
                  </div>
                  <div>
                    <Label htmlFor="nationality">{{ t('actors.fields.nationality') }}</Label>
                    <EditableField
                      id="nationality"
                      :value="actor.nationalityInfo?.name"
                      :update-url="`/actor/${actor.id}`"
                      field-name="nationality"
                      type="autocomplete"
                      autocomplete-url="/country/autocomplete"
                      :placeholder="'-'"
                      @update="handleUpdate"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="language">{{ t('actors.fields.language') }}</Label>
                    <EditableField
                      id="language"
                      :value="actor.language"
                      :update-url="`/actor/${actor.id}`"
                      field-name="language"
                      :placeholder="'fr/en/de'"
                      @update="handleUpdate"
                    />
                  </div>
                  <div>
                    <Label htmlFor="function">{{ t('actors.fields.function') }}</Label>
                    <EditableField
                      id="function"
                      :value="actor.function"
                      :update-url="`/actor/${actor.id}`"
                      field-name="function"
                      :placeholder="'-'"
                      @update="handleUpdate"
                    />
                  </div>
                </div>

                <div>
                  <Label htmlFor="company_id">{{ t('actors.fields.employer') }}</Label>
                  <EditableField
                    id="company_id"
                    :value="actor.company?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="company_id"
                    type="autocomplete"
                    autocomplete-url="/actor/autocomplete"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div class="flex items-center space-x-4">
                  <div class="flex items-center space-x-2">
                    <Checkbox
                      id="phy_person"
                      :checked="actor.phy_person"
                      :disabled="!isFieldEditable('phy_person')"
                      @update:checked="updateCheckbox('phy_person', $event)"
                    />
                    <Label htmlFor="phy_person" :class="{ 'text-muted-foreground': !isFieldEditable('phy_person') }">
                      {{ t('actors.fields.physicalPerson') }}
                    </Label>
                    <div v-if="!isFieldEditable('phy_person')" class="flex items-center gap-1">
                      <Lock class="h-3 w-3 text-muted-foreground" />
                      <div class="relative group">
                        <AlertCircle 
                          class="h-3 w-3 text-orange-500 cursor-help" 
                          @click="showRestrictionFeedback('phy_person')"
                        />
                        <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                          {{ getTranslatedRestrictionReason('phy_person') }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <Checkbox
                      id="small_entity"
                      :checked="actor.small_entity"
                      :disabled="!isFieldEditable('small_entity')"
                      @update:checked="updateCheckbox('small_entity', $event)"
                    />
                    <Label htmlFor="small_entity" :class="{ 'text-muted-foreground': !isFieldEditable('small_entity') }">
                      {{ t('actors.fields.smallEntity') }}
                    </Label>
                    <div v-if="!isFieldEditable('small_entity')" class="flex items-center gap-1">
                      <Lock class="h-3 w-3 text-muted-foreground" />
                      <div class="relative group">
                        <AlertCircle 
                          class="h-3 w-3 text-orange-500 cursor-help" 
                          @click="showRestrictionFeedback('small_entity')"
                        />
                        <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                          {{ getTranslatedRestrictionReason('small_entity') }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Contact Tab -->
        <TabsContent value="contact">
          <Card>
            <CardContent class="pt-6">
              <div class="space-y-4">
                <div>
                  <Label htmlFor="address_mailing">{{ t('actors.fields.addressMailing') }}</Label>
                  <EditableField
                    id="address_mailing"
                    :value="actor.address_mailing"
                    :update-url="`/actor/${actor.id}`"
                    field-name="address_mailing"
                    type="textarea"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="country_mailing">{{ t('actors.fields.countryMailing') }}</Label>
                  <EditableField
                    id="country_mailing"
                    :value="actor.country_mailingInfo?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="country_mailing"
                    type="autocomplete"
                    autocomplete-url="/country/autocomplete"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="address_billing">{{ t('actors.fields.addressBilling') }}</Label>
                  <EditableField
                    id="address_billing"
                    :value="actor.address_billing"
                    :update-url="`/actor/${actor.id}`"
                    field-name="address_billing"
                    type="textarea"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="country_billing">{{ t('actors.fields.countryBilling') }}</Label>
                  <EditableField
                    id="country_billing"
                    :value="actor.country_billingInfo?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="country_billing"
                    type="autocomplete"
                    autocomplete-url="/country/autocomplete"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="email">{{ t('actors.fields.email') }}</Label>
                    <EditableField
                      id="email"
                      :value="actor.email"
                      :update-url="`/actor/${actor.id}`"
                      field-name="email"
                      type="email"
                      :placeholder="'-'"
                      @update="handleUpdate"
                    />
                  </div>
                  <div>
                    <Label htmlFor="phone">{{ t('actors.fields.phone') }}</Label>
                    <EditableField
                      id="phone"
                      :value="actor.phone"
                      :update-url="`/actor/${actor.id}`"
                      field-name="phone"
                      :placeholder="'-'"
                      @update="handleUpdate"
                    />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Other Tab -->
        <TabsContent value="other">
          <Card>
            <CardContent class="pt-6">
              <div class="space-y-4">
                <div>
                  <Label htmlFor="login">{{ t('actors.fields.userName') }}</Label>
                  <EditableField
                    id="login"
                    :value="actor.login"
                    :update-url="`/actor/${actor.id}`"
                    field-name="login"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="default_role">
                    {{ t('actors.fields.defaultRole') }}
                    <span v-if="actor.login" class="text-sm text-muted-foreground ml-2">
                      {{ t('actors.fields.roleDisabledHint') }}
                    </span>
                  </Label>
                  <EditableField
                    id="default_role"
                    :value="actor.droleInfo?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="default_role"
                    type="autocomplete"
                    autocomplete-url="/role/autocomplete"
                    :placeholder="'-'"
                    :disabled="!!actor.login"
                    @update="handleUpdate"
                  />
                </div>

                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="warn"
                    :checked="actor.warn"
                    @update:checked="updateCheckbox('warn', $event)"
                  />
                  <Label htmlFor="warn">{{ t('actors.fields.warn') }}</Label>
                </div>

                <div>
                  <Label htmlFor="ren_discount">{{ t('actors.fields.renewalDiscount') }}</Label>
                  <EditableField
                    id="ren_discount"
                    :value="actor.ren_discount"
                    :update-url="`/actor/${actor.id}`"
                    field-name="ren_discount"
                    type="number"
                    :placeholder="t('actors.fields.discountHint')"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="legal_form">{{ t('actors.fields.legalForm') }}</Label>
                  <EditableField
                    id="legal_form"
                    :value="actor.legal_form"
                    :update-url="`/actor/${actor.id}`"
                    field-name="legal_form"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="registration_no">{{ t('actors.fields.registrationNo') }}</Label>
                  <EditableField
                    id="registration_no"
                    :value="actor.registration_no"
                    :update-url="`/actor/${actor.id}`"
                    field-name="registration_no"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="VAT_number">{{ t('actors.fields.vatNumber') }}</Label>
                  <EditableField
                    id="VAT_number"
                    :value="actor.VAT_number"
                    :update-url="`/actor/${actor.id}`"
                    field-name="VAT_number"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="parent_id">{{ t('actors.fields.parentCompany') }}</Label>
                  <EditableField
                    id="parent_id"
                    :value="actor.parent?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="parent_id"
                    type="autocomplete"
                    autocomplete-url="/actor/autocomplete"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>

                <div>
                  <Label htmlFor="site_id">{{ t('actors.fields.workSite') }}</Label>
                  <EditableField
                    id="site_id"
                    :value="actor.site?.name"
                    :update-url="`/actor/${actor.id}`"
                    field-name="site_id"
                    type="autocomplete"
                    autocomplete-url="/actor/autocomplete"
                    :placeholder="'-'"
                    @update="handleUpdate"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Used In Tab -->
        <TabsContent value="usedin">
          <Card>
            <CardContent class="pt-6">
              <UsedInSkeleton v-if="loadingUsedIn" />
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
                        <Link
                          v-for="actor in actors"
                          :key="actor.id"
                          :href="`/actor/${actor.id}`"
                          class="text-primary hover:underline"
                        >
                          {{ actor.Actor }}
                        </Link>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted-foreground">
                    {{ t('actors.show.noDependencies') }}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      <!-- Actions -->
      <div class="mt-6 flex justify-between">
        <Button @click="router.visit('/actor')" variant="outline">
          <ArrowLeft class="mr-2 h-4 w-4" />
          {{ t('actors.show.backToList') }}
        </Button>
        <Button v-if="canDeleteActor(actor)" @click="confirmDelete" variant="destructive">
          <Trash2 class="mr-2 h-4 w-4" />
          {{ t('actors.show.delete') }}
        </Button>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      v-model:open="showDeleteDialog"
      :title="t('actors.show.deleteTitle')"
      :message="t('actors.show.deleteDescription', { name: actor.name })"
      :confirm-text="t('actors.show.confirmDelete')"
      :cancel-text="t('actions.cancel')"
      variant="destructive"
      @confirm="deleteActor"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { 
  ArrowLeft,
  Trash2,
  Loader2,
  Lock,
  AlertCircle
} from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import MainLayout from '@/Layouts/MainLayout.vue'
import EditableField from '@/Components/ui/EditableField.vue'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'
import UsedInSkeleton from '@/Components/ui/skeleton/UsedInSkeleton.vue'
import { Card, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import { Checkbox } from '@/Components/ui/checkbox'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/Components/ui/tabs'

const props = defineProps({
  actor: Object,
  comments: Object,
})

const { t } = useI18n()
const page = usePage()
const showDeleteDialog = ref(false)
const loadingUsedIn = ref(false)
const usedInData = ref(null)

// Use simple permissions composable
const { role, isAdmin, isClient, canWrite, canRead, hasRole } = usePermissions()

// Define restricted fields by category
const financialFields = ['VAT_number', 'ren_discount', 'registration_no']
const loginFields = ['login', 'default_role', 'password', 'remember_token']
const systemFields = ['created_at', 'updated_at', 'creator', 'updater']

// Simple field permission logic
const isFieldEditable = (field) => {
  // Admin can edit everything
  if (isAdmin.value) return true
  
  // Read-only and clients can't edit anything
  if (hasRole('DBRO') || hasRole('CLI')) return false
  
  // DBRW users have restrictions
  if (hasRole('DBRW')) {
    if (financialFields.includes(field)) return false
    if (loginFields.includes(field)) return false
    if (systemFields.includes(field)) return false
    return true
  }
  
  return false
}

// Simple restriction messages
const getTranslatedRestrictionReason = (field) => {
  if (financialFields.includes(field)) {
    return t('Only administrators can modify financial information')
  }
  if (loginFields.includes(field)) {
    return t('Only administrators can modify login credentials')
  }
  if (systemFields.includes(field)) {
    return t('System fields cannot be modified')
  }
  if (hasRole('DBRO')) {
    return t('Read-only users cannot modify actor data')
  }
  if (hasRole('CLI')) {
    return t('Client users cannot modify actor data')
  }
  return t('You do not have permission to modify this field')
}

// Simple actions based on role
const canViewActor = canRead.value
const canEditActor = canWrite.value  
const canDeleteActor = isAdmin.value

// Helper function to show field restriction tooltip/alert
const showRestrictionFeedback = (fieldName) => {
  const reason = getTranslatedRestrictionReason(fieldName)
  if (reason) {
    // Create a temporary notification element
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
    
    // Remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 5000)
  }
}

// Check if there are any restricted fields in this actor
const actorFields = ['name', 'first_name', 'display_name', 'address', 'country', 'nationality', 'language', 'function', 'email', 'phone', 'warn']

const hasRestrictedFieldsInActor = computed(() => {
  return !isAdmin.value && (hasRole('DBRO') || hasRole('CLI'))
})

const hasEditableFieldsInActor = computed(() => {
  return isAdmin.value || canWrite.value
})

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

// Load used in data when tab is activated
async function loadUsedInData() {
  if (usedInData.value) return // Already loaded
  
  loadingUsedIn.value = true
  try {
    const response = await fetch(`/actor/${props.actor.id}/usedin`, {
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

// Handle tab change
function handleTabChange(value) {
  if (value === 'usedin') {
    loadUsedInData()
  }
}

// Navigate to matter
function navigateToMatter(matterId) {
  router.visit(`/matter/${matterId}`)
}

// Handle field updates
function handleUpdate(field, value) {
  // Reload only the actor data
  router.reload({ only: ['actor'] })
}

// Update checkbox fields
async function updateCheckbox(field, checked) {
  // Check permissions before allowing update
  if (!isFieldEditable(field)) {
    showRestrictionFeedback(field)
    return
  }
  
  try {
    const response = await fetch(`/actor/${props.actor.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ [field]: checked })
    })
    
    if (response.ok) {
      router.reload({ only: ['actor'] })
    }
  } catch (error) {
    console.error('Failed to update actor:', error)
  }
}

// Delete confirmation
function confirmDelete() {
  showDeleteDialog.value = true
}

// Delete actor
async function deleteActor() {
  try {
    await router.delete(`/actor/${props.actor.id}`, {
      onSuccess: () => {
        router.visit('/actor')
      }
    })
  } catch (error) {
    console.error('Failed to delete actor:', error)
  }
}

// Listen for tab changes
onMounted(() => {
  // If there's a specific implementation for tab change events in your tabs component
  // You might need to add it here or use a watcher
})
</script>