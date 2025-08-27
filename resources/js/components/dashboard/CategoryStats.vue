<template>
  <Card>
    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
      <CardTitle>{{ $t('dashboard.categories.title') }}</CardTitle>
      <Button 
        v-if="permissions.canWrite"
        size="sm"
        @click="openCreateMatter()"
      >
        <Plus class="h-4 w-4 mr-2" />
        {{ $t('dashboard.categories.create_matter') }}
      </Button>
    </CardHeader>
    <CardContent class="pt-4">
      <div class="space-y-4">
        <div 
          v-for="category in categoriesWithProgress" 
          :key="category.code"
          class="space-y-2"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <component 
                :is="getCategoryIcon(category.code)" 
                class="h-4 w-4 text-muted-foreground" 
              />
              <Link 
                :href="`/matter?Cat=${category.code}`"
                class="text-sm font-medium hover:underline"
              >
                {{ translated(category.category) }}
              </Link>
              <Badge v-if="category.new > 0" variant="secondary" class="text-xs">
                {{ category.new }} {{ $t('dashboard.categories.new') }}
              </Badge>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground">{{ category.total }}</span>
              <Button
                v-if="permissions.canWrite"
                size="icon"
                variant="ghost"
                class="h-6 w-6"
                @click="openCreateMatter(category.code)"
                :title="`Create ${category.category}`"
              >
                <Plus class="h-3 w-3" />
              </Button>
            </div>
          </div>
          <Progress :value="category.percentage" class="h-2" />
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Plus, FileText, Shield, Scale, Briefcase } from 'lucide-vue-next'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {translate} from "@intlify/core-base";
import {useTranslatedField} from "@/composables/useTranslation.js";

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

const { translated } = useTranslatedField()

const emit = defineEmits(['openCreateMatter'])

// Calculate total matters for percentage
const totalMatters = computed(() => {
  return props.categories.reduce((sum, cat) => sum + cat.total, 0)
})

// Add progress percentage and new matters count to categories
const categoriesWithProgress = computed(() => {
  console.log(props.categories)
  return props.categories.map(category => ({
    ...category,
    percentage: totalMatters.value > 0 ? (category.total / totalMatters.value) * 100 : 0,
  }))
})

// Map category codes to icons
const getCategoryIcon = (code) => {
  const iconMap = {
    'PAT': FileText,
    'TM': Shield,
    'DES': Briefcase,
    'DOM': Scale,
    // Add more mappings as needed
  }
  return iconMap[code] || FileText
}

const openCreateMatter = (categoryCode = null) => {
  emit('openCreateMatter', categoryCode)
}
</script>