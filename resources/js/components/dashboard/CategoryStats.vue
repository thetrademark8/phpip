<template>
  <Card class="border-info">
    <CardHeader class="bg-info text-white p-2">
      <div class="flex items-center justify-between">
        <CardTitle class="text-lg">Categories</CardTitle>
        <Button 
          v-if="permissions.canWrite"
          size="sm"
          variant="primary"
          @click="openCreateMatter()"
        >
          Create matter
        </Button>
      </div>
    </CardHeader>
    <CardContent class="p-0 max-h-[350px] overflow-auto">
      <table class="w-full">
        <thead class="sticky top-0 bg-background">
          <tr class="border-b">
            <th class="text-left p-2"></th>
            <th class="text-left p-2">Count</th>
            <th class="p-2" v-if="permissions.canWrite">
              <span class="float-right text-muted-foreground">New</span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="category in categories" 
            :key="category.code"
            class="border-b hover:bg-accent group"
          >
            <td class="p-2">
              <Link 
                :href="`/matter?Cat=${category.code}`"
                class="text-primary hover:underline"
              >
                {{ category.category }}
              </Link>
            </td>
            <td class="p-2">{{ category.total }}</td>
            <td class="p-2" v-if="permissions.canWrite">
              <Button
                size="icon"
                variant="ghost"
                class="h-6 w-6 float-right opacity-0 group-hover:opacity-100 transition-opacity"
                @click="openCreateMatter(category.code)"
                :title="`Create ${category.category}`"
              >
                <Plus class="h-4 w-4" />
              </Button>
            </td>
          </tr>
        </tbody>
      </table>
    </CardContent>
  </Card>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import MatterDialog from '@/Components/dialogs/MatterDialog.vue'

const props = defineProps({
  categories: {
    type: Array,
    required: true
  },
  permissions: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['openCreateMatter'])

const openCreateMatter = (categoryCode = null) => {
  // For now, just navigate to the create page
  // Later this can open a dialog when we implement the matter creation dialog
  const url = categoryCode 
    ? `/matter/create?operation=new&category=${categoryCode}`
    : '/matter/create?operation=new'
  
  window.location.href = url
}
</script>