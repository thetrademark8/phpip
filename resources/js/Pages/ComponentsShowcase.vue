<template>
  <MainLayout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Components Showcase</h1>
      
      <!-- DatePicker Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">DatePicker Component</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-lg border bg-card p-6">
            <h3 class="font-medium mb-3">Basic DatePicker</h3>
            <DatePicker v-model="date1" placeholder="Select a date" />
            <p class="mt-2 text-sm text-muted-foreground">
              Selected: {{ date1 || 'None' }}
            </p>
          </div>
          
          <div class="rounded-lg border bg-card p-6">
            <h3 class="font-medium mb-3">Date Range</h3>
            <div class="space-y-3">
              <DatePicker v-model="startDate" placeholder="Start date" />
              <DatePicker 
                v-model="endDate" 
                placeholder="End date"
                :is-date-disabled="(date) => startDate && date < new Date(startDate)"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Status Badges Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Status Badges</h2>
        <div class="rounded-lg border bg-card p-6">
          <div class="flex flex-wrap gap-2">
            <StatusBadge status="active" />
            <StatusBadge status="pending" />
            <StatusBadge status="done" />
            <StatusBadge status="overdue" />
            <StatusBadge status="urgent" />
            <StatusBadge status="granted" />
            <StatusBadge status="published" />
            <StatusBadge status="abandoned" />
          </div>
        </div>
      </section>

      <!-- MatterCard Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Matter Card</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <MatterCard :matter="sampleMatter" />
          <MatterCard :matter="sampleMatter2" :show-actions="false" />
        </div>
      </section>

      <!-- ActorList Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Actor List</h2>
        <div class="rounded-lg border bg-card p-6">
          <ActorList :actors="sampleActors" :editable="true" :matter-id="1" />
        </div>
      </section>

      <!-- TaskList Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Task List</h2>
        <div class="rounded-lg border bg-card p-6">
          <TaskList :tasks="sampleTasks" :editable="true" :show-financials="true" />
        </div>
      </section>

      <!-- EventTimeline Section -->
      <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Event Timeline</h2>
        <div class="rounded-lg border bg-card p-6">
          <EventTimeline :events="sampleEvents" :editable="false" />
        </div>
      </section>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { DatePicker } from '@/Components/ui/date-picker'
import MatterCard from '@/Components/display/MatterCard.vue'
import ActorList from '@/Components/display/ActorList.vue'
import TaskList from '@/Components/display/TaskList.vue'
import EventTimeline from '@/Components/display/EventTimeline.vue'
import StatusBadge from '@/Components/display/StatusBadge.vue'

// DatePicker state
const date1 = ref(null)
const startDate = ref(null)
const endDate = ref(null)

// Sample data
const sampleMatter = {
  id: 1,
  uid: 'PAT-2024-001',
  caseref: 'REF123',
  dead: false,
  category: { code: 'PAT', category: 'Patent' },
  titles: [{ value: 'Innovative Widget Design' }],
  status: 'active',
  client: { name: 'Acme Corp' },
  country: 'US',
  filing_date: '2024-01-15',
  expire_date: '2044-01-15',
  responsible: 'John Doe',
  tasks_count: 5,
  events_count: 8,
  actors_count: 4
}

const sampleMatter2 = {
  id: 2,
  uid: 'TM-2024-100',
  caseref: 'TM-REF-456',
  dead: false,
  category: { code: 'TM', category: 'Trademark' },
  titles: [{ value: 'SUPERWIDGET' }],
  status: 'published',
  client: { name: 'Beta Industries' },
  country: 'EU',
  filing_date: '2024-03-20',
  responsible: 'Jane Smith',
  tasks_count: 3,
  events_count: 4,
  actors_count: 2
}

const sampleActors = [
  {
    id: 1,
    actor_id: 101,
    display_name: 'Acme Corporation',
    role_code: 'CLI',
    role_name: 'Client',
    company: 'Acme Corp',
    show_company: true,
    show_ref: true,
    actor_ref: 'AC-001',
    inherited: false,
    warn: false,
    display_order: 0
  },
  {
    id: 2,
    actor_id: 102,
    display_name: 'John Doe',
    role_code: 'INV',
    role_name: 'Inventor',
    show_date: true,
    date: '2024-01-15',
    inherited: false,
    display_order: 0
  },
  {
    id: 3,
    actor_id: 103,
    display_name: 'Jane Smith',
    role_code: 'INV',
    role_name: 'Inventor',
    show_rate: true,
    rate: '50',
    inherited: false,
    display_order: 1
  },
  {
    id: 4,
    actor_id: 104,
    display_name: 'Patent Law Firm LLC',
    role_code: 'AGT',
    role_name: 'Agent',
    company: 'Patent Law Firm',
    show_company: true,
    inherited: true,
    display_order: 0
  }
]

const sampleTasks = [
  {
    id: 1,
    code: 'REN',
    info: { name: 'Renewal' },
    detail: 'Year 4',
    due_date: '2024-12-31',
    done: false,
    assigned_to: 'John Doe',
    notes: 'Annual renewal fee',
    cost: 1000,
    fee: 500,
    currency: 'EUR'
  },
  {
    id: 2,
    code: 'REP',
    info: { name: 'Response' },
    detail: 'Office Action',
    due_date: '2024-06-15',
    done: true,
    done_date: '2024-06-10',
    assigned_to: 'Jane Smith',
    notes: 'Responded to examiner objections'
  },
  {
    id: 3,
    code: 'PAY',
    info: { name: 'Payment' },
    detail: 'Issue Fee',
    due_date: '2024-02-28',
    done: false,
    assigned_to: 'Admin',
    cost: 2000,
    fee: 0,
    currency: 'USD'
  }
]

const sampleEvents = [
  {
    id: 1,
    code: 'FIL',
    event_date: '2024-01-15',
    detail: 'US16/123,456',
    info: { name: 'Filing', status_event: false },
    notes: 'Priority application filed'
  },
  {
    id: 2,
    code: 'PUB',
    event_date: '2024-07-15',
    detail: 'US2024/0123456',
    info: { name: 'Publication', status_event: true },
    publicUrl: 'https://patents.google.com/patent/US2024123456',
  },
  {
    id: 3,
    code: 'OA',
    event_date: '2024-10-01',
    detail: 'Non-Final',
    info: { name: 'Office Action', has_tasks: true },
    notes: 'Examiner rejected claims 1-10'
  },
  {
    id: 4,
    code: 'GRT',
    event_date: '2025-01-20',
    detail: 'US11,123,456',
    info: { name: 'Grant', status_event: true },
    altMatter: { id: 10, uid: 'PAT-2025-050', country: 'US' },
    alt_matter_id: 10
  }
]
</script>