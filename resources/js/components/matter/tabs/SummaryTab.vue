<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Status Information -->
    <Card 
      :class="canEdit ? 'cursor-pointer hover:shadow-md transition-shadow' : ''"
      @click="canEdit && $emit('openStatusInfo')">
      <CardHeader class="pb-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CardTitle class="text-base">Status Information</CardTitle>
            <Pencil v-if="canEdit" class="h-3 w-3 text-muted-foreground" />
          </div>
          <Link
            :href="`/matter/${matter.id}/events`"
            class="text-muted-foreground hover:text-foreground"
            title="All events"
            @click.stop
          >
            <List class="h-4 w-4" />
          </Link>
        </div>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <div v-for="event in statusEvents" :key="event.id" class="flex justify-between text-sm">
            <span class="font-medium">{{ event.info.name }}</span>
            <div class="flex items-center gap-2">
              <span class="text-muted-foreground">
                {{ formatDate(event.alt_matter_id ? event.link?.event_date : event.event_date) }}
              </span>
              <span>
                <a 
                  v-if="event.alt_matter_id"
                  :href="`/matter/${event.alt_matter_id}`"
                  :title="event.alt_matter?.uid"
                  target="_blank"
                  class="text-primary hover:underline"
                >
                  {{ event.alt_matter?.country }} {{ event.link?.detail || event.detail }}
                </a>
                <a 
                  v-else-if="event.public_url"
                  :href="event.public_url"
                  target="_blank"
                  class="text-primary hover:underline flex items-center gap-1"
                >
                  {{ event.detail }}
                  <ExternalLink class="h-3 w-3" />
                </a>
                <span v-else>{{ event.detail }}</span>
              </span>
            </div>
          </div>
          <div v-if="!statusEvents.length" class="text-muted-foreground text-center py-4">
            No status events
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Classifiers -->
    <Card
      :class="canEdit ? 'cursor-pointer hover:shadow-md transition-shadow' : ''"
      @click="canEdit && $emit('openClassifiers')">
      <CardHeader class="pb-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <CardTitle class="text-base">Classifiers</CardTitle>
            <Pencil v-if="canEdit" class="h-3 w-3 text-muted-foreground" />
          </div>
          <Link
            :href="`/matter/${matter.id}/classifiers`"
            class="text-muted-foreground hover:text-foreground"
            title="Classifier detail"
            @click.stop
          >
            <List class="h-4 w-4" />
          </Link>
        </div>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div v-for="(classifierGroup, typeCode) in filteredClassifiers" :key="typeCode">
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-medium text-sm">{{ classifierGroup[0].type_name }}:</span>
              <div class="flex flex-wrap gap-1">
                <template v-for="classifier in classifierGroup" :key="classifier.id">
                  <a
                    v-if="classifier.url"
                    :href="classifier.url"
                    target="_blank"
                    class="inline-flex"
                  >
                    <Badge variant="default">{{ classifier.value }}</Badge>
                  </a>
                  <Link
                    v-else-if="classifier.lnk_matter_id"
                    :href="`/matter/${classifier.lnk_matter_id}`"
                    class="inline-flex"
                  >
                    <Badge variant="default">{{ classifier.linked_matter?.uid }}</Badge>
                  </Link>
                  <Badge v-else variant="secondary">{{ classifier.value }}</Badge>
                </template>
              </div>
            </div>
          </div>

          <!-- Linked By matters -->
          <div v-if="matter.linked_by?.length" class="flex flex-wrap items-center gap-2">
            <span class="font-medium text-sm">Linked by:</span>
            <div class="flex flex-wrap gap-1">
              <Link
                v-for="linkedBy in matter.linked_by"
                :key="linkedBy.id"
                :href="`/matter/${linkedBy.id}`"
                class="inline-flex"
              >
                <Badge variant="default">{{ linkedBy.uid }}</Badge>
              </Link>
            </div>
          </div>

          <div v-if="Object.keys(filteredClassifiers).length === 0 && !matter.linked_by?.length" 
               class="text-muted-foreground text-center py-4">
            No classifiers
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Related Matters -->
    <Card>
      <CardHeader class="pb-3">
        <CardTitle class="text-base">Related Matters</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <!-- Family members -->
          <div v-if="relatedMatters.family?.length">
            <h4 class="font-medium text-sm mb-1">Family</h4>
            <div class="flex flex-wrap gap-1">
              <Link
                v-for="member in relatedMatters.family"
                :key="member.id"
                :href="`/matter/${member.id}`"
              >
                <Badge :variant="member.suffix === matter.suffix ? 'secondary' : 'default'">
                  {{ member.suffix }}
                </Badge>
              </Link>
            </div>
          </div>

          <!-- Priority claims -->
          <div v-for="(family, caseref) in groupedPriorityTo" :key="caseref" class="space-y-1">
            <h4 class="font-medium text-sm">{{ caseref }}</h4>
            <div class="flex flex-wrap gap-1">
              <Link
                v-for="rmatter in family"
                :key="rmatter.id"
                :href="`/matter/${rmatter.id}`"
              >
                <Badge variant="default">{{ rmatter.suffix }}</Badge>
              </Link>
            </div>
          </div>

          <div v-if="!hasRelatedMatters" class="text-muted-foreground text-center py-4">
            No related matters
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Open Tasks Summary -->
    <Card>
      <CardHeader class="pb-3">
        <div class="flex items-center justify-between">
          <CardTitle class="text-base">
            Open Tasks
            <Badge v-if="openTasks.length" variant="warning" class="ml-2">
              {{ openTasks.length }}
            </Badge>
          </CardTitle>
          <Link
            :href="`/matter/${matter.id}/tasks`"
            class="text-muted-foreground hover:text-foreground"
            title="All tasks"
          >
            <List class="h-4 w-4" />
          </Link>
        </div>
      </CardHeader>
      <CardContent>
        <div class="space-y-2">
          <div v-for="task in openTasks.slice(0, 5)" :key="task.id" class="flex justify-between text-sm">
            <span>{{ task.info.name }}: {{ task.detail }}</span>
            <span :class="getTaskDateClass(task.due_date)">
              {{ formatDate(task.due_date) }}
            </span>
          </div>
          <div v-if="openTasks.length > 5" class="text-sm text-muted-foreground text-center">
            And {{ openTasks.length - 5 }} more...
          </div>
          <div v-if="!openTasks.length" class="text-muted-foreground text-center py-4">
            No open tasks
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { format, isPast, addDays } from 'date-fns'
import { List, ExternalLink, Pencil } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'

const props = defineProps({
  matter: Object,
  classifiers: Object,
  relatedMatters: Object,
  canEdit: Boolean
})

defineEmits(['openStatusInfo', 'openClassifiers'])

// Filter out IMG classifiers and get status events
const filteredClassifiers = computed(() => {
  const filtered = {}
  for (const [typeCode, group] of Object.entries(props.classifiers || {})) {
    if (typeCode !== 'IMG') {
      filtered[typeCode] = group
    }
  }
  return filtered
})

const statusEvents = computed(() => 
  props.matter.events?.filter(e => e.info?.status_event === 1) || []
)

const openTasks = computed(() => 
  props.matter.tasks_pending || []
)

const groupedPriorityTo = computed(() => {
  const grouped = {}
  props.relatedMatters.priorityTo?.forEach(matter => {
    if (!grouped[matter.caseref]) {
      grouped[matter.caseref] = []
    }
    grouped[matter.caseref].push(matter)
  })
  return grouped
})

const hasRelatedMatters = computed(() => 
  (props.relatedMatters.family?.length > 0) || 
  (Object.keys(groupedPriorityTo.value).length > 0)
)

function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}

function getTaskDateClass(dueDate) {
  if (!dueDate) return ''
  const date = new Date(dueDate)
  if (isPast(date)) return 'text-destructive'
  if (isPast(addDays(new Date(), 7))) return 'text-warning'
  return 'text-muted-foreground'
}
</script>