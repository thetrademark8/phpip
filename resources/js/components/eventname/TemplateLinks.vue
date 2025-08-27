<template>
  <div class="space-y-4">
    <!-- Add new template link -->
    <div v-if="canEdit" class="border-b pb-4">
      <Label class="mb-2">{{ $t('Add Template Link') }}</Label>
      <div class="flex gap-2">
        <AutocompleteInput
          v-model="newTemplateId"
          v-model:display-model-value="newTemplateDisplay"
          endpoint="/template-class/autocomplete"
          :placeholder="$t('Select template class')"
          class="flex-1"
        />
        <Button 
          @click="addTemplateLink"
          :disabled="!newTemplateId || adding"
          size="sm"
        >
          <Plus v-if="!adding" class="h-4 w-4 mr-2" />
          <Loader2 v-else class="h-4 w-4 mr-2 animate-spin" />
          {{ $t('Add') }}
        </Button>
      </div>
    </div>

    <!-- Existing template links -->
    <div class="space-y-3">
      <h4 class="text-sm font-medium">
        {{ $t('Linked Templates') }}
        <span v-if="templateLinks.length > 0" class="text-muted-foreground">
          ({{ templateLinks.length }})
        </span>
      </h4>
      
      <div v-if="templateLinks.length === 0" class="text-center py-8 text-muted-foreground">
        <FileText class="h-12 w-12 mx-auto mb-2 opacity-50" />
        <p>{{ $t('No template links configured') }}</p>
        <p v-if="canEdit" class="text-sm">{{ $t('Add a template class above to get started') }}</p>
      </div>
      
      <div v-else class="space-y-2">
        <div
          v-for="link in templateLinks"
          :key="link.id"
          class="flex items-center justify-between p-3 border rounded-lg hover:bg-muted/50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <FileText class="h-4 w-4 text-muted-foreground" />
            <div>
              <div class="font-medium">
                {{ link.templateClass?.code || link.template_class_code }}
              </div>
              <div class="text-sm text-muted-foreground">
                {{ translated(link.templateClass?.name) || 'No name' }}
              </div>
            </div>
          </div>
          
          <Button
            v-if="canEdit"
            @click="removeTemplateLink(link)"
            variant="ghost"
            size="sm"
            class="text-destructive hover:text-destructive hover:bg-destructive/10"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Plus, X, FileText, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import { useTranslatedField } from '@/composables/useTranslation'

const props = defineProps({
  eventName: {
    type: Object,
    required: true
  },
  templateLinks: {
    type: Array,
    default: () => []
  },
  canEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['updated'])

const { t } = useI18n()
const { translated } = useTranslatedField()
const page = usePage()

// State
const newTemplateId = ref('')
const newTemplateDisplay = ref('')
const adding = ref(false)

// Methods
const addTemplateLink = async () => {
  if (!newTemplateId.value) return
  
  adding.value = true
  
  try {
    const response = await fetch('/event-class', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        event_name_code: props.eventName.code,
        template_class_code: newTemplateId.value
      })
    })
    
    if (response.ok) {
      // Reset form
      newTemplateId.value = ''
      newTemplateDisplay.value = ''
      
      // Emit update event
      emit('updated')
    } else {
      console.error('Failed to add template link')
    }
  } catch (error) {
    console.error('Failed to add template link:', error)
  } finally {
    adding.value = false
  }
}

const removeTemplateLink = async (link) => {
  if (!confirm(t('Are you sure you want to remove this template link?'))) {
    return
  }
  
  try {
    const response = await fetch(`/event-class/${link.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': page.props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
      }
    })
    
    if (response.ok) {
      emit('updated')
    } else {
      console.error('Failed to remove template link')
    }
  } catch (error) {
    console.error('Failed to remove template link:', error)
  }
}
</script>