# Release Notes

## [Unreleased]

### Fixed
- Fixed deferring write connection ([#16673](https://github.com/laravel/framework/pull/16673))


## v5.2.45 (2016-08-26)

### Fixed
- Revert changes to Eloquent `Builder` that breaks `firstOr*` methods ([#15018](https://github.com/laravel/framework/pull/15018))
- Revert aggregate changes in [#14793](https://github.com/laravel/framework/pull/14793) ([#14994](https://github.com/laravel/framework/pull/14994))


## v5.2.44 (2016-08-23)

### Added
- Added `BelongsToMany::syncWithoutDetaching()` method ([33aee31](https://github.com/laravel/framework/commit/33aee31523b9fc280aced35a5eb5f6b627263b45))
- Added `withoutTrashed()` method to `SoftDeletingScope` ([#14805](https://github.com/laravel/framework/pull/14805))
- Support Flysystem's `disable_asserts` config value ([#14864](https://github.com/laravel/framework/pull/14864))

### Changed
- Support multi-dimensional `$data` arrays in `invalid()` and `valid()` methods ([#14651](https://github.com/laravel/framework/pull/14651))
- Support column aliases in `chunkById()` ([#14711](https://github.com/laravel/framework/pull/14711))
- Re-attempt transaction when encountering a deadlock ([#14930](https://github.com/laravel/framework/pull/14930))

### Fixed
- Only return floats or integers in `aggregate()` ([#14781](https://github.com/laravel/framework/pull/14781))
- Fixed numeric aggregate queries ([#14793](https://github.com/laravel/framework/pull/14793))
- Create new row in `firstOrCreate()` when a model has a mutator ([#14656](https://github.com/laravel/framework/pull/14656))
- Protect against empty paths in the `view:clear` command ([#14812](https://github.com/laravel/framework/pull/14812))
- Convert `$attributes` in `makeHidden()` to array ([#14852](https://github.com/laravel/framework/pull/14852), [#14857](https://github.com/laravel/framework/pull/14857))
- Prevent conflicting class name import to namespace in `ValidatesWhenResolvedTrait` ([#14878](https://github.com/laravel/framework/pull/14878))


## v5.2.43 (2016-08-10)

### Changed
- Throw exception if `$amount` is not numeric in `increment()` and `decrement()` ([915cb84](https://github.com/laravel/framework/commit/915cb843981ad434b10709425d968bf2db37cb1a))


## v5.2.42 (2016-08-08)

### Added
- Allow `BelongsToMany::detach()` to accept a collection ([#14412](https://github.com/laravel/framework/pull/14412))
- Added `whereTime()` and `orWhereTime()` to query builder ([#14528](https://github.com/laravel/framework/pull/14528))
- Added PHP 7.1 support ([#14549](https://github.com/laravel/framework/pull/14549))
- Allow collections to be created from objects that implement `Traversable` ([#14628](https://github.com/laravel/framework/pull/14628))
- Support dot notation in `Request::exists()` ([#14660](https://github.com/laravel/framework/pull/14660))
- Added missing `Model::makeHidden()` method ([#14641](https://github.com/laravel/framework/pull/14641))

### Changed
- Return `true` when `$key` is empty in `MessageBag::has()` ([#14409](https://github.com/laravel/framework/pull/14409))
- Optimized `Filesystem::moveDirectory` ([#14362](https://github.com/laravel/framework/pull/14362))
- Convert `$count` to integer in `Str::plural()` ([#14502](https://github.com/laravel/framework/pull/14502))
- Handle arrays in `validateIn()` method ([#14607](https://github.com/laravel/framework/pull/14607))

### Fixed
- Fixed an issue with `wherePivotIn()` ([#14397](https://github.com/laravel/framework/issues/14397))
- Fixed PDO connection on HHVM ([#14429](https://github.com/laravel/framework/pull/14429))
- Prevent `make:migration` from creating duplicate classes ([#14432](https://github.com/laravel/framework/pull/14432))
- Fixed lazy eager loading issue in `LengthAwarePaginator` collection ([#14476](https://github.com/laravel/framework/pull/14476))
- Fixed plural form of Pokémon ([#14525](https://github.com/laravel/framework/pull/14525))
- Fixed authentication bug in `TokenGuard::validate()` ([#14568](https://github.com/laravel/framework/pull/14568))
- Fix missing middleware parameters when using `authorizeResource()` ([#14592](https://github.com/laravel/framework/pull/14592))

### Removed
- Removed duplicate interface implementation in `Dispatcher` ([#14515](https://github.com/laravel/framework/pull/14515))


## v5.2.41 (2016-07-20)

### Changed
- Run session garbage collection before response is returned ([#14386](https://github.com/laravel/framework/pull/14386))

### Fixed
- Fixed pagination bug introduced in [#14188](https://github.com/laravel/framework/pull/14188) ([#14389](https://github.com/laravel/framework/pull/14389))
- Fixed `median()` issue when collection is out of order ([#14381](https://github.com/laravel/framework/pull/14381))


## v5.2.40 (2016-07-19)

### Added
- Added `--tags` option to `cache:clear` command ([#13927](https://github.com/laravel/framework/pull/13927))
- Added `scopes()` method to Eloquent query builder ([#14049](https://github.com/laravel/framework/pull/14049))
- Added `hasAny()` method to `MessageBag` ([#14151](https://github.com/laravel/framework/pull/14151))
- Allowing passing along transmission options to SparkPost ([#14166](https://github.com/laravel/framework/pull/14166))
- Added `Filesystem::moveDirectory()` ([#14198](https://github.com/laravel/framework/pull/14198))
- Added `increment()` and `decrement()` methods to session store ([#14196](https://github.com/laravel/framework/pull/14196))
- Added `pipe()` method to `Collection` ([#13899](https://github.com/laravel/framework/pull/13899))
- Added additional PostgreSQL operators ([#14224](https://github.com/laravel/framework/pull/14224))
- Support `::` expressions in Blade directive names ([#14265](https://github.com/laravel/framework/pull/14265))
- Added `median()` and `mode()` methods to collections ([#14305](https://github.com/laravel/framework/pull/14305))
- Add `tightenco/collect` to Composer `replace` list ([#14118](https://github.com/laravel/framework/pull/14118), [#14127](https://github.com/laravel/framework/pull/14127))

### Changed
- Don't release jobs that have been reserved too long ([#13833](https://github.com/laravel/framework/pull/13833))
- Throw `Exception` if `Queue` has no encrypter ([#14038](https://github.com/laravel/framework/pull/14038))
- Cast `unique` validation rule `id` to integer ([#14076](https://github.com/laravel/framework/pull/14076))
- Ensure database transaction count is not negative ([#14085](https://github.com/laravel/framework/pull/14085))
- Use `session.lifetime` for CSRF cookie ([#14080](https://github.com/laravel/framework/pull/14080))
- Allow the `shuffle()` method to be seeded ([#14099](https://github.com/laravel/framework/pull/14099))
- Allow passing of multiple keys to `MessageBag::has()` ([a0cd0ae](https://github.com/laravel/framework/commit/a0cd0aea9a475f76baf968ef2f53aeb71fcda4c0))
- Allow model connection in `newFromBuilder()` to be overridden ([#14194](https://github.com/laravel/framework/pull/14194))
- Only load pagination results if `$total` is greater than zero ([#14188](https://github.com/laravel/framework/pull/14188))
- Accept fallback parameter in `UrlGenerator::previous` ([#14207](https://github.com/laravel/framework/pull/14207))
- Only do `use` call if `database` is not empty ([#14225](https://github.com/laravel/framework/pull/14225))
- Removed unnecessary nesting in the `Macroable` trait ([#14222](https://github.com/laravel/framework/pull/14222))
- Refactored `DatabaseQueue::getNextAvailableJob()` ([cffcd34](https://github.com/laravel/framework/commit/cffcd347901617b19e8eca05be55cda280e0d262))
- Look for `getUrl()` method on Filesystem adapter before throwing exception ([#14246](https://github.com/laravel/framework/pull/14246))
- Make `seeIsSelected()` work with `<option>` elements without `value` attributes ([#14279](https://github.com/laravel/framework/pull/14279))
- Improved performance of `Filesystem::sharedGet()` ([#14319](https://github.com/laravel/framework/pull/14319))
- Throw exception if view cache path is empty ([#14291](https://github.com/laravel/framework/pull/14291))
- Changes several validation methods return type from integers to booleans ([#14373](https://github.com/laravel/framework/pull/14373))
- Remove files from input in `withInput()` method ([85249be](https://github.com/laravel/framework/commit/85249beed1e4512d71f7ae52474b9a59a80381d2))

### Fixed
- Require file instance for `dimensions` validation rule ([#14025](https://github.com/laravel/framework/pull/14025))
- Fixes for SQL Server `processInsertGetId()` with ODBC ([#14121](https://github.com/laravel/framework/pull/14121))
- Fixed PostgreSQL `processInsertGetId()` with `PDO::FETCH_CLASS` ([#14115](https://github.com/laravel/framework/pull/14115))
- Fixed `PDO::FETCH_CLASS` support in `Connection::cursor()` ([#14052](https://github.com/laravel/framework/pull/14052))
- Fixed eager loading of multi-level `morphTo` relationships ([#14190](https://github.com/laravel/framework/pull/14190))
- Fixed MySQL multiple-table DELETE ([#14179](https://github.com/laravel/framework/pull/14179))
- Always cast `vendor:publish` tags to array ([#14228](https://github.com/laravel/framework/pull/14228))
- Fixed translation capitalization when replacements are a numerical array ([#14249](https://github.com/laravel/framework/pull/14249))
- Fixed double `urldecode()` on route parameters ([#14370](https://github.com/laravel/framework/pull/14370))

### Removed
- Remove method overwrites in `PostgresGrammar` ([#14372](https://github.com/laravel/framework/pull/14372))


## v5.2.39 (2016-06-17)

### Added
- Added `without()` method to Eloquent query builder ([#14031](https://github.com/laravel/framework/pull/14031))
- Added `keyType` property Eloquent models to set key type cast ([#13985](https://github.com/laravel/framework/pull/13985))
- Added support for mail transport `StreamOptions` ([#13925](https://github.com/laravel/framework/pull/13925))
- Added `validationData()` method to `FormRequest` ([#13914](https://github.com/laravel/framework/pull/13914))

### Changed
- Only `set names` for MySQL connections if `charset` is set in config ([#13930](https://github.com/laravel/framework/pull/13930))
- Support recursive container alias resolution ([#13976](https://github.com/laravel/framework/pull/13976))
- Use late static binding in `PasswordBroker` ([#13975](https://github.com/laravel/framework/pull/13975))
- Make sure Ajax requests are not Pjax requests in `FormRequest` ([#14024](https://github.com/laravel/framework/pull/14024))
- Set existence state of expired database sessions, instead of deleting them ([53c0440](https://github.com/laravel/framework/commit/53c04406baa5f63bbb41127f40afee0a0facadd1))
- Release Beanstalkd jobs before burying them ([#13963](https://github.com/laravel/framework/pull/13963))

### Fixed
- Use `getIncrementing()` method instead of the `$incrementing` attribute ([#14005](https://github.com/laravel/framework/pull/14005))
- Fixed fatal error when `services.json` is empty ([#14030](https://github.com/laravel/framework/pull/14030))


## v5.2.38 (2016-06-13)

### Changed
- Convert multiple `Model::fresh()` arguments to array before passing to `with()` ([#13950](https://github.com/laravel/framework/pull/13950))
- Iterate only through files that contain a namespace in `app:name` command. ([#13961](https://github.com/laravel/framework/pull/13961))

### Fixed
- Close swift mailer connection after sending mail ([#13583](https://github.com/laravel/framework/pull/13583))
- Prevent possible key overlap in `Str::snake` cache ([#13943](https://github.com/laravel/framework/pull/13943))
- Fixed issue when eager loading chained `MorphTo` relationships ([#13967](https://github.com/laravel/framework/pull/13967))
- Delete database session record if it's expired ([09b09eb](https://github.com/laravel/framework/commit/09b09ebad480940f2b49f96bbfbea0647783025e))


## v5.2.37 (2016-06-10)

### Added
- Added `hasArgument()` and `hasOption()` methods to `Command` class ([#13919](https://github.com/laravel/framework/pull/13919))
- Added `$failedId` property to `JobFailed` event ([#13920](https://github.com/laravel/framework/pull/13920))

### Fixed
- Fixed session expiration on several drivers ([0831312](https://github.com/laravel/framework/commit/0831312aec47d904a65039e07574f41ab7492418))


## v5.2.36 (2016-06-06)

### Added
- Allow passing along options to the S3 client ([#13791](https://github.com/laravel/framework/pull/13791))
- Allow nested `WHERE` clauses in `whereHas()` queries ([#13794](https://github.com/laravel/framework/pull/13794))
- Support `DateTime` instances in `Before`/`After` date validation ([#13844](https://github.com/laravel/framework/pull/13844))
- Support queueing collections ([d159f02](https://github.com/laravel/framework/commit/d159f02fe8cb5310b90c73d416a684e4bf51785a))

### Changed
- Reverted SparkPost driver back to `email_rfc822` parameter for simplicity ([#13780](https://github.com/laravel/framework/pull/13780))
- Simplified `Model::__isset()` ([8fb89c6](https://github.com/laravel/framework/commit/8fb89c61c24af905b0b9db4d645d68a2c4a133b9))
- Set exception handler even on non-daemon `queue:work` calls ([d5bbda9](https://github.com/laravel/framework/commit/d5bbda95a6435fa8cb38b8b640440b38de6b7f83))
- Show handler class names in `queue:work` console output ([4d7eb59](https://github.com/laravel/framework/commit/4d7eb59f9813723bab00b4e42ce9885b54e65778))
- Use queue events to update the console output of `queue:work` ([ace7f04](https://github.com/laravel/framework/commit/ace7f04ae579146ca3adf1c5992256c50ddc05a8))
- Made `ResetsPasswords` trait easier to customize ([#13818](https://github.com/laravel/framework/pull/13818))
- Refactored Eloquent relations and scopes ([#13824](https://github.com/laravel/framework/pull/13824), [#13884](https://github.com/laravel/framework/pull/13884), [#13894](https://github.com/laravel/framework/pull/13894))
- Respected `session.http_only` option in `StartSession` middleware ([#13825](https://github.com/laravel/framework/pull/13825))
- Don't return in `ApcStore::forever()` ([#13871](https://github.com/laravel/framework/pull/13871))
- Allow Redis key expiration to be lower than one minute ([#13810](https://github.com/laravel/framework/pull/13810))

### Fixed
- Fixed `morphTo` relations across database connections ([#13784](https://github.com/laravel/framework/pull/13784))
- Fixed `morphTo` relations without soft deletes ([13806](https://github.com/laravel/framework/pull/13806))
- Fixed edge case on `morphTo` relations macro call that only exists on the related model ([#13828](https://github.com/laravel/framework/pull/13828))
- Fixed formatting of `updatedAt` timestamp when calling `touch()` on `BelongsToMany` relation ([#13799](https://github.com/laravel/framework/pull/13799))
- Don't get `$id` from Recaller in `Auth::id()` ([#13769](https://github.com/laravel/framework/pull/13769))
- Fixed `AuthorizesResources` trait ([25443e3](https://github.com/laravel/framework/commit/25443e3e218cce1121f546b596dd70b5fd2fb619))

### Removed
- Removed unused `ArrayStore::putMultiple()` method ([#13840](https://github.com/laravel/framework/pull/13840))


## v5.2.35 (2016-05-30)

### Added
- Added failed login event ([#13761](https://github.com/laravel/framework/pull/13761))

### Changed
- Always cast `FileStore::expiration()` return value to integer ([#13708](https://github.com/laravel/framework/pull/13708))
- Simplified `Container::isCallableWithAtSign()` ([#13757](https://github.com/laravel/framework/pull/13757))
- Pass key to the `Collection::keyBy()` callback ([#13766](https://github.com/laravel/framework/pull/13766))
- Support unlimited log files by setting `app.log_max_files` to `0` ([#13776](https://github.com/laravel/framework/pull/13776))
- Wathan-ize `MorphTo::getEagerLoadsForInstance()` ([#13741](https://github.com/laravel/framework/pull/13741), [#13777](https://github.com/laravel/framework/pull/13777))

### Fixed
- Fixed MySQL JSON boolean binding update grammar ([38acdd8](https://github.com/laravel/framework/commit/38acdd807faec4b85fd47051341ccaf666499551))
- Fixed loading of nested polymorphic relationships ([#13737](https://github.com/laravel/framework/pull/13737))
- Fixed early return in `AuthManager::shouldUse()` ([5b88244](https://github.com/laravel/framework/commit/5b88244c0afd5febe9f54e8544b0870b55ef6cfd))
- Fixed the remaining attempts calculation in `ThrottleRequests` ([#13756](https://github.com/laravel/framework/pull/13756), [#13759](https://github.com/laravel/framework/pull/13759))
- Fixed strict `TypeError` in `AbstractPaginator::url()` ([#13758](https://github.com/laravel/framework/pull/13758))

## v5.2.34 (2016-05-26)

### Added
- Added correct MySQL JSON boolean handling and updating grammar ([#13242](https://github.com/laravel/framework/pull/13242))
- Added `stream` option to mail `TransportManager` ([#13715](https://github.com/laravel/framework/pull/13715))
- Added `when()` method to eloquent query builder ([#13726](https://github.com/laravel/framework/pull/13726))

### Changed
- Catch exceptions in `Worker::pop()` to prevent log spam ([#13688](https://github.com/laravel/framework/pull/13688))
- Use write connection when validating uniqueness ([#13718](https://github.com/laravel/framework/pull/13718))
- Use `withException()` method in `Handler::toIlluminateResponse()` ([#13712](https://github.com/laravel/framework/pull/13712))
- Apply constraints to `morphTo` relationships when using eager loading ([#13724](https://github.com/laravel/framework/pull/13724))
- Use SETs rather than LISTs for storing Redis cache key references ([#13731](https://github.com/laravel/framework/pull/13731))

### Fixed
- Map `destroy` instead of `delete` in `AuthorizesResources` ([#13716](https://github.com/laravel/framework/pull/13716))
- Reverted [#13519](https://github.com/laravel/framework/pull/13519) ([#13733](https://github.com/laravel/framework/pull/13733))


## v5.2.33 (2016-05-25)

### Added
- Allow query results to be traversed using a cursor ([#13030](https://github.com/laravel/framework/pull/13030))
- Added support for log levels ([#13513](https://github.com/laravel/framework/pull/13513))
- Added `inRandomOrder()` method to query builder ([#13642](https://github.com/laravel/framework/pull/13642))
- Added support for custom connection in `PasswordBrokerManager` ([#13646](https://github.com/laravel/framework/pull/13646))
- Allow connection timeouts in `TransportManager` ([#13621](https://github.com/laravel/framework/pull/13621))
- Added missing `$test` argument to `UploadedFile` ([#13656](https://github.com/laravel/framework/pull/13656))
- Added `authenticate()` method to guards ([#13651](https://github.com/laravel/framework/pull/13651))

### Changed
- Use locking to migrate stale jobs ([26a24d6](https://github.com/laravel/framework/commit/26a24d61ced4c5833eba6572d585af90b22fcdb7))
- Avoid `chunkById` duplicating `orders` clause with the same column ([#13604](https://github.com/laravel/framework/pull/13604))
- Fire `RouteMatched` event on `route:list` command ([#13474](https://github.com/laravel/framework/pull/13474))
- Set user resolver for request in `AuthManager::shouldUse()` ([bf5303f](https://github.com/laravel/framework/commit/bf5303fdc919d9d560df128b92a1891dc64ea488))
- Always execute `use` call, unless database is empty ([#13701](https://github.com/laravel/framework/pull/13701), [ef770ed](https://github.com/laravel/framework/commit/ef770edb08f3540aefffd916ae6ef5c8db58f0af))
- Allow `elixir()` `$buildDirectory` to be `null`. ([#13661](https://github.com/laravel/framework/pull/13661))
- Simplified calling `Model::replicate()` with `$except` argument ([#13676](https://github.com/laravel/framework/pull/13676))
- Allow auth events to be serialized ([#13704](https://github.com/laravel/framework/pull/13704))
- Added `for` and `id` attributes to auth scaffold ([#13689](https://github.com/laravel/framework/pull/13689))
- Acquire lock before deleting reserved job ([4b502dc](https://github.com/laravel/framework/commit/4b502dc6eecd80efad01e845469b9a2bac26dae0#diff-b05083dc38b4e45d38d28c676abbad83))

### Fixed
- Prefix timestamps when updating many-to-many relationships ([#13519](https://github.com/laravel/framework/pull/13519))
- Fixed missing wheres defined on the relation when creating the subquery for a relation count ([#13612](https://github.com/laravel/framework/pull/13612))
- Fixed `Model::makeVisible()` when `$visible` property is not empty ([#13625](https://github.com/laravel/framework/pull/13625))
- Fixed PostgreSQL's `Schema::hasTable()` ([#13008](https://github.com/laravel/framework/pull/13008))
- Fixed `url` validation rule when missing trailing slash ([#13700](https://github.com/laravel/framework/pull/13700))


## v5.2.32 (2016-05-17)

### Added
- Allow user to enable/disable foreign key checks dynamically ([#13333](https://github.com/laravel/framework/pull/13333))
- Added `file` validation rule ([#13371](https://github.com/laravel/framework/pull/13371))
- Added `guestMiddleware()` method to get guest middleware with guard parameter ([#13384](https://github.com/laravel/framework/pull/13384))
- Added `Pivot::fromRawAttributes()` to create a new pivot model from raw values returned from a query ([f356419](https://github.com/laravel/framework/commit/f356419fa6f6b6fbc3322ca587b0bc1e075ba8d2))
- Added `Builder::withCount()` to add a relationship subquery count ([#13414](https://github.com/laravel/framework/pull/13414))
- Support `reply_to` field when using SparkPost ([#13410](https://github.com/laravel/framework/pull/13410))
- Added validation rule for image dimensions ([#13428](https://github.com/laravel/framework/pull/13428))
- Added "Generated Columns" support to MySQL grammar ([#13430](https://github.com/laravel/framework/pull/13430))
- Added `Response::throwResponse()` ([#13473](https://github.com/laravel/framework/pull/13473))
- Added `page` parameter to the `simplePaginate()` method ([#13502](https://github.com/laravel/framework/pull/13502))
- Added `whereColumn()` method to Query Builder ([#13549](https://github.com/laravel/framework/pull/13549))
- Allow `File::allFiles()` to show hidden dot files ([#13555](https://github.com/laravel/framework/pull/13555))

### Changed
- Return `null` instead of `0` for a default `BelongsTo` key ([#13378](https://github.com/laravel/framework/pull/13378))
- Avoid useless logical operation ([#13397](https://github.com/laravel/framework/pull/13397))
- Stop using `{!! !!}` for `csrf_field()` ([#13398](https://github.com/laravel/framework/pull/13398))
- Improvements for `SessionGuard` methods `loginUsingId()` and `onceUsingId()` ([#13393](https://github.com/laravel/framework/pull/13393))
- Added Work-around due to lack of `lastInsertId()` for ODBC for MSSQL ([#13423](https://github.com/laravel/framework/pull/13423))
- Ensure `MigrationCreator::create()` receives `$create` as boolean ([#13439](https://github.com/laravel/framework/pull/13439))
- Allow custom validators to be called with out function name ([#13444](https://github.com/laravel/framework/pull/13444))
- Moved the `payload` column of jobs table to the end ([#13469](https://github.com/laravel/framework/pull/13469))
- Stabilized table aliases for self joins by adding count ([#13401](https://github.com/laravel/framework/pull/13401))
- Account for `__isset` changes in PHP 7 ([#13509](https://github.com/laravel/framework/pull/13509))
- Bring back support for `Carbon` instances to `before` and `after` validators ([#13494](https://github.com/laravel/framework/pull/13494))
- Allow method chaining for `MakesHttpRequest` trait ([#13529](https://github.com/laravel/framework/pull/13529))
- Allow `Request::intersect()` to accept argument list ([#13515](https://github.com/laravel/framework/pull/13515))

### Fixed
- Accept `!=` and `<>` as operators while value is `null` ([#13370](https://github.com/laravel/framework/pull/13370))
- Fixed SparkPost BCC issue ([#13361](https://github.com/laravel/framework/pull/13361))
- Fixed fatal error with optional `morphTo` relationship ([#13360](https://github.com/laravel/framework/pull/13360))
- Fixed using `onlyTrashed()` and `withTrashed()` with `whereHas()` ([#13396](https://github.com/laravel/framework/pull/13396))
- Fixed automatic scope nesting ([#13413](https://github.com/laravel/framework/pull/13413))
- Fixed scheduler issue when using `user()` and `withoutOverlapping()` combined ([#13412](https://github.com/laravel/framework/pull/13412))
- Fixed SqlServer grammar issue when table name is equal to a reserved keyword ([#13458](https://github.com/laravel/framework/pull/13458))
- Fixed replacing route default parameters ([#13514](https://github.com/laravel/framework/pull/13514))
- Fixed missing model attribute on `ModelNotFoundException` ([#13537](https://github.com/laravel/framework/pull/13537))
- Decrement transaction count when `beginTransaction()` errors ([#13551](https://github.com/laravel/framework/pull/13551))
- Fixed `seeJson()` issue when comparing two equal arrays ([#13531](https://github.com/laravel/framework/pull/13531))
- Fixed a Scheduler issue where would no longer run in background ([#12628](https://github.com/laravel/framework/issues/12628))
- Fixed sending attachments with SparkPost ([#13577](https://github.com/laravel/framework/pull/13577))


## v5.2.31 (2016-04-27)

### Added
- Added missing suggested dependency `SuperClosure` ([09a793f](https://git.io/vwZx4))
- Added ODBC connection support for SQL Server ([#13298](https://github.com/laravel/framework/pull/13298))
- Added `Request::hasHeader()` method ([#13271](https://github.com/laravel/framework/pull/13271))
- Added `@elsecan` and `@elsecannot` Blade directives ([#13256](https://github.com/laravel/framework/pull/13256))
- Support booleans in `required_if` Validator rule ([#13327](https://github.com/laravel/framework/pull/13327))

### Changed
- Simplified `Translator::parseLocale()` method ([#13244](https://github.com/laravel/framework/pull/13244))
- Simplified `Builder::shouldRunExistsQuery()` method ([#13321](https://github.com/laravel/framework/pull/13321))
- Use `Gate` contract instead of Facade ([#13260](https://github.com/laravel/framework/pull/13260))
- Return result in `SoftDeletes::forceDelete()` ([#13272](https://github.com/laravel/framework/pull/13272))

### Fixed
- Fixed BCC for SparkPost ([#13237](https://github.com/laravel/framework/pull/13237))
- Use Carbon for everything time related in `DatabaseTokenRepository` ([#13234](https://github.com/laravel/framework/pull/13234))
- Fixed an issue with `data_set()` affecting the Validator ([#13224](https://github.com/laravel/framework/pull/13224))
- Fixed setting nested namespaces with `app:name` command ([#13208](https://github.com/laravel/framework/pull/13208))
- Decode base64 encoded keys before using it in `PasswordBrokerManager` ([#13270](https://github.com/laravel/framework/pull/13270))
- Prevented race condition in `RateLimiter` ([#13283](https://github.com/laravel/framework/pull/13283))
- Use `DIRECTORY_SEPARATOR` to create path for migrations ([#13254](https://github.com/laravel/framework/pull/13254))
- Fixed adding implicit rules via `sometimes()` method ([#12976](https://github.com/laravel/framework/pull/12976))
- Fixed `Schema::hasTable()` when using PostgreSQL ([#13008](https://github.com/laravel/framework/pull/13008))
- Allow `seeAuthenticatedAs()` to be called with any user object ([#13308](https://github.com/laravel/framework/pull/13308))

### Removed
- Removed unused base64 decoding from `Encrypter` ([#13291](https://github.com/laravel/framework/pull/13291))


## v5.2.30 (2016-04-19)

### Added
- Added messages and custom attributes to the password reset validation ([#12997](https://github.com/laravel/framework/pull/12997))
- Added `Before` and `After` dependent rules array ([#13025](https://github.com/laravel/framework/pull/13025))
- Exposed token methods to user in password broker ([#13054](https://github.com/laravel/framework/pull/13054))
- Added array support on `Cache::has()` ([#13028](https://github.com/laravel/framework/pull/13028))
- Allow objects to be passed as pipes ([#13024](https://github.com/laravel/framework/pull/13024))
- Adding alias for `FailedJobProviderInterface` ([#13088](https://github.com/laravel/framework/pull/13088))
- Allow console commands registering from `Kernel` class ([#13097](https://github.com/laravel/framework/pull/13097))
- Added the ability to get routes keyed by method ([#13146](https://github.com/laravel/framework/pull/13146))
- Added PostgreSQL specific operators for `jsonb` type ([#13161](https://github.com/laravel/framework/pull/13161))
- Added `makeHidden()` method to the Eloquent collection ([#13152](https://github.com/laravel/framework/pull/13152))
- Added `intersect()` method to `Request` ([#13167](https://github.com/laravel/framework/pull/13167))
- Allow disabling of model observers in tests ([#13178](https://github.com/laravel/framework/pull/13178))
- Allow `ON` clauses on cross joins ([#13159](https://github.com/laravel/framework/pull/13159))

### Changed
- Use relation setter when setting relations ([#13001](https://github.com/laravel/framework/pull/13001))
- Use `isEmpty()` to check for empty message bag in `Validator::passes()` ([#13014](https://github.com/laravel/framework/pull/13014))
- Refresh `remember_token` when resetting password ([#13016](https://github.com/laravel/framework/pull/13016))
- Use multibyte string functions in `Str` class ([#12953](https://github.com/laravel/framework/pull/12953))
- Use CloudFlare CDN and use SRI checking for assets ([#13044](https://github.com/laravel/framework/pull/13044))
- Enabling array on method has() ([#13028](https://github.com/laravel/framework/pull/13028))
- Allow unix timestamps to be numeric in `Validator` ([da62677](https://git.io/vVi3M))
- Reverted forcing middleware uniqueness ([#13075](https://github.com/laravel/framework/pull/13075))
- Forget keys that contain periods ([#13121](https://github.com/laravel/framework/pull/13121))
- Don't limit column selection while chunking by id ([#13137](https://github.com/laravel/framework/pull/13137))
- Prefix table name on `getColumnType()` call ([#13136](https://github.com/laravel/framework/pull/13136))
- Moved ability map in `AuthorizesResources` trait to a method ([#13214](https://github.com/laravel/framework/pull/13214))
- Make sure `unguarded()` does not change state on exception ([#13186](https://github.com/laravel/framework/pull/13186))
- Return `$this` in `InteractWithPages::within()` to allow method chaining ([13200](https://github.com/laravel/framework/pull/13200))

### Fixed
- Fixed a empty value case with `Arr:dot()` ([#13009](https://github.com/laravel/framework/pull/13009))
- Fixed a Scheduler issues on Windows ([#13004](https://github.com/laravel/framework/issues/13004))
- Prevent crashes with bad `Accept` headers ([#13039](https://github.com/laravel/framework/pull/13039), [#13059](https://github.com/laravel/framework/pull/13059))
- Fixed explicit depending rules when the explicit keys are non-numeric ([#13058](https://github.com/laravel/framework/pull/13058))
- Fixed an issue with fluent routes with `uses()` ([#13076](https://github.com/laravel/framework/pull/13076))
- Prevent generating listeners for listeners ([3079175](https://git.io/vVNdg))

### Removed
- Removed unused parameter call in `Filesystem::exists()` ([#13102](https://github.com/laravel/framework/pull/13102))
- Removed duplicate "[y/N]" from confirmable console commands ([#13203](https://github.com/laravel/framework/pull/13203))
- Removed unused parameter in `route()` helper ([#13206](https://github.com/laravel/framework/pull/13206))


## v5.2.29 (2016-04-02)

### Fixed
- Fixed `Arr::get()` when given array is empty ([#12975](https://github.com/laravel/framework/pull/12975))
- Add backticks around JSON selector field names in PostgreSQL query builder ([#12978](https://github.com/laravel/framework/pull/12978))
- Reverted #12899 ([#12991](https://github.com/laravel/framework/pull/12991))


## v5.2.28 (2016-04-01)

### Added
- Added `Authorize` middleware ([#12913](https://git.io/vVLel), [0c48ba4](https://git.io/vVlib), [183f8e1](https://git.io/vVliF))
- Added `UploadedFile::clientExtension()` ([75a7c01](https://git.io/vVO7I))
- Added cross join support for query builder ([#12950](https://git.io/vVZqP))
- Added `ThrottlesLogins::secondsRemainingOnLockout()` ([#12963](https://git.io/vVc1Z), [7c2c098](https://git.io/vVli9))

### Changed
- Optimized validation performance of large arrays ([#12651](https://git.io/v2xhi))
- Never retry database query, if failed within transaction ([#12929](https://git.io/vVYUB))
- Allow customization of email sent by `ResetsPasswords::sendResetLinkEmail()` ([#12935](https://git.io/vVYKE), [aae873e](https://git.io/vVliD))
- Improved file system tests ([#12940](https://git.io/vVsTV), [#12949](https://git.io/vVGjP), [#12970](https://git.io/vVCBq))
- Allowing merging an array of rules ([a5ea1aa](https://git.io/vVli1))
- Consider implicit attributes while guessing column names in validator ([#12961](https://git.io/vVcgA), [a3827cf](https://git.io/vVliX))
- Reverted [#12307](https://git.io/vgQeJ) ([#12928](https://git.io/vVqni))

### Fixed
- Fixed elixir manifest caching to detect different build paths ([#12920](https://git.io/vVtJR))
- Fixed `Str::snake()` to work with UTF-8 strings ([#12923](https://git.io/vVtVp))
- Trim the input name in the generator commands ([#12933](https://git.io/vVY4a))
- Check for non-string values in validation rules ([#12973](https://git.io/vVWew))
- Add backticks around JSON selector field names in MySQL query builder ([#12964](https://git.io/vVc9n))
- Fixed terminable middleware assigned to controller ([#12899](https://git.io/vVTnt), [74b0636](https://git.io/vVliP))


## v5.2.27 (2016-03-29)
### Added
- Allow ignoring an id using an array key in the `unique` validation rule ([#12612](https://git.io/v29rH))
- Added `InteractsWithSession::assertSessionMissing()` ([#12860](https://git.io/vajXr))
- Added `chunkById()` method to query builder for faster chunking of large sets of data ([#12861](https://git.io/vajSd))
- Added Blade `@hasSection` directive to determine whether something can be yielded ([#12866](https://git.io/vVem5))
- Allow optional query builder calls via `when()` method ([#12878](https://git.io/vVflh))
- Added IP and MAC address column types ([#12884](https://git.io/vVJsj))
- Added Collections `union` method for true unions of two collections ([#12910](https://git.io/vVIzh))

### Changed
- Allow array size validation of implicit attributes ([#12640](https://git.io/v2Nzl))
- Separated logic of Blade `@push` and `@section` directives ([#12808](https://git.io/vaD8n))
- Ensured that middleware is applied only once ([#12911](https://git.io/vVIr2))

### Fixed
- Reverted improvements to Redis cache tagging ([#12897](https://git.io/vVUD5))
- Removed route group from `make:auth` stub ([#12903](https://git.io/vVkHI))


## v5.2.26 (2016-03-25)
### Added
- Added support for Base64 encoded `Encrypter` keys ([370ae34](https://git.io/vapFX))
- Added `EncryptionServiceProvider::getEncrypterForKeyAndCipher()` ([17ce4ed](https://git.io/vahbo))
- Added `Application::environmentFilePath()` ([370ae34](https://git.io/vapFX))

### Fixed
- Fixed mock in `ValidationValidatorTest::testValidateMimetypes()` ([7f35988](https://git.io/vaxfB))


## v5.2.25 (2016-03-24)
### Added
- Added bootstrap Composer scripts to avoid loading of config/compiled files ([#12827](https://git.io/va5ja))

### Changed
- Use `File::guessExtension()` instead of `UploadedFile::guessClientExtension()` ([87e6175](https://git.io/vaAxC))

### Fixed
- Fix an issue with explicit custom validation attributes ([#12822](https://git.io/vaQbD))
- Fix an issue where a view would run the `BladeEngine` instead of the `PhpEngine` ([#12830](https://git.io/vad1X))
- Prevent wrong auth driver from causing unexpected end of execution ([#12821](https://git.io/vajFq))
