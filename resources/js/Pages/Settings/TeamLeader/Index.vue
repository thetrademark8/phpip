<template>
  <MainLayout :title="$t('teamleader.title')">
    <div class="container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Header -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ $t('teamleader.title') }}</h1>
        <p class="text-lg text-muted-foreground">{{ $t('teamleader.description') }}</p>
      </div>

      <!-- Flash Messages -->
      <Alert v-if="$page.props.flash?.success" variant="default" class="border-green-500 bg-green-50 dark:bg-green-950">
        <CheckCircle2 class="h-4 w-4 text-green-600" />
        <AlertDescription class="text-green-800 dark:text-green-200">
          {{ $page.props.flash.success }}
        </AlertDescription>
      </Alert>

      <Alert v-if="$page.props.flash?.error" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>
          {{ $page.props.flash.error }}
        </AlertDescription>
      </Alert>

      <!-- Not Enabled Warning -->
      <Alert v-if="!enabled" variant="default" class="border-amber-500 bg-amber-50 dark:bg-amber-950">
        <AlertTriangle class="h-4 w-4 text-amber-600" />
        <AlertDescription class="text-amber-800 dark:text-amber-200">
          {{ $t('teamleader.not_enabled') }}
        </AlertDescription>
      </Alert>

      <!-- Connection Status Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <Link2 class="h-5 w-5 text-primary" />
            {{ $t('teamleader.connection_status') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="pt-0 space-y-6">
          <!-- Status Indicator -->
          <div class="flex items-center gap-4 p-4 rounded-lg" :class="statusBackgroundClass">
            <div class="p-3 rounded-full" :class="statusIconClass">
              <CheckCircle2 v-if="status.connected" class="h-6 w-6" />
              <XCircle v-else class="h-6 w-6" />
            </div>
            <div>
              <p class="font-medium text-lg">
                {{ status.connected ? $t('teamleader.status.connected') : $t('teamleader.status.disconnected') }}
              </p>
              <p class="text-sm text-muted-foreground">
                {{ status.connected ? $t('teamleader.status.connected_description') : $t('teamleader.status.disconnected_description') }}
              </p>
            </div>
          </div>

          <!-- Connection Details (when connected) -->
          <div v-if="status.connected" class="space-y-4">
            <Separator />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Token Status -->
              <div class="p-4 rounded-lg bg-muted/50 space-y-2">
                <div class="flex items-center gap-2">
                  <Key class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm font-medium">{{ $t('teamleader.token_status') }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :variant="status.token_valid ? 'default' : 'destructive'">
                    {{ status.token_valid ? $t('teamleader.token.valid') : $t('teamleader.token.expired') }}
                  </Badge>
                </div>
                <p v-if="status.expires_at" class="text-xs text-muted-foreground">
                  {{ $t('teamleader.token.expires_at') }}: {{ formatDate(status.expires_at) }}
                </p>
              </div>

              <!-- Webhook Status -->
              <div class="p-4 rounded-lg bg-muted/50 space-y-2">
                <div class="flex items-center gap-2">
                  <Webhook class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm font-medium">{{ $t('teamleader.webhook_status') }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Badge :variant="status.webhook_id ? 'default' : 'secondary'">
                    {{ status.webhook_id ? $t('teamleader.webhook.registered') : $t('teamleader.webhook.not_registered') }}
                  </Badge>
                </div>
              </div>
            </div>

            <!-- Last Updated -->
            <p v-if="status.updated_at" class="text-sm text-muted-foreground text-center">
              {{ $t('teamleader.last_updated') }}: {{ formatDate(status.updated_at) }}
            </p>
          </div>

          <!-- Actions -->
          <Separator />

          <div class="flex flex-wrap gap-4 justify-end">
            <Button
              v-if="status.connected"
              variant="outline"
              :disabled="isLoading"
              @click="testConnection"
            >
              <RefreshCw v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
              <Plug v-else class="mr-2 h-4 w-4" />
              {{ $t('teamleader.actions.test') }}
            </Button>

            <Button
              v-if="status.connected"
              variant="destructive"
              :disabled="isLoading"
              @click="disconnect"
            >
              <Unplug class="mr-2 h-4 w-4" />
              {{ $t('teamleader.actions.disconnect') }}
            </Button>

            <Button
              v-else
              :disabled="!enabled || isLoading"
              @click="authenticate"
            >
              <ExternalLink class="mr-2 h-4 w-4" />
              {{ $t('teamleader.actions.connect') }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Help Card -->
      <Card>
        <CardHeader class="pb-4">
          <CardTitle class="text-lg font-semibold flex items-center gap-2">
            <HelpCircle class="h-5 w-5 text-primary" />
            {{ $t('teamleader.help.title') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="pt-0">
          <div class="space-y-4 text-sm text-muted-foreground">
            <p>{{ $t('teamleader.help.description') }}</p>
            <ul class="list-disc list-inside space-y-1">
              <li>{{ $t('teamleader.help.sync_companies') }}</li>
              <li>{{ $t('teamleader.help.sync_contacts') }}</li>
              <li>{{ $t('teamleader.help.realtime_updates') }}</li>
            </ul>
            <p class="text-xs">
              {{ $t('teamleader.help.config_note') }}
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  Link2,
  CheckCircle2,
  XCircle,
  Key,
  Webhook,
  RefreshCw,
  Plug,
  Unplug,
  ExternalLink,
  HelpCircle,
  AlertCircle,
  AlertTriangle
} from 'lucide-vue-next'

const props = defineProps({
  status: {
    type: Object,
    required: true
  },
  enabled: {
    type: Boolean,
    default: false
  }
})

const { t } = useI18n()
const page = usePage()
const isLoading = ref(false)

const statusBackgroundClass = computed(() => {
  return props.status.connected
    ? 'bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800'
    : 'bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800'
})

const statusIconClass = computed(() => {
  return props.status.connected
    ? 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400'
    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

const authenticate = () => {
  window.location.href = route('settings.teamleader.authenticate')
}

const disconnect = () => {
  if (confirm(t('teamleader.confirm_disconnect'))) {
    isLoading.value = true
    router.post(route('settings.teamleader.disconnect'), {}, {
      onFinish: () => {
        isLoading.value = false
      }
    })
  }
}

const testConnection = () => {
  isLoading.value = true
  router.post(route('settings.teamleader.test'), {}, {
    onFinish: () => {
      isLoading.value = false
    }
  })
}
</script>
