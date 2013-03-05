# Laravel 4 Beta Change Log

## Beta 4

- Added `Model::creating(Closure)` and `Model::updating(Closure)` methods for hooking into Eloquent save events.
- Added `Event::queue` and `Event::flush`.
- Added a `Str` class in support component. Adopted Patchwork UTF-8 to provide solid UTF-8 handling for the framework.
- Allow Eloquent attributes to be accessed by camelCase in addition to snake_case.
- Added `App::environment` method.
- Added `resolving` method to IoC container for catching resolutions.
- Added `shouldReceive` and `swap` methods to facade.
- Added `bound` method to the IoC container.
- Utilize `checkdate` in the `date` validation rule to make sure the date is actually valid.
- Allow controller actions in base classes to be routed via `Route::controller`.
- Encode queue payloads as JSON instead of serializing, to make the Queue place nicely with other languages.
- Added `Model::created(Closure)` and `Model::updated(Closure)` methods for hooking into Eloquent post-save events.
- Added `Model::boot` static method for a one time "booting" method for models.
- Passing `null` into a `where` call will not short-cut into `whereNull`.
- Changed Blade `{{ }}` to not escape. Made the triple braces escape.
- Added `DB::getName` to get the configured name of the connection.
- Made Eloquent casing agnostic. Will use whatever casing the properties use. Added `snakeRelations` property to model (default `true`) to control casing on relationships when using `toArray`.
- Added `restart identity` to Postgres `truncate` SQL.
- Added `Log::listen` callback and `illuminate.log` event which can be hooked into for custom logging handling.

## Beta 3

- Fixed a few things in the ArrayStore session driver.
- Improve reasons in Password Broker.

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
- Added `extendImplicit` method to `Validator`.
- Added `Password::remind` and `Password::reset` methods.
- Implemented `RemindableInterface` on the default `User` model.
- Added unified queue API component, with drivers for `sync` and `beanstalkd` (Amazon SQS to come).
- Ported `Model->touch` method from L3 Eloquent.
- Added `isEmpty` method to the `Paginator`.
- Added ability to specify `prefix` on a route group.
- Added `setBaseUrl` method to pagination environment.
- Eloquent Model and Collections objects now include JSON_NUMERIC_CHECK by default on `toJson` method.
- Eloquent mutators are now prefixed with `getFooAttribute` and `setFooAttribute` instead of `getFoo` and `setFoo`. This is to avoid conflicts with other get and set methods on the model, and in your own code.
- Added `auth:reminders` Artisan command for generating a migration for the password reminders table.
- Added `App::fatal` method for registering an error listener for PHP fatal errors.
- Added `session:table` Artisan command for generating a migration for the session database table.
- Fix bug when using `first` method on a `belongsToMany` relationship.
- Added SQL and bindings array to database query exceptions.
- Allow manipulation of session using "dot" notation.
- Route regular expression constraints may now be defined globally via `Route::pattern`.
- Auto-increment fields are now unsigned if the database system supports it.
- Changed how database seeding works to give more freedom and allow use of Eloquent, etc.
- Change event dispatcher to use more L3 style conventions instead of passing event objects. Added `until` method.
- Fix bug with Eloquent eager loads with joins.
- Allow method specification on class based View composers.
- Allow method specification on class based Route filters.
- Added new configuration option for specifying session cookie name.
- Escape Blade echos by default. Made `{{{ foo }}}` echo for raw output with no escaping.
- Allow the sending of e-mails with only plain text parts.
