# Preventing Access to Missing Attributes

Laravel Eloquent provides a configuration option to help catch typos and prevent silent failures when accessing undefined model attributes.

## Configuration

To enable strict attribute checking, add the following to your `config/database.php` file:

```php
'eloquent' => [
    'prevent_accessing_missing_attributes' => env('DB_PREVENT_MISSING_ATTRIBUTES', false),
],
```

Alternatively, you can set the environment variable in your `.env` file:

```env
DB_PREVENT_MISSING_ATTRIBUTES=true
```

## Behavior

When enabled, accessing undefined attributes will throw a `MissingAttributeException`:

```php
$user = User::find(1);

// This will throw MissingAttributeException if 'full_name' doesn't exist
$fullName = $user->full_name;
```

This helps catch common typos during development:

```php
// Instead of silently returning null:
$email = $user->emial; // Throws MissingAttributeException instead of returning null
```

## Important Notes

- **Disabled by default**: This feature is opt-in to maintain backward compatibility
- **Only for existing models**: The exception is only thrown for models that have been retrieved from the database (`$model->exists === true`)
- **Not for recently created models**: Models that were just created (`$model->wasRecentlyCreated === true`) will not throw exceptions
- **Programmatic control**: You can also enable/disable this feature programmatically:

```php
// Enable for all models
Model::preventAccessingMissingAttributes(true);

// Disable for all models
Model::preventAccessingMissingAttributes(false);

// Check current state
$isEnabled = Model::preventsAccessingMissingAttributes();
```

## Custom Exception Handling

You can provide a custom callback to handle missing attribute violations:

```php
Model::handleMissingAttributeViolationUsing(function ($model, $key) {
    // Log the violation instead of throwing an exception
    Log::warning("Attempted to access missing attribute [{$key}] on model [" . get_class($model) . "]");
    
    return null; // or some default value
});
```

## Use Cases

This feature is particularly useful for:

- **Development environments**: Catching typos and undefined attributes early
- **Code reviews**: Ensuring all accessed attributes are properly defined
- **Testing**: Verifying that all expected attributes are present
- **Refactoring**: Detecting when attributes are removed but still accessed elsewhere

## Performance Impact

When disabled (default), there is no performance impact. When enabled, there is a minimal performance overhead for the additional checks, but this is typically negligible in development environments where this feature would be most beneficial.
