<template>
  <div class="space-y-4">
    <div
      v-for="(actors, role) in groupedActors"
      :key="role"
      class="border rounded-lg p-4"
    >
      <h3 class="font-semibold text-sm text-muted-foreground mb-3">
        {{ getRoleName(role) }}
      </h3>
      
      <div class="space-y-2">
        <div
          v-for="actor in actors"
          :key="actor.id"
          :class="cn(
            'flex items-center justify-between p-3 rounded-md border bg-background',
            selectedActorId === actor.id && 'ring-2 ring-primary',
            editable && 'cursor-move hover:bg-accent'
          )"
          :draggable="editable"
          @dragstart="handleDragStart($event, actor)"
          @dragover.prevent
          @drop="handleDrop($event, role)"
          @click="handleActorClick(actor)"
        >
          <div class="flex-1">
            <div class="font-medium">
              <EditableField
                v-if="enableInlineEdit && !actor.inherited"
                :model-value="actor.display_name || actor.name"
                field="display_name"
                :url="updateUrl(actor)"
                placeholder="Name"
                @saved="emit('update', { ...actor, display_name: $event })"
              />
              <span v-else :class="{ 'italic text-muted-foreground': actor.inherited }">
                {{ actor.display_name || actor.name }}{{ actor.rate && actor.rate != 100 ? ` (${actor.rate}%)` : '' }}
              </span>
            </div>
            <div v-if="actor.company_name" class="text-sm text-muted-foreground">
              <EditableField
                v-if="enableInlineEdit && !actor.inherited"
                :model-value="actor.company_name"
                field="company_name"
                :url="updateUrl(actor)"
                placeholder="Company"
                value-class="text-sm text-muted-foreground"
                @saved="emit('update', { ...actor, company_name: $event })"
              />
              <span v-else :class="{ 'italic': actor.inherited }">
                {{ actor.company_name }}
              </span>
            </div>
            <div class="flex gap-4 mt-1 text-sm text-muted-foreground">
              <span v-if="actor.email">
                <EditableField
                  v-if="enableInlineEdit && !actor.inherited"
                  :model-value="actor.email"
                  field="email"
                  :url="updateUrl(actor)"
                  placeholder="Email"
                  value-class="text-sm text-muted-foreground"
                  @saved="emit('update', { ...actor, email: $event })"
                />
                <span v-else :class="{ 'italic': actor.inherited }">
                  {{ actor.email }}
                </span>
              </span>
              <span v-if="actor.phone">
                <EditableField
                  v-if="enableInlineEdit && !actor.inherited"
                  :model-value="actor.phone"
                  field="phone"
                  :url="updateUrl(actor)"
                  placeholder="Phone"
                  value-class="text-sm text-muted-foreground"
                  @saved="emit('update', { ...actor, phone: $event })"
                />
                <span v-else :class="{ 'italic': actor.inherited }">
                  {{ actor.phone }}
                </span>
              </span>
            </div>
          </div>
          
          <div v-if="editable" class="flex items-center gap-2 ml-4">
            <Button
              size="icon"
              variant="ghost"
              @click.stop="emit('edit', actor)"
            >
              <Edit2 class="h-4 w-4" />
            </Button>
            <Button
              size="icon"
              variant="ghost"
              @click.stop="emit('remove', actor)"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
      
      <Button
        v-if="editable"
        variant="outline"
        size="sm"
        class="mt-3 w-full"
        @click="emit('add', role)"
      >
        <Plus class="h-4 w-4 mr-2" />
        Add {{ getRoleName(role) }}
      </Button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Edit2, X, Plus } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/Components/ui/button'
import InlineEdit from '@/Components/ui/InlineEdit.vue'
import EditableField from '@/Components/ui/EditableField.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  actors: {
    type: Array,
    required: true
  },
  roleNames: {
    type: Object,
    default: () => ({
      CLI: 'Client',
      OWN: 'Owner',
      INV: 'Inventor',
      APP: 'Applicant',
      AGT: 'Agent',
      AGTS: 'Agent (Shared)',
      AGTC: 'Agent (Client)',
      FAGT: 'Foreign Agent',
      FGTAGT: 'Foreign Agent',
      LCAGT: 'Local Agent',
      PAY: 'Payor',
      CPY: 'Copy To'
    })
  },
  editable: {
    type: Boolean,
    default: false
  },
  selectedActorId: {
    type: [String, Number],
    default: null
  },
  enableInlineEdit: {
    type: Boolean,
    default: false
  },
  updateUrl: {
    type: Function,
    default: (actor) => `/actor/${actor.id}`
  }
})

const emit = defineEmits(['edit', 'remove', 'add', 'click', 'reorder', 'update'])

const { translated } = useTranslatedField()

// Group actors by role
const groupedActors = computed(() => {
  const groups = {}
  props.actors.forEach(actor => {
    const role = actor.role_code || actor.role || 'OTHER'
    if (!groups[role]) {
      groups[role] = []
    }
    groups[role].push(actor)
  })
  return groups
})

// Drag and drop handling
const draggedActor = ref(null)

const handleDragStart = (event, actor) => {
  if (!props.editable) return
  draggedActor.value = actor
  event.dataTransfer.effectAllowed = 'move'
}

const handleDrop = (event, newRole) => {
  if (!props.editable || !draggedActor.value) return
  
  event.preventDefault()
  emit('reorder', {
    actor: draggedActor.value,
    oldRole: draggedActor.value.role_code || draggedActor.value.role,
    newRole
  })
  
  draggedActor.value = null
}

const handleActorClick = (actor) => {
  emit('click', actor)
}

// Get role name with translation support
const getRoleName = (role) => {
  // First, check if we have a translatable role_name from the first actor in this role group
  const firstActor = groupedActors.value[role]?.[0]
  if (firstActor?.role_name) {
    return translated(firstActor.role_name)
  }
  
  // Fallback to roleNames prop
  return props.roleNames[role] || role
}
</script>