# Release Notes for 7.x

## [Unreleased](https://github.com/laravel/framework/compare/v7.30.5...7.x)


## [v7.30.5 (2021-11-17)](https://github.com/laravel/framework/compare/v7.30.4...v7.30.5)

### Added
- Added new line to `DetectsLostConnections` ([#36373](https://github.com/laravel/framework/pull/36373))

### Fixed
- Fixed `Illuminate\View\ViewException::report()` ([#36110](https://github.com/laravel/framework/pull/36110))
- Fixed `Illuminate\Redis\Connections\PhpRedisConnection::spop()` ([#36106](https://github.com/laravel/framework/pull/36106))
- Fixed `Illuminate/Database/Query/Builder::limit()` to only cast integer when given other than null ([#39653](https://github.com/laravel/framework/pull/39653))
- Fixes database offset value with non numbers ([#39656](https://github.com/laravel/framework/pull/39656))

### Changed
- Pipe new through render and report exception methods ([#36037](https://github.com/laravel/framework/pull/36037))
- Typecast page number as integer in `Illuminate\Pagination\AbstractPaginator::resolveCurrentPage()` ([#36055](https://github.com/laravel/framework/pull/36055))


## [v7.30.4 (2021-01-21)](https://github.com/laravel/framework/compare/v7.30.3...v7.30.4)

### Fixed
- Fixed empty html mail ([#35941](https://github.com/laravel/framework/pull/35941))
- Fixed type error in `Illuminate\Http\Concerns\InteractsWithContentTypes::isJson()` ([#35956](https://github.com/laravel/framework/pull/35956))
- Limit expected bindings ([#35972](https://github.com/laravel/framework/pull/35972), [006873d](https://github.com/laravel/framework/commit/006873df411d28bfd03fea5e7f91a2afe3918498))


## [v7.30.3 (2021-01-15)](https://github.com/laravel/framework/compare/v7.30.2...v7.30.3)


## [v7.30.2 (2021-01-13)](https://github.com/laravel/framework/compare/v7.30.1...v7.30.2)

### Added
- Added strings to `DetectsLostConnections` ([#35752](https://github.com/laravel/framework/pull/35752))

### Fixed
- Fixed error from missing null check on PHP 8 ([#35797](https://github.com/laravel/framework/pull/35797))
- Limit expected bindings ([#35865](https://github.com/laravel/framework/pull/35865))

### Changed
- Retry connection if DNS lookup fails ([#35790](https://github.com/laravel/framework/pull/35790))


## [v7.30.1 (2020-12-22)](https://github.com/laravel/framework/compare/v7.30.0...v7.30.1)

### Fixed
- Backport for fix issue with polymorphic morphMaps with literal 0 ([#35487](https://github.com/laravel/framework/pull/35487))
- Fixed mime validation for jpeg files ([#35518](https://github.com/laravel/framework/pull/35518))
- Fixed `Illuminate\Validation\Concerns\ValidatesAttributes::validateJson()` for PHP8 ([#35646](https://github.com/laravel/framework/pull/35646))
- Catch DecryptException with invalid X-XSRF-TOKEN in `Illuminate\Foundation\Http\Middleware\VerifyCsrfToken` ([#35671](https://github.com/laravel/framework/pull/35671))


## [v7.30.0 (2020-12-01)](https://github.com/laravel/framework/compare/v7.29.3...v7.30.0)

### Fixed
- Turn the eloquent collection into a base collection if mapWithKeys loses models ([#35129](https://github.com/laravel/framework/pull/35129))
- Fixed pivot restoration ([#35218](https://github.com/laravel/framework/pull/35218))
- Fixing BroadcastException message in PusherBroadcaster@broadcast ([#35290](https://github.com/laravel/framework/pull/35290))
- Fixed generic DetectsLostConnection string ([#35323](https://github.com/laravel/framework/pull/35323))
- Backport Redis context option ([#35370](https://github.com/laravel/framework/pull/35370))
- Fixed validating image/jpeg images after Symfony/Mime update ([#35419](https://github.com/laravel/framework/pull/35419))

### Changed
- Updated `aws/aws-sdk-php` suggest to `^3.155` ([#35267](https://github.com/laravel/framework/pull/35267))


## [v7.29.3 (2020-11-03)](https://github.com/laravel/framework/compare/v7.29.2...v7.29.3)

### Fixed
- Added php 8 support for Illuminate Testing 7.x ([#35045](https://github.com/laravel/framework/pull/35045))


## [v7.29.2 (2020-10-29)](https://github.com/laravel/framework/compare/v7.29.1...v7.29.2)

### Fixed
- [Add some fixes](https://github.com/laravel/framework/compare/v7.29.1...v7.29.2) 


## [v7.29.1 (2020-10-29)](https://github.com/laravel/framework/compare/v7.29.0...v7.29.1)

### Fixed
- Fixed alias usage in `Eloquent` ([6091048](https://github.com/laravel/framework/commit/609104806b8b639710268c75c22f43034c2b72db))
- Fixed `Illuminate\Support\Reflector::isCallable()` ([a90f344](https://github.com/laravel/framework/commit/a90f344c66f0a5bb1d718f8bbd20c257d4de9e02))


## [v7.29.0 (2020-10-29)](https://github.com/laravel/framework/compare/v7.28.4...v7.29.0)

### Added
- Full PHP 8.0 Support ([#34884](https://github.com/laravel/framework/pull/34884), [28bb76e](https://github.com/laravel/framework/commit/28bb76efbcfc5fee57307ffa062b67ff709240dc), [#33388](https://github.com/laravel/framework/pull/33388))
- Added `Illuminate\Support\Reflector::isCallable()` ([#34994](https://github.com/laravel/framework/pull/34994), [8c16891](https://github.com/laravel/framework/commit/8c16891c6e7a4738d63788f4447614056ab5136e), [31917ab](https://github.com/laravel/framework/commit/31917abcfa0db6ec6221bb07fc91b6e768ff5ec8), [11cfa4d](https://github.com/laravel/framework/commit/11cfa4d4c92bf2f023544d58d51b35c5d31dece0), [#34999](https://github.com/laravel/framework/pull/34999))

### Changed
- Bump minimum PHP version to v7.2.5 ([#34928](https://github.com/laravel/framework/pull/34928))

### Fixed
- Fixed ambigious column on many to many with select load ([5007986](https://github.com/laravel/framework/commit/500798623d100a9746b2931ae6191cb756521f05))
 

## [v7.28.4 (2020-10-06)](https://github.com/laravel/framework/compare/v7.28.3...v7.28.4)

### Fixed
- Added missed RESET_THROTTLED constant to Password Facade ([#34641](https://github.com/laravel/framework/pull/34641))


## [v7.28.3 (2020-09-17)](https://github.com/laravel/framework/compare/v7.28.2...v7.28.3)

### Fixed
- Fixed problems with dots in validator ([#34355](https://github.com/laravel/framework/pull/34355))


## [v7.28.2 (2020-09-15)](https://github.com/laravel/framework/compare/v7.28.1...v7.28.2)

### Fixed
- Do not used `now` helper in `Illuminate/Cache/DatabaseLock::expiresAt()` ([#34262](https://github.com/laravel/framework/pull/34262))
- Fixed `Illuminate\View\ComponentAttributeBag::whereDoesntStartWith()` ([#34329](https://github.com/laravel/framework/pull/34329))


## [v7.28.1 (2020-09-09)](https://github.com/laravel/framework/compare/v7.28.0...v7.28.1)

### Revert
- Revert of ["Fixed for empty fallback_locale in `Illuminate\Translation\Translator`"](https://github.com/laravel/framework/pull/34136) ([7c54eb6](https://github.com/laravel/framework/commit/7c54eb678d58fb9ee7f532a5a5842e6f0e1fe4c9))


## [v7.28.0 (2020-09-08)](https://github.com/laravel/framework/compare/v7.27.0...v7.28.0)

### Added
- Added expectsTable console assertion ([74e1fca](https://github.com/laravel/framework/commit/74e1fca5fa333e32e24a7aa24049d5303a1bf281), [c6cf381](https://github.com/laravel/framework/commit/c6cf38139d2524a7c3accb606e3fb1b035c98d6a))

### Fixed
- Use `getTouchedRelations` when touching owners ([#34100](https://github.com/laravel/framework/pull/34100))
- Fixed for empty fallback_locale in `Illuminate\Translation\Translator` ([#34136](https://github.com/laravel/framework/pull/34136))
- Fixed `Illuminate\Database\Schema\Grammars\SqlServerGrammar::compileColumnListing()` for tables with schema ([#34076](https://github.com/laravel/framework/pull/34076))
- Fixed Significant performance issue in Eloquent Collection loadCount() method ([#34177](https://github.com/laravel/framework/pull/34177))


## [v7.27.0 (2020-09-01)](https://github.com/laravel/framework/compare/v7.26.1...v7.27.0)

### Added
- Allow to use alias of morphed model ([#34032](https://github.com/laravel/framework/pull/34032))
- Introduced basic padding (both, left, right) methods to Str and Stringable ([#34053](https://github.com/laravel/framework/pull/34053))

### Refactoring
- RefreshDatabase migration commands parameters moved to methods ([#34007](https://github.com/laravel/framework/pull/34007), [8b35c8e](https://github.com/laravel/framework/commit/8b35c8e6ba5879e71fd81fd03b5687ee2b46c55a), [256f71c](https://github.com/laravel/framework/commit/256f71c1f81da2d4bb3e327b18389ac43fa97a72))

### Changed
- Allow to reset forced scheme and root-url in UrlGenerator ([#34039](https://github.com/laravel/framework/pull/34039))
- Updating the make commands to use a custom views path ([#34060](https://github.com/laravel/framework/pull/34060), [b593c62](https://github.com/laravel/framework/commit/b593c6242942623fcc12638d0390da7c58dbbb11))
- Using "public static property" in View Component causes an error ([#34058](https://github.com/laravel/framework/pull/34058))
- Changed postgres processor ([#34055](https://github.com/laravel/framework/pull/34055))


## [v7.26.1 (2020-08-27)](https://github.com/laravel/framework/compare/v7.26.0...v7.26.1)

### Fixed
- Fixed offset error on invalid remember token ([#34020](https://github.com/laravel/framework/pull/34020))
- Only prepend scheme to PhpRedis host when necessary ([#34017](https://github.com/laravel/framework/pull/34017))
- Fixed `whereKey` and `whereKeyNot` in `Illuminate\Database\Eloquent\Builder` ([#34031](https://github.com/laravel/framework/pull/34031))


## [v7.26.0 (2020-08-25)](https://github.com/laravel/framework/compare/v7.25.0...v7.26.0)

### Added
- Added `whenHas` and `whenFilled` methods to  `Illuminate\Http\Concerns\InteractsWithInput` class ([#33829](https://github.com/laravel/framework/pull/33829))
- Added email validating with custom class ([#33835](https://github.com/laravel/framework/pull/33835))
- Added `Illuminate\View\ComponentAttributeBag::whereDoesntStartWith()` ([#33851](https://github.com/laravel/framework/pull/33851))
- Allow setting synchronous_commit for Postgres ([#33897](https://github.com/laravel/framework/pull/33897))
- Allow nested errors in `Illuminate\Testing\TestResponse::assertJsonValidationErrors()` ([#33989](https://github.com/laravel/framework/pull/33989))
- Added support for stream reads to `FilesystemManager` ([#34001](https://github.com/laravel/framework/pull/34001))

### Fixed
- Fix defaultTimezone not respected in scheduled Events ([#33834](https://github.com/laravel/framework/pull/33834))
- Fixed usage of Support `Collection#countBy($key)` ([#33852](https://github.com/laravel/framework/pull/33852))
- Fixed route registerar bug ([42ba0ef](https://github.com/laravel/framework/commit/42ba0ef3e379cb1e0fa38c3d3297109ff1234a1d))
- Fixed key composition for attribute with dot at validation error messages ([#33932](https://github.com/laravel/framework/pull/33932))
- Fixed the `dump` method for `LazyCollection` ([#33944](https://github.com/laravel/framework/pull/33944))
- Fixed dimension ratio calculation in `Illuminate\Validation\Concerns\ValidatesAttributes::failsRatioCheck()` ([#34003](https://github.com/laravel/framework/pull/34003))

### Changed
- Implement LockProvider on DatabaseStore ([#33844](https://github.com/laravel/framework/pull/33844))
- Publish resources.stub in stub:publish command ([#33862](https://github.com/laravel/framework/pull/33862))
- Handle argon failures robustly ([#33856](https://github.com/laravel/framework/pull/33856))
- Normalize scheme in Redis connections ([#33892](https://github.com/laravel/framework/pull/33892))
- Cast primary key to string when $keyType is string ([#33930](https://github.com/laravel/framework/pull/33930))
- Load anonymous components from packages ([#33954](https://github.com/laravel/framework/pull/33954))
- Check no-interaction flag exists and is true for Artisan commands ([#33950](https://github.com/laravel/framework/pull/33950))

### Deprecated
- Deprecate `Illuminate\Database\Eloquent\Model::removeTableFromKey()` ([#33859](https://github.com/laravel/framework/pull/33859))


## [v7.25.0 (2020-08-11)](https://github.com/laravel/framework/compare/v7.24.0...v7.25.0)

### Added
- Added support to use `where` in `apiResource` method ([#33790](https://github.com/laravel/framework/pull/33790), [3dcc4a6](https://github.com/laravel/framework/commit/3dcc4a6bc6640b3d577c6740d63b6ef3df42e124))
- Support `tls://` scheme when using `url` in Redis config ([#33800](https://github.com/laravel/framework/pull/33800))
- Scoped resource routes ([#33752](https://github.com/laravel/framework/pull/33752))
- Added Once blade Blocks ([#33812](https://github.com/laravel/framework/pull/33812))
- Let mailables accept a simple array of email addresses as cc or bcc ([#33810](https://github.com/laravel/framework/pull/33810))
- Added support for PhpRedis 5.3 options parameter ([#33799](https://github.com/laravel/framework/pull/33799))

### Changed
- Removed quotes when setting isolation level for mysql connections ([#33805](https://github.com/laravel/framework/pull/33805))
- Make LazyCollection#countBy be lazy ([#33801](https://github.com/laravel/framework/pull/33801))

### Fixed
- Revert changes to MailMessage ([#33816](https://github.com/laravel/framework/pull/33816))


## [v7.24.0 (2020-08-07)](https://github.com/laravel/framework/compare/v7.23.2...v7.24.0)

### Added
- Added possibility to configure isolation level for mysql connections ([#33783](https://github.com/laravel/framework/pull/33783), [c6a3174](https://github.com/laravel/framework/commit/c6a317405e5e9075206a019246a8a79d0c68def4))
- Added plain text only notifications ([#33781](https://github.com/laravel/framework/pull/33781))

### Changed
- Verify column names are actual columns when using guarded ([#33777](https://github.com/laravel/framework/pull/33777))


## [v7.23.2 (2020-08-06)](https://github.com/laravel/framework/compare/v7.23.1...v7.23.2)

### Fixed
- Fixed `Illuminate\Support\Arr::query()` ([c6f9ae2](https://github.com/laravel/framework/commit/c6f9ae2b6fdc3c1716938223de731b97f6a5a255))
- Don't allow mass filling with table names ([9240404](https://github.com/laravel/framework/commit/9240404b22ef6f9e827577b3753e4713ddce7471), [f5fa6e3](https://github.com/laravel/framework/commit/f5fa6e3a0fbf9a93eab45b9ae73265b4dbfc3ad7))


## [v7.23.1 (2020-08-06)](https://github.com/laravel/framework/compare/v7.23.0...v7.23.1)

### Added
- Added isNotFilled() method to Request ([#33732](https://github.com/laravel/framework/pull/33732))

### Fixed
- Fixed `Illuminate\Database\Eloquent\Concerns\GuardsAttributes::isGuarded()` ([1b70bef](https://github.com/laravel/framework/commit/1b70bef5fd7cc5da74abcdf79e283f830fa3b0a4), [624d873](https://github.com/laravel/framework/commit/624d873733388aa2246553a3b465e38554953180), [b70876a](https://github.com/laravel/framework/commit/b70876ac80759fbf168c91cdffd7a2b2305e27cb))
- Fixed escaping quotes ([687df01](https://github.com/laravel/framework/commit/687df01fa19c99546c1ae1dd53c2a465459b50dc))


## [v7.23.0 (2020-08-04)](https://github.com/laravel/framework/compare/v7.22.4...v7.23.0)

### Added
- Added dynamic slot (directive) name support ([#33724](https://github.com/laravel/framework/pull/33724))
- Added plain mail to notifications ([#33725](https://github.com/laravel/framework/pull/33725))
- Support the `sink` option when using Http::fake() ([#33720](https://github.com/laravel/framework/pull/33720), [fba984b](https://github.com/laravel/framework/commit/fba984b05081f8aee19447caa0d92624bcf04312))
- Added whereBetweenColumns | orWhereBetweenColumns | whereNotBetweenColumns | orWhereNotBetweenColumns methods to `Illuminate\Database\Query\Builder` ([#33728](https://github.com/laravel/framework/pull/33728))

### Changed
- Ignore numeric field names in validators ([#33712](https://github.com/laravel/framework/pull/33712))
- Fixed validation rule 'required_unless' when other field value is boolean. ([#33715](https://github.com/laravel/framework/pull/33715))


## [v7.22.4 (2020-07-27)](https://github.com/laravel/framework/compare/v7.22.3...v7.22.4)

### Update
- Update cookies encryption ([release](https://github.com/laravel/framework/compare/v7.22.3...v7.22.4))


## [v7.22.3 (2020-07-27)](https://github.com/laravel/framework/compare/v7.22.2...v7.22.3)

### Update
- Update cookies encryption ([release](https://github.com/laravel/framework/compare/v7.22.2...v7.22.3))


## [v7.22.2 (2020-07-27)](https://github.com/laravel/framework/compare/v7.22.1...v7.22.2)

### Fixed
- Fixed cookie issues encryption ([c9ce261](https://github.com/laravel/framework/commit/c9ce261a9f7b8e07c9ebc8a7d45651ee1cf86215), [5786aa4](https://github.com/laravel/framework/commit/5786aa4a388adfcc62862573275bd37d49aa07d7))


## [v7.22.1 (2020-07-27)](https://github.com/laravel/framework/compare/v7.22.0...v7.22.1)

### Fixed
- Fixed cookie issues ([bb9db21](https://github.com/laravel/framework/commit/bb9db21af137344feffa192fcabe4e439c8b0f60))


## [v7.22.0 (2020-07-27)](https://github.com/laravel/framework/compare/v7.21.0...v7.22.0)

### Added
- Added `sectionMissing` Blade Directive ([#33614](https://github.com/laravel/framework/pull/33614))
- Added range option to queue:retry command ([#33627](https://github.com/laravel/framework/pull/33627))

### Fixed
- Prevent usage of get*AtColumn() when model has no timestamps ([#33634](https://github.com/laravel/framework/pull/33634))
- Don't decrement transaction below 0 in `Illuminate\Database\Concerns\ManagesTransactions::handleCommitTransactionException()` ([7681795](https://github.com/laravel/framework/commit/768179578e5492b5f80c391bd43b233938e16e27))
- Fixed transaction problems on closure transaction ([c4cdfc7](https://github.com/laravel/framework/commit/c4cdfc7c54127b772ef10f37cfc9ef8e9d6b3227))
- Prevent to serialize uninitialized properties ([#33644](https://github.com/laravel/framework/pull/33644))
- Fixed missing statement preventing deletion in `Illuminate\Database\Eloquent\Relations\MorphPivot::delete()` ([#33648](https://github.com/laravel/framework/pull/33648))

### Changed
- Throw a TypeError if concrete is not a string or closure in `Illuminate\Container\Container::bind()` ([#33539](https://github.com/laravel/framework/pull/33539))
- Add HTML comment block around inline inspiring quote for consistency with blade template version ([#33625](https://github.com/laravel/framework/pull/33625))
- Improve cookie encryption ([#33662](https://github.com/laravel/framework/pull/33662))


## [v7.21.0 (2020-07-21)](https://github.com/laravel/framework/compare/v7.20.0...v7.21.0)

### Added
- Added `Illuminate\Database\Schema\ForeignKeyDefinition::nullOnDelete()` ([#33551](https://github.com/laravel/framework/pull/33551))
- Added `getFallbackLocale()` and `setFallbackLocale()` methods to `Illuminate\Foundation\Application` ([#33595](https://github.com/laravel/framework/pull/33595))

### Fixed
- Fixed `Illuminate/Redis/Connections/PhpRedisConnection::*scan()` returns ([d3d36f0](https://github.com/laravel/framework/commit/d3d36f059ef1c56e17d8e434e9fd3dfd6cbe6e53))
- Align (fix) nested arrays support for `assertViewHas` & `assertViewMissing` in `Illuminate\Testing\TestResponse` ([#33566](https://github.com/laravel/framework/pull/33566))
- Fixed issue where Storage::path breaks when using cache due to missing method in CachedAdapter ([#33602](https://github.com/laravel/framework/pull/33602))

### Changed
- Added a base exception for Http Client exceptions ([#33581](https://github.com/laravel/framework/pull/33581))


## [v7.20.0 (2020-07-14)](https://github.com/laravel/framework/compare/v7.19.1...v7.20.0)

### Added
- Added `Illuminate\Database\Schema\ForeignKeyDefinition::cascadeOnUpdate()` ([#33522](https://github.com/laravel/framework/pull/33522))

### Changed
- Apply model connection name to Database validation rules ([#33525](https://github.com/laravel/framework/pull/33525))
- Allow calling invokable classes using FQN in `Illuminate\Container\BoundMethod.php::call()` ([#33535](https://github.com/laravel/framework/pull/33535))


## [v7.19.1 (2020-07-10)](https://github.com/laravel/framework/compare/v7.19.0...v7.19.1)

### Added
- Added support for SQL Server LoginTimeout to specify seconds to wait before failing connection attempt ([#33472](https://github.com/laravel/framework/pull/33472))
- Added ability to simulate "withCredentials" in test requests ([#33497](https://github.com/laravel/framework/pull/33497), [aa17e75](https://github.com/laravel/framework/commit/aa17e75f216c58f03652625866f5ac5c2fcbcab7))

### Fixed
- Fixed `Illuminate\Cache\FileStore::flush()` ([#33458](https://github.com/laravel/framework/pull/33458))
- Fixed auto creating model by class name ([#33481](https://github.com/laravel/framework/pull/33481))
- Don't return nested data from validator when failing an exclude rule ([#33435](https://github.com/laravel/framework/pull/33435))
- Fixed validation nested error messages ([6615371](https://github.com/laravel/framework/commit/6615371d7c0a7431372244d21eae54696b3c19f2))
- Fixed `Illuminate\Support\Reflector` to handle parent ([#33502](https://github.com/laravel/framework/pull/33502))

### Revert
- Revert [Improve SQL Server last insert id retrieval](https://github.com/laravel/framework/pull/33453) ([#33496](https://github.com/laravel/framework/pull/33496))


## [v7.19.0 (2020-07-07)](https://github.com/laravel/framework/compare/v7.18.0...v7.19.0)

### Added
- Added `everyTwoHours()` | `everyThreeHours()` | `everyFourHours()` | `everySixHours()` methods to `Illuminate\Console\Scheduling\ManagesFrequencies` ([#33393](https://github.com/laravel/framework/pull/33393))
- Conditionally returning appended attributes in API resources ([#33422](https://github.com/laravel/framework/pull/33422))
- Added `ScheduledTaskFailed` event ([#33427](https://github.com/laravel/framework/pull/33427))
- Added `Illuminate\Support\Stringable::when()` ([#33455](https://github.com/laravel/framework/pull/33455))

### Fixed
- Fixed signed urls with custom parameters ([bcb133e](https://github.com/laravel/framework/commit/bcb133e46906e748067772cf49b2f355441815c5))
- Determine model key name correctly in Illuminate/Validation/Concerns/ValidatesAttributes.php ([a1fdd53](https://github.com/laravel/framework/commit/a1fdd536c542dabbe9882f50e849cc177dc0ad88))
- Fixed notifications database channel for anonymous notifiables ([#33409](https://github.com/laravel/framework/pull/33409))

### Changed
- Improve SQL Server last insert id retrieval ([#33430](https://github.com/laravel/framework/pull/33430), [de1d159](https://github.com/laravel/framework/commit/de1d1592f3a69bd9952431ee67e76996d00e001c))
- Make Str::endsWith return false if both haystack and needle are empty strings ([#33434](https://github.com/laravel/framework/pull/33434))


## [v7.18.0 (2020-06-30)](https://github.com/laravel/framework/compare/v7.17.2...v7.18.0)

### Added
- Added `Illuminate\Http\Client\PendingRequest::withMiddleware()` ([#33315](https://github.com/laravel/framework/pull/33315), [b718d3a](https://github.com/laravel/framework/commit/b718d3a06d7009c0fd0237222602c1e42681b6a3))
- Make ComponentAttributeBag Macroable ([#33354](https://github.com/laravel/framework/pull/33354))
- Added `filter` and `whereStartsWith` and `thatStartWith` to `Illuminate\View\ComponentAttributeBag` ([0abe2db](https://github.com/laravel/framework/commit/0abe2dbed9d9b1c4a733a4c24e8383d747134286), [07ee3e8](https://github.com/laravel/framework/commit/07ee3e820b34df5e422fb868886fd190880dfc7f))
- Added `Illuminate\Database\Eloquent\Collection::toQuery()` ([#33356](https://github.com/laravel/framework/pull/33356), [15586fa](https://github.com/laravel/framework/commit/15586fa6691884db18627721f6e143c3e035ddc0))
- Added `first()` to `Illuminate\View\ComponentAttributeBag` ([#33358](https://github.com/laravel/framework/pull/33358), [731b94f](https://github.com/laravel/framework/commit/731b94f1734dcdb97a9466948111ab639ac11a2a))
- Added `everyTwoMinutes()` | `everyThreeMinutes()` | `everyFourMinutes()` methods to `Illuminate/Console/Scheduling/ManagesFrequencies` ([#33379](https://github.com/laravel/framework/pull/33379))

### Fixed
- Fixed `ConfigurationUrlParser` query decoding ([#33340](https://github.com/laravel/framework/pull/33340))
- Fixed exists in `Illuminate\Database\Eloquent\Relations\Concerns\AsPivot::delete()` ([#33347](https://github.com/laravel/framework/pull/33347))

### Changed
- Replace placeholder for dots and asterisks in validator ([#33367](https://github.com/laravel/framework/pull/33367))


## [v7.17.2 (2020-06-24)](https://github.com/laravel/framework/compare/v7.17.1...v7.17.2)

### Added
- Added `Illuminate\Http\Client\PendingRequest::withBody()` method ([1e1f531](https://github.com/laravel/framework/commit/1e1f5311f062d62468fe2d3cef1695b8fa338cfb), [7b0b437](https://github.com/laravel/framework/commit/7b0b4375bbe231a3b96c739ff144b1df1465a387))

### Fixed
- Fixed `Illuminate\Database\Eloquent\Concerns\HasAttributes::getOriginal()` ([b20125d](https://github.com/laravel/framework/commit/b20125d7bf270bcd6cc651114512a2dc7f182a96), [899c765](https://github.com/laravel/framework/commit/899c765e89573d8a64e16b008af519096e12d534), [2937cce](https://github.com/laravel/framework/commit/2937cce360f4feb96e93d6cf86e24f2e8c0832fc))

### Revert
- Revert "Fixed `Model::originalIsEquivalent()` with floats ([#33259](https://github.com/laravel/framework/pull/33259), [d68d915](https://github.com/laravel/framework/commit/d68d91516db6d1b9cba8a72f99b2c7e8223e988f))" [bf3cb6f](https://github.com/laravel/framework/commit/bf3cb6f6979df2d6965d2e0aa731724d0e2b15e5)


## [v7.17.1 (2020-06-23)](https://github.com/laravel/framework/compare/v7.17.0...v7.17.1)

### Fixed
- Fixed "Undefined variable: current" exception  in `Illuminate\Database\Eloquent\Concerns\HasAttributes::originalIsEquivalent()` [#33308](https://github.com/laravel/framework/pull/33308)


## [v7.17.0 (2020-06-23)](https://github.com/laravel/framework/compare/v7.16.1...v7.17.0)

### Added
- Added `Illuminate\Console\Scheduling\ManagesFrequencies::lastDayOfMonth()` ([#33241](https://github.com/laravel/framework/pull/33241), [be194a8](https://github.com/laravel/framework/commit/be194a8a7b302fa68b1b2ed66d440f9f91dfec9f))
- Allow array based event listeners ([7594267](https://github.com/laravel/framework/commit/75942673f6f54dc70fec246051171183af8e06e3))
- Allow array callback format with non-static methods in `Illuminate\Auth\Access\Gate::define()` ([b7977d3](https://github.com/laravel/framework/commit/b7977d322a2c9baf28cc127cee09c70727c5f56e))
- Added `Illuminate\Console\Scheduling\ManagesFrequencies::time()` parameter on twiceMonthly function ([#33274](https://github.com/laravel/framework/pull/33274))
- Added `providerIsLoaded` method to `Illuminate\Foundation\Application` ([#33286](https://github.com/laravel/framework/pull/33286), [b87233f](https://github.com/laravel/framework/commit/b87233f48da0b4f219adebd851acd22058dfd551))

### Fixed
- Fixed domain binding with custom fields in `Illuminate\Routing\Route::domain()` ([#33231](https://github.com/laravel/framework/pull/33231))
- Fixed `Model::originalIsEquivalent()` with floats ([#33259](https://github.com/laravel/framework/pull/33259), [d68d915](https://github.com/laravel/framework/commit/d68d91516db6d1b9cba8a72f99b2c7e8223e988f))


## [v7.16.1 (2020-06-16)](https://github.com/laravel/framework/compare/v7.16.0...v7.16.1)

### Revert
- Revert "handle array callbacks" in event dispatcher ([4e3fedb](https://github.com/laravel/framework/commit/4e3fedb2a401986676f9d6aa5f244e95e9c92444))


## [v7.16.0 (2020-06-16)](https://github.com/laravel/framework/compare/v7.15.0...v7.16.0)

### Added
- Added `makeVisibleIf` and `makeHiddenIf` methods to `Illuminate\Database\Eloquent\Concerns\HidesAttributes` ([#33176](https://github.com/laravel/framework/pull/33176), [42383e4](https://github.com/laravel/framework/commit/42383e4ba8806ac0ab69f80d0325fa01fd9c30f4))
- Added option to specify a custom guard for the `make:policy` command ([#33210](https://github.com/laravel/framework/pull/33210), [13e3b65](https://github.com/laravel/framework/commit/13e3b65bad5062eeba34aa2f39effd0fc4081ccd))
- Added `theme` property to `Illuminate\Mail\Mailable` class ([#33218](https://github.com/laravel/framework/pull/33218))

### Changed
- Improved the reflector ([#33184](https://github.com/laravel/framework/pull/33184))
- Streamline ease of use with relation subquery ([#33180](https://github.com/laravel/framework/pull/33180))
- Improve event subscribers ([#33191](https://github.com/laravel/framework/pull/33191), [058d92f](https://github.com/laravel/framework/commit/058d92f2842211a0bc60222fd464ca5350965c22), [b80ddf4](https://github.com/laravel/framework/commit/b80ddf458bd08de375d83b716a1309ed927197aa))


## [v7.15.0 (2020-06-09)](https://github.com/laravel/framework/compare/v7.14.1...v7.15.0)

### Added
- Added extendable relations for models ([#33025](https://github.com/laravel/framework/pull/33025))
- Added `Illuminate\Foundation\Testing\Concerns\MakesHttpRequests::withToken()` ([#33075](https://github.com/laravel/framework/pull/33075), [79383a1](https://github.com/laravel/framework/commit/79383a129bf213177ff00ec1ba7c396da5d7749b))
- Added the ability to `Illuminate\Database\Eloquent\Relations\HasOneOrMany::makeMany()` (create many without saving) ([#33021](https://github.com/laravel/framework/pull/33021))
- Added `Illuminate\Database\Schema\Blueprint::foreignUuid()` ([#33129](https://github.com/laravel/framework/pull/33129))
- Allow setting the event handler queue via a `viaQueue()` method ([#32770](https://github.com/laravel/framework/pull/32770), [852a927](https://github.com/laravel/framework/commit/852a927d254af9719c9fde6eb31466472fd03dfc)) 

### Fixed
- Fixed `Model::withoutEvents()` not registering listeners inside boot() ([#33149](https://github.com/laravel/framework/pull/33149), [4bb32ae](https://github.com/laravel/framework/commit/4bb32aea50eec4c3cc8b77f463e4a96213a0af09))


## [v7.14.1 (2020-06-03)](https://github.com/laravel/framework/compare/v7.14.0...v7.14.1)

### Added
- Added missing `symfony/mime` suggest ([#33067](https://github.com/laravel/framework/pull/33067))

### Fixed
- Fixed `Illuminate\Database\Eloquent\Relations\MorphToMany::getCurrentlyAttachedPivots()` ([110b129](https://github.com/laravel/framework/commit/110b129531df172f03bf163f561c71123fac6296))


## [v7.14.0 (2020-06-02)](https://github.com/laravel/framework/compare/v7.13.0...v7.14.0)

### Added
- Views: make attributes available within render method ([#32978](https://github.com/laravel/framework/pull/32978))
- Added `forceDeleted` method to `SoftDeletes` ([#32982](https://github.com/laravel/framework/pull/32982))
- Added `Illuminate\Filesystem\Filesystem::guessExtension()` method ([#33001](https://github.com/laravel/framework/pull/33001), [d26be90](https://github.com/laravel/framework/commit/d26be90df373dfd911029679b1765a46ae091d34))
- Added `Illuminate\Http\Client\Request::toPsrRequest()` ([#33016](https://github.com/laravel/framework/pull/33016))
- Added `Illuminate\Support\MessageBag::addIf()` method ([50efe09](https://github.com/laravel/framework/commit/50efe099b59e75563298deb992017b4cabfb021d))
- Provide `psr/container-implementation` ([#33020](https://github.com/laravel/framework/pull/33020))
- Support PHP 8's reflection API ([#33039](https://github.com/laravel/framework/pull/33039), [6018c1d](https://github.com/laravel/framework/commit/6018c1d18e7b764c17307c1f98d64482a00a668d))

### Fixed
- Restore `app()->getCached*Path()` absolute '/' behavior in Windows ([#32969](https://github.com/laravel/framework/pull/32969))
- Fixed [Issue with using "sticky" option with Postgresql driver and read/write connections.](https://github.com/laravel/framework/issues/32966) ([#32973](https://github.com/laravel/framework/pull/32973))
- Fixed custom class cast with dates ([2d52abc](https://github.com/laravel/framework/commit/2d52abc33865cc29b8e92a41ed7ad9a2b5383a11))
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` ([00e9ed7](https://github.com/laravel/framework/commit/00e9ed76483ea6ad1264676e7b1095b23e16a433))
- Fixed bug with update existing pivot and polymorphic many to many ([684208b](https://github.com/laravel/framework/commit/684208b10460b49fa34354cc42f33b9b7135814f))
- Fixed localization in tailwind view ([f2eb9ab](https://github.com/laravel/framework/commit/f2eb9ab82f7f5b126faf05241afe75e341fa22b1))

### Changed
- Use new line for `route:list` middleware ([#32993](https://github.com/laravel/framework/pull/32993))
- Disallow generation commands with reserved names ([#33037](https://github.com/laravel/framework/pull/33037))


## [v7.13.0 (2020-05-26)](https://github.com/laravel/framework/compare/v7.12.0...v7.13.0)

### Added
- Added `Illuminate\Pagination\AbstractPaginator::useTailwind()` ([2279b73](https://github.com/laravel/framework/commit/2279b73d5553c34c970128264a248f3bb57afad6), [bf1eef4](https://github.com/laravel/framework/commit/bf1eef400951dcee04839a9ab7c15da1a807f89c), [13a9ec3](https://github.com/laravel/framework/commit/13a9ec349b8bcaa31d1757752ae0304f0328e5ce))

### Fixed
- Fixed route list command for excluded middleware ([7ebd211](https://github.com/laravel/framework/commit/7ebd21193df520d78269d7abd740537a2fae889e))
- Fixed behavior of oneachside = 1 with paginator in `Illuminate\Pagination\UrlWindow` ([c59cffa](https://github.com/laravel/framework/commit/c59cffa7825498e1d419d8c86cd8527520f718cb), [5d817be](https://github.com/laravel/framework/commit/5d817bef236559cc9368e1ec4ceafa8a790f751d))

### Changed
- Using an indexed array as the limit modifier for phpredis zrangebyscore ([#32952](https://github.com/laravel/framework/pull/32952))


## [v7.12.0 (2020-05-19)](https://github.com/laravel/framework/compare/v7.11.0...v7.12.0)

### Added
- Added `Illuminate\Http\Middleware\TrustHosts` ([9229264](https://github.com/laravel/framework/commit/92292649621f2aadc84ab94376244650a9f55696))
- Added ability to skip middleware from resource routes ([#32891](https://github.com/laravel/framework/pull/32891))

### Fixed
- Fixed Queued Mail MessageSent Listener With Attachments ([#32795](https://github.com/laravel/framework/pull/32795))
- Added error clearing before sending in `Illuminate\Mail\Mailer::sendSwiftMessage()` ([#32799](https://github.com/laravel/framework/pull/32799))
- Avoid foundation function call in the auth component ([#32805](https://github.com/laravel/framework/pull/32805))
- Fixed inferred table reference for `Illuminate\Database\Schema\ForeignIdColumnDefinition::constrained()` ([#32847](https://github.com/laravel/framework/pull/32847))
- Fixed wrong component generation ([73060db](https://github.com/laravel/framework/commit/73060db7c5541fadf5e4f2874a89d18621d705a3))
- Fixed bug with request rebind and url defaults in `Illuminate\Routing\UrlGenerator` ([6ad92bf](https://github.com/laravel/framework/commit/6ad92bf9a8552a7759a7757cf821b01969baf0b6))
- Fixed `Illuminate\Cache\ArrayStore::increment()` bug that changes expiration to forever ([#32875](https://github.com/laravel/framework/pull/32875))

### Changed
- Don't cache non objects in `Illuminate/Database/Eloquent/Concerns/HasAttributes::getClassCastableAttributeValue()` ([894fe22](https://github.com/laravel/framework/commit/894fe22c6c111b224de5bada24dcbba4c93f0305))
- Added explicit `symfony/polyfill-php73` dependency ([5796b1e](https://github.com/laravel/framework/commit/5796b1e43dfe14914050a7e5dd24ddf803ec99b8))
- Set `Cache\FileStore` file permissions only once ([#32845](https://github.com/laravel/framework/pull/32845), [11c533b](https://github.com/laravel/framework/commit/11c533b9aa062f4cba1dd0fe3673bf33d275480f))
- Added alias as key of package's view components ([#32863](https://github.com/laravel/framework/pull/32863))


## [v7.11.0 (2020-05-12)](https://github.com/laravel/framework/compare/v7.10.3...v7.11.0)

### Added
- Added support for FILTER_FLAG_EMAIL_UNICODE via "email:filter_unicode" in email validator ([#32711](https://github.com/laravel/framework/pull/32711), [43a1ed1](https://github.com/laravel/framework/commit/43a1ed1ee272b77547d292af7d337c745cccd48a))
- Added `Illuminate\Support\Stringable::split()` ([#32713](https://github.com/laravel/framework/pull/32713), [19c5054](https://github.com/laravel/framework/commit/19c5054eff4d00d234cd928db1e085aaa14c4692))
- Added `orWhereIntegerInRaw()` and `orWhereIntegerNotInRaw()` to `Illuminate\Database\Query\Builder` ([#32710](https://github.com/laravel/framework/pull/32710))
- Added `Illuminate\Cache\DatabaseStore::add()` ([7fc452b](https://github.com/laravel/framework/commit/7fc452bd8d6cebd7e7a0c6cd057aea7d4e9a7fc0))
- Implement env and production Blade directives ([#32742](https://github.com/laravel/framework/pull/32742))
- Added `Illuminate\Database\Eloquent\Relations\MorphTo::morphWithCount()` method ([#32738](https://github.com/laravel/framework/pull/32738))
- Added `Illuminate\Database\Eloquent\Collection::loadMorphCount()` method ([#32739](https://github.com/laravel/framework/pull/32739))
- Added support `viaQueues` method for notifications ([e97d17c](https://github.com/laravel/framework/commit/e97d17cb6061600960bca2818f419bccca6f7da2))
- Added `loadMorph` and `loadMorphCount` methods to `Illuminate\Database\Eloquent\Model` ([#32760](https://github.com/laravel/framework/pull/32760))
- Added `Illuminate\Database\DatabaseManager::usingConnection()` method ([#32761](https://github.com/laravel/framework/pull/32761), [5f8c7de](https://github.com/laravel/framework/commit/5f8c7de58c5ba2cdb38ba50f1dfcc4c869d0e02d))
- Added `Illuminate\Http\Client\PendingRequest::head()` method ([#32782](https://github.com/laravel/framework/pull/32782))

### Fixed
- Fixed belongsToMany child relationship solving ([c5e88be](https://github.com/laravel/framework/commit/c5e88be082bc690961889812360cd6c5ba983117))
- Allow overriding the MySQL server version for strict mode ([#32708](https://github.com/laravel/framework/pull/32708))
- Added boolean to types that don't need character options ([#32716](https://github.com/laravel/framework/pull/32716))
- Fixed `Illuminate\Foundation\Testing\PendingCommand` that do not resolve 'OutputStyle::class' from the container ([#32687](https://github.com/laravel/framework/pull/32687))
- Clear resolved event facade on `Illuminate\Foundation\Testing\Concerns\MocksApplicationServices::withoutEvents()` ([d1e7f85](https://github.com/laravel/framework/commit/d1e7f85dfd79abbe4f5e01818f620f6ecc67de4d))
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` for filtered collections ([#32747](https://github.com/laravel/framework/pull/32747))
- Fixed `Illuminate\Database\Eloquent\Collection::loadCount` method to ensure count is set on all models ([#32740](https://github.com/laravel/framework/pull/32740))
- Fixed deprecated "Doctrine/Common/Inflector/Inflector" class ([#32734](https://github.com/laravel/framework/pull/32734))
- Fixed `Illuminate\Validation\Validator::getPrimaryAttribute()` ([#32775](https://github.com/laravel/framework/pull/32775))
- Revert of ["Remove `strval` from `Illuminate/Validation/ValidationRuleParser::explodeWildcardRules()`"](https://github.com/laravel/framework/commit/1c76a6f3a80fa8f756740566dffd9fa1be65c123) ([52940cf](https://github.com/laravel/framework/commit/52940cf3275cfebd47ec008fd8ae5bc6d6a42dfd))

### Changed
- Updated user model var name in `make:policy` command ([#32748](https://github.com/laravel/framework/pull/32748))
- Remove the undocumented dot keys support in validators ([#32764](https://github.com/laravel/framework/pull/32764))


## [v7.10.3 (2020-05-06)](https://github.com/laravel/framework/compare/v7.10.2...v7.10.3)

### Added
- Added `Illuminate\Http\Client\Response::failed()` ([#32699](https://github.com/laravel/framework/pull/32699))
- Added SSL SYSCALL EOF as a lost connection message ([#32697](https://github.com/laravel/framework/pull/32697))

### Fixed
- Fixed `FakerGenerator` Unique caching issue ([#32703](https://github.com/laravel/framework/pull/32703))
- Set/reset the select to from.* in `Illuminate/Database/Query/Builder::runPaginationCountQuery()` ([858f454](https://github.com/laravel/framework/commit/858f4544d5672bf277686bdb112b1ce055416413), [98a242e](https://github.com/laravel/framework/commit/98a242e21041462054b965e587c250ac7be4f912))


## [v7.10.2 (2020-05-06)](https://github.com/laravel/framework/compare/v7.10.1...v7.10.2)

### Fixed
- Updated `Illuminate\Database\Query\Builder::runPaginationCountQuery()`  to support groupBy and sub-selects ([#32688](https://github.com/laravel/framework/pull/32688))


## [v7.10.1 (2020-05-05)](https://github.com/laravel/framework/compare/v7.10.0...v7.10.1)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` ([7b32460](https://github.com/laravel/framework/commit/7b32469420258e9e52b24b2ffa7f491e79a3a870))


## [v7.10.0 (2020-05-05)](https://github.com/laravel/framework/compare/v7.9.2...v7.10.0)

### Added
- Added `artisan make:cast` command ([#32594](https://github.com/laravel/framework/pull/32594))
- Added `Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase::assertDatabaseCount()` ([#32597](https://github.com/laravel/framework/pull/32597))
- Allow configuring the auth_mode for SMTP mail driver ([#32616](https://github.com/laravel/framework/pull/32616))
- Added `hasNamedScope()` function to the Base Model ([#32622](https://github.com/laravel/framework/pull/32622), [#32631](https://github.com/laravel/framework/pull/32631))
- Allow doing truth-test assertions with just a closure ([#32626](https://github.com/laravel/framework/pull/32626), [f69ad90](https://github.com/laravel/framework/commit/f69ad90b9d508b59a017d0e412d8228e71230a51), [22d6fca](https://github.com/laravel/framework/commit/22d6fcafba610364aabb2b8e5c385edf56ae0156))
- Run pagination count as subquery for group by and havings ([#32624](https://github.com/laravel/framework/pull/32624))
- Added Callbacks with Output to Console Schedule ([#32633](https://github.com/laravel/framework/pull/32633), [35a7883](https://github.com/laravel/framework/commit/35a788316a0bc20295abe048a1bc1aa34a729ec7), [8d8d620](https://github.com/laravel/framework/commit/8d8d62024188c870df9dec1eeac428089f44c18e))
- Added `Cache::lock()` support for the database cache driver ([#32639](https://github.com/laravel/framework/pull/32639), [573831b](https://github.com/laravel/framework/commit/573831b5028aa440f555d1072672db5069f306d1))
- Same-session ID request concurrency limiting ([#32636](https://github.com/laravel/framework/pull/32636))
- Add `skipUntil` and `skipWhile` methods to the collections ([#32672](https://github.com/laravel/framework/pull/32672), [#32676](https://github.com/laravel/framework/pull/32676))
- Support delete with limit on sqlsrv ([f16d325](https://github.com/laravel/framework/commit/f16d3256f93be71935ed86951e58f90b83912feb))
- Added `mergeFillable()` and `mergeGuarded()` to `Model` ([#32679](https://github.com/laravel/framework/pull/32679))

### Fixed
- Prevents a memory leak in Faker ([2228233](https://github.com/laravel/framework/commit/222823377c936ab4cceeb1fa42db84821c04bff6))
- Fixed setting component name and attributes ([#32599](https://github.com/laravel/framework/pull/32599), [f8ff3ca](https://github.com/laravel/framework/commit/f8ff3cae1ebf2865ef7263b88559c581d48cde6e))
- Fixed `Illuminate\Foundation\Testing\TestResponse::assertSessionHasInput()` ([f0639fd](https://github.com/laravel/framework/commit/f0639fda45fc2874986fe409d944dde21d42c6f3))
- Set relation connection on eager loaded MorphTo ([#32602](https://github.com/laravel/framework/pull/32602))
- Filtering null's in `hasMorph()` ([#32614](https://github.com/laravel/framework/pull/32614))
- Fixed `Illuminate\Foundation\Console\EventMakeCommand::alreadyExists()` ([7bba4bf](https://github.com/laravel/framework/commit/7bba4bfbedb85ee252464aa932414d5517240722))
- Fixed `Illuminate\Console\Scheduling\Schedule::compileParameters()` ([cfc3ac9](https://github.com/laravel/framework/commit/cfc3ac9c8b0a593d264ae722ab90601fa4882d0e), [36e215d](https://github.com/laravel/framework/commit/36e215dd39cd757a8ffc6b17794de60476b2289d))
- Fixed bug with model name in `Illuminate\Database\Eloquent\RelationNotFoundException::make()` ([f72a166](https://github.com/laravel/framework/commit/f72a1662ab64cc543c532941b1ab1279001af8e9))
- Allow trashed through parents to be included in has many through queries ([#32609](https://github.com/laravel/framework/pull/32609))

### Changed
- Changed `Illuminate/Database/Eloquent/Relations/Concerns/AsPivot::fromRawAttributes()` ([6c502c1](https://github.com/laravel/framework/commit/6c502c1135082e8b25f2720931b19d36eeec8f41))
- Restore оnly common relations ([#32613](https://github.com/laravel/framework/pull/32613), [d82f78b](https://github.com/laravel/framework/commit/d82f78b13631c4a04b9595099da0022ca3d8b94e), [48e4d60](https://github.com/laravel/framework/commit/48e4d602d4f8fe9304e8998c5893206f67504dbf))
- Use single space if plain email is empty in `Illuminate\Mail\Mailer::addContent()` ([0557622](https://github.com/laravel/framework/commit/055762286132d545cbc064dce645562c0d51532f))
- Remove wasted file read when loading package manifest in `Illuminate\Foundation\PackageManifest::getManifest()` ([#32646](https://github.com/laravel/framework/pull/32646))
- Do not change `character` and `collation` for some columns on change ([fccdf7c](https://github.com/laravel/framework/commit/fccdf7c42d5ceb50985b3e8243d7ba650de996d6))
- Use table name when resolving has many through / one relationships ([8d69454](https://github.com/laravel/framework/commit/8d69454575267840643289b8de27d615cfe4bb62))


## [v7.9.2 (2020-04-28)](https://github.com/laravel/framework/compare/v7.9.1...v7.9.2)

### Changed
- Extract `InvokableComponentVariable` class ([f1ef6e6](https://github.com/laravel/framework/commit/f1ef6e6c40028cdafb95fc53e950b6ef73030458))
- Changed argument order in `Illuminate\View\Compilers\ComponentTagCompiler::__construct()` ([520544d](https://github.com/laravel/framework/commit/520544dc24772b421410a2528ba01fd47818eeea))


## [v7.9.1 (2020-04-28)](https://github.com/laravel/framework/compare/v7.9.0...v7.9.1)

### Added
- Added more proxy methods to deferred value from `Illuminate\View\Component::createInvokableVariable()` ([08c4012](https://github.com/laravel/framework/commit/08c40123a438e40ad82582fee7ddaa1ff056bb83))


## [v7.9.0 (2020-04-28)](https://github.com/laravel/framework/compare/v7.8.1...v7.9.0)

### Added
- Add pdo try again as lost connection message ([#32544](https://github.com/laravel/framework/pull/32544))
- Compile Echos Within Blade Component Attributes ([#32558](https://github.com/laravel/framework/pull/32558))
- Parameterless Component Methods Invokable With & Without Parens ([#32560](https://github.com/laravel/framework/pull/32560))

### Fixed
- Fixed `firstWhere` behavior for relations ([#32525](https://github.com/laravel/framework/pull/32525))
- Added check to avoid endless loop in `MailManager::createTransport()` ([#32549](https://github.com/laravel/framework/pull/32549))
- Fixed table prefixes with `compileDropDefaultConstraint()` ([#32554](https://github.com/laravel/framework/pull/32554))
- Fixed boolean value in `Illuminate\Foundation\Testing\TestResponse::assertSessionHasErrors()` ([#32555](https://github.com/laravel/framework/pull/32555))
- Fixed `Model::getOriginal()` with custom casts ([9e22c7c](https://github.com/laravel/framework/commit/9e22c7cfa629773eab981ccad13080c0f4cb81b2))

### Changed
- Added `withName` to `Illuminate\View\Component::ignoredMethods()` ([2e9eef2](https://github.com/laravel/framework/commit/2e9eef20a17a8b78493ae775ee95ed11349455d7))


## [v7.8.1 (2020-04-24)](https://github.com/laravel/framework/compare/v7.8.0...v7.8.1)

### Fixed
- Fixed `Illuminate\Http\Resources\Json\PaginatedResourceResponse::toResponse()` ([d460374](https://github.com/laravel/framework/commit/d4603749c03e03e224de3d867e88458599bb9d58))


## [v7.8.0 (2020-04-24)](https://github.com/laravel/framework/compare/v7.7.1...v7.8.0)

### Added
- Added `signedRoute()` and `temporarySignedRoute()` methods to `Illuminate\Routing\Redirector` ([#32489](https://github.com/laravel/framework/pull/32489))
- Added `takeUntil` and `takeWhile` collection methods ([#32494](https://github.com/laravel/framework/pull/32494), [#32496](https://github.com/laravel/framework/pull/32496))
- Added `Illuminate\Container\ContextualBindingBuilder::giveTagged()` ([#32514](https://github.com/laravel/framework/pull/32514))
- Added methods `withFragment` and `withoutFragment` to `Illuminate\Http\RedirectResponse` ([11d6bef](https://github.com/laravel/framework/commit/11d6befb4ed8b306f7ed40a205539a20d4bebe16), [0099591](https://github.com/laravel/framework/commit/0099591d63c51f9139db957ad42f3e783c1d0d30), [42c67a1](https://github.com/laravel/framework/commit/42c67a156acd6e6d44595e973774ad96fdc03857), [a1e741a](https://github.com/laravel/framework/commit/a1e741a1709b3d4998995b76abd990a6c09a5841))
- Added `exclude_without` validation rule ([4083ae5](https://github.com/laravel/framework/commit/4083ae57c6371c889de94df526bb849040bb895c))

### Fixed
- Fixed compiled route actions without a namespace ([#32512](https://github.com/laravel/framework/pull/32512))
- Reset select bindings when setting select ([#32531](https://github.com/laravel/framework/pull/32531))

### Changed
- Added warn in `Illuminate/Support/Facades/Auth::routes()` when laravel/ui is not installed ([#32482](https://github.com/laravel/framework/pull/32482))
- Added auth to each master on `Illuminate\Redis\Connections\PhpRedisConnection::flushdb()` ([837921b](https://github.com/laravel/framework/commit/837921b23311e875a9d22c296a9193a1cd8205cb))
- Register opis key so it is not tied to a deferred service provider (Illuminate/Encryption/EncryptionServiceProvider.php) ([62d8a07](https://github.com/laravel/framework/commit/62d8a0772553f3dff2d52a3ab062182c5efd75a2))
- Pass status code to schedule finish ([#32516](https://github.com/laravel/framework/pull/32516))
- Check route:list --columns option case insensitively ([#32521](https://github.com/laravel/framework/pull/32521))

### Deprecated
- Deprecate `Illuminate\Support\Traits\EnumeratesValues::until` ([#32517](https://github.com/laravel/framework/pull/32517))


## [v7.7.1 (2020-04-21)](https://github.com/laravel/framework/compare/v7.7.0...v7.7.1)

### Added
- Allow developers to specify accepted keys in array rule ([#32452](https://github.com/laravel/framework/pull/32452))

### Changed
- Add check is_object to `Illuminate\Database\Eloquent\Model::refresh()` ([1b0bdb4](https://github.com/laravel/framework/commit/1b0bdb43062a2792befe6fd754140124a8e4dc35))


## [v7.7.0 (2020-04-21)](https://github.com/laravel/framework/compare/v7.6.2...v7.7.0)

### Added
- Added ArrayAccess support for Http client get requests ([#32401](https://github.com/laravel/framework/pull/32401))
- Added `Illuminate\Http\Client\Factory::assertSentCount()` ([#32407](https://github.com/laravel/framework/pull/32407))
- Added `Illuminate\Database\Schema\Blueprint::rawIndex()` ([#32411](https://github.com/laravel/framework/pull/32411))
- Added getGrammar into passthru in Eloquent builder ([#32412](https://github.com/laravel/framework/pull/32412))
- Added `--relative` option to `storage:link` command ([#32457](https://github.com/laravel/framework/pull/32457), [24b705e](https://github.com/laravel/framework/commit/24b705e105d22df014bee3aab7ff12272457771e))
- Added dynamic `column` key for foreign constraints ([#32449](https://github.com/laravel/framework/pull/32449))
- Added container support for variadic constructor arguments ([#32454](https://github.com/laravel/framework/pull/32454), [1dd6db3](https://github.com/laravel/framework/commit/1dd6db3f2f22b1c65d13b3cbd58561f69aa4b317))
- Added `Illuminate\Http\Client\Request::hasHeaders()` ([#32462](https://github.com/laravel/framework/pull/32462))

### Fixed
- Fixed `MorphPivot::delete()` for models with primary key ([#32421](https://github.com/laravel/framework/pull/32421))
- Throw exception on missing required parameter on Container call method ([#32439](https://github.com/laravel/framework/pull/32439), [44c2a8d](https://github.com/laravel/framework/commit/44c2a8dc527f87f5a7fc59058df0f874a23449fa))
- Fixed Http Client multipart request ([#32428](https://github.com/laravel/framework/pull/32428), [1f163d4](https://github.com/laravel/framework/commit/1f163d471b973b237772bb11cdcb994aadd3d530))
- Fixed `Illuminate\Support\Stringable::isEmpty()` ([#32447](https://github.com/laravel/framework/pull/32447))
- Fixed `whereNull`/`whereNotNull` for json in MySQL ([#32417](https://github.com/laravel/framework/pull/32417), [d3bb329](https://github.com/laravel/framework/commit/d3bb329ce40e716e8e92aa7c27a929be60511a97))
- Fixed `Collection::orderBy()` with callable ([#32471](https://github.com/laravel/framework/pull/32471))

### Changed
- Re-use `Router::newRoute()` inside `CompiledRouteCollection` ([#32416](https://github.com/laravel/framework/pull/32416))
- Make `Illuminate\Queue\InteractsWithQueue.php::$job` public ([2e272ee](https://github.com/laravel/framework/commit/2e272ee6df6ac22675a4645cac8b581017aac53f))
- Catch and report exceptions thrown during schedule run execution ([#32461](https://github.com/laravel/framework/pull/32461))


## [v7.6.2 (2020-04-15)](https://github.com/laravel/framework/compare/v7.6.1...v7.6.2)

### Added
- Added `substrCount()` method to `Stringable` and `Str` ([#32393](https://github.com/laravel/framework/pull/32393))

### Fixed
- Fixed Lazyload `PackageManifest` ([#32391](https://github.com/laravel/framework/pull/32391))
- Fixed email validator ([#32388](https://github.com/laravel/framework/pull/32388))
- Fixed `Illuminate\Mail\Mailable::attachFromStorageDisk()` ([#32394](https://github.com/laravel/framework/pull/32394))

### Changed
- Changed `Illuminate\Translation\Translator::setLocale()` ([e78d24f](https://github.com/laravel/framework/commit/e78d24f31b84cd81c30b5d8837731d77ec089761), [a0094a5](https://github.com/laravel/framework/commit/a0094a57717b1f4c3e2a6feb978cc14f2c4690ff))
- Changed `Illuminate\Mail\Mailable::attachData()` ([#32392](https://github.com/laravel/framework/pull/32392))


## [v7.6.1 (2020-04-14)](https://github.com/laravel/framework/compare/v7.6.0...v7.6.1)

### Fixed
- Fixed `Illuminate\Testing\TestResponse::offsetExists()` ([#32377](https://github.com/laravel/framework/pull/32377))


## [v7.6.0 (2020-04-14)](https://github.com/laravel/framework/compare/v7.5.2...v7.6.0)

### Added
- Added `Collection::until()` method ([#32262](https://github.com/laravel/framework/pull/32262))
- Added `HtmlString::isEmpty()` method ([#32289](https://github.com/laravel/framework/pull/32289), [#32300](https://github.com/laravel/framework/pull/32300))
- Added `Illuminate\Support\Stringable::isNotEmpty()` method ([#32293](https://github.com/laravel/framework/pull/32293))
- Added `ltrim()` and `rtrim()` methods to `Illuminate\Support\Stringable` class ([#32288](https://github.com/laravel/framework/pull/32288))
- Added ability to skip a middleware ([#32347](https://github.com/laravel/framework/pull/32347), [412261c](https://github.com/laravel/framework/commit/412261c180a0ffb561078b7f0647f2a0a5c46c8d))
- Added `Illuminate\Http\Client\Response::object()` method ([#32341](https://github.com/laravel/framework/pull/32341))
- Set component alias name ([#32346](https://github.com/laravel/framework/pull/32346))
- Added `Illuminate\Database\Eloquent\Collection::append()` method ([#32324](https://github.com/laravel/framework/pull/32324))
- Added "between" clauses for BelongsToMany pivot columns ([#32364](https://github.com/laravel/framework/pull/32364))
- Support `retryAfter()` method option on Queued Listeners ([#32370](https://github.com/laravel/framework/pull/32370))
- Added support for the new composer installed.json format ([#32310](https://github.com/laravel/framework/pull/32310))
- Added `uuid` change support in migrations ([#32316](https://github.com/laravel/framework/pull/32316))
- Allowed store resource into postgresql bytea ([#32319](https://github.com/laravel/framework/pull/32319))

### Fixed
- Fixed `*scan` methods for phpredis ([#32336](https://github.com/laravel/framework/pull/32336))
- Fixed `Illuminate\Auth\Notifications\ResetPassword::toMail()` ([#32345](https://github.com/laravel/framework/pull/32345))
- Call setLocale in `Illuminate\Translation\Translator::__construct()` ([1c6a504](https://github.com/laravel/framework/commit/1c6a50424c5558782a55769a226ab834484282e1))
- Used a map to prevent unnecessary array access in `Illuminate\Http\Resources\Json\PaginatedResourceResponse::toResponse()` ([#32296](https://github.com/laravel/framework/pull/32296))
- Prevent timestamp update when pivot is not dirty ([#32311](https://github.com/laravel/framework/pull/32311))
- Fixed CURRENT_TIMESTAMP precision bug in `Illuminate\Database\Schema\Grammars\MySqlGrammar` ([#32298](https://github.com/laravel/framework/pull/32298))

### Changed
- Added default value to `HtmlString` constructor ([#32290](https://github.com/laravel/framework/pull/32290))
- Used `BindingResolutionException` to signal problem with container resolution ([#32349](https://github.com/laravel/framework/pull/32349))
- `Illuminate\Validation\Concerns\ValidatesAttributes.php ::validateUrl()` use Symfony/Validator 5.0.7 regex ([#32315](https://github.com/laravel/framework/pull/32315))

### Depreciated
- Depreciate the `elixir` function ([#32366](https://github.com/laravel/framework/pull/32366))


## [v7.5.2 (2020-04-08)](https://github.com/laravel/framework/compare/v7.5.1...v7.5.2)

### Fixed
- Prevent insecure characters in locale ([c248521](https://github.com/laravel/framework/commit/c248521f502c74c6cea7b0d221639d4aa752d5db))

### Optimization
- Optimize `Arr::set()` method ([#32282](https://github.com/laravel/framework/pull/32282))


## [v7.5.1 (2020-04-07)](https://github.com/laravel/framework/compare/v7.5.0...v7.5.1)

### Fixed
- Fixed Check a request header with an array value in `Illuminate\Http\Client\Request::hasHeader()` ([#32274](https://github.com/laravel/framework/pull/32274))
- Fixed setting mail header ([#32272](https://github.com/laravel/framework/pull/32272))


## [v7.5.0 (2020-04-07)](https://github.com/laravel/framework/compare/v7.4.0...v7.5.0)

### Added
- Added `assertNotSent()` and `assertNothingSent()` methods to  `Illuminate\Http\Client\Factory` ([#32197](https://github.com/laravel/framework/pull/32197))
- Added enum support for `renameColumn()` ([#32205](https://github.com/laravel/framework/pull/32205))
- Support returning an instance of a caster ([#32225](https://github.com/laravel/framework/pull/32225))

### Fixed
- Prevent long URLs from breaking email layouts ([#32189](https://github.com/laravel/framework/pull/32189))
- Fixed camel casing relationship ([#32217](https://github.com/laravel/framework/pull/32217))
- Fixed merging boolean or null attributes in Blade components ([#32245](https://github.com/laravel/framework/pull/32245))
- Fixed Console expectation assertion order ([#32258](https://github.com/laravel/framework/pull/32258))
- Fixed `route` helper with custom binding key ([#32264](https://github.com/laravel/framework/pull/32264))
- Fixed double slashes matching in UriValidator (fix inconsistencies between cached and none cached routes) ([#32260](https://github.com/laravel/framework/pull/32260))
- Fixed setting mail header ([#32272](https://github.com/laravel/framework/pull/32272))

### Optimization
- Optimize `Container::resolve()` method ([#32194](https://github.com/laravel/framework/pull/32194))
- Optimize performance for `data_get()` method ([#32192](https://github.com/laravel/framework/pull/32192))
- Optimize `Str::startsWith()` ([#32243](https://github.com/laravel/framework/pull/32243))


## [v7.4.0 (2020-03-31)](https://github.com/laravel/framework/compare/v7.3.0...v7.4.0)

### Added
- Makes the stubs used for `make:policy` customizable ([#32040](https://github.com/laravel/framework/pull/32040), [9d36a36](https://github.com/laravel/framework/commit/9d36a369d377044d0f468d1f02fa317cbb93571f))
- Implement `HigherOrderWhenProxy` for Collections ([#32148](https://github.com/laravel/framework/pull/32148))
- Added `Illuminate\Testing\PendingCommand::expectsChoice()` ([#32139](https://github.com/laravel/framework/pull/32139))
- Added support for default values for the "props" blade tag ([#32177](https://github.com/laravel/framework/pull/32177))
- Added `Castable` interface ([#32129](https://github.com/laravel/framework/pull/32129), [9cbf908](https://github.com/laravel/framework/commit/9cbf908c218bba74fbf83a83740b5c9f21c13e4e), [651371a](https://github.com/laravel/framework/commit/651371a2a982c06654b4df9af56110b666b2157f))
- Added the ability to remove orders from the query builder ([#32186](https://github.com/laravel/framework/pull/32186))

### Fixed
- Added missing return in the `PendingMailFake::sendNow()` and `PendingMailFake::send()` ([#32093](https://github.com/laravel/framework/pull/32093))
- Fixed custom Model attributes casts ([#32118](https://github.com/laravel/framework/pull/32118))
- Fixed route group prefixing ([#32135](https://github.com/laravel/framework/pull/32135), [870efef](https://github.com/laravel/framework/commit/870efef4c23ff7f151b6e1f267ac18951a3af2f1))
- Fixed component class view reference ([#32132](https://github.com/laravel/framework/pull/32132))

### Changed
- Remove Swift Mailer bindings ([#32165](https://github.com/laravel/framework/pull/32165))
- Publish console stub when running `stub:publish` command ([#32096](https://github.com/laravel/framework/pull/32096))
- Publish rule stub when running `make:rule` command ([#32097](https://github.com/laravel/framework/pull/32097))
- Adding the middleware.stub to the files that will be published when running php artisan `stub:publish` ([#32099](https://github.com/laravel/framework/pull/32099))
- Adding the factory.stub to the files that will be published when running php artisan `stub:publish` ([#32100](https://github.com/laravel/framework/pull/32100))
- Adding the seeder.stub to the files that will be published when running php artisan `stub:publish` ([#32122](https://github.com/laravel/framework/pull/32122))


## [v7.3.0 (2020-03-24)](https://github.com/laravel/framework/compare/v7.2.2...v7.3.0)

### Added
- Added possibility to use `^4.0` versions of `ramsey/uuid` ([#32086](https://github.com/laravel/framework/pull/32086))

### Fixed
- Corrected suggested dependencies ([#32072](https://github.com/laravel/framework/pull/32072), [c01a70e](https://github.com/laravel/framework/commit/c01a70e33198e81d06d4b581e36e25a80acf8a68))
- Avoid deadlock in test when sharing process group ([#32067](https://github.com/laravel/framework/pull/32067))


## [v7.2.2 (2020-03-20)](https://github.com/laravel/framework/compare/v7.2.1...v7.2.2)

### Fixed
- Fixed empty data for blade components ([#32032](https://github.com/laravel/framework/pull/32032))
- Fixed subdirectories when making components by `make:component` ([#32030](https://github.com/laravel/framework/pull/32030))
- Fixed serialization of models when sending notifications ([#32051](https://github.com/laravel/framework/pull/32051))
- Fixed route trailing slash in cached routes matcher ([#32048](https://github.com/laravel/framework/pull/32048))

### Changed
- Throw exception for non existing component alias ([#32036](https://github.com/laravel/framework/pull/32036))
- Don't overwrite published stub files by default in `stub:publish` command ([#32038](https://github.com/laravel/framework/pull/32038))


## [v7.2.1 (2020-03-19)](https://github.com/laravel/framework/compare/v7.2.0...v7.2.1)

### Fixed
- Enabling Windows absolute cache paths normalizing ([#31985](https://github.com/laravel/framework/pull/31985), [adfcb59](https://github.com/laravel/framework/commit/adfcb593fef058a32398d1e84d9083c8c5f893ac))
- Fixed blade newlines ([#32026](https://github.com/laravel/framework/pull/32026))
- Fixed exception rendering in debug mode ([#32027](https://github.com/laravel/framework/pull/32027))
- Fixed route naming issue ([#32028](https://github.com/laravel/framework/pull/32028))


## [v7.2.0 (2020-03-17)](https://github.com/laravel/framework/compare/v7.1.3...v7.2.0)

### Added
- Added `Illuminate\Testing\PendingCommand::expectsConfirmation()` ([#31965](https://github.com/laravel/framework/pull/31965))
- Allowed configuring the timeout for the smtp mail driver ([#31973](https://github.com/laravel/framework/pull/31973))
- Added `Http client` query string support ([#31996](https://github.com/laravel/framework/pull/31996))

### Fixed
- Fixed `cookie` helper signature , matching match `CookieFactory` ([#31974](https://github.com/laravel/framework/pull/31974))
- Added missing `ramsey/uuid` dependency to `Illuminate/Queue/composer.json` ([#31988](https://github.com/laravel/framework/pull/31988))
- Fixed output of component attributes in View ([#31994](https://github.com/laravel/framework/pull/31994))

### Changed
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
- Corrected suggested dependencies ([bb0ec42](https://github.com/laravel/framework/commit/bb0ec42b5a55b3ebf3a5a35cc6df01eec290dfa9))
- Fixed null value injected from container in routes ([#31867](https://github.com/laravel/framework/pull/31867), [c666c42](https://github.com/laravel/framework/commit/c666c424e8a60539a8fbd7cb5a3474785d9db22a))

### Changed 
- Escape attributes automatically in some situations in `Illuminate\View\Compilers\ComponentTagCompiler` ([#31945](https://github.com/laravel/framework/pull/31945))


## [v7.1.1 (2020-03-12)](https://github.com/laravel/framework/compare/v7.1.0...v7.1.1)

### Added
- Added `dispatchToQueue()` to `BusFake` ([#31935](https://github.com/laravel/framework/pull/31935))
- Support either order of arguments for symmetry with livewire ([8d558670](https://github.com/laravel/framework/commit/8d5586700ad97b92ac622ea72c1fefe52c359265))

### Fixed
- Bring `--daemon` option back to `queue:work` command ([24c1818](https://github.com/laravel/framework/commit/24c18182a82ee24be62d2ac1c6793c237944cda8))
- Fixed scheduler dependency assumptions ([#31894](https://github.com/laravel/framework/pull/31894))
- Fixed ComponentAttributeBag merge behaviour ([#31932](https://github.com/laravel/framework/pull/31932))

### Changed
- Intelligently drop unnamed prefix name routes when caching ([#31917](https://github.com/laravel/framework/pull/31917))
- Closure jobs needs illuminate/queue ([#31933](https://github.com/laravel/framework/pull/31933)) 
- Have a cache aware interface instead of concrete checks ([#31903](https://github.com/laravel/framework/pull/31903))


## [v7.1.0 (2020-03-10)](https://github.com/laravel/framework/compare/v7.0.8...v7.1.0)

### Added
- Added `Illuminate\Routing\RouteRegistrar::apiResource()` method ([#31857](https://github.com/laravel/framework/pull/31857)) 
- Added optional $table parameter to `ForeignIdColumnDefinition::constrained()` method ([#31853](https://github.com/laravel/framework/pull/31853))

### Fixed
- Fixed phpredis "zadd" and "exists" on cluster ([#31838](https://github.com/laravel/framework/pull/31838))
- Fixed trailing slash in `Illuminate\Routing\CompiledRouteCollection::match()` ([3d58cd9](https://github.com/laravel/framework/commit/3d58cd91d6ec483a43a4c23af9b75ecdd4a358de), [ac6f3a8](https://github.com/laravel/framework/commit/ac6f3a8bd0e94ea1319b6f278ecf7f3f8bada3c2))
- Fixed "srid" mysql schema ([#31852](https://github.com/laravel/framework/pull/31852))
- Fixed Microsoft ODBC lost connection handling ([#31879](https://github.com/laravel/framework/pull/31879))

### Changed
- Fire `MessageLogged` event after the message has been logged (not before) ([#31843](https://github.com/laravel/framework/pull/31843))
- Avoid using array_merge_recursive in HTTP client ([#31858](https://github.com/laravel/framework/pull/31858))
- Expire the jobs cache keys after 1 day ([#31854](https://github.com/laravel/framework/pull/31854))
- Avoid global app() when compiling components ([#31868](https://github.com/laravel/framework/pull/31868))


## [v7.0.8 (2020-03-08)](https://github.com/laravel/framework/compare/v7.0.7...v7.0.8)

### Added
- Added `Illuminate\Mail\Mailable::when()` method ([#31828](https://github.com/laravel/framework/pull/31828))

### Fixed
- Match Symfony's `Command::setHidden` declaration ([#31840](https://github.com/laravel/framework/pull/31840))
- Fixed dynamically adding of routes during caching ([#31829](https://github.com/laravel/framework/pull/31829))

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
- Fixed model restoring right after being soft deleting ([#31719](https://github.com/laravel/framework/pull/31719))
- Fixed array lock release behavior ([#31795](https://github.com/laravel/framework/pull/31795))
- Fixed `Illuminate\Support\Str::slug()` method ([e4f22d8](https://github.com/laravel/framework/commit/e4f22d855b429e4141885d542438c859f84bfe49))

### Changed
- Throw exception for duplicate route names in `Illuminate\Routing\AbstractRouteCollection::addToSymfonyRoutesCollection()` method ([#31755](https://github.com/laravel/framework/pull/31755))
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
- Remove comments before compiling components in the `BladeCompiler` ([2964d2d](https://github.com/laravel/framework/commit/2964d2dfd3cc50f7a709effee0af671c86587915))


## [v7.0.1 (2020-03-03)](https://github.com/laravel/framework/compare/v7.0.0...v7.0.1)

### Fixed
- Fixed `Illuminate\View\Component::withAttributes()` method ([c81ffad](https://github.com/laravel/framework/commit/c81ffad7ef8d74ebd109f399abbdc5c7ebabff88))


## [v7.0.0 (2020-03-03)](https://github.com/laravel/framework/compare/v6.18.0...v7.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/7.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/7.x/releases).
