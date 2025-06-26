# Comprehensive UI Refactoring Plan: Laravel Blade to Inertia.js + Vue.js + shadcn-vue

## Overview
This plan outlines a complete UI layer refactoring from Laravel Blade templates to a modern SPA using Inertia.js, Vue.js 3, and shadcn-vue components. The refactoring will maintain all current functionality while improving code organization, reusability, and user experience.

## Phase 1: Foundation Setup (Week 1)

### 1.1 Install Core Dependencies
- Install Inertia.js server-side adapter for Laravel
- Install Inertia.js client-side adapter for Vue 3
- Install Vue 3 and required build tools
- Install shadcn-vue and its dependencies (Tailwind CSS, Reka UI)
- Configure Vite for Vue SFCs

### 1.2 Setup Inertia.js
- Configure HandleInertiaRequests middleware
- Update app.blade.php to be the Inertia root template
- Create app.js entry point for Vue/Inertia
- Setup shared data (auth user, flash messages, etc.)

### 1.3 Setup shadcn-vue
- Initialize shadcn-vue configuration
- Configure Tailwind CSS with shadcn-vue presets
- Setup component aliases (@/components/ui)
- Install essential shadcn-vue components:
  - Button, Input, Label, Select, Textarea
  - Dialog (to replace Bootstrap modals)
  - Table, DataTable
  - Form components
  - DatePicker (to replace our custom date picker)
  - Card, Alert, Badge
  - DropdownMenu, ContextMenu
  - Tabs, Sheet
  - Toast/Sonner for notifications

### 1.4 Create Base Layout Components
- MainLayout.vue (replacing app.blade.php layout)
- Navigation.vue (top navbar with search)
- Sidebar.vue (if needed)
- Footer.vue

## Phase 2: Component Library Development (Week 2)

### 2.1 Replace Custom Components
- **DateInput**: Replace with shadcn-vue DatePicker
  - Remove all AJAX-related code (MutationObserver, etc.)
  - Use Vue's v-model for two-way data binding
  - Maintain ISO format handling in Vue component
  - Keep French locale support
  - Simpler implementation with Vue reactivity

### 2.2 Create Reusable Components
- **MatterCard**: Display matter information
- **ActorList**: Display actors with roles
- **TaskList**: Show tasks with status indicators
- **EventTimeline**: Display matter events
- **SearchBar**: Global search component
- **StatusBadge**: For matter/task status display
- **FileUploader**: For document management

### 2.3 Form Components
- **MatterForm**: Create/edit matters
- **ActorForm**: Create/edit actors  
- **TaskForm**: Create/edit tasks
- **RuleForm**: Create/edit rules
- Use shadcn-vue Form with Zod validation
- All forms will use Vue's reactive data binding

## Phase 3: Page Migration - Core Features (Weeks 3-4)

### 3.1 Authentication Pages
- Login.vue (using shadcn-vue Card and Form)
- ForgotPassword.vue
- ResetPassword.vue

### 3.2 Dashboard/Home
- Home.vue with statistics cards
- Task summary with shadcn-vue DataTable
- Category matter counts
- Real-time updates without page refresh

### 3.3 Matter Management (Priority)
- **Index.vue**: Matter listing with filters
  - Use shadcn-vue DataTable with sorting/filtering
  - Implement server-side pagination via Inertia
  - Add column visibility toggle
  - No AJAX needed - Inertia handles data fetching
- **Show.vue**: Matter details
  - Replace Bootstrap tabs with shadcn-vue Tabs
  - Use shadcn-vue Dialog for inline editing
  - Vue reactivity for real-time UI updates
  - Component-based architecture for each section
- **Create.vue**: New matter form
- **Edit.vue**: Edit matter (as Dialog or inline)

### 3.4 Modal Replacements
Replace all Bootstrap modals with shadcn-vue Dialog:
- Matter edit dialog
- Task edit dialog (inline editing with Vue)
- Event edit dialog (reactive forms)
- Actor management dialogs
- Document selection dialog
- All dialogs will use Vue's reactive data, no AJAX loading

## Phase 4: Page Migration - Admin Features (Week 5)

### 4.1 Configuration Pages
- Categories, Types, Roles, Event Names
- All using shadcn-vue DataTable
- Inline editing with Vue reactivity
- Immediate UI updates on save

### 4.2 User Management
- User listing with DataTable
- Profile editing with reactive forms
- Permission management with checkboxes

### 4.3 Rule Management
- Complex form with shadcn-vue components
- Conditional field display using Vue directives
- Date range pickers with v-model binding

## Phase 5: Advanced Features (Week 6)

### 5.1 Renewals Module
- Complex filtering UI with Vue computed properties
- Batch operations with reactive selections
- Export functionality

### 5.2 Document Management
- File upload with progress (Vue reactive)
- Document templates
- Preview functionality
- Drag-and-drop with Vue directives

### 5.3 Reports and Analytics
- Use shadcn-vue Card for statistics
- Reactive charts that update with data changes
- No need for manual DOM updates

## Phase 6: Migration Strategy & Testing (Week 7)

### 6.1 Incremental Migration
1. Start with authentication pages
2. Move to simple CRUD pages
3. Tackle complex pages (Matter show/edit)
4. Migrate component by component

### 6.2 Testing Strategy
- Unit tests for Vue components
- E2E tests for critical workflows
- Maintain existing Pest tests
- Add Inertia-specific tests

### 6.3 Data Flow Changes
- Remove all jQuery/vanilla JS DOM manipulation
- Replace AJAX calls with Inertia visits
- Use Vue reactivity for all UI updates
- Leverage computed properties and watchers

## Technical Considerations

### State Management
- Use Vue's reactive/ref for local state
- Inertia's shared data for global state
- Props for parent-child communication
- Event emitters for child-parent communication

### Performance
- Remove jQuery and reduce bundle size
- Use Vue's built-in optimizations
- Implement code splitting
- Lazy load heavy components

### Form Handling
- Replace manual form serialization with v-model
- Use Vee-validate with Zod schemas
- Automatic form state management
- Real-time validation feedback

### Data Updates
- No more manual DOM updates
- Vue handles all reactivity
- Inertia provides seamless server communication
- Optimistic UI updates where appropriate

## Key Improvements from Current System

1. **No AJAX Complexity**: Vue reactivity replaces manual AJAX handling
2. **Simplified Date Handling**: v-model on date pickers, no DOM observation needed
3. **Cleaner Code**: Remove all jQuery event handlers
4. **Better Performance**: Virtual DOM and reactive updates
5. **Type Safety**: Can add TypeScript for better development experience
6. **Component Isolation**: Each component manages its own state

## Migration of Current Features

### Current DatePicker
- Remove: MutationObserver, manual event dispatching, AJAX reinitialization
- Add: Simple v-model binding, Vue lifecycle hooks
- Keep: ISO format conversion, French locale

### Current Modals
- Remove: Bootstrap modal events, AJAX content loading
- Add: shadcn-vue Dialog with Vue components
- Keep: All functionality, improved UX

### Current Forms
- Remove: Manual serialization, jQuery validation
- Add: v-model bindings, Vee-validate, reactive validation
- Keep: All business logic, validation rules

## Benefits of Vue-Based Approach

1. **Simplicity**: No manual DOM manipulation
2. **Maintainability**: Clear component structure
3. **Performance**: Efficient reactive updates
4. **Developer Experience**: Hot reload, Vue devtools
5. **Testing**: Easier to test isolated components
6. **Scalability**: Component-based architecture

This revised plan leverages Vue's reactivity system to eliminate the need for AJAX-based solutions, resulting in cleaner, more maintainable code while preserving all existing functionality.

## Current View Structure Analysis

### Views Using Modals (16 files)
All Bootstrap modals will be replaced with shadcn-vue Dialog components:
- layouts/app.blade.php (main modal container)
- home.blade.php
- user/index.blade.php
- type/index.blade.php
- template-members/index.blade.php
- task/index.blade.php
- rule/index.blade.php
- role/index.blade.php
- matter/show.blade.php (complex modals)
- fee/index.blade.php
- eventname/index.blade.php
- documents/index.blade.php
- default_actor/index.blade.php
- classifier_type/index.blade.php
- category/index.blade.php
- actor/index.blade.php

### Views Using DatePicker (10 files)
All will use shadcn-vue DatePicker with v-model:
- home.blade.php
- renewals/logs.blade.php
- renewals/index.blade.php
- matter/roleActors.blade.php
- matter/tasks.blade.php
- matter/events.blade.php
- fee/create.blade.php
- rule/show.blade.php
- rule/create.blade.php

### Complex Views Requiring Special Attention
1. **matter/show.blade.php**: Most complex view with tabs, modals, and real-time updates
2. **renewals/index.blade.php**: Complex filtering and batch operations
3. **home.blade.php**: Dashboard with multiple data sources
4. **matter/index.blade.php**: Advanced search and filtering

## Implementation Timeline

### Week 1: Foundation
- Day 1-2: Install dependencies, configure Inertia.js
- Day 3-4: Setup shadcn-vue, create base components
- Day 5: Create layout components, test basic setup

### Week 2: Component Development
- Day 1-2: Replace DatePicker, create form components
- Day 3-4: Build reusable UI components
- Day 5: Create complex components (DataTable wrappers)

### Week 3-4: Core Features
- Week 3: Migrate authentication, dashboard, matter index
- Week 4: Migrate matter show page and related features

### Week 5: Admin Features
- Migrate all admin CRUD pages
- Implement inline editing
- Setup permission-based UI

### Week 6: Advanced Features
- Renewals module
- Document management
- Reports and analytics

### Week 7: Testing & Polish
- Write comprehensive tests
- Performance optimization
- Final UI/UX polish

## Success Metrics

1. **Performance**: 50% reduction in page load times
2. **Code Quality**: 70% reduction in JavaScript complexity
3. **Maintainability**: Component reuse rate >80%
4. **User Experience**: Seamless transitions, no page reloads
5. **Developer Experience**: Faster feature development

## Next Steps

1. Create a new branch for the refactoring
2. Start with Phase 1.1 - Install dependencies
3. Set up basic Inertia.js configuration
4. Create first Vue component (Login page)
5. Iteratively migrate each section

This plan ensures a systematic approach to modernizing the UI while maintaining all existing functionality and improving the overall user experience.