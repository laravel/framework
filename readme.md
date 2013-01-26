# Laravel 4 Beta Change Log

## Beta 2

- Migrated to ircmaxell's [password-compat](http://github.com/ircmaxell/password_compat) library for PHP 5.5 forward compatibility on hashes. No backward compatibility breaks.
- Inflector migrated to L4. Eloquent models now assume their table names if one is not specified. New helpers `str_plural` and `str_singular`.
- Improved `Route::controller` so that `URL::action` may be used with RESTful controllers.
- Added model binding to routing engine via `Route::model` and `Route::bind`.
- Added `missingMethod` to base Controller, can be used to handle catch-all routes into the controller.
- Fixed bug with Redis data retrieval that caused server to hang.
- Implemented `ArrayableInterface` and `JsonableInterface` on `MessageBag`.
- Fixed bug where `hasFile` returned `true` when `file` returned `null`.
- Changed default PDO case constant to `CASE_NATURAL`.
- `DB::table('foo')->truncate()` now available on all supported databases.
- Fixed Twitter Bootstrap compatibility in Paginator.
- Allow multiple views to be passed to `View::composer`.
- Added `Request::segment` method.
- No need to prefix Translator methods with colons anymore.
- Allow inline error messages for an entire rule on the Validator.
- Can now automatically auto-load a relation for every query by setting the `with` attribute on models.
- Fix fallback locale handling in Translator.
- Added constructor arguments and `merge` method to `MessageBag`.
- IoC container will now resolve default parameters if no binding is available.
- Fix auto environment detection on Artisan.
- Fix BrowserKit request processing.
- Added `Config::hasGroup` method.
- Added `DB::unprepared` method for running raw, unprepared queries against PDO.
- Allow `:key` place-holder in MessageBag messages.
- Added `Auth::validate` method for validating credentials without logging in.
- Added `Auth::stateless` method for logging in for a single request without sessions or cookies.
- Added `DB::extend` method for adding custom connection resolvers.
- Added `each` and `filter` methods to Eloquent collections.
- Swapped method order on `Route::controller` to make it more consistent with other similar methods.
- Added route names to resource routes.
- Added support for nested resources.
- Changed resource route parameter names to match resource name, allowing for use with `Route::model`.