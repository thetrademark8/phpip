<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>{{ t('Manage Actors') }}</DialogTitle>
        <DialogDescription>
          {{ t('Add or remove actors from this matter') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 max-h-[60vh] overflow-y-auto">
        <!-- Permission Notice -->
        <div v-if="!canWrite"
             class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
          <div class="flex items-start gap-2">
            <AlertCircle class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5"/>
            <div>
              <p class="font-medium text-orange-800 dark:text-orange-200 text-sm">
                {{ t('Actor management restricted') }}
              </p>
              <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                {{
                  t('You do not have permission to add or remove actors from this matter. Contact an administrator if you need to modify actor assignments.')
                }}
              </p>
            </div>
          </div>
        </div>

        <!-- Add Actor Section -->
        <Card v-if="canWrite">
          <CardHeader>
            <CardTitle class="text-base">{{ t('Add Actor') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleAddActor" class="space-y-4">
              <div class="grid gap-4">
                <FormField
                    :label="t('Role')"
                    name="role"
                    :error="addForm.errors.role"
                    required
                >
                  <AutocompleteInput
                      v-model="addForm.role"
                      v-model:display-model-value="roleDisplay"
                      endpoint="/role/autocomplete"
                      :placeholder="t('Select role')"
                      :min-length="0"
                      value-key="code"
                      label-key="name"
                      @selected="handleRoleSelect"
                  />
                </FormField>

                <FormField
                    :label="t('Actor')"
                    name="actor_id"
                    :error="addForm.errors.actor_id"
                    required
                >
                  <AutocompleteInput
                      v-model="addForm.actor_id"
                      v-model:display-model-value="actorDisplay"
                      endpoint="/actor/autocomplete/1"
                      :placeholder="t('Search or create actor')"
                      value-key="key"
                      label-key="value"
                  />
                </FormField>
              </div>

              <div class="grid gap-4">
                <FormField
                    :label="t('Reference')"
                    name="actor_ref"
                    :error="addForm.errors.actor_ref"
                >
                  <Input
                      v-model="addForm.actor_ref"
                      :placeholder="t('Actor reference (optional)')"
                  />
                </FormField>

                <FormField
                    :label="t('Date')"
                    name="date"
                    :error="addForm.errors.date"
                >
                  <DatePicker
                      v-model="addForm.date"
                      :placeholder="t('Select date (optional)')"
                  />
                </FormField>
              </div>

              <div class="grid gap-4">
                <FormField
                    :label="t('Ownership %')"
                    name="rate"
                    :error="addForm.errors.rate"
                >
                  <Input
                      v-model="addForm.rate"
                      type="number"
                      min="0"
                      max="100"
                      placeholder="100"
                  />
                </FormField>

                <FormField
                    :label="t('Display Order')"
                    name="display_order"
                    :error="addForm.errors.display_order"
                >
                  <Input
                      v-model.number="addForm.display_order"
                      type="number"
                      :placeholder="t('Display order (optional)')"
                  />
                </FormField>
              </div>

              <div v-if="selectedRole?.shareable && matter.container_id" class="space-y-2">
                <Label>{{ t('Scope') }}</Label>
                <RadioGroup v-model="addForm.matter_id">
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem :value="matter.container_id || matter.id" id="shared"/>
                    <Label htmlFor="shared">{{ t('Add to container (shared with family)') }}</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem :value="matter.id" id="local"/>
                    <Label htmlFor="local">{{ t('Add to this matter only') }}</Label>
                  </div>
                </RadioGroup>
              </div>

              <Button type="submit" :disabled="addForm.processing">
                <UserPlus class="mr-2 h-4 w-4"/>
                {{ t('Add Actor') }}
              </Button>
            </form>
          </CardContent>
        </Card>

        <!-- Current Actors Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ t('Current Actors') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="groupedActors && Object.keys(groupedActors).length > 0" class="space-y-4">
              <div v-for="(roleData, roleKey) in groupedActors" :key="roleKey" class="space-y-2">
                <h4 class="font-medium text-sm text-muted-foreground">{{ translated(roleData.name) || t('Other') }}</h4>
                <div class="space-y-1">
                  <div
                      v-for="actor in roleData.actors"
                      :key="actor.id"
                      class="flex items-center justify-between p-2 border rounded-lg"
                      :class="{ 'opacity-60': actor.inherited }"
                  >
                    <div class="flex-1">
                      <div class="font-medium">
                        {{
                          actor.display_name || actor.name
                        }}{{ actor.rate && actor.rate != 100 ? ` (${actor.rate}%)` : '' }}
                        <span v-if="actor.inherited" class="text-sm text-muted-foreground italic">
                          {{ t('(inherited)') }}
                        </span>
                      </div>
                      <div v-if="actor.company_name" class="text-sm text-muted-foreground">
                        {{ translated(translatedactor.company_name) }}
                      </div>
                      <div class="flex gap-4 text-sm text-muted-foreground">
                        <span v-if="actor.actor_ref">{{ t('Ref') }}: {{ actor.actor_ref }}</span>
                        <span v-if="actor.date">{{ formatDate(actor.date) }}</span>
                        <span v-if="actor.shared"
                              class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">{{ t('Shared') }}</span>
                      </div>
                    </div>
                    <Button
                        v-if="!actor.inherited && canWrite"
                        variant="ghost"
                        size="icon"
                        @click="handleRemoveActor(actor)"
                        :disabled="removingActorId === actor.id"
                    >
                      <Trash2 class="h-4 w-4"/>
                    </Button>
                    <div
                        v-else-if="!actor.inherited && !canWrite"
                        class="p-2"
                        :title="t('Removal restricted - insufficient permissions')"
                    >
                      <Lock class="h-4 w-4 text-muted-foreground"/>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-muted-foreground">
              {{ t('No actors assigned to this matter') }}
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ t('Close') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import {ref, computed} from 'vue'
import {useForm, router} from '@inertiajs/vue3'
import {format} from 'date-fns'
import {useTranslatedField} from '@/composables/useTranslation'
import {UserPlus, Trash2, Lock, AlertCircle} from 'lucide-vue-next'
import {usePermissions} from '@/composables/usePermissions.js'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card'
import {Button} from '@/components/ui/button'
import {Input} from '@/components/ui/input'
import {Label} from '@/components/ui/label'
import {RadioGroup, RadioGroupItem} from '@/components/ui/radio-group'
import FormField from '@/components/ui/form/FormField.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/components/ui/date-picker/DatePicker.vue'

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

const {translated, t} = useTranslatedField()

// Actor permissions
const {
  canWrite,
  canRead
} = usePermissions()

// State
const roleDisplay = ref('')
const actorDisplay = ref('')
const selectedRole = ref(null)
const removingActorId = ref(null)

// Forms
const addForm = useForm({
  matter_id: props.matter.container_id || props.matter.id,
  role: '',
  actor_id: '',
  actor_ref: '',
  date: '',
  rate: 100,
  display_order: 0
})

// Group actors by role
const groupedActors = computed(() => {
  if (!props.matter.actors || !Array.isArray(props.matter.actors)) return {}

  const groups = {}
  props.matter.actors.forEach(actor => {
    // Use role code as key (string), not the full role object
    const roleKey = actor.role?.code || actor.role_code || 'other'
    if (!groups[roleKey]) {
      groups[roleKey] = {
        name: actor.role_name || 'Other',
        actors: []
      }
    }
    groups[roleKey].actors.push(actor)
  })
  return groups
})

function handleRoleSelect(role) {
  selectedRole.value = role
  // Set default matter_id based on role shareability
  if (role && !role.shareable) {
    addForm.matter_id = props.matter.id
  }
}

function handleAddActor() {
  addForm.post(route('matter.actors.store', props.matter.id), {
    onSuccess: () => {
      // Reset form
      addForm.reset()
      roleDisplay.value = ''
      actorDisplay.value = ''
      selectedRole.value = null

      // Reload matter data
      emit('success')
    }
  })
}

function handleRemoveActor(actor) {
  if (confirm(`Remove ${actor.display_name || actor.name} from this matter?`)) {
    removingActorId.value = actor.id

    // Use the actor-pivot route with the pivot ID
    router.delete(`/actor-pivot/${actor.id}`, {
      onSuccess: () => {
        removingActorId.value = null
        emit('success')
      },
      onError: () => {
        removingActorId.value = null
      }
    })
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}
</script>