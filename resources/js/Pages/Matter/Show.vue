<template>
  <MainLayout :title="`Matter: ${matter.uid}`">
    <div class="space-y-4">
      <!-- Quick Actions Bar -->
      <Card v-if="canWrite" class="bg-muted/50">
        <CardContent class="p-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
              <Zap class="h-4 w-4 text-warning" />
              {{ $t('Quick Actions') }}
            </div>
            <div class="flex flex-wrap gap-2">
              <Button
                size="sm"
                variant="outline"
                @click="generateEmail"
                title="Generate email"
              >
                <Mail class="mr-1 h-3 w-3" />
                {{ $t('Email') }}
              </Button>
              <Button
                size="sm"
                variant="outline"
                @click="generateReport"
                title="Generate report"
              >
                <FileText class="mr-1 h-3 w-3" />
                {{ $t('Report') }}
              </Button>
              <Button
                size="sm"
                variant="outline"
                @click="showFileMergeDialog = true"
                title="Merge document"
              >
                <FileText class="mr-1 h-3 w-3" />
                {{ $t('Merge') }}
              </Button>
              <Button
                v-if="matter.country === 'WO' && matter.category_code === 'TM'"
                size="sm"
                variant="outline"
                @click="showInternationalCreatorDialog = true"
                title="Create national trademark matters"
              >
                <Globe class="mr-1 h-3 w-3" />
                {{ $t('International') }}
              </Button>
              <Button
                size="sm"
                variant="outline"
                @click="exportMatter"
                title="Export matter data"
              >
                <Download class="mr-1 h-3 w-3" />
                {{ $t('Export') }}
              </Button>
              <Button
                size="sm"
                variant="outline"
                @click="shareMatter"
                title="Share matter"
              >
                <Share2 class="mr-1 h-3 w-3" />
                {{ $t('Share') }}
              </Button>
              <Button
                v-if="!matter.dead"
                size="sm"
                variant="outline"
                @click="archiveMatter"
                title="Archive matter"
              >
                <Archive class="mr-1 h-3 w-3" />
                {{ $t('Archive') }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Header Section -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Left Panel - Matter Info -->
        <div class="lg:col-span-1">
          <Card class="border-primary h-full overflow-hidden">
            <CardHeader class="bg-primary text-primary-foreground p-3 overflow-hidden">
              <div class="flex items-center justify-between">
                <Link
                  :href="`/matter?Ref=${matter.caseref}`"
                  target="_blank"
                  :class="['text-primary-foreground font-bold text-lg hover:underline', matter.dead ? 'line-through' : '']"
                  :title="'See family'"
                >
                  {{ matter.uid }}
                </Link>
                <span class="text-sm">({{ translated(matter.category.category) }})</span>
              </div>
              <div class="flex gap-2 mt-2">
                <a
                  v-if="sharePointLink || matter.caseref"
                  :href="sharePointLink || `/matter?Ref=${matter.caseref}`"
                  :title="sharePointLink ? 'Go to documents' : 'See family'"
                  target="_blank"
                  class="text-warning hover:text-warning/80"
                >
                  <FolderSymlink class="h-4 w-4" />
                </a>
                <Button
                  v-if="canWrite"
                  @click="showEditDialog = true"
                  variant="ghost"
                  size="icon"
                  class="h-4 w-4 p-0 hover:bg-primary-foreground/10"
                  title="Advanced matter edition"
                >
                  <Pencil class="h-4 w-4 text-primary-foreground" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-3">
              <dl class="space-y-2 text-sm">
                <div v-if="matter.container_id" class="flex">
                  <dt class="font-medium w-24">{{ $t('Container:') }}</dt>
                  <dd>
                    <Link :href="`/matter/${matter.container_id}`" class="text-primary hover:underline">
                      {{ matter.container.uid }}
                    </Link>
                  </dd>
                </div>
                <div v-if="matter.parent_id" class="flex">
                  <dt class="font-medium w-24">{{ $t('Parent:') }}</dt>
                  <dd>
                    <Link :href="`/matter/${matter.parent_id}`" class="text-primary hover:underline">
                      {{ matter.parent.uid }}
                    </Link>
                  </dd>
                </div>
                <div v-if="matter.alt_ref" class="flex">
                  <dt class="font-medium w-24">{{ $t('Alt. ref:') }}</dt>
                  <dd>{{ matter.alt_ref }}</dd>
                </div>
                <div v-if="matter.expire_date" class="flex">
                  <dt class="font-medium w-24">{{ $t('Expiry:') }}</dt>
                  <dd>{{ formatDate(matter.expire_date) }}</dd>
                </div>
              </dl>
              <Alert class="mt-3 py-2">
                <AlertDescription class="text-center">
                  <strong>{{ $t('Responsible:') }}</strong> {{ matter.responsible }}
                </AlertDescription>
              </Alert>
            </CardContent>
            <CardFooter v-if="canWrite" class="p-3">
              <div class="grid gap-2 w-full">
                <div class="flex gap-2">
                  <Button
                    size="sm"
                    variant="secondary"
                    class="flex-1"
                    @click="createChild"
                  >
                    <GitBranch class="mr-1 h-3 w-3" />
                    {{ $t('New Child') }}
                  </Button>
                  <Button
                    size="sm"
                    variant="secondary"
                    class="flex-1"
                    @click="cloneMatter"
                  >
                    <Copy class="mr-1 h-3 w-3" />
                    {{ $t('Clone') }}
                  </Button>
                </div>
                <Button
                  v-if="matter.country_info?.goesnational"
                  size="sm"
                  variant="secondary"
                  @click="enterNationalPhase"
                  class="w-full"
                >
                  <Flag class="mr-1 h-3 w-3" />
                  {{ $t('Nat. Phase') }}
                </Button>
                
                <!-- Official Links Section -->
                <div class="pt-3 border-t">
                  <OfficeLinks :matter="matter" />
                </div>
              </div>
            </CardFooter>
          </Card>
        </div>

        <!-- Middle Panel - Titles -->
        <div class="lg:col-span-3">
          <Card class="h-full">
            <CardContent class="p-3">
              <div v-for="(titleGroup, typeName) in titles" :key="typeName" class="mb-3">
                <h4 class="font-semibold mb-1">{{ typeName }}</h4>
                <div v-for="title in titleGroup" :key="title.id" class="mb-1">
                  {{ title.value }}
                </div>
              </div>
              <div v-if="canWrite" class="mt-3">
                <Button
                  size="sm"
                  variant="outline"
                  @click="showTitleManagerDialog = true"
                >
                  <Settings class="mr-1 h-3 w-3" />
                  {{ $t('Manage Titles') }}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Panel - Image (if exists) -->
        <div v-if="imageClassifier" class="lg:col-span-1">
          <Card class="bg-secondary h-full">
            <CardContent class="p-3">
              <img
                :src="`/classifier/${imageClassifier.id}/img`"
                class="w-full h-auto max-h-40 object-contain"
                alt="Matter image"
              />
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Main Content Area with Tabs -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Actors Panel -->
        <div class="lg:col-span-1">
          <Card class="h-[600px] overflow-hidden">
            <CardHeader class="bg-secondary text-secondary-foreground p-3">
              <div class="flex items-center justify-between">
                <h3 class="font-semibold">{{ $t('Actors') }}</h3>
                <Button
                  v-if="canWrite"
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6"
                  @click="showActorManagerDialog = true"
                >
                  <Settings class="h-4 w-4" />
                </Button>
              </div>
            </CardHeader>
            <CardContent class="p-3 overflow-y-auto max-h-[calc(600px-60px)]">
              <ActorList
                :actors="matter.actors"
                :matter-id="matter.id"
                :container-id="matter.container_id"
                :enable-inline-edit="false"
                :editable="canWrite"
                @update="handleActorUpdate"
                @edit="handleActorEdit"
              />
            </CardContent>
          </Card>
        </div>

        <!-- Main Content Tabs -->
        <div class="lg:col-span-3">
          <Tabs v-model="activeTab" class="w-full">
            <TabsList class="grid w-full grid-cols-6">
              <TabsTrigger value="summary">{{ $t('Summary') }}</TabsTrigger>
              <TabsTrigger value="events">
                {{ $t('Events') }}
                <Badge v-if="statusEvents.length" variant="secondary" class="ml-1">
                  {{ statusEvents.length }}
                </Badge>
              </TabsTrigger>
              <TabsTrigger value="tasks">
                {{ $t('Tasks') }}
                <Badge v-if="matter.tasks_pending.length" variant="warning" class="ml-1">
                  {{ matter.tasks_pending.length }}
                </Badge>
              </TabsTrigger>
              <TabsTrigger value="renewals">
                {{ $t('Renewals') }}
                <Badge v-if="matter.renewals_pending.length" variant="warning" class="ml-1">
                  {{ matter.renewals_pending.length }}
                </Badge>
              </TabsTrigger>
              <TabsTrigger value="notes">{{ $t('Notes') }}</TabsTrigger>
              <TabsTrigger value="activity">{{ $t('Activity') }}</TabsTrigger>
            </TabsList>

            <TabsContent value="summary" class="mt-4">
              <SummaryTab
                :matter="matter"
                :classifiers="classifiers"
                :related-matters="getRelatedMatters()"
                :can-edit="canWrite"
                @open-status-info="showStatusInfoDialog = true"
                @open-classifiers="showClassifierDialog = true"
              />
            </TabsContent>

            <TabsContent value="events" class="mt-4">
              <EventsTab
                :events="matter.events"
                :matter-id="matter.id"
                :matter="matter"
                :enable-inline-edit="canWrite"
              />
            </TabsContent>

            <TabsContent value="tasks" class="mt-4">
              <TasksTab
                :tasks="matter.tasks_pending"
                :matter-id="matter.id"
                :enable-inline-edit="canWrite"
              />
            </TabsContent>

            <TabsContent value="renewals" class="mt-4">
              <RenewalsTab
                :renewals="matter.renewals_pending"
                :matter-id="matter.id"
              />
            </TabsContent>

            <TabsContent value="notes" class="mt-4">
              <NotesTab
                :notes="matter.notes"
                :matter-id="matter.id"
                :can-edit="canWrite"
                @update="handleNotesUpdate"
              />
            </TabsContent>

            <TabsContent value="activity" class="mt-4">
              <ActivityFeed
                :matter-id="matter.id"
                :events="matter.events"
                :tasks="matter.tasks_pending"
              />
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </div>

    <!-- Dialogs -->
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

    <ActorDialog
      v-model:open="showActorEditDialog"
      :actor-id="selectedActor?.actor_id || selectedActor?.id"
      operation="edit"
      @success="handleActorUpdate"
    />

    <StatusInfoManager
      v-model:open="showStatusInfoDialog"
      :matter="matter"
      :status-events="statusEvents"
      @success="handleMatterUpdate"
    />

    <ClassifierManager
      v-model:open="showClassifierDialog"
      :matter="matter"
      :classifiers="classifiers"
      @success="handleClassifierUpdate"
    />

    <FileMergeDialog
      v-model:open="showFileMergeDialog"
      :matter-id="matter.id"
    />

    <ChildMatterDialog
      v-model:open="showChildDialog"
      :parent-matter="matter"
      :current-user="$page.props.auth.user"
      @success="handleMatterUpdate"
    />

    <InternationalTrademarkCreator
      v-model:open="showInternationalCreatorDialog"
      :matter="matter"
      @success="handleInternationalSuccess"
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
  FolderSymlink, 
  Pencil, 
  GitBranch, 
  Copy, 
  Flag, 
  Plus,
  Settings,
  Mail,
  FileText,
  Download,
  Share2,
  Archive,
  Zap,
  Globe
} from 'lucide-vue-next'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import ActorList from '@/components/display/ActorList.vue'
import MatterDialog from '@/components/dialogs/MatterDialog.vue'
import MatterActorManager from '@/components/dialogs/MatterActorManager.vue'
import ActorDialog from '@/components/dialogs/ActorDialog.vue'
import TitleManager from '@/components/dialogs/TitleManager.vue'
import StatusInfoManager from '@/components/dialogs/StatusInfoManager.vue'
import ClassifierManager from '@/components/dialogs/ClassifierManager.vue'
import FileMergeDialog from '@/components/dialogs/FileMergeDialog.vue'
import ChildMatterDialog from '@/components/dialogs/ChildMatterDialog.vue'
import InternationalTrademarkCreator from '@/components/matter/InternationalTrademarkCreator.vue'
import OfficeLinks from '@/components/matter/OfficeLinks.vue'

// Import tab components (to be created)
import SummaryTab from '@/components/matter/tabs/SummaryTab.vue'
import EventsTab from '@/components/matter/tabs/EventsTab.vue'
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
const activeTab = ref('summary')
const showEditDialog = ref(false)
const showTitleManagerDialog = ref(false)
const showActorManagerDialog = ref(false)
const showActorEditDialog = ref(false)
const selectedActor = ref(null)
const showStatusInfoDialog = ref(false)
const showClassifierDialog = ref(false)
const showFileMergeDialog = ref(false)
const showChildDialog = ref(false)
const showInternationalCreatorDialog = ref(false)

// Computed
const imageClassifier = computed(() => 
  props.matter.classifiers?.find(c => c.type_code === 'IMG')
)

// Methods
function formatDate(dateString) {
  if (!dateString) return ''
  return format(new Date(dateString), 'dd/MM/yyyy')
}

function getRelatedMatters() {
  return {
    family: props.matter.family || [],
    priorityTo: props.matter.priority_to || []
  }
}

function createChild() {
  showChildDialog.value = true
}

function cloneMatter() {
  router.get(`/matter/create?matter_id=${props.matter.id}&operation=clone`)
}

function enterNationalPhase() {
  router.get(`/matter/${props.matter.id}/createN`)
}

function handleMatterUpdate() {
  showEditDialog.value = false
  router.reload({ only: ['matter'] })
}

function handleInternationalSuccess() {
  showInternationalCreatorDialog.value = false
  // Reload the full page to show new national matters in family
  router.visit(`/matter?Ref=${props.matter.caseref}`)
}

function handleTitleUpdate() {
  router.reload({ only: ['titles'] })
}

function handleClassifierUpdate() {
  router.reload({ only: ['classifiers', 'titles'] })
}


function handleActorUpdate() {
  router.reload({ only: ['actors', 'matter'] })
}

function handleActorEdit(actor) {
  // Open the actor dialog for editing a specific actor
  selectedActor.value = actor
  showActorEditDialog.value = true
}


function handleNotesUpdate() {
  router.reload({ only: ['matter'] })
}

// Quick Actions
function generateEmail() {
  // TODO: Implement email generation
  alert(t('Email generation coming soon!'))
}

function generateReport() {
  // TODO: Implement report generation
  alert(t('Report generation coming soon!'))
}

function exportMatter() {
  window.location.href = route('matter.export.single', props.matter.id)
}

function shareMatter() {
  // TODO: Implement sharing functionality
  alert(t('Sharing functionality coming soon!'))
}

function archiveMatter() {
  if (confirm(t('Are you sure you want to archive this matter?'))) {
    router.put(`/matter/${props.matter.id}`, {
      dead: 1
    }, {
      onSuccess: () => {
        router.reload()
      }
    })
  }
}
</script>