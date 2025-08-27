<template>
  <GuestLayout>
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl text-center">Forgot Password</CardTitle>
        <CardDescription class="text-center">
          Enter your username and we'll send you a password reset link.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
          {{ status }}
        </div>

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

          <!-- Submit Button -->
          <div class="flex items-center justify-between">
            <Button 
              type="submit" 
              :disabled="form.processing"
              class="w-full sm:w-auto"
            >
              <span v-if="form.processing">Sending...</span>
              <span v-else>Send Password Reset Link</span>
            </Button>

            <Link
              :href="route('login')"
              class="text-sm text-primary hover:underline"
            >
              Back to Login
            </Link>
          </div>
        </form>
      </CardContent>
    </Card>
  </GuestLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FormField from '@/components/ui/form/FormField.vue'

defineProps({
  status: {
    type: String,
    default: ''
  }
})

const form = useForm({
  login: ''
})

const submit = () => {
  form.post(route('password.email'))
}
</script>