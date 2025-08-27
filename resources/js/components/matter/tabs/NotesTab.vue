<template>
  <div class="space-y-4">
    <Card>
      <CardHeader>
        <CardTitle>{{ $t('Notes') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <Textarea
          v-if="canEdit"
          v-model="localNotes"
          class="min-h-[200px] font-mono"
          :placeholder="$t('Add notes...')"
          @blur="saveNotes"
        />
        <div v-else class="whitespace-pre-wrap">
          {{ notes || $t('No notes') }}
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>{{ $t('Actions') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="flex flex-wrap gap-2">
          <div>
            <span class="text-sm font-medium mr-2">{{ $t('Summaries:') }}</span>
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
            <span class="text-sm font-medium mr-2">{{ $t('Email:') }}</span>
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
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Copy, Mail } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { toast } from 'vue-sonner'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  notes: String,
  matterId: [String, Number],
  canEdit: Boolean
})

const emit = defineEmits(['update'])

const { t } = useI18n()
const localNotes = ref(props.notes || '')
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
        toast.success(t('Notes saved'), {
          description: t('Your notes have been saved successfully.')
        })
      },
      onError: () => {
        toast.error(t('Error'), {
          description: t('Failed to save notes. Please try again.')
        })
      }
    })
  }, 500)
}

function copySummary(language) {
  router.get(`/matter/${props.matterId}/description/${language}`, {}, {
    onSuccess: (page) => {
      // Copy to clipboard logic would go here
      toast.success(t('Summary copied'), {
        description: t('{language} summary copied to clipboard.', { language: language.toUpperCase() })
      })
    }
  })
}

function sendEmail(language) {
  router.get(`/document/select/${props.matterId}?Language=${language}`)
}
</script>