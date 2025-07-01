<template>
  <div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-8">Form Components Test</h1>
    
    <div class="space-y-8">
      <!-- Matter Form Section -->
      <Card>
        <CardHeader>
          <CardTitle>Matter Form</CardTitle>
          <CardDescription>Create a new matter with useForm integration</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs v-model="matterOperation">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="new">New Matter</TabsTrigger>
              <TabsTrigger value="child">Child Matter</TabsTrigger>
              <TabsTrigger value="ops">OPS Matter</TabsTrigger>
            </TabsList>
            <TabsContent value="new" class="mt-4">
              <MatterForm
                operation="new"
                :category="sampleCategory"
                :current-user="currentUser"
                :on-success="handleMatterSuccess"
              />
            </TabsContent>
            <TabsContent value="child" class="mt-4">
              <MatterForm
                operation="child"
                :parent-matter="sampleParentMatter"
                :current-user="currentUser"
                :on-success="handleMatterSuccess"
              />
            </TabsContent>
            <TabsContent value="ops" class="mt-4">
              <MatterForm
                operation="ops"
                :current-user="currentUser"
                :on-success="handleMatterSuccess"
              />
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <!-- Actor Form Section -->
      <Card>
        <CardHeader>
          <CardTitle>Actor Form</CardTitle>
          <CardDescription>Create or edit an actor</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs v-model="actorMode">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="create">Create Actor</TabsTrigger>
              <TabsTrigger value="edit">Edit Actor</TabsTrigger>
            </TabsList>
            <TabsContent value="create" class="mt-4">
              <ActorForm
                :on-success="handleActorSuccess"
              />
            </TabsContent>
            <TabsContent value="edit" class="mt-4">
              <ActorForm
                :actor="sampleActor"
                :on-success="handleActorSuccess"
              />
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <!-- Task Form Section -->
      <Card>
        <CardHeader>
          <CardTitle>Task Form</CardTitle>
          <CardDescription>Create or edit a task</CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs v-model="taskMode">
            <TabsList class="grid w-full grid-cols-3">
              <TabsTrigger value="create">Create Task</TabsTrigger>
              <TabsTrigger value="edit">Edit Task</TabsTrigger>
              <TabsTrigger value="renewal">Renewal Task</TabsTrigger>
            </TabsList>
            <TabsContent value="create" class="mt-4">
              <TaskForm
                :event-id="1"
                :matter-id="1"
                default-assignee="john.doe"
                :on-success="handleTaskSuccess"
              />
            </TabsContent>
            <TabsContent value="edit" class="mt-4">
              <TaskForm
                :task="sampleTask"
                :on-success="handleTaskSuccess"
              />
            </TabsContent>
            <TabsContent value="renewal" class="mt-4">
              <TaskForm
                :event-id="1"
                :matter-id="1"
                :show-financial-fields="true"
                :on-success="handleTaskSuccess"
              />
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      <!-- Form Submission Results -->
      <Card v-if="lastSubmission">
        <CardHeader>
          <CardTitle>Last Form Submission</CardTitle>
        </CardHeader>
        <CardContent>
          <pre class="bg-muted p-4 rounded-lg overflow-auto">{{ JSON.stringify(lastSubmission, null, 2) }}</pre>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs'
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group'
import MatterForm from '@/Components/forms/MatterForm.vue'
import ActorForm from '@/Components/forms/ActorForm.vue'
import TaskForm from '@/Components/forms/TaskForm.vue'

// Tab states
const matterOperation = ref('new')
const actorMode = ref('create')
const taskMode = ref('create')

// Last submission data
const lastSubmission = ref(null)

// Sample data
const currentUser = {
  id: 1,
  name: 'John Doe',
  login: 'john.doe'
}

const sampleCategory = {
  code: 'PAT',
  name: 'Patent',
  next_caseref: 'PAT-2024-001'
}

const sampleParentMatter = {
  id: 123,
  category_code: 'PAT',
  country: 'US',
  origin: 'EP',
  type_code: 'FIL',
  caseref: 'PAT-2023-100',
  responsible: 'jane.doe',
  category: {
    category: 'Patent'
  },
  countryInfo: {
    name: 'United States'
  },
  originInfo: {
    name: 'European Patent Office'
  },
  type: {
    type: 'Filing'
  }
}

const sampleActor = {
  id: 456,
  name: 'Smith',
  first_name: 'John',
  display_name: 'John Smith',
  company_id: 789,
  default_role: 'CLI',
  function: 'Patent Attorney',
  address: '123 Main St\nSuite 100',
  country: 'US',
  email: 'john.smith@example.com',
  phone: '+1 555 123 4567'
}

const sampleTask = {
  id: 789,
  code: 'REN',
  due_date: '2024-12-31',
  assigned_to: 'john.doe',
  detail: 'Annual renewal fee payment',
  done: false,
  done_date: null,
  trigger_id: 1,
  info: {
    name: 'Renewal'
  }
}

// Success handlers
const handleMatterSuccess = (page) => {
  console.log('Matter form submitted successfully', page)
  lastSubmission.value = {
    type: 'Matter',
    operation: matterOperation.value,
    data: page.props.flash?.message || 'Success'
  }
}

const handleActorSuccess = (page) => {
  console.log('Actor form submitted successfully', page)
  lastSubmission.value = {
    type: 'Actor',
    mode: actorMode.value,
    data: page.props.flash?.message || 'Success'
  }
}

const handleTaskSuccess = (page) => {
  console.log('Task form submitted successfully', page)
  lastSubmission.value = {
    type: 'Task',
    mode: taskMode.value,
    data: page.props.flash?.message || 'Success'
  }
}
</script>