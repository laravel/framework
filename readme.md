# Laravel 4 Beta Change Log

## Beta 2

- Migrated to ircmaxell's "password-compat" library for PHP 5.5 forward compatibility on hashes. No backward compatibility breaks.
- Inflector migrated to L4. Eloquent models now assume their table names if one is not specified. New helpers `str_plural` and `str_singular`.