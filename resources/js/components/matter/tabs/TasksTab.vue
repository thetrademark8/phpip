<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle>Tasks</CardTitle>
        <div class="flex gap-2">
          <Button
            v-if="enableInlineEdit"
            size="sm"
            @click="showAddTaskDialog = true"
          >
            <Plus class="mr-1 h-3 w-3" />
            Add Task
          </Button>
        </div>
      </div>
    </CardHeader>
    <CardContent>
      <TaskList
        :tasks="tasks"
        :enable-inline-edit="enableInlineEdit"
        show-filter
        @update="handleTaskUpdate"
      />
    </CardContent>
  </Card>

  <TaskDialog
    v-model:open="showAddTaskDialog"
    :matter-id="matterId"
    @success="handleTaskAdded"
  />
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import TaskList from '@/Components/display/TaskList.vue'
import TaskDialog from '@/Components/dialogs/TaskDialog.vue'

const props = defineProps({
  tasks: Array,
  matterId: [String, Number],
  enableInlineEdit: Boolean
})

const showAddTaskDialog = ref(false)

function handleTaskUpdate() {
  router.reload({ only: ['matter'] })
}

function handleTaskAdded() {
  router.reload({ only: ['matter'] })
}
</script>