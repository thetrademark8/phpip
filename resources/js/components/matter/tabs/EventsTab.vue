<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Events Timeline</CardTitle>
        <Button
          v-if="enableInlineEdit"
          size="sm"
          @click="showEventManagerDialog = true"
        >
          <Settings class="mr-1 h-3 w-3" />
          Manage Events
        </Button>
      </div>
    </CardHeader>
    <CardContent>
      <EventTimeline
        :events="events"
        :enable-inline-edit="false"
        @update="handleEventUpdate"
      />
    </CardContent>
  </Card>

  <MatterEventManager
    v-model:open="showEventManagerDialog"
    :matter="matter"
    :events="events"
    @success="handleEventAdded"
  />
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Settings } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import EventTimeline from '@/Components/display/EventTimeline.vue'
import MatterEventManager from '@/Components/dialogs/MatterEventManager.vue'

const props = defineProps({
  events: Array,
  matterId: [String, Number],
  matter: Object,
  enableInlineEdit: Boolean
})

const showEventManagerDialog = ref(false)

function handleEventUpdate() {
  router.reload({ only: ['matter'] })
}

function handleEventAdded() {
  router.reload({ only: ['matter'] })
}
</script>