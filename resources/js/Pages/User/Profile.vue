<template>
  <MainLayout :title="$t('My Profile')">
    <div class="container max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-8">
      <!-- Header -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{{ $t('My Profile') }}</h1>
        <p class="text-lg text-muted-foreground">{{ $t('User information') }}</p>
      </div>

      <!-- User Info Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <User class="h-5 w-5 text-primary" />
            {{ $t('User Info') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="pt-0">
          <form @submit.prevent="submitUserInfo" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
              <!-- Name -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="name" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.name }"
                  >
                    {{ $t('Name') }}
                    <span class="text-destructive ml-1">*</span>
                  </label>
                  <div v-if="!isFieldEditable('name')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('name')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('name') }}
                      </div>
                    </div>
                  </div>
                </div>
                <Input
                  id="name"
                  v-model="userForm.name"
                  type="text"
                  required
                  :disabled="!isFieldEditable('name')"
                  :class="[
                    'transition-all duration-200 focus:ring-2 focus:ring-ring/20 focus:border-ring',
                    userForm.errors.name ? 'border-destructive focus:border-destructive focus:ring-destructive/20' : 'border-input hover:border-ring/50',
                    !isFieldEditable('name') ? 'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75' : ''
                  ]"
                  :placeholder="$t('Enter your full name')"
                  :aria-describedby="!isFieldEditable('name') ? 'name-restriction' : undefined"
                />
                <div v-if="!isFieldEditable('name')" id="name-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('name') }}
                </div>
                <p v-if="userForm.errors.name" class="text-sm text-destructive">
                  {{ userForm.errors.name }}
                </p>
              </div>

              <!-- Email -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="email" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.email }"
                  >
                    {{ $t('Email') }}
                    <span class="text-destructive ml-1">*</span>
                  </label>
                  <div v-if="!isFieldEditable('email')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('email')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('email') }}
                      </div>
                    </div>
                  </div>
                </div>
                <Input
                  id="email"
                  v-model="userForm.email"
                  type="email"
                  required
                  :disabled="!isFieldEditable('email')"
                  :class="getFieldClasses('email',
                    'transition-all duration-200 focus:ring-2 focus:ring-ring/20 focus:border-ring',
                    userForm.errors.email ? 'border-destructive focus:border-destructive focus:ring-destructive/20' : 'border-input hover:border-ring/50'
                  )"
                  :placeholder="$t('Enter your email address')"
                  :aria-describedby="!isFieldEditable('email') ? 'email-restriction' : undefined"
                />
                <div v-if="!isFieldEditable('email')" id="email-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('email') }}
                </div>
                <p v-if="userForm.errors.email" class="text-sm text-destructive">
                  {{ userForm.errors.email }}
                </p>
              </div>

              <!-- Phone -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="phone" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.phone }"
                  >
                    {{ $t('Phone') }}
                  </label>
                  <div v-if="!isFieldEditable('phone')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('phone')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('phone') }}
                      </div>
                    </div>
                  </div>
                </div>
                <Input
                  id="phone"
                  v-model="userForm.phone"
                  type="tel"
                  :disabled="!isFieldEditable('phone')"
                  :class="getFieldClasses('phone',
                    'transition-all duration-200 focus:ring-2 focus:ring-ring/20 focus:border-ring',
                    userForm.errors.phone ? 'border-destructive focus:border-destructive focus:ring-destructive/20' : 'border-input hover:border-ring/50'
                  )"
                  :placeholder="$t('Enter your phone number')"
                  :aria-describedby="!isFieldEditable('phone') ? 'phone-restriction' : undefined"
                />
                <div v-if="!isFieldEditable('phone')" id="phone-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('phone') }}
                </div>
                <p v-if="userForm.errors.phone" class="text-sm text-destructive">
                  {{ userForm.errors.phone }}
                </p>
              </div>

              <!-- Role -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="default_role" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.default_role }"
                  >
                    {{ $t('Role') }}
                  </label>
                  <div v-if="!isFieldEditable('default_role')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('default_role')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('default_role') }}
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="!isFieldEditable('default_role')" class="relative">
                  <!-- Read-only display for restricted role field -->
                  <Input
                    :model-value="userForm.role_display || $t('No role assigned')"
                    type="text"
                    readonly
                    disabled
                    class="bg-muted/50 cursor-not-allowed border-input text-muted-foreground opacity-75"
                    :aria-describedby="'default_role-restriction'"
                  />
                </div>
                <AutocompleteInput
                  v-else
                  v-model="userForm.default_role"
                  v-model:display-model-value="userForm.role_display"
                  endpoint="/role/autocomplete"
                  :placeholder="$t('Select or search for a role')"
                  :initial-display-value="userForm.role_display"
                  :class="{ 
                    'border-destructive focus-within:border-destructive focus-within:ring-destructive/20': userForm.errors.default_role,
                    'border-input hover:border-ring/50': !userForm.errors.default_role
                  }"
                />
                <div v-if="!isFieldEditable('default_role')" id="default_role-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('default_role') }}
                </div>
                <p v-if="userForm.errors.default_role" class="text-sm text-destructive">
                  {{ userForm.errors.default_role }}
                </p>
              </div>

              <!-- Company -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="company_id" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.company_id }"
                  >
                    {{ $t('Company') }}
                  </label>
                  <div v-if="!isFieldEditable('company_id')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('company_id')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('company_id') }}
                      </div>
                    </div>
                  </div>
                </div>
                <div v-if="!isFieldEditable('company_id')" class="relative">
                  <!-- Read-only display for restricted company field -->
                  <Input
                    :model-value="userForm.company_display || $t('No company assigned')"
                    type="text"
                    readonly
                    disabled
                    class="bg-muted/50 cursor-not-allowed border-input text-muted-foreground opacity-75"
                    :aria-describedby="'company_id-restriction'"
                  />
                </div>
                <AutocompleteInput
                  v-else
                  v-model="userForm.company_id"
                  v-model:display-model-value="userForm.company_display"
                  endpoint="/actor/autocomplete"
                  :placeholder="$t('Select or search for a company')"
                  :initial-display-value="userForm.company_display"
                  :class="{ 
                    'border-destructive focus-within:border-destructive focus-within:ring-destructive/20': userForm.errors.company_id,
                    'border-input hover:border-ring/50': !userForm.errors.company_id
                  }"
                />
                <div v-if="!isFieldEditable('company_id')" id="company_id-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('company_id') }}
                </div>
                <p v-if="userForm.errors.company_id" class="text-sm text-destructive">
                  {{ userForm.errors.company_id }}
                </p>
              </div>

              <!-- Language -->
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <label 
                    for="language" 
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    :class="{ 'text-destructive': userForm.errors.language }"
                  >
                    {{ $t('Language') }}
                  </label>
                  <div v-if="!isFieldEditable('language')" class="flex items-center gap-1">
                    <Lock class="h-3 w-3 text-muted-foreground" />
                    <div class="relative group">
                      <AlertCircle 
                        class="h-3 w-3 text-orange-500 cursor-help" 
                        @click="showRestrictionFeedback('language')"
                      />
                      <div class="absolute left-0 top-5 bg-popover border rounded-md p-2 text-xs z-50 shadow-lg min-w-64 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        {{ getTranslatedRestrictionReason('language') }}
                      </div>
                    </div>
                  </div>
                </div>
                <Select 
                  v-model="userForm.language" 
                  :disabled="!isFieldEditable('language')"
                >
                  <SelectTrigger
                    :class="getFieldClasses('language',
                      'transition-all duration-200 w-full',
                      userForm.errors.language ? 'border-destructive focus:border-destructive focus:ring-destructive/20' : 'border-input hover:border-ring/50'
                    )"
                    :aria-describedby="!isFieldEditable('language') ? 'language-restriction' : undefined"
                  >
                    <SelectValue :placeholder="$t('Select your preferred language')" />
                  </SelectTrigger>
                  <SelectContent class="z-50">
                    <SelectItem value="en_GB" class="cursor-pointer hover:bg-accent">🇬🇧 English (British)</SelectItem>
                    <SelectItem value="en_US" class="cursor-pointer hover:bg-accent">🇺🇸 English (American)</SelectItem>
                    <SelectItem value="fr" class="cursor-pointer hover:bg-accent">🇫🇷 Français</SelectItem>
                    <SelectItem value="de" class="cursor-pointer hover:bg-accent">🇩🇪 Deutsch</SelectItem>
                    <SelectItem value="es" class="cursor-pointer hover:bg-accent">🇪🇸 Español</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="!isFieldEditable('language')" id="language-restriction" class="sr-only">
                  {{ getTranslatedRestrictionReason('language') }}
                </div>
                <p v-if="userForm.errors.language" class="text-sm text-destructive">
                  {{ userForm.errors.language }}
                </p>
              </div>
            </div>

            <!-- Show restriction summary if any fields are restricted -->
            <div v-if="hasRestrictedFieldsInForm" class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
              <div class="flex items-start gap-2">
                <AlertCircle class="h-5 w-5 text-orange-500 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-medium text-orange-800 dark:text-orange-200 text-sm">
                    {{ $t('Some fields are restricted') }}
                  </p>
                  <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                    {{ $t('Fields marked with a lock icon cannot be edited based on your user role. Contact an administrator if you need to modify these fields.') }}
                  </p>
                </div>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 pt-6 border-t border-border">
              <p class="text-sm text-muted-foreground w-2/3">
                {{ $t('Changes will be saved immediately after clicking update.') }}
              </p>
              <div class="text-right w-1/3">
                <Button
                    type="submit"
                    :disabled="userForm.processing || !hasEditableFieldsInForm"
                    class="w-full sm:w-auto px-8 font-medium transition-all duration-200 hover:scale-105 focus:scale-105"
                    :class="{
                      'opacity-50 cursor-not-allowed': !hasEditableFieldsInForm
                    }"
                >
                  <Loader2 v-if="userForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                  <span v-if="userForm.processing">{{ $t('Updating...') }}</span>
                  <span v-else>{{ $t('Update Profile') }}</span>
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Credentials Card -->
      <Card>
        <CardHeader class="pb-6">
          <CardTitle class="text-xl font-semibold flex items-center gap-2">
            <Key class="h-5 w-5 text-primary" />
            {{ $t('Update Password') }}
          </CardTitle>
        </CardHeader>
        <CardContent class="pt-0">
          <form @submit.prevent="submitPassword" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
              <!-- Username (Read-only) -->
              <div class="lg:col-span-2">
                <FormField
                  :label="$t('User name')"
                  :help-text="$t('Username cannot be changed')"
                  container-class="space-y-3"
                >
                  <Input
                    :model-value="user.login"
                    type="text"
                    readonly
                    class=" px-4 text-base bg-muted/50 cursor-not-allowed border-input text-muted-foreground font-medium"
                  />
                </FormField>
              </div>

              <!-- New Password -->
              <FormField
                :label="$t('Password')"
                :error="passwordForm.errors.password"
                :help-text="$t('Leave empty to keep current password')"
                container-class="space-y-3"
              >
                <Input
                  id="password"
                  v-model="passwordForm.password"
                  type="password"
                  class=" px-4 text-base transition-all duration-200 focus:ring-2 focus:ring-ring/20 focus:border-ring"
                  :class="{ 
                    'border-destructive focus:border-destructive focus:ring-destructive/20': passwordForm.errors.password,
                    'border-input hover:border-ring/50': !passwordForm.errors.password
                  }"
                  :placeholder="$t('Enter new password')"
                />
              </FormField>

              <!-- Confirm Password -->
              <FormField
                :label="$t('Confirm password')"
                :error="passwordForm.errors.password_confirmation"
                container-class="space-y-3"
              >
                <Input
                  id="password_confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  class=" px-4 text-base transition-all duration-200 focus:ring-2 focus:ring-ring/20 focus:border-ring"
                  :class="{ 
                    'border-destructive focus:border-destructive focus:ring-destructive/20': passwordForm.errors.password_confirmation,
                    'border-input hover:border-ring/50': !passwordForm.errors.password_confirmation
                  }"
                  :placeholder="$t('Confirm new password')"
                />
              </FormField>
            </div>

            <!-- Password requirements -->
            <div class="bg-muted/50 border border-border rounded-lg p-6 space-y-3">
              <div class="flex items-center gap-2 mb-3">
                <Info class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm font-medium text-foreground">{{ $t('Password Requirements') }}</span>
              </div>
              <ul class="text-sm text-muted-foreground space-y-1 ml-6">
                <li class="flex items-center gap-2">
                  <span class="w-1 h-1 bg-muted-foreground rounded-full"></span>
                  {{ $t('At least 8 characters long') }}
                </li>
                <li class="flex items-center gap-2">
                  <span class="w-1 h-1 bg-muted-foreground rounded-full"></span>
                  {{ $t('Contains uppercase and lowercase letters') }}
                </li>
                <li class="flex items-center gap-2">
                  <span class="w-1 h-1 bg-muted-foreground rounded-full"></span>
                  {{ $t('Contains at least one number') }}
                </li>
                <li class="flex items-center gap-2">
                  <span class="w-1 h-1 bg-muted-foreground rounded-full"></span>
                  {{ $t('Contains at least one special character') }}
                </li>
              </ul>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between items-start sm:items-center gap-4 pt-6 border-t border-border">
              <p class="text-sm text-muted-foreground w-2/3">
                {{ $t('You will be logged out after changing your password.') }}
              </p>
              <div class="text-right w-1/3">
                <Button
                    type="submit"
                    :disabled="passwordForm.processing || (!passwordForm.password && !passwordForm.password_confirmation)"
                    variant="outline"
                    class="w-full sm:w-auto  px-8 font-medium transition-all duration-200 hover:scale-105 focus:scale-105"
                >
                  <Loader2 v-if="passwordForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                  <span v-if="passwordForm.processing">{{ $t('Updating...') }}</span>
                  <span v-else>{{ $t('Update Password') }}</span>
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </MainLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import MainLayout from '@/Layouts/MainLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/components/ui/select'
import FormField from '@/components/ui/form/FormField.vue'
import AutocompleteInput from '@/components/ui/form/AutocompleteInput.vue'
import { User, Loader2, Key, Info, Lock, AlertCircle } from 'lucide-vue-next'
import { computed } from 'vue'
import { usePermissions } from '@/composables/usePermissions'

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const { t } = useI18n()

// Use the simple permissions composable
const { role, isAdmin, isClient, canWrite } = usePermissions()

// Simple field permission logic
const isFieldEditable = (field) => {
  if (isAdmin.value) return true // DBA can edit all
  if (field === 'default_role') return false // Only admin can change role
  if (field === 'company_id' && isClient.value) return false // Client can't change company
  return canWrite.value // Others need write permission
}

const getFieldClasses = (fieldName, baseClasses = '', errorClasses = '', disabledClasses = '') => {
  const classes = [baseClasses]

  if (!isFieldEditable(fieldName)) {
    classes.push(disabledClasses || 'bg-muted/50 cursor-not-allowed text-muted-foreground opacity-75')
  } else if (errorClasses) {
    classes.push(errorClasses)
  }

  return classes.filter(Boolean).join(' ')
}

// Simple restriction reason
const getTranslatedRestrictionReason = (fieldName) => {
  if (fieldName === 'default_role') {
    return t('Only administrators can modify user roles')
  }
  if (fieldName === 'company_id' && isClient.value) {
    return t('Client users cannot modify company assignment')
  }
  return t('You do not have permission to modify this field')
}



// Define profile fields for consistent checking
const profileFields = ['name', 'email', 'phone', 'default_role', 'company_id', 'language']

// Check if there are any restricted fields in the form
const hasRestrictedFieldsInForm = computed(() => {
  return profileFields.some(field => !isFieldEditable(field))
})

// Check if there are any editable fields
const hasEditableFieldsInForm = computed(() => {
  return profileFields.some(field => isFieldEditable(field))
})

// User info form
const userForm = useForm({
  name: props.user.name || '',
  email: props.user.email || '',
  phone: props.user.phone || '',
  default_role: props.user.default_role || '',
  role_display: props.user.roleInfo?.name || '',
  company_id: props.user.company_id || '',
  company_display: props.user.company?.name || '',
  language: props.user.language || 'en',
})

// Password form (separate to avoid sending user data unnecessarily)
const passwordForm = useForm({
  password: '',
  password_confirmation: '',
})

// Submit user info
const submitUserInfo = () => {
  // Filter out non-editable fields before submission
  const editableData = {}
  Object.keys(userForm.data()).forEach(field => {
    if (isFieldEditable(field)) {
      editableData[field] = userForm[field]
    }
  })
  
  // Submit only editable fields
  userForm.transform(() => editableData).put(route('profile.update'), {
    onSuccess: () => {
      // Handle language change - force page reload if language changed
      if (editableData.language !== props.user.language) {
        window.location.reload()
      }
    }
  })
}

// Submit password
const submitPassword = () => {
  passwordForm.put(route('profile.update'), {
    onSuccess: () => {
      passwordForm.reset()
    },
  })
}
</script>