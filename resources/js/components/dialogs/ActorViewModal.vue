<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ actor?.name || 'Loading...' }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditMode ? t('actors.modal.editDescription') : t('actors.modal.viewDescription') }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="loading" class="flex justify-center py-8">
        <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="actor" class="space-y-6">
        <!-- Mode Toggle -->
        <div class="flex items-center justify-between border-b pb-4">
          <div class="flex items-center gap-2">
            <Badge v-if="actor.warn" variant="destructive">
              <AlertTriangle class="mr-1 h-3 w-3" />
              {{ t('actors.fields.warn') }}
            </Badge>
            <Badge variant="secondary">
              {{ actor.phy_person ? t('actors.types.physical') : t('actors.types.legal') }}
            </Badge>
          </div>
          <Button 
            @click="toggleEditMode" 
            variant="outline" 
            size="sm"
            v-if="canWrite"
          >
            <Edit class="mr-2 h-4 w-4" />
            {{ isEditMode ? t('actors.modal.viewMode') : t('actors.modal.editMode') }}
          </Button>
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
                <Label>{{ t('actors.fields.name') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.name || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.name"
                  :update-url="`/actor/${actor.id}`"
                  field-name="name"
                  @update="handleUpdate"
                />
              </div>
              <div>
                <Label>{{ t('actors.fields.firstName') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.first_name || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.first_name"
                  :update-url="`/actor/${actor.id}`"
                  field-name="first_name"
                  @update="handleUpdate"
                />
              </div>
            </div>

            <div>
              <Label>{{ t('actors.fields.displayName') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.display_name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.display_name"
                :update-url="`/actor/${actor.id}`"
                field-name="display_name"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.address') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md whitespace-pre-wrap">
                {{ actor.address || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.address"
                :update-url="`/actor/${actor.id}`"
                field-name="address"
                type="textarea"
                @update="handleUpdate"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label>{{ t('actors.fields.country') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.countryInfo?.name || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.countryInfo?.name"
                  :update-url="`/actor/${actor.id}`"
                  field-name="country"
                  type="autocomplete"
                  autocomplete-url="/country/autocomplete"
                  @update="handleUpdate"
                />
              </div>
              <div>
                <Label>{{ t('actors.fields.nationality') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.nationalityInfo?.name || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.nationalityInfo?.name"
                  :update-url="`/actor/${actor.id}`"
                  field-name="nationality"
                  type="autocomplete"
                  autocomplete-url="/country/autocomplete"
                  @update="handleUpdate"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label>{{ t('actors.fields.language') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.language || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.language"
                  :update-url="`/actor/${actor.id}`"
                  field-name="language"
                  :placeholder="'fr/en/de'"
                  @update="handleUpdate"
                />
              </div>
              <div>
                <Label>{{ t('actors.fields.function') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.function || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.function"
                  :update-url="`/actor/${actor.id}`"
                  field-name="function"
                  @update="handleUpdate"
                />
              </div>
            </div>

            <div>
              <Label>{{ t('actors.fields.employer') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.company?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.company?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="company_id"
                type="autocomplete"
                autocomplete-url="/actor/autocomplete"
                @update="handleUpdate"
              />
            </div>

            <div v-if="isEditMode" class="flex items-center space-x-4">
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="phy_person"
                  :checked="actor.phy_person"
                  @update:checked="updateCheckbox('phy_person', $event)"
                />
                <Label htmlFor="phy_person">{{ t('actors.fields.physicalPerson') }}</Label>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="small_entity"
                  :checked="actor.small_entity"
                  @update:checked="updateCheckbox('small_entity', $event)"
                />
                <Label htmlFor="small_entity">{{ t('actors.fields.smallEntity') }}</Label>
              </div>
            </div>
          </TabsContent>

          <!-- Contact Tab -->
          <TabsContent value="contact" class="space-y-4">
            <div>
              <Label>{{ t('actors.fields.addressMailing') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md whitespace-pre-wrap">
                {{ actor.address_mailing || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.address_mailing"
                :update-url="`/actor/${actor.id}`"
                field-name="address_mailing"
                type="textarea"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.countryMailing') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.country_mailingInfo?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.country_mailingInfo?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="country_mailing"
                type="autocomplete"
                autocomplete-url="/country/autocomplete"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.addressBilling') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md whitespace-pre-wrap">
                {{ actor.address_billing || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.address_billing"
                :update-url="`/actor/${actor.id}`"
                field-name="address_billing"
                type="textarea"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.countryBilling') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.country_billingInfo?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.country_billingInfo?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="country_billing"
                type="autocomplete"
                autocomplete-url="/country/autocomplete"
                @update="handleUpdate"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label>{{ t('actors.fields.email') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.email || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.email"
                  :update-url="`/actor/${actor.id}`"
                  field-name="email"
                  type="email"
                  @update="handleUpdate"
                />
              </div>
              <div>
                <Label>{{ t('actors.fields.phone') }}</Label>
                <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                  {{ actor.phone || '-' }}
                </div>
                <EditableField
                  v-else
                  :value="actor.phone"
                  :update-url="`/actor/${actor.id}`"
                  field-name="phone"
                  @update="handleUpdate"
                />
              </div>
            </div>
          </TabsContent>

          <!-- Other Tab -->
          <TabsContent value="other" class="space-y-4">
            <div>
              <Label>{{ t('actors.fields.userName') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.login || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.login"
                :update-url="`/actor/${actor.id}`"
                field-name="login"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>
                {{ t('actors.fields.defaultRole') }}
                <span v-if="actor.login" class="text-sm text-muted-foreground ml-2">
                  {{ t('actors.fields.roleDisabledHint') }}
                </span>
              </Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.droleInfo?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.droleInfo?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="default_role"
                type="autocomplete"
                autocomplete-url="/role/autocomplete"
                :disabled="!!actor.login"
                @update="handleUpdate"
              />
            </div>

            <div v-if="isEditMode" class="flex items-center space-x-2">
              <Checkbox
                id="warn"
                :checked="actor.warn"
                @update:checked="updateCheckbox('warn', $event)"
              />
              <Label htmlFor="warn">{{ t('actors.fields.warn') }}</Label>
            </div>

            <div>
              <Label>{{ t('actors.fields.renewalDiscount') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.ren_discount || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.ren_discount"
                :update-url="`/actor/${actor.id}`"
                field-name="ren_discount"
                type="number"
                :placeholder="t('actors.fields.discountHint')"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.legalForm') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.legal_form || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.legal_form"
                :update-url="`/actor/${actor.id}`"
                field-name="legal_form"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.registrationNo') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.registration_no || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.registration_no"
                :update-url="`/actor/${actor.id}`"
                field-name="registration_no"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.vatNumber') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.VAT_number || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.VAT_number"
                :update-url="`/actor/${actor.id}`"
                field-name="VAT_number"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.parentCompany') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.parent?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.parent?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="parent_id"
                type="autocomplete"
                autocomplete-url="/actor/autocomplete"
                @update="handleUpdate"
              />
            </div>

            <div>
              <Label>{{ t('actors.fields.workSite') }}</Label>
              <div v-if="!isEditMode" class="mt-1 p-2 bg-muted rounded-md">
                {{ actor.site?.name || '-' }}
              </div>
              <EditableField
                v-else
                :value="actor.site?.name"
                :update-url="`/actor/${actor.id}`"
                field-name="site_id"
                type="autocomplete"
                autocomplete-url="/actor/autocomplete"
                @update="handleUpdate"
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
        <Button @click="$emit('update:open', false)" variant="outline">
          {{ t('actions.cancel') }}
        </Button>
        <Button 
          v-if="canWrite && isEditMode" 
          @click="confirmDelete" 
          variant="destructive"
        >
          <Trash2 class="mr-2 h-4 w-4" />
          {{ t('actors.show.delete') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:open="showDeleteDialog"
    :title="t('actors.show.deleteTitle')"
    :description="t('actors.show.deleteDescription', { name: actor?.name })"
    :confirm-text="t('actors.show.confirmDelete')"
    :cancel-text="t('actions.cancel')"
    variant="destructive"
    @confirm="deleteActor"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { 
  Loader2,
  AlertTriangle,
  Edit,
  Trash2
} from 'lucide-vue-next'
import EditableField from '@/Components/ui/EditableField.vue'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'
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
import { Checkbox } from '@/Components/ui/checkbox'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/Components/ui/tabs'

const props = defineProps({
  open: Boolean,
  actorId: [Number, String],
})

const emit = defineEmits(['update:open', 'show-actor'])

const { t } = useI18n()
const page = usePage()
const loading = ref(false)
const actor = ref(null)
const isEditMode = ref(false)
const loadingUsedIn = ref(false)
const usedInData = ref(null)
const showDeleteDialog = ref(false)

// Check permissions
const canWrite = computed(() => {
  const user = page.props.auth?.user
  return user?.default_role !== 'CLI'
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

// Watch for actorId changes and fetch data
watch(() => props.actorId, (newId) => {
  if (newId && props.open) {
    fetchActorData(newId)
  }
}, { immediate: true })

// Watch for open state changes
watch(() => props.open, (isOpen) => {
  if (isOpen && props.actorId) {
    fetchActorData(props.actorId)
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

function toggleEditMode() {
  isEditMode.value = !isEditMode.value
}

function handleUpdate(field, value) {
  // Refresh actor data after update
  if (actor.value) {
    fetchActorData(actor.value.id)
  }
}

async function updateCheckbox(field, checked) {
  try {
    const response = await fetch(`/actor/${actor.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ [field]: checked })
    })
    
    if (response.ok) {
      fetchActorData(actor.value.id)
    }
  } catch (error) {
    console.error('Failed to update actor:', error)
  }
}

function navigateToMatter(matterId) {
  router.visit(`/matter/${matterId}`)
}

function confirmDelete() {
  showDeleteDialog.value = true
}

async function deleteActor() {
  try {
    await router.delete(`/actor/${actor.value.id}`, {
      onSuccess: () => {
        emit('update:open', false)
        // Emit event to refresh the actor list
        router.reload({ only: ['actors'] })
      }
    })
  } catch (error) {
    console.error('Failed to delete actor:', error)
  }
}
</script>