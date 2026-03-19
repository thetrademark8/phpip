<template>
  <MainLayout :title="t('Branded Data Import')">
    <div class="container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Header -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ t('Branded Data Import') }}</h1>
        <p class="text-lg text-muted-foreground">{{ t('Import actors and matters from CSV files') }}</p>
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
        <AlertTriangle class="h-4 w-4" />
        <AlertDescription>
          {{ errorMessage }}
        </AlertDescription>
      </Alert>

      <!-- Responsible Actor Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <UserCog class="h-5 w-5 text-primary" />
            {{ t('Responsible actor') }}
          </CardTitle>
          <CardDescription>
            {{ t('Select or create the actor responsible for generated tasks') }}
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-0">
          <Tabs v-model="actorMode" class="w-full">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="existing">{{ t('Select existing user') }}</TabsTrigger>
              <TabsTrigger value="create">{{ t('Create new actor') }}</TabsTrigger>
            </TabsList>

            <!-- Existing user -->
            <TabsContent value="existing" class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ t('User') }}</label>
                <AutocompleteInput
                  v-model="responsibleLogin"
                  v-model:display-model-value="responsibleDisplay"
                  endpoint="/user/autocomplete"
                  :placeholder="t('Select responsible user')"
                  value-key="key"
                  label-key="value"
                />
              </div>
            </TabsContent>

            <!-- Create new actor -->
            <TabsContent value="create" class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ t('Name') }}</label>
                <Input v-model="newActorName" :placeholder="t('e.g. Gestion Ines-PI')" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ t('Login') }}</label>
                <Input v-model="newActorLogin" :placeholder="t('e.g. gestion.ines-pi')" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ t('Password') }}</label>
                <Input v-model="newActorPassword" type="password" />
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <!-- Upload Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <Upload class="h-5 w-5 text-primary" />
            {{ t('CSV Files') }}
          </CardTitle>
          <CardDescription>
            {{ t('Upload actors and matters CSV files (semicolon-separated, max 10MB each)') }}
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-0 space-y-6">
          <!-- Actors file input -->
          <div class="space-y-2">
            <label class="text-sm font-medium">{{ t('Actors file') }} (actors.csv)</label>
            <Input
              type="file"
              accept=".csv,.txt"
              class="cursor-pointer"
              @change="actorsFile = $event.target.files?.[0] ?? null"
            />
          </div>
          <!-- Matters file input -->
          <div class="space-y-2">
            <label class="text-sm font-medium">{{ t('Matters file') }} (matters.csv)</label>
            <Input
              type="file"
              accept=".csv,.txt"
              class="cursor-pointer"
              @change="mattersFile = $event.target.files?.[0] ?? null"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Action buttons -->
      <div class="flex flex-wrap gap-4 justify-end">
        <Button
          variant="outline"
          :disabled="!canPreview || isPreviewing"
          @click="runPreview"
        >
          <Eye v-if="!isPreviewing" class="mr-2 h-4 w-4" />
          <span v-else class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
          {{ isPreviewing ? t('Previewing...') : t('Preview') }}
        </Button>
        <Button
          :disabled="!canImport || isImporting"
          @click="runImport"
        >
          <FileUp v-if="!isImporting" class="mr-2 h-4 w-4" />
          <span v-else class="mr-2 h-4 w-4 animate-spin inline-block border-2 border-current border-t-transparent rounded-full" />
          {{ isImporting ? t('Importing...') : t('Import') }}
        </Button>
      </div>

      <!-- Preview Results Card -->
      <Card v-if="previewData">
        <CardHeader class="pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xl font-semibold flex items-center gap-2">
              <Eye class="h-5 w-5 text-primary" />
              {{ t('Preview Results') }}
            </CardTitle>
            <Button variant="ghost" size="sm" @click="previewData = null">
              &times;
            </Button>
          </div>
        </CardHeader>
        <CardContent class="pt-0 space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border p-4 text-center">
              <p class="text-2xl font-bold">{{ previewData.actors_count }}</p>
              <p class="text-sm text-muted-foreground">{{ t('Actors') }}</p>
            </div>
            <div class="rounded-lg border p-4 text-center">
              <p class="text-2xl font-bold">{{ previewData.matters_count }}</p>
              <p class="text-sm text-muted-foreground">{{ t('Matters') }}</p>
            </div>
          </div>

          <!-- Actors preview -->
          <div v-if="previewData.actors_preview?.length" class="space-y-2">
            <h3 class="text-sm font-semibold">{{ t('First actors') }}</h3>
            <ul class="space-y-1 text-sm">
              <li v-for="(actor, i) in previewData.actors_preview" :key="'actor-' + i" class="rounded border px-3 py-2">
                <span class="font-medium">{{ actor.name }}</span>
                <span v-if="actor.display_name" class="text-muted-foreground"> - {{ actor.display_name }}</span>
              </li>
            </ul>
          </div>

          <!-- Matters preview -->
          <div v-if="previewData.matters_preview?.length" class="space-y-2">
            <h3 class="text-sm font-semibold">{{ t('First matters') }}</h3>
            <ul class="space-y-1 text-sm">
              <li v-for="(matter, i) in previewData.matters_preview" :key="'matter-' + i" class="rounded border px-3 py-2">
                {{ matter.caseref }}
              </li>
            </ul>
          </div>
        </CardContent>
      </Card>

      <!-- Import Results Card -->
      <Card v-if="importResult">
        <CardHeader class="pb-4">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xl font-semibold flex items-center gap-2">
              <CheckCircle2 class="h-5 w-5 text-green-600" />
              {{ t('Import Results') }}
            </CardTitle>
            <Button variant="ghost" size="sm" @click="importResult = null">
              &times;
            </Button>
          </div>
        </CardHeader>
        <CardContent class="pt-0 space-y-4">
          <ul v-if="importResult.stats" class="space-y-2 text-sm">
            <li v-if="importResult.stats.actors_created != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Actors created') }}</span>
              <span class="font-medium">{{ importResult.stats.actors_created }}</span>
            </li>
            <li v-if="importResult.stats.actors_updated != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Actors updated') }}</span>
              <span class="font-medium">{{ importResult.stats.actors_updated }}</span>
            </li>
            <li v-if="importResult.stats.matters_created != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Matters created') }}</span>
              <span class="font-medium">{{ importResult.stats.matters_created }}</span>
            </li>
            <li v-if="importResult.stats.matters_updated != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Matters updated') }}</span>
              <span class="font-medium">{{ importResult.stats.matters_updated }}</span>
            </li>
            <li v-if="importResult.stats.events_upserted != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Events upserted') }}</span>
              <span class="font-medium">{{ importResult.stats.events_upserted }}</span>
            </li>
            <li v-if="importResult.stats.classifiers_upserted != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Classifiers upserted') }}</span>
              <span class="font-medium">{{ importResult.stats.classifiers_upserted }}</span>
            </li>
            <li v-if="importResult.stats.actor_links_upserted != null" class="flex justify-between rounded border px-3 py-2">
              <span>{{ t('Actor links upserted') }}</span>
              <span class="font-medium">{{ importResult.stats.actor_links_upserted }}</span>
            </li>
          </ul>

          <!-- Warnings -->
          <div v-if="importResult.warnings?.length" class="space-y-2">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-amber-600">
              <AlertTriangle class="h-4 w-4" />
              {{ t('Warnings') }} ({{ importResult.warnings.length }})
            </h3>
            <ul class="space-y-1 text-sm">
              <li v-for="(warning, i) in importResult.warnings" :key="'warn-' + i" class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                {{ warning }}
              </li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import { Upload, Eye, FileUp, CheckCircle2, AlertTriangle, UserCog } from 'lucide-vue-next'

const { t } = useI18n()

// --- Helpers ---

const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

// --- State ---

const actorsFile = ref(null)
const mattersFile = ref(null)
const isPreviewing = ref(false)
const isImporting = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const previewData = ref(null)
const importResult = ref(null)

// Responsible actor
const actorMode = ref('existing')
const responsibleLogin = ref('')
const responsibleDisplay = ref('')
const newActorName = ref('')
const newActorLogin = ref('')
const newActorPassword = ref('')

const hasFiles = computed(() => actorsFile.value && mattersFile.value)
const canPreview = computed(() => hasFiles.value)

const hasResponsible = computed(() => {
  if (actorMode.value === 'existing') {
    return !!responsibleLogin.value
  }
  return !!newActorName.value && !!newActorLogin.value && !!newActorPassword.value && newActorPassword.value.length >= 8
})

const canImport = computed(() => hasFiles.value && hasResponsible.value)

// --- Build FormData ---

const buildFormData = () => {
  const formData = new FormData()
  formData.append('actors_file', actorsFile.value)
  formData.append('matters_file', mattersFile.value)
  return formData
}

const buildImportFormData = () => {
  const formData = buildFormData()
  formData.append('actor_mode', actorMode.value)

  if (actorMode.value === 'existing') {
    formData.append('responsible_login', responsibleLogin.value)
  } else {
    formData.append('responsible_name', newActorName.value)
    formData.append('responsible_login', newActorLogin.value)
    formData.append('responsible_password', newActorPassword.value)
  }

  return formData
}

// --- Preview ---

const runPreview = async () => {
  isPreviewing.value = true
  errorMessage.value = ''
  successMessage.value = ''
  previewData.value = null
  importResult.value = null

  try {
    const response = await fetch('/settings/branded-import/preview', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: buildFormData(),
    })

    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      throw new Error(data.message || `Preview failed (${response.status})`)
    }

    const data = await response.json()
    previewData.value = data.data
  } catch (error) {
    errorMessage.value = error.message || t('An error occurred while previewing the files')
  } finally {
    isPreviewing.value = false
  }
}

// --- Import ---

const runImport = async () => {
  isImporting.value = true
  errorMessage.value = ''
  successMessage.value = ''
  previewData.value = null
  importResult.value = null

  try {
    const response = await fetch('/settings/branded-import', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: buildImportFormData(),
    })

    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      throw new Error(data.message || `Import failed (${response.status})`)
    }

    const data = await response.json()
    importResult.value = data
    successMessage.value = t('Import completed successfully')
  } catch (error) {
    errorMessage.value = error.message || t('An error occurred while importing the files')
  } finally {
    isImporting.value = false
  }
}
</script>
