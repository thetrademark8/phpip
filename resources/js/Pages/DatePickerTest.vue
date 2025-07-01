<template>
  <MainLayout>
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="rounded-lg border bg-card p-6 shadow-sm">
        <h1 class="text-2xl font-bold mb-6">DatePicker with useForm Test</h1>
        
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Single Date Example -->
          <div>
            <label class="block text-sm font-medium mb-2">
              Due Date
            </label>
            <DatePicker 
              v-model="form.due_date" 
              placeholder="Select due date"
              :locale="locale"
            />
            <div v-if="form.errors.due_date" class="text-destructive text-sm mt-1">
              {{ form.errors.due_date }}
            </div>
          </div>

          <!-- Date Range Example -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">
                Start Date
              </label>
              <DatePicker 
                v-model="form.start_date" 
                placeholder="Start date"
                :locale="locale"
              />
              <div v-if="form.errors.start_date" class="text-destructive text-sm mt-1">
                {{ form.errors.start_date }}
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">
                End Date
              </label>
              <DatePicker 
                v-model="form.end_date" 
                placeholder="End date"
                :locale="locale"
                :is-date-disabled="(date) => form.start_date && date < new Date(form.start_date)"
              />
              <div v-if="form.errors.end_date" class="text-destructive text-sm mt-1">
                {{ form.errors.end_date }}
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center gap-4">
            <Button 
              type="submit" 
              :disabled="form.processing"
            >
              <span v-if="form.processing">Saving...</span>
              <span v-else>Save Dates</span>
            </Button>
            
            <Button 
              type="button" 
              variant="outline"
              @click="form.reset()"
              :disabled="!form.isDirty"
            >
              Reset
            </Button>
          </div>

          <!-- Form State Display -->
          <div class="mt-6 p-4 bg-muted rounded-lg">
            <h3 class="font-semibold mb-2">Form State (Debug)</h3>
            <dl class="space-y-1 text-sm">
              <div class="flex">
                <dt class="font-medium w-32">Processing:</dt>
                <dd>{{ form.processing }}</dd>
              </div>
              <div class="flex">
                <dt class="font-medium w-32">Has Errors:</dt>
                <dd>{{ form.hasErrors }}</dd>
              </div>
              <div class="flex">
                <dt class="font-medium w-32">Is Dirty:</dt>
                <dd>{{ form.isDirty }}</dd>
              </div>
              <div class="flex">
                <dt class="font-medium w-32">Form Data:</dt>
                <dd class="font-mono">{{ JSON.stringify(form.data(), null, 2) }}</dd>
              </div>
            </dl>
          </div>
        </form>

        <!-- Display submitted data -->
        <div v-if="submittedData" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
          <h3 class="font-semibold text-green-800 mb-2">Submitted Data:</h3>
          <pre class="text-sm">{{ JSON.stringify(submittedData, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { DatePicker } from '@/Components/ui/date-picker'
import { Button } from '@/Components/ui/button'

// Get locale from document or default to 'en'
const locale = computed(() => document.documentElement.lang || 'en')

// Initialize form with useForm helper
const form = useForm({
  due_date: null,
  start_date: null,
  end_date: null,
})

// Store submitted data for display
const submittedData = ref(null)

// Submit form
const submit = () => {
  // In a real app, this would post to a server endpoint
  // For testing, we'll just simulate a successful submission
  form.post('/test-date-submit', {
    preserveScroll: true,
    onSuccess: () => {
      submittedData.value = form.data()
      form.reset()
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
    },
    // Simulate the request for testing
    onStart: () => {
      // Simulate a delay
      setTimeout(() => {
        submittedData.value = form.data()
        form.reset()
        form.processing = false
      }, 1000)
    }
  })
}
</script>