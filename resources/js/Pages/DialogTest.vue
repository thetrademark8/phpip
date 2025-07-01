<template>
  <MainLayout>
    <div class="container mx-auto py-8">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Simplified Dialog System Test</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Matter Dialog Tests -->
          <div class="space-y-2">
            <h2 class="text-lg font-semibold">Matter Dialogs</h2>
            <Button @click="showCreateMatter = true" class="w-full">
              Create Matter
            </Button>
            <Button @click="showCreateChildMatter = true" variant="outline" class="w-full">
              Create Child Matter
            </Button>
            <Button @click="showCreateOPSMatter = true" variant="outline" class="w-full">
              Create from OPS
            </Button>
          </div>

          <!-- Actor Dialog Tests -->
          <div class="space-y-2">
            <h2 class="text-lg font-semibold">Actor Dialogs</h2>
            <Button @click="showCreateActor = true" class="w-full">
              Create Actor
            </Button>
            <Button @click="showEditActor = true" variant="outline" class="w-full">
              Edit Actor
            </Button>
          </div>

          <!-- Task Dialog Tests -->
          <div class="space-y-2">
            <h2 class="text-lg font-semibold">Task Dialogs</h2>
            <Button @click="showCreateTask = true" class="w-full">
              Create Task
            </Button>
            <Button @click="showCreateRenewalTask = true" variant="outline" class="w-full">
              Create Renewal Task
            </Button>
          </div>

          <!-- Confirmation Dialog Tests -->
          <div class="space-y-2">
            <h2 class="text-lg font-semibold">Confirmation Dialogs</h2>
            <Button @click="showDeleteConfirm = true" variant="destructive" class="w-full">
              Delete Confirmation
            </Button>
            <Button @click="showCustomConfirm = true" variant="outline" class="w-full">
              Custom Confirmation
            </Button>
          </div>

          <!-- Dialog States -->
          <div class="space-y-2 md:col-span-2">
            <h2 class="text-lg font-semibold">Dialog State</h2>
            <div class="bg-muted p-4 rounded-lg">
              <p class="text-sm text-muted-foreground mb-2">
                Active Dialogs: {{ activeDialogCount }}
              </p>
              <div class="space-y-1">
                <div v-if="showCreateMatter" class="text-xs bg-background p-2 rounded border">
                  Create Matter Dialog (Local State)
                </div>
                <div v-if="showCreateActor" class="text-xs bg-background p-2 rounded border">
                  Create Actor Dialog (Local State)
                </div>
                <div v-if="showCreateTask" class="text-xs bg-background p-2 rounded border">
                  Create Task Dialog (Local State)
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Test Results -->
        <div v-if="lastResult" class="mt-8 p-4 bg-green-50 border border-green-200 rounded-lg">
          <h3 class="font-semibold text-green-800">Last Action Result:</h3>
          <pre class="text-sm text-green-700 mt-2">{{ lastResult }}</pre>
        </div>
      </div>
    </div>

    <!-- Matter Dialogs -->
    <MatterDialog
      v-if="showCreateMatter"
      operation="new"
      @close="showCreateMatter = false"
      @success="handleMatterSuccess"
    />
    
    <MatterDialog
      v-if="showCreateChildMatter"
      operation="child"
      :child-id="123"
      @close="showCreateChildMatter = false"
      @success="handleMatterSuccess"
    />
    
    <MatterDialog
      v-if="showCreateOPSMatter"
      operation="ops"
      ops-number="EP21123456"
      @close="showCreateOPSMatter = false"
      @success="handleMatterSuccess"
    />

    <!-- Actor Dialogs -->
    <ActorDialog
      v-if="showCreateActor"
      @close="showCreateActor = false"
      @success="handleActorSuccess"
    />
    
    <ActorDialog
      v-if="showEditActor"
      :actor="mockActor"
      @close="showEditActor = false"
      @success="handleActorSuccess"
    />

    <!-- Task Dialogs -->
    <TaskDialog
      v-if="showCreateTask"
      :matter-id="123"
      default-assignee="current-user"
      @close="showCreateTask = false"
      @success="handleTaskSuccess"
    />
    
    <TaskDialog
      v-if="showCreateRenewalTask"
      :event-id="456"
      :show-financial-fields="true"
      @close="showCreateRenewalTask = false"
      @success="handleTaskSuccess"
    />

    <!-- Confirmation Dialogs -->
    <ConfirmDialog
      v-if="showDeleteConfirm"
      title="Delete Item"
      message="Are you sure you want to delete this item? This action cannot be undone."
      confirm-text="Delete"
      type="destructive"
      @confirm="handleDeleteConfirm"
      @cancel="showDeleteConfirm = false"
    />
    
    <ConfirmDialog
      v-if="showCustomConfirm"
      title="Custom Action"
      message="Do you want to proceed with this action?"
      description="This is a custom confirmation dialog with additional options."
      confirm-text="Yes, proceed"
      cancel-text="No, cancel"
      @confirm="handleCustomConfirm"
      @cancel="showCustomConfirm = false"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/Components/ui/button'
import MainLayout from '@/Layouts/MainLayout.vue'
import MatterDialog from '@/Components/dialogs/MatterDialog.vue'
import ActorDialog from '@/Components/dialogs/ActorDialog.vue'
import TaskDialog from '@/Components/dialogs/TaskDialog.vue'
import ConfirmDialog from '@/Components/dialogs/ConfirmDialog.vue'

// Local dialog state
const showCreateMatter = ref(false)
const showCreateChildMatter = ref(false)
const showCreateOPSMatter = ref(false)
const showCreateActor = ref(false)
const showEditActor = ref(false)
const showCreateTask = ref(false)
const showCreateRenewalTask = ref(false)
const showDeleteConfirm = ref(false)
const showCustomConfirm = ref(false)

const lastResult = ref(null)

// Mock data
const mockActor = {
  id: 1,
  name: 'Test Actor',
  first_name: 'John',
  display_name: 'John Test Actor',
  email: 'john@example.com',
  country: 'US'
}

// Computed
const activeDialogCount = computed(() => {
  return [
    showCreateMatter,
    showCreateChildMatter,
    showCreateOPSMatter,
    showCreateActor,
    showEditActor,
    showCreateTask,
    showCreateRenewalTask,
    showDeleteConfirm,
    showCustomConfirm
  ].filter(ref => ref.value).length
})

// Event handlers
const handleMatterSuccess = (response) => {
  lastResult.value = `Matter created successfully: ${response?.props?.matter?.id || 'unknown'}`
  // All matter dialogs will be closed by their @close handlers
}

const handleActorSuccess = (response) => {
  lastResult.value = `Actor saved successfully: ${response?.props?.actor?.id || 'unknown'}`
  // All actor dialogs will be closed by their @close handlers
}

const handleTaskSuccess = (response) => {
  lastResult.value = `Task saved successfully: ${response?.props?.task?.id || 'unknown'}`
  // All task dialogs will be closed by their @close handlers
}

const handleDeleteConfirm = () => {
  showDeleteConfirm.value = false
  lastResult.value = 'Delete action confirmed'
}

const handleCustomConfirm = () => {
  showCustomConfirm.value = false
  lastResult.value = 'Custom action confirmed'
}
</script>