# Testing Guide for phpIP

This project uses [Pest PHP](https://pestphp.com/) for testing, which provides a more elegant and expressive testing experience built on top of PHPUnit.

## Running Tests

### All Tests
```bash
composer test
# or
./vendor/bin/pest
```

### Unit Tests Only
```bash
composer test:unit
# or
./vendor/bin/pest --group=unit
```

### Feature Tests Only
```bash
composer test:feature
# or
./vendor/bin/pest --group=feature
```

### Architecture Tests
```bash
composer test:arch
# or
./vendor/bin/pest --group=arch
```

### With Coverage
```bash
composer test:coverage
# or
./vendor/bin/pest --coverage
```

## Writing Tests

### Basic Test Structure

```php
// Feature test
test('user can view actor index', function () {
    $user = User::factory()->create(['default_role' => 'DBRO']);
    
    $this->actingAs($user)
        ->get('/actor')
        ->assertStatus(200)
        ->assertViewHas('actorslist');
});

// Unit test
test('date service converts to ISO format', function () {
    $service = new DateService();
    
    expect($service->toIso('26/06/2025'))->toBe('2025-06-26');
});
```

### Custom Expectations

We've added custom expectations for date testing:

```php
expect('2025-06-26')->toBeIsoDate();
expect('2025-06-26 14:30:00')->toBeDateTime();
```

### Helper Functions

- `actingAs(?User $user = null)` - Create and authenticate a user
- `actingAsRole(string $role)` - Create a user with specific role
- `createTestMatter(array $attributes = [])` - Create test matter
- `createTestActor(array $attributes = [])` - Create test actor

## Architecture Tests

Architecture tests ensure code follows SOLID principles:

```php
arch('controllers follow single responsibility')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->not->toHaveMethod('__invoke')->ignoring('App\Http\Controllers\Api');

arch('services follow interface segregation')
    ->expect('App\Services')
    ->toImplement('App\Contracts\Services');
```

## Test Database

Tests use SQLite in-memory database for speed. Configuration is in `.env.testing`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

For MySQL testing, update `.env.testing`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=phpip_test
DB_USERNAME=root
DB_PASSWORD=
```

## CI/CD

Tests run automatically on GitHub Actions for:
- PHP 8.2 and 8.3
- Laravel 12.x
- Code style checks with Laravel Pint
- Architecture tests

## Best Practices

1. **Follow AAA Pattern**: Arrange, Act, Assert
2. **Use Factories**: Don't hardcode test data
3. **Test One Thing**: Each test should verify one behavior
4. **Descriptive Names**: Test names should explain what they test
5. **Fast Tests**: Use SQLite for speed, mock external services
6. **SOLID Principles**: New code must follow SOLID principles

## Troubleshooting

### Tests Failing with Migration Errors

Some migrations are MySQL-specific. The test setup skips these for SQLite. If you see migration errors, check that the migration has SQLite compatibility checks:

```php
if (DB::connection()->getDriverName() === 'sqlite' && app()->environment('testing')) {
    return;
}
```

### Permission Errors

Ensure proper permissions:
```bash
chmod -R 777 storage bootstrap/cache
```

### Clear Test Cache

```bash
./vendor/bin/pest --clear-cache
```