# Release Notes for 5.4.x

## [Unreleased]

### Added
- Added Support for PhpRedis ([#15160](https://github.com/laravel/framework/pull/15160))
- Added `retry()` helper ([e3bd359](https://github.com/laravel/framework/commit/e3bd359d52cee0ba8db9673e45a8221c1c1d95d6), [52e9381](https://github.com/laravel/framework/commit/52e9381d3d64631f2842c1d86fee2aa64a6c73ac))
- Allow rolling back to a given transaction save-point ([#15876](https://github.com/laravel/framework/pull/15876))
- Added `Container::factory()` method to the Container contract ([#15430](https://github.com/laravel/framework/pull/15430))
- Added `RedisStore::add()` to store an item in the cache if the key doesn't exist ([#15877](https://github.com/laravel/framework/pull/15877))
- Replaced Symfony's translator ([#15563](https://github.com/laravel/framework/pull/15563))
- Added `$values` parameter to `Builder::firstOrNew()` ([#15567](https://github.com/laravel/framework/pull/15567))
- Allow dependency injection on database seeders `run()` method ([#15959](https://github.com/laravel/framework/pull/15959))
- Added model binding in broadcasting channel definitions ([#16120](https://github.com/laravel/framework/pull/16120), [515d97c](https://github.com/laravel/framework/commit/515d97c1f3ad4797876979d450304684012142d6))
- Added support for object-based events for native Eloquent events ([e7a724d](https://github.com/laravel/framework/commit/e7a724d3895f2b24b98c0cafb1650f2193351d83))
- Added `base64:` prefix support to `env()` helper ([#16186](https://github.com/laravel/framework/pull/16186))
- Added `cache:forget` command ([#16201](https://github.com/laravel/framework/pull/16201), [7644977](https://github.com/laravel/framework/commit/76449777741fa1d7669028973958a7e4a5e64f71))

### Changed
- Consider interfaces and extended classes in `Gate::resolvePolicyCallback()` ([#15757](https://github.com/laravel/framework/pull/15757))
- Added `LogServiceProvider` to defer loading of logging code ([#15451](https://github.com/laravel/framework/pull/15451), [6550153](https://github.com/laravel/framework/commit/6550153162b4d54d03d37dd9adfd0c95ca0383a9), [#15794](https://github.com/laravel/framework/pull/15794))
- Support wildcards in `MessageBag::first()` ([#15217](https://github.com/laravel/framework/pull/15217))
- The `Log` facade now uses `LoggerInterface` instead of the log writer ([#15855](https://github.com/laravel/framework/pull/15855))
- `Cache::flush()` now returns boolean ([#15831](https://github.com/laravel/framework/pull/15831), [057492d](https://github.com/laravel/framework/commit/057492d31c569e96a3ba2f99722112a9762c6071))
- Don't rollback to save-points on deadlock (nested transaction) ([#15932](https://github.com/laravel/framework/pull/15932))
- Return a database collection from `HasOneOrMany::createMany()` ([#15944](https://github.com/laravel/framework/pull/15944))
- Refactored Blade `@parent` compilation ([#16033](https://github.com/laravel/framework/pull/16033), [16f72a5](https://github.com/laravel/framework/commit/16f72a5a580b593ac804bc0b2fdcc6eb278e55b2))
- Removed files hydration in `Validator` ([#16017](https://github.com/laravel/framework/pull/16017))
- Only detach all associations if no parameter is passed to `BelongsToMany::detach()` ([#16144](https://github.com/laravel/framework/pull/16144))
- Improve `Connection::selectOne()` performance by switching to `array_shift()` ([#16188](https://github.com/laravel/framework/pull/16188))
- Switched from file to cache based Schedule overlap locking ([#16196](https://github.com/laravel/framework/pull/16196), [5973f6c](https://github.com/laravel/framework/commit/5973f6c54ccd0d99e15f055c5a16b19b8c45db91))
