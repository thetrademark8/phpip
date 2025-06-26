# Deprecated Seeders

This directory contains deprecated seeders that have been replaced by the new seeding strategy.

## Why these seeders are deprecated

The original sample seeders had several issues:
- Used hardcoded IDs which could cause conflicts
- Did not use factories, making them inflexible
- Mixed production and development data
- Did not properly handle relationships

## New seeding strategy

The new seeding system uses three separate seeders:

1. **ProductionSeeder**: Contains only essential data required for production
   - Countries, roles, categories, events, classifier types
   - Default admin user
   - Should only be run once during initial deployment

2. **DevelopmentSeeder**: For development environments
   - Includes all production data
   - Adds sample actors, matters, events, tasks using factories
   - Creates realistic test scenarios

3. **TestSeeder**: For automated testing
   - Minimal data required for tests to run
   - Tests should create their own specific data using factories

## Usage

```bash
# Production deployment
php artisan db:seed --class=ProductionSeeder

# Development environment
php artisan db:seed --class=DevelopmentSeeder

# Testing environment
php artisan db:seed --class=TestSeeder
```

## Do not use these deprecated seeders

The files with `.deprecated` extension should not be used. They are kept here for reference only and will be removed in a future version.