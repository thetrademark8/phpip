<template>
  <MainLayout :title="t('Brand Settings')">
    <div class="container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Header -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ t('Brand Settings') }}</h1>
        <p class="text-lg text-muted-foreground">{{ t('Manage branding, logo and email signature for your company') }}</p>
      </div>

      <!-- Success Message -->
      <Alert v-if="successMessage" variant="default" class="border-green-500 bg-green-50 dark:bg-green-950">
        <CheckCircle2 class="h-4 w-4 text-green-600" />
        <AlertDescription class="text-green-800 dark:text-green-200">
          {{ successMessage }}
        </AlertDescription>
      </Alert>

      <!-- Error Message -->
      <Alert v-if="errorMessage" variant="destructive">
        <CheckCircle2 class="h-4 w-4" />
        <AlertDescription>
          {{ errorMessage }}
        </AlertDescription>
      </Alert>

      <!-- Logos Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <Image class="h-5 w-5 text-primary" />
            {{ t('Logos') }}
          </CardTitle>
          <CardDescription>
            {{ t('Upload separate logos for the website and for emails') }}
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-0 space-y-8">
          <!-- Site Logo -->
          <div class="space-y-4">
            <div class="space-y-1">
              <h3 class="text-sm font-semibold">{{ t('Site Logo') }}</h3>
              <p class="text-sm text-muted-foreground">{{ t('Displayed in the navigation bar and login page') }}</p>
            </div>
            <div v-if="siteLogo" class="p-4 rounded-lg border bg-muted/30 inline-block">
              <img :src="siteLogo" :alt="t('Site Logo')" class="max-h-16 max-w-xs object-contain" />
            </div>
            <div v-else class="p-4 rounded-lg border border-dashed bg-muted/20">
              <p class="text-sm text-muted-foreground italic">{{ t('No logo uploaded') }}</p>
            </div>
            <div class="flex items-end gap-3">
              <div class="space-y-2 flex-1">
                <Input
                  type="file"
                  accept="image/png,image/jpeg,image/svg+xml,image/gif"
                  class="cursor-pointer"
                  @change="siteFile = $event.target.files?.[0] ?? null"
                />
                <p class="text-xs text-muted-foreground">{{ t('Accepted formats: PNG, JPG, SVG, GIF (max 2MB)') }}</p>
              </div>
              <Button :disabled="!siteFile || isUploadingSite" @click="upload('site')">
                <Upload v-if="!isUploadingSite" class="mr-2 h-4 w-4" />
                <span v-else class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
                {{ isUploadingSite ? t('Uploading...') : t('Upload') }}
              </Button>
            </div>
          </div>

          <Separator />

          <!-- Email Logo -->
          <div class="space-y-4">
            <div class="space-y-1">
              <h3 class="text-sm font-semibold">{{ t('Email Logo') }}</h3>
              <p class="text-sm text-muted-foreground">{{ t('Displayed in email signatures and headers. Falls back to the site logo if not set.') }}</p>
            </div>
            <div v-if="emailLogo" class="p-4 rounded-lg border bg-muted/30 inline-block">
              <img :src="emailLogo" :alt="t('Email Logo')" class="max-h-16 max-w-xs object-contain" />
            </div>
            <div v-else class="p-4 rounded-lg border border-dashed bg-muted/20">
              <p class="text-sm text-muted-foreground italic">{{ t('No logo uploaded — using site logo') }}</p>
            </div>
            <div class="flex items-end gap-3">
              <div class="space-y-2 flex-1">
                <Input
                  type="file"
                  accept="image/png,image/jpeg,image/svg+xml,image/gif"
                  class="cursor-pointer"
                  @change="emailFile = $event.target.files?.[0] ?? null"
                />
                <p class="text-xs text-muted-foreground">{{ t('Accepted formats: PNG, JPG, SVG, GIF (max 2MB)') }}</p>
              </div>
              <Button :disabled="!emailFile || isUploadingEmail" @click="upload('email')">
                <Upload v-if="!isUploadingEmail" class="mr-2 h-4 w-4" />
                <span v-else class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
                {{ isUploadingEmail ? t('Uploading...') : t('Upload') }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Email Branding Tabs -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <Mail class="h-5 w-5 text-primary" />
            {{ t('Email Settings') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="pt-0">
          <Tabs default-value="signature" class="w-full">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="signature">{{ t('Email Signature') }}</TabsTrigger>
              <TabsTrigger value="header">{{ t('Email Header') }}</TabsTrigger>
              <TabsTrigger value="footer">{{ t('Email Footer') }}</TabsTrigger>
            </TabsList>

            <!-- Signature Tab -->
            <TabsContent value="signature" class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium leading-none">
                  {{ t('Email Signature') }}
                </label>
                <p class="text-sm text-muted-foreground">
                  {{ t('HTML signature text displayed under the logo in all outgoing emails') }}
                </p>
              </div>
              <Textarea
                v-model="form.email_signature"
                :placeholder="t('Email Signature')"
                class="min-h-40 font-mono text-sm"
              />
            </TabsContent>

            <!-- Header Tab -->
            <TabsContent value="header" class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium leading-none">
                  {{ t('Email Header') }}
                </label>
                <p class="text-sm text-muted-foreground">
                  {{ t('HTML content displayed at the top of matter emails') }}
                </p>
              </div>
              <Textarea
                v-model="form.email_header"
                :placeholder="t('Email Header')"
                class="min-h-40 font-mono text-sm"
              />
            </TabsContent>

            <!-- Footer Tab -->
            <TabsContent value="footer" class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium leading-none">
                  {{ t('Email Footer') }}
                </label>
                <p class="text-sm text-muted-foreground">
                  {{ t('HTML content displayed at the bottom of matter emails, before the signature') }}
                </p>
              </div>
              <Textarea
                v-model="form.email_footer"
                :placeholder="t('Email Footer')"
                class="min-h-40 font-mono text-sm"
              />
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex flex-wrap gap-4 justify-end">
        <Button
          variant="outline"
          :disabled="isLoadingPreview"
          @click="fetchPreview"
        >
          <Eye v-if="!isLoadingPreview" class="mr-2 h-4 w-4" />
          <span v-if="isLoadingPreview" class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
          {{ t('Preview') }}
        </Button>
        <Button
          :disabled="isSaving"
          @click="saveSettings"
        >
          <Save v-if="!isSaving" class="mr-2 h-4 w-4" />
          <span v-if="isSaving" class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
          {{ isSaving ? t('Saving...') : t('Save Settings') }}
        </Button>
      </div>

      <!-- Preview Card -->
      <Card v-if="previewHtml">
        <CardHeader class="pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xl font-semibold flex items-center gap-2">
              <Eye class="h-5 w-5 text-primary" />
              {{ t('Email Preview') }}
            </CardTitle>
            <Button variant="ghost" size="sm" @click="previewHtml = ''">
              &times;
            </Button>
          </div>
        </CardHeader>
        <CardContent class="pt-0">
          <div class="rounded-lg border bg-white">
            <iframe
              ref="previewFrame"
              :srcdoc="previewHtml"
              class="w-full min-h-[400px] border-0 rounded-lg"
              sandbox="allow-same-origin"
            />
          </div>
        </CardContent>
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Mail, Upload, Eye, Save, Image, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  site_logo: {
    type: String,
    default: '',
  },
  email_logo: {
    type: String,
    default: '',
  },
})

const { t } = useI18n()

// --- Helpers ---

const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const findSettingValue = (key) => {
  const setting = props.settings?.branding?.find((s) => s.key === key)
  return setting?.value ?? ''
}

// --- Form state ---

const form = ref({
  email_header: findSettingValue('email_header'),
  email_footer: findSettingValue('email_footer'),
  email_signature: findSettingValue('email_signature'),
})

// --- Logo upload ---

const siteLogo = ref(props.site_logo || '')
const emailLogo = ref(props.email_logo || '')
const siteFile = ref(null)
const emailFile = ref(null)
const isUploadingSite = ref(false)
const isUploadingEmail = ref(false)

const upload = async (type) => {
  const file = type === 'site' ? siteFile.value : emailFile.value
  if (!file) return

  const isUploading = type === 'site' ? isUploadingSite : isUploadingEmail
  isUploading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('logo', file)
    formData.append('type', type)

    const response = await fetch('/settings/email/upload-logo', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: formData,
    })

    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      throw new Error(data.message || `Upload failed (${response.status})`)
    }

    const data = await response.json()
    if (type === 'site') {
      siteLogo.value = data.url
      siteFile.value = null
    } else {
      emailLogo.value = data.url
      emailFile.value = null
    }
    successMessage.value = data.message || t('Settings saved successfully')
  } catch (error) {
    errorMessage.value = error.message || t('An error occurred while uploading the logo')
  } finally {
    isUploading.value = false
  }
}

// --- Save settings ---

const isSaving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const saveSettings = async () => {
  isSaving.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const payload = {
      settings: [
        { key: 'email_header', value: form.value.email_header, type: 'html', group: 'branding' },
        { key: 'email_footer', value: form.value.email_footer, type: 'html', group: 'branding' },
        { key: 'email_signature', value: form.value.email_signature, type: 'html', group: 'branding' },
      ],
    }

    const response = await fetch('/settings/email', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      throw new Error(data.message || `Save failed (${response.status})`)
    }

    successMessage.value = t('Settings saved successfully')
  } catch (error) {
    errorMessage.value = error.message || t('An error occurred while saving settings')
  } finally {
    isSaving.value = false
  }
}

// --- Preview ---

const previewHtml = ref('')
const isLoadingPreview = ref(false)
const previewFrame = ref(null)

const fetchPreview = async () => {
  isLoadingPreview.value = true
  errorMessage.value = ''

  try {
    const response = await fetch('/settings/email/preview', {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`Preview failed (${response.status})`)
    }

    const data = await response.json()
    previewHtml.value = data.html
  } catch (error) {
    errorMessage.value = error.message || t('An error occurred while loading the preview')
  } finally {
    isLoadingPreview.value = false
  }
}
</script>
