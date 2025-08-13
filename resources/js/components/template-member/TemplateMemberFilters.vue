<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="summary">{{ t('templateMember.fields.summary') }}</Label>
      <Input
        id="summary"
        v-model="localFilters.summary"
        :placeholder="t('templateMember.filters.summaryPlaceholder')"
      />
    </div>
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="class">{{ t('templateMember.fields.class') }}</Label>
      <Input
        id="class"
        v-model="localFilters.class"
        :placeholder="t('templateMember.filters.classPlaceholder')"
      />
    </div>
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="language">{{ t('templateMember.fields.language') }}</Label>
      <Select v-model="localFilters.language">
        <SelectTrigger class="w-full">
          <SelectValue :placeholder="t('templateMember.filters.languagePlaceholder')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">{{ t('common.all') }}</SelectItem>
          <SelectItem value="en">English</SelectItem>
          <SelectItem value="fr">Français</SelectItem>
          <SelectItem value="de">Deutsch</SelectItem>
        </SelectContent>
      </Select>
    </div>
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="style">{{ t('templateMember.fields.style') }}</Label>
      <Input
        id="style"
        v-model="localFilters.style"
        :placeholder="t('templateMember.filters.stylePlaceholder')"
      />
    </div>
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="format">{{ t('templateMember.fields.format') }}</Label>
      <Select v-model="localFilters.format">
        <SelectTrigger class="w-full">
          <SelectValue :placeholder="t('templateMember.filters.formatPlaceholder')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">{{ t('common.all') }}</SelectItem>
          <SelectItem value="TEXT">{{ t('templateMember.formats.text') }}</SelectItem>
          <SelectItem value="HTML">{{ t('templateMember.formats.html') }}</SelectItem>
        </SelectContent>
      </Select>
    </div>
    <div class="flex flex-col items-stretch justify-start space-y-2">
      <Label for="category">{{ t('templateMember.fields.category') }}</Label>
      <Input
        id="category"
        v-model="localFilters.category"
        :placeholder="t('templateMember.filters.categoryPlaceholder')"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:filters'])

const { t } = useI18n()

const localFilters = ref({ ...props.filters })

// Watch for changes in local filters and emit them
watch(localFilters, (newFilters) => {
  emit('update:filters', { ...newFilters })
}, { deep: true })

// Watch for changes in props and update local filters
watch(() => props.filters, (newFilters) => {
  localFilters.value = { ...newFilters }
}, { deep: true })
</script>