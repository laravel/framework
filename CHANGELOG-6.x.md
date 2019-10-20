# Release Notes for 6.x

## [Unreleased](https://github.com/laravel/framework/compare/v6.3.0...6.x)

### Added
- Added `missing()` method to `Request` class ([#30320](https://github.com/laravel/framework/pull/30320))

### TODO:
- Improvements on subqueries ([#30307](https://github.com/laravel/framework/pull/30307), [3f3b621](https://github.com/laravel/framework/commit/3f3b6214cc3353156a490d88fc8f0c148da400d5))
- Use exit code [1] when migration table not found ([#30321](https://github.com/laravel/framework/pull/30321))


## [v6.3.0 (2019-10-15)](https://github.com/laravel/framework/compare/v6.2.0...v6.3.0)

### Added
- Added ability to override `setUserPassword` on password reset ([#30218](https://github.com/laravel/framework/pull/30218))
- Added firing `deleting` \ `deleted` events in `MorphPivot` ([#30229](https://github.com/laravel/framework/pull/30229))
- Added locking mechanism for the array cache driver ([#30253](https://github.com/laravel/framework/pull/30253))
- Added `dropAllViews` functionality to the SQL Server builder ([#30222](https://github.com/laravel/framework/pull/30222))

### Optimization
- Optimize eager loading memory handling ([#30248](https://github.com/laravel/framework/pull/30248))

### Fixed
- Fixed extra `?` for empty query string in `RouteUrlGenerator::getRouteQueryString()` ([#30280](https://github.com/laravel/framework/pull/30280))

### Changed
- Updated list of URI schemes for `Url` validator ([#30220](https://github.com/laravel/framework/pull/30220))
- Added schema name when dropping all FKs in SQL Server ([#30221](https://github.com/laravel/framework/pull/30221))
- Used contracts in `RequirePassword` middleware ([#30215](https://github.com/laravel/framework/pull/30215))
- Added ability to return array in `receivesBroadcastNotificationsOn` if `channelName` is array ([#30242](https://github.com/laravel/framework/pull/30242), [2faadcd](https://github.com/laravel/framework/commit/2faadcd288cdc86cf7a1a3644e68e5e0ce641a8b))


## [v6.2.0 (2019-10-08)](https://github.com/laravel/framework/compare/v6.1.0...v6.2.0)

### Added
- Added support for callable objects in `Container::call()` ([#30156](https://github.com/laravel/framework/pull/30156))
- Add multipolygonz type for postgreSQL ([#30173](https://github.com/laravel/framework/pull/30173))
- Add "unauthenticated" method in auth middleware ([#30177](https://github.com/laravel/framework/pull/30177))
- Add partialMock shorthand ([#30202](https://github.com/laravel/framework/pull/30202))
- Allow Storage::put to accept a Psr StreamInterface ([#30179](https://github.com/laravel/framework/pull/30179))
- Implement new password rule and password confirmation ([#30214](https://github.com/laravel/framework/pull/30214))

### Changed
- Remove unnecessary param passed to updatePackageArray method ([#30155](https://github.com/laravel/framework/pull/30155))
- Add optional connection name to DatabaseUserProvider ([#30154](https://github.com/laravel/framework/pull/30154))
- Remove brackets arround URL php artisan serve ([#30168](https://github.com/laravel/framework/pull/30168))
- Apply limit to database rather than collection ([#30148](https://github.com/laravel/framework/pull/30148))
- Allow to use scoped macro in nested queries ([#30127](https://github.com/laravel/framework/pull/30127))
- Added array to json conversion for sqlite ([#30133](https://github.com/laravel/framework/pull/30133))
- Use the `policies()` method instead of the property policies ([#30189](https://github.com/laravel/framework/pull/30189))
- Split hasValidSignature method ([#30208](https://github.com/laravel/framework/pull/30208))

### Fixed
- `validateDimensions()` handle `image/svg` MIME ([#30204](https://github.com/laravel/framework/pull/30204))


## [v6.1.0 (2019-10-01)](https://github.com/laravel/framework/compare/v6.0.4...v6.1.0)

### Added
- Added `Illuminate\Support\LazyCollection::eager()` method ([#29832](https://github.com/laravel/framework/pull/29832))
- Added `forgetChannel()` and `getChannels()` methods to `Illuminate\Log\LogManager` ([#30132](https://github.com/laravel/framework/pull/30132), [a52a0dd](https://github.com/laravel/framework/commit/a52a0dd239262f31edfaefe9a99213cccefc2f36))
- Added `Illuminate\Foundation\Testing\TestResponse::assertNoContent()` method ([#30125](https://github.com/laravel/framework/pull/30125))
- Added `InteractsWithQueue` to `SendQueueNotifications` ([#30140](https://github.com/laravel/framework/pull/30140))
- Added `SendQueueNotifications::retryUntil()` method ([#30141](https://github.com/laravel/framework/pull/30141))
- Added methods for sending cookies with test requests ([#30101](https://github.com/laravel/framework/pull/30101))
- Added support of job middleware for queued notifications ([#30070](https://github.com/laravel/framework/pull/30070))

### Fixed
- Fixed migration class duplicate check in `make:migration` command ([#30095](https://github.com/laravel/framework/pull/30095))
- Fixed monolog v2 handler preparation ([#30123](https://github.com/laravel/framework/pull/30123))
- Fixed return of callback value for DurationLimiter ([#30143](https://github.com/laravel/framework/pull/30143))

### Changed
- Added runtime information output for seeders ([#30086](https://github.com/laravel/framework/pull/30086))
- Added strict parameter to `Illuminate\Foundation\Testing\TestResponse::assertJsonPath()` ([#30142](https://github.com/laravel/framework/pull/30142))
- Added `deletedAtColumn` optional parameter to `Foundation\Testing\Concerns\InteractsWithDatabase::assertSoftDeleted()` ([#30111](https://github.com/laravel/framework/pull/30111))

### Improved
- Improved `AuthServiceProvider::registerEventRebindHandler()` in case if guard is not initialized ([#30105](https://github.com/laravel/framework/pull/30105))


## [v6.0.4 (2019-09-24)](https://github.com/laravel/framework/compare/v6.0.3...v6.0.4)

### Added
- Added `TestResponse::assertJsonPath()` method ([#29957](https://github.com/laravel/framework/pull/29957))
- Added `hasMacro` \ `getGlobalMacro` \ `hasGlobalMacro` methods to `Eloquent Builder` ([#30008](https://github.com/laravel/framework/pull/30008))
- Added `Illuminate\Database\Eloquent\Relations\BelongsToMany::getPivotColumns()` method ([#30049](https://github.com/laravel/framework/pull/30049)) 
- Added `ScheduledTaskFinished` \ `ScheduledTaskStarting` events to signal when scheduled task runs ([#29888](https://github.com/laravel/framework/pull/29888))
- Allowing adding command arguments and options with `InputArgument` \ `InputOption` objects ([#29987](https://github.com/laravel/framework/pull/29987))

### Fixed
- Fixed `__()` with `null` parameter ([#29967](https://github.com/laravel/framework/pull/29967)) 
- Fixed modifying `updated_at` column on custom pivot model ([#29970](https://github.com/laravel/framework/pull/29970))
- Fixed `Illuminate\Redis\Limiters\ConcurrencyLimiter` ([#30005](https://github.com/laravel/framework/pull/30005))
- Fixed `VerifyCsrfToken` middleware when response object instance of `Responsable` interface ([#29972](https://github.com/laravel/framework/pull/29972))
- Fixed Postgresql column creation without optional precision ([#29873](https://github.com/laravel/framework/pull/29873))
- Fixed migrations orders with multiple path with certain filenames ([#29996](https://github.com/laravel/framework/pull/29996))
- Fixed adding `NotFoundHttpException` to "allowed" exceptions in tests ([#29975](https://github.com/laravel/framework/pull/29975))

### Changed
- Make it possible to disable encryption via `0`/`false` ([#29985](https://github.com/laravel/framework/pull/29985))
- Allowed a symfony file instance in validate dimensions ([#30009](https://github.com/laravel/framework/pull/30009))
- Create storage fakes with custom configuration ([#29999](https://github.com/laravel/framework/pull/29999))
- Set locale in `PendingMail` only if locale present conditionally ([dd1e0a6](https://github.com/laravel/framework/commit/dd1e0a604713ddae21e6a893e4f605a6777300e8))
- Improved sorting of imports alphabetically on class generation from stub ([#29951](https://github.com/laravel/framework/pull/29951))

### Refactoring
- Changed imports to Alpha ordering in stubs ([#29954](https://github.com/laravel/framework/pull/29954), [#29958](https://github.com/laravel/framework/pull/29958))
- Used value helper where possible ([#29959](https://github.com/laravel/framework/pull/29959))
- Improved readability in `auth.throttle` translation ([#30011](https://github.com/laravel/framework/pull/30011), [#30017](https://github.com/laravel/framework/pull/30017))


## [v6.0.3 (2019-09-10)](https://github.com/laravel/framework/compare/v6.0.2...v6.0.3)

### Reverted
- Reverted [Wrapped `MySQL` default values in parentheses](https://github.com/laravel/framework/pull/29878) ([#29943](https://github.com/laravel/framework/pull/29943))

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

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/6.0/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/6.x/releases).
