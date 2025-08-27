# Translation Guide

This guide documents how to handle translations in the phpIP application. The application supports multiple languages with Vue i18n for frontend translations and JSON-based translatable fields in the database.

## Overview

The application supports three languages:
- **English (en)** - Primary language and fallback
- **French (fr)** - Secondary language
- **German (de)** - Secondary language

Translation files are located in `/lang/` directory:
- `/lang/en.json` - English translations
- `/lang/fr.json` - French translations  
- `/lang/de.json` - German translations

## Translation System Architecture

### 1. Frontend Translations (Vue i18n)

Static text in Vue components uses Vue i18n with the `t()` function:

```vue
<template>
  <h1>{{ t('actors.index.title') }}</h1>
  <p>{{ t('actors.index.description') }}</p>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>
```

### 2. Database Translatable Fields

Dynamic content stored in the database uses JSON format for translations:

```json
{
  "en": "English text",
  "fr": "Texte français", 
  "de": "Deutscher Text"
}
```

## Working with Frontend Translations

### Basic Usage

```vue
<!-- Simple translation -->
<Button>{{ t('actions.save') }}</Button>

<!-- Translation with parameters -->
<div>{{ t('users.welcome', { name: userName }) }}</div>

<!-- Pluralization -->
<div>{{ t('items.count', itemCount, { count: itemCount }) }}</div>
```

### Translation Key Naming Conventions

Use a hierarchical structure with dots:

```javascript
// Module-based organization
'actors.index.title'          // Page titles
'actors.fields.name'          // Form field labels
'actors.placeholders.email'   // Input placeholders
'actors.dialog.createTitle'   // Dialog titles
'actors.validation.required'  // Validation messages

// Common actions
'actions.save'
'actions.cancel'
'actions.delete'
'actions.create'

// Common terms
'common.creator'
'common.updater'
'common.createdBy'
'common.on'
```

### Parameters and Interpolation

```vue
<!-- In template -->
<div>{{ t('users.lastSeen', { time: lastSeenTime }) }}</div>

<!-- In translation file -->
{
  "users.lastSeen": "Last seen {time}"
}
```

### Pluralization

```vue
<!-- In template -->
<div>{{ t('items.foundCount', total, { count: total }) }}</div>

<!-- In translation files -->
{
  "items.foundCount": "{count} item found|{count} items found"
}
```

## Working with Database Translatable Fields

### Using the Translation Composable

```vue
<script setup>
import { useTranslatedField } from '@/composables/useTranslation'

const { translated } = useTranslatedField()

// In template or computed
const categoryName = translated(category.value.name)
</script>
```

### Direct Usage in Templates

```vue
<template>
  <!-- For database fields with translations -->
  <div>{{ translated(rule.detail) }}</div>
  
  <!-- In table columns -->
  <TableCell>{{ translated(row.original.category) }}</TableCell>
</template>
```

### Column Definitions with Translations

```javascript
const columns = computed(() => [
  {
    accessorKey: 'name',
    header: t('fields.name'),
    cell: ({ row }) => translated(row.original.name) || '-'
  }
])
```

## Translation File Organization

### Structure by Module

```json
{
  "actors": {
    "index": {
      "title": "Actors",
      "heading": "Actor Management",
      "description": "Manage actors and their information"
    },
    "fields": {
      "name": "Name",
      "email": "Email",
      "phone": "Phone"
    },
    "placeholders": {
      "name": "Enter actor name...",
      "email": "Enter email address..."
    },
    "dialog": {
      "createTitle": "Create New Actor",
      "editTitle": "Edit Actor",
      "deleteTitle": "Delete Actor"
    }
  }
}
```

### Common Sections

All translation files should include these common sections:

```json
{
  "actions": {
    "save": "Save",
    "cancel": "Cancel", 
    "delete": "Delete",
    "create": "Create",
    "edit": "Edit",
    "view": "View",
    "search": "Search",
    "clear": "Clear",
    "export": "Export"
  },
  "common": {
    "creator": "Creator",
    "updater": "Updated by",
    "createdBy": "Created by",
    "updatedBy": "Updated by",
    "on": "on",
    "yes": "Yes",
    "no": "No"
  },
  "pagination": {
    "showingResults": "Showing {from} to {to} of {total} results",
    "previous": "Previous",
    "next": "Next",
    "first": "First",
    "last": "Last"
  }
}
```

## Required Workflow for New Translations

### When Adding New Translatable Strings:

1. **Add to English first** (`/lang/en.json`):
   ```json
   "module.newString": "English text"
   ```

2. **Add French translation** (`/lang/fr.json`):
   ```json
   "module.newString": "Texte français"
   ```

3. **Add German translation** (`/lang/de.json`):
   ```json
   "module.newString": "Deutscher Text"
   ```

4. **Verify usage** in your Vue component:
   ```vue
   {{ t('module.newString') }}
   ```

### ⚠️ Critical Rule

**NEVER add a translation key without providing translations in ALL supported languages (en, fr, de).**

Missing translations will:
- Show the translation key instead of text
- Break the user experience
- Cause console warnings
- Fail quality checks

## Examples from the Codebase

### Category Module Translations

```json
// English
{
  "Categories": "Categories",
  "categories.fields.code": "Code",
  "categories.fields.category": "Category",
  "categories.placeholders.code": "Enter code...",
  "categories.dialog.createTitle": "Create New Category"
}

// French  
{
  "Categories": "Catégories",
  "categories.fields.code": "Code", 
  "categories.fields.category": "Catégorie",
  "categories.placeholders.code": "Entrez le code...",
  "categories.dialog.createTitle": "Créer une nouvelle catégorie"
}

// German
{
  "Categories": "Kategorien",
  "categories.fields.code": "Code",
  "categories.fields.category": "Kategorie", 
  "categories.placeholders.code": "Code eingeben...",
  "categories.dialog.createTitle": "Neue Kategorie erstellen"
}
```

### Database Field Example

```javascript
// Database stores:
{
  "en": "Patent Application",
  "fr": "Demande de brevet",
  "de": "Patentanmeldung"
}

// Vue component displays:
{{ translated(category.name) }}
// Returns appropriate translation based on current locale
```

## Best Practices

### 1. Consistent Key Naming
- Use module.section.item format
- Keep keys descriptive but concise
- Use camelCase for multi-word items

### 2. Translation Quality
- Provide professional translations
- Consider cultural context
- Use appropriate formality level
- Maintain consistent terminology

### 3. Parameter Usage
```vue
<!-- Good: Clear parameter names -->
{{ t('users.greeting', { userName: user.name }) }}

<!-- Bad: Unclear parameters -->
{{ t('users.greeting', { p1: user.name }) }}
```

### 4. Pluralization
```json
// Correct pluralization format
{
  "items.count": "no items|{count} item|{count} items"
}
```

### 5. Form Validation
```json
{
  "validation": {
    "required": "This field is required",
    "email": "Please enter a valid email address",
    "minLength": "Minimum {min} characters required"
  }
}
```

## Common Mistakes to Avoid

### ❌ Don't:
- Add translation keys without all language translations
- Hardcode text directly in templates
- Use translation keys as display text
- Mix database translatable fields with i18n keys
- Forget to import `useI18n` when using `t()`

### ✅ Do:
- Always provide en, fr, and de translations
- Use descriptive, hierarchical key names
- Test translations in all supported languages
- Use the `translated()` function for database fields
- Use `t()` function for static interface text

## Testing Translations

### Manual Testing
1. Switch language in the application
2. Verify all text displays correctly
3. Check for missing translations (shows keys)
4. Test pluralization with different counts
5. Verify parameter interpolation

### Code Review Checklist
- [ ] All translation keys have en, fr, de versions
- [ ] Key names follow naming conventions
- [ ] No hardcoded text in templates
- [ ] Proper use of `t()` vs `translated()`
- [ ] Parameters are clearly named
- [ ] Pluralization format is correct

## Translation File Maintenance

### Adding New Modules
When creating a new module, add a complete translation section:

```json
{
  "moduleName": {
    "index": {
      "title": "...",
      "heading": "...",
      "description": "..."
    },
    "fields": { "...": "..." },
    "placeholders": { "...": "..." },
    "dialog": { "...": "..." },
    "foundCount": "{count} item found|{count} items found"
  }
}
```

### File Organization
- Keep related translations grouped together
- Maintain alphabetical order within sections
- Use consistent indentation (2 spaces)
- End files with a single newline

### Backup and Recovery
Translation files are critical:
- Keep backups before major changes
- Use version control for all changes
- Test thoroughly after modifications
- Consider translation impact on UI layout

## Integration with components

### Filter components
```vue
<Label>{{ t('module.fields.fieldName') }}</Label>
<Input :placeholder="t('module.placeholders.fieldName')" />
```

### Dialog components
```vue
<DialogTitle>
  {{ operation === 'create' ? t('module.dialog.createTitle') : t('module.dialog.editTitle') }}
</DialogTitle>
```

### Table components
```vue
const columns = computed(() => [
  {
    accessorKey: 'field',
    header: t('module.fields.field'),
    cell: ({ row }) => translated(row.original.field)
  }
])
```

This translation system ensures a consistent, maintainable approach to internationalization across the entire application.