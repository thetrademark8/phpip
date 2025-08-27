<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>{{ $t('Manage Classifiers') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Manage all classifiers including titles, classes, and images for this matter') }}
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-6">
        <!-- Add Classifier Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ $t('Add Classifier') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="handleAddClassifier" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <FormField
                  :label="$t('Type')"
                  name="type_code"
                  :error="addForm.errors.type_code"
                  required
                >
                  <AutocompleteInput
                    v-model="addForm.type_code"
                    v-model:display-model-value="typeDisplay"
                    endpoint="/classifier-type/autocomplete/0"
                    :placeholder="$t('Select type')"
                    :min-length="0"
                    value-key="code"
                    label-key="type"
                    @selected="handleTypeSelect"
                  />
                </FormField>

                <FormField
                  v-if="!isImageType"
                  :label="$t('Value')"
                  name="value"
                  :error="addForm.errors.value"
                  required
                >
                  <Input
                    v-model="addForm.value"
                    :placeholder="$t('Enter value')"
                  />
                </FormField>

                <FormField
                  v-if="isImageType"
                  :label="$t('Image')"
                  name="image"
                  :error="addForm.errors.image"
                  required
                >
                  <Input
                    type="file"
                    accept="image/*"
                    @change="handleImageSelect"
                  />
                </FormField>
              </div>

              <FormField
                v-if="showLinkedMatter"
                :label="$t('Linked Matter')"
                name="lnk_matter_id"
                :error="addForm.errors.lnk_matter_id"
              >
                <Input
                  v-model="addForm.lnk_matter_id"
                  :placeholder="$t('Enter linked matter ID')"
                />
              </FormField>

              <FormField
                :label="$t('Display Order')"
                name="display_order"
                :error="addForm.errors.display_order"
              >
                <Input
                  v-model.number="addForm.display_order"
                  type="number"
                  :placeholder="'0'"
                />
              </FormField>

              <Button type="submit" :disabled="addForm.processing">
                <Plus class="mr-2 h-4 w-4" />
                {{ $t('Add Classifier') }}
              </Button>
            </form>
          </CardContent>
        </Card>

        <!-- Current Classifiers Section -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ $t('Current Classifiers') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="groupedClassifiers && Object.keys(groupedClassifiers).length > 0" class="space-y-4">
              <div v-for="(classifiers, typeName) in groupedClassifiers" :key="typeName" class="space-y-2">
                <h4 class="font-medium text-sm text-muted-foreground">{{ typeName }}</h4>
                <div class="space-y-1">
                  <div
                    v-for="classifier in classifiers"
                    :key="classifier.id"
                    class="flex items-center justify-between p-2 border rounded-lg hover:bg-muted/50"
                  >
                    <div class="flex-1">
                      <div v-if="classifier.type_code === 'IMG'" class="flex items-center gap-2">
                        <img 
                          :src="`/classifier/${classifier.id}/img`" 
                          class="h-12 w-12 object-contain border rounded"
                          :alt="$t('Classifier image')"
                        />
                        <span class="text-sm text-muted-foreground">{{ classifier.value }}</span>
                      </div>
                      <div v-else-if="editingClassifierId === classifier.id" class="flex items-center gap-2">
                        <Input
                          v-model="editForm.value"
                          @keyup.enter="saveEdit(classifier)"
                          @keyup.escape="cancelEdit"
                          class="max-w-md"
                          autofocus
                        />
                        <Button
                          variant="ghost"
                          size="icon"
                          @click="saveEdit(classifier)"
                        >
                          <Check class="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          @click="cancelEdit"
                        >
                          <X class="h-4 w-4" />
                        </Button>
                      </div>
                      <div v-else class="font-medium">
                        {{ classifier.value }}
                        <span v-if="classifier.lnk_matter_id" class="text-sm text-muted-foreground ml-2">
                          (→ {{ classifier.linked_matter?.uid }})
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                      <Button
                        v-if="!isImageClassifier(classifier) && editingClassifierId !== classifier.id"
                        variant="ghost"
                        size="icon"
                        @click="startEdit(classifier)"
                      >
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        @click="handleRemoveClassifier(classifier)"
                        :disabled="removingClassifierId === classifier.id"
                      >
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-muted-foreground">
              {{ $t('No classifiers assigned to this matter') }}
            </div>
          </CardContent>
        </Card>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Close') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { Plus, Trash2, Pencil, Check, X } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import FormField from '@/components/ui/form/FormField.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matter: {
    type: Object,
    required: true
  },
  classifiers: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:open', 'success'])

const { t } = useI18n()

// State
const typeDisplay = ref('')
const removingClassifierId = ref(null)
const editingClassifierId = ref(null)
const selectedType = ref(null)
const isImageType = ref(false)
const showLinkedMatter = ref(false)

// Forms
const addForm = useForm({
  matter_id: props.matter.container_id ?? props.matter.id,
  type_code: '',
  value: '',
  lnk_matter_id: '',
  display_order: 0,
  image: null
})

const editForm = useForm({
  value: ''
})

// Group classifiers by type
const groupedClassifiers = computed(() => {
  return props.classifiers || {}
})

function handleTypeSelect(type) {
  console.log(type)
  selectedType.value = type
  isImageType.value = type?.key === 'IMG'
  showLinkedMatter.value = type?.key === 'LINK'
}

function handleImageSelect(event) {
  const file = event.target.files[0]
  if (file) {
    addForm.image = file
  }
}

function handleAddClassifier() {
  if (isImageType.value && addForm.image) {
    // For image upload, use FormData
    const formData = new FormData()
    formData.append('matter_id', addForm.matter_id)
    formData.append('type_code', addForm.type_code)
    formData.append('image', addForm.image)
    formData.append('display_order', addForm.display_order)
    
    router.post(route('matter.classifiers.store', {
      matter: props.matter.id,
    }), formData, {
      forceFormData: true,
      onSuccess: () => {
        addForm.reset()
        typeDisplay.value = ''
        selectedType.value = null
        isImageType.value = false
        showLinkedMatter.value = false
        emit('success')
      }
    })
  } else {
    // Regular classifier
    addForm.post(route('matter.classifiers.store', {
      matter: props.matter.id,
    }), {
      onSuccess: () => {
        addForm.reset()
        typeDisplay.value = ''
        selectedType.value = null
        isImageType.value = false
        showLinkedMatter.value = false
        emit('success')
      }
    })
  }
}

function isImageClassifier(classifier) {
  return classifier.type_code === 'IMG'
}

function startEdit(classifier) {
  editingClassifierId.value = classifier.id
  editForm.value = classifier.value
}

function cancelEdit() {
  editingClassifierId.value = null
  editForm.reset()
}

function saveEdit(classifier) {
  editForm.put(`/classifier/${classifier.id}`, {
    onSuccess: () => {
      editingClassifierId.value = null
      editForm.reset()
      emit('success')
    }
  })
}

function handleRemoveClassifier(classifier) {
  if (confirm(t('Remove classifier "{value}"?', { value: classifier.value }))) {
    removingClassifierId.value = classifier.id
    
    router.delete(`/classifier/${classifier.id}`, {
      onSuccess: () => {
        removingClassifierId.value = null
        emit('success')
      },
      onError: () => {
        removingClassifierId.value = null
      }
    })
  }
}
</script>