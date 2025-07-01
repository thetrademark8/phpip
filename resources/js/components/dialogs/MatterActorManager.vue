<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>Manage Actors</DialogTitle>
        <DialogDescription>
          Add or remove actors from this matter
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-6 max-h-[60vh] overflow-y-auto">
        <!-- Add Actor Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Add Actor</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleAddActor" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <FormField
                  label="Role"
                  name="role"
                  :error="addForm.errors.role"
                  required
                >
                  <AutocompleteInput
                    v-model="addForm.role"
                    v-model:display-model-value="roleDisplay"
                    endpoint="/role/autocomplete"
                    placeholder="Select role"
                    :min-length="0"
                    value-key="code"
                    label-key="name"
                    @selected="handleRoleSelect"
                  />
                </FormField>

                <FormField
                  label="Actor"
                  name="actor_id"
                  :error="addForm.errors.actor_id"
                  required
                >
                  <AutocompleteInput
                    v-model="addForm.actor_id"
                    v-model:display-model-value="actorDisplay"
                    endpoint="/actor/autocomplete/1"
                    placeholder="Search or create actor"
                    value-key="key"
                    label-key="value"
                  />
                </FormField>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <FormField
                  label="Reference"
                  name="actor_ref"
                  :error="addForm.errors.actor_ref"
                >
                  <Input
                    v-model="addForm.actor_ref"
                    placeholder="Actor reference (optional)"
                  />
                </FormField>

                <FormField
                  label="Date"
                  name="date"
                  :error="addForm.errors.date"
                >
                  <DatePicker
                    v-model="addForm.date"
                    placeholder="Select date (optional)"
                  />
                </FormField>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <FormField
                  label="Rate"
                  name="rate"
                  :error="addForm.errors.rate"
                >
                  <Input
                    v-model="addForm.rate"
                    type="number"
                    step="0.01"
                    placeholder="Hourly rate (optional)"
                  />
                </FormField>

                <FormField
                  label="Display Order"
                  name="display_order"
                  :error="addForm.errors.display_order"
                >
                  <Input
                    v-model.number="addForm.display_order"
                    type="number"
                    placeholder="Display order (optional)"
                  />
                </FormField>
              </div>

              <div v-if="selectedRole?.shareable && matter.container_id" class="space-y-2">
                <Label>Scope</Label>
                <RadioGroup v-model="addForm.matter_id">
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem :value="matter.container_id || matter.id" id="shared" />
                    <Label htmlFor="shared">Add to container (shared with family)</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem :value="matter.id" id="local" />
                    <Label htmlFor="local">Add to this matter only</Label>
                  </div>
                </RadioGroup>
              </div>

              <Button type="submit" :disabled="addForm.processing">
                <UserPlus class="mr-2 h-4 w-4" />
                Add Actor
              </Button>
            </form>
          </CardContent>
        </Card>

        <!-- Current Actors Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Current Actors</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="groupedActors && Object.keys(groupedActors).length > 0" class="space-y-4">
              <div v-for="(actors, role) in groupedActors" :key="role" class="space-y-2">
                <h4 class="font-medium text-sm text-muted-foreground">{{ role }}</h4>
                <div class="space-y-1">
                  <div
                    v-for="actor in actors"
                    :key="actor.id"
                    class="flex items-center justify-between p-2 border rounded-lg"
                    :class="{ 'opacity-60': actor.inherited }"
                  >
                    <div class="flex-1">
                      <div class="font-medium">
                        {{ actor.display_name || actor.name }}
                        <span v-if="actor.inherited" class="text-sm text-muted-foreground italic">
                          (inherited)
                        </span>
                      </div>
                      <div v-if="actor.company_name" class="text-sm text-muted-foreground">
                        {{ actor.company_name }}
                      </div>
                      <div class="flex gap-4 text-sm text-muted-foreground">
                        <span v-if="actor.actor_ref">Ref: {{ actor.actor_ref }}</span>
                        <span v-if="actor.date">{{ formatDate(actor.date) }}</span>
                        <span v-if="actor.rate">Rate: ${{ actor.rate }}/hr</span>
                        <span v-if="actor.shared" class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">Shared</span>
                      </div>
                    </div>
                    <Button
                      v-if="!actor.inherited"
                      variant="ghost"
                      size="icon"
                      @click="handleRemoveActor(actor)"
                      :disabled="removingActorId === actor.id"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-muted-foreground">
              No actors assigned to this matter
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          Close
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { UserPlus, Trash2 } from 'lucide-vue-next'
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
import { Label } from '@/Components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group'
import FormField from '@/Components/ui/form/FormField.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'

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
  rate: '',
  display_order: 0
})

// Group actors by role
const groupedActors = computed(() => {
  if (!props.matter.actors || !Array.isArray(props.matter.actors)) return {}
  
  const groups = {}
  props.matter.actors.forEach(actor => {
    const roleName = actor.role_name || 'Other'
    if (!groups[roleName]) {
      groups[roleName] = []
    }
    groups[roleName].push(actor)
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
  addForm.post(`/matter/${props.matter.id}/actors`, {
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