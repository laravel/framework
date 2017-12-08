# Release Notes for 5.5.x

## [Unreleased]

### Added
- Added support for Flysystem caching ([#22310](https://github.com/laravel/framework/pull/22310), [0657496](https://github.com/laravel/framework/commit/06574964c677c4205668def84ede22e3ca5eee9c))


## v5.5.24 (2017-12-06)

### Added
- Added `WithFaker` testing trait ([#22280](https://github.com/laravel/framework/pull/22280), [4841089](https://github.com/laravel/framework/commit/4841089645e1772e64b501a4f69478b2ee7f4550), [5c72698](https://github.com/laravel/framework/commit/5c726986ae1ecb926ae9d77e28848bb38d020041))

### Changed
- Made `Relation::$morphMap` public ([e44596f](https://github.com/laravel/framework/commit/e44596f65667962af69fd9cd9e63beb927fecaa1))
- Trim return value of `ValidatesAttributes::shouldBlockPhpUpload()` ([90a8faf](https://github.com/laravel/framework/commit/90a8fafd6b70d8f059e86010de996c5db6da4c37))

### Fixed
- Fixed an issue with queueable notifications ([#22275](https://github.com/laravel/framework/pull/22275))
- Fixed `NullSessionDriver` upstream issues ([#22314](https://github.com/laravel/framework/pull/22314))
- Move `payload` to the end of the insert array of a job ([#22334](https://github.com/laravel/framework/pull/22334))


## v5.5.23 (2017-12-04)

### Added
- Added a `Collection::firstWhere()` method ([#22261](https://github.com/laravel/framework/pull/22261), [#22264](https://github.com/laravel/framework/pull/22264))
- Added several accessors to `BelongsToMany` ([f09ea98](https://github.com/laravel/framework/commit/f09ea98bc814c708215896dad702d715923f3bc3), [cbe8123](https://github.com/laravel/framework/commit/cbe8123a479e81779cd85251eb4a5cf861e93ea3), [3bcf9d1](https://github.com/laravel/framework/commit/3bcf9d1d67ca3f288270e910898c77334322128a))

### Changed
- Pass test value to `Collection::when()` callbacks ([#22224](https://github.com/laravel/framework/pull/22224))
- Support worker sleep time of less than 1s ([#22246](https://github.com/laravel/framework/pull/22246), [#22255](https://github.com/laravel/framework/pull/22255))
- Detect persistent connection resets ([#22277](https://github.com/laravel/framework/pull/22277))
- Support chaining seeders ([#22288](https://github.com/laravel/framework/pull/22288))

### Fixed
- Fixed negative comparison to objects in `Collection::where()` ([#22256](https://github.com/laravel/framework/pull/22256))
- Fixed comparing strings with objects that can be casted to string in `Collection::where()` ([#22295](https://github.com/laravel/framework/pull/22295))
- Fixed integer validation using `distinct:ignore_case` ([#22235](https://github.com/laravel/framework/pull/22235))
- Fixes building nested JSON accessors in `MySqlGrammar` ([#22254](https://github.com/laravel/framework/pull/22254))
- Remove `SELECT` bindings from MySQL delete statements ([#22285](https://github.com/laravel/framework/pull/22285))


## v5.5.22 (2017-11-27)

### Added
- Added `response()` and `download()` methods to file system ([#22089](https://github.com/laravel/framework/pull/22089))
- Added complete temporary table support ([#22110](https://github.com/laravel/framework/pull/22110))
- Added `Mode::newQueryForRestoration()` method ([#22119](https://github.com/laravel/framework/pull/22119))
- Added precision support for date/time columns ([#22122](https://github.com/laravel/framework/pull/22122))
- Added detection for MySQL Galera deadlocks ([#22214](https://github.com/laravel/framework/pull/22214))

### Changed
- Updated depreciated `MailFake::queue()` method signature ([#22072](https://github.com/laravel/framework/pull/22072))
- Use `MEDIUMTEXT` instead of `TEXT` for database cache values (MySQL only) ([#22091](https://github.com/laravel/framework/pull/22091))
- Include the name of the scheduled job in the output email subject ([#22098](https://github.com/laravel/framework/pull/22098))
- Support `Dblib` version config for SQL Server ([#22102](https://github.com/laravel/framework/pull/22102))
- Set `Model::$exists` to `false` when force-deleting a model using `SoftDeletes` ([#22100](https://github.com/laravel/framework/pull/22100))
- Support empty strings in `HasAttributes::fromDateTime()` ([#22108](https://github.com/laravel/framework/pull/22108))
- Return condition from `throw_*` helpers ([#22149](https://github.com/laravel/framework/pull/22149))
- Make `Collection::where()` independent of error reporting ([#22172](https://github.com/laravel/framework/pull/22172))
- Show more meaningful message when json translation file contains errors ([#22165](https://github.com/laravel/framework/pull/22165), [cf29b88](https://github.com/laravel/framework/commit/cf29b884006a4ce4d84a115b531143337a875211))
- Improve `Model::getTable()` performance ([#22222](https://github.com/laravel/framework/pull/22222))
- Use transaction in migrations using SQL Server ([#22187](https://github.com/laravel/framework/pull/22187))

### Fixed
- Fixed `HasManyThrough` relation with custom intermediate and local keys when used in `whereHas()` ([#22071](https://github.com/laravel/framework/pull/22071), [3788cbd](https://github.com/laravel/framework/commit/3788cbd3e606caa4a6e4137f92709c85e52b2cf3))
- Fixed SQL Server handling of `DATETIME` columns ([#22052](https://github.com/laravel/framework/pull/22052))
- Return default value from `old()` when session isn't available ([#22082](https://github.com/laravel/framework/pull/22082))
- Refactor `Arr::flatten()` to prevent performance issue ([#22103](https://github.com/laravel/framework/pull/22103))
- Wrap MySQL JSON keys in double quotes when updating JSON columns ([#22118](https://github.com/laravel/framework/pull/22118))
- Fixed custom URLs with prefix (`root`) for AWS storage ([#22130](https://github.com/laravel/framework/pull/22130))
- Prevent authentication if `password` is the only specified field ([#22167](https://github.com/laravel/framework/pull/22167))

### Removed
- Removed `between` operator from basic where clauses ([#22182](https://github.com/laravel/framework/pull/22182))


## v5.5.21 (2017-11-14)

### Added
- Add support for `MultiSubnetFailover` parameter to SqlServer ([#22022](https://github.com/laravel/framework/pull/22022))
- Support custom URLs for S3 ([#22037](https://github.com/laravel/framework/pull/22037))
- Added `MakesHttpRequests::withMiddleware()` method ([#22060](https://github.com/laravel/framework/pull/22060))

### Changed
- Display controller name in `BadMethodCallException` ([#22005](https://github.com/laravel/framework/pull/22005))
- Unify `Collection::dd()` and `Collection::dump()` output ([#22036](https://github.com/laravel/framework/pull/22036))
- Support event generation from multiple service providers ([#22063](https://github.com/laravel/framework/pull/22063))


## v5.5.20 (2017-11-07)

### Added
- Added `TestResponse::assertJsonMissingExact()` ([#21881](https://github.com/laravel/framework/pull/21881))
- Added `assertValidationErrors()` and `assertJsonCount()` to `TestResponse` ([#21917](https://github.com/laravel/framework/pull/21917))
- Added `allOnQueue()` and `allOnConnection()` for job chaining ([#21765](https://github.com/laravel/framework/pull/21765))
- Support variadic arguments on fluent `Route::middleware()` ([#21930](https://github.com/laravel/framework/pull/21930))
- Added precision to `Blueprint::time()` ([#21936](https://github.com/laravel/framework/pull/21936))
- Added `Router::apiResources()` method ([#21956](https://github.com/laravel/framework/pull/21956))
- Support graceful handling of `SIGTERM` in queue workers ([#21964](https://github.com/laravel/framework/pull/21964))

### Changed
- Added "kin" as an uncountable word ([#21843](https://github.com/laravel/framework/pull/21843))
- Improved geo spatial support ([#21919](https://github.com/laravel/framework/pull/21919))
- Include job name in the `MaxAttemptsExcededException` ([#21941](https://github.com/laravel/framework/pull/21941), [#21943](https://github.com/laravel/framework/pull/21943))
- Support rendering multiple `@verbatim` and `@php` blocks ([#21900](https://github.com/laravel/framework/pull/21900))
- Moved `InteractsWithRedis` to `Illuminate\Foundation\Testing` ([#21967](https://github.com/laravel/framework/pull/21967))
- Don't bind macro when it is not a `Closure` ([#21980](https://github.com/laravel/framework/pull/21980))
- Check for `before()` method on policies classes ([#21989](https://github.com/laravel/framework/pull/21989))
- Detect lost pgbouncer connections ([#21988](https://github.com/laravel/framework/pull/21988))

### Fixed
- Fixed `BroadcastController` namespace issue ([#21844](https://github.com/laravel/framework/pull/21844))
- Fixed eager loading `HasManyThrough` relations with custom intermediate and local key ([#21902](https://github.com/laravel/framework/pull/21902))
- Use table aliases when calling self-referencing `HasManyThrough` relation ([#21883](https://github.com/laravel/framework/pull/21883))
- Fixed Vue component file name in React present ([#21945](https://github.com/laravel/framework/pull/21945))
- Reverted changes to `BadMethodException` in [#20196](https://github.com/laravel/framework/pull/20196) ([#21929](https://github.com/laravel/framework/pull/21929))


## v5.5.19 (2017-10-25)

### Added
- Added `MakesHttpRequests::followingRedirects()` method ([#21771](https://github.com/laravel/framework/pull/21771))
- Added `MakesHttpRequests::from()` method ([#21788](https://github.com/laravel/framework/pull/21788))
- Added `notifyNow()` method to notifiables ([#21795](https://github.com/laravel/framework/pull/21795))
- Added `TestResponse::assertCookieExpired()` method ([#21793](https://github.com/laravel/framework/pull/21793))
- Added `TestResponse::assertCookieMissing()` method ([#21803](https://github.com/laravel/framework/pull/21803))

### Changed
- Allow the distinct validation rule to optionally ignore case ([#21757](https://github.com/laravel/framework/pull/21757))

### Fixed
- Excluding `spatial_ref_sys` table from `migrate:fresh` ([#21778](https://github.com/laravel/framework/pull/21778))
- Fixed issue with `SessionGuard` setting the logged in user after firing the `Authenticated` event ([#21790](https://github.com/laravel/framework/pull/21790))
- Fixed issue with `Model::refresh()` when model has a global scope ([#21815](https://github.com/laravel/framework/pull/21815))
- Fixed scheduling a non-queuable job ([#21820](https://github.com/laravel/framework/pull/21820))


## v5.5.18 (2017-10-19)

### Added
- Made `Redirector` macroable ([#21714](https://github.com/laravel/framework/pull/21714))

### Changed
- Prevent reloading default relationships while lazy eager-loading ([#21710](https://github.com/laravel/framework/pull/21710))
- Don't reload pivot relationship on refresh ([#21713](https://github.com/laravel/framework/pull/21713))
- Unify Vue.js preset ([#21711](https://github.com/laravel/framework/pull/21711), [#21724](https://github.com/laravel/framework/pull/21724))
- Revert multibyte functions in `Str` ([#21722](https://github.com/laravel/framework/pull/21722))
- Remove hardcoded fields in DatabaseUserProvider ([#21749](https://github.com/laravel/framework/pull/21749))

### Fixed
- Don't recreate the SQLite database file in `refreshDatabaseFile()` ([#21720](https://github.com/laravel/framework/pull/21720))


## v5.5.17 (2017-10-17)

### Fixed
- Allow `@json` options to be `0` ([#21692](https://github.com/laravel/framework/pull/21692))
- Fixed "invalid argument" error in `ServiceProvider::loadViewsFrom()` ([#21705](https://github.com/laravel/framework/pull/21705))


## v5.5.16 (2017-10-16)

_No changes._


## v5.5.15 (2017-10-16)

### Added
- Added missing PostgreSQL network address operators ([#21518](https://github.com/laravel/framework/pull/21518))
- Added raw and same-site parameters to `cookie()` helper ([#21551](https://github.com/laravel/framework/pull/21551))
- Added option to create pivot model to `make:model` command ([#21549](https://github.com/laravel/framework/pull/21549))
- Added support for a `failed()` method to mailables and notifications ([#21585](https://github.com/laravel/framework/pull/21585))
- Added `__toString` on `ViewErrorBag` ([#21605](https://github.com/laravel/framework/pull/21605))
- Support higher order call of `keyBy()` ([#21606](https://github.com/laravel/framework/pull/21606))
- Generate `@else*` directives for `Blade::if()` ([#21611](https://github.com/laravel/framework/pull/21611))
- Made `Response` and `JsonResponse` macroable ([#21669](https://github.com/laravel/framework/pull/21669))

### Changed
- Escape HTML characters in `@json` directive ([#21574](https://github.com/laravel/framework/pull/21574))
- Only accept strings in `Session::flash()` ([#21576](https://github.com/laravel/framework/pull/21576))
- Use message from `AuthenticationException` in `Handler::unauthenticated()` ([#21575](https://github.com/laravel/framework/pull/21575))
- Don't use global scope while touching parent timestamp ([#21604](https://github.com/laravel/framework/pull/21604))
- Accept multiple middleware when defining middleware fluently ([#21621](https://github.com/laravel/framework/pull/21621))
- Bind `true` as `1` while preparing an SQL statement ([#21623](https://github.com/laravel/framework/pull/21623))
- Ensure config load order across multiple installations ([#21634](https://github.com/laravel/framework/pull/21634))
- Pass previous exception to `AccessDeniedHttpException` and `HttpException` ([#21645](https://github.com/laravel/framework/pull/21645))
- Unify Bootstrap preset ([#21686](https://github.com/laravel/framework/pull/21686), [#21685](https://github.com/laravel/framework/pull/21685))

### Fixed
- Reset table cell margins in mail theme ([#21647](https://github.com/laravel/framework/pull/21647))
- Check for vendor views for each paths given in `config/view.php` ([#21636](https://github.com/laravel/framework/pull/21636))
- Prevent negative offsets when paginating collection ([#21658](https://github.com/laravel/framework/pull/21658))
- Flush all listeners of custom Eloquent events ([#21688](https://github.com/laravel/framework/pull/21688))


## v5.5.14 (2017-10-03)

### Added
- Allow testing anonymous notifiables ([#21379](https://github.com/laravel/framework/pull/21379))
- Add relation and model attributes on `RelationNotFoundException` ([#21426](https://github.com/laravel/framework/pull/21426))
- Allow passing a callback to `with()` ([#21445](https://github.com/laravel/framework/pull/21445))
- Added `PotentiallyMissing` interface to `MissingValue` class ([be7dccc](https://github.com/laravel/framework/commit/be7dccca9f9249c928108d957fe70e78d370d26e))
- Accept `$queue` name on `Schedule::job()` ([#21473](https://github.com/laravel/framework/pull/21473))
- Added callback and default parameter `whenLoaded()` method ([#21490](https://github.com/laravel/framework/pull/21490))
- Allow marking notifications as unread ([#21497](https://github.com/laravel/framework/pull/21497))
- Added `Collection::mapToDictionary()` method ([#21505](https://github.com/laravel/framework/pull/21505))
- Added `make:exception` command ([#21483](https://github.com/laravel/framework/pull/21483))

### Changed
- Reset RefreshDatabaseState after DatabaseMigrations rolls back ([#21325](https://github.com/laravel/framework/pull/21325))
- Make sure page resolver returns an integer ([#21378](https://github.com/laravel/framework/pull/21378))
- Allow single error messages in `ValidationException::withMessages()` ([#21400](https://github.com/laravel/framework/pull/21400))
- Revert `Collection::sortBy()` behaviour to 5.4 ([#21382](https://github.com/laravel/framework/pull/21382))
- Use fill instead of forceFill while storing pivot attributes ([#21403](https://github.com/laravel/framework/pull/21403))
- Alphabetize `vendor:publish` options ([#21412](https://github.com/laravel/framework/pull/21412))
- Extract `AnonymousResourceCollection` into class to allow serialization ([#21456](https://github.com/laravel/framework/pull/21456))
- Extract some methods from `Resource` into `ConditionallyLoadsAttributes` trait ([28d945e](https://github.com/laravel/framework/commit/28d945e557736598c10c492c2918d5697b70570d), [1f37ccc](https://github.com/laravel/framework/commit/1f37ccc06485410fa0866c012b083a042c4cc73e))

### Fixed
- Ensure user is logged in before expecting user instance ([#21377](https://github.com/laravel/framework/pull/21377))
- Remove leading slash from class while resolving controllers ([#21407](https://github.com/laravel/framework/pull/21407))
- Make sure SQL for virtual columns is added after the unsigned modifier ([#21441](https://github.com/laravel/framework/pull/21441))
- Fixed `Collection::contains()` when the found value is `null` ([#21442](https://github.com/laravel/framework/pull/21442))
- Fixed merge issue in `Relation::morphMap()` ([#21458](https://github.com/laravel/framework/pull/21458))
- Clear count query select bindings in `Relation::getRelationExistenceCountQuery()` ([#21468](https://github.com/laravel/framework/pull/21468))
- Fixed user model type hints policy stub ([#21499](https://github.com/laravel/framework/pull/21499))


## v5.5.13 (2017-09-24)

### Added
- Added optional config option for whoops blacklist ([#21336](https://github.com/laravel/framework/pull/21336), [a83ebc1](https://github.com/laravel/framework/commit/a83ebc15e768fab3955013bf5797fa090ee693d7))
- Added `Collection::pad()` method ([#21342](https://github.com/laravel/framework/pull/21342))

### Changed
- Made `$callback` parameter of `Arr::sort()` optional  ([#21337](https://github.com/laravel/framework/pull/21337))
- Support setting custom `REMOTE_ADDR` in `MakesHttpRequests` ([#21351](https://github.com/laravel/framework/pull/21351))
- Changed visibility of `$output` to public on console events ([#21356](https://github.com/laravel/framework/pull/21356))

### Fixed
- Don't compare remember token in user providers if it's `null` (srsly for real this time, pinky promise) ([#21328](https://github.com/laravel/framework/pull/21328), [#21368](https://github.com/laravel/framework/pull/21368))


## v5.5.12 (2017-09-22)

### Added
- Added "software" as an uncountable word ([#21324](https://github.com/laravel/framework/pull/21324))

### Fixed
- Don't compare remember token if it's `null` ([#21328](https://github.com/laravel/framework/pull/21328))


## v5.5.11 (2017-09-21)

### Fixed
- Fixed bug in `EloquentUserProvider` introduced in [#21320](https://github.com/laravel/framework/pull/21320) ([#21323](https://github.com/laravel/framework/pull/21323))


## v5.5.10 (2017-09-21)

### Added
- Added `Route::respondWithRoute($name)` method ([#21299](https://github.com/laravel/framework/pull/21299), [66c5e46](https://github.com/laravel/framework/commit/66c5e462dbdb9d0c9d23114da3a3dc1b6e9fa0a1))
- Added `$strict` parameter to `TestResponse::assertJson()` ([#21301](https://github.com/laravel/framework/pull/21301))

### Changed
- Added "firmware" as an uncountable word ([#21306](https://github.com/laravel/framework/pull/21306))
- Allow `MorphTo::associate()` accept `null` ([#21318](https://github.com/laravel/framework/pull/21318))
- Changed `__()` signature to match `Translation::trans()` ([10c013c](https://github.com/laravel/framework/commit/10c013c564b7e518640e42e97d9178f9e05ec7d9))

### Fixed
- Add missing `driver` parameter to doctrine connection ([#21297](https://github.com/laravel/framework/pull/21297))

### Security
- Perform constant-time token comparison in `DatabaseUserProvider` ([#21320](https://github.com/laravel/framework/pull/21320))


## v5.5.9 (2017-09-20)

### Changed
- Perform stable sort in `Collection::sortBy()` (2nd attempt) ([#21270](https://github.com/laravel/framework/pull/21270))
- Return empty string if database session key is expired ([#21277](https://github.com/laravel/framework/pull/21277))

### Fixed
- Fixed setting `Model::UPDATED_AT` in `SoftDeletes` ([#21286](https://github.com/laravel/framework/pull/21286))
- Apply custom pivot model attribute casting on arrays ([#21275](https://github.com/laravel/framework/pull/21275))


## v5.5.8 (2017-09-20)

### Fixed
- Fixed issue with routes sorting ([#21261](https://github.com/laravel/framework/pull/21261))


## v5.5.7 (2017-09-19)

### Fixed
- Fix `CacheClearCommand` binding ([#21256](https://github.com/laravel/framework/pull/21256))


## v5.5.6 (2017-09-19)

### Changed
- Clear real-time facades when running `cache:clear` ([#21250](https://github.com/laravel/framework/pull/21250), [1856601](https://github.com/laravel/framework/commit/185660178ad213140411ca27550cdaf44c650002))

### Fixed
- Reverted stable sort support in `Collection::sortBy()` ([#21255](https://github.com/laravel/framework/pull/21255))


## v5.5.5 (2017-09-19)

### Added
- Added `:input` placeholder in validation error messages ([#21175](https://github.com/laravel/framework/pull/21175))
- Added `@includeFirst` Blade directive ([#21172](https://github.com/laravel/framework/pull/21172))
- Allow setting column styles for tables in Artisan commands ([#21169](https://github.com/laravel/framework/pull/21169))
- Added `ValidatesRequests::extractInputFromRules()` method ([#21192](https://github.com/laravel/framework/pull/21192))
- Added `-m` shortcut to `make:factory` ([#21219](https://github.com/laravel/framework/pull/21219))
- Added ability to set a fallback (catch-all) route ([#21234](https://github.com/laravel/framework/pull/21234))

### Changed
- Support `null` on `Model::UPDATED_AT` ([#21178](https://github.com/laravel/framework/pull/21178))
- Render views from config while building error views ([#21145](https://github.com/laravel/framework/pull/21145))
- Use multibyte functions in some `Str` methods ([#21207](https://github.com/laravel/framework/pull/21207))
- Perform stable sort in `Collection::sortBy()` ([#21214](https://github.com/laravel/framework/pull/21214))

### Fixed
- Ignore `SELECT` bindings in `prepareBindingsForUpdate()` ([#21173](https://github.com/laravel/framework/pull/21173))
- Fixed `remember` check in `AuthenticatesUsers::attemptLogin()` ([#21221](https://github.com/laravel/framework/pull/21221))
- Added "hardware" as an uncountable word ([#21236](https://github.com/laravel/framework/pull/21236))


## v5.5.4 (2017-09-13)

### Added
- Added `Blueprint::spatialIndex()` method ([#21070](https://github.com/laravel/framework/pull/21070))
- Added support for SQL Server's `TransactionIsolation` DSN key ([#21069](https://github.com/laravel/framework/pull/21069))
- Added `ManagesFrequencies::everyFifteenMinutes()` method ([#21092](https://github.com/laravel/framework/pull/21092))
- Added `Mailable::hasReplyTo()` method ([#21093](https://github.com/laravel/framework/pull/21093))
- Added `MailMessage::template()` method ([#21154](https://github.com/laravel/framework/pull/21154))
- Added support for Rackspace to `Storage::url()` ([#21157](https://github.com/laravel/framework/pull/21157))
- Added support to use sub-queries as a where condition on a join clause ([#21008](https://github.com/laravel/framework/pull/21008))

### Changed
- Return `null` from `Route::getAction()` if `$key` is not found ([#21083](https://github.com/laravel/framework/pull/21083))
- Restore non-static signature of `Router::prepareResponse()` ([#21114](https://github.com/laravel/framework/pull/21114), [e1a1265](https://github.com/laravel/framework/commit/e1a1265b6cd19c1597faafd4af409b913298c782))
- Removed `Model` type hint from `Model::isNot()` ([4d8f0a1](https://github.com/laravel/framework/commit/4d8f0a1a72fe9ea915570df2ef58cbafd43ec96a))
- Prefer `Jsonable` over `Arrayable` in `JsonResponse::setData()` ([#21136](https://github.com/laravel/framework/pull/21136))
- Reset `cc` and `bcc` in `Mailer::setGlobalTo()` ([#21137](https://github.com/laravel/framework/pull/21137))
- Avoid trace `args` in JSON exceptions ([#21149](https://github.com/laravel/framework/pull/21149))

### Fixed
- Fixed `@json` directive parameter logic ([2a25ee7](https://github.com/laravel/framework/commit/2a25ee7f2f2d5e2cbc1397cc24abbb2838a9b439))
- Fixed a problem with `withoutExceptionHandling()` when called more than once ([#21086](https://github.com/laravel/framework/pull/21086))
- Added a `compileForeign()` method to `PostgresGrammar` ([#21038](https://github.com/laravel/framework/pull/21038))
- Reset the index after a `MissingValue` while resolving resource ([#21127](https://github.com/laravel/framework/pull/21127))
- Fixed `getQualifiedParentKey()` on `BelongsToMany` relationships ([#21128](https://github.com/laravel/framework/pull/21128))
- Fixed parameters on `Route::view()` when using `where()` ([#21113](https://github.com/laravel/framework/pull/21113))
- Show real directory name in the exception message thrown by `PackageManifest` ([#21099](https://github.com/laravel/framework/pull/21099))
- Fixed undefined offset error when refreshing a database collection ([#21159](https://github.com/laravel/framework/pull/21159))


## v5.5.3 (2017-09-07)

### Added
- Added `$action` parameter to `Route::getAction()` for simpler access ([#20975](https://github.com/laravel/framework/pull/20975))
- Added `@json` blade directive ([#21004](https://github.com/laravel/framework/pull/21004))
- Added `rescue()` helper ([#21010](https://github.com/laravel/framework/pull/21010), [74ecb34](https://github.com/laravel/framework/commit/74ecb34e1af89969f139e2d1d0f22694704a30d1), [c4d1c47](https://github.com/laravel/framework/commit/c4d1c471d2a9d080362b8bed70be35cd84e2cdef))
- Support commas in `In` and `NotIn` parameters ([#21012](https://github.com/laravel/framework/pull/21012), [3c3c5e4](https://github.com/laravel/framework/commit/3c3c5e4402ed14ad86823aeec0f67b2da04629a0))
- Added `RedisManager::connections()` method ([#21014](https://github.com/laravel/framework/pull/21014), [1deaaa9](https://github.com/laravel/framework/commit/1deaaa9dc08e1f194558df745d17e468d35d9eae))
- Added exception class to JSON exceptions ([#21043](https://github.com/laravel/framework/pull/21043))
- Added `Gate::policies()` method ([#21036](https://github.com/laravel/framework/pull/21036))
- Added geo spatial blueprint methods ([#21056](https://github.com/laravel/framework/pull/21056))

### Changed
- Fixed migrations not being run in batch order ([#20986](https://github.com/laravel/framework/pull/20986))
- Flush application resources on teardown ([#21022](https://github.com/laravel/framework/pull/21022))
- Catch errors while building exception context ([#21047](https://github.com/laravel/framework/pull/21047))
- Return `$this` from `Validator::setCustomMessages()` ([#21046](https://github.com/laravel/framework/pull/21046))

### Fixed
- Make `Request::validate()` return the value of parent key ([#20974](https://github.com/laravel/framework/pull/20974))
- Fixed date comparison validators failing when a format is specified ([#20940](https://github.com/laravel/framework/pull/20940))
- Fixed login throttling failing when `decayMinutes` is more than `1` ([#20997](https://github.com/laravel/framework/pull/20997))
- Only use reflection on classes in `Kernel::load()` ([#20998](https://github.com/laravel/framework/pull/20998))
- Specify lower case `column_name` in `MySqlGrammar::compileColumnListing()` ([#21037](https://github.com/laravel/framework/pull/21037))
- Fixed eager loading problem with `BelongsToMany` ([#21044](https://github.com/laravel/framework/pull/21044))

### Removed
- Remove unnecessary `lcfirst()` call in `authorizeResource()` ([#21017](https://github.com/laravel/framework/pull/21017))
- Removed `$listensFor` from listener stubs ([#21039](https://github.com/laravel/framework/pull/21039))


## v5.5.2 (2017-09-04)

### Added
- Added `mov` extension and `MimeType::search()` method ([#20917](https://github.com/laravel/framework/pull/20917))
- Added support for `dont-discover` in packages ([#20921](https://github.com/laravel/framework/pull/20921), [4a6f1f2](https://github.com/laravel/framework/commit/4a6f1f2613f2ca5a1ef3792b019a769d6a269cda))
- Added `retrieved` model event ([#20852](https://github.com/laravel/framework/pull/20852), [84291a6](https://github.com/laravel/framework/commit/84291a63d86bd97339f9d3970913c20035b733b9))
- Added `HasOneOrMany::setForeignAttributesForCreate()` method ([#20871](https://github.com/laravel/framework/pull/20871))
- Made `Route` macroable ([#20970](https://github.com/laravel/framework/pull/20970))

### Changed
- Load deferred providers before commands ([366c50e](https://github.com/laravel/framework/commit/366c50ec161f296df99961ecc71229b5b097ad49))
- Don't pass cache instance to Schedule anymore ([#20916](https://github.com/laravel/framework/pull/20916), [#20933](https://github.com/laravel/framework/pull/20933))
- Simplified `mix` require ([#20929](https://github.com/laravel/framework/pull/20929))
- Return `null` if resource attribute contains relation with a null value ([#20969](https://github.com/laravel/framework/pull/20969))

### Fixed
- Prevent `ErrorException` in `Collection::operatorForWhere()` method ([#20913](https://github.com/laravel/framework/pull/20913))
- Create default console input/output in `Application::run()` ([#20922](https://github.com/laravel/framework/pull/20922), [7647399](https://github.com/laravel/framework/commit/7647399b54c42b12cd66b76da046e73d15bcbff1))
- Ignore abstract classes in `Kernel::load()` ([#20931](https://github.com/laravel/framework/pull/20931))
- Fixed `listener-queued-duck.stub` filename ([#20937](https://github.com/laravel/framework/pull/20937))
- Fixed faking notification sending while using AnonymousNotifiable ([#20965](https://github.com/laravel/framework/pull/20965))
- Fixed `eachSpread()` and `mapSpread()` with nested collections ([#20962](https://github.com/laravel/framework/pull/20962))
- Fixed generating names for classes beginning with slash ([#20961](https://github.com/laravel/framework/pull/20961))


## v5.5.1 (2017-09-01)

### Added
- Added getter methods on `MimeType` ([#20826](https://github.com/laravel/framework/pull/20826))

### Changed
- Moved console commands auto discovery to `Kernel::bootstrap()` ([#20863](https://github.com/laravel/framework/pull/20863))
- Use classes instead of helper functions ([#20879](https://github.com/laravel/framework/pull/20879), [#20880](https://github.com/laravel/framework/pull/20880))
- Changed `Resource::$collects` visibility to `public` ([#20885](https://github.com/laravel/framework/pull/20885))

### Fixed
- Fixed `choice()` on non-tty terminals ([#20840](https://github.com/laravel/framework/pull/20840))
- Fixed Macroable calls on `Optional` ([#20845](https://github.com/laravel/framework/pull/20845), [#20898](https://github.com/laravel/framework/pull/20898))
- Fixed `dropAllTables()` when using `PDO::FETCH_ASSOC` mode ([#20859](https://github.com/laravel/framework/pull/20859))
- Pass model name to `ModelNotFoundException::setModel()` ([#20896](https://github.com/laravel/framework/pull/20896), [891f90e](https://github.com/laravel/framework/commit/891f90ea48056979add7319c5642501c8678bc9c))
- Fixed `Basic` authentication ([#20905](https://github.com/laravel/framework/pull/20905))
- Fixed `DelegatesToResource::offsetExists()` ([#20887](https://github.com/laravel/framework/pull/20887))

### Removed
- Removed redundant methods from `MorphOneOrMany` ([#20837](https://github.com/laravel/framework/pull/20837))


## v5.5.0 (2017-08-30)

### General
- ⚠️ Require PHP 7+ ([06907a0](https://github.com/laravel/framework/pull/17048/commits/06907a055e3d28c219f6b6ab97902f0be3e8a4ef), [39809ce](https://github.com/laravel/framework/pull/17048/commits/39809cea81a5564d196c16a87cbc25de88dd3d1c))
- ⚠️ Removed deprecated `ServiceProvider::compile()` method ([10da428](https://github.com/laravel/framework/pull/17048/commits/10da428eb344191608474f1c12ee7edb0290e80a))
- ⚠️ Removed deprecated `Str::quickRandom()` method ([2ef257a](https://github.com/laravel/framework/pull/17048/commits/2ef257a4197b7e6efeb0d6ac4a3958f82b7fed39))
- Removed `build` scripts ([7c16b15](https://github.com/laravel/framework/pull/17048/commits/7c16b154ede10ff9a37756e32d7dddf317524634))
- Upgraded to Symfony 3.3 components ([4db7031](https://github.com/laravel/framework/commit/4db70311b1b3813359b250d3f5a58743fa436453), [67a5367](https://github.com/laravel/framework/commit/67a536758d1636935ab5502bb6faedd73b30810f))
- Throw `RuntimeException` when app key is missing ([#19145](https://github.com/laravel/framework/pull/19145), [8adbaa7](https://github.com/laravel/framework/commit/8adbaa714d37bb7214f29b12c52354900a1c6dc5))

### Artisan Console
- Added interactive prompt to `vendor:publish` ([#18230](https://github.com/laravel/framework/pull/18230))
- Added `migrate:fresh` command ([f6511d4](https://github.com/laravel/framework/commit/f6511d477f73b3033ef2336257f4cac5f20594a0), [#20090](https://github.com/laravel/framework/pull/20090))
- Added `make:factory` command and added `--factory` to `make:model` ([a6ffd8b](https://github.com/laravel/framework/commit/a6ffd8bfa896844fee4b4c83cc6aed9d0c33fd9d), [#19985](https://github.com/laravel/framework/pull/19985))
- Added `make:rule` command ([76853fd](https://github.com/laravel/framework/commit/76853fd192f8f378ad9b781d64e3e40a9511f737))
- ⚠️ Added `runningInConsole()` method `Application` contract ([#18658](https://github.com/laravel/framework/pull/18658))
- Support default value(s) on command arguments ([#18572](https://github.com/laravel/framework/pull/18572))
- Improved CLI detection for phpdbg ([#18781](https://github.com/laravel/framework/pull/18781))
- ⚠️ Always return array from `RetryCommand::getJobIds()` ([#19232](https://github.com/laravel/framework/pull/19232))
- Support passing absolute paths to `make::listener` ([#19660](https://github.com/laravel/framework/pull/19660))
- ⚠️ Use `handle()` method instead of `fire()` ([#19827](https://github.com/laravel/framework/pull/19827), [#19839](https://github.com/laravel/framework/pull/19839), [#20024](https://github.com/laravel/framework/pull/20024))
- Try to guess the `--create` option when generation migrations ([#20032](https://github.com/laravel/framework/pull/20032))
- Generate `make:policy` with real user model namespace ([#20047](https://github.com/laravel/framework/pull/20047))
- Added `Kernel::load()` to auto register a directory of commands ([2e7ddca](https://github.com/laravel/framework/commit/2e7ddca682214ea5ffd21aadc93d33b7a2805e94), [d607b9c](https://github.com/laravel/framework/commit/d607b9c670d9c7f7c749cda0a12a1dc6f55da6e4))
- ⚠️ Removed `array` type hint from `Command::table()` ([#20120](https://github.com/laravel/framework/pull/20120))
- Support loading multiple paths in `Kernel::load()` ([#20251](https://github.com/laravel/framework/pull/20251), [#20595](https://github.com/laravel/framework/pull/20595))
- Added `CommandStarting` and `CommandFinished` events ([#20298](https://github.com/laravel/framework/pull/20298))
- Show error message if a reverted migration is not found ([#20499](https://github.com/laravel/framework/pull/20499), [a895b1e](https://github.com/laravel/framework/commit/a895b1eb0e50683c4583c24bb17b3f8d9e8127ab))
- Set correct namespace in model factories when using the `app:name` command ([#20766](https://github.com/laravel/framework/pull/20766))
- ⚠️ Switched the `-f` shortcut from `--force` to `--factory` on `make:model` command ([#20800](https://github.com/laravel/framework/pull/20800))

### Assets
- Added frontend preset commands ([463b769](https://github.com/laravel/framework/commit/463b769270d462468e1b1dcc51a7a1144e003157), _too many follow-up commits_)

### Authentication
- ⚠️ Support default user providers and pass user provider to `RequestGuard` ([#18856](https://github.com/laravel/framework/pull/18856))
- Made the user provider parameter on `RequestGuard` optional ([d7f0b26](https://github.com/laravel/framework/commit/d7f0b2603ce0a0a568f84a8861c351a2c00d5613))
- Use `setRememberToken()` in `ResetsPasswords` ([#19189](https://github.com/laravel/framework/pull/19189))
- Added a `PasswordReset` event ([#19188](https://github.com/laravel/framework/pull/19188))
- ⚠️ Support multiword models in `authorizeResource()` ([#19821](https://github.com/laravel/framework/pull/19821))
- Added support for no user provider in `CreatesUserProviders` ([4feb847](https://github.com/laravel/framework/commit/4feb8477bab424da4ff9f34cba7afaed875db42d))

### Authorization
- Support multiple values in `Gate::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- ⚠️ Prevent policies from being too greedy ([#19120](https://github.com/laravel/framework/pull/19120))
- ⚠️ Added `abilities()` method to `Gate` contract ([#19173](https://github.com/laravel/framework/pull/19173))
- ⚠️ Implement `iterable` on `Gate::check()` and `Gate::any()` ([#20084](https://github.com/laravel/framework/pull/20084))

### Blade Templates
- Added `Blade::if()` method ([71dfe0f](https://github.com/laravel/framework/commit/71dfe0f0824412f106b80df8dedd7708e66dfb00), [2905364](https://github.com/laravel/framework/commit/2905364f7c9f14b42a7283e56313b38d256ce09d), [#20025](https://github.com/laravel/framework/pull/20025))
- Added `@switch`, `@case`, `@break` and `@default` directives ([#19758](https://github.com/laravel/framework/pull/19758))
- ⚠️ Prevent Blade from parsing PHP code inside `@php` blocks ([#20065](https://github.com/laravel/framework/pull/20065))

### Broadcasting
- ⚠️ Use `AccessDeniedHttpException` instead if `HttpException` ([#19611](https://github.com/laravel/framework/pull/19611))
- ⚠️ Upgraded to Pusher SDK v3 ([#20016](https://github.com/laravel/framework/pull/20016))

### Cache
- ⚠️ PSR-16 compliance ([#20194](https://github.com/laravel/framework/pull/20194))
- Don't encrypt database cache values ([f0c72ec](https://github.com/laravel/framework/commit/f0c72ec9bcbdecb7e6267f7ec8f7ecbf8169a388))
- Added support cache locks ([4e6b2e4](https://github.com/laravel/framework/commit/4e6b2e4ecbbec5a4b265f4d5a57ad1399227cf12), [045e6f2](https://github.com/laravel/framework/commit/045e6f25a860763942c928c4e6d8857d59741486), [#19669](https://github.com/laravel/framework/pull/19669))
- Accept `DatetimeInterface` and `DateInterval` in cache repository ([#20034](https://github.com/laravel/framework/pull/20034))
- Added `getStore()` method to cache `Repository` interface ([#20338](https://github.com/laravel/framework/pull/20338))
- ⚠️ Made `RateLimiter` less aggressive ([#20759](https://github.com/laravel/framework/pull/20759))

### Collections
- Support multiple values in `Collection::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- Added `Collection::mapInto()` method ([2642ac7](https://github.com/laravel/framework/commit/2642ac73cc5718a8aebe3d009b143b0fa43be085))
- Added `Collection::dd()` method ([f5fafad](https://github.com/laravel/framework/commit/f5fafad80dbb08353824483f5b849031693cc477))
- Added `Collection::dump()` method ([#19755](https://github.com/laravel/framework/pull/19755))
- Added `wrap()` and `unwrap()` methods ([#20055](https://github.com/laravel/framework/pull/20055), [#20068](https://github.com/laravel/framework/pull/20068))
- Added keys to `partition()`, `eachSpread()` and `mapSpread` callback ([#20783](https://github.com/laravel/framework/pull/20783), [#20723](https://github.com/laravel/framework/pull/20723))

### Configuration
- Added `Config::getMany()` method ([#19770](https://github.com/laravel/framework/pull/19770))

### Controllers
- ⚠️ Added `ControllerDispatcher` ([#20031](https://github.com/laravel/framework/pull/20031))
- ⚠️ Removed `Controller::missingMethod()` ([bf5d221](https://github.com/laravel/framework/commit/bf5d221037d9857a74020f2623839e282035a420))

### Database
- ⚠️ Added `dropAllTables()` to schema builder ([#18484](https://github.com/laravel/framework/pull/18484), [d910bc8](https://github.com/laravel/framework/commit/d910bc8039f3cec2d906797818984e825601a3f5), [#19644](https://github.com/laravel/framework/pull/19644), [#19645](https://github.com/laravel/framework/pull/19645), [#20239](https://github.com/laravel/framework/pull/20239), [#20536](https://github.com/laravel/framework/pull/20536))
- Added precision to `dateTime` and `timestamp` column types ([#18847](https://github.com/laravel/framework/pull/18847), [f85f6db](https://github.com/laravel/framework/commit/f85f6db7c00a43ae45d963d089458477cf3e44b3), [#18962](https://github.com/laravel/framework/pull/18962), [#20464](https://github.com/laravel/framework/pull/20464))
- Pass page number to `chunk()` callback ([#19316](https://github.com/laravel/framework/pull/19316))
- Improve memory usage in `chunk()` and `chunkById()` ([#19345](https://github.com/laravel/framework/pull/19345), [#19369](https://github.com/laravel/framework/pull/19369), [#19368](https://github.com/laravel/framework/pull/19368))
- Fixed `compileColumnListing()` when using PostgreSQL with multiple schemas ([#19553](https://github.com/laravel/framework/pull/19553))
- Allow the seeder to call multiple commands at once ([#19912](https://github.com/laravel/framework/pull/19912))
- Added pgpool message to `DetectsLostConnections` ([#20418](https://github.com/laravel/framework/pull/20418))
- Prevent race conditions on replicated databases ([#20445](https://github.com/laravel/framework/pull/20445), [0ec1522](https://github.com/laravel/framework/commit/0ec1522a74f4ef7b6a01d617a482ae3f46c81a70), [3824a36](https://github.com/laravel/framework/commit/3824a366b0cd8a081bef26d3b4509c5db2fe7aae))
- ⚠️ Support sticky database connections ([#20746](https://github.com/laravel/framework/pull/20746))

### Eloquent ORM
- Added API resources ([#20710](https://github.com/laravel/framework/pull/20710), _too many follow-up commits_)
- ⚠️ Indicate soft deleted models as existing ([#17613](https://github.com/laravel/framework/pull/17613))
- ⚠️ Added `$localKey` parameter to `HasRelationships::belongsToMany()` and `BelongsToMany` ([#17903](https://github.com/laravel/framework/pull/17903), [7c7c3bc](https://github.com/laravel/framework/commit/7c7c3bc4be3052afe0889fe323230dfd92f81000))
- ⚠️ Added `$parentKey` parameter to `belongsToMany()`, `BelongsToMany` and `MorphToMany` ([#17915](https://github.com/laravel/framework/pull/17915), [#18380](https://github.com/laravel/framework/pull/18380))
- ⚠️ Renamed `$parent` property to `$pivotParent` in `Pivot` class ([#17933](https://github.com/laravel/framework/pull/17933), [#18150](https://github.com/laravel/framework/pull/18150))
- ⚠️ Don't add `_count` suffix to column name when using `withCount()` with an alias ([#17871](https://github.com/laravel/framework/pull/17871))
- ⚠️ Renamed `$events` to `$dispatchesEvents` ([#17961](https://github.com/laravel/framework/pull/17961), [b6472bf](https://github.com/laravel/framework/commit/b6472bf6fec1af6e76604aaf3f7fed665440ac66), [3dbe12f](https://github.com/laravel/framework/commit/3dbe12f16f470e3bca868576d517d57876bc50af))
- ⚠️ Only return query builder when the result is null for `callScope()` ([#18845](https://github.com/laravel/framework/pull/18845))
- Allow setting a factory's attribute to a factory instance ([#18879](https://github.com/laravel/framework/pull/18879))
- Support `null` comparison in `Model::is()` ([#18511](https://github.com/laravel/framework/pull/18511))
- Added `getDirty()` checks for date and castable attributes ([#18400](https://github.com/laravel/framework/pull/18400), [e180e20](https://github.com/laravel/framework/commit/e180e20aa479525b34f77b9cf348148d329a4d2c))
- Show method name in invalid relationship `LogicException` ([#18749](https://github.com/laravel/framework/pull/18749))
- Add support for additional values in `firstOrCreate()` and `firstOrNew()` ([#18878](https://github.com/laravel/framework/pull/18878))
- Added a second local key to `HasManyThrough` ([#19114](https://github.com/laravel/framework/pull/19114))
- Respect casts declaration on custom pivot models ([#19335](https://github.com/laravel/framework/pull/19335))
- Support creating relations without attributes ([#19506](https://github.com/laravel/framework/pull/19506))
- Added `Model::only()` method ([#19459](https://github.com/laravel/framework/pull/19459))
- ⚠️ Support model serialization on non default connection ([#19521](https://github.com/laravel/framework/pull/19521), [dd45f70](https://github.com/laravel/framework/commit/dd45f70519b72aa57bc21cec4e89886917990fa9))
- ⚠️ Support updating nullable dates ([#19672](https://github.com/laravel/framework/pull/19672))
- ⚠️ Make pivot model instantiable ([#20179](https://github.com/laravel/framework/pull/20179))
- Simplified `BelongsToMany::allRelatedIds()` logic ([#20189](https://github.com/laravel/framework/pull/20189))
- Added `Relation::get()` method ([#20052](https://github.com/laravel/framework/pull/20052))
- Added `hasChanges()`, `wasChanged()`, `getChanges()` and `syncChanges()` ([#20129](https://github.com/laravel/framework/pull/20129), [#20130](https://github.com/laravel/framework/pull/20130))
- Better exception message when calling non existing methods on models ([#20196](https://github.com/laravel/framework/pull/20196), [91c1f03](https://github.com/laravel/framework/commit/91c1f03be2835f5b15998ead9f47f37d5397c0cc))
- Added support for connections on model factories ([#20191](https://github.com/laravel/framework/pull/20191))
- Check for real primary key in `Pivot` methods ([8d82618](https://github.com/laravel/framework/commit/8d826189bb2db1c177d8605eb9218daa973acb6a))
- Default `$attributes` on `BelongsToMany::create()` to empty array ([973bff4](https://github.com/laravel/framework/commit/973bff4527a433fa039fd937ecfe048ed2325a5f))
- Add ability to set a custom pivot accessor ([#20411](https://github.com/laravel/framework/pull/20411))
- ⚠️ Call `setConnection()` in `Model::save()` ([#20466](https://github.com/laravel/framework/pull/20466))
- ⚠️ Touch parent timestamp only if the model is dirty ([#20489](https://github.com/laravel/framework/pull/20489))
- Added `Model::loadMissing()` method ([#20630](https://github.com/laravel/framework/pull/20630), [4166c12](https://github.com/laravel/framework/commit/4166c12492ce7b1112911299caf4cdb17efc9364))
- Added `Model::whereKeyNot()` method ([#20817](https://github.com/laravel/framework/pull/20817))

### Encryption
- Use `openssl_cipher_iv_length()` in `Encrypter` ([#18684](https://github.com/laravel/framework/pull/18684))
- Added `Encrypter::generateKey()` method ([6623996](https://github.com/laravel/framework/commit/6623996212b3d59aa31a374b70311f03fd158075))
- Use `json_last_error()` in `Encrypter` ([#20099](https://github.com/laravel/framework/pull/20099))

### Errors & Logging
- Added default 404, 419 and 500 error pages ([#18483](https://github.com/laravel/framework/pull/18483), [4d8c2c1](https://github.com/laravel/framework/commit/4d8c2c1f53979a669a59793b4ec61c8e60ed5b29))
- ⚠️ Always show custom 500 error page for all exception types when not in debug mode ([#18481](https://github.com/laravel/framework/pull/18481), [3cb7b0f](https://github.com/laravel/framework/commit/3cb7b0f4304274f209ed0f776ef70ccd4f9fe5dd))
- ⚠️ Show 419 error page on `TokenMismatchException` ([#18728](https://github.com/laravel/framework/pull/18728))
- Support `render()` method on exceptions ([ed51160](https://github.com/laravel/framework/commit/ed51160b97d8c4cf16526a0f8ba57ce7cb131b53), [c8a9413](https://github.com/laravel/framework/commit/c8a9413e2dc3bf00c206742e2bc76a88134cba84))
- Support `report()` method on exceptions ([e77f6f7](https://github.com/laravel/framework/commit/e77f6f76049050fd4abced63ffa768432d8974f2))
- ⚠️ Send exceptions as JSON in debug mode if the request wants JSON ([5225389](https://github.com/laravel/framework/commit/5225389dfdf03d656b862bba59cebf1820e0e8f4), [#18732](https://github.com/laravel/framework/pull/18732), [4fe6091](https://github.com/laravel/framework/commit/4fe6091e9fc94817a70c47a6a1c2098d5a1805f8), [9ab58fd](https://github.com/laravel/framework/commit/9ab58fd1a0543b1c728124db7f70738b04dcf362), [#19333](https://github.com/laravel/framework/pull/19333))
- ⚠️ Moved exceptions from `$dontReport` into `$internalDontReport` ([841b36c](https://github.com/laravel/framework/commit/841b36cc005ee5c400f1276175db9e2692d1e167))
- Added `Handler::context()` method, that by default adds some default context to logs ([23b7d6b](https://github.com/laravel/framework/commit/23b7d6b45c675bcd93e9f1fb9cd33e71779142c6))
- ⚠️ Don't set formatter on `ErrorLogHandler` ([a044f17](https://github.com/laravel/framework/commit/a044f17897eeda3ab909ea47eeba3804dabdf9ad))
- Use whoops for errors ([b697272](https://github.com/laravel/framework/commit/b69727243305e0ffa4a68819450716f26396c5e6), [f6b67d4](https://github.com/laravel/framework/commit/f6b67d4e49e6c4de765f4b29b3c36c5d4ff84471), [#19471](https://github.com/laravel/framework/pull/19471), [#20412](https://github.com/laravel/framework/pull/20412))
- Changed how exceptions are logged ([#19698](https://github.com/laravel/framework/pull/19698), [f1971c2](https://github.com/laravel/framework/commit/f1971c2242e4882440162fe504126a1475f7f2b4))
- ⚠️ Return `HttpException` with code `413` from `PostTooLargeException` ([#19773](https://github.com/laravel/framework/pull/19773))
- Support custom logger channel names ([#20133](https://github.com/laravel/framework/pull/20133))
- ⚠️ Unify exception formatting ([#20173](https://github.com/laravel/framework/pull/20173), [#20067](https://github.com/laravel/framework/pull/20067), [#20167](https://github.com/laravel/framework/pull/20167), _too many follow-up commits, sorry_)
- Added default `Handler::unauthenticated()` method ([11b0de0](https://github.com/laravel/framework/commit/11b0de0485632d5712f7fb59071a4acbc4af2bdc))

### Events
- ⚠️ Removed calling queue method on handlers ([0360cb1](https://github.com/laravel/framework/commit/0360cb1c6b71ec89d406517b19d1508511e98fb5), [ec96979](https://github.com/laravel/framework/commit/ec969797878f2c731034455af2397110732d14c4), [d9be4bf](https://github.com/laravel/framework/commit/d9be4bfe0367a8e07eed4931bdabf135292abb1b))
- Allow faking only specific events ([#19429](https://github.com/laravel/framework/pull/19429))
- Support self-registering event listeners ([#19917](https://github.com/laravel/framework/pull/19917), [4d557c5](https://github.com/laravel/framework/commit/4d557c5f0aa81fb9cb753d77ffec931c9166a927), [#19962](https://github.com/laravel/framework/pull/19962), [5ed4f50](https://github.com/laravel/framework/commit/5ed4f5081f3674003919a79b346e256b162359cf))
- Added ability to determine if queued handler should be pushed to queue ([#19957](https://github.com/laravel/framework/pull/19957), [efe616c](https://github.com/laravel/framework/commit/efe616cc2872ad096dd7fb1b8d6dd8e2e65ec846))

### Filesystem
- ⚠️ Made `Storage::files()` work like `Storage::allFiles()` ([#18874](https://github.com/laravel/framework/pull/18874), [7073457](https://github.com/laravel/framework/commit/7073457041a29ada14e0ed01d7d65f5c76a92689))
- ⚠️ Fixed compatibility between `FilesystemAdapter` and the `Filesystem` interface ([#19389](https://github.com/laravel/framework/pull/19389))

### Helpers
- Added `report()` helper ([2b67619](https://github.com/laravel/framework/commit/2b676191b1688b8edc9d43317a2989642fe95b5d))
- Added `throw_if()` and `throw_unless()` helpers ([18bb4df](https://github.com/laravel/framework/commit/18bb4dfc77c7c289e9b40c4096816ebeff1cd843), [#19166](https://github.com/laravel/framework/pull/19166), [#19255](https://github.com/laravel/framework/pull/19255))
- Added `dispatch_now()` helper ([#18668](https://github.com/laravel/framework/pull/18668), [61f2e7b](https://github.com/laravel/framework/commit/61f2e7b4106f8eb0b79603d9792426f7c6a6d273))
- Added `$language` parameter to `str_slug()` helper ([#19011](https://github.com/laravel/framework/pull/19011))
- Added `str_before()` helper ([#19940](https://github.com/laravel/framework/pull/19940), [#20049](https://github.com/laravel/framework/pull/20049))
- Added `now()` and `today()` helpers ([3c888b6](https://github.com/laravel/framework/commit/3c888b6c7b89c3d3f90e9024ffbebed3ee80bd23), [#20716](https://github.com/laravel/framework/pull/20716))
- Added `blank()`, `filled()`, `optional()` and `transform()` helpers ([06de9b2](https://github.com/laravel/framework/commit/06de9b2beb9e3c13758d93cee86a1657545cb435), [31308e3](https://github.com/laravel/framework/commit/31308e396ecbfeb5a6e505c50a6b1a6b721b094d))
- Handle lower case words better in as `Str::snake()` ([#18764](https://github.com/laravel/framework/pull/18764))
- Removed usages of the `with()` helper ([#17888](https://github.com/laravel/framework/pull/17888))
- Support multiple patterns in `Str::is()` ([#20108](https://github.com/laravel/framework/pull/20108))
- Speed up `Arr::get()` calls without dot notations ([#20139](https://github.com/laravel/framework/pull/20139))
- Use `report()` helper in `mix()` ([#20603](https://github.com/laravel/framework/pull/20603), [bf0cb82](https://github.com/laravel/framework/commit/bf0cb82a8990d99a0ed504c2fa6684b1c59c9d7e))

### Localization
- ⚠️ Moved `LoaderInterface` to contracts ([#20460](https://github.com/laravel/framework/pull/20460))
- ⚠️ Support loading JSON translation for packages ([#20599](https://github.com/laravel/framework/pull/20599), [573f85c](https://github.com/laravel/framework/commit/573f85c3dd968f97081382b6f633b0a08b51fed5))
- Support language specific characters in `Str` ([#18974](https://github.com/laravel/framework/pull/18974), [#19694](https://github.com/laravel/framework/pull/19694))

### Mail
- Allow mailables to be rendered directly to views ([d9a6dfa](https://github.com/laravel/framework/commit/d9a6dfa4f46a10feceb67921b78c60a905b7c28c))
- Allow for per-mailable theme configuration ([b2c35ca](https://github.com/laravel/framework/commit/b2c35ca9eb769d1a4752a64e936defd7f7099043))
- ⚠️ Removed `$data` and `$callback` parameters from `Mailer` and `MailQueue`
- ⚠️ Made `Markdown` a dependency of `MailChannel` ([#19349](https://github.com/laravel/framework/pull/19349))
- ⚠️ Upgraded to SwiftMailer 6 ([#19356](https://github.com/laravel/framework/pull/19356))
- ⚠️ Added `to()` and `bcc()` to `Mailer` contract ([#19955](https://github.com/laravel/framework/pull/19955))

### Notifications
- Added methods for Slack's `thumb_url` and `unfurl_*` options ([#19150](https://github.com/laravel/framework/pull/19150), [#19200](https://github.com/laravel/framework/pull/19200))
- Support sending notifications via `AnonymousNotifiable` ([#19998](https://github.com/laravel/framework/pull/19998), [ba82579](https://github.com/laravel/framework/commit/ba825798f107c961a2337f13928bc6f4acac9447))
- Accept other types on `SlackAttachment::timestamp()` ([#20671](https://github.com/laravel/framework/pull/20671))

### Queues
- Added support for chainable jobs ([81bcb03](https://github.com/laravel/framework/commit/81bcb03b303707cdc94420983b9d72ed558a2b3d), _too many follow-up commits, sorry_)
- ⚠️ Removed redundant `$queue` parameter from `Queue::createPayload()` ([#17948](https://github.com/laravel/framework/pull/17948))
- Made all `getQueue()` methods `public` ([#18501](https://github.com/laravel/framework/pull/18501))
- Pass connection and queue to `Looping` event ([#19081](https://github.com/laravel/framework/pull/19081))
- ⚠️ Clone Job specific properties ([#19123](https://github.com/laravel/framework/pull/19123))
- ⚠️ Declare missing abstract `Job::getRawBody()` method ([#19677](https://github.com/laravel/framework/pull/19677))
- ⚠️ Fail (or optionally silently delete) job when model is missing during deserialization ([44b1f85](https://github.com/laravel/framework/commit/44b1f859bbaf8f33733c804857cc269de92b1fd4), [bceded6](https://github.com/laravel/framework/commit/bceded6fef79760b9907dbe105829f7d2d62f899))
- Added `CallQueuedListener::__clone()` method ([#20022](https://github.com/laravel/framework/pull/20022))
- Accept `DatetimeInterface` and `DateInterval` in queue ([#20102](https://github.com/laravel/framework/pull/20102), [92e2aff](https://github.com/laravel/framework/commit/92e2aff2fd9569fedf3164ef9a1a834e553a6881))
- ⚠️ Use `dispatch()` instead of `fire()` ([#20446](https://github.com/laravel/framework/pull/20446))
- Removed `reserved_at` index from jobs table stub ([#20702](https://github.com/laravel/framework/pull/20702))
- Support job expiration ([#20776](https://github.com/laravel/framework/pull/20776), [1592b9b](https://github.com/laravel/framework/commit/1592b9b27b9ba25bf8bbb313900c5ffc635b0f10))

### Redis
- ⚠️ Several improvements on `PhpRedisConnection` ([#20269](https://github.com/laravel/framework/pull/20269), [#20316](https://github.com/laravel/framework/pull/20316))
- ⚠️ Removed `PhpRedisConnection::proxyToEval()` method ([#17360](https://github.com/laravel/framework/pull/17360))
- Added Redis limiters ([#20597](https://github.com/laravel/framework/pull/20597), [ceb260e](https://github.com/laravel/framework/commit/ceb260e6e8825a150651299b017b6a1dd5bd4db3), [#20761](https://github.com/laravel/framework/pull/20761), [aba76bf](https://github.com/laravel/framework/commit/aba76bf36ae9b301da3c778d7d4fc427a58f8aa4), [3684f0c](https://github.com/laravel/framework/commit/3684f0cfce1effabeb5d02c929d2b5335800f759), [#20772](https://github.com/laravel/framework/pull/20772))

### Requests
- ⚠️ Made `Request::has()` work like `Collection::has()` ([#18715](https://github.com/laravel/framework/pull/18715))
- Added `Request::filled()` ([#18715](https://github.com/laravel/framework/pull/18715))
- ⚠️ Made `Request::only()` work like `Collection::only()` ([#18695](https://github.com/laravel/framework/pull/18695))
- Aliased `Request::exists()` to `Request::has()` ([183bf16](https://github.com/laravel/framework/commit/183bf16a2c939889f4461e237a851b55cf858f8e))
- Allow passing keys to `Request::all()` to behave like old `Request::only()` ([#18754](https://github.com/laravel/framework/pull/18754))
- ⚠️ Removed `Request::intersect()` ([#18695](https://github.com/laravel/framework/pull/18695))
- Return request data from `ValidatesRequests` calls ([#19033](https://github.com/laravel/framework/pull/19033))
- Added a `validate()` macro onto `Request` ([#19063](https://github.com/laravel/framework/pull/19063))
- Added `FormRequest::validated()` method ([#19112](https://github.com/laravel/framework/pull/19112))
- ⚠️ Made `request()` helper and `Request::__get()` consistent ([a6ff272](https://github.com/laravel/framework/commit/a6ff272c54677a9f52718292fc0938ffb1871832))
- Made `Request::routeIs()` work like `Request()::fullUrlIs()` ([#19267](https://github.com/laravel/framework/pull/19267), [bfc5321](https://github.com/laravel/framework/commit/bfc53213f67d50444d3db078737990fa14081d1b), [#19334](https://github.com/laravel/framework/pull/19334))
- Added `Request::hasAny()` method  ([#19367](https://github.com/laravel/framework/pull/19367))
- ⚠️ Throw validation exception from `ValidatesRequests` without formatting response ([#19929](https://github.com/laravel/framework/pull/19929), [6d33675](https://github.com/laravel/framework/commit/6d33675691aae86c71454b731ceed847256b9dac), [ec88362](https://github.com/laravel/framework/commit/ec88362ee06ad418db93eb0e19f6d285eed7e701), [c264807](https://github.com/laravel/framework/commit/c2648070eb2108b0f9a4189bfbabea195282b963))
- Added `Request::post()` method ([#20238](https://github.com/laravel/framework/pull/20238))
- Added `Request::keys()` method ([#20611](https://github.com/laravel/framework/pull/20611))

### Routing
- Support fluent resource options ([#18767](https://github.com/laravel/framework/pull/18767), [bb02fb2](https://github.com/laravel/framework/commit/bb02fb27387a8aeb2a47da1fe5ff2e086920b744))
- Support multiple values in `Router::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- ⚠️ Bind empty optional route parameter to `null` instead of empty model instance ([#17521](https://github.com/laravel/framework/pull/17521))
- Accept patterns on `Route::named()`, `Router::is()` and `Router::currentRouteNamed()` ([#19267](https://github.com/laravel/framework/pull/19267), [bfc5321](https://github.com/laravel/framework/commit/bfc53213f67d50444d3db078737990fa14081d1b))
- Added `domain()` setter/getter to `Route` ([#19245](https://github.com/laravel/framework/pull/19245), [bba04a1](https://github.com/laravel/framework/commit/bba04a1598c44a892e918c4f308407b0d297f217))
- Added `Route::redirect()` method ([#19794](https://github.com/laravel/framework/pull/19794))
- Added `Route::view()` method ([#19835](https://github.com/laravel/framework/pull/19835))
- ⚠️ Improved `ThrottleRequests` middleware ([#19807](https://github.com/laravel/framework/pull/19807), [#19860](https://github.com/laravel/framework/pull/19860))
- ⚠️ Return proper 304 responses ([#19867](https://github.com/laravel/framework/pull/19867))
- Return the resource from `Router::apiResource()` ([#20029](https://github.com/laravel/framework/pull/20029))
- ⚠️ Moved route model binding resolution logic to model ([#20521](https://github.com/laravel/framework/pull/20521), [370e626](https://github.com/laravel/framework/commit/370e626e5cf7d5763bbb0e58aa2a2cd3c01e2b61), [#20542](https://github.com/laravel/framework/pull/20542), [#20618](https://github.com/laravel/framework/pull/20618), [d911fa8](https://github.com/laravel/framework/commit/d911fa8f5db0100a861a3c1696d426624ec27b4e))
- Accept string on `parameters()` and `names()` methods ([#20531](https://github.com/laravel/framework/pull/20531), [#20529](https://github.com/laravel/framework/pull/20529))
- Handle `HEAD` requests in `Router::view()` ([#20672](https://github.com/laravel/framework/pull/20672))
- Added `ThrottleRequestsWithRedis` middleware ([#20761](https://github.com/laravel/framework/pull/20761), [0a10f9a](https://github.com/laravel/framework/commit/0a10f9a9dab928c9e4d75c66620e35aa73f329c2))

### Responses
- ⚠️ Ensure `Arrayable` and `Jsonable` return a `JsonResponse` ([#17875](https://github.com/laravel/framework/pull/17875))
- ⚠️ Ensure `Arrayable` objects are also morphed by `Response` ([#17868](https://github.com/laravel/framework/pull/17868))
- Added `SameSite` support to `CookieJar` ([#18040](https://github.com/laravel/framework/pull/18040), [#18059](https://github.com/laravel/framework/pull/18059), [e69d722](https://github.com/laravel/framework/commit/e69d72296cfd9969db569b950721461a521100c4))
- Accept `HeaderBag` in `ResponseTrait::withHeaders()` ([#18161](https://github.com/laravel/framework/pull/18161))
- ⚠️ Reset response content-type in `Response::setContent()` ([#18314](https://github.com/laravel/framework/pull/18314), [#20313](https://github.com/laravel/framework/pull/20313))
- ⚠️ Always retrieve the real original content ([#20002](https://github.com/laravel/framework/pull/20002))

### Service Container
- ⚠️ Refactored `Container` ([#19201](https://github.com/laravel/framework/pull/19201))
- ⚠️ Made container PSR-11 compliant ([#19822](https://github.com/laravel/framework/pull/19822), [a6068b0](https://github.com/laravel/framework/commit/a6068b06ba42700f25b613a7bc3036be75d5bc43), [66325c2](https://github.com/laravel/framework/commit/66325c2c5768a5b10376e1838288c5212e3c9c40))
- Return the bound instance from `Container::instance()` ([#19442](https://github.com/laravel/framework/pull/19442))
- ⚠️ Use instance instead of deferred service provider ([#20714](https://github.com/laravel/framework/pull/20714))

### Session
- ⚠️ Default value to `true` in `Store::flash()` ([#18136](https://github.com/laravel/framework/pull/18136))
- ⚠️ Store the user password hash when logging in ([#19843](https://github.com/laravel/framework/pull/19843))
- ⚠️ Throw `UnauthorizedHttpException` from `failedBasicResponse` ([#20673](https://github.com/laravel/framework/pull/20673))

### Support
- Autoload package providers ([#19420](https://github.com/laravel/framework/pull/19420), [a5a0f3e](https://github.com/laravel/framework/commit/a5a0f3e7b82a1a4dc00037c5463a31d42c94903a), [2954091](https://github.com/laravel/framework/commit/295409189af589c6389d01e9d55f5568741149ee), [#19455](https://github.com/laravel/framework/pull/19455), [#19561](https://github.com/laravel/framework/pull/19561), [#19646](https://github.com/laravel/framework/pull/19646))
- Added support for `Responsable` objects ([c0c89fd](https://github.com/laravel/framework/commit/c0c89fd73cebf9ed56e6c5e69ad35106df03d9db), [1229b7f](https://github.com/laravel/framework/commit/1229b7f45d3f574d7e0262cc2d5aec80ccbb1626), [#19614](https://github.com/laravel/framework/pull/19614), [ef0e37d](https://github.com/laravel/framework/commit/ef0e37d44182ac5043b5459bb25b1861e8e036df))
- Made `Carbon` macroable and serializeable ([#19771](https://github.com/laravel/framework/pull/19771), [#20568](https://github.com/laravel/framework/pull/20568), [6a18209](https://github.com/laravel/framework/commit/6a18209863a934446d21ad8bc82c83d4b7dee5e7))
- Support registering macros using classes ([#19782](https://github.com/laravel/framework/pull/19782), [353adbd](https://github.com/laravel/framework/commit/353adbd696e36764227e39980272d38147899d14))
- ⚠️ Moved `InteractsWithTime` to `Illuminate\Support` ([#20119](https://github.com/laravel/framework/pull/20119), [#20206](https://github.com/laravel/framework/pull/20206))
- Support callable/invokable objects in `Pipeline` ([#18264](https://github.com/laravel/framework/pull/18264))
- ⚠️ Prevent access to protected properties using array access on `Model` and `Fluent` ([#18403](https://github.com/laravel/framework/pull/18403))
- ⚠️ Extend `MessageBag` interface from `Arrayable` and add `getMessages()` method ([#19768](https://github.com/laravel/framework/pull/19768), [#20334](https://github.com/laravel/framework/pull/20334))
- Handle `Arrayable` items in `MessageBag` ([6f1f4d8](https://github.com/laravel/framework/commit/6f1f4d834a2f985a06d956305fc73b5329363071))
- Added `isNotEmpty()` method to message bags and paginators ([#19944](https://github.com/laravel/framework/pull/19944))
- Return the collection iterator from `AbstractPaginator::getIterator()` ([#20098](https://github.com/laravel/framework/pull/20098))
- ⚠️ Fixed minimum value of paginator `last_page` field ([#20335](https://github.com/laravel/framework/pull/20335))

### Task Scheduling
- Fire before callbacks on closure-based scheduling events ([#18861](https://github.com/laravel/framework/pull/18861))
- Run after-callbacks even if a callback event failed ([#19573](https://github.com/laravel/framework/pull/19573))
- ⚠️ Fixed bug in `quarterly()` method ([#19600](https://github.com/laravel/framework/pull/19600))
- ⚠️ Support passing boolean into `when()` and `skip()` ([1d1a96e](https://github.com/laravel/framework/commit/1d1a96e405fec58fd287940f005bd8e40d4e546b))

### Testing
- ⚠️ Switched to PHPUnit 6 ([#17755](https://github.com/laravel/framework/pull/17755), [#17864](https://github.com/laravel/framework/pull/17864))
- ⚠️ Renamed authentication assertion methods ([#17924](https://github.com/laravel/framework/pull/17924), [494a177](https://github.com/laravel/framework/commit/494a1774f217f0cd6b4efade63e200e3ac65f201))
- ⚠️ Unify database testing traits into `RefreshDatabase` trait ([79c6f67](https://github.com/laravel/framework/commit/79c6f6774eecf77aef8ed5e2f270551a6f378f1d), [0322e32](https://github.com/laravel/framework/commit/0322e3226196a435db436e2a00c035be892c2466), [#20308](https://github.com/laravel/framework/pull/20308))
- ⚠️ Changed Blade tests namespace to `Illuminate\Tests\View\Blade` ([#19675](https://github.com/laravel/framework/pull/19675))
- Added integration tests for the framework itself ([182027d](https://github.com/laravel/framework/commit/182027d3290e9a2e1bd9e2d52c125177ef6c6af6), [#18438](https://github.com/laravel/framework/pull/18438), [#18780](https://github.com/laravel/framework/pull/18780), [#19001](https://github.com/laravel/framework/pull/19001), [#20073](https://github.com/laravel/framework/pull/20073))
- Allow disabling of specific middleware ([#18673](https://github.com/laravel/framework/pull/18673))
- Added `withoutExceptionHandling()` method ([a171f44](https://github.com/laravel/framework/commit/a171f44594c248afe066fee74fad640765b12da0))
- Support inline eloquent factory states ([#19060](https://github.com/laravel/framework/pull/19060))
- Allow `assertSessionHasErrors()` to look into different error bags ([#19172](https://github.com/laravel/framework/pull/19172), [4287ebc](https://github.com/laravel/framework/commit/4287ebc76025cd31e0ba6730481a95aeb471e305))
- Ensure Redis is available in cache lock tests ([#19791](https://github.com/laravel/framework/pull/19791))
- Skip tests if Memcached is not found ([#20018](https://github.com/laravel/framework/pull/20018))
- ⚠️ Clear `Carbon` mock during tear down ([#19934](https://github.com/laravel/framework/pull/19934))
- Added debug info to `NotFoundHttpException` in `InteractsWithExceptionHandling` ([#20000](https://github.com/laravel/framework/pull/20000))
- Added `MailFake::assertSentTimes()`, `QueueFake::assertPushedTimes()` and `BusFake::assertDispatchedTimes()` methods ([#20485](https://github.com/laravel/framework/pull/20485), [e657f6e](https://github.com/laravel/framework/commit/e657f6ec20867fc748e4f8b8ca1bbaa344c07acb))
- Added queue assertions to `MailFake` ([#20454](https://github.com/laravel/framework/pull/20454), [#20701](https://github.com/laravel/framework/pull/20701))
- Added `assertNothingSent()` and `assertSentTimes()` methods to `NotificationFake` ([#20651](https://github.com/laravel/framework/pull/20651))
- Added Mockery expectations to the assertion count ([#20606](https://github.com/laravel/framework/pull/20606))
- Fake the default storage disk by default ([#20625](https://github.com/laravel/framework/pull/20625))
- Support sending default headers with requests ([#20590](https://github.com/laravel/framework/pull/20590), [c32418e](https://github.com/laravel/framework/commit/c32418e8ca13e1fef3908d3a497ea49df0cebbb3))
- Support disabling of exception handling for specified exceptions ([#20729](https://github.com/laravel/framework/pull/20729), [2db9716](https://github.com/laravel/framework/commit/2db9716186c71cd0604277fc377a2654a6f10aaf))

### Validation
- Added support for custom validation rule objects ([#19155](https://github.com/laravel/framework/pull/19155), [2aa5ea8](https://github.com/laravel/framework/commit/2aa5ea8a898bd220015ab9be453b36723ffb186e))
- Validate against `DateTimeInterface` instead of `DateTime` ([#20110](https://github.com/laravel/framework/pull/20110))
- ⚠️ Made several method in `ValidatesAttributes` public  ([#20200](https://github.com/laravel/framework/pull/20200))
- ⚠️ Added `errors()` method to `Validator` interface ([#20337](https://github.com/laravel/framework/pull/20337))
- Extend `Exists` and `Unique` rule from `DatabaseRule` class ([#20563](https://github.com/laravel/framework/pull/20563))
- Added `whereIn()` and `whereNotIn()` constraints to `DatabaseRule` ([#20691](https://github.com/laravel/framework/pull/20691), [#20739](https://github.com/laravel/framework/pull/20739), [52d28e3](https://github.com/laravel/framework/commit/52d28e3190833457d4efe811d1e993c1a4bba393))
- Added `date_equals` rule ([#20646](https://github.com/laravel/framework/pull/20646))

### Views
- ⚠️ Camel case variables names passed to views ([#18083](https://github.com/laravel/framework/pull/18083))
- Added pagination template for Semantic UI ([#18463](https://github.com/laravel/framework/pull/18463))
- Allow easier `ViewFactory` overriding ([#20205](https://github.com/laravel/framework/pull/20205), [56f103c](https://github.com/laravel/framework/commit/56f103c69757cc643120a3de9b601262ed1ff2dd))
- Added `View::first()` ([#20695](https://github.com/laravel/framework/pull/20695), [f18318b](https://github.com/laravel/framework/commit/f18318b35b246a7f279781fe7403d137fb55be05))
