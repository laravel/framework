## Strict Relationship Mode

Laravel now supports strict mode for Eloquent relationship existence checks. This helps catch typos and undefined relationships in your queries.

### Enabling Strict Mode

Add the following to your `config/database.php`:

```php
'eloquent' => [
    'strict_relationships' => true,
],
```

Or enable via Artisan:

```bash
php artisan model:strict --enable
```

### Behavior

When strict mode is enabled, any undefined relationship used in `has()` or `whereHas()` will throw a `RelationNotFoundException`:

```php
// Throws if 'activty' relationship doesn't exist
User::has('activty')->get();
```

When strict mode is disabled (default), such queries will return an empty result set without error.

### Why Use Strict Mode?

- Prevents silent failures due to typos in relationship names
- Improves developer experience and debugging
- Opt-in, non-breaking for existing projects

---

**Note:** Strict mode only affects relationship existence checks in Eloquent query methods like `has()` and `whereHas()`.
