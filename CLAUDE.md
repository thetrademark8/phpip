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