# Release Notes for 7.x

## [Unreleased](https://github.com/laravel/framework/compare/v7.0.7...7.x)


## [v7.0.7 (2020-03-07)](https://github.com/laravel/framework/compare/v7.0.6...v7.0.7)

### Fixed
- Fixed type hint for `Request::get()` method ([#31826](https://github.com/laravel/framework/pull/31826))
- Add missing public methods to `Illuminate\Routing\RouteCollectionInterface` ([e4f477c](https://github.com/laravel/framework/commit/e4f477c42d3e24f6cdf44a45801c0db476ad2b91))


## [v7.0.6 (2020-03-06)](https://github.com/laravel/framework/compare/v7.0.5...v7.0.6)

### Added
- Added queue suffix for SQS driver ([#31784](https://github.com/laravel/framework/pull/31784))

### Fixed
- Fixed model binding when route cached ([af80685](https://github.com/laravel/framework/commit/af806851931700e8dd8de0ac0333efd853b19f3d))
- Fixed incompatible `Factory` contract for `MailFacade` ([#31809](https://github.com/laravel/framework/pull/31809))

### Changed
- Fixed typehints in `Illuminate\Foundation\Application::handle()` ([#31806](https://github.com/laravel/framework/pull/31806))


## [v7.0.5 (2020-03-06)](https://github.com/laravel/framework/compare/v7.0.4...v7.0.5)

### Fixed
- Fixed `Illuminate\Http\Client\PendingRequest::withCookies()` method ([36d783c](https://github.com/laravel/framework/commit/36d783ce8dbd8736e694ff60ae66e542c62411c3))
- Catch Symfony `MethodNotAllowedException` exception in `CompiledRouteCollection::match()` method ([#31762](https://github.com/laravel/framework/pull/31762))
- Fixed a bug with slash prefix in the route ([#31760](https://github.com/laravel/framework/pull/31760))
- Fixed root URI not showing in the `route:list` ([#31771](https://github.com/laravel/framework/pull/31771))
- Fixed model restoring right after soft deleting it ([#31719](https://github.com/laravel/framework/pull/31719))

### Changed
- Throw exception for duplicate route names in `Illuminate\Routing\AbstractRouteCollection::addToSymfonyRoutesCollection()` method ([#31755](https://github.com/laravel/framework/pull/31755))
- Changed `Illuminate\Support\Str::slug()` method ([e4f22d8](https://github.com/laravel/framework/commit/e4f22d855b429e4141885d542438c859f84bfe49))
- Check if an array lock exists before releasing it in `Illuminate\Cache\ArrayLock::release()` ([#31795](https://github.com/laravel/framework/pull/31795))
- Revert disabling expired views checks ([#31798](https://github.com/laravel/framework/pull/31798))


## [v7.0.4 (2020-03-05)](https://github.com/laravel/framework/compare/v7.0.3...v7.0.4)

### Changed
- Changed of route prefix parameter parsing ([b38e179](https://github.com/laravel/framework/commit/b38e179642d6a76a7713ced1fddde841900ac3ad))


## [v7.0.3 (2020-03-04)](https://github.com/laravel/framework/compare/v7.0.2...v7.0.3)

### Fixed
- Fixed route caching attempt in `Illuminate\Routing\CompiledRouteCollection::newRoute()` ([90b0167](https://github.com/laravel/framework/commit/90b0167d97e61eb06fce9cfc58527f4e09cd2a5e))
- Catch Symfony exception in `CompiledRouteCollection::match()` method ([#31738](https://github.com/laravel/framework/pull/31738))
- Fixed Eloquent model casting ([2b395cd](https://github.com/laravel/framework/commit/2b395cd1f2fe95b67edf97684f09b7c5c4a55152))
- Fixed `UrlGenerator` constructor ([#31740](https://github.com/laravel/framework/pull/31740))

### Changed
- Added message to `Illuminate\Http\Client\RequestException` ([#31720](https://github.com/laravel/framework/pull/31720))


## [v7.0.2 (2020-03-04)](https://github.com/laravel/framework/compare/v7.0.1...v7.0.2)

### Fixed
- Fixed `ascii()` \ `isAscii()` \ `slug()` methods on the `Str` class with null value in the methods ([#31717](https://github.com/laravel/framework/pull/31717))
- Fixed `trim` of the prefix in the `CompiledRouteCollection::newRoute()` ([ce0355c](https://github.com/laravel/framework/commit/ce0355c72bf4defb93ae80c7bf7812bd6532031a), [b842c65](https://github.com/laravel/framework/commit/b842c65ecfe1ea7839d61a46b177b6b5887fd4d2))

### Changed
- remove comments before compiling components in the `BladeCompiler` ([2964d2d](https://github.com/laravel/framework/commit/2964d2dfd3cc50f7a709effee0af671c86587915))


## [v7.0.1 (2020-03-03)](https://github.com/laravel/framework/compare/v7.0.0...v7.0.1)

### Fixed
- Fixed `Illuminate\View\Component::withAttributes()` method ([c81ffad](https://github.com/laravel/framework/commit/c81ffad7ef8d74ebd109f399abbdc5c7ebabff88))


## [v7.0.0 (2020-03-03)](https://github.com/laravel/framework/compare/v6.18.0...v7.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/7.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/7.x/releases).
