<template>
  <div class="space-y-4">
    <Card>
      <CardHeader>
        <CardTitle>Notes</CardTitle>
      </CardHeader>
      <CardContent>
        <Textarea
          v-if="canEdit"
          v-model="localNotes"
          class="min-h-[200px] font-mono"
          placeholder="Add notes..."
          @blur="saveNotes"
        />
        <div v-else class="whitespace-pre-wrap">
          {{ notes || 'No notes' }}
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Actions</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-wrap gap-2">
          <div>
            <span class="text-sm font-medium mr-2">Summaries:</span>
            <Button
              size="sm"
              variant="outline"
              @click="copySummary('en')"
            >
              <Copy class="mr-1 h-3 w-3" />
              EN
            </Button>
            <Button
              size="sm"
              variant="outline"
              class="ml-1"
              @click="copySummary('fr')"
            >
              <Copy class="mr-1 h-3 w-3" />
              FR
            </Button>
          </div>
          <div>
            <span class="text-sm font-medium mr-2">Email:</span>
            <Button
              size="sm"
              variant="outline"
              @click="sendEmail('en')"
            >
              <Mail class="mr-1 h-3 w-3" />
              EN
            </Button>
            <Button
              size="sm"
              variant="outline"
              class="ml-1"
              @click="sendEmail('fr')"
            >
              <Mail class="mr-1 h-3 w-3" />
              FR
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Drop zone for document merge -->
    <Card>
      <CardContent class="p-0">
        <div
          class="h-32 bg-muted flex flex-col items-center justify-center text-center p-4 cursor-pointer hover:bg-muted/80 transition-colors"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          :class="{ 'bg-primary/10': isDragging }"
        >
          <FileText class="h-8 w-8 mb-2 text-muted-foreground" />
          <p class="text-sm font-medium">Drop File to Merge</p>
          <p class="text-xs text-muted-foreground mt-1">
            Or click to select a template
          </p>
          <a
            href="https://github.com/jjdejong/phpip/wiki/Templates-(email-and-documents)#document-template-usage"
            target="_blank"
            class="text-xs text-primary hover:underline mt-2"
          >
            Learn more
          </a>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Copy, Mail, FileText } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Textarea } from '@/Components/ui/textarea'
import { toast } from 'vue-sonner'

const props = defineProps({
  notes: String,
  matterId: [String, Number],
  canEdit: Boolean
})

const emit = defineEmits(['update'])

const localNotes = ref(props.notes || '')
const isDragging = ref(false)
let saveTimeout = null

// Watch for external changes to notes
watch(() => props.notes, (newNotes) => {
  localNotes.value = newNotes || ''
})

function saveNotes() {
  clearTimeout(saveTimeout)
  saveTimeout = setTimeout(() => {
    router.put(`/matter/${props.matterId}`, {
      notes: localNotes.value
    }, {
      preserveScroll: true,
      onSuccess: () => {
        emit('update')
        toast.success('Notes saved', {
          description: 'Your notes have been saved successfully.'
        })
      },
      onError: () => {
        toast.error('Error', {
          description: 'Failed to save notes. Please try again.'
        })
      }
    })
  }, 500)
}

function copySummary(language) {
  router.get(`/matter/${props.matterId}/description/${language}`, {}, {
    onSuccess: (page) => {
      // Copy to clipboard logic would go here
      toast.success('Summary copied', {
        description: `${language.toUpperCase()} summary copied to clipboard.`
      })
    }
  })
}

function sendEmail(language) {
  router.get(`/document/select/${props.matterId}?Language=${language}`)
}

function handleDrop(event) {
  isDragging.value = false
  const files = event.dataTransfer.files
  if (files.length) {
    uploadAndMerge(files[0])
  }
}

function uploadAndMerge(file) {
  const formData = new FormData()
  formData.append('file', file)
  
  router.post(`/matter/${props.matterId}/mergeFile`, formData, {
    forceFormData: true,
    onSuccess: () => {
      toast.success('Document merged', {
        description: 'The document has been merged successfully.'
      })
    },
    onError: () => {
      toast.error('Error', {
        description: 'Failed to merge document. Please try again.'
      })
    }
  })
}
</script>