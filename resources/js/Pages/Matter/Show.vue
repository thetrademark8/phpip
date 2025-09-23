<template>
  <MainLayout :title="`Matter: ${matter.uid}`">
    <div class="space-y-3">
      <!-- Compact Header with Quick Actions -->
      <div class="flex items-center justify-between bg-primary text-primary-foreground p-2 rounded-lg">
        <div class="flex items-center gap-4">
          <Link
            :href="`/matter?Ref=${matter.caseref}`"
            :class="['font-bold text-lg hover:underline', matter.dead ? 'line-through' : '']"
            :title="'See family'"
          >
            {{ matter.uid }}
          </Link>
          <Badge variant="secondary">{{ translated(matter.category.category) }}</Badge>
          <span v-if="matter.country_info" class="text-sm">{{ matter.country_info.name }}</span>
        </div>

        <div v-if="canWrite" class="flex items-center gap-2">
          <Button size="sm" variant="secondary" @click="generateEmail" title="Generate email">
            <Mail class="h-3 w-3" />
          </Button>
          <Button size="sm" variant="secondary" @click="showFileMergeDialog = true" title="Merge document">
            <FileText class="h-3 w-3" />
          </Button>
          <Button size="sm" variant="secondary" @click="exportMatter" title="Export">
            <Download class="h-3 w-3" />
          </Button>
          <Button size="sm" variant="secondary" @click="showEditDialog = true" title="Edit">
            <Pencil class="h-3 w-3" />
          </Button>
        </div>
      </div>

      <!-- Main 3-column Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">

        <!-- Left Column - Actors (25%) -->
        <div class="lg:col-span-1">
          <Card class="h-fit">
            <CardHeader class="py-2 px-3 bg-secondary">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-sm">{{ $t('Actors') }}</h3>
                <Button
                  v-if="canWrite"
                  variant="ghost"
                  size="icon"
                  class="h-5 w-5"
                  @click="showActorManagerDialog = true"
                >
                  <Settings class="h-3 w-3" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-3">
              <ActorList
                :actors="matter.actors"
                :matter-id="matter.id"
                :container-id="matter.container_id"
                :enable-inline-edit="false"
                :editable="canWrite"
                :compact="true"
                @update="handleActorUpdate"
                @edit="handleActorEdit"
              />
            </CardContent>
          </Card>

          <!-- Matter Links Below Actors -->
          <Card v-if="matter.container_id || matter.parent_id" class="mt-3">
            <CardContent class="p-3">
              <dl class="space-y-1 text-sm">
                <div v-if="matter.container_id" class="flex">
                  <dt class="font-medium w-20">Container:</dt>
                  <dd>
                    <Link :href="`/matter/${matter.container_id}`" class="text-primary hover:underline text-xs">
                      {{ matter.container.uid }}
                    </Link>
                  </dd>
                </div>
                <div v-if="matter.parent_id" class="flex">
                  <dt class="font-medium w-20">Parent:</dt>
                  <dd>
                    <Link :href="`/matter/${matter.parent_id}`" class="text-primary hover:underline text-xs">
                      {{ matter.parent.uid }}
                    </Link>
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>
        </div>

        <!-- Middle Column - Main Information (50%) -->
        <div class="lg:col-span-2 space-y-3">

          <!-- Key Dates & Status -->
          <Card>
            <CardHeader class="py-2 px-3 bg-secondary">
              <h3 class="font-semibold text-sm">{{ $t('Key Information') }}</h3>
            </CardHeader>
            <CardContent class="p-3">
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span class="font-medium">{{ $t('Status:') }}</span>
                  <div class="mt-1">
                    <StatusBadge
                      v-for="event in statusEvents.slice(0,2)"
                      :key="event.id"
                      :status="event.code"
                      :date="event.event_date"
                      class="mr-2 mb-1"
                    />
                  </div>
                </div>
                <div>
                  <span class="font-medium">{{ $t('Responsible:') }}</span>
                  <span class="ml-2">{{ matter.responsible }}</span>
                </div>
                <div v-if="matter.filing">
                  <span class="font-medium">{{ $t('Filed:') }}</span>
                  <span class="ml-2">{{ formatDate(matter.filing.event_date) }}</span>
                  <span v-if="matter.filing.detail" class="text-xs text-muted-foreground ml-1">({{ matter.filing.detail }})</span>
                </div>
                <div v-if="matter.grant || matter.registration">
                  <span class="font-medium">{{ $t('Registered:') }}</span>
                  <span class="ml-2">{{ formatDate((matter.grant || matter.registration)?.event_date) }}</span>
                </div>
                <div v-if="matter.expire_date">
                  <span class="font-medium">{{ $t('Next Renewal:') }}</span>
                  <span class="ml-2 text-warning">{{ formatDate(matter.expire_date) }}</span>
                </div>
                <div v-if="matter.alt_ref">
                  <span class="font-medium">{{ $t('Alt. Ref:') }}</span>
                  <span class="ml-2">{{ matter.alt_ref }}</span>
                </div>
              </div>

              <!-- Classes for TM -->
              <div v-if="tmClasses.length > 0" class="mt-3 pt-3 border-t">
                <span class="font-medium text-sm">{{ $t('Classes:') }}</span>
                <div class="mt-1 flex flex-wrap gap-1">
                  <Badge v-for="cls in tmClasses" :key="cls.id" variant="outline" class="text-xs">
                    {{ cls.value }}
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Tasks & Renewals -->
          <div v-if="matter.tasks_pending.length > 0 || matter.renewals_pending.length > 0" class="grid gap-3" :class="{'grid-cols-2': matter.tasks_pending.length > 0 && matter.renewals_pending.length > 0, 'grid-cols-1': matter.tasks_pending.length <= 0 || matter.renewals_pending.length <= 0}">
            <Card v-if="matter.tasks_pending.length > 0">
              <CardHeader class="py-2 px-3 bg-warning/10">
                <h3 class="font-semibold text-sm flex items-center justify-between">
                  {{ $t('Pending Tasks') }}
                  <Badge variant="warning">{{ matter.tasks_pending.length }}</Badge>
                </h3>
              </CardHeader>
              <CardContent class="p-3 overflow-y-auto">
                <TasksTab
                  :tasks="matter.tasks_pending"
                  :matter-id="matter.id"
                  :enable-inline-edit="canWrite"
                  :compact="true"
                />
              </CardContent>
            </Card>

            <Card v-if="matter.renewals_pending.length > 0">
              <CardHeader class="py-2 px-3 bg-warning/10">
                <h3 class="font-semibold text-sm flex items-center justify-between">
                  {{ $t('Renewals') }}
                  <Badge variant="warning">{{ matter.renewals_pending.length }}</Badge>
                </h3>
              </CardHeader>
              <CardContent class="p-3 overflow-y-auto">
                <RenewalsTab
                  :renewals="matter.renewals_pending"
                  :matter-id="matter.id"
                  :compact="true"
                />
              </CardContent>
            </Card>
          </div>

          <!-- Events - Timeline with Actions -->
          <Card>
            <CardHeader class="py-2 px-3 bg-secondary">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-sm">{{ $t('Recent Events') }}</h3>
                <Button
                  v-if="canWrite"
                  variant="ghost"
                  size="icon"
                  class="h-5 w-5"
                  @click="showEventManagerDialog = true"
                >
                  <Settings class="h-3 w-3" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-3 max-h-64 overflow-y-auto">
              <EventTimeline
                :events="recentEvents"
                :enable-inline-edit="false"
                :editable="canWrite"
                :show-tasks="false"
                @edit="handleEventEdit"
                @remove="handleEventRemove"
                @update="handleEventUpdate"
              />
            </CardContent>
          </Card>
        </div>

        <!-- Right Column - Titles & Image (25%) -->
        <div class="lg:col-span-1 space-y-3">

          <!-- Titles -->
          <Card>
            <CardHeader class="py-2 px-3 bg-secondary">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold text-sm">{{ $t('Titles') }}</h3>
                <Button
                  v-if="canWrite"
                  variant="ghost"
                  size="icon"
                  class="h-5 w-5"
                  @click="showTitleManagerDialog = true"
                >
                  <Settings class="h-3 w-3" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-3">
              <div v-for="(titleGroup, typeName) in titles" :key="typeName" class="mb-2">
                <h4 class="font-medium text-xs text-muted-foreground mb-1">{{ typeName }}</h4>
                <div v-for="title in titleGroup" :key="title.id" class="text-sm mb-1">
                  {{ title.value }}
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Image -->
          <Card v-if="imageClassifier">
            <CardContent class="p-2">
              <img
                :src="`/classifier/${imageClassifier.id}/img`"
                class="w-full h-auto max-h-32 object-contain"
                alt="Matter image"
              />
            </CardContent>
          </Card>

          <!-- Notes -->
          <Card>
            <CardHeader class="py-2 px-3 bg-secondary">
              <h3 class="font-semibold text-sm">{{ $t('Notes') }}</h3>
            </CardHeader>
            <CardContent class="p-3 overflow-y-auto">
              <NotesTab
                :notes="matter.notes"
                :matter-id="matter.id"
                :can-edit="canWrite"
                :compact="true"
                @update="handleNotesUpdate"
              />
            </CardContent>
          </Card>

          <!-- Office Links -->
          <Card v-if="matter.country_info">
            <CardContent class="p-3">
              <OfficeLinks :matter="matter" />
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Collapsible Timeline at Bottom -->
      <Collapsible v-model:open="timelineOpen">
        <Card>
          <CardHeader class="py-2 px-3 bg-secondary">
            <div class="flex items-center justify-between">
              <h3 class="font-semibold text-sm">{{ $t('Complete Timeline') }}</h3>
              <CollapsibleTrigger asChild>
                <Button variant="ghost" size="sm">
                  <ChevronDown v-if="!timelineOpen" class="h-4 w-4" />
                  <ChevronUp v-else class="h-4 w-4" />
                </Button>
              </CollapsibleTrigger>
            </div>
          </CardHeader>
          <CollapsibleContent>
            <CardContent class="p-3">
              <ActivityFeed
                :matter-id="matter.id"
                :events="matter.events"
                :tasks="matter.tasks_pending"
              />
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>
    </div>

    <!-- Dialogs (same as before) -->
    <MatterDialog
      v-model:open="showEditDialog"
      :matter="matter"
      operation="edit"
      @success="handleMatterUpdate"
    />

    <TitleManager
      v-model:open="showTitleManagerDialog"
      :matter="matter"
      :titles="titles"
      @success="handleTitleUpdate"
    />

    <MatterActorManager
      v-model:open="showActorManagerDialog"
      :matter="matter"
      @success="handleActorUpdate"
    />

    <MatterEventManager
      v-model:open="showEventManagerDialog"
      :matter="matter"
      :events="matter.events"
      @success="handleEventUpdate"
    />

    <ActorDialog
      v-model:open="showActorEditDialog"
      :actor-id="selectedActor?.actor_id || selectedActor?.id"
      operation="edit"
      @success="handleActorUpdate"
    />

    <FileMergeDialog
      v-model:open="showFileMergeDialog"
      :matter-id="matter.id"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { useI18n } from 'vue-i18n'
import { useTranslatedField } from '@/composables/useTranslation'
import {
  Mail,
  FileText,
  Download,
  Pencil,
  Settings,
  ChevronDown,
  ChevronUp
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import StatusBadge from '@/components/display/StatusBadge.vue'
import ActorList from '@/components/display/ActorList.vue'
import EventTimeline from '@/components/display/EventTimeline.vue'
import MatterDialog from '@/components/dialogs/MatterDialog.vue'
import MatterActorManager from '@/components/dialogs/MatterActorManager.vue'
import MatterEventManager from '@/components/dialogs/MatterEventManager.vue'
import ActorDialog from '@/components/dialogs/ActorDialog.vue'
import TitleManager from '@/components/dialogs/TitleManager.vue'
import FileMergeDialog from '@/components/dialogs/FileMergeDialog.vue'
import OfficeLinks from '@/components/matter/OfficeLinks.vue'
import TasksTab from '@/components/matter/tabs/TasksTab.vue'
import RenewalsTab from '@/components/matter/tabs/RenewalsTab.vue'
import NotesTab from '@/components/matter/tabs/NotesTab.vue'
import ActivityFeed from '@/components/matter/ActivityFeed.vue'

const props = defineProps({
  matter: Object,
  titles: Object,
  classifiers: Object,
  actors: Object,
  statusEvents: Array,
  sharePointLink: String,
  canWrite: Boolean
})

const { t } = useI18n()
const { translated } = useTranslatedField()

// State
const timelineOpen = ref(false)
const showEditDialog = ref(false)
const showTitleManagerDialog = ref(false)
const showActorManagerDialog = ref(false)
const showEventManagerDialog = ref(false)
const showActorEditDialog = ref(false)
const selectedActor = ref(null)
const showFileMergeDialog = ref(false)

// Computed
const imageClassifier = computed(() =>
  props.matter.classifiers?.find(c => c.type_code === 'IMG')
)

const tmClasses = computed(() =>
  props.classifiers?.TMCL || []
)

const recentEvents = computed(() =>
  props.matter.events?.slice(0, 10) || []
)

// Methods
function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}

function handleMatterUpdate() {
  showEditDialog.value = false
  router.reload({ only: ['matter'] })
}

function handleTitleUpdate() {
  router.reload({ only: ['titles'] })
}

function handleActorUpdate() {
  router.reload({ only: ['actors', 'matter'] })
}

function handleActorEdit(actor) {
  selectedActor.value = actor
  showActorEditDialog.value = true
}

function handleNotesUpdate() {
  router.reload({ only: ['matter'] })
}

function generateEmail() {
  alert(t('Email generation coming soon!'))
}

function exportMatter() {
  window.location.href = route('matter.export.single', props.matter.id)
}

function handleEventEdit(event) {
  // TODO: Open event edit dialog
  console.log('Edit event:', event)
}

function handleEventRemove(event) {
  if (confirm(t('Are you sure you want to remove this event?'))) {
    router.delete(route('event.destroy', event.id), {
      preserveScroll: true,
      onSuccess: () => router.reload({ only: ['matter'] })
    })
  }
}

function handleEventUpdate(event) {
  router.reload({ only: ['matter'] })
}
</script>