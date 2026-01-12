<template>
  <div class="placeholder-panel space-y-2">
    <!-- Search Input -->
    <div class="relative">
      <Search class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <Input
        v-model="searchQuery"
        :placeholder="$t('email.searchPlaceholders')"
        class="pl-8 h-8 text-sm"
      />
      <Button
        v-if="searchQuery"
        variant="ghost"
        size="sm"
        class="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6 p-0"
        @click="searchQuery = ''"
      >
        <X class="h-3 w-3" />
      </Button>
    </div>

    <!-- Content area -->
    <div class="h-[350px] overflow-y-auto space-y-1">
      <!-- Flat search results when searching -->
      <template v-if="searchQuery">
        <div v-if="filteredPlaceholders.length === 0" class="text-sm text-muted-foreground py-4 text-center">
          {{ $t('email.noPlaceholdersFound') }}
        </div>
        <Button
          v-for="item in filteredPlaceholders"
          :key="item.placeholder"
          variant="ghost"
          size="sm"
          class="w-full justify-start text-xs h-auto py-1.5 px-2"
          @click="$emit('insert', item.placeholder)"
        >
          <span class="font-mono truncate">{{ item.placeholder }}</span>
          <span class="ml-auto text-muted-foreground text-[10px] capitalize">{{ item.category }}</span>
        </Button>
      </template>

      <!-- Collapsible categories when not searching -->
      <template v-else>
        <Collapsible
          v-for="(items, category) in placeholders"
          :key="category"
          :default-open="false"
          class="border rounded-md"
        >
          <CollapsibleTrigger class="flex items-center justify-between w-full p-2 text-sm font-medium hover:bg-muted/50">
            <span class="capitalize">{{ $t(`email.placeholder.category.${category}`) }}</span>
            <ChevronDown class="h-4 w-4 transition-transform duration-200" />
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div class="px-2 pb-2 space-y-0.5">
              <Button
                v-for="(description, placeholder) in items"
                :key="placeholder"
                variant="ghost"
                size="sm"
                class="w-full justify-start text-xs h-auto py-1.5 px-2 font-mono"
                @click="$emit('insert', placeholder)"
                :title="description"
              >
                <span class="truncate">{{ placeholder }}</span>
              </Button>
            </div>
          </CollapsibleContent>
        </Collapsible>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { ChevronDown, Search, X } from 'lucide-vue-next'

const props = defineProps({
  placeholders: {
    type: Object,
    required: true,
  },
})

defineEmits(['insert'])

const searchQuery = ref('')

// Flatten and filter placeholders for search
const filteredPlaceholders = computed(() => {
  if (!searchQuery.value) return []

  const query = searchQuery.value.toLowerCase()
  const results = []

  for (const [category, items] of Object.entries(props.placeholders)) {
    for (const [placeholder, description] of Object.entries(items)) {
      if (
        placeholder.toLowerCase().includes(query) ||
        description.toLowerCase().includes(query) ||
        category.toLowerCase().includes(query)
      ) {
        results.push({ placeholder, description, category })
      }
    }
  }

  return results
})
</script>
