<template>
  <GuestLayout>
    <Card>
      <CardHeader>
        <CardTitle class="text-2xl text-center">Login</CardTitle>
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
            <Label for="password">Password</Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              :class="{ 'border-destructive': form.errors.password }"
            />
            <p v-if="form.errors.password" class="text-sm text-destructive mt-1">
              {{ form.errors.password }}
            </p>
          </FormField>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center">
            <Checkbox
              id="remember"
              v-model:checked="form.remember"
            />
            <Label 
              for="remember" 
              class="ml-2 text-sm text-gray-600 cursor-pointer"
            >
              Remember Me
            </Label>
          </div>

          <!-- Submit Button and Forgot Password Link -->
          <div class="flex items-center justify-between">
            <Button 
              type="submit" 
              :disabled="form.processing"
              class="w-full sm:w-auto"
            >
              <span v-if="form.processing">Logging in...</span>
              <span v-else>Login</span>
            </Button>

            <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-sm text-primary hover:underline"
            >
              Forgot Your Password?
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
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Checkbox } from '@/Components/ui/checkbox'
import FormField from '@/Components/ui/form/FormField.vue'

defineProps({
  canResetPassword: {
    type: Boolean,
    default: false
  }
})

const form = useForm({
  login: '',
  password: '',
  remember: false
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>