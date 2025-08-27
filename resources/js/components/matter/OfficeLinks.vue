<template>
  <div v-if="officialLinks.length > 0" class="space-y-3">
    <h4 class="font-medium text-sm text-muted-foreground">{{ t('Official Links') }}</h4>
    
    <div class="flex flex-wrap gap-2">
      <div 
        v-for="link in officialLinks"
        :key="`${link.office}-${link.type}`"
        class="inline-flex rounded-md shadow-sm"
        role="group"
      >
        <!-- Main button for opening link -->
        <Button
          variant="outline"
          size="sm"
          @click="openOfficeLink(link.url)"
          :title="`${t('View on')} ${link.office_name} (${link.type}: ${link.number})`"
          class="text-xs rounded-r-none border-r-0"
        >
          <component :is="getIcon(link.icon)" class="mr-1 h-3 w-3" />
          {{ link.office }}
          <Badge v-if="link.type !== 'filing'" variant="secondary" class="ml-1 text-xs">
            {{ t(link.type) }}
          </Badge>
        </Button>
        
        <!-- Copy button -->
        <Button
          variant="outline"
          size="sm"
          @click.stop="copyLink(link)"
          :title="t('Copy link')"
          class="text-xs rounded-l-none px-2"
        >
          <Copy v-if="!copiedStates[`${link.office}-${link.type}`]" class="h-3 w-3" />
          <Check v-else class="h-3 w-3 text-green-600" />
        </Button>
      </div>
    </div>
    
    <!-- Additional Info -->
    <div v-if="showDetails" class="space-y-1 text-xs text-muted-foreground">
      <div v-for="link in officialLinks" :key="`${link.office}-${link.type}-detail`">
        <strong>{{ link.office_name }}</strong>: 
        {{ t(link.type) }} {{ link.number }} 
        <span v-if="link.date">({{ formatDate(link.date) }})</span>
      </div>
    </div>
    
    <!-- Toggle Details -->
    <Button
      v-if="officialLinks.length > 1"
      variant="ghost"
      size="sm"
      @click="showDetails = !showDetails"
      class="text-xs p-1 h-auto"
    >
      {{ showDetails ? t('Hide details') : t('Show details') }}
    </Button>
  </div>
  
  <!-- No Links Available -->
  <div v-else-if="hasChecked" class="text-xs text-muted-foreground">
    {{ t('No official links available for this matter') }}
  </div>
  
  <!-- Loading State -->
  <div v-else class="text-xs text-muted-foreground">
    {{ t('Loading official links...') }}
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { format } from 'date-fns'
import { 
  ExternalLink, 
  Search, 
  Database, 
  FileText, 
  Globe,
  Link as LinkIcon,
  Copy,
  Check,
  Palette
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  matter: {
    type: Object,
    required: true
  },
  autoLoad: {
    type: Boolean,
    default: true
  }
})

const { t } = useI18n()

// State
const officialLinks = ref([])
const loading = ref(false)
const hasChecked = ref(false)
const showDetails = ref(false)
const copiedStates = ref({})
const copyTimeouts = ref({})

// Icon mapping
const iconcomponents = {
  Globe,
  ExternalLink,
  Search,
  Database,
  FileText,
  Link: LinkIcon,
  Palette
}

// Methods
function getIcon(iconName) {
  return iconcomponents[iconName] || ExternalLink
}

async function loadOfficialLinks() {
  if (loading.value || hasChecked.value) return
  
  loading.value = true
  hasChecked.value = true
  
  try {
    const response = await fetch(`/matter/${props.matter.id}/official-links`)
    const data = await response.json()
    
    officialLinks.value = data.links || []
    
  } catch (error) {
    console.error('Failed to load official links:', error)
    officialLinks.value = []
  } finally {
    loading.value = false
  }
}

function openOfficeLink(url) {
  if (!url) return
  
  // Open in new tab
  window.open(url, '_blank', 'noopener,noreferrer')
}

async function copyLink(link) {
  const key = `${link.office}-${link.type}`
  
  try {
    // Use modern Clipboard API
    await navigator.clipboard.writeText(link.url)
    
    // Show success state
    copiedStates.value[key] = true
    
    // Simple success feedback (you can replace with toast later)
    console.log(t('Link copied to clipboard'))
    
    // Clear any existing timeout
    if (copyTimeouts.value[key]) {
      clearTimeout(copyTimeouts.value[key])
    }
    
    // Reset icon after 2 seconds
    copyTimeouts.value[key] = setTimeout(() => {
      copiedStates.value[key] = false
      delete copyTimeouts.value[key]
    }, 2000)
    
  } catch (error) {
    console.error('Failed to copy link:', error)
    
    // Fallback for older browsers or permission issues
    fallbackCopyToClipboard(link.url)
  }
}

function fallbackCopyToClipboard(text) {
  const textArea = document.createElement('textarea')
  textArea.value = text
  textArea.style.position = 'fixed'
  textArea.style.left = '-999999px'
  textArea.style.top = '-999999px'
  document.body.appendChild(textArea)
  textArea.focus()
  textArea.select()
  
  try {
    const successful = document.execCommand('copy')
    if (successful) {
      console.log(t('Link copied to clipboard'))
    } else {
      console.error(t('Failed to copy link'))
    }
  } catch (error) {
    console.error(t('Failed to copy link'))
  }
  
  document.body.removeChild(textArea)
}

function formatDate(dateString) {
  if (!dateString) return ''
  try {
    return format(new Date(dateString), 'dd/MM/yyyy')
  } catch (error) {
    return dateString
  }
}

// Auto-load on mount if enabled
onMounted(() => {
  if (props.autoLoad) {
    loadOfficialLinks()
  }
})

// Expose methods for manual loading
defineExpose({
  loadOfficialLinks,
  reload: loadOfficialLinks
})
</script>