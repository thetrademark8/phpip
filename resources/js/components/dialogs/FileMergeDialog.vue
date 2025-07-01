<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('Merge Document') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Drop a DOCX template file or click to select') }}
        </DialogDescription>
      </DialogHeader>
      
      <div
        class="h-40 bg-muted flex flex-col items-center justify-center text-center p-4 cursor-pointer hover:bg-muted/80 transition-colors rounded-lg border-2 border-dashed"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        @click="selectFile"
        :class="{ 'bg-primary/10 border-primary': isDragging }"
      >
        <FileText class="h-10 w-10 mb-3 text-muted-foreground" />
        <p class="text-sm font-medium">{{ $t('Drop DOCX file here') }}</p>
        <p class="text-xs text-muted-foreground mt-1">
          {{ $t('Or click to select a template') }}
        </p>
        <input
          ref="fileInput"
          type="file"
          accept=".docx"
          class="hidden"
          @change="handleFileSelect"
        />
      </div>

      <div class="text-center mt-4">
        <a
          href="https://github.com/jjdejong/phpip/wiki/Templates-(email-and-documents)#document-template-usage"
          target="_blank"
          class="text-xs text-primary hover:underline"
        >
          {{ $t('Learn about document templates') }}
        </a>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('update:open', false)">
          {{ $t('Cancel') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue'
import { FileText } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  matterId: {
    type: [String, Number],
    required: true
  }
})

const emit = defineEmits(['update:open'])

const { t } = useI18n()
const isDragging = ref(false)
const fileInput = ref(null)

function selectFile() {
  fileInput.value?.click()
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    mergeDocument(file)
  }
}

function handleDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file && file.name.endsWith('.docx')) {
    mergeDocument(file)
  }
}

function mergeDocument(file) {
  const formData = new FormData()
  formData.append('file', file)
  
  fetch(`/matter/${props.matterId}/merge`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
  })
  .then(response => response.blob())
  .then(blob => {
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = file.name.replace('.docx', '_merged.docx')
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
    emit('update:open', false)
  })
  .catch(error => {
    console.error('Error merging document:', error)
    alert(t('Error merging document. Please try again.'))
  })
}
</script>