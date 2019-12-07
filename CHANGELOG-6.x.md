# Release Notes for 6.x

## [Unreleased](https://github.com/laravel/framework/compare/v6.6.2...6.x)

### TODO
- Added `ResourceCollection::preserveQueryParameters()` for preserve query parameters on paginated api resources ([#30745](https://github.com/laravel/framework/pull/30745), [e92a708](https://github.com/laravel/framework/commit/e92a70800671187cc30a39e965144101d5db169a))
- Reconnect on connection missing to recover horizon ([#30778](https://github.com/laravel/framework/pull/30778))


## [v6.6.2 (2019-12-05)](https://github.com/laravel/framework/compare/v6.6.1...v6.6.2)

### Added
- Added `Illuminate\Support\Facades\Facade::partialMock()` method ([#30754](https://github.com/laravel/framework/pull/30754))
- Added of support `retryAfter` option on queued listeners ([#30743](https://github.com/laravel/framework/pull/30743))

### Fixed
- Fixed zero parameter for routes ([#30768](https://github.com/laravel/framework/pull/30768))

### Changed
- Changed `getAllViews()` method visibility from `protected` to `public` in all schema builders ([#30757](https://github.com/laravel/framework/pull/30757))


## [v6.6.1 (2019-12-03)](https://github.com/laravel/framework/compare/v6.6.0...v6.6.1)

### Added
- Added `setInput()` and `setOutput()` methods to `Illuminate\Console\Command` ([#30706](https://github.com/laravel/framework/pull/30706))

### Fixed
- Fixed RouteUrlGenerator with empty string for required parameter ([#30714](https://github.com/laravel/framework/pull/30714))

### Changed
- Force usage getting timestamps columns in model ([#30697](https://github.com/laravel/framework/pull/30697))

### Reverted
- Revert [Added `Illuminate\Routing\Router::head()`](https://github.com/laravel/framework/pull/30646) ([#30710](https://github.com/laravel/framework/pull/30710))


## [v6.6.0 (2019-11-26)](https://github.com/laravel/framework/compare/v6.5.2...v6.6.0)

### Added
- Allowed explicit Model definitions in database rules ([#30653](https://github.com/laravel/framework/pull/30653), [9beceac](https://github.com/laravel/framework/commit/9beceacb1a1b8ba37cd0f775cb2fb81e21ba4c31))
- Allowed `ResponseFactory::view()` to return first view ([#30651](https://github.com/laravel/framework/pull/30651))
- Added `Foundation\Testing\Concerns\InteractsWithDatabase::assertDeleted()` method ([#30648](https://github.com/laravel/framework/pull/30648))
- Added `Illuminate\Routing\Router::head()` ([#30646](https://github.com/laravel/framework/pull/30646))
- Added `wherePivotNotIn()` and `orWherePivotNotIn()` methods to `BelongsToMany` ([#30671](https://github.com/laravel/framework/pull/30671))
- Added options in `SqlServerConnector` to encrypt data with Azure Key vault ([#30636](https://github.com/laravel/framework/pull/30636))

### Fixed
- Fixed errors in `Illuminate\Http\Testing\FileFactory::create()` ([#30632](https://github.com/laravel/framework/pull/30632))
- Fixed routing bug that causes missing parameters to be ignored ([#30659](https://github.com/laravel/framework/pull/30659))

### Changed
- Updated error message in `PhpRedisConnector::createClient()` if redis extension is not loaded ([#30673](https://github.com/laravel/framework/pull/30673), [184a0f4](https://github.com/laravel/framework/commit/184a0f45bc9959ebadf36a7dd6966c2bfcb96191))
- Updated `windows_os()` helper to use PHP_OS_FAMILY ([#30660](https://github.com/laravel/framework/pull/30660))


## [v6.5.2 (2019-11-19)](https://github.com/laravel/framework/compare/v6.5.1...v6.5.2)

### Added
- Allowed model serialization on jobs for typed properties ([#30604](https://github.com/laravel/framework/pull/30604), [#30605](https://github.com/laravel/framework/pull/30605), [920c364](https://github.com/laravel/framework/commit/920c3640269b7c1dd0f26e5b6f765ca9b7f99366))
- Allowed fallback when facade root accessor has previously been resolved ([#30616](https://github.com/laravel/framework/pull/30616))
- Added support for separation between `geometry` and `geography` types for `Postgres` ([#30545](https://github.com/laravel/framework/pull/30545))
- Added `createWithContent()` method to `Illuminate\Http\Testing\File` and `Illuminate\Http\Testing\FileFactory` ([2cc6fa3](https://github.com/laravel/framework/commit/2cc6fa33732118cc71c74209b02382b989689b63), [181db51](https://github.com/laravel/framework/commit/181db51595d546cbd24b3fac0cb276255e147286))

### Refactoring
- Improved `PostgresGrammar::formatPostGisType()` method readability ([#30593](https://github.com/laravel/framework/pull/30593))

### Changed
- Added `symfony/debug` dependency to `illuminate/pipeline` ([#30611](https://github.com/laravel/framework/pull/30611))
- Override `BelongsToMany::cursor()` to hydrate pivot relations ([#30580](https://github.com/laravel/framework/pull/30580))
- Ignore Redis prefix when verifying channel access in RedisBroadcaster ([#30597](https://github.com/laravel/framework/pull/30597), [d77ce36](https://github.com/laravel/framework/commit/d77ce36917510d5a6800dd4116a4e18b7bf720b3))


## [v6.5.1 (2019-11-12)](https://github.com/laravel/framework/compare/v6.5.0...v6.5.1)

### Added
- Added `includeUnless` Blade directive ([#30538](https://github.com/laravel/framework/pull/30538))

### Fixed
- Fixed default value for $count in `PhpRedisConnection::spop()` method ([#30546](https://github.com/laravel/framework/pull/30546))
- Fixed breaking compatibility with multi-schema postgres ([#30562](https://github.com/laravel/framework/pull/30562), [6460d2b](https://github.com/laravel/framework/commit/6460d2b1bd89f470a76f5c2c3bddd390fe430e0f))
- Fixed `Model::isDirty()` with `collection` / `object` casts ([#30565](https://github.com/laravel/framework/pull/30565))
- Fixed `bcc` in `MailgunTransport::send()` ([#30569](https://github.com/laravel/framework/pull/30569))

### Changed
- Remove `illuminate/support` dependency from `Container` package  ([#30518](https://github.com/laravel/framework/pull/30518), [#30528](https://github.com/laravel/framework/pull/30528))


## [v6.5.0 (2019-11-05)](https://github.com/laravel/framework/compare/v6.4.1...v6.5.0)

### Added
- Added `LazyCollection::remember()` method ([#30443](https://github.com/laravel/framework/pull/30443))
- Added `Str::afterLast()` and `Str::beforeLast()` methods ([#30507](https://github.com/laravel/framework/pull/30507))
- Added `existsOr()` and `doesntExistOr()` methods to the query builder ([#30495](https://github.com/laravel/framework/pull/30495))
- Added `unless` condition to Blade custom `if` directives ([#30492](https://github.com/laravel/framework/pull/30492))

### Changed
- Added reconnect if missing connection when beginning transaction ([#30474](https://github.com/laravel/framework/pull/30474))
- Set Redis cluster prefix with PhpRedis ([#30461](https://github.com/laravel/framework/pull/30461))


## [v6.4.1 (2019-10-29)](https://github.com/laravel/framework/compare/v6.4.0...v6.4.1)

### Added
- Added `ScheduledTaskSkipped` event when a scheduled command was filtered from running ([#30407](https://github.com/laravel/framework/pull/30407))
- Added `Login timeout expired` to `DetectsLostConnections` ([#30362](https://github.com/laravel/framework/pull/30362))
- Added `missing` method to `Illuminate\Filesystem\Filesystem` and `Illuminate\Filesystem\FilesystemAdapter` classes ([#30441](https://github.com/laravel/framework/pull/30441))

### Changed
- Make `vendor:publish` command more informative ([#30408](https://github.com/laravel/framework/pull/30408), [65d040d](https://github.com/laravel/framework/commit/65d040d44f1cef3830748ec59c0056bc2418dca6))
- Accepted underscores URL in the `URL` validator ([#30417](https://github.com/laravel/framework/pull/30417))
- Updated `artisan down` output to be consistent with `artisan up` ([#30422](https://github.com/laravel/framework/pull/30422))
- Changed `!empty` to `isset` for changing redis database ([#30420](https://github.com/laravel/framework/pull/30420))
- Throw an exception when signing route got in parameter keys `signature` ([#30444](https://github.com/laravel/framework/pull/30444), [71af732](https://github.com/laravel/framework/commit/71af732b6b00ab148cd23b95aca4e05bcb86c242))

### Fixed
- Fixed of retrieving view config in `ServiceProvider::loadViewsFrom()` for Lumen ([#30404](https://github.com/laravel/framework/pull/30404))


## [v6.4.0 (2019-10-23)](https://github.com/laravel/framework/compare/v6.3.0...v6.4.0)

### Added
- Added `missing()` method to `Request` class ([#30320](https://github.com/laravel/framework/pull/30320))
- Added `Pipeline::pipes()` method ([#30346](https://github.com/laravel/framework/pull/30346))
- Added `TestResponse::assertCreated()` method ([#30368](https://github.com/laravel/framework/pull/30368)) 

### Changed
- Added `connection is no longer usable` to `DetectsLostConnections` ([#30362](https://github.com/laravel/framework/pull/30362))
- Implemented parse ID on find method for many to many relation ([#30359](https://github.com/laravel/framework/pull/30359))
- Improvements on subqueries ([#30307](https://github.com/laravel/framework/pull/30307), [3f3b621](https://github.com/laravel/framework/commit/3f3b6214cc3353156a490d88fc8f0c148da400d5))
- Pass mail data to theme css in `Markdown::render()` method ([#30376](https://github.com/laravel/framework/pull/30376))
- Handle ajax requests in RequirePassword middleware ([#30390](https://github.com/laravel/framework/pull/30390), [331c354](https://github.com/laravel/framework/commit/331c354e586a5a27a9edc9b9a49d23aa872e4b32))

### Fixed
- Fixed `retry()` with `$times` value less then 1 ([#30356](https://github.com/laravel/framework/pull/30356))
- Fixed `last_modified` option in `SetCacheHeader` ([#30335](https://github.com/laravel/framework/pull/30335))
- Fixed the Filesystem manager's exception on unsupported driver ([#30331](https://github.com/laravel/framework/pull/30331), [#30369](https://github.com/laravel/framework/pull/30369))
- Fixed `shouldQueue()` check for bound event listeners ([#30378](https://github.com/laravel/framework/pull/30378))
- Used exit code `1` when migration table not found ([#30321](https://github.com/laravel/framework/pull/30321))
- Alleviate breaking change introduced by password confirm feature ([#30389](https://github.com/laravel/framework/pull/30389))

### Security:
- Password Reset Security fix ([23041e9](https://github.com/laravel/framework/commit/23041e99833630d93cc7672bd7087eaa350c3a59), [a934160](https://github.com/laravel/framework/commit/a9341609705e2f8febcd356cdfa33391ec6538c7))


## [v6.3.0 (2019-10-15)](https://github.com/laravel/framework/compare/v6.2.0...v6.3.0)

### Added
- Added ability to override `setUserPassword` on password reset ([#30218](https://github.com/laravel/framework/pull/30218))
- Added firing `deleting` / `deleted` events in `MorphPivot` ([#30229](https://github.com/laravel/framework/pull/30229))
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
- Added `hasMacro` / `getGlobalMacro` / `hasGlobalMacro` methods to `Eloquent Builder` ([#30008](https://github.com/laravel/framework/pull/30008))
- Added `Illuminate\Database\Eloquent\Relations\BelongsToMany::getPivotColumns()` method ([#30049](https://github.com/laravel/framework/pull/30049)) 
- Added `ScheduledTaskFinished` / `ScheduledTaskStarting` events to signal when scheduled task runs ([#29888](https://github.com/laravel/framework/pull/29888))
- Allowing adding command arguments and options with `InputArgument` / `InputOption` objects ([#29987](https://github.com/laravel/framework/pull/29987))

### Fixed
- Fixed `__()` with `null` parameter ([#29967](https://github.com/laravel/framework/pull/29967)) 
- Fixed modifying `updated_at` column on custom pivot model ([#29970](https://github.com/laravel/framework/pull/29970))
- Fixed `Illuminate\Redis\Limiters\ConcurrencyLimiter` ([#30005](https://github.com/laravel/framework/pull/30005))
- Fixed `VerifyCsrfToken` middleware when response object instance of `Responsable` interface ([#29972](https://github.com/laravel/framework/pull/29972))
- Fixed Postgresql column creation without optional precision ([#29873](https://github.com/laravel/framework/pull/29873))
- Fixed migrations orders with multiple path with certain filenames ([#29996](https://github.com/laravel/framework/pull/29996))
- Fixed adding `NotFoundHttpException` to "allowed" exceptions in tests ([#29975](https://github.com/laravel/framework/pull/29975))

### Changed
- Make it possible to disable encryption via `0` / `false` ([#29985](https://github.com/laravel/framework/pull/29985))
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
