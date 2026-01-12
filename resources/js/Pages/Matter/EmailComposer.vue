<template>
  <MainLayout :title="`${$t('email.compose')} - ${matter.uid}`">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link :href="`/matter/${matter.id}`">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-1" />
              {{ $t('Back') }}
            </Button>
          </Link>
          <h1 class="text-xl font-semibold">{{ $t('email.compose') }}</h1>
          <Badge variant="outline">{{ matter.uid }}</Badge>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Left Panel: Placeholders -->
        <Card class="lg:col-span-1">
          <CardHeader class="py-3">
            <CardTitle class="text-sm">{{ $t('email.placeholders') }}</CardTitle>
          </CardHeader>
          <CardContent>
            <PlaceholderPanel
              :placeholders="placeholders"
              @insert="insertPlaceholder"
            />
          </CardContent>
        </Card>

        <!-- Main Panel: Email Form -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Template Selection -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">{{ $t('email.selectTemplate') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <Select v-model="selectedTemplateId" @update:modelValue="handleTemplateChange">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('email.selectTemplate')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">{{ $t('email.noTemplate') }}</SelectItem>
                  <SelectItem
                    v-for="template in templates"
                    :key="template.id"
                    :value="template.id"
                  >
                    {{ template.summary }} ({{ template.language }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </CardContent>
          </Card>

          <!-- Subject -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">{{ $t('email.subject') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <Input
                v-model="form.subject"
                :placeholder="$t('email.subjectPlaceholder')"
              />
            </CardContent>
          </Card>

          <!-- Body -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">{{ $t('email.body') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <TipTapEditor
                ref="editorRef"
                v-model="form.body"
                :placeholder="$t('email.bodyPlaceholder')"
              />
            </CardContent>
          </Card>

          <!-- CC/BCC -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">CC / BCC</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <EmailTagInput
                v-model="form.cc"
                label="CC"
                :placeholder="$t('email.ccPlaceholder')"
              />
              <EmailTagInput
                v-model="form.bcc"
                label="BCC"
                :placeholder="$t('email.bccPlaceholder')"
              />
            </CardContent>
          </Card>
        </div>

        <!-- Right Panel: Recipients & Attachments -->
        <div class="lg:col-span-1 space-y-4">
          <!-- Recipients -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">{{ $t('email.recipients') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <RecipientSelector
                v-model="form.recipient_ids"
                :recipients="recipients"
              />
            </CardContent>
          </Card>

          <!-- Attachments -->
          <Card>
            <CardHeader class="py-3">
              <CardTitle class="text-sm">{{ $t('email.attachments') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <AttachmentManager
                v-model="form.attachment_ids"
                :attachments="matterAttachments"
                :matter-id="matter.id"
                @uploaded="handleAttachmentUploaded"
                @delete="deleteAttachment"
              />
            </CardContent>
          </Card>

          <!-- Actions -->
          <Card>
            <CardContent class="pt-4 space-y-2">
              <Button
                class="w-full"
                variant="outline"
                @click="previewEmail"
                :disabled="!canSend || previewing"
              >
                <Eye class="h-4 w-4 mr-2" />
                {{ $t('email.preview') }}
              </Button>
              <Button
                class="w-full"
                @click="openConfirmDialog"
                :disabled="!canSend || sending"
              >
                <Send class="h-4 w-4 mr-2" />
                {{ $t('email.send') }}
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Email History Section -->
      <Card>
        <CardHeader class="py-3">
          <CardTitle class="text-sm">{{ $t('email.history') }}</CardTitle>
        </CardHeader>
        <CardContent>
          <EmailHistory
            :emails="emailHistory"
            :pagination="historyPagination"
            :loading="loadingHistory"
            @view="viewEmail"
            @page="loadHistory"
          />
        </CardContent>
      </Card>
    </div>

    <!-- Preview Dialog -->
    <EmailPreviewDialog
      v-model:open="showPreview"
      :preview="previewData"
      :loading="previewing"
      :recipients="selectedRecipients"
      :cc="form.cc"
      :bcc="form.bcc"
      :attachments="selectedAttachments"
      @send="sendEmail"
    />

    <!-- Send Confirmation Dialog -->
    <SendConfirmDialog
      v-model:open="showConfirmSend"
      :recipients="selectedRecipients"
      :cc="form.cc"
      :bcc="form.bcc"
      :attachments="selectedAttachments"
      :subject="form.subject"
      :sending="sending"
      @confirm="sendEmail"
    />

    <!-- View Email Dialog -->
    <EmailViewDialog
      v-model:open="showViewEmail"
      :email="viewingEmail"
      :attachments="viewingAttachments"
      :matter-id="matter.id"
      :loading="loadingEmail"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import TipTapEditor from '@/components/ui/TipTapEditor.vue'
import PlaceholderPanel from '@/components/email/PlaceholderPanel.vue'
import RecipientSelector from '@/components/email/RecipientSelector.vue'
import AttachmentManager from '@/components/email/AttachmentManager.vue'
import EmailTagInput from '@/components/email/EmailTagInput.vue'
import EmailHistory from '@/components/email/EmailHistory.vue'
import EmailPreviewDialog from '@/components/dialogs/EmailPreviewDialog.vue'
import EmailViewDialog from '@/components/dialogs/EmailViewDialog.vue'
import SendConfirmDialog from '@/components/dialogs/SendConfirmDialog.vue'
import { ArrowLeft, Eye, Send } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import axios from 'axios'
import { useEmailForm } from '@/composables/useEmailForm'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  matter: Object,
  recipients: Array,
  templates: Array,
  placeholders: Object,
  attachments: Array,
})

// Form state via composable
const { form, isDirty, canSend, loadTemplate, resetAfterSend } = useEmailForm()

// Local state
const editorRef = ref(null)
const selectedTemplateId = ref(null)
const matterAttachments = ref(props.attachments || [])

// UI state
const sending = ref(false)
const previewing = ref(false)
const showPreview = ref(false)
const previewData = ref(null)
const showConfirmSend = ref(false)
const showViewEmail = ref(false)
const viewingEmail = ref(null)
const viewingAttachments = ref([])
const loadingEmail = ref(false)

// History state
const emailHistory = ref([])
const historyPagination = ref(null)
const loadingHistory = ref(false)

// Computed: selected recipients as objects for display
const selectedRecipients = computed(() => {
  return props.recipients.filter(r => form.recipient_ids.includes(r.id))
})

// Computed: selected attachments as objects for display
const selectedAttachments = computed(() => {
  return matterAttachments.value.filter(a => form.attachment_ids.includes(a.id))
})

// Handle template change
const handleTemplateChange = (templateId) => {
  if (!templateId) {
    loadTemplate(null)
    return
  }
  const id = parseInt(templateId, 10)
  const template = props.templates.find(t => t.id === id)
  loadTemplate(template)
}

// Insert placeholder at cursor
const insertPlaceholder = (placeholder) => {
  editorRef.value?.insertText(placeholder)
}

// Handle attachment uploaded
const handleAttachmentUploaded = (attachment) => {
  matterAttachments.value.unshift(attachment)
}

// Delete attachment
const deleteAttachment = async (attachment) => {
  try {
    await axios.delete(`/matter/${props.matter.id}/attachments/${attachment.id}`)
    matterAttachments.value = matterAttachments.value.filter(a => a.id !== attachment.id)
    form.attachment_ids = form.attachment_ids.filter(id => id !== attachment.id)
    toast.success(t('email.attachmentDeleted'))
  } catch (error) {
    toast.error(error.response?.data?.message || t('email.deleteFailed'))
  }
}

// Preview email
const previewEmail = async () => {
  previewing.value = true
  showPreview.value = true

  try {
    const response = await axios.post(`/matter/${props.matter.id}/email/preview`, {
      subject: form.subject,
      body: form.body,
      recipient_id: form.recipient_ids[0] || null,
    })
    previewData.value = response.data
  } catch (error) {
    toast.error(error.response?.data?.message || t('email.previewFailed'))
  } finally {
    previewing.value = false
  }
}

// Open confirmation dialog
const openConfirmDialog = () => {
  showConfirmSend.value = true
}

// Send email
const sendEmail = async () => {
  sending.value = true
  showPreview.value = false
  showConfirmSend.value = false

  try {
    const response = await axios.post(`/matter/${props.matter.id}/email/send`, {
      recipient_ids: form.recipient_ids,
      subject: form.subject,
      body: form.body,
      cc: form.cc,
      bcc: form.bcc,
      attachment_ids: form.attachment_ids,
      template_id: form.template_id,
    })

    if (response.data.success) {
      toast.success(response.data.message || t('email.sentSuccessfully'))
      loadHistory(1)
      resetAfterSend()
    } else {
      toast.warning(response.data.message || t('email.partialFailure'))
    }
  } catch (error) {
    toast.error(error.response?.data?.message || t('email.sendFailed'))
  } finally {
    sending.value = false
  }
}

// Load email history
const loadHistory = async (page = 1) => {
  loadingHistory.value = true
  try {
    const response = await axios.get(`/matter/${props.matter.id}/email/history`, {
      params: { page },
    })
    emailHistory.value = response.data.data
    historyPagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
    }
  } catch (error) {
    console.error('Failed to load history', error)
  } finally {
    loadingHistory.value = false
  }
}

// View specific email
const viewEmail = async (email) => {
  showViewEmail.value = true
  loadingEmail.value = true
  viewingEmail.value = null
  viewingAttachments.value = []

  try {
    const response = await axios.get(`/matter/${props.matter.id}/email/${email.id}`)
    viewingEmail.value = response.data.email
    viewingAttachments.value = response.data.attachments
  } catch (error) {
    toast.error(t('email.loadFailed'))
  } finally {
    loadingEmail.value = false
  }
}

// Unsaved changes warning
const handleBeforeUnload = (e) => {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = t('email.unsavedChanges')
    return e.returnValue
  }
}

onMounted(() => {
  loadHistory()
  window.addEventListener('beforeunload', handleBeforeUnload)
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>
