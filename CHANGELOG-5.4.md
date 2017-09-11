# Release Notes for 5.4.x

## v5.4.36 (2017-08-30)

### Added
- Added MP3 to `Testing/MimeType::$mimes` ([#20745](https://github.com/laravel/framework/pull/20745))

### Changed
- Mailables that defined a `$delay` property will honor it ([#20717](https://github.com/laravel/framework/pull/20717))

### Fixed
- Fixed route URLs building from artisan commands ([#20788](https://github.com/laravel/framework/pull/20788))


## v5.4.35 (2017-08-24)

### Fixed
- Fixed breaking change in `FactoryBuilder` ([#20727](https://github.com/laravel/framework/pull/20727))


## v5.4.34 (2017-08-23)

### Added
- Added `Str::start()` and `str_start()` helper ([#20569](https://github.com/laravel/framework/pull/20569))
- Added `orDoesntHave()` and `orWhereDoesntHave()` to `QueriesRelationships` ([#20685](https://github.com/laravel/framework/pull/20685))
- Added support for callables in model factory attributes ([#20692](https://github.com/laravel/framework/pull/20692))

### Changed
- Return the model instance from `Model::refresh()` ([#20657](https://github.com/laravel/framework/pull/20657))
- Use `self::$verbs` in `Router::any()` ([#20698](https://github.com/laravel/framework/pull/20698))

### Fixed
- Fixed duplicate user model import in `make:policy` ([#20645](https://github.com/laravel/framework/pull/20645), [48f5f23](https://github.com/laravel/framework/commit/48f5f23fd8615f48f2aee27a301c1f2f1505bdfb))
- Fixed PHP 7.2 incompatibility in `Builder::mergeWheres()` ([#20635](https://github.com/laravel/framework/pull/20635))
- Fixed issue in `RateLimiter` ([#20684](https://github.com/laravel/framework/pull/20684))
- Fixed success message after password reset ([#20707](https://github.com/laravel/framework/pull/20707))
- Fail job only if it didn't fail already ([#20654](https://github.com/laravel/framework/pull/20654))


## v5.4.33 (2017-08-14)

### Added
- Show error message if a reverted migration is not found ([#20499](https://github.com/laravel/framework/pull/20499), [a895b1e](https://github.com/laravel/framework/commit/a895b1eb0e50683c4583c24bb17b3f8d9e8127ab))

### Changed
- Moved `tap()` method from `Builder` to `BuildsQueries` ([#20384](https://github.com/laravel/framework/pull/20384))
- Made Blade `or` operator case-insensitive ([#20425](https://github.com/laravel/framework/pull/20425))
- Support `$amount = 0` in `Arr::random()` ([#20439](https://github.com/laravel/framework/pull/20439))
- Reverted `doctrine/inflector` version change made in v5.4.31 ([#20227](https://github.com/laravel/framework/pull/20227))

### Fixed
- Fixed bug when using empty values in `SQLiteGrammar::compileInsert()` ([#20424](https://github.com/laravel/framework/pull/20424))
- Fixed `$boolean` parameter being ignored in `Builder::addArrayOfWheres()` ([#20553](https://github.com/laravel/framework/pull/20553))
- Fixed `JoinClause::whereIn()` when using a subquery ([#20453](https://github.com/laravel/framework/pull/20453))
- Reset day parameter when using `Y-m` with `date_format` rule ([#20566](https://github.com/laravel/framework/pull/20566))


## v5.4.32 (2017-08-03)

### Added
- Added `FilesystemAdapter::path()` method  ([#20395](https://github.com/laravel/framework/pull/20395))

### Changed
- Allow `Collection::random()` to return `0` items ([#20396](https://github.com/laravel/framework/pull/20396), [#20402](https://github.com/laravel/framework/pull/20402))
- Accept options on `FilesystemAdapter::temporaryUrl()` ([#20394](https://github.com/laravel/framework/pull/20394))
- Sync `withoutOverlapping` method on `Event` and `CallbackEvent` ([#20389](https://github.com/laravel/framework/pull/20389))
- Prevent PHP file uploads by default unless explicitly allowed ([#20392](https://github.com/laravel/framework/pull/20392), [#20400](https://github.com/laravel/framework/pull/20400))
- Allow other filesystem adapter to implement `temporaryUrl()` ([#20398](https://github.com/laravel/framework/pull/20398))

### Fixed
- Reverted breaking change on `BelongsToMany::create()` ([#20407](https://github.com/laravel/framework/pull/20407))


## v5.4.31 (2017-08-02)

### Added
- Added `Blueprint::unsignedDecimal()` method ([#20243](https://github.com/laravel/framework/pull/20243), [3b4483d](https://github.com/laravel/framework/commit/3b4483d1ad885ca0943558b896c6f27f937c193a), [06dcaaa](https://github.com/laravel/framework/commit/06dcaaafe3add4a1bd2a41610278fc7f3d8c43df))
- Added `Relation::getMorphedModel()` method ([#20244](https://github.com/laravel/framework/pull/20244))
- Added `Model::isNot()` method ([#20354](https://github.com/laravel/framework/pull/20354))
- Added `FilesystemAdapter::temporaryUrl()` method ([#20375](https://github.com/laravel/framework/pull/20375), [09cfd7f](https://github.com/laravel/framework/commit/09cfd7f5d2982f4faca915125d88eb4552ca3db4))
- Added `Request::userAgent()` method ([#20367](https://github.com/laravel/framework/pull/20367))

### Changed
- Renamed `MakeAuthCommand` to `AuthMakeCommand` ([#20216](https://github.com/laravel/framework/pull/20216))
- Don't use `asset()` helper inside `mix()` ([#20197](https://github.com/laravel/framework/pull/20197))
- Removed `array` type-hint in `Builder::orWhereRaw()` signature ([#20234](https://github.com/laravel/framework/pull/20234))
- Added empty array default to `$attributes` on `BelongsToMany::create()` ([#20321](https://github.com/laravel/framework/pull/20321))
- Prepare for PHP 7.2 ([#20258](https://github.com/laravel/framework/pull/20258), [#20330](https://github.com/laravel/framework/pull/20330), [#20336](https://github.com/laravel/framework/pull/20336), [#20378](https://github.com/laravel/framework/pull/20378))
- Use `unsignedTinyInteger()` in `jobs.stub` ([#20382](https://github.com/laravel/framework/pull/20382))

### Fixed
- Make sure `Model::getDates()` returns unique columns ([#20193](https://github.com/laravel/framework/pull/20193))
- Fixed pulled `doctrine/inflector` version ([#20227](https://github.com/laravel/framework/pull/20227))
- Fixed issue with `chunkById()` when `orderByRaw()` is used ([#20236](https://github.com/laravel/framework/pull/20236))
- Terminate user defined database connections after rollback during testing ([#20340](https://github.com/laravel/framework/pull/20340))


## v5.4.30 (2017-07-19)

### Fixed
- Handle a non-existing key in `ArrayStore` ([#20156](https://github.com/laravel/framework/pull/20156))
- Fixed bug `@guest` and `@auth` directives ([#20166](https://github.com/laravel/framework/pull/20166), [b164e45](https://github.com/laravel/framework/commit/b164e4552517b6126eac4dc77e276131b835b784))


## v5.4.29 (2017-07-19)

### Added
- Added `ManagesFrequencies::twiceMonthly()` method ([#19874](https://github.com/laravel/framework/pull/19874))
- Added `RouteCollection::getRoutesByName()` method ([#19901](https://github.com/laravel/framework/pull/19901))
- Added `$expiresAt` parameter to `CallbackEvent::withoutOverlapping()` ([#19861](https://github.com/laravel/framework/pull/19861))
- Support keeping old files when testing uploads ([#19859](https://github.com/laravel/framework/pull/19859))
- Added `--force` option to `make:mail`, `make:model` and `make:notification` ([#19932](https://github.com/laravel/framework/pull/19932))
- Added support for PostgreSQL deletes with `USES` clauses ([#20062](https://github.com/laravel/framework/pull/20062), [f94fc02](https://github.com/laravel/framework/commit/f94fc026c64471ca5a5b42919c9b5795021d783c))
- Added support for CC and BBC on mail notifications ([#20093](https://github.com/laravel/framework/pull/20093))
- Added Blade `@auth` and `@guest` directive ([#20087](https://github.com/laravel/framework/pull/20087), [#20114](https://github.com/laravel/framework/pull/20114))
- Added option to configure MARS on SqlServer connections ([#20113](https://github.com/laravel/framework/pull/20113), [c2c917c](https://github.com/laravel/framework/commit/c2c917c5f773d3df7f59242768f921af95309bcc))

### Changed
- Support object items in `Arr::pluck()` ([#19838](https://github.com/laravel/framework/pull/19838), [#19845](https://github.com/laravel/framework/pull/19845))
- `MessageBag` interface now extends `Arrayable` ([#19849](https://github.com/laravel/framework/pull/19849))
- Made `Blueprint` macroable ([#19862](https://github.com/laravel/framework/pull/19862))
- Improved performance for `Arr::crossJoin()` ([#19864](https://github.com/laravel/framework/pull/19864))
- Use the correct `User` model namespace for new policies ([#19965](https://github.com/laravel/framework/pull/19965), [a7094c2](https://github.com/laravel/framework/commit/a7094c2e68a6eb9768462ce9b8d26fec00e9ba65))
- Consider scheduled event timezone in `inTimeInterval()` ([#19959](https://github.com/laravel/framework/pull/19959))
- Render exception if handler can't report it ([#19977](https://github.com/laravel/framework/pull/19977))
- Made `MakesHttpRequests::withServerVariables()` public ([#20086](https://github.com/laravel/framework/pull/20086))
- Invalidate session instead of regenerating it when logging out ([#20107](https://github.com/laravel/framework/pull/20107))
- Improved `InvalidPayloadException` error message ([#20143](https://github.com/laravel/framework/pull/20143))

### Fixed
- Don't re-escape a `View` instance passed as the default value to `@yield` or `@section` directives ([#19884](https://github.com/laravel/framework/pull/19884))
- Make sure migration file is loaded before trying to rollback ([#19922](https://github.com/laravel/framework/pull/19922))
- Fixed caching issue in `mix()` ([#19968](https://github.com/laravel/framework/pull/19968))
- Signal alarm after timeout passes ([#19978](https://github.com/laravel/framework/pull/19978))


## v5.4.28 (2017-06-30)

### Added
- Added `avg()` and `average()` as higher order proxies ([#19628](https://github.com/laravel/framework/pull/19628))
- Added `fresh()` method to Eloquent collection ([#19616](https://github.com/laravel/framework/pull/19616), [#19671](https://github.com/laravel/framework/pull/19671))
- Added ability to remove a global scope with another global scope ([#19657](https://github.com/laravel/framework/pull/19657))
- Added `Collection::intersectKey()` method ([#19683](https://github.com/laravel/framework/pull/19683))
- Support setting queue name via `broadcastQueue()` method ([#19703](https://github.com/laravel/framework/pull/19703), [#19708](https://github.com/laravel/framework/pull/19708))
- Support default return on `BelongsTo` relations ([#19733](https://github.com/laravel/framework/pull/19733), [#19788](https://github.com/laravel/framework/pull/19788), [1137d86](https://github.com/laravel/framework/commit/1137d860d8ef009630ae4951ea6323f552571268), [ed0182b](https://github.com/laravel/framework/commit/ed0182bb49008032b02649f2fa9ff644b086deac))
- Added `unless()` method to query builder and collection ([#19738](https://github.com/laravel/framework/pull/19738), [#19740](https://github.com/laravel/framework/pull/19740))
- Added `array_random()` helper ([#19741](https://github.com/laravel/framework/pull/19741), [#19818](https://github.com/laravel/framework/pull/19818), [#19826](https://github.com/laravel/framework/pull/19826))
- Support multiple manifest files on `mix()` ([#19764](https://github.com/laravel/framework/pull/19764))

### Changed
- Escape default value passed to `@yield` directive ([#19643](https://github.com/laravel/framework/pull/19643))
- Support passing multiple fields to `different` validation rule ([#19637](https://github.com/laravel/framework/pull/19637))
- Only dispatch the `MessageSent` event if mails should be sent ([#19690](https://github.com/laravel/framework/pull/19690))
- Removed duplicate `/` from `public_path()` ([#19731](https://github.com/laravel/framework/pull/19731))
- Made `ThrottlesLogins` more customizable ([#19787](https://github.com/laravel/framework/pull/19787))
- Support PostgreSQL insert statements with `DEFAULT VALUES` ([#19804](https://github.com/laravel/framework/pull/19804))

### Fixed
- Fixed `BelongsTo` bug with incrementing keys ([#19631](https://github.com/laravel/framework/pull/19631))
- Fixed PDO return value bug in `unprepared()` ([#19667](https://github.com/laravel/framework/pull/19667))
- Don't use `event()` helper in `Http\Kernel` ([#19688](https://github.com/laravel/framework/pull/19688))
- Detect lock wait timeout as deadlock ([#19749](https://github.com/laravel/framework/pull/19749))
- Improved escaping special characters in MySQL comments ([#19798](https://github.com/laravel/framework/pull/19798))
- Fixed passing email as string to `Event::emailOutputTo()` ([#19802](https://github.com/laravel/framework/pull/19802))
- Fixed `withoutOverlapping()` not creating mutex ([#19834](https://github.com/laravel/framework/pull/19834))

### Removed
- Removed `role` attribute from forms in stubs ([#19792](https://github.com/laravel/framework/pull/19792))


## v5.4.27 (2017-06-15)

### Added
- Added `Collection::diffAssoc()` method ([#19604](https://github.com/laravel/framework/pull/19604))

### Changed
- Updated PHPUnit whitelist ([#19609](https://github.com/laravel/framework/pull/19609))

### Fixed
- Update timestamps on soft delete only when they are used ([#19627](https://github.com/laravel/framework/pull/19627))


## v5.4.26 (2017-06-13)

### Added
- Added `Event::nextRunDate()` method ([#19537](https://github.com/laravel/framework/pull/19537), [09dd336](https://github.com/laravel/framework/commit/09dd336b5146afa9278064c2f7cd901f55e10fc0))
- Added null safe operator `<=>` to query builder operators list ([#19539](https://github.com/laravel/framework/pull/19539))
- Added `Macroable` trait to `RequestGuard` ([#19569](https://github.com/laravel/framework/pull/19569))

### Changed
- Touch `updated_at` timestamp when soft deleting ([#19538](https://github.com/laravel/framework/pull/19538))
- Accept argument list in `Rule::in()` and `Rule::notIn()` ([#19555](https://github.com/laravel/framework/pull/19555))
- Support checking for strings job names using `QueueFake` ([#19575](https://github.com/laravel/framework/pull/19575))
- Improved image ratio validation precision ([#19542](https://github.com/laravel/framework/pull/19542))

### Fixed
- Resume scheduled task if an error occurs ([#19419](https://github.com/laravel/framework/pull/19419))
- Decode HTML entities in plain text emails ([#19518](https://github.com/laravel/framework/pull/19518))
- Added missing locales to `MessageSelector::getPluralIndex()` ([#19562](https://github.com/laravel/framework/pull/19562))
- Use strict check when object is passed to `Collection::contains()` ([#19568](https://github.com/laravel/framework/pull/19568))
- Fixed jobs with a timeout of `0` ([#19586](https://github.com/laravel/framework/pull/19586))
- Never pass `Throwable` to `stopWorkerIfLostConnection()` ([#19591](https://github.com/laravel/framework/pull/19591))


## v5.4.25 (2017-06-07)

### Added
- Added `Macroable` trait to `FactoryBuilder` ([#19425](https://github.com/laravel/framework/pull/19425))
- Allow a plain text alternative view when using markdown within Mailables ([#19436](https://github.com/laravel/framework/pull/19436), [ad2eaf7](https://github.com/laravel/framework/commit/ad2eaf72d7fc003a98215d4a373ea603658c646a))
- Added nested transactions support for SqlServer ([#19439](https://github.com/laravel/framework/pull/19439))

### Changed
- Moved `env()` helper to Support component ([#19409](https://github.com/laravel/framework/pull/19409))
- Prevent `BadMethodCallException` in `RedirectResponse::withErrors()` ([#19426](https://github.com/laravel/framework/pull/19426))
- Suppress error if calling `Str::replaceFirst()` with an empty search ([#19427](https://github.com/laravel/framework/pull/19427))
- Removed the `callable` type hint for `array_sort()` ([#19483](https://github.com/laravel/framework/pull/19483))
- Return the used traits from `TestCase::setUpTraits()` ([#19486](https://github.com/laravel/framework/pull/19486))

### Fixed
- Fixes and optimizations for `Str::after()` ([#19428](https://github.com/laravel/framework/pull/19428))
- Fixed queue size when using Beanstalkd driver ([#19465](https://github.com/laravel/framework/pull/19465))
- Check if a mutex can be created before running the callback task in `CallbackEvent::run()` ([#19466](https://github.com/laravel/framework/pull/19466))
- Flip expected and actual value on `TestResponse::assertCookie()` ([#19495](https://github.com/laravel/framework/pull/19495))
- Fixed undefined variable error in `Mailable` class ([#19504](https://github.com/laravel/framework/pull/19504))
- Prevent error notice when `database.collation` is not set ([#19507](https://github.com/laravel/framework/pull/19507))


## v5.4.24 (2017-05-30)

### Added
- Support magic controller methods ([#19168](https://github.com/laravel/framework/pull/19168))
- Added `Gate` resources ([#19124](https://github.com/laravel/framework/pull/19124))
- Added `Request::routeIs()` method ([#19202](https://github.com/laravel/framework/pull/19202), [26681eb](https://github.com/laravel/framework/commit/26681eb1c8ba35a0129ad47d4f0f03af9c2baa45))
- Route `Route::isName()` shorthand method ([#19227](https://github.com/laravel/framework/pull/19227))
- Added support for custom columns in `softDeletes()` method ([#19203](https://github.com/laravel/framework/pull/19203))
- Added `ManagesLayouts::getSection()` method ([#19213](https://github.com/laravel/framework/pull/19213))
- Added `Model::refresh()` shorthand ([#19174](https://github.com/laravel/framework/pull/19174))
- Added `Container::forgetExtenders()` method ([#19269](https://github.com/laravel/framework/pull/19269), [7c17bf5](https://github.com/laravel/framework/commit/7c17bf540f37f8b4667be5332c94d7423780ca83))
- Added `Filesystem::hash()` method ([#19256](https://github.com/laravel/framework/pull/19256))
- Added `TestResponse::assertViewIs()` method ([#19291](https://github.com/laravel/framework/pull/19291))
- Added `path` to `Paginator` ([#19314](https://github.com/laravel/framework/pull/19314))
- Added `Collection::concat()` method ([#19318](https://github.com/laravel/framework/pull/19318), [0f5337f](https://github.com/laravel/framework/commit/0f5337f854ecdd722e7e289ff58cc252337e7a9d))
- Added `make()` method to `HasOneOrMany` and `MorphOneOrMany` relations ([#19307](https://github.com/laravel/framework/pull/19307))
- Added `str_after()` helper function ([#19357](https://github.com/laravel/framework/pull/19357))
- Added `Router::apiResource()` method ([#19347](https://github.com/laravel/framework/pull/19347))

### Changed
- Move `$sizeRules` and `$numericRules` properties from `FormatsMessages` to `Validator` ([dc7e7cb](https://github.com/laravel/framework/commit/dc7e7cb26500fcba91e7e32762e367d59b12913b))
- Allows calls to `Collection::times()` without the `$callback` parameter ([#19278](https://github.com/laravel/framework/pull/19278))
- Don't ignore jobs with a timeout of `0` ([#19266](https://github.com/laravel/framework/pull/19266))
- Resolve database paginators from the container ([#19328](https://github.com/laravel/framework/pull/19328))
- Added `news` to `Pluralizer::$uncountable()` ([#19353](https://github.com/laravel/framework/pull/19353))
- Switched to using `app()->getLocale()` in `app.stub` ([#19405](https://github.com/laravel/framework/pull/19405))

### Fixed
- Fixed `Container::makeWith()` not using parameters when resolving interfaces ([#19178](https://github.com/laravel/framework/pull/19178))
- Stop validating Memcached connection ([#19192](https://github.com/laravel/framework/pull/19192))
- Fixed the position of `bound()` in `Container::instance()` ([#19207](https://github.com/laravel/framework/pull/19207))
- Prevent applying global scopes on the factory while setting the connection ([#19258](https://github.com/laravel/framework/pull/19258))
- Fixed database connection issue in queue worker ([#19263](https://github.com/laravel/framework/pull/19263))
- Don't use HTML comments in notification email template ([#19289](https://github.com/laravel/framework/pull/19289))
- Fire rebinding callback when using `bind()` method to bind abstract ([#19288](https://github.com/laravel/framework/pull/19288))
- Return `0` from `callScope()` if `$query->wheres` is `null` ([#19381](https://github.com/laravel/framework/pull/19381))


## v5.4.23 (2017-05-11)

### Added
- Added `Gate::abilities()` accessor ([#19143](https://github.com/laravel/framework/pull/19143), [e9e34b5](https://github.com/laravel/framework/commit/e9e34b5acd3feccec5ffe3ff6d84ff9009ff2a7a))
- Added ability to eager load counts via `$withCount` property ([#19154](https://github.com/laravel/framework/pull/19154))

### Fixed
- Fixed inversion of expected and actual on assertHeader ([#19110](https://github.com/laravel/framework/pull/19110))
- Fixed filesystem bug in `Filesystem::files()` method on Windows ([#19157](https://github.com/laravel/framework/pull/19157))
- Fixed bug in `Container::build()` ([#19161](https://github.com/laravel/framework/pull/19161), [bf669e1](https://github.com/laravel/framework/commit/bf669e16d61ef0225b86adc781ddcc752aafd62b))

### Removed
- Removed `window.Laravel` object ([#19135](https://github.com/laravel/framework/pull/19135))


## v5.4.22 (2017-05-08)

### Added
- Support dynamic number of keys in `MessageBag::hasAny()` ([#19002](https://github.com/laravel/framework/pull/19002))
- Added `Seeder::callSilent()` method ([#19007](https://github.com/laravel/framework/pull/19007))
- Add `make()` method to Eloquent query builder ([#19015](https://github.com/laravel/framework/pull/19015))
- Support `Arrayable` on Eloquent's `find()` method ([#19019](https://github.com/laravel/framework/pull/19019))
- Added `SendsPasswordResetEmails::validateEmail()` method ([#19042](https://github.com/laravel/framework/pull/19042))
- Allow factory attributes to be factory instances themselves ([#19055](https://github.com/laravel/framework/pull/19055))
- Implemented `until()` method on `EventFake` ([#19062](https://github.com/laravel/framework/pull/19062))
- Added `$encoding` parameter to `Str::length()` ([#19047](https://github.com/laravel/framework/pull/19047), [#19079](https://github.com/laravel/framework/pull/19079))

### Changed
- Throw exception when invalid first argument is passed to `cache()` helper ([d9459b2](https://github.com/laravel/framework/commit/d9459b2f8bec4a807e7ba2b3301de4c5248aa933))
- Use `getAuthIdentifierName()` in `Authenticatable::getAuthIdentifier()` ([#19038](https://github.com/laravel/framework/pull/19038))
- Clone queries without order by for aggregates ([#19064](https://github.com/laravel/framework/pull/19064))
- Force host on password reset notification ([cef1055](https://github.com/laravel/framework/commit/cef10551820530632a86fa6f1306fee95c5cac43))

### Fixed
- Set data key when testing file uploads in nested array ([#18954](https://github.com/laravel/framework/pull/18954))
- Fixed a bug related to sub select queries and extra select statements ([#19013](https://github.com/laravel/framework/pull/19013))
- Resolve aliases from container when using parameters ([#19071](https://github.com/laravel/framework/pull/19071))
- Stop worker if database disconnect occurred ([#19080](https://github.com/laravel/framework/pull/19080), [583b1b8](https://github.com/laravel/framework/commit/583b1b885bbc6883073baa1a0417fffcdbe5ebed))
- Fixed internal call to `assertJson()` in `assertJsonStructure()` ([#19090](https://github.com/laravel/framework/pull/19090))


## v5.4.21 (2017-04-28)

### Added
- Support conditional broadcasting ([#18970](https://github.com/laravel/framework/pull/18970), [2665d9b](https://github.com/laravel/framework/commit/2665d9b9a49633d73c71db735c2597c8b960c985))

### Fixed
- Reverted queue prefix option [#18860](https://github.com/laravel/framework/pull/18860) ([#18987](https://github.com/laravel/framework/pull/18987))
- Return `null` if key is not found from `RedisStore:many()` ([#18984](https://github.com/laravel/framework/pull/18984))


## v5.4.20 (2017-04-27)

### Added
- Added higher order tap ([3abc4fb](https://github.com/laravel/framework/commit/3abc4fb90fe59a90c2d8cccd27e310b20e5e2631))
- Added `Collection::mapToGroups()` ([#18949](https://github.com/laravel/framework/pull/18949))
- Added `FactoryBuilder::lazy()` method ([#18823](https://github.com/laravel/framework/pull/18823))
- Support Redis Sentinel configuration ([#18850](https://github.com/laravel/framework/pull/18850))
- Added `queue.prefix` option ([#18860](https://github.com/laravel/framework/pull/18860), [8510bf9](https://github.com/laravel/framework/commit/8510bf9986fffd8af58d288a297de573e78d97a5))
- Allow `getDisplayableAttribute()` to be used in custom replacers ([#18895](https://github.com/laravel/framework/pull/18895))
- Added `resourceMethodsWithoutModels()` method to `AuthorizesRequests` ([#18916](https://github.com/laravel/framework/pull/18916), [#18964](https://github.com/laravel/framework/pull/18964))
- Added name to `home` route ([#18942](https://github.com/laravel/framework/pull/18942))

### Changed
- Return `PendingDispatch` for `Kernel::queue()` ([51647eb](https://github.com/laravel/framework/commit/51647eb701307e7682f7489b605a146e750abf0f))
- Made `RedisManager::resolve()` public ([#18830](https://github.com/laravel/framework/pull/18830), [eb9b99d](https://github.com/laravel/framework/commit/eb9b99dfe80ca266bf675e8aaa3fdfdf6c4a69f3))
- Changed email body color to match wrapper color ([#18824](https://github.com/laravel/framework/pull/18824))
- Break and hyphenate long words in emails ([#18827](https://github.com/laravel/framework/pull/18827))
- Force database migration to use the write PDO ([#18898](https://github.com/laravel/framework/pull/18898))
- Support `JSON_PARTIAL_OUTPUT_ON_ERROR` on `JsonResponse` ([#18917](https://github.com/laravel/framework/pull/18917), [db5f011](https://github.com/laravel/framework/commit/db5f011d8ba9d0bcb5d768bea9d94688739f5b4c))

### Fixed
- Set connection on model factory ([#18846](https://github.com/laravel/framework/pull/18846), [95a0663](https://github.com/laravel/framework/commit/95a06638633359965961a11ab05967d32351cfdf))
- Fixed route parameter binding for routes with leading slashes ([#18855](https://github.com/laravel/framework/pull/18855))
- Don't call `cleanParameterBag()` twice during JSON request ([#18840](https://github.com/laravel/framework/pull/18840))
- Prevent exception in `getActualClassNameForMorph()` when morph map is `null` ([#18921](https://github.com/laravel/framework/pull/18921))
- Use protocol-relative URL in `mix()` helper ([#18943](https://github.com/laravel/framework/pull/18943))
- Cast `$viaChannels` to array ([#18960](https://github.com/laravel/framework/pull/18960))


## v5.4.19 (2017-04-16)

### Added
- Added ability to send `link_names` parameter in Slack notification ([#18765](https://github.com/laravel/framework/pull/18765))
- Added `Mailable::hasFrom()` method ([#18790](https://github.com/laravel/framework/pull/18790))

### Changed
- Made `Mailer` macroable ([#18763](https://github.com/laravel/framework/pull/18763))
- Made `SessionGuard` macroable ([#18796](https://github.com/laravel/framework/pull/18796))
- Improved queue worker output ([#18773](https://github.com/laravel/framework/pull/18773))
- Added `newModelInstance()` method to Eloquent Builder ([#18775](https://github.com/laravel/framework/pull/18775))
- Use assertions instead of exceptions in `MocksApplicationServices` ([#18774](https://github.com/laravel/framework/pull/18774))

### Fixed
- Fixed memory issue in `Container` ([#18812](https://github.com/laravel/framework/pull/18812))
- Set database connection while retrieving models ([#18769](https://github.com/laravel/framework/pull/18769))


## v5.4.18 (2017-04-10)

### Added
- Added `assertSuccessful()` and `assertRedirect()` to `TestResponse` ([#18629](https://github.com/laravel/framework/pull/18629))
- Added `assertSeeText()` and `assertDontSeeText()` to `TestResponse` ([#18690](https://github.com/laravel/framework/pull/18690))
- Added `assertJsonMissing()` to `TestResponse` ([#18721](https://github.com/laravel/framework/pull/18721), [786b782](https://github.com/laravel/framework/commit/786b7821e0009a4288bff032443f2b1a273ee459))
- Added support for attaching an image to Slack attachments `$attachment->image($url)`([#18664](https://github.com/laravel/framework/pull/18664))
- Added `Validator::extendDependent()` to allow adding custom rules that depend on other fields ([#18654](https://github.com/laravel/framework/pull/18654))
- Added support for `--parent` option on `make:controller` ([#18606](https://github.com/laravel/framework/pull/18606))
- Added `MessageSent` event to `Mailer` ([#18744](https://github.com/laravel/framework/pull/18744), [6c5f3a4](https://github.com/laravel/framework/commit/6c5f3a47c6308d1d4f2f5912c34cb85f57f0aeed))

### Changed
- Don't trim leading slashes on local filesystem base URLs ([acd66fe](https://github.com/laravel/framework/commit/acd66fef646fd5f7ca54380d55bc8e2e2cbe64b6))
- Accept variable on `@empty()` directive ([#18738](https://github.com/laravel/framework/pull/18738))
- Added `string` validation rules to `AuthenticatesUsers` ([#18746](https://github.com/laravel/framework/pull/18746))

### Fixed
- Fixed an issue with `Collection::groupBy()` when the provided value is a boolean ([#18674](https://github.com/laravel/framework/pull/18674))
- Bring back an old behaviour in resolving controller method dependencies ([#18646](https://github.com/laravel/framework/pull/18646))
- Fixed job release when exception occurs ([#18737](https://github.com/laravel/framework/pull/18737))
- Fixed eloquent `increment()` and `decrement()` update attributes ([#18739](https://github.com/laravel/framework/pull/18739), [1728a88](https://github.com/laravel/framework/commit/1728a887e450f997a0dcde2ef84be2947b05af42))


## v5.4.17 (2017-04-03)

### Added
- Added `getManager()` and `setManager()` to queue worker ([#18452](https://github.com/laravel/framework/pull/18452))
- Added support for Pheanstalk's `$timeout` and `$persistent` options ([#18448](https://github.com/laravel/framework/pull/18448))
- Added `Collection::times()` method ([#18457](https://github.com/laravel/framework/pull/18457))
- Added PostgreSQL's `REAL` data type ([#18513](https://github.com/laravel/framework/pull/18513))
- Added `flatMap` to collection higher order proxies ([#18529](https://github.com/laravel/framework/pull/18529))
- Support multiple `--path` parameters with `migrate:reset` ([#18540](https://github.com/laravel/framework/pull/18540))
- Store SparkPost `Transmission-ID` in the header after sending message ([#18594](https://github.com/laravel/framework/pull/18594))

### Changed
- Check for `Htmlable` instead of `HtmlString` in `Mailer::renderView()` ([#18459](https://github.com/laravel/framework/pull/18459), [da7b006](https://github.com/laravel/framework/commit/da7b006d8b236d8b29adb5f5f2696f1c2ec3e999))
- Added mutex for schedule events ([#18295](https://github.com/laravel/framework/pull/18295), [ae2eb1f](https://github.com/laravel/framework/commit/ae2eb1f498aa6c2e6c45040d26fb9502eabab535))
- Don't use helper functions in service providers ([#18506](https://github.com/laravel/framework/pull/18506), [#18521](https://github.com/laravel/framework/pull/18521))
- Change `user_id` to unsigned integer in database session stub ([#18557](https://github.com/laravel/framework/pull/18557))
- Improved performance of `UrlGenerator::isValidUrl()` ([#18566](https://github.com/laravel/framework/pull/18566))

### Fixed
- Handle missing or malformed `config/app.php` file ([#18466](https://github.com/laravel/framework/pull/18466), [92931cf](https://github.com/laravel/framework/commit/92931cffe48503dfe7095c23856da07de304fef2))
- Only call `up` and `down` on migration if the method exists ([d27d94e](https://github.com/laravel/framework/commit/d27d94ed5220da3f6462c57eef122d8f40419ab1))
- Fixed overwriting of routes with identical path and method ([#18475](https://github.com/laravel/framework/pull/18475), [5aee967](https://github.com/laravel/framework/commit/5aee9675038f0ec30ba4ed2c1eb9b544ac370c06))
- Fixing model/route binding with identical name ([#18476](https://github.com/laravel/framework/pull/18476))
- Allow `rollbackMigrations()` path to be with string ([#18535](https://github.com/laravel/framework/pull/18535))
- Use `getStatusCode()` in `TestResponse::assertRedirect()` ([#18559](https://github.com/laravel/framework/pull/18559))
- Case `parseIds()` to array in `InteractsWithPivotTable::sync()` ([#18547](https://github.com/laravel/framework/pull/18547))
- Preserve route parameter names ([#18604](https://github.com/laravel/framework/pull/18604))


## v5.4.16 (2017-03-21)

### Added
- Added PHPDBG detection to `runningInConsole()` ([#18198](https://github.com/laravel/framework/pull/18198))
- Added `Arr:wrap()` method ([#18216](https://github.com/laravel/framework/pull/18216))
- Allow scheduling of queued jobs ([#18235](https://github.com/laravel/framework/pull/18235), [7bb67e2](https://github.com/laravel/framework/commit/7bb67e225646fb578c039cc0af130f7aa6858120))
- Allow skipping mail sending if a listener to `MessageSending` returns `false` ([#18245](https://github.com/laravel/framework/pull/18245))
- Added `BcryptHasher::cost()` method ([#18266](https://github.com/laravel/framework/pull/18266))
- Added `Command::alert()` method ([#18272](https://github.com/laravel/framework/pull/18272))
- Added `tap()` method to query builder ([#18284](https://github.com/laravel/framework/pull/18284))
- Added `orderByDesc()` methods to query builder ([#18292](https://github.com/laravel/framework/pull/18292))
- Added `Container::makeWith()` method ([#18271](https://github.com/laravel/framework/pull/18271), [#18320](https://github.com/laravel/framework/pull/18320))
- Added `InteractsWithDatabase::assertSoftDeleted()` ([#18328](https://github.com/laravel/framework/pull/18328), [2d4e1f0](https://github.com/laravel/framework/commit/2d4e1f0fe0bae3c7e3304317a69d619e71e476cc), [f89f917](https://github.com/laravel/framework/commit/f89f917b256ad79df786c688b11247ce1e48299a))
- Added ability to set queue parameters inside queued listeners ([#18375](https://github.com/laravel/framework/pull/18375), [cf461e2](https://github.com/laravel/framework/commit/cf461e23b6123ba6ed084d2fb8e8306482973b88))
- Added `Model::setKeyType()` ([#18354](https://github.com/laravel/framework/pull/18354))
- Output migration name before starting a migration or rollback ([#18379](https://github.com/laravel/framework/pull/18379), [e47e8b1](https://github.com/laravel/framework/commit/e47e8b16c1e65d752943faaa65db14ca323f5feb))
- Added `pipeline()`, `transaction()`, and `executeRaw()` to `PhpRedisConnection` ([#18421](https://github.com/laravel/framework/pull/18421))
- Added `@isset()` directive ([#18425](https://github.com/laravel/framework/pull/18425))
- Added `tinyIncrements()` database schema method ([#18424](https://github.com/laravel/framework/pull/18424))

### Changed
- Throw exception when `bootstrap/cache` directory is not writable ([#18188](https://github.com/laravel/framework/pull/18188), [b4f0005](https://github.com/laravel/framework/commit/b4f000516166b0694e842d64f5b2fde1167d4690))
- Use `resource_path()` helper in `MakeAuthCommand` ([#18215](https://github.com/laravel/framework/pull/18215))
- Added `file_exists()` check to `Event::emailOutput()` ([c8eafa8](https://github.com/laravel/framework/commit/c8eafa8e6741dc5add4c5030aa7362744dcdab29))
- Allow wildcards in MIME type validations ([#18243](https://github.com/laravel/framework/pull/18243))
- Only push existing jobs back into the queue using `queue:retry` ([#18279](https://github.com/laravel/framework/pull/18279), [e874a56](https://github.com/laravel/framework/commit/e874a56e5b75663861aab13ff8e13b82050de54e))
- Support file uploads in nested array ([#18276](https://github.com/laravel/framework/pull/18276))
- Don't use `config()` helper in Mail component ([#18290](https://github.com/laravel/framework/pull/18290))
- Return the insert ID from `DatabaseJob::release()` ([#18288](https://github.com/laravel/framework/pull/18288), [#18291](https://github.com/laravel/framework/pull/18291))
- Changed `id` in failed jobs migration stub to `bigIncrements()` ([#18300](https://github.com/laravel/framework/pull/18300))
- Prevent `make:auth` from overwriting existing views ([#18319](https://github.com/laravel/framework/pull/18319), [bef8f35](https://github.com/laravel/framework/commit/bef8f35694b7c22516a27929dbfc4c49032f78c6))
- Ensure Mailable view data is not overridden by order of operations ([#18322](https://github.com/laravel/framework/pull/18322))
- Use `getAuthIdentifier()` method in broadcasters ([#18351](https://github.com/laravel/framework/pull/18351))
- Use atomic cache operation when checking for event overlaps ([8ebb5b8](https://github.com/laravel/framework/commit/8ebb5b859ae8f2382a1836a83a47542de234d63a))
- Return pretty JSON response from `HasInDatabase::failureDescription()` ([#18377](https://github.com/laravel/framework/pull/18377))
- Allow Validator extension to use array-style callable ([#18399](https://github.com/laravel/framework/pull/18399))
- Pass the condition value to query builder's `when()` method ([#18419](https://github.com/laravel/framework/pull/18419))
- Don't require returning the query from `when()` method ([#18422](https://github.com/laravel/framework/pull/18422))

### Fixed
- Fixed an issue with slots when passed content equals `null` ([#18246](https://github.com/laravel/framework/pull/18246))
- Do require `Closure` in `orWhereHas()` ([#18277](https://github.com/laravel/framework/pull/18277))
- Let PHP parse `@includeWhen` directive ([#18285](https://github.com/laravel/framework/pull/18285))
- Only include `.php` files when loading database factories ([#18336](https://github.com/laravel/framework/pull/18336))
- Fixed PHP 5.6 issue in `FileFactory::generateImage()` ([#18345](https://github.com/laravel/framework/pull/18345))
- Allow `ImplicitRouteBinding` to match camelcase method parameter names ([#18307](https://github.com/laravel/framework/pull/18307), [4ae31a1](https://github.com/laravel/framework/commit/4ae31a154f81c2d76c7397dbdebfcac0e74e4ccd))
- Fixing weird behaviour of `Connection::getConfig()` when `null` was passed ([#18356](https://github.com/laravel/framework/pull/18356))
- Attempt to solve an issue with using `required_*` rules while the `ConvertEmptyStringToNull` middleware is applied ([#18376](https://github.com/laravel/framework/pull/18376))
- Fixed faking of model events ([d6cb75c](https://github.com/laravel/framework/commit/d6cb75c057009c6316d4efd865dccb3c4a5c7b36))
- Prevent model event result from firing observable events ([#18401](https://github.com/laravel/framework/pull/18401), [0607db0](https://github.com/laravel/framework/commit/0607db02ba9eeab0a22abe1dabb19536f4aa1ff2))
- Fix issue in `authorizeResource()` with compound names ([#18435](https://github.com/laravel/framework/pull/18435))


## v5.4.15 (2017-03-02)

### Added
- Added `any()` method to `ViewErrorBag` ([#18176](https://github.com/laravel/framework/pull/18176))
- Added `Storage` and `File` fakes ([#18178](https://github.com/laravel/framework/pull/18178), [#18180](https://github.com/laravel/framework/pull/18180))

### Changed
- Made queue worker properties `$shouldQuit` and `$paused` public ([e40c0e7](https://github.com/laravel/framework/commit/e40c0e7cd885156caa402d6d016cc686479736f4))

### Fixed
- Proxy `isset()` checks on `TestResponse` ([#18182](https://github.com/laravel/framework/pull/18182))


## v5.4.14 (2017-03-01)

### Added
- Added `Str::kebab()` and `kebab_case()` helper ([#18084](https://github.com/laravel/framework/pull/18084))
- Added `Route::getActionMethod()` ([#18105](https://github.com/laravel/framework/pull/18105))
- Support granular `$tries` and `$timeout` on `Mailable` ([#18103](https://github.com/laravel/framework/pull/18103))
- Added context to the `assertJson()` response ([#18166](https://github.com/laravel/framework/pull/18166), [da2c892](https://github.com/laravel/framework/commit/da2c8923328a3f852331fca5778214e05d8e6fde))
- Add `whereNotIn()` and `whereNotInStrict()` to `Collection` ([#18157](https://github.com/laravel/framework/pull/18157))

### Changed
- Create `TestResponse` using composition instead of inheritance ([#18089](https://github.com/laravel/framework/pull/18089))
- Changed visibility of `Pivot::$parent` from `protected` to `public` ([#18096](https://github.com/laravel/framework/pull/18096))
- Catch Sqlite3 deadlocks in `DetectsDeadlocks` ([#18107](https://github.com/laravel/framework/pull/18107))
- Use `fromRawAttributes()` when in `Model::newPivot()` ([#18127](https://github.com/laravel/framework/pull/18127), [063e5ae](https://github.com/laravel/framework/commit/063e5ae341c32220d4679d0215ab9b263d0c8a33))
- Use default connection in `DatabaseManager::purge()` when no connection name is provided ([#18128](https://github.com/laravel/framework/pull/18128))
- Convert rule objects only once to string ([#18141](https://github.com/laravel/framework/pull/18141))
- Respect `ShouldQueue` contract in `Mailer::send()` ([#18144](https://github.com/laravel/framework/pull/18144), [717f1f6](https://github.com/laravel/framework/commit/717f1f67eb7bb2fac7c57311f0afaa637375407d), [#18160](https://github.com/laravel/framework/pull/18160))

### Fixed
- Don't use `value()` helper in `BoundMethod` class ([#18075](https://github.com/laravel/framework/pull/18075))
- Don't require manifest file when running `npm run hot` ([#18088](https://github.com/laravel/framework/pull/18088))
- Fixed injection placement of dependencies for routing method parameters ([#17973](https://github.com/laravel/framework/pull/17973))
- Return `false` from `Model::fireModelEvent()` if custom model event returns `false` ([c5a6290](https://github.com/laravel/framework/commit/c5a62901589e313474376e241549ac31d9d3c695))
- Fixed `firstOrFail()` on relation setting the wrong model on `ModelNotFoundException` ([#18138](https://github.com/laravel/framework/pull/18138))
- Fixed `RateLimiter` setting initial attempts count to `2` ([#18139](https://github.com/laravel/framework/pull/18139))
- Fixed compiled `DELETE` query when using `JOIN` with aliases ([#18156](https://github.com/laravel/framework/pull/18156), [e09b9eb](https://github.com/laravel/framework/commit/e09b9eb62715706a0368acf0af7911340e0a3298))
- Fixed validating `present` in combination with `nullable` ([#18173](https://github.com/laravel/framework/pull/18173))


## v5.4.13 (2017-02-22)

### Added
- Add `$default` parameter to `Collection::when()` method ([#17941](https://github.com/laravel/framework/pull/17941))
- Support `--resource` argument on `make:model` command ([#17955](https://github.com/laravel/framework/pull/17955))
- Added `replaceDimensions()` for validator messages ([#17946](https://github.com/laravel/framework/pull/17946), [b219058](https://github.com/laravel/framework/commit/b219058063336dd9cc851349e287052d76bcc18e))
- Allow Slack notifications to use image urls ([#18011](https://github.com/laravel/framework/pull/18011))
- Added `@includeWhen($condition, $view, $data)` directive ([#18047](https://github.com/laravel/framework/pull/18047))

### Changed
- Prevent Blade from compiling statements inside comments ([#17952](https://github.com/laravel/framework/pull/17952))
- Don't flash empty array to session, if `withInput()` is given empty array ([#17979](https://github.com/laravel/framework/pull/17979))
- Use the pagination translation strings in paginator templates ([#18009](https://github.com/laravel/framework/pull/18009))
- Use `getAuthPassword()` method in `AuthenticateSession` middleware ([#17965](https://github.com/laravel/framework/pull/17965))
- Return `null` from `Gate::getPolicyFor()` if given class is not a string ([#17972](https://github.com/laravel/framework/pull/17972))
- Add missing methods to the `Job` interface ([#18034](https://github.com/laravel/framework/pull/18034))
- Improved PostgreSQL table existence check ([#18041](https://github.com/laravel/framework/pull/18041))
- Allow `getActualClassNameForMorph()` used by `morphInstanceTo()` to be overridden ([#18058](https://github.com/laravel/framework/pull/18058))

### Fixed
- Fixed `@lang` directive when used with JSON file ([#17919](https://github.com/laravel/framework/pull/17919), [2bd35c1](https://github.com/laravel/framework/commit/2bd35c13678faae68ee0bbe95d46b12f77357c98))
- Improved image `dimensions` validation rule ([#17943](https://github.com/laravel/framework/pull/17943), [#17944](https://github.com/laravel/framework/pull/17944), [#17963](https://github.com/laravel/framework/pull/17963), [#17980](https://github.com/laravel/framework/pull/17980))
- Fixed `$willBeAvailableAt` having the wrong time if using `$retryAfter` in `MaintenanceModeException` ([#17991](https://github.com/laravel/framework/pull/17991))
- Trim spaces while collecting section name ([#18012](https://github.com/laravel/framework/pull/18012))
- Fixed implementation of `SqsQueue::size()` ([#18037](https://github.com/laravel/framework/pull/18037))
- Fixed bug in `PasswordBroker::deleteToken()` ([#18045](https://github.com/laravel/framework/pull/18045))
- Fixed route parameters binding ([#17973](https://github.com/laravel/framework/pull/17973))


## v5.4.12 (2017-02-15)

### Added
- Allow configuration of Faker locale through `app.faker_locale` config ([#17895](https://github.com/laravel/framework/pull/17895))
- Added `when()` method to `Collection` class ([#17917](https://github.com/laravel/framework/pull/17917))
- Check for files in `request()` helper ([e08714d](https://github.com/laravel/framework/commit/e08714d65a2f3ab503fd59061aa8ab2fc37e19a4), [18d1648](https://github.com/laravel/framework/commit/18d16486103292c681c29f503b27bf3affb5a2d3))
- Added `TestResponse::assertSessionHasErrors()` method ([dbaf3b1](https://github.com/laravel/framework/commit/dbaf3b10f1daa483a1db840c60ec95337c9a1f6e))

### Changed
- Prevent duplication of embedded files in `Mail\Message` ([#17877](https://github.com/laravel/framework/pull/17877))
- Only unserialize data if needed in `CallQueuedListener` ([964fb7f](https://github.com/laravel/framework/commit/964fb7fc1c80948beb521f1672d0b250eedea94d))
- Made a couple of properties public ([614b94a](https://github.com/laravel/framework/commit/614b94a15564352f3775d3bc81097f89ba79e586))
- Handle `Model` instances in `Authorize` middleware ([#17898](https://github.com/laravel/framework/pull/17898))
- Use `asset()` helper in `app.stub` ([#17896](https://github.com/laravel/framework/pull/17896))
- Test PhpRedis connection if extension is loaded ([#17882](https://github.com/laravel/framework/pull/17882), [#17910](https://github.com/laravel/framework/pull/17910))
- Use `Carbon::setTestNow()` in all tests ([#17937](https://github.com/laravel/framework/pull/17937))

### Fixed
- Make sub select union queries work with SQLite ([#17890](https://github.com/laravel/framework/pull/17890))
- Fix `zRangeByScore()` options syntax for PhpRedis ([#17912](https://github.com/laravel/framework/pull/17912))


## v5.4.11 (2017-02-10)

### Added
- Support `Encrypt` and `TrustServerCertificate` options on SqlServer connections ([#17841](https://github.com/laravel/framework/pull/17841))
- Support custom pivot models in `MorphToMany::newPivot()` ([#17862](https://github.com/laravel/framework/pull/17862))
- Support `Arrayable` objects in Eloquent's `whereKey()` method ([#17812](https://github.com/laravel/framework/pull/17812))

### Changed
- Use `app.locale` lang attribute in `app.stub` ([#17827](https://github.com/laravel/framework/pull/17827))
- Throw `JsonEncodingException` when JSON encoder fails to encode eloquent attribute ([#17804](https://github.com/laravel/framework/pull/17804), [11e89f3](https://github.com/laravel/framework/commit/11e89f35b0f1fc1654d51bc31c865ab796da9f46))
- Ensure file `hashName()` is unique ([#17879](https://github.com/laravel/framework/pull/17879), [830f194](https://github.com/laravel/framework/commit/830f194f9f72cd3de31151990c7aad6db52f1e86))

### Fixed
- Added missing `Str` class import to `TestResponse` ([#17835](https://github.com/laravel/framework/pull/17835))
- Fixed PhpRedis’ `zadd()` method signature ([#17832](https://github.com/laravel/framework/pull/17832))


## v5.4.10 (2017-02-08)

### Added
- Added Blade `@prepend` directive ([#17696](https://github.com/laravel/framework/pull/17696))
- Added `Collection::tap()` method ([#17756](https://github.com/laravel/framework/pull/17756))
- Allow multiple manifest files for `mix()` helper ([#17759](https://github.com/laravel/framework/pull/17759))
- Adding facility to `Log:useSyslog()` ([#17789](https://github.com/laravel/framework/pull/17789))
- Added macros to Eloquent builder ([#17719](https://github.com/laravel/framework/pull/17719))
- Added `TestResponse::assertJsonFragment()` method ([#17809](https://github.com/laravel/framework/pull/17809))

### Changed
- Use `route()` helper instead of `url()` in authentication component ([#17718](https://github.com/laravel/framework/pull/17718))
- Ensure that the `map()` method exists before calling in `RouteServiceProvider::loadRoutes()` ([#17784](https://github.com/laravel/framework/pull/17784))
- Clean up how events are dispatched ([8c90e7f](https://github.com/laravel/framework/commit/8c90e7fe31bee11bd496050a807c790da12cc519), [c9e8854](https://github.com/laravel/framework/commit/c9e8854f9a9954e29ee5cdb0ca757736a65cbe15))
- Changed job `name` to `displayName` ([4e85a9a](https://github.com/laravel/framework/commit/4e85a9aaeb97a56a203a152ee7b114daaf07c6d8), [d033626](https://github.com/laravel/framework/commit/d0336261cdb63df6fff666490a6e9987cba5c0f0))
- Made plain string job payloads more similar to object based payloads ([bd49288](https://github.com/laravel/framework/commit/bd49288c551c47f4d15cb371fabfd2b0670b903d))
- Bring back pluralization rules for translations ([#17826](https://github.com/laravel/framework/pull/17826))

### Fixed
- Apply overrides in `factory()->raw()` method ([#17763](https://github.com/laravel/framework/pull/17763))


## v5.4.9 (2017-02-03)

### Added
- Added `Macroable` trait to `TestResponse` ([#17726](https://github.com/laravel/framework/pull/17726))
- Added container alias for the Redis connection instance ([#17722](https://github.com/laravel/framework/pull/17722))
- Added `raw()` method to `FactoryBuilder` ([#17734](https://github.com/laravel/framework/pull/17734))
- Added `json()` as alias for `decodeResponseJson` method on `TestResponse` ([#17735](https://github.com/laravel/framework/pull/17735))

### Changed
- Use `route()` helper instead of `url()` in authentication component ([#17718](https://github.com/laravel/framework/pull/17718))
- Added `Jedi` to uncountable list for pluralization ([#17729](https://github.com/laravel/framework/pull/17729))
- Return relative URL from `mix()` helper ([#17727](https://github.com/laravel/framework/pull/17727))
- Sort foreign keys on eloquent relations ([#17730](https://github.com/laravel/framework/pull/17730))
- Properly set the JSON payload of `FormRequests` instead of re-building it ([#17760](https://github.com/laravel/framework/pull/17760), [2d725c2](https://github.com/laravel/framework/commit/2d725c205a7f700f0143670def471e0b5082a420))
- Use `isset()` instead of `array_key_exists()` in `HasAttributes::getAttributeFromArray()` ([#17739](https://github.com/laravel/framework/pull/17739))

### Fixed
- Added missing `sleep` option to `queue:listen` ([59ef5bf](https://github.com/laravel/framework/commit/59ef5bff290195445925dc89a160782485bfc425))
- Switched to strict comparison `TrimStrings` middleware ([#17741](https://github.com/laravel/framework/pull/17741))


## v5.4.8 (2017-02-01)

### Added
- Added `TestResponse::assertJsonStructure()` ([#17700](https://github.com/laravel/framework/pull/17700))
- Added `Macroable` trait to Eloquent `Relation` class ([#17707](https://github.com/laravel/framework/pull/17707))

### Changed
- Move `shouldKill()` check from `daemon()` to `stopIfNecessary()` ([8403b34](https://github.com/laravel/framework/commit/8403b34a6de212e5cd98d40333fd84d112fbb9f7))
- Removed `isset()` check from `validateSame()` ([#17708](https://github.com/laravel/framework/pull/17708))

### Fixed
- Added `force` option to `queue:listen` signature ([#17716](https://github.com/laravel/framework/pull/17716))
- Fixed missing `return` in `HasManyThrough::find()` and `HasManyThrough::findMany()` ([#17717](https://github.com/laravel/framework/pull/17717))


## v5.4.7 (2017-01-31)

### Added
- Added `Illuminate\Support\Facades\Schema` to `notifications.stub` ([#17664](https://github.com/laravel/framework/pull/17664))
- Added support for numeric arguments to `@break` and `@continue` ([#17603](https://github.com/laravel/framework/pull/17603))

### Changed
- Use `usesTimestamps()` in Eloquent traits ([#17612](https://github.com/laravel/framework/pull/17612))
- Default to `null` if amount isn't set in `factory()` helper ([#17614](https://github.com/laravel/framework/pull/17614))
- Normalize PhpRedis GET/MGET results ([#17196](https://github.com/laravel/framework/pull/17196))
- Changed visibility of `Validator::addRules()` from `protected` to `public` ([#17654](https://github.com/laravel/framework/pull/17654))
- Gracefully handle `SIGTERM` signal in queue worker ([b38ba01](https://github.com/laravel/framework/commit/b38ba016283c6491d6e525caeb6206b2b04321fc), [819888c](https://github.com/laravel/framework/commit/819888ca1776581225d57e00fdc4ba709cbcc5d0))
- Support inspecting multiple events of same type using `Event` fake ([55be2ea](https://github.com/laravel/framework/commit/55be2ea35ccd2e450f9ffad23fd7ac446c035013))
- Replaced hard-coded year in plain-text markdown emails ([#17684](https://github.com/laravel/framework/pull/17684))
- Made button component in plain-text markdown emails easier to read ([#17683](https://github.com/laravel/framework/pull/17683))

### Fixed
- Set `Command::$name` in `Command::configureUsingFluentDefinition()` ([#17610](https://github.com/laravel/framework/pull/17610))
- Support post size `0` (unlimited) in `ValidatePostSize` ([#17607](https://github.com/laravel/framework/pull/17607))
- Fixed method signature issues in `PhpRedisConnection` ([#17627](https://github.com/laravel/framework/pull/17627))
- Fixed `BelongsTo` not accepting id of `0` in foreign relations ([#17668](https://github.com/laravel/framework/pull/17668))
- Support double quotes with `@section()` ([#17677](https://github.com/laravel/framework/pull/17677))
- Fixed parsing explicit validator rules ([#17681](https://github.com/laravel/framework/pull/17681))
- Fixed `SessionGuard::recaller()` when request is `null` ([#17688](https://github.com/laravel/framework/pull/17688), [565456d](https://github.com/laravel/framework/commit/565456d89e8d6378c213edb7e9d0724fa8a5f473))
- Added missing `force` and `tries` options for `queue:listen` ([#17687](https://github.com/laravel/framework/pull/17687))
- Fixed how reservation works when queue is paused ([9d348c5](https://github.com/laravel/framework/commit/9d348c5d57873bfcca86cff1987e22472d1f0e5b))


## v5.4.6 (2017-01-27)

### Added
- Generate non-existent models with `make:controller` ([#17587](https://github.com/laravel/framework/pull/17587), [382b78c](https://github.com/laravel/framework/commit/382b78ca12282c580ff801a00b2e52faf50c6d38))
- Added `TestResponse::dump()` method ([#17600](https://github.com/laravel/framework/pull/17600))

### Changed
- Switch to `ViewFactory` contract in `Mail/Markdown` ([#17591](https://github.com/laravel/framework/pull/17591))
- Use implicit binding when generating controllers with `make:model` ([#17588](https://github.com/laravel/framework/pull/17588))
- Made PhpRedis method signatures compatibility with Predis ([#17488](https://github.com/laravel/framework/pull/17488))
- Use `config('app.name')` in `markdown/message.blade.php` ([#17604](https://github.com/laravel/framework/pull/17604))
- Use `getStatusCode()` instead of `status()` in `TestResponse::fromBaseResponse()` ([#17590](https://github.com/laravel/framework/pull/17590))

### Fixed
- Fixed loading of `.env.testing` when running PHPUnit ([#17596](https://github.com/laravel/framework/pull/17596))


## v5.4.5 (2017-01-26)

### Fixed
- Fixed database session data not persisting ([#17584](https://github.com/laravel/framework/pull/17584))


## v5.4.4 (2017-01-26)

### Added
- Add `hasMiddlewareGroup()` and `getMiddlewareGroups()` method to `Router` ([#17576](https://github.com/laravel/framework/pull/17576))

### Fixed
- Fixed `--database` option on `migrate` commands ([#17574](https://github.com/laravel/framework/pull/17574))
- Fixed `$sequence` being always overwritten in `PostgresGrammar::compileInsertGetId()` ([#17570](https://github.com/laravel/framework/pull/17570))

### Removed
- Removed various unused parameters from view compilers ([#17554](https://github.com/laravel/framework/pull/17554))
- Removed superfluous `ForceDelete` extension from `SoftDeletingScope` ([#17552](https://github.com/laravel/framework/pull/17552))


## v5.4.3 (2017-01-25)

### Added
- Mock `dispatch()` method in `MocksApplicationServices` ([#17543](https://github.com/laravel/framework/pull/17543), [d974a88](https://github.com/laravel/framework/commit/d974a8828221ba8673cc4f6d9124d1d33f3de447))

### Changed
- Moved `$forElseCounter` property from `BladeCompiler` to `CompilesLoops` ([#17538](https://github.com/laravel/framework/pull/17538))

### Fixed
- Fixed bug in `Router::pushMiddlewareToGroup()` ([1054fd2](https://github.com/laravel/framework/commit/1054fd2523913e59e980553b5411a22f16ecf817))
- Fixed indentation in `Notifications/resources/views/email.blade.php` ([0435cfc](https://github.com/laravel/framework/commit/0435cfcf171908432d88e447fe4021998e515b9f))


## v5.4.2 (2017-01-25)

### Fixed
- Fixed removal of reset tokens after password reset ([#17524](https://github.com/laravel/framework/pull/17524))


## v5.4.1 (2017-01-24)

### Fixed
- Fixed view parent placeholding ([64f7e9c](https://github.com/laravel/framework/commit/64f7e9c4e37637df7b0820b11d5fcee1c1cca58d))

## v5.4.0 (2017-01-24)

### General
- Added real-time facades 😈 ([feb52bf](https://github.com/laravel/framework/commit/feb52bf966c0ea517ec0cf688b5a2534b50a8268))
- Added `retry()` helper ([e3bd359](https://github.com/laravel/framework/commit/e3bd359d52cee0ba8db9673e45a8221c1c1d95d6), [52e9381](https://github.com/laravel/framework/commit/52e9381d3d64631f2842c1d86fee2aa64a6c73ac))
- Added `array_wrap()` helper function ([0f76617](https://github.com/laravel/framework/commit/0f766177e4ac42eceb00aa691634b00a77b18b59))
- Added default 503 error page into framework ([855a8aa](https://github.com/laravel/framework/commit/855a8aaca2903015e3fe26f756e73af9f1b98374), [#16848](https://github.com/laravel/framework/pull/16848))
- Added `Encrypter::encryptString()` to bypass serialization ([9725a8e](https://github.com/laravel/framework/commit/9725a8e7d0555474114f5cad9249fe8fe556836c))
- Removed compiled class file generation and deprecated `ServiceProvider::compiles()` ([#17003](https://github.com/laravel/framework/pull/17003), [733d829](https://github.com/laravel/framework/commit/733d829d6551dde2f290b6c26543dd08956e82e7))
- Renamed `DetectEnvironment` to `LoadEnvironmentVariables` ([c36874d](https://github.com/laravel/framework/commit/c36874dda29d8eb9f9364c4bd308c7ee10060c25))
- Switched to `::class` notation across the codebase ([#17357](https://github.com/laravel/framework/pull/17357))

### Authentication
- Secured password reset tokens against timing attacks and compromised databases ([#16850](https://github.com/laravel/framework/pull/16850), [9d674b0](https://github.com/laravel/framework/commit/9d674b053145968ff9060b930a644ddd7851d66f))
- Refactored authentication component ([7b48bfc](https://github.com/laravel/framework/commit/7b48bfccf9ed12c71461651bbf52a3214b58d82e), [5c4541b](https://github.com/laravel/framework/commit/5c4541bc43f22b0d99c5cc6db38781060bff836f))
- Added names to password reset routes ([#16988](https://github.com/laravel/framework/pull/16988))
- Stopped touching the user timestamp when updating the `remember_token` ([#17135](https://github.com/laravel/framework/pull/17135))

### Authorization
- Consider interfaces and extended classes in `Gate::resolvePolicyCallback()` ([#15757](https://github.com/laravel/framework/pull/15757))

### Blade
- Added Blade components and slots ([e8d2a45](https://github.com/laravel/framework/commit/e8d2a45479abd2ba6b524293ce5cfb599c8bf910), [a00a201](https://github.com/laravel/framework/commit/a00a2016a4ff6518b60845745fc0533058f6adc6))
- Refactored Blade component ([7cdb6a6](https://github.com/laravel/framework/commit/7cdb6a6f6b77c91906c4ad0c6110b30042a43277), [5e394bb](https://github.com/laravel/framework/commit/5e394bb2c4b20833ea07b052823fe744491bdbd5))
- Refactored View component ([#17018](https://github.com/laravel/framework/pull/17018), [bb998dc](https://github.com/laravel/framework/commit/bb998dc23e7f4da5820b61fb5eb606fe4a654a2a))
- Refactored Blade `@parent` compilation ([#16033](https://github.com/laravel/framework/pull/16033), [16f72a5](https://github.com/laravel/framework/commit/16f72a5a580b593ac804bc0b2fdcc6eb278e55b2))
- Added support for translation blocks in Blade templates ([7179935](https://github.com/laravel/framework/commit/71799359b7e74995be862e498d1b21841ff55fbc))
- Don't reverse the order of `@push`ed data ([#16325](https://github.com/laravel/framework/pull/16325))
- Allow view data to be passed Paginator methods ([#17331](https://github.com/laravel/framework/pull/17331))
- Add `mix()` helper method ([6ea4997](https://github.com/laravel/framework/commit/6ea4997fcf7cf0ae4c18bce9418817ff00e4727f))
- Escape inline sections content ([#17453](https://github.com/laravel/framework/pull/17453))

### Broadcasting
- Added model binding in broadcasting channel definitions ([#16120](https://github.com/laravel/framework/pull/16120), [515d97c](https://github.com/laravel/framework/commit/515d97c1f3ad4797876979d450304684012142d6))
- Added `Dispatchable::broadcast()` [0fd8f8d](https://github.com/laravel/framework/commit/0fd8f8de75545b5701e59f51c88d02c12528800a)
- Switched to broadcasting events using new style jobs ([#17433](https://github.com/laravel/framework/pull/17433))

### Cache
- Added `RedisStore::add()` to store an item in the cache if the key doesn't exist ([#15877](https://github.com/laravel/framework/pull/15877))
- Added `cache:forget` command ([#16201](https://github.com/laravel/framework/pull/16201), [7644977](https://github.com/laravel/framework/commit/76449777741fa1d7669028973958a7e4a5e64f71))
- Refactored cache events ([b7454f0](https://github.com/laravel/framework/commit/b7454f0e67720c702d8f201fd6ca81db6837d461), [#17120](https://github.com/laravel/framework/pull/17120))
- `Cache::flush()` now returns boolean ([#15831](https://github.com/laravel/framework/pull/15831), [057492d](https://github.com/laravel/framework/commit/057492d31c569e96a3ba2f99722112a9762c6071))

### Collections
- Added higher-order messages for the collections ([#16267](https://github.com/laravel/framework/pull/16267), [e276b3d](https://github.com/laravel/framework/commit/e276b3d4bf2a124c4eb5975a8a2724b8c806139a), [2b7ab30](https://github.com/laravel/framework/commit/2b7ab30e0ec56ac4e4093d7f2775da98086c8000), [#16274](https://github.com/laravel/framework/pull/16274), [724950a](https://github.com/laravel/framework/commit/724950a42c225c7b53c56283c01576b050fea37a), [#17000](https://github.com/laravel/framework/pull/17000))
- Allow collection macros to be proxied ([#16749](https://github.com/laravel/framework/pull/16749))
- Added operator support to `Collection::contains()` method ([#16791](https://github.com/laravel/framework/pull/16791))
- Renamed `every()` method to `nth()` and added new `every()` to determine if all items pass the given test ([#16777](https://github.com/laravel/framework/pull/16777))
- Allow passing an array to `Collection::find()` ([#16849](https://github.com/laravel/framework/pull/16849))
- Always return a collection when calling `Collection::random()` with a parameter ([#16865](https://github.com/laravel/framework/pull/16865))
- Don't renumber the keys and keep the input array order in `mapWithKeys()` ([#16564](https://github.com/laravel/framework/pull/16564))

### Console
- Added `--model` to `make:controller` command to generate resource controller with type-hinted model ([#16787](https://github.com/laravel/framework/pull/16787))
- Require confirmation for `key:generate` command in production ([#16804](https://github.com/laravel/framework/pull/16804))
- Added `ManagesFrequencies` trait ([e238299](https://github.com/laravel/framework/commit/e238299f12ee91a65ac021feca29b870b05f5dd7))
- Added `Queueable` to queued listener stub ([dcd64b6](https://github.com/laravel/framework/commit/dcd64b6c36d1e545c1c2612764ec280c47fdea97))
- Switched from file to cache based Schedule overlap locking ([#16196](https://github.com/laravel/framework/pull/16196), [5973f6c](https://github.com/laravel/framework/commit/5973f6c54ccd0d99e15f055c5a16b19b8c45db91))
- Changed namespace generation in `GeneratorCommand` ([de9e03d](https://github.com/laravel/framework/commit/de9e03d5bd80d32a936d30ab133d2df0a3fa1d8d))
- Added `Command::$hidden` and `ScheduleFinishCommand` ([#16806](https://github.com/laravel/framework/pull/16806))
- Moved all framework command registrations into `ArtisanServiceProvider` ([954a333](https://github.com/laravel/framework/commit/954a33371bd7f7597eae6fce2ed1d391a2268099), [baa6054](https://github.com/laravel/framework/commit/baa605424a4448ab4f1c6068d8755ecf83bde665), [87bd2a9](https://github.com/laravel/framework/commit/87bd2a9e6c79715a9c73ca6134074919ede1a0e7))
- Support passing output buffer to `Artisan::call()` ([#16930](https://github.com/laravel/framework/pull/16930))
- Moved `tinker` into an external package ([#17002](https://github.com/laravel/framework/pull/17002))
- Refactored queue commands ([07a9402](https://github.com/laravel/framework/commit/07a9402f5d1b2fb5dedc22751a59914ebcf41562), [a82a25f](https://github.com/laravel/framework/commit/a82a25f58252eab6831a0efde35a17403710abdc), [f2beb2b](https://github.com/laravel/framework/commit/f2beb2bbce11433283ba52744dbe7134aa55cbfa))
- Allow tasks to be scheduled on weekends ([#17085](https://github.com/laravel/framework/pull/17085))
- Allow console events to be macroable ([#17107](https://github.com/laravel/framework/pull/17107))

### Container
- Added `Container::factory()` method to the Container contract ([#15430](https://github.com/laravel/framework/pull/15430))
- Added support for binding methods to the container ([#16800](https://github.com/laravel/framework/pull/16800), [1fa8ea0](https://github.com/laravel/framework/commit/1fa8ea02c096d09bea909b7bffa24b861dc76240))
- Trigger callback when binding an extension or resolving callback to an alias ([c99098f](https://github.com/laravel/framework/commit/c99098fc85c9633db578f70fba454184609c515d))
- Support contextual binding with aliases ([c99098f](https://github.com/laravel/framework/commit/c99098fc85c9633db578f70fba454184609c515d))
- Removed `$parameters` from `Application::make()` and `app()`/`resolve()` helpers ([#17071](https://github.com/laravel/framework/pull/17071), [#17060](https://github.com/laravel/framework/pull/17060))
- Removed `Container::share()` ([1a1969b](https://github.com/laravel/framework/commit/1a1969b6e6f793c3b2a479362641487ee9cbf736))
- Removed `Container::normalize()` ([ff993b8](https://github.com/laravel/framework/commit/ff993b806dcb21ba8a5367594e87d113338c1670))

### DB
- Refactored all database components (_too many commits, sorry_)
- Allow rolling back to a given transaction save-point ([#15876](https://github.com/laravel/framework/pull/15876))
- Added `$values` parameter to `Builder::firstOrNew()` ([#15567](https://github.com/laravel/framework/pull/15567))
- Allow dependency injection on database seeders `run()` method ([#15959](https://github.com/laravel/framework/pull/15959))
- Added support for joins when deleting deleting records using SqlServer ([#16618](https://github.com/laravel/framework/pull/16618))
- Added collation support to `SQLServerGrammar` ([#16227](https://github.com/laravel/framework/pull/16227))
- Don't rollback to save-points on deadlock (nested transaction) ([#15932](https://github.com/laravel/framework/pull/15932))
- Improve `Connection::selectOne()` performance by switching to `array_shift()` ([#16188](https://github.com/laravel/framework/pull/16188))
- Added `having()` shortcut ([#17160](https://github.com/laravel/framework/pull/17160))
- Added customer connection resolver ([#17248](https://github.com/laravel/framework/pull/17248))
- Support aliasing database names with spaces ([#17312](https://github.com/laravel/framework/pull/17312))
- Support column aliases using `chunkById()` ([#17034](https://github.com/laravel/framework/pull/17034))
- Execute queries with locks only on write connection ([#17386](https://github.com/laravel/framework/pull/17386))
- Added `compileLock()` method to `SqlServerGrammar` ([#17424](https://github.com/laravel/framework/pull/17424))

### Eloquent
- Refactored Eloquent (_too many commits, sorry_)
- Added support for object-based events for native Eloquent events ([e7a724d](https://github.com/laravel/framework/commit/e7a724d3895f2b24b98c0cafb1650f2193351d83), [9770d1a](https://github.com/laravel/framework/commit/9770d1a64c1010daf845fcebfcc4695a30d8df2d))
- Added custom class support for pivot models ([#14293](https://github.com/laravel/framework/pull/14293), [5459777](https://github.com/laravel/framework/commit/5459777c90ff6d0888bd821027c417d57cc89981))
- Use the model's primary key instead of `id` in `Model::getForeignKey()` ([#16396](https://github.com/laravel/framework/pull/16396))
- Made `date` and `datetime` cast difference more explicit ([#16799](https://github.com/laravel/framework/pull/16799))
- Use `getKeyType()` instead of `$keyType` in `Model` ([#16608](https://github.com/laravel/framework/pull/16608))
- Only detach all associations if no parameter is passed to `BelongsToMany::detach()` ([#16144](https://github.com/laravel/framework/pull/16144))
- Return a database collection from `HasOneOrMany::createMany()` ([#15944](https://github.com/laravel/framework/pull/15944))
- Throw `JsonEncodingException` when `Model::toJson()` fails ([#16159](https://github.com/laravel/framework/pull/16159), [0bda866](https://github.com/laravel/framework/commit/0bda866a475de524eeff3e7f7471031dd64cf2d3))
- Default foreign key for `belongsTo()` relationship is now dynamic ([#16847](https://github.com/laravel/framework/pull/16847))
- Added `whereKey()` method ([#16558](https://github.com/laravel/framework/pull/16558))
- Use parent connection if related model doesn't specify one ([#16103](https://github.com/laravel/framework/pull/16103))
- Enforce an `orderBy` clause for `chunk()` ([#16283](https://github.com/laravel/framework/pull/16283), [#16513](https://github.com/laravel/framework/pull/16513))
- Added `$connection` parameter to `create()` and `forceCreate()` ([#17392](https://github.com/laravel/framework/pull/17392))

### Events
- Removed event priorities ([dbbfc62](https://github.com/laravel/framework/commit/dbbfc62beff1625b0d45bbf39650d047555cf4fa), [#17245](https://github.com/laravel/framework/pull/17245), [f83edc1](https://github.com/laravel/framework/commit/f83edc1cb820523fd933c3d3c0430a1f63a073ec))
- Allow queued handlers to specify their queue and connection ([fedd4cd](https://github.com/laravel/framework/commit/fedd4cd4d900656071d44fc1ee9c83e6de986fa8))
- Converted `locale.changed` event into `LocaleUpdated` class ([3385fdc](https://github.com/laravel/framework/commit/3385fdc0f8e4890ab57261755bcbbf79f9ec828d))
- Unified wording ([2dcde69](https://github.com/laravel/framework/commit/2dcde6983ffbb4faf1c238544b51831b33c3a857))
- Allow chaining queueable methods onto trait dispatch ([9fde549](https://github.com/laravel/framework/commit/9fde54954c326b9021476aa87c96bf43a7885bcc))
- Removed `Queueable` trait from event listeners subs ([2a90ef4](https://github.com/laravel/framework/commit/2a90ef46a2121783f8c5c8f20274634cde115612))

### Filesystem
- Use UUID instead of `md5()` for generating file names in `FileHelpers` ([#16193](https://github.com/laravel/framework/pull/16193))
- Allow array of options on `Filesystem` operations ([481f760](https://github.com/laravel/framework/commit/481f76000c861e3e2540dcdda986fb44622ccbbe))

### HTTP
- Refactored session component ([66976ba](https://github.com/laravel/framework/commit/66976ba3f559ee6ede4cc865ea995996cd42ee1b), [d9e0a6a](https://github.com/laravel/framework/commit/d9e0a6a03891d16ed6a71151354445fbdc9e6f50))
- Added `Illuminate\Http\Request\Concerns` traits ([4810e9d](https://github.com/laravel/framework/commit/4810e9d1bc118367f3d70cd6f64f1d4c4acf85ca))
- Use variable-length method signature for `CookieJar::queue()` ([#16290](https://github.com/laravel/framework/pull/16290), [ddabaaa](https://github.com/laravel/framework/commit/ddabaaa6a8ce16876ddec36be1391eae14649aea))
- Added `FormRequestServiceProvider` ([b892805](https://github.com/laravel/framework/commit/b892805124ecdf4821c2dac7aea4f829ce2248bc))
- Renamed `Http/Exception` namespace to `Http/Exceptions` ([#17398](https://github.com/laravel/framework/pull/17398))
- Renamed `getJsonOptions()` to `getEncodingOptions()` on `JsonResponse` ([e689b2a](https://github.com/laravel/framework/commit/e689b2aa06d1d35d2593ffa77f8a56df314f7e49))
- Renamed `VerifyPostSize` middleware to `ValidatePostSize` ([893a044](https://github.com/laravel/framework/commit/893a044fb10c87095e99081de4d1668bc1e19997))
- Converted `kernel.handled` event into `RequestHandled` class ([43a5e5f](https://github.com/laravel/framework/commit/43a5e5f341cc8affd52e77019f50e2d96feb94a5))
- Throw `AuthorizationException` in `FormRequest` ([1a75409](https://github.com/laravel/framework/commit/1a7540967ca36f875a262a22b76c2a094b9ba3b4))
- Use `Str::random` instead of UUID in `FileHelpers` ([#17046](https://github.com/laravel/framework/pull/17046))
- Moved `getOriginalContent()` to `ResponseTrait` ([#17137](https://github.com/laravel/framework/pull/17137))
- Added JSON responses to the `AuthenticatesUsers` and `ThrottlesLogins` ([#17369](https://github.com/laravel/framework/pull/17369))
- Added middleware to trim strings and convert empty strings to null ([f578bbc](https://github.com/laravel/framework/commit/f578bbce25843492fc996ac96797e0395e16cf2e))

### Logging
- Added `LogServiceProvider` to defer loading of logging code ([#15451](https://github.com/laravel/framework/pull/15451), [6550153](https://github.com/laravel/framework/commit/6550153162b4d54d03d37dd9adfd0c95ca0383a9), [#15794](https://github.com/laravel/framework/pull/15794))
- The `Log` facade now uses `LoggerInterface` instead of the log writer ([#15855](https://github.com/laravel/framework/pull/15855))
- Converted `illuminate.log` event into `MessageLogged` class ([57c82d0](https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072))

### Mail
- Added support for Markdown emails and notifications ([#16768](https://github.com/laravel/framework/pull/16768), [b876759](https://github.com/laravel/framework/commit/b8767595e762d241a52607123da5922899bf65e1), [cd569f0](https://github.com/laravel/framework/commit/cd569f074fd566f30d3eb760c3c9027203da3850), [5325385](https://github.com/laravel/framework/commit/5325385f32331c44c5050cdd790dfbdfe943357b))
- Refactored Mail component and removed `SuperClosure` dependency ([50ab994](https://github.com/laravel/framework/commit/50ab994b5b9c2675eb6cc24412672df5aefd248c), [5dace8f](https://github.com/laravel/framework/commit/5dace8f0d6f6e67b4862abbbae376dcd8a641f00))
- Allow `Mailer` to email `HtmlString` objects ([882ea28](https://github.com/laravel/framework/commit/882ea283045a7a231ca86c75058ebdea1d160fda))
- Added `hasTo()`, `hasCc()` and `hasBcc()` to `Mailable` ([fb29b38](https://github.com/laravel/framework/commit/fb29b38d7c04c59e1f442b0d89fc6108c8671a08))

### Notifications
- Added `NotificationSender` class ([5f93133](https://github.com/laravel/framework/commit/5f93133170c40b203f0922fd29eb22e1ee20be21))
- Removed `to` and `cc` from mail `MailMessage` ([ff68549](https://github.com/laravel/framework/commit/ff685491f4739b899dbe91e5fb1683c28e2dc5e1))
- Add salutation option to `SimpleMessage` notification ([#17429](https://github.com/laravel/framework/pull/17429))

### Queue
- Support job-based queue options ([#16257](https://github.com/laravel/framework/pull/16257), [2382dc3](https://github.com/laravel/framework/commit/2382dc3f374bee7ad966d11ecb35a1429d9a09e8), [ee385fa](https://github.com/laravel/framework/commit/ee385fa5eab0c4642f47636f0e033e982d402bb9))
- Fixed manually failing jobs and added `FailingJob` class ([707a3bc](https://github.com/laravel/framework/commit/707a3bc84ce82bbe44e2c722ede24b5edb194b6b), [55afe12](https://github.com/laravel/framework/commit/55afe12977b55dbafda940e18102bb52276ca569))
- Converted `illuminate.queue.looping` event into `Looping` class ([57c82d0](https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072))
- Refactored Queue component ([9bc8ca5](https://github.com/laravel/framework/commit/9bc8ca502687f29761b9eb78f70db6e3c3f0a09e), [e030231](https://github.com/laravel/framework/commit/e030231604479d0326ad9bfb56a2a36229d78ff4), [a041fb5](https://github.com/laravel/framework/commit/a041fb5ec9fc775d1a3efb6b647604da2b02b866), [7bb15cf](https://github.com/laravel/framework/commit/7bb15cf40a182bed3d00bc55de55798e58bf1ed0), [5505728](https://github.com/laravel/framework/commit/55057285b321b4b668d12fade330b0d196f9514a), [fed36bd](https://github.com/laravel/framework/commit/fed36bd7e09658009d36d9dd568f19ddcb75172e))
- Refactored how queue connection names are set ([4c600fb](https://github.com/laravel/framework/commit/4c600fb7af855747b6b44a194a5d0061d6294488))
- Let queue worker exit ungracefully on `memoryExceeded()` ([#17302](https://github.com/laravel/framework/pull/17302))
- Support pause and continue signals in queue worker ([827d075](https://github.com/laravel/framework/commit/827d075fb06b516eea992393279fc5ec2adabcf8))

### Redis
- Added support for [PhpRedis](https://github.com/phpredis/phpredis) ([#15160](https://github.com/laravel/framework/pull/15160), [01ed1c8](https://github.com/laravel/framework/commit/01ed1c8348a8e69ad213c95dd8d24e652154e6f0), [1ef8b9c](https://github.com/laravel/framework/commit/1ef8b9c3f156c7d4debc6c6f67b73b032d8337d5))
- Added support for multiple Redis clusters ([#16696](https://github.com/laravel/framework/pull/16696), [464075d](https://github.com/laravel/framework/commit/464075d3c5f152dfc4fc9287595d62dbdc3c6347))
- Added `RedisQueue::laterRaw()` method ([7fbac1c](https://github.com/laravel/framework/commit/7fbac1c6c09080da698f4c3256356cb896465692))
- Return migrated jobs from `RedisQueue::migrateExpiredJobs()` ([f21e942](https://github.com/laravel/framework/commit/f21e942ae8a4104ffdf42c231601efe8759c4c10))
- Send full job back into `RedisQueue` ([16e862c](https://github.com/laravel/framework/commit/16e862c1e22795acab869fa01ec5f8bcd7d400b3))

### Routing
- Added support for fluent routes ([#16647](https://github.com/laravel/framework/pull/16647), [#16748](https://github.com/laravel/framework/pull/16748))
- Removed `RouteServiceProvider::loadRoutesFrom()` ([0f2b3be](https://github.com/laravel/framework/commit/0f2b3be9b8753ba2813595f9191aa8d8c31886b1))
- Allow route groups to be loaded directly from a file ([#16707](https://github.com/laravel/framework/pull/16707), [#16792](https://github.com/laravel/framework/pull/16792))
- Added named parameters to `UrlGenerator` ([#16736](https://github.com/laravel/framework/pull/16736), [ce4d86b](https://github.com/laravel/framework/commit/ce4d86b48732a707e3909dbc553a2c349c8ecae7))
- Refactored Route component ([b75aca6](https://github.com/laravel/framework/commit/b75aca6a203590068161835945213fd1a39c7080), [9d3ff16](https://github.com/laravel/framework/commit/9d3ff161fd3929f9a106f007ce63fffdd118d490), [c906ed9](https://github.com/laravel/framework/commit/c906ed933713df22e4356cf4ea274f19b15d1ab7), [0f7985c](https://github.com/laravel/framework/commit/0f7985c888abb0a1824e87b32ab3d8feaca5fecf), [0f7985c](https://github.com/laravel/framework/commit/0f7985c888abb0a1824e87b32ab3d8feaca5fecf), [3f4221f](https://github.com/laravel/framework/commit/3f4221fe07c3e9d12eb814c144c1ffca09b577da))
- Refactored Router component ([eecf6ec](https://github.com/laravel/framework/commit/eecf6eca8b4a0cfdf8ec2b0148ee726b8b67c6bb), [b208a4f](https://github.com/laravel/framework/commit/b208a4fc3b35da167a2dcb9b581d9e072d20ec92), [21de409](https://github.com/laravel/framework/commit/21de40971cd81712b398ef3895357843fd34250d), [e75730e](https://github.com/laravel/framework/commit/e75730ec192bb2927a46f37ef854ba8c7372cac6))
- Refactored Router URL generator component ([39e8c83](https://github.com/laravel/framework/commit/39e8c83af778d8086b0b5e8f4f2e21331b015b39), [098da0d](https://github.com/laravel/framework/commit/098da0d6b4c20104c60b969b9a7f10ac5ff50c8e))
- Removed `RouteDependencyResolverTrait::callWithDependencies()` ([f7f13fa](https://github.com/laravel/framework/commit/f7f13fab9a451bc2249fc0709b6cf1fa6b7c795a))
- `UrlGenerator` improvements ([f0b9858](https://github.com/laravel/framework/commit/f0b985831f72a896735d02bf14b1c6680e3d7092), [4f96f42](https://github.com/laravel/framework/commit/4f96f429b22b1b09de6a263bd7d50eda18075b52))
- Compile routes only once ([c8ed0c3](https://github.com/laravel/framework/commit/c8ed0c3a11bf7d8180982a3d32a60364594bbfe1), [b11fbcc](https://github.com/laravel/framework/commit/b11fbcc209b8a57501bac6221728e7ed6c7a82a2))

### Testing
- Simplified built-in testing for Dusk ([#16667](https://github.com/laravel/framework/pull/16667), [126adb7](https://github.com/laravel/framework/commit/126adb781c204129600363f243b9d73e202d229e), [b6dec26](https://github.com/laravel/framework/commit/b6dec2602d4a7aa1e61667c02c301c8011267a19), [939264f](https://github.com/laravel/framework/commit/939264f91edc5d33da5ce6cf95a271a6f4a2e1f2))
- Improve database testing methods ([#16679](https://github.com/laravel/framework/pull/16679), [14e9dad](https://github.com/laravel/framework/commit/14e9dad05d09429fab244e2d8f6c49e679a3a975), [f23ac64](https://github.com/laravel/framework/commit/f23ac640fa403ca8d4131c36367b53e123b6b852))
- Refactored `MailFake` ([b1d8f81](https://github.com/laravel/framework/commit/b1d8f813d13960096493f3adc3bc32ace66ba2e6))
- Namespaced all tests ([#17058](https://github.com/laravel/framework/pull/17058), [#17148](https://github.com/laravel/framework/pull/17148))
- Allow chaining of response assertions ([#17330](https://github.com/laravel/framework/pull/17330))
- Return `TestResponse` from `MakesHttpRequests::json()` ([#17341](https://github.com/laravel/framework/pull/17341))
- Always return collection from factory when `$amount` is set ([#17493](https://github.com/laravel/framework/pull/17493))

### Translations
- Added JSON loader for translations and `__()` helper ([#16424](https://github.com/laravel/framework/pull/16424), [#16470](https://github.com/laravel/framework/pull/16470), [9437244](https://github.com/laravel/framework/commit/94372447b9de48f5c174db2cf7c81dffb3c0c692))
- Replaced Symfony's translator ([#15563](https://github.com/laravel/framework/pull/15563))
- Added `namespaces()` method to translation loaders ([#16664](https://github.com/laravel/framework/pull/16664), [fe7bbf7](https://github.com/laravel/framework/commit/fe7bbf727834a748b04fcf5145b1137dd45ac4b7))
- Switched to `trans()` helper in `AuthenticatesUsers` ([#17202](https://github.com/laravel/framework/pull/17202))

### Validation
- Refactored Validation component ([#17005](https://github.com/laravel/framework/pull/17005), [9e98e7a](https://github.com/laravel/framework/commit/9e98e7a5120f14e942bd00a1439e1a049440eea8), [9b817f1](https://github.com/laravel/framework/commit/9b817f1d03b3a7b3379723a32ab818aa3860060a))
- Removed files hydration in `Validator` ([#16017](https://github.com/laravel/framework/pull/16017))
- Added IPv4 and IPv6 validators ([#16545](https://github.com/laravel/framework/pull/16545))
- Made `date_format` validation more precise ([#16858](https://github.com/laravel/framework/pull/16858))
- Add place-holder replacers for `*_or_equal` rules ([#17030](https://github.com/laravel/framework/pull/17030))
- Made `sometimes()` chainable ([#17241](https://github.com/laravel/framework/pull/17241))
- Support wildcards in `MessageBag::first()` ([#15217](https://github.com/laravel/framework/pull/15217))
- Support implicit keys in `MessageBag::first()` and `MessageBag::first()` ([#17001](https://github.com/laravel/framework/pull/17001))
- Support arrays with empty string as key ([#17427](https://github.com/laravel/framework/pull/17427))
- Add type check to `validateUrl()` ([#17504](https://github.com/laravel/framework/pull/17504))
