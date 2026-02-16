<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogScrollContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Manage Titles') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Add, edit, or remove titles for this matter') }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-6 max-h-[60vh] overflow-y-auto">
        <!-- Add Title Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ $t('Add Title') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleAddTitle" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <FormField
                  :label="$t('Type')"
                  name="type_code"
                  :error="addForm.errors.type_code"
                  required
                >
                  <Combobox
                    v-model="addForm.type_code"
                    :options="titleTypeOptions"
                    :placeholder="$t('Select type')"
                    :search-placeholder="$t('Search type...')"
                    :no-results-text="$t('No type found.')"
                  />
                </FormField>

                <FormField
                  :label="$t('Title')"
                  name="value"
                  :error="addForm.errors.value"
                  required
                >
                  <Input
                    v-model="addForm.value"
                    :placeholder="$t('Enter title')"
                  />
                </FormField>
              </div>

              <Button type="submit" :disabled="addForm.processing">
                <Plus class="mr-2 h-4 w-4" />
                {{ $t('Add Title') }}
              </Button>
            </form>
          </CardContent>
        </Card>

        <!-- Current Titles Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ $t('Current Titles') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="groupedTitles && Object.keys(groupedTitles).length > 0" class="space-y-4">
              <div v-for="(titles, typeName) in groupedTitles" :key="typeName" class="space-y-2">
                <h4 class="font-medium text-sm text-muted-foreground">{{ typeName }}</h4>
                <div class="space-y-1">
                  <div
                    v-for="title in titles"
                    :key="title.id"
                    class="flex items-center justify-between p-2 border rounded-lg hover:bg-muted/50"
                  >
                    <div class="flex-1">
                      <Input
                        v-if="editingTitleId === title.id"
                        v-model="editForm.value"
                        @keyup.enter="saveEdit(title)"
                        @keyup.escape="cancelEdit"
                        class="max-w-md"
                        autofocus
                      />
                      <div v-else class="font-medium">
                        {{ title.value }}
                      </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                      <Button
                        v-if="editingTitleId === title.id"
                        variant="ghost"
                        size="icon"
                        @click="saveEdit(title)"
                      >
                        <Check class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="editingTitleId === title.id"
                        variant="ghost"
                        size="icon"
                        @click="cancelEdit"
                      >
                        <X class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="editingTitleId !== title.id"
                        variant="ghost"
                        size="icon"
                        @click="startEdit(title)"
                      >
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button
                        v-if="editingTitleId !== title.id"
                        variant="ghost"
                        size="icon"
                        @click="handleRemoveTitle(title)"
                        :disabled="removingTitleId === title.id"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-muted-foreground">
              {{ $t('No titles assigned to this matter') }}
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Close') }}
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Plus, Trash2, Pencil, Check, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogScrollContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import FormField from '@/components/ui/form/FormField.vue'
import { Combobox } from '@/components/ui/combobox'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matter: {
    type: Object,
    required: true
  },
  titles: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()

// State
const titleTypeOptions = ref([])
const removingTitleId = ref(null)
const editingTitleId = ref(null)

// Compute the correct matter_id (use container if exists, otherwise use matter itself)
const targetMatterId = computed(() => props.matter.container_id || props.matter.id)

// Load title type options when dialog opens
watch(() => props.open, async (isOpen) => {
  if (isOpen && titleTypeOptions.value.length === 0) {
    try {
      const response = await fetch('/options/classifier-types/1', {
        headers: { 'Accept': 'application/json' }
      })
      if (response.ok) {
        titleTypeOptions.value = await response.json()
      }
    } catch (error) {
      console.error('Error loading title type options:', error)
    }
  }
}, { immediate: true })

// Forms
const addForm = useForm({
  matter_id: targetMatterId.value,
  type_code: '',
  value: ''
})

// Update matter_id when matter prop changes (e.g., navigating to different matter)
watch(targetMatterId, (newMatterId) => {
  addForm.matter_id = newMatterId
}, { immediate: false })

const editForm = useForm({
  value: ''
})

// Group titles by type
const groupedTitles = computed(() => {
  return props.titles || {}
})

function handleAddTitle() {
  addForm.post('/classifier', {
    onSuccess: () => {
      // Reset form
      addForm.reset()
      
      // Reload matter data
      emit('success')
    }
  })
}

function startEdit(title) {
  editingTitleId.value = title.id
  editForm.value = title.value
}

function cancelEdit() {
  editingTitleId.value = null
  editForm.reset()
}

function saveEdit(title) {
  editForm.put(`/classifier/${title.id}`, {
    onSuccess: () => {
      editingTitleId.value = null
      editForm.reset()
      emit('success')
    }
  })
}

function handleRemoveTitle(title) {
  if (confirm(t('Remove title "{title}"?', { title: title.value }))) {
    removingTitleId.value = title.id
    
    router.delete(`/classifier/${title.id}`, {
      onSuccess: () => {
        removingTitleId.value = null
        emit('success')
      },
      onError: () => {
        removingTitleId.value = null
      }
    })
  }
}
</script>