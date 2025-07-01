<template>
  <MainLayout>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Left Panel -->
      <div class="lg:col-span-1 space-y-4">
        <!-- Categories Card -->
        <CategoryStats 
          :categories="categories" 
          :permissions="permissions" 
          @openCreateMatter="handleOpenCreateMatter"
        />
        
        <!-- Users Tasks Card -->
        <UserTasksSummary :tasks-count="tasksCount" />
      </div>

      <!-- Right Panel -->
      <div class="lg:col-span-2 space-y-4">
        <!-- Open Tasks Card -->
        <Card>
          <CardHeader>
            <div class="flex flex-wrap items-center justify-between gap-4">
              <CardTitle>Open tasks</CardTitle>
              
              <div class="flex items-center gap-2">
                <!-- Task Filters -->
                <RadioGroup
                  v-model="taskFilter"
                  class="flex flex-row gap-2"
                  @update:modelValue="updateFilters"
                >
                  <div class="flex items-center">
                    <RadioGroupItem value="0" id="alltasks" />
                    <Label for="alltasks" class="ml-2 cursor-pointer">Everyone</Label>
                  </div>
                  <div v-if="!filters.user_dashboard" class="flex items-center">
                    <RadioGroupItem value="1" id="mytasks" />
                    <Label for="mytasks" class="ml-2 cursor-pointer">{{ $page.props.auth.user.login }}</Label>
                  </div>
                  <div class="flex items-center">
                    <RadioGroupItem value="2" id="clientTasks" />
                    <Label for="clientTasks" class="ml-2 cursor-pointer">Client</Label>
                  </div>
                </RadioGroup>

                <!-- Client Autocomplete -->
                <div v-if="taskFilter === '2'" class="w-64">
                  <AutocompleteInput
                    v-model="selectedClientId"
                    v-model:display-model-value="selectedClientName"
                    endpoint="/actor/autocomplete"
                    placeholder="Select Client"
                    @selected="onClientSelected"
                  />
                </div>

                <!-- Clear Tasks Button -->
                <div v-if="permissions.canWrite" class="flex items-center gap-2">
                  <Button
                    variant="secondary"
                    size="sm"
                    @click="clearSelectedTasks"
                    :disabled="selectedTaskIds.length === 0"
                  >
                    Clear selected on
                  </Button>
                  <DatePicker
                    v-model="taskClearDate"
                    placeholder="Select date"
                    button-class="w-auto"
                  />
                </div>
              </div>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <DashboardTaskList
              :tasks="tasks"
              :permissions="permissions"
              @update:selected="selectedTaskIds = $event"
            />
          </CardContent>
        </Card>

        <!-- Open Renewals Card -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Open renewals</CardTitle>
              
              <!-- Clear Renewals Button -->
              <div v-if="permissions.canWrite" class="flex items-center gap-2">
                <Button
                  variant="secondary"
                  size="sm"
                  @click="clearSelectedRenewals"
                  :disabled="selectedRenewalIds.length === 0"
                >
                  Clear selected on
                </Button>
                <DatePicker
                  v-model="renewalClearDate"
                  placeholder="Select date"
                  button-class="w-auto"
                />
              </div>
            </div>
          </CardHeader>
          <CardContent class="p-0">
            <RenewalList
              :renewals="renewals"
              :permissions="permissions"
              @update:selected="selectedRenewalIds = $event"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Label } from '@/Components/ui/label'
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group'
import DatePicker from '@/Components/ui/date-picker/DatePicker.vue'
import AutocompleteInput from '@/Components/ui/form/AutocompleteInput.vue'
import CategoryStats from '@/Components/dashboard/CategoryStats.vue'
import UserTasksSummary from '@/Components/dashboard/UserTasksSummary.vue'
import DashboardTaskList from '@/Components/dashboard/TaskList.vue'
import RenewalList from '@/Components/dashboard/RenewalList.vue'

const props = defineProps({
  categories: Array,
  tasksCount: Array,
  tasks: Array,
  renewals: Array,
  filters: Object,
  permissions: Object
})

// Local state
const taskFilter = ref(props.filters.what_tasks?.toString() || '0')
const selectedClientId = ref(props.filters.client_id || '')
const selectedClientName = ref('')
const selectedTaskIds = ref([])
const selectedRenewalIds = ref([])
const taskClearDate = ref(new Date().toISOString().split('T')[0])
const renewalClearDate = ref(new Date().toISOString().split('T')[0])


// Update filters when radio selection changes
const updateFilters = (value) => {
  if (value !== '2') {
    selectedClientId.value = ''
    selectedClientName.value = ''
  }
  
  const params = {
    what_tasks: value,
    user_dashboard: props.filters.user_dashboard
  }
  
  if (value === '2' && selectedClientId.value) {
    params.client_id = selectedClientId.value
  }
  
  router.get('/home', params, { preserveState: true })
}

// Handle client selection
const onClientSelected = (client) => {
  selectedClientId.value = client.key
  selectedClientName.value = client.value
  updateFilters('2')
}

// Clear selected tasks
const clearSelectedTasks = async () => {
  if (selectedTaskIds.value.length === 0) {
    alert('No tasks selected for clearing!')
    return
  }

  try {
    const response = await fetch('/matter/clear-tasks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': usePage().props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        task_ids: selectedTaskIds.value,
        done_date: taskClearDate.value
      })
    })

    const data = await response.json()
    
    if (data.success) {
      // Refresh the page data
      router.reload({ only: ['categories', 'tasksCount', 'tasks', 'renewals'] })
      selectedTaskIds.value = []
    } else {
      alert('Error clearing tasks')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error clearing tasks')
  }
}

// Clear selected renewals
const clearSelectedRenewals = async () => {
  if (selectedRenewalIds.value.length === 0) {
    alert('No renewals selected for clearing!')
    return
  }

  try {
    const response = await fetch('/matter/clear-tasks', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': usePage().props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        task_ids: selectedRenewalIds.value,
        done_date: renewalClearDate.value
      })
    })

    const data = await response.json()
    
    if (data.success) {
      // Refresh the page data
      router.reload({ only: ['categories', 'tasksCount', 'tasks', 'renewals'] })
      selectedRenewalIds.value = []
    } else {
      alert('Error clearing renewals')
    }
  } catch (error) {
    console.error('Error:', error)
    alert('Error clearing renewals')
  }
}

// Handle opening the create matter dialog from CategoryStats
const handleOpenCreateMatter = (categoryCode) => {
  // Emit event to MainLayout via inject/provide or use a global event bus
  // For now, we'll use the Navigation's approach
  window.dispatchEvent(new CustomEvent('openCreateMatterWithCategory', { detail: { category: categoryCode } }))
}
</script>