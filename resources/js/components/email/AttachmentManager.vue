<template>
  <div class="attachment-manager space-y-3">
    <div class="flex items-center justify-between">
      <Button variant="outline" size="sm" @click="triggerUpload" :disabled="uploading">
        <Upload class="h-4 w-4 mr-1" />
        {{ $t('email.uploadFile') }}
      </Button>
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        multiple
        @change="handleFileUpload"
      />
    </div>

    <!-- Upload Progress -->
    <div v-if="uploading" class="flex items-center gap-2 text-sm text-muted-foreground">
      <Loader2 class="h-4 w-4 animate-spin" />
      {{ $t('email.uploading') }}
    </div>

    <!-- Upload Error -->
    <div v-if="uploadError" class="flex items-center gap-2 text-sm text-destructive">
      <AlertCircle class="h-4 w-4" />
      {{ uploadError }}
      <Button variant="ghost" size="sm" class="h-6 px-2" @click="uploadError = null">
        <X class="h-3 w-3" />
      </Button>
    </div>

    <!-- Existing Attachments -->
    <div v-if="attachments.length > 0" class="space-y-2 max-h-[200px] overflow-y-auto">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        class="flex items-center justify-between p-2 border rounded-md"
      >
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <Checkbox
            :id="`attachment-${attachment.id}`"
            :checked="isSelected(attachment.id)"
            @update:checked="(checked) => toggleAttachment(attachment.id, checked)"
          />
          <label :for="`attachment-${attachment.id}`" class="flex items-center gap-2 cursor-pointer min-w-0 flex-1">
            <FileIcon class="h-4 w-4 text-muted-foreground flex-shrink-0" />
            <span class="text-sm truncate">{{ attachment.original_name }}</span>
            <span class="text-xs text-muted-foreground flex-shrink-0">({{ attachment.size_formatted }})</span>
          </label>
        </div>
        <div class="flex items-center gap-1 flex-shrink-0">
          <Button
            variant="ghost"
            size="sm"
            class="h-7 w-7 p-0"
            @click="downloadAttachment(attachment)"
            :title="$t('email.download')"
          >
            <Download class="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="sm"
            class="h-7 w-7 p-0 text-destructive"
            @click="$emit('delete', attachment)"
            :title="$t('email.delete')"
          >
            <Trash2 class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <div v-else class="text-sm text-muted-foreground py-2">
      {{ $t('email.noAttachments') }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Upload, FileIcon, Download, Trash2, Loader2, AlertCircle, X } from 'lucide-vue-next'
import { useAsyncUpload } from '@/composables/useAsyncUpload'

const props = defineProps({
  attachments: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
  matterId: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue', 'uploaded', 'delete'])

const fileInput = ref(null)
const uploadError = ref(null)

// Use composable for proper async upload handling
const { uploading, uploadFile } = useAsyncUpload(props.matterId)

// Use Set for O(1) lookup
const selectedSet = computed(() => new Set(props.modelValue))

const isSelected = (id) => selectedSet.value.has(id)

const triggerUpload = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (event) => {
  const files = event.target.files
  if (!files.length) return

  uploadError.value = null

  // Upload files sequentially with proper async handling
  for (const file of files) {
    try {
      await uploadFile(file, (data) => {
        emit('uploaded', data)
      })
    } catch (error) {
      uploadError.value = error.response?.data?.message || `Failed to upload ${file.name}`
    }
  }

  // Reset file input
  event.target.value = ''
}

const toggleAttachment = (id, checked) => {
  const newValue = checked
    ? [...props.modelValue, id]
    : props.modelValue.filter(i => i !== id)
  emit('update:modelValue', newValue)
}

const downloadAttachment = (attachment) => {
  window.open(`/matter/${props.matterId}/attachments/${attachment.id}/download`, '_blank')
}
</script>
