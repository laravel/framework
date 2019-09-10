# Release Notes for 6.x

## [Unreleased](https://github.com/laravel/framework/compare/v6.0.2...6.x)

### Refactoring
- Converted `call_user_func` where appropriate to native calls ([#29932](https://github.com/laravel/framework/pull/29932))
- Changed imports to Alpha ordering ([#29933](https://github.com/laravel/framework/pull/29933))


## [v6.0.2 (2019-09-10)](https://github.com/laravel/framework/compare/v6.0.1...v6.0.2)

### Changed
- Used `Application::normalizeCachePath()` method to define cache path`s ([#29890](https://github.com/laravel/framework/pull/29890), [ac9dbf6](https://github.com/laravel/framework/commit/ac9dbf6beaded2ad86f5595958c75e3c4b1147ae))
- Wrapped `MySQL` default values in parentheses ([#29878](https://github.com/laravel/framework/pull/29878))

### Fixed
- Prevent `event auto discovery` from crashing when trying to instantiate files without php classes ([#29895](https://github.com/laravel/framework/pull/29895))
- Fix resolving class command via container ([#29869](https://github.com/laravel/framework/pull/29869))


## [v6.0.1 (2019-09-06)](https://github.com/laravel/framework/compare/v6.0.0...v6.0.1)

### Fixed
- Fixed `Schedule::runInBackground()` not fired on Windows ([#29826](https://github.com/laravel/framework/pull/29826))

### Changed
- Throw `Symfony\Component\Routing\Exception\RouteNotFoundException` instead of `InvalidArgumentException` in `UrlGenerator::route()` ([#29861](https://github.com/laravel/framework/pull/29861))

### Reverted
- Reverted: [`Extract registered event and login to registered method`](https://github.com/laravel/framework/pull/27807) ([#29875](https://github.com/laravel/framework/pull/29875))


## [v6.0.0 (2019-09-03)](https://github.com/laravel/framework/compare/5.8...v6.0.0)

Check the upgrade guide in the [Official Laravel Documentation](https://laravel.com/docs/6.0/upgrade).
