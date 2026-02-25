<template>
  <div class="space-y-4">
        <Textarea
            v-if="canEdit"
            v-model="localNotes"
            class="min-h-[100px] font-mono"
            :placeholder="$t('Add notes...')"
            @blur="saveNotes"
        />
    <div v-else class="whitespace-pre-wrap">
      {{ notes || $t('No notes') }}
    </div>

  </div>
</template>

<script setup>
import {ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import {Card, CardContent, CardHeader, CardTitle} from '@/components/ui/card'
import {Textarea} from '@/components/ui/textarea'
import {toast} from 'vue-sonner'
import {useI18n} from 'vue-i18n'

const props = defineProps({
  notes: String,
  matterId: [String, Number],
  canEdit: Boolean
})

const emit = defineEmits(['update'])

const {t} = useI18n()
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

</script>