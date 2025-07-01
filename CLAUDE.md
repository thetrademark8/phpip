# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

phpIP is a Laravel-based IP (Intellectual Property) rights portfolio management system for law firms, originally developed by [jjdejong](https://github.com/jjdejong/phpip). This repository is a fork focused on modernizing the codebase and optimizing it for trademark management while maintaining support for designs and other IP assets.

### Fork Context
- **License**: GPL-3.0 (maintaining open-source compatibility)
- **Original Focus**: Patent management
- **New Focus**: ADD trademark management while PRESERVING all patent functionality
- **Goal**: Create a unified IP management system for both patents and trademarks

**IMPORTANT**: This fork aims to ADD comprehensive trademark functionality while PRESERVING all existing patent features. The goal is not to replace patent functionality but to create a unified IP management system that handles both patents and trademarks effectively.

The project manages patents, trademarks, designs, and other IP assets with features including multi-language support, document merging, automated task reminders, and various integrations.

## Code Quality Standards

### SOLID Principles
**All new code in this project MUST follow SOLID principles**. These principles ensure maintainable, scalable, and testable code:

1. **Single Responsibility Principle (SRP)**
   - Each class should have only one reason to change
   - Controllers should only handle HTTP requests/responses
   - Business logic belongs in Service classes
   - Models should only handle data and relationships

2. **Open/Closed Principle (OCP)**
   - Classes should be open for extension but closed for modification
   - Use interfaces and abstract classes for extensibility
   - Leverage Laravel's service container for dependency injection

3. **Liskov Substitution Principle (LSP)**
   - Derived classes must be substitutable for their base classes
   - Implement interfaces fully and correctly
   - Avoid breaking contracts established by parent classes

4. **Interface Segregation Principle (ISP)**
   - Clients should not depend on interfaces they don't use
   - Create specific interfaces rather than large, general ones
   - Use Laravel's contracts when appropriate

5. **Dependency Inversion Principle (DIP)**
   - Depend on abstractions, not concrete implementations
   - Use dependency injection via constructors
   - Leverage Laravel's IoC container for managing dependencies

### Code Style
- Follow PSR-12 coding standards (enforced by Laravel Pint)
- Use type declarations for all method parameters and return types
- Document complex logic with clear comments
- Write unit tests for all new service classes

## Development Commands

### Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Development
```bash
php artisan serve          # Start Laravel development server (default: http://localhost:8000)
npm run dev                 # Start Vite dev server for hot-reloading assets
php artisan queue:work      # Process background jobs (required for email sending)
```

### Testing (Pest PHP)
```bash
composer test                              # Run all tests with Pest
composer test:unit                         # Run unit tests only
composer test:feature                      # Run feature tests only
composer test:arch                         # Run architecture tests (SOLID compliance)
composer test:coverage                     # Run tests with coverage report
./vendor/bin/pest --filter=TestName        # Run specific test
```

### Code Quality
```bash
composer format             # Format PHP code using Laravel Pint
composer format:test        # Check code formatting without changing files
php artisan pint            # Alternative: Format PHP code
php artisan pint --test     # Alternative: Check formatting
```

### Build
```bash
npm run build               # Build production assets with Vite
```

### Database
```bash
php artisan migrate:fresh --seed    # Reset database and reseed
php artisan migrate:rollback        # Rollback last migration batch

# Seeding strategies (use one based on environment):
php artisan db:seed --class=ProductionSeeder   # Production: Essential data only
php artisan db:seed --class=DevelopmentSeeder  # Development: Sample data with factories
php artisan db:seed --class=TestSeeder         # Testing: Minimal data for tests
```

## Architecture Overview

### Core Business Logic
- **Models** (`app/Models/`): Eloquent models representing IP entities (Matter, Actor, Task, Event, etc.)
  - Key model: `Matter` - represents patents, trademarks, or designs
  - `Actor` - people/companies involved in matters
  - `Task` - deadlines and actions for matters
  - `Event` - history and status changes for matters

- **Controllers** (`app/Http/Controllers/`): HTTP request handlers
  - `MatterController`: Main controller for IP matter management
  - `ActorController`: Manages actors (applicants, inventors, etc.)
  - `TaskController`: Handles task/deadline management
  - `DocumentController`: File upload and document management

- **Services** (`app/Services/`): Business logic services
  - `DocumentMergeService`: Merges data into DOCX templates
  - `MatterExportService`: Exports matter data to Excel
  - `OpsService`: Integrates with EPO Open Patent Services
  - `SharePointService`: Optional SharePoint integration

### Frontend Architecture
- **Views** (`resources/views/`): Blade templates using Bootstrap 5
  - Layout: `layouts/app.blade.php`
  - Modular components in subdirectories by feature
- **JavaScript** (`resources/js/`): Vanilla JS and Bootstrap components
- **Styles** (`resources/sass/`): SCSS files compiled by Vite

### Key Features Implementation
1. **Multi-language Support**: Uses Spatie Laravel Translatable package
   - Translations stored in database
   - Managed via `app/Models/Translation.php`
   - Console command: `php artisan translations:refresh`

2. **Document Templates**: DOCX templates in `storage/app/templates/`
   - Uses PHPWord for document generation
   - Template variables replaced with matter data

3. **Task Reminders**: Automated email system
   - Command: `php artisan tasks:send-due-email`
   - Configured in Laravel scheduler

4. **Renewal Management**: Integration with Renewr.io
   - Command: `php artisan renewr:sync`
   - Stores renewal data and calculates fees

### Database Structure
- Uses MySQL/MariaDB
- Key tables: `matter`, `actor`, `task`, `event`, `matter_actor_lnk`
- Pivot tables for many-to-many relationships
- JSON columns for flexible data storage (e.g., `renewal_data`)

### Authentication & Authorization
- Laravel's built-in authentication
- Role-based access control via `default_role` table
- Client isolation for multi-tenant usage

### External Integrations
- **EPO OPS API**: Patent data retrieval (`app/Services/OpsService.php`)
- **Renewr.io**: Renewal fee calculations
- **SharePoint**: Optional document storage
- **AQS**: Patent search integration (`resources/scripts/integration_aqs.js`)

## Important Considerations

1. **Environment Variables**: Critical settings in `.env`:
   - Database connection
   - Mail configuration for task reminders
   - OPS API credentials
   - SharePoint credentials (if used)

2. **File Storage**: Documents stored in `storage/app/` by default
   - Can be configured to use SharePoint
   - Templates in `storage/app/templates/`

3. **Queue Processing**: Required for:
   - Sending emails
   - Background data syncing
   - Run with `php artisan queue:work`

4. **Scheduled Tasks**: Configure cron to run `php artisan schedule:run` every minute for:
   - Translation refresh (daily)
   - Renewal sync (daily)
   - Task reminders (daily)

## Phase 1 Completion Status

### ✅ Completed in Phase 1:
1. **Pest Testing Framework**
   - Installed and configured Pest PHP v3
   - Converted existing PHPUnit tests to Pest syntax
   - Added helper functions and custom expectations
   - Created architecture tests to enforce SOLID principles

2. **Date Standardization Foundation**
   - Audited current date usage (uses locale-based formats)
   - Created `DateServiceInterface` and `DateService` implementation
   - All dates will be standardized to ISO format (YYYY-MM-DD)
   - Added comprehensive date handling tests

3. **Service Layer Architecture**
   - Created service interfaces: `MatterServiceInterface`, `RenewalServiceInterface`, `NotificationServiceInterface`
   - Implemented `RepositoryServiceProvider` for dependency injection
   - Placeholder implementations ready for Phase 2 development

4. **CI/CD Pipeline**
   - GitHub Actions workflow for automated testing
   - Tests run on PHP 8.2 and 8.3
   - Code style checks with Laravel Pint
   - Architecture tests for SOLID compliance

5. **Testing Infrastructure**
   - SQLite in-memory database for fast tests
   - Test environment configuration (.env.testing)
   - Comprehensive test documentation
   - Composer scripts for easy test execution

6. **Database Seeding Refactoring**
   - Created model factories for Actor, Matter, Event, Task, and Classifier
   - Implemented three-tier seeding strategy (Production, Development, Test)
   - Replaced hardcoded sample data with flexible factories
   - Deprecated old sample seeders (moved to Deprecated/ directory)

### Using the New Services

When implementing new features, use dependency injection:

```php
use App\Contracts\Services\DateServiceInterface;

class MatterController extends Controller
{
    public function __construct(
        private DateServiceInterface $dateService
    ) {}
    
    public function store(Request $request)
    {
        $isoDate = $this->dateService->normalizeToIso($request->date);
        // Use $isoDate for storage
    }
}
```

## Database Seeding Strategy

The project uses a three-tier seeding strategy to separate concerns:

### 1. ProductionSeeder
- Contains only essential reference data (countries, roles, categories, events)
- Creates default admin user (login: admin, password: changeme)
- Should be run ONCE during initial production deployment
- Located in `database/seeders/ProductionSeeder.php`

### 2. DevelopmentSeeder
- Includes all production data
- Adds sample companies, inventors, matters, events using factories
- Creates realistic test scenarios with patents and trademarks
- Perfect for development and demos
- Located in `database/seeders/DevelopmentSeeder.php`

### 3. TestSeeder
- Minimal data required for automated tests
- Tests should create their own specific data using factories
- Ensures fast test execution
- Located in `database/seeders/TestSeeder.php`

### Model Factories

All major models now have factories for flexible test data generation:
- **ActorFactory**: Creates companies, persons, agents with realistic data
- **MatterFactory**: Generates patents, trademarks, designs with proper relationships
- **EventFactory**: Creates filing, publication, grant events with appropriate dates
- **TaskFactory**: Generates renewals, deadlines, responses with states
- **ClassifierFactory**: Creates titles, IPC/Nice classes, logos

### Factory Usage Examples

```php
// Create a patent with all related data
$patent = Matter::factory()
    ->patent()
    ->has(Event::factory()->filing())
    ->has(Event::factory()->publication())
    ->has(Classifier::factory()->title())
    ->create();

// Create a trademark portfolio
$company = Actor::factory()->company()->create();
$trademark = Matter::factory()
    ->trademark()
    ->for($company, 'client')
    ->has(Classifier::factory()->nice()->count(3))
    ->create();

// Create renewal tasks
$grantEvent = Event::factory()->grant()->create();
Task::factory()
    ->renewal()
    ->forEvent($grantEvent)
    ->forRenewalYear(5)
    ->create();
```

## Refactoring Goals & Planned Features

### Technical Refactoring Objectives
- **Modernization**: Update to current web development standards
- **Security**: Enhance security measures throughout the application
- **Maintainability**: Implement modular, testable architecture following SOLID principles
- **Focus Shift**: Optimize for trademark management (moving away from patent-centric features)

### Features to Remove
- Patent-specific functionality that doesn't apply to trademarks
- Obsolete features identified during the refactoring process

### Must-Have Features (Priority)

#### UI/UX Improvements
- **Interactive Calendar**: Add date picker for all date inputs to prevent manual entry errors
- **Case Preservation**: Maintain original case for titles (currently converts to lowercase)
- **Company Branding**: Display company logo on login screen and strategic interface locations

#### Category & Status Management
- **Matter Categories**: Update menu to include all trademark categories, remove "Patents"
- **Automatic Status Handling**: When status = "Refused" or "Abandoned", auto-remove renewal dates

#### Search & Display
- **Result Counter**: Show total number of matters matching search query
- **Unified View**: Merge Actor View and Status View into single table
- **Column Updates**: Remove patent-specific columns, add trademark-relevant columns
- **Export Alignment**: Ensure CSV exports match the new unified view

#### Notifications & Automation
- **Email Alerts**: Send notifications for tasks due in 7 days (red/orange status)
- **International Trademarks**: Auto-create country-specific matters for international marks
- **Trademark Label Field**: Add dedicated field for trademark labels

#### Technical Enhancements
- **HTML Emails**: Support HTML email generation for Outlook (not just Thunderbird)
- **Database Links**: Adapt Design Patent links for FR/EU databases
- **Trademark Links**: Add automatic links for trademark numbers to:
  - WIPO
  - UKIPO
  - USPTO
- **Logo in Emails**: Enable trademark logo insertion in generated emails

#### Configuration & Security
- **Email Templates**: Add default email rules and templates
- **Template Security**: Restrict client access to email templates
- **Custom Templates**: Integrate user-created email templates into standard config

### Nice-to-Have Features

#### Business Intelligence
- **Interactive Dashboards**: Statistics and analytics including:
  - Annual filing counts
  - Territory distribution (FR, EU, IR, etc.)
  - Upcoming renewal forecasts

#### Renewal System
- **Trademark-Optimized Renewals**: Simplify renewal system for trademark-specific needs

#### Localization
- **French Translation**: Complete French language support

#### Advanced Features
- **Extended Branding**: Company logo in CSV exports and matter listings
- **External Links**: Clickable links to external resources (Pappers, company websites)
- **SIREN Integration**: Auto-link to Pappers/Infogreffe based on SIREN numbers
- **Multiple Images**: Support multiple images per matter record

### Recent Additions (2025-05-27)
- **Design Patents**: Display registration date and number in "granted/reg'd" and "number" columns

## Phase 2.1 Completion Status - Interactive Calendar with Date Standardization

### ✅ Completed in Phase 2.1:

1. **Date Picker Implementation**
   - Installed Flatpickr (`npm install flatpickr`)
   - Created `DatePickerComponent.js` with AJAX-aware auto-initialization
   - Implemented MutationObserver for dynamic content support
   - Added French locale support with proper error handling
   - Fixed timezone issues with custom ISO formatting

2. **Service Layer for Date Handling**
   - Created `DatePickerServiceInterface` following SOLID principles
   - Implemented `DatePickerService` with flexible date parsing
   - Supports multiple input formats: DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY, ISO
   - Added strict validation to prevent invalid date auto-correction

3. **Middleware for Automatic Date Conversion**
   - Created `ValidateDateFields` middleware
   - Automatically converts all date fields to ISO format before validation
   - Handles both AJAX and traditional form submissions
   - Returns proper validation errors with original format

4. **Blade Component System**
   - Created reusable `DateInput` component
   - Dual input system: visible formatted input + hidden ISO input
   - Optional label display with `showLabel` parameter
   - Integrated with existing form change handlers

5. **Views Updated (16 date fields across 9 views)**
   - matter/events.blade.php (event_date)
   - matter/tasks.blade.php (due_date, done_date)
   - matter/roleActors.blade.php (date)
   - rule/create.blade.php (active_from, active_to)
   - rule/show.blade.php (active_from, active_to)
   - fee/create.blade.php (from, to)
   - renewals/index.blade.php (start_date, end_date)
   - renewals/logs.blade.php (from_date, to_date)
   - home.blade.php (expire_date)

6. **Controller Updates**
   - Removed locale-specific date parsing from EventController, TaskController, HomeController
   - Simplified to use Carbon::parse() or createFromFormat('Y-m-d')
   - Middleware now handles all format conversions

### Key Technical Decisions:

1. **Timezone-Neutral Date Handling**
   ```javascript
   // Avoid using toISOString() which converts to UTC
   formatDateToIso(date) {
       if (!date) return '';
       return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
   }
   ```

2. **AJAX Compatibility**
   - MutationObserver watches for DOM changes
   - Automatic reinitialization of date pickers
   - Proper cleanup to prevent memory leaks

3. **Integration with Existing Systems**
   - Preserved existing noformat change handlers
   - Synthetic event triggering for compatibility
   - Maintained all existing save functionality

### Testing:
- Created comprehensive Pest tests for DatePickerService
- 45 tests with 113 assertions covering all edge cases
- Tests for flexible parsing, validation, and conversion

### Important Commands:
```bash
# Run tests for date functionality
./vendor/bin/pest --filter=DatePickerService

# Rebuild assets after Flatpickr installation
npm run build

# Check for any date-related issues
php artisan pint
```

### Lessons Learned:
1. **Timezone Bug**: JavaScript's toISOString() converts to UTC, causing date shifts. Always use timezone-neutral formatting for date-only fields.
2. **AJAX Integration**: MutationObserver is essential for dynamic content. Always destroy existing instances before reinitializing.
3. **Locale Support**: Import specific locales explicitly to avoid console errors.
4. **Event Handling**: Synthetic events must be dispatched after programmatic value changes for existing handlers to work.

## Phase 2.2 Status - Case Preservation

**Status**: SKIPPED - Pending client clarification
**Date**: 2025-06-26

This phase was skipped because the database collation was previously changed to ensure uniform case handling across the system. Before implementing case preservation for matter titles, clarification is needed from the client regarding:
- The specific collation requirements
- Whether the current lowercase conversion is intentional for search/sorting purposes
- The desired behavior for case preservation vs. case-insensitive searching

See TODO.md for details.

## Phase 2.3 Completion Status - Company Branding (Static Logo)

### ✅ Completed in Phase 2.3:

1. **Static Logo Configuration**
   - Added `COMPANY_LOGO` environment variable to `.env.example`
   - Added logo configuration to `config/app.php`
   - Each IP firm instance can configure their own logo path

2. **Logo Storage Structure**
   - Created `/public/images/logos/` directory
   - Added README.md with usage instructions
   - Created sample SVG logo for testing

3. **UI Implementation**
   - Updated navbar in `layouts/app.blade.php`:
     - Logo displays before app name
     - Responsive sizing (max 40px height, 150px width)
     - Only shows if configured
   - Updated login page:
     - Logo centered above login form
     - Larger size for better visibility (max 80px height, 250px width)

4. **Testing**
   - Created `CompanyLogoTest` with comprehensive coverage
   - Tests configuration access, login page display, and navbar display
   - All 4 tests passing

### Key Design Decisions:

1. **Static Configuration Approach**
   - Used environment variables instead of database storage
   - Each IP firm has one instance with a fixed logo
   - Simple `.env` configuration: `COMPANY_LOGO=images/logos/company-logo.png`

2. **Flexible Implementation**
   - Logo display is conditional - works without configuration
   - Supports PNG, JPG, and SVG formats
   - Uses Bootstrap classes for responsive behavior

3. **Accessibility**
   - Alt text uses company name from configuration
   - Maintains visual hierarchy with proper sizing

4. **Logo/Text Display Logic**
   - When logo is configured: Only logo displays in navbar (text is hidden)
   - When no logo configured: App name displays as text
   - Provides cleaner branding when logo is available

### Usage Instructions:

1. Place your company logo in `/public/images/logos/`
2. Update your `.env` file:
   ```
   COMPANY_LOGO=images/logos/your-logo.png
   ```
3. Logo will automatically appear in navbar and login page
4. App name text will be hidden in navbar when logo is present

This completes Phase 2 of the refactoring plan. The UI/UX improvements are now implemented with date standardization, skipped case preservation (pending clarification), and static company branding with intelligent logo/text display.

## UI Refactoring: Laravel Blade to Inertia.js + Vue.js + shadcn-vue

### Phase 1: Foundation Setup ✅

Successfully implemented the foundation for the UI refactoring from Laravel Blade templates to a modern SPA using Inertia.js, Vue.js 3, and shadcn-vue.

#### 1.1 Core Dependencies Installed ✅
- **Inertia.js server-side**: `composer require inertiajs/inertia-laravel`
- **Inertia.js client-side**: `npm install @inertiajs/vue3`
- **Vue 3 and build tools**: `npm install vue@3 @vitejs/plugin-vue`
- **Tailwind CSS v4**: `npm install tailwindcss @tailwindcss/vite`
- **shadcn-vue dependencies**: 
  - Utilities: `@vueuse/core class-variance-authority clsx tailwind-merge`
  - Core: `radix-vue vee-validate @vee-validate/zod zod`
  - Icons: `lucide-vue-next`
  - Animations: `tw-animate-css`
  - Types: `@types/node`

#### 1.2 Inertia.js Configuration ✅
- **Middleware**: Created and configured `HandleInertiaRequests` middleware
  - Added shared data for auth user, flash messages, and app config
  - Registered in `bootstrap/app.php`
- **Root template**: Created `resources/views/app.blade.php` with Inertia directives
- **Vue entry point**: Set up `resources/js/app.js` with Inertia app initialization
- **Ziggy integration**: Installed for JavaScript route handling

#### 1.3 Tailwind CSS v4 & shadcn-vue Setup ✅
- **Tailwind v4 approach**: No config file needed - everything is CSS-based
- **CSS configuration**: Set up with `@import "tailwindcss"` and CSS variables
- **Theme setup**: Configured design tokens using `:root` CSS variables
- **shadcn-vue config**: Created `components.json` with proper paths
- **Utilities**: Created `cn()` utility function in `lib/utils.js`

#### 1.4 Base Layout Components ✅
- **MainLayout.vue**: Main layout wrapper with navigation and footer
- **Navigation.vue**: Top navbar with logo/text logic and auth status
- **Footer.vue**: Simple footer component
- **Test page**: Created `Test.vue` to verify setup

### Key Technical Decisions:

1. **Tailwind CSS v4**: Using the new CSS-based configuration approach with `@theme` directive
2. **Component structure**: Organized with `/Pages`, `/Components`, `/Layouts` directories
3. **State management**: Using Inertia's shared data for global state
4. **Styling approach**: CSS variables for theming, supporting light/dark modes
5. **Build setup**: Vite configured with Vue and Tailwind plugins

### Verification:
- Build successful: `npm run build` completes without errors
- Test route created: `/test-inertia` to verify Inertia rendering
- All dependencies properly installed and configured

### Next Steps:
Continue with Phase 2 (Component Library Development) to:
1. Replace the custom DatePicker with shadcn-vue DatePicker
2. Create reusable components (MatterCard, ActorList, etc.)
3. Build form components with Zod validation
4. Start migrating actual pages from Blade to Vue

## Phase 2: Component Library Development

### Phase 2.1: DatePicker Replacement ✅

Successfully replaced the complex 345-line jQuery DatePicker with a clean Vue component:

**Old Implementation Issues:**
- MutationObserver for dynamic content detection
- Manual AJAX reinitialization
- Hidden input fields for ISO format conversion
- Complex event handling and form submission hacks

**New DatePicker Component:**
- Simple v-model binding (no hidden inputs)
- Built-in ISO format handling (always stores YYYY-MM-DD)
- French locale support via date-fns
- Clean integration with Inertia's useForm
- Only ~100 lines of code vs 345 lines
- Test page available at `/test-datepicker`

### Phase 2.2: Display Components ✅

Created a comprehensive set of reusable display components:

1. **StatusBadge** (`/Components/display/StatusBadge.vue`)
   - Configurable status indicators with icons
   - Supports task, matter, and event statuses
   - Color-coded based on status type
   - Tooltips for additional context

2. **MatterCard** (`/Components/display/MatterCard.vue`)
   - Compact matter information display
   - Shows key details: UID, category, title, client, dates
   - Action buttons for edit/view
   - Count badges for tasks, events, actors
   - Responsive design

3. **ActorList** (`/Components/display/ActorList.vue`)
   - Groups actors by role
   - Drag-and-drop reordering within roles
   - Inline editing capabilities
   - Shows inherited actors in italic
   - Special handling for client warnings

4. **TaskList** (`/Components/display/TaskList.vue`)
   - Filterable task display (All, Open, Overdue, Done)
   - Color-coded based on urgency
   - Checkbox for quick completion
   - Shows financial information for renewals
   - Inline editing support

5. **EventTimeline** (`/Components/display/EventTimeline.vue`)
   - Chronological event display with timeline UI
   - Icons based on event type
   - Links to public URLs (patents, etc.)
   - Related matter connections
   - Status events highlighted

### Component Showcase

Created a comprehensive showcase page at `/test-components` demonstrating:
- All display components with sample data
- DatePicker variations (single date, date range)
- Status badge variations
- Interactive examples

### Key Technical Achievements:

1. **Vue Composition API**: All components use `<script setup>` syntax
2. **TypeScript-ready**: Props are properly typed
3. **Tailwind Styling**: Consistent use of utility classes
4. **Icon Library**: Integrated lucide-vue-next for consistent icons
5. **Date Handling**: Standardized date formatting with date-fns
6. **Inertia Integration**: Components work seamlessly with Inertia's router and forms

### Benefits Over jQuery Implementation:

1. **Simplicity**: No DOM manipulation, just reactive data
2. **Maintainability**: Clear component boundaries
3. **Reusability**: Components can be used anywhere
4. **Performance**: Virtual DOM updates only what changes
5. **Testing**: Easier to unit test isolated components

### Next Steps for Phase 2:

1. **Phase 2.3**: Build form components with useForm integration
   - FormField wrapper
   - AutocompleteInput replacement
   - MatterForm, ActorForm, TaskForm
   - Validation with Zod schemas

2. **Phase 2.4**: Dialog system to replace Bootstrap modals
   - Generic DialogWrapper
   - FormDialog for inline editing
   - ConfirmDialog for deletions
   - Integration with useForm for submissions

### Phase 2.5: Dialog System & Inline Editing ✅

Successfully implemented a comprehensive dialog system and inline editing capabilities:

**Dialog Component Renaming:**
- Renamed all "Simple" prefix components for cleaner naming
- `SimpleMatterDialog` → `MatterDialog`
- `SimpleActorDialog` → `ActorDialog`
- `SimpleTaskDialog` → `TaskDialog`
- `SimpleConfirmDialog` → `ConfirmDialog`
- Updated test page route from `/test-dialogs-simple` to `/test-dialogs`

**Inline Editing Components:**

1. **InlineEdit.vue** - Generic inline edit wrapper
   - Supports text, select, and date input types
   - Shows edit icon on hover
   - Smooth transition between view/edit modes
   - Keyboard shortcuts (Enter to save, Escape to cancel)
   - Loading states and error handling

2. **EditableField.vue** - Inertia-integrated field editor
   - Wraps InlineEdit with useForm integration
   - Automatic form submission to specified URLs
   - Error display and validation
   - Preserves scroll and state on updates
   - Emits events for parent component updates

**Enhanced Display Components:**

1. **ActorList.vue**
   - Added inline editing for name, company, email, phone
   - Disabled editing for inherited actors
   - New prop: `enableInlineEdit` and `updateUrl`
   - Emits 'update' events when fields are saved

2. **TaskList.vue**
   - Inline editing for task details, due date, assigned user
   - Date picker integration for due dates
   - Real-time updates without page refresh
   - Maintains task filtering and sorting

3. **EventTimeline.vue**
   - Inline editing for event dates and details
   - Timeline visualization preserved during edits
   - Consistent styling with other components

**Technical Improvements:**
- All components use modern Vue 3 Composition API
- Removed jQuery dependencies
- Fetch API already in use (no AJAX conversion needed)
- Consistent event handling patterns
- Proper TypeScript-ready prop definitions

**Benefits:**
- Reduced clicks - edit in place without dialogs
- Better UX with instant feedback
- Cleaner codebase without "Simple" prefixes
- Reusable inline editing pattern
- Seamless Inertia.js integration

### Phase 2.5 Completion Summary

✅ **All tasks completed successfully:**
1. Renamed all dialog components (removed "Simple" prefix)
2. Created InlineEdit.vue and EditableField.vue components
3. Added inline editing to ActorList, TaskList, and EventTimeline
4. Updated route from `/test-dialogs-simple` to `/test-dialogs`
5. Verified AutocompleteInput already uses fetch API

**Key Achievements:**
- Clean component naming convention
- Reusable inline editing system
- Seamless Inertia.js integration
- Modern Vue 3 Composition API usage
- No jQuery dependencies

**Test Pages Available:**
- `/test-dialogs` - Dialog system showcase
- `/test-components` - Display components with inline editing
- `/test-datepicker` - Date picker functionality

The UI refactoring is progressing well with Phase 2.5 complete. The inline editing feature significantly improves user experience by reducing the need for modal dialogs.

## Phase 3: Page Migration - Core Features

### Phase 3.3: Matter Management (In Progress)

#### Matter Index Page ✅

Successfully modernized the Matter listing page using the generic DataTable component with shadcn-vue for a clean, modern UI.

**Created Components:**

1. **Pages/Matter/Index.vue** - Modernized matter listing page
   - Clean header with title and export button
   - Filter panel in a Card component for better organization
   - Active filter badges that can be clicked to remove
   - Results count display
   - DataTable integration with custom columns
   - Custom pagination controls

2. **Components/matter/MatterFilters.vue** - Advanced filter panel
   - Toggle between Actor View and Status View using ToggleGroup
   - Switch components for toggles (Show Containers, Show Mine, Include Dead)
   - Grid layout for filter inputs
   - DatePicker integration for date filters
   - Debounced text inputs (500ms delay)
   - Responsive design

3. **New UI Components Created:**
   - `ToggleGroup` and `ToggleGroupItem` - For view mode selection
   - `Badge` - For filter badges and category display
   - `Switch` - For toggle options

**Key UI/UX Improvements:**
- ✅ Filters moved to dedicated panel above table (cleaner layout)
- ✅ Active filters shown as removable badges
- ✅ Modern toggle components instead of radio buttons
- ✅ Switch components for boolean options
- ✅ Results count prominently displayed
- ✅ Category badges with color coding
- ✅ Status badges for matter status
- ✅ Relative dates for recent items (e.g., "2 days ago")
- ✅ Icons for filing/grant numbers
- ✅ Tooltips for truncated text
- ✅ Loading states during navigation
- ✅ Responsive grid layout for filters

**Controller Updates:**
- Changed from `simplePaginate` to `paginate` to get total count
- Maintained all existing filter functionality
- Backward compatibility preserved

#### Matter Search ✅

The Matter Search functionality was already implemented and working:

**Existing Components:**
- `SearchModal.vue` - Full-featured search dialog in navigation
- Advanced filters for category, status, and responsible person
- Real-time search results display
- Click-to-navigate functionality

**Search Endpoint:**
- `MatterController@search` - Returns JSON with filtered results
- Supports text search across reference, title, and actors
- Category and status filtering
- Returns up to 50 results ordered by relevance

**Integration:**
- Search modal accessible from navigation bar
- Keyboard shortcut support (could be added)
- Results show matter reference, title, status, and client

#### Matter Index Page ✅

Successfully migrated the Matter listing page from Laravel Blade to Vue.js with Inertia.js.

**Created Components:**

1. **Pages/Matter/Index.vue** - Main matter listing page
   - Filter buttons header (Show Containers, Actor/Status View, Show Mine, Include Dead)
   - Export functionality
   - Clear filters button
   - Integrates with MatterDataTable component

2. **Components/matter/MatterDataTable.vue** - Reusable data table component
   - Dynamic columns based on Actor View / Status View toggle
   - Sortable columns with visual indicators
   - Inline filter inputs with debounced updates
   - Pagination support
   - Click-to-navigate rows
   - Date formatting with date-fns

**Controller Updates:**
- Modified `MatterController@index` to return Inertia response when X-Inertia header is present
- Maintains backward compatibility with Blade views
- Passes all filters and pagination data to Vue component

**Key Features Preserved:**
- ✅ Dual view mode (Actor View / Status View)
- ✅ All filter inputs with live updates
- ✅ Column sorting with visual indicators
- ✅ URL-based filtering for bookmarks
- ✅ Show Containers toggle
- ✅ Show Mine filter (for non-client users)
- ✅ Include Dead matters option
- ✅ Export to CSV functionality
- ✅ Pagination with preserved filters

**Technical Implementation:**
- Uses Inertia router for seamless navigation
- Debounced filter inputs (500ms delay)
- Preserves state and scroll position during filter updates
- Clean URL parameters (removes empty values)
- Responsive design with Tailwind CSS

**Usage:**
Navigate to `/matter` to see the new Vue-based listing page. All existing functionality is preserved with improved performance and user experience.

### Phase 3.1: Authentication Pages ✅

Successfully migrated the authentication system from Laravel Blade templates to Vue.js components using Inertia.js and shadcn-vue.

**Created Components:**

1. **GuestLayout.vue** - Clean guest layout for auth pages
   - Centered design with responsive breakpoints
   - Company logo display support
   - Flash message handling (success/error)
   - Minimal, focused UI for authentication

2. **Auth/Login.vue** - Modern login page
   - Uses shadcn-vue Card and form components
   - Username-based authentication (uses 'login' field)
   - Remember Me functionality
   - Form validation with error display
   - Link to password reset (if enabled)

3. **Auth/ForgotPassword.vue** - Password reset request
   - Simple form to request password reset
   - Username input (not email)
   - Success message display
   - Back to login link

4. **Auth/ResetPassword.vue** - Password reset form
   - Token-based password reset
   - Password and confirmation fields
   - Auto-fills username if provided
   - Redirects to home on success

**Controller Updates:**

1. **LoginController**
   - Added `showLoginForm()` for Inertia response
   - Added `authenticated()` for proper redirects
   - Maintains username-based auth (login field)

2. **ForgotPasswordController**
   - Added `showLinkRequestForm()` for Inertia
   - Override `credentials()` to use 'login' field
   - Custom `sendResetLinkResponse()` for status messages

3. **ResetPasswordController**
   - Added `showResetForm()` with token support
   - Override `credentials()` to use 'login' field
   - Custom `sendResetResponse()` with success flash

**Technical Implementation:**
- All forms use Inertia's `useForm` composable
- Automatic CSRF protection via Inertia
- Preserved all Laravel auth functionality
- Flash messages handled via shared Inertia data
- Responsive design with Tailwind CSS
- Consistent with existing authentication flow

**Key Features Preserved:**
- Username-based authentication (not email)
- Remember Me functionality
- Password reset flow (if mail is configured)
- Intended URL redirect after login
- Guest/auth middleware configuration

The authentication pages are now fully migrated to Vue.js while maintaining all existing functionality and improving the user experience with modern UI components.

### Phase 3.2: Dashboard/Home Page ✅

Successfully migrated the home/dashboard page from Laravel Blade to Vue.js with Inertia, fixing the login redirect issue in the process.

**Created Components:**

1. **Home.vue** - Main dashboard page
   - Grid layout with left panel (categories/users) and right panel (tasks/renewals)
   - Filter system with radio buttons (Everyone/User/Client)
   - Client autocomplete integration
   - Batch task/renewal clearing with date selection
   - Real-time updates using Inertia router

2. **CategoryStats.vue** - Categories statistics card
   - Display matter counts per category
   - Links to filtered matter views
   - Quick create buttons for each category
   - Permission-based UI (create buttons only for readwrite users)

3. **UserTasksSummary.vue** - Users task overview
   - Shows open tasks count per user
   - Color-coded urgent dates (red for overdue, yellow for upcoming)
   - Links to user-specific dashboard views
   - Clean table layout with hover effects

4. **TaskList.vue** - Dashboard-specific task list
   - Dynamic loading based on filters
   - Pagination support
   - Checkbox selection for batch operations
   - Color-coded rows for overdue/upcoming tasks
   - Handles both HTML (current) and JSON (future) responses

5. **RenewalList.vue** - Renewal tasks display
   - Similar to TaskList but filtered for renewals
   - Cost display support (when available)
   - Batch selection and clearing
   - Pagination support

**Controller Updates:**

1. **HomeController**
   - Returns Inertia response with categories and task counts
   - Handles filter parameters
   - Updated clearTasks method with proper validation and JSON response

2. **TaskController**
   - Added JSON response support for AJAX requests
   - Returns paginated data with related models
   - Maintains backward compatibility with Blade views

3. **HandleInertiaRequests**
   - Added CSRF token to shared data for fetch requests

**Key Features Implemented:**
- ✅ Fixed login redirect issue (now properly redirects to dashboard)
- ✅ Category statistics with create buttons
- ✅ User tasks summary with urgency indicators
- ✅ Task filtering (all/mine/client)
- ✅ Client autocomplete for filtering
- ✅ Batch task/renewal clearing
- ✅ Real-time updates without page refresh
- ✅ Permission-based UI elements
- ✅ Responsive grid layout

**Technical Improvements:**
- No more vanilla JavaScript with DOM manipulation
- Clean component architecture
- Proper state management
- Modern fetch API instead of jQuery AJAX
- Loading states and error handling
- Maintains all existing functionality

The dashboard is now fully modernized with Vue.js components while preserving all functionality and improving user experience with instant updates and better visual feedback.

### UI Improvements - Navbar & DataTable Implementation

Successfully enhanced the Navigation component and implemented DataTable functionality:

**Navigation Enhancement:**
- Full menu structure restored with dropdowns (Matters, Tools, Tables)
- Matter search functionality with field selection
- Company logo display support
- User dropdown with profile/logout
- Permission-based menu visibility
- Mobile responsive design

**DataTable Implementation:**
1. **Installed Dependencies:**
   - @tanstack/vue-table for table functionality
   - Created shadcn-vue table components (Table, TableBody, TableCell, etc.)
   - Created DropdownMenu components for navigation

2. **Built Reusable DataTable Component:**
   - Supports sorting, pagination, and selection
   - Custom column rendering with render functions
   - Loading and empty states
   - Row selection with checkboxes
   - Configurable page sizes

3. **Dashboard Refactoring:**
   - **HomeController**: Now fetches tasks and renewals server-side
   - **TaskList & RenewalList**: Converted to use DataTable component
   - **Home.vue**: Receives pre-loaded data (no more client-side fetching)
   - Eliminated loading delays by passing data during initial render

**Benefits Achieved:**
- **Performance**: No more client-side data fetching delays
- **Consistency**: Reusable DataTable for all table needs
- **UX**: Full navigation features restored with modern UI
- **Maintainability**: Clean component architecture
- **Accessibility**: Proper ARIA labels and keyboard navigation

**Important Note**: Run `npm install` to install the new @tanstack/vue-table dependency before building assets.