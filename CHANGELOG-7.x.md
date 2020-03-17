# Release Notes for 7.x

## [Unreleased](https://github.com/laravel/framework/compare/v7.2.0...7.x)


## [v7.1.4 (2020-03-17)](https://github.com/laravel/framework/compare/v7.1.3...v7.2.0)

### Added
- Added `Illuminate\Testing\PendingCommand::expectsConfirmation()` ([#31965](https://github.com/laravel/framework/pull/31965))
- Allowed configuring the timeout for the smtp mail driver ([#31973](https://github.com/laravel/framework/pull/31973))
- Added `Http client` query string support ([#31996](https://github.com/laravel/framework/pull/31996))

### Fixed
- Added missing `ramsey/uuid` dependency to `Illuminate/Queue/composer.json` ([#31988](https://github.com/laravel/framework/pull/31988))
- Fixed output of component attributes in View ([#31994](https://github.com/laravel/framework/pull/31994))

### Changed
- Changed `cookie` helper signature to match `CookieFactory` ([#31974](https://github.com/laravel/framework/pull/31974))
- Publish the form request stub used by RequestMakeCommand ([#31962](https://github.com/laravel/framework/pull/31962))
- Handle prefix update on route level prefix ([449c80](https://github.com/laravel/framework/commit/449c8056cc0f13e7e20428700045339bae6bdca2))
- Ensure SqsQueue queues are only suffixed once ([#31925](https://github.com/laravel/framework/pull/31925))
- Added space after component closing tag for the View ([#32005](https://github.com/laravel/framework/pull/32005))


## [v7.1.3 (2020-03-14)](https://github.com/laravel/framework/compare/v7.1.2...v7.1.3)

### Fixed
- Unset `pivotParent` on `Pivot::unsetRelations()` ([#31956](https://github.com/laravel/framework/pull/31956))

### Changed
- Escape merged attributes by default in `Illuminate\View\ComponentAttributeBag` ([83c8e6e](https://github.com/laravel/framework/commit/83c8e6e6b575d0029ea164ba4b44f4c4895dbb3d))
 

## [v7.1.2 (2020-03-13)](https://github.com/laravel/framework/compare/v7.1.1...v7.1.2)

### Fixed
- Fixed null value injected from container in routes ([#31867](https://github.com/laravel/framework/pull/31867), [c666c42](https://github.com/laravel/framework/commit/c666c424e8a60539a8fbd7cb5a3474785d9db22a))

### Changed 
- Escape attributes automatically in some situations in `Illuminate\View\Compilers\ComponentTagCompiler` ([#31945](https://github.com/laravel/framework/pull/31945))


## [v7.1.1 (2020-03-12)](https://github.com/laravel/framework/compare/v7.1.0...v7.1.1)

### Added
- Added `dispatchToQueue()` to `BusFake` ([#31935](https://github.com/laravel/framework/pull/31935))
- Support either order of arguments for symmetry with livewire ([8d558670](https://github.com/laravel/framework/commit/8d5586700ad97b92ac622ea72c1fefe52c359265))

### Fixed
- Bring `--daemon` option back to `queue:work` command ([24c1818](https://github.com/laravel/framework/commit/24c18182a82ee24be62d2ac1c6793c237944cda8))
- Fixed ComponentAttributeBag merge behaviour ([#31932](https://github.com/laravel/framework/pull/31932))

### Changed
- Intelligently drop unnamed prefix name routes when caching ([#31917](https://github.com/laravel/framework/pull/31917))
- Closure jobs needs illuminate/queue ([#31933](https://github.com/laravel/framework/pull/31933)) 
- Fixed bad dependency assumptions ([#31894](https://github.com/laravel/framework/pull/31894))
- Have a cache aware interface instead of concrete checks ([#31903](https://github.com/laravel/framework/pull/31903))


## [v7.1.0 (2020-03-10)](https://github.com/laravel/framework/compare/v7.0.8...v7.1.0)

### Added
- Added `Illuminate\Routing\RouteRegistrar::apiResource()` method ([#31857](https://github.com/laravel/framework/pull/31857)) 
- Added optional $table parameter to `ForeignIdColumnDefinition::constrained()` method ([#31853](https://github.com/laravel/framework/pull/31853))
- Added `The connection is broken and recovery is not possible. ...` to `DetectsLostConnections` ([#31539](https://github.com/laravel/framework/pull/31539))

### Fixed
- Fixed phpredis `zadd` and `exists` on cluster ([#31838](https://github.com/laravel/framework/pull/31838))
- Fixed trailing slash in `Illuminate\Routing\CompiledRouteCollection::match()` ([3d58cd9](https://github.com/laravel/framework/commit/3d58cd91d6ec483a43a4c23af9b75ecdd4a358de), [ac6f3a8](https://github.com/laravel/framework/commit/ac6f3a8bd0e94ea1319b6f278ecf7f3f8bada3c2))

### Changed
- Fire `MessageLogged` event after the message has been logged (not before) ([#31843](https://github.com/laravel/framework/pull/31843))
- Avoid using array_merge_recursive in HTTP client ([#31858](https://github.com/laravel/framework/pull/31858))
- Expire the jobs cache keys after 1 day ([#31854](https://github.com/laravel/framework/pull/31854))
- Avoid global app() when compiling components ([#31868](https://github.com/laravel/framework/pull/31868))


## [v7.0.8 (2020-03-08)](https://github.com/laravel/framework/compare/v7.0.7...v7.0.8)

### Added
- Added `Illuminate\Mail\Mailable::when()` method ([#31828](https://github.com/laravel/framework/pull/31828))
- Allowed dynamically adding of routes during caching ([#31829](https://github.com/laravel/framework/pull/31829))

### Fixed
- Match Symfony's `Command::setHidden` declaration ([#31840](https://github.com/laravel/framework/pull/31840))

### Changed
- Update the encryption algorithm to provide deterministic encryption sizes ([#31721](https://github.com/laravel/framework/pull/31721))


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
