# Laravel phpIP Fork Refactoring Plan - Trademark Enhancement

## Overview
This plan outlines the enhancement of phpIP to better support trademark management while preserving all existing patent functionality. The approach emphasizes using the existing database structure, implementing SOLID principles, and maintaining a clean architecture.

## Phase 1: Foundation & Testing Infrastructure (Weeks 1-2)
**Goal**: Establish SOLID principles foundation with Pest testing

### 1. Set up Pest Testing Framework
- Install Pest PHP for elegant testing
- Create test suites for existing functionality
- Add architectural tests to enforce SOLID principles
- Configure GitHub Actions for CI/CD with Pest
- Create test helpers and custom assertions

### 2. Date Standardization Preparation
- Audit all date fields in database and code
- Identify all date input/output points
- Plan migration to ISO format (YYYY-MM-DD) throughout
- Create date service for consistent handling

### 3. Service Layer Architecture (Using Existing Structure)
- Create service interfaces in `App\Contracts\Services`
- Build services that leverage existing models:
  - `MatterService` - Enhance existing matter handling
  - `RenewalService` - Adapt existing renewal logic for trademark cycles
  - `NotificationService` - Improve email capabilities
- Use dependency injection without breaking existing code

## Phase 2: Core UI/UX Improvements (Weeks 3-4)
**Goal**: Implement must-have interface improvements

### 1. Interactive Calendar with Date Standardization
- Integrate date picker (Flatpickr/Pikaday)
- Migrate ALL dates to ISO format (YYYY-MM-DD):
  - Database storage
  - Display formatting
  - Form inputs
  - API responses
- Create Laravel middleware for date format conversion
- Update existing date validation rules

### 2. Case Preservation
- Add database migration to fix case handling
- Update Matter model mutators to preserve original case
- Fix display throughout application
- Ensure backward compatibility

### 3. Company Branding
- Use existing `applicant` table for company settings
- Add logo_path field to applicant table
- Display logo on login and key interfaces
- Store logos in existing storage structure

## Phase 3: Enhance Existing Trademark Features (Weeks 5-7)
**Goal**: Improve trademark functionality using current structure

### 1. Matter Category Enhancement
- Leverage existing `matter_category` table
- Add missing trademark categories
- Remove "Patents" from navigation (but keep functionality)
- Improve category-based UI logic

### 2. Status Automation
- Use existing `event` system for status changes
- Add event listeners for "Refused"/"Abandoned" status
- Automatically update renewal tasks using existing task system
- Keep all patent logic intact

### 3. Search and Display Optimization
- Create unified view using existing `matter_actors` view
- Enhance `matter_list` view for better trademark display
- Add counter to existing search functionality
- Modify `MatterExportService` to support unified view

### 4. Trademark-Specific Fields
- Add `trademark_label` to matter table (single migration)
- Use existing `classifier` system for Nice classes
- Leverage `classifier_type` for different classification systems

## Phase 4: Notification & Task Management (Weeks 8-9)
**Goal**: Enhance existing notification system

### 1. Email Notifications Enhancement
- Extend existing `TaskController::emailTasks()`
- Add 7-day lookahead for red/orange tasks
- Create HTML email templates (Outlook compatible)
- Use existing `task` table and rules

### 2. Template Management
- Enhance existing email template system
- Add role-based access using existing `default_role`
- Store templates in existing structure
- Add logo support in email generation

### 3. International Trademark Support
- Use existing `country` table and relationships
- Leverage `matter` parent/child relationships for WIPO marks
- Auto-create linked matters using existing `Matter::copyToFamily()`
- Adapt existing logic rather than creating new

## Phase 5: External Links & Integration (Weeks 10-11)
**Goal**: Add external database links and integrations

### 1. Database Links
- Extend existing `Event::publicUrl()` method
- Add trademark database URLs:
  - WIPO, UKIPO, USPTO
  - Enhance existing EUIPO links
- Use existing URL generation pattern
- Make system configurable via .env

### 2. External Resource Links
- Use existing `classifier` system for external links
- Add link type to `classifier_type` table
- Store Pappers/website links as classifiers
- Display in existing matter detail views

### 3. SIREN Integration
- Add SIREN field to existing `actor` table
- Generate Pappers/Infogreffe links dynamically
- Include in existing CSV exports

## Phase 6: Analytics & Advanced Features (Weeks 12-13)
**Goal**: Add reporting without adding complexity

### 1. Dashboard Creation
- Create dashboard using existing data structure
- Leverage Laravel's query builder for statistics
- Use existing `matter`, `event`, `task` tables
- Charts for filings, territories, renewals

### 2. Renewal System Enhancement
- Adapt existing renewal calculation for trademarks
- Use `matter_category` to determine renewal cycle
- 10-year cycles for trademarks vs annual for patents
- Modify existing stored procedures minimally

### 3. Multiple Images
- Use existing document system
- Add image type to document categories
- Display multiple images in matter view
- No new tables needed

## Phase 7: Localization & Polish (Week 14)
**Goal**: Complete French translation and finalization

### 1. French Translation
- Use existing Laravel localization
- Translate all new features
- Maintain terminology consistency
- Add to existing language files

### 2. Recent Requirements
- Design Patents: Update display logic for registration data
- Ensure dates show in granted/reg'd columns correctly
- All using existing database structure

## Technical Implementation Principles

### 1. SOLID Without Disruption
- Apply SOLID to new code
- Refactor existing code gradually
- Use adapters for legacy integration
- Maintain all existing functionality

### 2. Database Philosophy
- Minimal migrations (additive only)
- Leverage existing tables and relationships
- Use JSON fields for flexibility where appropriate
- No "squishy" tables - clean, purposeful additions only

### 3. Testing Strategy with Pest
```php
test('existing patent functionality remains intact')
test('trademark renewals calculate every 10 years')
test('dates are consistently formatted as ISO')
test('SOLID principles are followed in new services')
```

### 4. Clean Architecture
- Services use existing models
- Controllers progressively refactored
- No duplicate functionality
- Clear separation of concerns

## Success Criteria

- All existing functionality preserved
- Trademark features seamlessly integrated
- All dates standardized to ISO format
- Clean codebase following SOLID principles
- Comprehensive Pest test coverage
- No unnecessary database additions
- Performance maintained or improved

## Implementation Notes

### Priority Order
1. Testing infrastructure (foundation for safe refactoring)
2. Date standardization (affects entire system)
3. UI/UX improvements (immediate user value)
4. Trademark enhancements (core business value)
5. Notifications and automation (efficiency gains)
6. Analytics and reporting (business insights)
7. Localization (market expansion)

### Risk Mitigation
- Feature flags for gradual rollout
- Comprehensive testing before each phase
- Backward compatibility checks
- Regular user feedback loops
- Performance monitoring throughout

### Development Workflow
- Feature branches for each phase
- Pull request reviews mandatory
- Pest tests required for all new code
- Documentation updates with each feature
- Semantic versioning for releases