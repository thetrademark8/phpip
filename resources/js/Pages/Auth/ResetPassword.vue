<template>
  <GuestLayout>
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl text-center">Reset Password</CardTitle>
        <CardDescription class="text-center">
          Please enter your new password below.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Username Field -->
          <FormField>
            <Label for="login">User name</Label>
            <Input
              id="login"
              v-model="form.login"
              type="text"
              autocomplete="username"
              required
              autofocus
              :class="{ 'border-destructive': form.errors.login }"
            />
            <p v-if="form.errors.login" class="text-sm text-destructive mt-1">
              {{ form.errors.login }}
            </p>
          </FormField>

          <!-- Password Field -->
          <FormField>
            <Label for="password">New Password</Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              required
              :class="{ 'border-destructive': form.errors.password }"
            />
            <p v-if="form.errors.password" class="text-sm text-destructive mt-1">
              {{ form.errors.password }}
            </p>
          </FormField>

          <!-- Password Confirmation Field -->
          <FormField>
            <Label for="password_confirmation">Confirm Password</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              :class="{ 'border-destructive': form.errors.password_confirmation }"
            />
            <p v-if="form.errors.password_confirmation" class="text-sm text-destructive mt-1">
              {{ form.errors.password_confirmation }}
            </p>
          </FormField>

          <!-- Submit Button -->
          <div class="flex items-center justify-between">
            <Button 
              type="submit" 
              :disabled="form.processing"
              class="w-full"
            >
              <span v-if="form.processing">Resetting Password...</span>
              <span v-else>Reset Password</span>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </GuestLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FormField from '@/components/ui/form/FormField.vue'

const props = defineProps({
  token: {
    type: String,
    required: true
  },
  login: {
    type: String,
    default: ''
  }
})

const form = useForm({
  token: props.token,
  login: props.login,
  password: '',
  password_confirmation: ''
})

const submit = () => {
  form.post(route('password.update'), {
    onFinish: () => {
      form.reset('password', 'password_confirmation')
    },
  })
}
</script>