<template>
  <nav class="bg-primary text-primary-foreground shadow-sm mb-1">
    <div class="container-fluid px-4">
      <div class="flex items-center justify-between h-16">
        <!-- Logo and Brand -->
        <div class="flex items-center">
          <Link href="/" class="flex items-center">
            <img 
              v-if="$page.props.app.company_logo" 
              :src="`/${$page.props.app.company_logo}`"
              :alt="$page.props.app.company_name || $page.props.app.name"
              class="h-10 w-auto max-w-[150px]"
            >
            <span v-else class="text-xl font-semibold">{{ $page.props.app.name }}</span>
          </Link>
        </div>

        <!-- Search Modal (Desktop) -->
        <div v-if="$page.props.auth.user" class="hidden md:block">
          <SearchModal />
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center space-x-2">
          <template v-if="$page.props.auth.user">
            <!-- Dashboard -->
            <Link 
              href="/home" 
              class="px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-foreground/10 transition-colors"
            >
              Dashboard
            </Link>

            <!-- Matters Dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="hover:bg-white/5 hover:text-primary-foreground">
                  Matters
                  <ChevronDown class="ml-1 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" class="w-48">
                <DropdownMenuItem as-child>
                  <Link href="/matter">All</Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link href="/matter?display_with=PAT">Patents</Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link href="/matter?display_with=TM">Trademarks</Link>
                </DropdownMenuItem>
                <template v-if="canWrite">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="openCreateMatter">
                    Create
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="openCreateFromOPS">
                    Create family from OPS
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>

            <!-- Tools Dropdown (for readonly and above) -->
            <DropdownMenu v-if="canRead">
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="hover:bg-white/5 hover:text-primary-foreground">
                  Tools
                  <ChevronDown class="ml-1 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" class="w-48">
                <DropdownMenuItem as-child>
                  <Link href="/renewal">Manage renewals</Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link href="/fee">Renewal fees</Link>
                </DropdownMenuItem>
                <template v-if="isAdmin">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem as-child>
                    <Link href="/rule">Rules</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/document">Email template classes</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/template-member">Email templates</Link>
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>

            <!-- Tables Dropdown -->
            <DropdownMenu v-if="canRead">
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="hover:bg-white/5 hover:text-primary-foreground">
                  Tables
                  <ChevronDown class="ml-1 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" class="w-48">
                <DropdownMenuItem as-child>
                  <Link href="/actor">Actors</Link>
                </DropdownMenuItem>
                <template v-if="isAdmin">
                  <DropdownMenuSeparator />
                  <DropdownMenuItem as-child>
                    <Link href="/user">DB Users</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/eventname">Event names</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/category">Categories</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/role">Actor roles</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/default_actor">Default actors</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/type">Matter types</Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child>
                    <Link href="/classifier_type">Classifier types</Link>
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>

            <!-- User Dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="hover:bg-white/5 hover:text-primary-foreground">
                  {{ $page.props.auth.user.login }}
                  <ChevronDown class="ml-1 h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuItem as-child>
                  <Link href="/user/profile">My Profile</Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="logout">
                  Logout
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </template>

          <!-- Guest Links -->
          <template v-else>
            <Link 
              href="/login" 
              class="px-3 py-2 rounded-md text-sm font-medium hover:bg-primary-foreground/10 transition-colors"
            >
              Login
            </Link>
          </template>
        </div>

        <!-- Mobile menu button -->
        <Button 
          v-if="$page.props.auth.user"
          @click="mobileMenuOpen = !mobileMenuOpen"
          variant="ghost" 
          size="icon"
          class="md:hidden text-primary-foreground hover:bg-primary-foreground/10"
        >
          <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
          <X v-else class="h-6 w-6" />
        </Button>
      </div>

      <!-- Mobile Menu -->
      <div v-if="mobileMenuOpen && $page.props.auth.user" class="md:hidden pb-4">
        <!-- Mobile Search -->
        <div class="mb-4">
          <SearchModal>
            <template #trigger>
              <Button variant="secondary" class="w-full justify-start">
                <Search class="h-4 w-4 mr-2" />
                Search Matters
              </Button>
            </template>
          </SearchModal>
        </div>

        <!-- Mobile Navigation Links -->
        <div class="space-y-2">
          <Link 
            href="/home" 
            class="block px-3 py-2 rounded-md text-base font-medium hover:bg-primary-foreground/10 transition-colors"
          >
            Dashboard
          </Link>
          
          <!-- Mobile menu items would follow similar pattern -->
          <!-- Simplified for brevity, but would include all menu items -->
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { ChevronDown, Menu, X, Search } from 'lucide-vue-next'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import SearchModal from '@/Components/ui/SearchModal.vue'

// State
const mobileMenuOpen = ref(false)

// Computed permissions
const canRead = computed(() => {
  const role = usePage().props.auth.user?.role
  return role && (role === 'RO' || role === 'RW' || role === 'DBA')
})

const canWrite = computed(() => {
  const role = usePage().props.auth.user?.role
  return role && (role === 'RW' || role === 'DBA')
})

const isAdmin = computed(() => {
  const role = usePage().props.auth.user?.role
  return role === 'DBA'
})

// Methods
const logout = () => {
  router.post('/logout')
}

const openCreateMatter = () => {
  // This will be replaced with a proper modal/dialog in the future
  window.location.href = '/matter/create?operation=new'
}

const openCreateFromOPS = () => {
  // This will be replaced with a proper modal/dialog in the future  
  window.location.href = '/matter/create?operation=ops'
}
</script>