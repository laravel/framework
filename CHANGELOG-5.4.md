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

### Changed
- Consider interfaces and extended classes in `Gate::resolvePolicyCallback()` ([#15757](https://github.com/laravel/framework/pull/15757))
- Added `LogServiceProvider` to defer loading of logging code ([#15451](https://github.com/laravel/framework/pull/15451), [6550153](https://github.com/laravel/framework/commit/6550153162b4d54d03d37dd9adfd0c95ca0383a9), [#15794](https://github.com/laravel/framework/pull/15794))
- Support wildcards in `MessageBag::first()` ([#15217](https://github.com/laravel/framework/pull/15217))
- The `Log` facade now uses `LoggerInterface` instead of the log writer ([#15855](https://github.com/laravel/framework/pull/15855))
- `Cache::flush()` now returns boolean ([#15831](https://github.com/laravel/framework/pull/15831), [057492d](https://github.com/laravel/framework/commit/057492d31c569e96a3ba2f99722112a9762c6071))
