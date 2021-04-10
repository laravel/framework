# Release Notes for 8.x

## [Unreleased](https://github.com/laravel/framework/compare/v8.36.0...8.x)


## [v8.36.0 (2021-04-06)](https://github.com/laravel/framework/compare/v8.35.1...v8.36.0)

### Revert
- Revert ["[8.x] Allow lazy collection to be instantiated from a generator"](https://github.com/laravel/framework/pull/36738) ([#36844](https://github.com/laravel/framework/pull/36844))

### Added
- Added support useCurrentOnUpdate for MySQL datetime column types ([#36817](https://github.com/laravel/framework/pull/36817))
- Added `dispatch_sync()` helper ([#36835](https://github.com/laravel/framework/pull/36835))
- Allowing skipping TransformRequests middlewares via Closure ([#36856](https://github.com/laravel/framework/pull/36856))
- Added type option to make controller command ([#36853](https://github.com/laravel/framework/pull/36853))
- Added missing return $this to `Illuminate\Support\Manager::forgetDrivers()` ([#36859](https://github.com/laravel/framework/pull/36859))
- Added unfinished option to PruneBatchesCommand ([#36877](https://github.com/laravel/framework/pull/36877))
- Added a simple Str::repeat() helper function ([#36887](https://github.com/laravel/framework/pull/36887))

### Fixed
- Fixed getMultiple and increment / decrement on tagged cache ([0d21194](https://github.com/laravel/framework/commit/0d211947da9ad222fa8eb092889bb7d7f5fb1726))
- Implement proper return types in cache increment and decrement ([#36836](https://github.com/laravel/framework/pull/36836))
- Fixed blade compiler regex issue ([#36843](https://github.com/laravel/framework/pull/36843), [#36848](https://github.com/laravel/framework/pull/36848))
- Added missing temporary_url when creating flysystem  ([#36860](https://github.com/laravel/framework/pull/36860))
- Fixed PostgreSQL schema:dump when read/write hosts are arrays ([#36881](https://github.com/laravel/framework/pull/36881))

### Changed
- Improve the exception thrown when JSON encoding response contents fails in `Response::setContent()` ([#36851](https://github.com/laravel/framework/pull/36851), [#36868](https://github.com/laravel/framework/pull/36868))
- Revert isDownForMaintenance function to use file_exists() ([#36889](https://github.com/laravel/framework/pull/36889))


## [v8.35.1 (2021-03-31)](https://github.com/laravel/framework/compare/v8.35.0...v8.35.1)

### Fixed
- Fixed setting DynamoDB credentials ([#36822](https://github.com/laravel/framework/pull/36822))


## [v8.35.0 (2021-03-30)](https://github.com/laravel/framework/compare/v8.34.0...v8.35.0)

### Added
- Added support of DynamoDB in CI suite ([#36749](https://github.com/laravel/framework/pull/36749))
- Support username parameter for predis ([#36762](https://github.com/laravel/framework/pull/36762))
- Added missing months() to Wormhole ([#36808](https://github.com/laravel/framework/pull/36808))

### Deprecated
- Deprecate MocksApplicationServices trait ([#36716](https://github.com/laravel/framework/pull/36716))

### Fixed
- Fixes missing lazy() and lazyById() on BelongsToMany and HasManyThrough relation query builder ([#36758](https://github.com/laravel/framework/pull/36758))
- Ensure the compiled view directory exists  ([#36772](https://github.com/laravel/framework/pull/36772))
- Fix Artisan test method PendingCommand::doesntExpectOutput() always causing a failed test ([#36806](https://github.com/laravel/framework/pull/36806))
- FIXED: The use of whereHasMorph in a whereHas callback generates a wrong sql statements ([#36801](https://github.com/laravel/framework/pull/36801))

### Changed
- Allow lazy collection to be instantiated from a generator ([#36738](https://github.com/laravel/framework/pull/36738))
- Use qualified column names in pivot query ([#36720](https://github.com/laravel/framework/pull/36720))
- Octane Prep ([#36777](https://github.com/laravel/framework/pull/36777))

### Refactoring
- Remove useless loop in `Str::remove()` ([#36722](https://github.com/laravel/framework/pull/36722))


## [v8.34.0 (2021-03-23)](https://github.com/laravel/framework/compare/v8.33.1...v8.34.0)

### Inspiring
- Added more inspiring quotes ([92b7bde](https://github.com/laravel/framework/commit/92b7bdeb4b8c40848fa276cfe1897c656302942f))
  
### Added
- Added WSREP communication link failure for lost connection detection ([#36668](https://github.com/laravel/framework/pull/36668))
- Added "except-path" option to `route:list` command ([#36619](https://github.com/laravel/framework/pull/36619), [76e11ee](https://github.com/laravel/framework/commit/76e11ee97fc8068be1d55986b4524d4c329af387))
- Added `Illuminate\Support\Str::remove()` and `Illuminate\Support\Stringable::remove()` methods ([#36639](https://github.com/laravel/framework/pull/36639), [7b0259f](https://github.com/laravel/framework/commit/7b0259faa46409513b75a8a0b512b3aacfcad944), [20e2470](https://github.com/laravel/framework/commit/20e24701e71f71a44b477b4311d0cb69f97906f1))
- Added `Illuminate\Database\Eloquent\Relations\MorphPivot::getMorphType()` ([#36640](https://github.com/laravel/framework/pull/36640), [7e08215](https://github.com/laravel/framework/commit/7e08215f0d370c3c33beb7bba7e2c1ee2ac7aab5))
- Added assertion to verify type of key in JSON ([#36638](https://github.com/laravel/framework/pull/36638))
- Added prohibited validation rule ([#36667](https://github.com/laravel/framework/pull/36667))
- Added strict comparison to distinct validation rule ([#36669](https://github.com/laravel/framework/pull/36669))
- Added `Illuminate\Translation\FileLoader::getJsonPaths()` ([#36689](https://github.com/laravel/framework/pull/36689))
- Added `Illuminate\Support\Testing\Fakes\EventFake::assertAttached()` ([#36690](https://github.com/laravel/framework/pull/36690))
- Added `lazy()` and `lazyById()`  methods to `Illuminate\Database\Concerns\BuildsQueries` ([#36699](https://github.com/laravel/framework/pull/36699))

### Fixed
- Fixes the issue using cache:clear with PhpRedis and a clustered Redis instance. ([#36665](https://github.com/laravel/framework/pull/36665))
- Fix replacing required :input with null on PHP 8.1 in `Illuminate\Validation\Concerns\FormatsMessages::getDisplayableValue()` ([#36622](https://github.com/laravel/framework/pull/36622))
- Fixed artisan schema:dump error ([#36698](https://github.com/laravel/framework/pull/36698))

### Changed
- Adjust Fluent Assertions ([#36620](https://github.com/laravel/framework/pull/36620))
- Added timestamp reference to schedule:work artisan command output ([#36621](https://github.com/laravel/framework/pull/36621))
- Expect custom markdown mailable themes to be in mail subdirectory ([#36673](https://github.com/laravel/framework/pull/36673))
- Throw exception when unable to create LockableFile ([#36674](https://github.com/laravel/framework/pull/36674))

### Refactoring
- Always prefer typesafe string comparisons ([#36657](https://github.com/laravel/framework/pull/36657))


## [v8.33.1 (2021-03-16)](https://github.com/laravel/framework/compare/v8.33.0...v8.33.1)

### Added
- Added `Illuminate\Database\Connection::forgetRecordModificationState()` ([#36617](https://github.com/laravel/framework/pull/36617))

### Reverted
- Reverted "Container - detect circular dependencies" ([332844e](https://github.com/laravel/framework/commit/332844e5bde34f8db91aeca4d21cd4e0925d691e))


## [v8.33.0 (2021-03-16)](https://github.com/laravel/framework/compare/v8.32.1...v8.33.0)

### Added
- Added broken pipe exception as lost connection error ([#36601](https://github.com/laravel/framework/pull/36601))
- Added missing option to resource ([#36562](https://github.com/laravel/framework/pull/36562))
- Introduce StringEncrypter interface ([#36578](https://github.com/laravel/framework/pull/36578))

### Fixed
- Fixed returns with Mail & Notification components ([#36559](https://github.com/laravel/framework/pull/36559))
- Stack driver fix: respect the defined processors in LogManager ([#36591](https://github.com/laravel/framework/pull/36591))
- Require the correct password to rehash it when logging out other devices ([#36608](https://github.com/laravel/framework/pull/36608), [1e61612](https://github.com/laravel/framework/commit/1e6161250074b8106c1fcf153eeaef7c0bf74c6c))

### Changed
- Allow nullable columns for `AsArrayObject/AsCollection` casts ([#36526](https://github.com/laravel/framework/pull/36526))
- Accept callable class for reportable and renderable in exception handler ([#36551](https://github.com/laravel/framework/pull/36551))
- Container - detect circular dependencies ([dd7274d](https://github.com/laravel/framework/commit/dd7274d23a9ee58cc1abdf7107403169a3994b68), [a712f72](https://github.com/laravel/framework/commit/a712f72ca88f709335576530b31635738abd4c89), [6f9bb4c](https://github.com/laravel/framework/commit/6f9bb4cdd84295cbcf7908cc4b4684f47f38b8cf))
- Initialize CronExpression class using new keyword ([#36600](https://github.com/laravel/framework/pull/36600))
- Use different config key for overriding temporary url host in AwsTemporaryUrl method ([#36612](https://github.com/laravel/framework/pull/36612))


## [v8.32.1 (2021-03-09)](https://github.com/laravel/framework/compare/v8.32.0...v8.32.1)

### Changed
- Changed `Illuminate\Queue\Middleware\ThrottlesExceptions` ([b8a70e9](https://github.com/laravel/framework/commit/b8a70e9a3685871ed46a24fc03c0267849d2d7c8))


## [v8.32.0 (2021-03-09)](https://github.com/laravel/framework/compare/v8.31.0...v8.32.0)

Added
- Phpredis lock serialization and compression support ([#36412](https://github.com/laravel/framework/pull/36412), [10f1a93](https://github.com/laravel/framework/commit/10f1a935205340ba8954e7075c1d9b67943db27d))
- Added Fluent JSON Assertions ([#36454](https://github.com/laravel/framework/pull/36454))
- Added methods to dump requests of the Laravel HTTP client ([#36466](https://github.com/laravel/framework/pull/36466))
- Added `ThrottlesExceptions` and `ThrottlesExceptionsWithRedis` job middlewares for unstable services ([#36473](https://github.com/laravel/framework/pull/36473), [21fee76](https://github.com/laravel/framework/commit/21fee7649e1b48a7701b8ba860218741c2c3bcef), [36518](https://github.com/laravel/framework/pull/36518), [37e48ba](https://github.com/laravel/framework/commit/37e48ba864e2f463517429d41cefd94e88136c1c))
- Added support to Eloquent Collection on `Model::destroy()` ([#36497](https://github.com/laravel/framework/pull/36497))
- Added `rest` option to `php artisan queue:work` command ([#36521](https://github.com/laravel/framework/pull/36521), [c6ea49c](https://github.com/laravel/framework/commit/c6ea49c80a2ac93aebb8fdf2360161b73cec26af))
- Added `prohibited_if` and `prohibited_unless` validation rules ([#36516](https://github.com/laravel/framework/pull/36516))
- Added class `argument` to `Illuminate\Database\Console\Seeds\SeedCommand` ([#36513](https://github.com/laravel/framework/pull/36513))

### Fixed
- Fix validator treating null as true for (required|exclude)_(if|unless) due to loose `in_array()` check ([#36504](https://github.com/laravel/framework/pull/36504))

### Changed
- Delete existing links that are broken in `Illuminate\Foundation\Console\StorageLinkCommand` ([#36470](https://github.com/laravel/framework/pull/36470))
- Use user provided url in AwsTemporaryUrl method ([#36480](https://github.com/laravel/framework/pull/36480))
- Allow to override discover events base path ([#36515](https://github.com/laravel/framework/pull/36515))


## [v8.31.0 (2021-03-04)](https://github.com/laravel/framework/compare/v8.30.1...v8.31.0)

### Added
- Added new `VendorTagPublished` event ([#36458](https://github.com/laravel/framework/pull/36458))
- Added new `Stringable::test()` method ([#36462](https://github.com/laravel/framework/pull/36462))

### Reverted
- Reverted [Fixed `formatWheres()` methods in `DatabaseRule`](https://github.com/laravel/framework/pull/36441) ([#36452](https://github.com/laravel/framework/pull/36452))

### Changed
- Make user policy command fix (Windows) ([#36464](https://github.com/laravel/framework/pull/36464))


## [v8.30.1 (2021-03-03)](https://github.com/laravel/framework/compare/v8.30.0...v8.30.1)

### Reverted
- Reverted [Respect custom route key with explicit route model binding](https://github.com/laravel/framework/pull/36375) ([#36449](https://github.com/laravel/framework/pull/36449))

### Fixed
- Fixed `formatWheres()` methods in `DatabaseRule` ([#36441](https://github.com/laravel/framework/pull/36441))


## [v8.30.0 (2021-03-02)](https://github.com/laravel/framework/compare/v8.29.0...v8.30.0)

### Added
- Added new line to `DetectsLostConnections` ([#36373](https://github.com/laravel/framework/pull/36373))
- Added `Illuminate\Cache\RateLimiting\Limit::perMinutes()` ([#36352](https://github.com/laravel/framework/pull/36352), [86d0a5c](https://github.com/laravel/framework/commit/86d0a5c733b3f22ae2353df538e07605963c3052))
- Make Database Factory macroable ([#36380](https://github.com/laravel/framework/pull/36380))
- Added stop on first failure for Validators ([39e1f84](https://github.com/laravel/framework/commit/39e1f84a48fec024859d4e80948aca9bd7878658))
- Added `containsOneItem()` method to Collections ([#36428](https://github.com/laravel/framework/pull/36428), [5b7ffc2](https://github.com/laravel/framework/commit/5b7ffc2b54dec803bd12541ab9c3d6bf3d4666ca))
  
### Changed
- Respect custom route key with explicit route model binding ([#36375](https://github.com/laravel/framework/pull/36375))
- Add Buffered Console Output ([#36404](https://github.com/laravel/framework/pull/36404))
- Don't flash 'current_password' input ([#36415](https://github.com/laravel/framework/pull/36415))
- Check for context method in Exception Handler ([#36424](https://github.com/laravel/framework/pull/36424))


## [v8.29.0 (2021-02-23)](https://github.com/laravel/framework/compare/v8.28.1...v8.29.0)

### Added
- Support username parameter for predis ([#36299](https://github.com/laravel/framework/pull/36299))
- Adds "setUpTestDatabase" support to Parallel Testing ([#36301](https://github.com/laravel/framework/pull/36301))
- Added support closures in sequences ([3c66f6c](https://github.com/laravel/framework/commit/3c66f6cda2ac4ee2844a67fc98e676cb170ff4b1))
- Added gate evaluation event ([0c6f5f7](https://github.com/laravel/framework/commit/0c6f5f75bf0ba4d3307145c9d92ae022f60414be))
- Added a `collect` method to the HTTP Client response ([#36331](https://github.com/laravel/framework/pull/36331))  
- Allow Blade's service injection to inject services typed using class name resolution ([#36356](https://github.com/laravel/framework/pull/36356))  
  
### Fixed
- Fixed: Using withoutMiddleware() and a closure-based middleware on PHP8 throws an exception ([#36293](https://github.com/laravel/framework/pull/36293))
- Fixed: The label for page number in pagination links should always be a string ([#36292](https://github.com/laravel/framework/pull/36292))
- Clean up custom Queue payload between tests ([#36295](https://github.com/laravel/framework/pull/36295))
- Fixed flushDb (cache:clear) for redis clusters ([#36281](https://github.com/laravel/framework/pull/36281))
- Fixed retry command for encrypted jobs ([#36334](https://github.com/laravel/framework/pull/36334), [2fb5e44](https://github.com/laravel/framework/commit/2fb5e444ef55a764ba2363a10320e75f3c830504))
- Make sure `trait_uses_recursive` returns an array ([#36335](https://github.com/laravel/framework/pull/36335))

### Changed
- Make use of specified ownerKey in MorphTo::associate() ([#36303](https://github.com/laravel/framework/pull/36303))
- Update pusher deps and update broadcasting ([3404185](https://github.com/laravel/framework/commit/3404185fbe36139dfbe6d0d9595811b41ee53068))


## [v8.28.1 (2021-02-16)](https://github.com/laravel/framework/compare/v8.28.0...v8.28.1)

### Fixed
- Revert "[8.x] Clean up custom Queue payload between tests" ([#36287](https://github.com/laravel/framework/pull/36287))


## [v8.28.0 (2021-02-16)](https://github.com/laravel/framework/compare/v8.27.0...v8.28.0)

### Added
- Allow users to specify configuration keys to be used for primitive binding ([#36241](https://github.com/laravel/framework/pull/36241))
- ArrayObject + Collection Custom Casts ([#36245](https://github.com/laravel/framework/pull/36245))
- Add view path method ([af3a651](https://github.com/laravel/framework/commit/af3a651ad6ae3e90bd673fe7a6bfc1ce9e569d25))

### Changed
- Allow using dot syntax for `$responseKey` ([#36196](https://github.com/laravel/framework/pull/36196))
- Full trace for http errors ([#36219](https://github.com/laravel/framework/pull/36219))

### Fixed
- Fix undefined property with sole query ([#36216](https://github.com/laravel/framework/pull/36216))
- Resolving non-instantiables corrupts `Container::$with` ([#36212](https://github.com/laravel/framework/pull/36212))
- Fix attribute nesting on anonymous components ([#36240](https://github.com/laravel/framework/pull/36240))
- Ensure `$prefix` is a string ([#36254](https://github.com/laravel/framework/pull/36254))
- Add missing import ([#34569](https://github.com/laravel/framework/pull/34569))
- Align PHP 8.1 behavior of `e()` ([#36262](https://github.com/laravel/framework/pull/36262))
- Ensure null values won't break on PHP 8.1 ([#36264](https://github.com/laravel/framework/pull/36264))
- Handle directive `$value` as a string ([#36260](https://github.com/laravel/framework/pull/36260))
- Use explicit flag as default sorting ([#36261](https://github.com/laravel/framework/pull/36261))
- Fix middleware group display ([d9e28dc](https://github.com/laravel/framework/commit/d9e28dcb1f4a5638b33829d919bd7417321ab39e))


## [v8.27.0 (2021-02-09)](https://github.com/laravel/framework/compare/v8.26.1...v8.27.0)

### Added
- Conditionally merge classes into a Blade Component attribute bag ([#36131](https://github.com/laravel/framework/pull/36131))
- Allow adding multiple columns after a column ([#36145](https://github.com/laravel/framework/pull/36145))
- Add query builder `chunkMap` method ([#36193](https://github.com/laravel/framework/pull/36193), [048ac6d](https://github.com/laravel/framework/commit/048ac6d49f2f7b2d64eb1695848df4590c38be98))

### Changed
- Update CallQueuedClosure to catch Throwable/Error ([#36159](https://github.com/laravel/framework/pull/36159))
- Allow components to use custom attribute bag ([#36186](https://github.com/laravel/framework/pull/36186))

### Fixed
- Set process timeout to null for load mysql schema into database ([#36126](https://github.com/laravel/framework/pull/36126))
- Don't pluralise string if string ends with none alphanumeric character ([#36137](https://github.com/laravel/framework/pull/36137))
- Add query log methods to the DB facade ([#36177](https://github.com/laravel/framework/pull/36177))
- Add commonmark as recommended package for `Illuminate\Support` ([#36171](https://github.com/laravel/framework/pull/36171))
- Fix Eager loading partially nullable morphTo relations ([#36129](https://github.com/laravel/framework/pull/36129))
- Make height of image working with yahoo ([#36201](https://github.com/laravel/framework/pull/36201))
- Make `sole()` relationship friendly ([#36200](https://github.com/laravel/framework/pull/36200))
- Make layout in mail responsive in Gmail app ([#36198](https://github.com/laravel/framework/pull/36198))
- Fixes parallel testing when a database is configured using URLs ([#36204](https://github.com/laravel/framework/pull/36204))


## [v8.26.1 (2021-02-02)](https://github.com/laravel/framework/compare/v8.26.0...v8.26.1)

### Fixed
- Fixed merge conflict in `src/Illuminate/Foundation/Console/stubs/exception-render-report.stub` ([#36123](https://github.com/laravel/framework/pull/36123))


## [v8.26.0 (2021-02-02)](https://github.com/laravel/framework/compare/v8.25.0...v8.26.0)

### Added
- Allow to fillJsonAttribute with encrypted field ([#36063](https://github.com/laravel/framework/pull/36063))
- Added `Route::missing()` ([#36035](https://github.com/laravel/framework/pull/36035))
- Added `Illuminate\Support\Str::markdown()` and `Illuminate\Support\Stringable::markdown()` ([#36071](https://github.com/laravel/framework/pull/36071))
- Support retrieving URL for Sftp adapter ([#36120](https://github.com/laravel/framework/pull/36120))

### Fixed
- Fixed issues with dumping PostgreSQL databases that contain multiple schemata ([#36046](https://github.com/laravel/framework/pull/36046))
- Fixes job batch serialization for PostgreSQL ([#36081](https://github.com/laravel/framework/pull/36081))
- Fixed `Illuminate\View\ViewException::report()` ([#36110](https://github.com/laravel/framework/pull/36110))

### Changed
- Typecast page number as integer in `Illuminate\Pagination\AbstractPaginator::resolveCurrentPage()` ([#36055](https://github.com/laravel/framework/pull/36055))
- Changed `Illuminate\Testing\ParallelRunner::createApplication()` ([1c11b78](https://github.com/laravel/framework/commit/1c11b7893fa3e9c592f6e85b2b1b0028ddd55645))


## [v8.25.0 (2021-01-26)](https://github.com/laravel/framework/compare/v8.24.0...v8.25.0)

### Added
- Added `Stringable::pipe` & make Stringable tappable ([#36017](https://github.com/laravel/framework/pull/36017))
- Accept a command in object form in Bus::assertChained ([#36031](https://github.com/laravel/framework/pull/36031))
- Adds parallel testing ([#36034](https://github.com/laravel/framework/pull/36034))
- Make Listeners, Mailables, and Notifications accept ShouldBeEncrypted ([#36036](https://github.com/laravel/framework/pull/36036))
- Support JSON encoding Stringable ([#36012](https://github.com/laravel/framework/pull/36012))
- Support for escaping bound attributes ([#36042](https://github.com/laravel/framework/pull/36042))
- Added `Illuminate\Foundation\Application::useLangPath()` ([#36044](https://github.com/laravel/framework/pull/36044))

### Changed
- Pipe through new render and report exception methods ([#36032](https://github.com/laravel/framework/pull/36032))

### Fixed
- Fixed issue with dumping schema from a postgres database using no default schema  ([#35966](https://github.com/laravel/framework/pull/35966), [7be50a5](https://github.com/laravel/framework/commit/7be50a511955dea2bf4d6e30208b6fbf07eaa36e))
- Fixed worker --delay option ([#35991](https://github.com/laravel/framework/pull/35991))
- Added support of PHP 7.3 to RateLimiter middleware(queue) serialization ([#35986](https://github.com/laravel/framework/pull/35986))
- Fixed `Illuminate\Foundation\Http\Middleware\TransformsRequest::cleanArray()` ([#36002](https://github.com/laravel/framework/pull/36002))
- ModelNotFoundException: ensure that the model class name is properly set ([#36011](https://github.com/laravel/framework/pull/36011))
- Fixed bus fake ([e720279](https://github.com/laravel/framework/commit/e72027960fd4d8ff281938edb4632e13e391b8fd))


## [v8.24.0 (2021-01-21)](https://github.com/laravel/framework/compare/v8.23.1...v8.24.0)

### Added
- Added `JobQueued` event ([8eaec03](https://github.com/laravel/framework/commit/8eaec037421aa9f3860da9d339986448b4c884eb), [5d572e7](https://github.com/laravel/framework/commit/5d572e7a6d479ef68ee92c9d67e2e9465174fb4c))

### Fixed
- Fixed type error in `Illuminate\Http\Concerns\InteractsWithContentTypes::isJson()` ([#35956](https://github.com/laravel/framework/pull/35956))
- Fixed `Illuminate\Collections\Collection::sortByMany()` ([#35950](https://github.com/laravel/framework/pull/35950))
- Fixed Limit expected bindings ([#35972](https://github.com/laravel/framework/pull/35972), [006873d](https://github.com/laravel/framework/commit/006873df411d28bfd03fea5e7f91a2afe3918498))
- Fixed serialization of rate limited with redis middleware ([#35971](https://github.com/laravel/framework/pull/35971))


## [v8.23.1 (2021-01-19)](https://github.com/laravel/framework/compare/v8.23.0...v8.23.1)

### Fixed
- Fixed empty html mail ([#35941](https://github.com/laravel/framework/pull/35941))


## [v8.23.0 (2021-01-19)](https://github.com/laravel/framework/compare/v8.22.1...v8.23.0)

### Added
- Added `Illuminate\Database\Concerns\BuildsQueries::sole()` ([#35869](https://github.com/laravel/framework/pull/35869), [29c7dae](https://github.com/laravel/framework/commit/29c7dae9b32af2abffa7489f4758fd67905683c3), [#35908](https://github.com/laravel/framework/pull/35908), [#35902](https://github.com/laravel/framework/pull/35902), [#35912](https://github.com/laravel/framework/pull/35912))
- Added default parameter to throw_if / throw_unless ([#35890](https://github.com/laravel/framework/pull/35890))
- Added validation support for TeamSpeak3 URI scheme ([#35933](https://github.com/laravel/framework/pull/35933))

### Fixed
- Fixed extra space on blade class components that are inline ([#35874](https://github.com/laravel/framework/pull/35874))
- Fixed serialization of rate limited middleware ([f3d4dcb](https://github.com/laravel/framework/commit/f3d4dcb21dc66824611fdde95c8075b694825bf5), [#35916](https://github.com/laravel/framework/pull/35916))

### Changed
- Allow a specific seeder to be used in tests in `Illuminate\Foundation\Testing\RefreshDatabase::migrateFreshUsing()` ([#35864](https://github.com/laravel/framework/pull/35864))
- Pass $key to closure in Collection and LazyCollection's reduce method as well ([#35878](https://github.com/laravel/framework/pull/35878))


## [v8.22.1 (2021-01-13)](https://github.com/laravel/framework/compare/v8.22.0...v8.22.1)
 
### Fixed
- Limit expected bindings ([#35865](https://github.com/laravel/framework/pull/35865))


## [v8.22.0 (2021-01-12)](https://github.com/laravel/framework/compare/v8.21.0...v8.22.0)

### Added
- Added new lines to `DetectsLostConnections` ([#35752](https://github.com/laravel/framework/pull/35752), [#35790](https://github.com/laravel/framework/pull/35790))
- Added `Illuminate\Support\Testing\Fakes\EventFake::assertNothingDispatched()` ([#35835](https://github.com/laravel/framework/pull/35835))
- Added reduce with keys to collections and lazy collections ([#35839](https://github.com/laravel/framework/pull/35839))

### Fixed
- Fixed error from missing null check on PHP 8 in `Illuminate\Validation\Concerns\ValidatesAttributes::validateJson()` ([#35797](https://github.com/laravel/framework/pull/35797))
- Fix bug with RetryCommand ([4415b94](https://github.com/laravel/framework/commit/4415b94623358bfd1dc2e8f20e4deab0025d2d03), [#35828](https://github.com/laravel/framework/pull/35828))
- Fixed `Illuminate\Testing\PendingCommand::expectsTable()` ([#35820](https://github.com/laravel/framework/pull/35820))
- Fixed `morphTo()` attempting to map an empty string morph type to an instance ([#35824](https://github.com/laravel/framework/pull/35824))

### Changes
- Update `Illuminate\Http\Resources\CollectsResources::collects()` ([1fa20dd](https://github.com/laravel/framework/commit/1fa20dd356af21af6e38d95e9ff2b1d444344fbe))
- "null" constraint prevents aliasing SQLite ROWID  ([#35792](https://github.com/laravel/framework/pull/35792))
- Allow strings to be passed to the `report` function ([#35803](https://github.com/laravel/framework/pull/35803))


## [v8.21.0 (2021-01-05)](https://github.com/laravel/framework/compare/v8.20.1...v8.21.0)

### Added
- Added command to clean batches table ([#35694](https://github.com/laravel/framework/pull/35694), [33f5ac6](https://github.com/laravel/framework/commit/33f5ac695a55d6cdbadcfe1b46e3409e4a66df16))
- Added item to list of causedByLostConnection errors ([#35744](https://github.com/laravel/framework/pull/35744))
- Make it possible to set Postmark Message Stream ID ([#35755](https://github.com/laravel/framework/pull/35755))

### Fixed
- Fixed `php artisan db` command for the Postgres CLI ([#35725](https://github.com/laravel/framework/pull/35725))
- Fixed OPTIONS method bug with use same path and diff domain when cache route ([#35714](https://github.com/laravel/framework/pull/35714))

### Changed
- Ensure DBAL custom type doesn't exists in `Illuminate\Database\DatabaseServiceProvider::registerDoctrineTypes()` ([#35704](https://github.com/laravel/framework/pull/35704))
- Added missing `dispatchAfterCommit` to `DatabaseQueue` ([#35715](https://github.com/laravel/framework/pull/35715))
- Set chain queue when inside a batch ([#35746](https://github.com/laravel/framework/pull/35746))
- Give a more meaningul message when route parameters are missing ([#35706](https://github.com/laravel/framework/pull/35706))
- Added table prefix to `Illuminate\Database\Console\DumpCommand::schemaState()` ([4ffe40f](https://github.com/laravel/framework/commit/4ffe40fb169c6bcce9193ff56958eca41e64294f))
- Refresh the retryUntil time on job retry ([#35780](https://github.com/laravel/framework/pull/35780), [45eb7a7](https://github.com/laravel/framework/commit/45eb7a7b1706ae175268731a673f369c0e556805))


## [v8.20.1 (2020-12-22)](https://github.com/laravel/framework/compare/v8.20.0...v8.20.1)

### Revert
- Revert [Clear a cached user in RequestGuard if a request is changed](https://github.com/laravel/framework/pull/35692) ([ca8ccd6](https://github.com/laravel/framework/commit/ca8ccd6757d5639f0e5fb241b3df6878da6ce34e))


## [v8.20.0 (2020-12-22)](https://github.com/laravel/framework/compare/v8.19.0...v8.20.0)

### Added
- Added `Illuminate\Database\DBAL\TimestampType` ([a5761d4](https://github.com/laravel/framework/commit/a5761d4187abea654cb422c2f70054a880ffd2e0), [cff3705](https://github.com/laravel/framework/commit/cff37055cbf031109ae769e8fd6ad1951be47aa6) [382445f](https://github.com/laravel/framework/commit/382445f8487de45a05ebe121837f917b92560a97), [810047e](https://github.com/laravel/framework/commit/810047e1f184f8a4def372885591e4fbb6996b51))
- Added ability to specify a separate lock connection ([#35621](https://github.com/laravel/framework/pull/35621), [3d95235](https://github.com/laravel/framework/commit/3d95235a6ad8525886071ad68e818a225786064f))
- Added `Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable::syncWithPivotValues()` ([#35644](https://github.com/laravel/framework/pull/35644), [49b3ce0](https://github.com/laravel/framework/commit/49b3ce098d8a612797b195c4e3774b1e00c604c8))

### Fixed
- Fixed `Illuminate\Validation\Concerns\ValidatesAttributes::validateJson()` for PHP8 ([#35646](https://github.com/laravel/framework/pull/35646))
- Fixed `assertCookieExpired()` and `assertCookieNotExpired()` methods in `Illuminate\Testing\TestResponse` ([#35637](https://github.com/laravel/framework/pull/35637))
- Fixed: Account for a numerical array of views in Mailable::renderForAssertions() ([#35662](https://github.com/laravel/framework/pull/35662))
- Catch DecryptException with invalid X-XSRF-TOKEN in `Illuminate\Foundation\Http\Middleware\VerifyCsrfToken` ([#35671](https://github.com/laravel/framework/pull/35671))

### Changed
- Check configuration in `Illuminate\Foundation\Console\Kernel::scheduleCache()` ([a253d0e](https://github.com/laravel/framework/commit/a253d0e40d3deb293d54df9f4455879af5365aab))
- Modify `Model::mergeCasts` to return `$this` ([#35683](https://github.com/laravel/framework/pull/35683))
- Clear a cached user in RequestGuard if a request is changed ([#35692](https://github.com/laravel/framework/pull/35692))


## [v8.19.0 (2020-12-15)](https://github.com/laravel/framework/compare/v8.18.1...v8.19.0)

### Added
- Delay pushing jobs to queue until database transactions are committed ([#35422](https://github.com/laravel/framework/pull/35422), [095d922](https://github.com/laravel/framework/commit/095d9221e837e7a7d8bd00d14184e619b962173a), [fa34d93](https://github.com/laravel/framework/commit/fa34d93ad0cda78e8956b2680ba7bada02bcabb2), [db0d0ba](https://github.com/laravel/framework/commit/db0d0ba94cf8a5c1e1fa4621bd4a16032aff800d), [d9b803a](https://github.com/laravel/framework/commit/d9b803a1a4898e7d5b3145e51c77499815ce3401), [3e55841](https://github.com/laravel/framework/commit/3e5584165fb66d8228bae79a856eac51ce147df5))
- Added `Illuminate\View\ComponentAttributeBag::has()` ([#35562](https://github.com/laravel/framework/pull/35562))
- Create ScheduleListCommand ([#35574](https://github.com/laravel/framework/pull/35574), [97d7834](https://github.com/laravel/framework/commit/97d783449c5330b1e5fb9104f6073869ad3079c1))
- Introducing Job Encryption ([#35527](https://github.com/laravel/framework/pull/35527), [f80f647](https://github.com/laravel/framework/commit/f80f647852106942e4a0ef3e9963f8f7a99122cf), [8c16156](https://github.com/laravel/framework/commit/8c16156636311e42883d9e84a6d71fa135bc2b73))

### Fixed
- Handle `Throwable` exceptions on `Illuminate\Redis\Limiters\ConcurrencyLimiter::block()` ([#35546](https://github.com/laravel/framework/pull/35546))
- Fixed PDO passing in SqlServerDriver ([#35564](https://github.com/laravel/framework/pull/35564))
- When following redirects, terminate each test request in proper order ([#35604](https://github.com/laravel/framework/pull/35604))


## [v8.18.1 (2020-12-09)](https://github.com/laravel/framework/compare/v8.18.0...v8.18.1)

### Fixed
- Bumped minimum Symfony version ([#35535](https://github.com/laravel/framework/pull/35535))
- Fixed passing model instances to factories ([#35541](https://github.com/laravel/framework/pull/35541))


## [v8.18.0 (2020-12-08)](https://github.com/laravel/framework/compare/v8.17.2...v8.18.0)

### Added
- Added `Illuminate\Http\Client\Factory::assertSentInOrder()` ([#35525](https://github.com/laravel/framework/pull/35525), [d257ce2](https://github.com/laravel/framework/commit/d257ce2e93dfe52151be3d0386fcc4ea281ca8d5), [2fd1411](https://github.com/laravel/framework/commit/2fd141158eb5aead8aa2afff51bcd98250b6bbe6))
- Added `Illuminate\Http\Client\Response::handlerStats()` ([#35520](https://github.com/laravel/framework/pull/35520))
- Added support for attaching existing model instances in factories ([#35494](https://github.com/laravel/framework/pull/35494))
- Added `assertChained()` and `assertDispatchedWithoutChain()` methods to `Illuminate\Support\Testing\Fakes\BusFake` class ([#35523](https://github.com/laravel/framework/pull/35523), [f1b8cac](https://github.com/laravel/framework/commit/f1b8cacfe2a8863894e258ce35a77decedbea36f), [236c67d](https://github.com/laravel/framework/commit/236c67db52f755bb475ba325148e9053733968aa))
- Allow testing of html and plain text bodies right off mailables ([afb858a](https://github.com/laravel/framework/commit/afb858ad9c944bd3f9ad56c3e4485527d77a7327), [b7391e4](https://github.com/laravel/framework/commit/b7391e486fc68c1c422668a277eaac2bcbe72b2b))
 
### Fixed
- Fixed Application flush method ([#35482](https://github.com/laravel/framework/pull/35482))
- Fixed mime validation for jpeg files ([#35518](https://github.com/laravel/framework/pull/35518))

### Revert
- Revert [Added ability to define table name as default morph type](https://github.com/laravel/framework/pull/35257) ([#35533](https://github.com/laravel/framework/pull/35533))


## [v8.17.2 (2020-12-03)](https://github.com/laravel/framework/compare/v8.17.1...v8.17.2)

### Added
- Added `Illuminate\Database\Eloquent\Relations\BelongsToMany::orderByPivot()` ([#35455](https://github.com/laravel/framework/pull/35455), [6f83a50](https://github.com/laravel/framework/commit/6f83a5099725dc47fbec1b0cf1bcc64f80f9dc86))


## [v8.17.1 (2020-12-02)](https://github.com/laravel/framework/compare/v8.17.0...v8.17.1)

### Fixed
- Fixed an issue with the database queue driver ([#35449](https://github.com/laravel/framework/pull/35449))


## [v8.17.0 (2020-12-01)](https://github.com/laravel/framework/compare/v8.16.1...v8.17.0)

### Added
- Added: Transaction aware code execution ([#35373](https://github.com/laravel/framework/pull/35373), [9565598](https://github.com/laravel/framework/commit/95655988ea1fb0c260ca792751e2e9da81afc3a7))
- Added dd() and dump() to the request object ([#35384](https://github.com/laravel/framework/pull/35384), [c43e08f](https://github.com/laravel/framework/commit/c43e08f98afe5dcf742956510e9ab170ea11ce45))
- Enqueue all jobs using a enqueueUsing method ([#35415](https://github.com/laravel/framework/pull/35415), [010d4d7](https://github.com/laravel/framework/commit/010d4d7ea7ec5581dfbf8b6ba84b812f8e4cb649), [#35437](https://github.com/laravel/framework/pull/35437))

### Fixed
- Fix issue with polymorphic morphMaps with literal 0 ([#35364](https://github.com/laravel/framework/pull/35364))
- Fixed Self-Relation issue in withAggregate method ([#35392](https://github.com/laravel/framework/pull/35392), [aec5cca](https://github.com/laravel/framework/commit/aec5cca4ace65bc4b4ca054170b645f1073ac9ca), [#35394](https://github.com/laravel/framework/pull/35394))
- Fixed Use PHP_EOL instead of `\n` in PendingCommand ([#35409](https://github.com/laravel/framework/pull/35409))
- Fixed validating image/jpeg images after Symfony/Mime update ([#35419](https://github.com/laravel/framework/pull/35419))
- Fixed fail to morph with custom casting to objects ([#35420](https://github.com/laravel/framework/pull/35420))
- Fixed `Illuminate\Collections\Collection::sortBy()` ([307f6fb](https://github.com/laravel/framework/commit/307f6fb8d9579427a9521a07e8700355a3e9d948))
- Don't overwrite minute and hour when specifying a time with twiceMonthly() ([#35436](https://github.com/laravel/framework/pull/35436))

### Changed
- Make DownCommand retryAfter available to prerendered view ([#35357](https://github.com/laravel/framework/pull/35357), [b1ee97e](https://github.com/laravel/framework/commit/b1ee97e5ae03dae293e3256b8c3013209d0fd9b0))
- Set default value on cloud driver ([0bb7fe4](https://github.com/laravel/framework/commit/0bb7fe4758d617b07b84f6fabfcfe2ca2cdb0964))
- Update Tailwind pagination focus styles ([#35365](https://github.com/laravel/framework/pull/35365))
- Redis: allow to pass connection name ([#35402](https://github.com/laravel/framework/pull/35402))
- Change Wormhole to use the Date Factory ([#35421](https://github.com/laravel/framework/pull/35421))


## [v8.16.1 (2020-11-25)](https://github.com/laravel/framework/compare/v8.16.0...v8.16.1)

### Fixed
- Fixed reflection exception in `Illuminate\Routing\Router::gatherRouteMiddleware()` ([c6e8357](https://github.com/laravel/framework/commit/c6e8357e19b10a800df8a67446f23310f4e83d1f))


## [v8.16.0 (2020-11-24)](https://github.com/laravel/framework/compare/v8.15.0...v8.16.0)

### Added
- Added `Illuminate\Console\Concerns\InteractsWithIO::withProgressBar()` ([4e52a60](https://github.com/laravel/framework/commit/4e52a606e91619f6082ed8d46f8d64f9d4dbd0b2), [169fd2b](https://github.com/laravel/framework/commit/169fd2b5156650a067aa77a38681875d2a6c5e57))
- Added `Illuminate\Console\Concerns\CallsCommands::callSilently()` as alias for `callSilent()` ([7f3101b](https://github.com/laravel/framework/commit/7f3101bf6e8a0f048a243a55be7fc79eb359b609), [0294433](https://github.com/laravel/framework/commit/029443349294e3b6e7bebfe9c23a51a9821ec497))
- Added option to release unique job locks before processing ([#35255](https://github.com/laravel/framework/pull/35255), [b53f13e](https://github.com/laravel/framework/commit/b53f13ef6c8625176defcb83d2fb8d4d5887d068))
- Added ably broadcaster ([e0f3f8e](https://github.com/laravel/framework/commit/e0f3f8e8241e1ea34a3a3b8c543871cdc00290bf), [6381aa9](https://github.com/laravel/framework/commit/6381aa994756429156b7376e98606458b052b1d7))
- Added ability to define table name as default morph type ([#35257](https://github.com/laravel/framework/pull/35257))
- Allow overriding the MySQL server version for database queue driver ([#35263](https://github.com/laravel/framework/pull/35263))
- Added `Illuminate\Foundation\Testing\Wormhole::back()` ([#35261](https://github.com/laravel/framework/pull/35261))
- Support delaying notifications per channel ([#35273](https://github.com/laravel/framework/pull/35273))
- Allow sorting on multiple criteria ([#35277](https://github.com/laravel/framework/pull/35277), [53eb307](https://github.com/laravel/framework/commit/53eb307fea077299d409adf3ba0307a8fda4c4d1))
- Added `Illuminate/Database/Console/DbCommand.php` command ([#35304](https://github.com/laravel/framework/pull/35304), [b559b3e](https://github.com/laravel/framework/commit/b559b3e7c4995ef468b35e8a6117ef24fdeca053))
- Added Collections `splitIn` methods ([#35295](https://github.com/laravel/framework/pull/35295))

### Fixed
- Fixed rendering of notifications with config custom theme ([325a335](https://github.com/laravel/framework/commit/325a335ccf45426eabb27131ed48aa6114434c99))
- Fixing BroadcastException message in PusherBroadcaster@broadcast ([#35290](https://github.com/laravel/framework/pull/35290))
- Fixed generic DetectsLostConnection string ([#35323](https://github.com/laravel/framework/pull/35323))
- Fixed SQL Server command generation ([#35317](https://github.com/laravel/framework/pull/35317))
- Fixed route model binding on cached closure routes ([eb3e262](https://github.com/laravel/framework/commit/eb3e262c870739a6e9705b851e0066b3473eed2b))

### Changed
- Disable CSRF on broadcast route ([acb4b77](https://github.com/laravel/framework/commit/acb4b77adc6e257e132e3b036abe1ec88885cfb7))
- Easily set a null cache driver ([#35262](https://github.com/laravel/framework/pull/35262))
- Updated `aws/aws-sdk-php` suggest to `^3.155` ([#35267](https://github.com/laravel/framework/pull/35267))
- Ensure ShouldBeUniqueUntilProcessing job lock is released once ([#35270](https://github.com/laravel/framework/pull/35270))
- Rename qualifyColumn to qualifyPivotColumn in BelongsToMany & MorphToMany ([#35276](https://github.com/laravel/framework/pull/35276))
- Check if AsPivot trait is used instead of Pivot Model in `Illuminate\Database\Eloquent\Relations\BelongsToMany` ([#35271](https://github.com/laravel/framework/pull/35271))
- Avoid no-op database query in Model::destroy() with empty ids ([#35294](https://github.com/laravel/framework/pull/35294))
- Use --no-owner and --no-acl with pg_restore ([#35309](https://github.com/laravel/framework/pull/35309))


## [v8.15.0 (2020-11-17)](https://github.com/laravel/framework/compare/v8.14.0...v8.15.0)

### Added
- Added lock support for file and null cache drivers ([#35139](https://github.com/laravel/framework/pull/35139), [a345185](https://github.com/laravel/framework/commit/a3451859d1cff45fba423cf577d00f5b2b648c7a))
- Added a `doesntExpectOutput` method for console command testing ([#35160](https://github.com/laravel/framework/pull/35160), [c90fc5f](https://github.com/laravel/framework/commit/c90fc5f6b8e91e3f6b0f2f3a74cad7d8a49bc71b))
- Added support of MorphTo relationship eager loading constraints ([#35190](https://github.com/laravel/framework/pull/35190))
- Added `Illuminate\Http\ResponseTrait::withoutCookie()` ([e9483c4](https://github.com/laravel/framework/commit/e9483c441d5f0c8598d438d6024db8b1a7aa55fe))
- Use dynamic app namespace in Eloquent Factory instead of App\ string ([#35204](https://github.com/laravel/framework/pull/35204), [4885bd2](https://github.com/laravel/framework/commit/4885bd2d4ecf79de175d5308569ab0d608e8f55b))
- Added `read` / `unread` scopes to database notifications ([#35215](https://github.com/laravel/framework/pull/35215))
- Added `classBasename()` method to `Stringable` ([#35219](https://github.com/laravel/framework/pull/35219))
- Added before resolving callbacks to container ([#35228](https://github.com/laravel/framework/pull/35228))
- Adds the possibility of testing file upload content ([#35231](https://github.com/laravel/framework/pull/35231))
- Added lost connection messages for MySQL persistent connections ([#35224](https://github.com/laravel/framework/pull/35224))
- Added Support DBAL v3.0 ([#35236](https://github.com/laravel/framework/pull/35236))

### Fixed
- Update MySqlSchemaState.php to support MariaDB dump ([#35184](https://github.com/laravel/framework/pull/35184))
- Fixed pivot and morphpivot fresh and refresh methods ([#35193](https://github.com/laravel/framework/pull/35193))
- Fixed pivot restoration ([#35218](https://github.com/laravel/framework/pull/35218))

### Changed
- Updated `EmailVerificationRequest.php` to check if user is not already verified ([#35174](https://github.com/laravel/framework/pull/35174))
- Make `Validator::parseNamedParameters()` public ([#35183](https://github.com/laravel/framework/pull/35183))
- Ignore max attempts if retryUntil is set in `queue:work` ([#35214](https://github.com/laravel/framework/pull/35214))
- Explode string channels on `Illuminate/Log/LogManager::createStackDriver()` ([e5b86f2](https://github.com/laravel/framework/commit/e5b86f2efec2959fb0e85ad5ee5de18f430643c4))


## [v8.14.0 (2020-11-10)](https://github.com/laravel/framework/compare/v8.13.0...v8.14.0)

### Added
- Added ability to dispatch unique jobs ([#35042](https://github.com/laravel/framework/pull/35042), [2123e60](https://github.com/laravel/framework/commit/2123e603af027e7590974864715c028357ea4969))
- Added `Model::encryptUsing()` ([#35080](https://github.com/laravel/framework/pull/35080))
- Added support to MySQL dump and import using socket ([#35083](https://github.com/laravel/framework/pull/35083), [c43054b](https://github.com/laravel/framework/commit/c43054b9decad4f66937c229e4ef0f32760c8611))
- Allow custom broadcastWith in notification broadcast channel ([#35142](https://github.com/laravel/framework/pull/35142))
- Added `Illuminate\Routing\CreatesRegularExpressionRouteConstraints::whereAlphaNumeric()` ([#35154](https://github.com/laravel/framework/pull/35154))

### Fixed
- Fixed typo in `make:seeder` command name inside ModelMakeCommand ([#35107](https://github.com/laravel/framework/pull/35107))
- Fix SQL Server grammar for upsert (missing semicolon) ([#35112](https://github.com/laravel/framework/pull/35112))
- Respect migration table name in config when dumping schema ([110eb15](https://github.com/laravel/framework/commit/110eb15a77f84da0d83ebc2bb123eec08ecc19ca))
- Respect theme when previewing notification ([ed4411d](https://github.com/laravel/framework/commit/ed4411d310f259f75e95e882b748ba9d76d7cfad))
- Fix appendable attributes in Blade components ([#35131](https://github.com/laravel/framework/pull/35131)) 
- Remove decrypting array cookies from cookie decrypting ([#35130](https://github.com/laravel/framework/pull/35130)) 
- Turn the eloquent collection into a base collection if mapWithKeys loses models ([#35129](https://github.com/laravel/framework/pull/35129))

### Changed
- Move dispatching of DatabaseRefreshed event to fire before seeders are run ([#35091](https://github.com/laravel/framework/pull/35091))
- Handle returning false from reportable callback ([55f0b5e](https://github.com/laravel/framework/commit/55f0b5e7449b87b7340a761bf9e6456fdc8ffc4d))
- Update `Illuminate\Database\Schema\Grammars\MySqlGrammar::typeTimestamp()` ([#35143](https://github.com/laravel/framework/pull/35143))
- Remove expectedTables after converting to expectedOutput in PendingCommand ([#35163](https://github.com/laravel/framework/pull/35163)) 
- Change SQLite schema command environment variables to work on Windows ([#35164](https://github.com/laravel/framework/pull/35164))
  
  
## [v8.13.0 (2020-11-03)](https://github.com/laravel/framework/compare/v8.12.3...v8.13.0)

### Added
- Added `loadMax()` | `loadMin()` | `loadSum()` | `loadAvg()` methods to `Illuminate\Database\Eloquent\Collection`. Added `loadMax()` | `loadMin()` | `loadSum()` | `loadAvg()` | `loadMorphMax()` | `loadMorphMin()` | `loadMorphSum()` | `loadMorphAvg()` methods to `Illuminate\Database\Eloquent\Model` ([#35029](https://github.com/laravel/framework/pull/35029))
- Modify `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::has()` method to support MorphTo relations ([#35050](https://github.com/laravel/framework/pull/35050))
- Added `Illuminate\Support\Stringable::chunk()` ([#35038](https://github.com/laravel/framework/pull/35038))

### Fixed
- Fixed a few issues in `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::withAggregate()` ([#35061](https://github.com/laravel/framework/pull/35061), [#35063](https://github.com/laravel/framework/pull/35063))

### Changed
- Set chain `queue` | `connection` | `delay` only when explicitly configured in ([#35047](https://github.com/laravel/framework/pull/35047))

### Refactoring
- Remove redundant unreachable return statements in some places ([#35053](https://github.com/laravel/framework/pull/35053))


## [v8.12.3 (2020-10-30)](https://github.com/laravel/framework/compare/v8.12.2...v8.12.3)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::withAggregate()` ([20b0c6e](https://github.com/laravel/framework/commit/20b0c6e19b635466f776502b3f1260c7c51b04ae))


## [v8.12.2 (2020-10-29)](https://github.com/laravel/framework/compare/v8.12.1...v8.12.2)

### Fixed
- [Add some fixes](https://github.com/laravel/framework/compare/v8.12.1...v8.12.2) 


## [v8.12.1 (2020-10-29)](https://github.com/laravel/framework/compare/v8.12.0...v8.12.1)

### Fixed
- Fixed alias usage in `Eloquent` ([6091048](https://github.com/laravel/framework/commit/609104806b8b639710268c75c22f43034c2b72db))
- Fixed `Illuminate\Support\Reflector::isCallable()` ([a90f344](https://github.com/laravel/framework/commit/a90f344c66f0a5bb1d718f8bbd20c257d4de9e02))


## [v8.12.0 (2020-10-29)](https://github.com/laravel/framework/compare/v8.11.2...v8.12.0)

### Added
- Added ability to create observers with custom path via `make:observer` command ([#34911](https://github.com/laravel/framework/pull/34911))
- Added `Illuminate\Database\Eloquent\Factories\Factory::lazy()` ([#34923](https://github.com/laravel/framework/pull/34923))
- Added ability to make cast with custom stub file via `make:cast` command ([#34930](https://github.com/laravel/framework/pull/34930))
- ADDED: Custom casts can implement increment/decrement logic  ([#34964](https://github.com/laravel/framework/pull/34964))
- Added encrypted Eloquent cast ([#34937](https://github.com/laravel/framework/pull/34937), [#34948](https://github.com/laravel/framework/pull/34948))
- Added `DatabaseRefreshed` event to be emitted after database refreshed ([#34952](https://github.com/laravel/framework/pull/34952), [f31bfe2](https://github.com/laravel/framework/commit/f31bfe2fb83829a900f75fccd12af4b69ffb6275))
- Added `withMax()`|`withMin()`|`withSum()`|`withAvg()` methods to `Illuminate/Database/Eloquent/Concerns/QueriesRelationships` ([#34965](https://github.com/laravel/framework/pull/34965), [f4e4d95](https://github.com/laravel/framework/commit/f4e4d95c8d4c2f63f9bd80c2a4cfa6b2c78bab1b), [#35004](https://github.com/laravel/framework/pull/35004))
- Added `explain()` to `Query\Builder` and `Eloquent\Builder` ([#34969](https://github.com/laravel/framework/pull/34969))
- Make `multiple_of` validation rule handle non-integer values ([#34971](https://github.com/laravel/framework/pull/34971))
- Added `setKeysForSelectQuery` method and use it when refreshing model data in Models ([#34974](https://github.com/laravel/framework/pull/34974))
- Full PHP 8.0 Support ([#33388](https://github.com/laravel/framework/pull/33388))
- Added `Illuminate\Support\Reflector::isCallable()` ([#34994](https://github.com/laravel/framework/pull/34994), [8c16891](https://github.com/laravel/framework/commit/8c16891c6e7a4738d63788f4447614056ab5136e), [31917ab](https://github.com/laravel/framework/commit/31917abcfa0db6ec6221bb07fc91b6e768ff5ec8), [11cfa4d](https://github.com/laravel/framework/commit/11cfa4d4c92bf2f023544d58d51b35c5d31dece0), [#34999](https://github.com/laravel/framework/pull/34999))
- Added route regex registration methods ([#34997](https://github.com/laravel/framework/pull/34997), [3d405cc](https://github.com/laravel/framework/commit/3d405cc2eb66bba97433b46abaca52623c64c94b), [c2df0d5](https://github.com/laravel/framework/commit/c2df0d5faddeb7e58d1832c1c1f0f309619969af))
- Added dontRelease option to RateLimited and RateLimitedWithRedis job middleware ([#35010](https://github.com/laravel/framework/pull/35010))

### Fixed
- Fixed check of file path in `Illuminate\Database\Schema\PostgresSchemaState::load()` ([268237f](https://github.com/laravel/framework/commit/268237fcda420e5c26ab2f0fbdb9b8783c276ff8))
- Fixed: `PhpRedis (v5.3.2)` cluster - set default connection context to `null` ([#34935](https://github.com/laravel/framework/pull/34935))
- Fixed Eloquent Model `loadMorph` and `loadMorphCount` methods ([#34972](https://github.com/laravel/framework/pull/34972))
- Fixed ambigious column on many to many with select load ([5007986](https://github.com/laravel/framework/commit/500798623d100a9746b2931ae6191cb756521f05))
- Fixed Postgres Dump ([#35018](https://github.com/laravel/framework/pull/35018))

### Changed
- Changed `make:factory` command ([#34947](https://github.com/laravel/framework/pull/34947), [4f38176](https://github.com/laravel/framework/commit/4f3817654a6376a2f6cd59dc5fb529ebad1d951f))
- Make assertSee, assertSeeText, assertDontSee and assertDontSeeText accept an array ([#34982](https://github.com/laravel/framework/pull/34982), [2b98bcc](https://github.com/laravel/framework/commit/2b98bcca598eb919b2afd61e5fb5cb86aec4c706))


## [v8.11.2 (2020-10-20)](https://github.com/laravel/framework/compare/v8.11.1...v8.11.2)

### Revert
- Revert ["Change loadRoutesFrom to accept $attributes](https://github.com/laravel/framework/pull/34866)" ([#34909](https://github.com/laravel/framework/pull/34909))


## [v8.11.1 (2020-10-20)](https://github.com/laravel/framework/compare/v8.11.0...v8.11.1)

### Fixed
- Fixed `bound()` method ([a7759d7](https://github.com/laravel/framework/commit/a7759d70e15b0be946569b8299ac694c08a35d7e))


## [v8.11.0 (2020-10-20)](https://github.com/laravel/framework/compare/v8.10.0...v8.11.0)

### Added
- Added job middleware to prevent overlapping jobs ([#34794](https://github.com/laravel/framework/pull/34794), [eed05b4](https://github.com/laravel/framework/commit/eed05b41097cfe62766d4086ede8dee97c057c29))
- Bring Rate Limiters to Jobs ([#34829](https://github.com/laravel/framework/pull/34829), [ae00294](https://github.com/laravel/framework/commit/ae00294c418e431372bad0d09ac15d15925247f7))
- Added `multiple_of` custom replacer in validator ([#34858](https://github.com/laravel/framework/pull/34858))
- Preserve eloquent collection type after calling ->fresh() ([#34848](https://github.com/laravel/framework/pull/34848))
- Provisional support for PHP 8.0 for 6.x (Changed some code in 8.x) ([#34884](https://github.com/laravel/framework/pull/34884), [28bb76e](https://github.com/laravel/framework/commit/28bb76efbcfc5fee57307ffa062b67ff709240dc))

### Fixed
- Fixed `fresh()` and `refresh()` on pivots and morph pivots ([#34836](https://github.com/laravel/framework/pull/34836))
- Fixed config `batching` typo ([#34852](https://github.com/laravel/framework/pull/34852))
- Fixed `Illuminate\Queue\Console\RetryBatchCommand` for un-found batch id ([#34878](https://github.com/laravel/framework/pull/34878))

### Changed
- Change `loadRoutesFrom()` to accept group $attributes ([#34866](https://github.com/laravel/framework/pull/34866))


## [v8.10.0 (2020-10-13)](https://github.com/laravel/framework/compare/v8.9.0...v8.10.0)

### Added
- Allow for chains to be added to batches ([#34612](https://github.com/laravel/framework/pull/34612), [7b4a9ec](https://github.com/laravel/framework/commit/7b4a9ec6c58906eb73957015e4c78f73e780e944))
- Added `is()` method to 1-1 relations for model comparison ([#34693](https://github.com/laravel/framework/pull/34693), [7ba2577](https://github.com/laravel/framework/commit/7ba257732d2342175a6ffe7db7a4ca847ca1d353))
- Added `upsert()` to Eloquent and Base Query Builders ([#34698](https://github.com/laravel/framework/pull/34698), [#34712](https://github.com/laravel/framework/pull/34712), [58a0e1b](https://github.com/laravel/framework/commit/58a0e1b7e2bb6df3923883c4fc8cf13b1bce7322))
- Support psql and pg_restore commands in schema load ([#34711](https://github.com/laravel/framework/pull/34711))
- Added `Illuminate\Database\Schema\Builder::dropColumns()` method on the schema class ([#34720](https://github.com/laravel/framework/pull/34720))
- Added `yearlyOn()` method to scheduler ([#34728](https://github.com/laravel/framework/pull/34728))
- Added `restrictOnDelete()` method to ForeignKeyDefinition class ([#34752](https://github.com/laravel/framework/pull/34752))
- Added `newLine()` method to `InteractsWithIO` trait ([#34754](https://github.com/laravel/framework/pull/34754))
- Added `isNotEmpty()` method to HtmlString ([#34774](https://github.com/laravel/framework/pull/34774))
- Added `delay()` to PendingChain ([#34789](https://github.com/laravel/framework/pull/34789))
- Added "multiple_of" validation rule ([#34788](https://github.com/laravel/framework/pull/34788))
- Added custom methods proxy support for jobs `dispatch()` ([#34781](https://github.com/laravel/framework/pull/34781))
- Added `QueryBuilder::clone()` ([#34780](https://github.com/laravel/framework/pull/34780))
- Support bus chain on fake ([a952ac24](https://github.com/laravel/framework/commit/a952ac24f34b832270a2f80cd425c2afe4c61fc1))
- Added missing force flag to `queue:clear` command ([#34809](https://github.com/laravel/framework/pull/34809))
- Added `dropConstrainedForeignId()` to `Blueprint ([#34806](https://github.com/laravel/framework/pull/34806))
- Implement `supportsTags()` on the Cache Repository ([#34820](https://github.com/laravel/framework/pull/34820))
- Added `canAny` to user model ([#34815](https://github.com/laravel/framework/pull/34815))
- Added `when()` and `unless()` methods to MailMessage ([#34814](https://github.com/laravel/framework/pull/34814))

### Fixed
- Fixed collection wrapping in `BelongsToManyRelationship` ([9245807](https://github.com/laravel/framework/commit/9245807f8a1132a30ce669513cf0e99e9e078267))
- Fixed `LengthAwarePaginator` translations issue ([#34714](https://github.com/laravel/framework/pull/34714))

### Changed
- Improve `schedule:work` command ([#34736](https://github.com/laravel/framework/pull/34736), [bbddba2](https://github.com/laravel/framework/commit/bbddba279bc781fc2868a6967430943de636614f))
- Guard against invalid guard in `make:policy` ([#34792](https://github.com/laravel/framework/pull/34792))
- Fixed router inconsistency for namespaced route groups ([#34793](https://github.com/laravel/framework/pull/34793))


## [v8.9.0 (2020-10-06)](https://github.com/laravel/framework/compare/v8.8.0...v8.9.0)

### Added
- Added support `times()` with `raw()` from `Illuminate\Database\Eloquent\Factories\Factory` ([#34667](https://github.com/laravel/framework/pull/34667))
- Added `Illuminate\Pagination\AbstractPaginator::through()` ([#34657](https://github.com/laravel/framework/pull/34657))
- Added `extendsFirst()` method similar to `includesFirst()` to view ([#34648](https://github.com/laravel/framework/pull/34648))
- Allowed `Illuminate\Http\Client\PendingRequest::attach()` method to accept many files ([#34697](https://github.com/laravel/framework/pull/34697), [1bb7ad6](https://github.com/laravel/framework/commit/1bb7ad664a3607f719af2d91c3f95cf71662dcd2))
- Allowed serializing custom casts when converting a model to an array ([#34702](https://github.com/laravel/framework/pull/34702))

### Fixed
- Added missed RESET_THROTTLED constant to Password Facade ([#34641](https://github.com/laravel/framework/pull/34641))
- Fixed queue clearing when blocking ([#34659](https://github.com/laravel/framework/pull/34659))
- Fixed missing import in TestView.php ([#34677](https://github.com/laravel/framework/pull/34677))
- Use `getRealPath` to ensure console command class names are generated correctly in `Illuminate\Foundation\Console\Kernel` ([#34653](https://github.com/laravel/framework/pull/34653))
- Added `pg_dump --no-owner` and `--no-acl` to avoid owner/permission issues in `Illuminate\Database\Schema\PostgresSchemaState::baseDumpCommand()` ([#34689](https://github.com/laravel/framework/pull/34689))
- Fixed `queue:failed` command when Class not exists ([#34696](https://github.com/laravel/framework/pull/34696))

### Performance
- Increase performance of `Str::before()` by over 60% ([#34642](https://github.com/laravel/framework/pull/34642))


## [v8.8.0 (2020-10-02)](https://github.com/laravel/framework/compare/v8.7.1...v8.8.0)

### Added
- Proxy URL Generation in `VerifyEmail` ([#34572](https://github.com/laravel/framework/pull/34572))
- Added `Illuminate\Collections\Traits\EnumeratesValues::pipeInto()` ([#34600](https://github.com/laravel/framework/pull/34600))
- Added `Illuminate\Http\Client\PendingRequest::withUserAgent()` ([#34611](https://github.com/laravel/framework/pull/34611))
- Added `schedule:work` command ([#34618](https://github.com/laravel/framework/pull/34618))
- Added support for appendable (prepends) component attributes ([09b887b](https://github.com/laravel/framework/commit/09b887b85614d3e2539e74f40d7aa9c1c9f903d3), [53fbc9f](https://github.com/laravel/framework/commit/53fbc9f3768f611c960a5d891a1abb259163978a))

### Fixed
- Fixed `Illuminate\Http\Client\Response::throw()` ([#34597](https://github.com/laravel/framework/pull/34597))
- Fixed breaking change in migrate command ([b2a3641](https://github.com/laravel/framework/commit/b2a36411a774dba218fa312b8fd3bcf4be44a4e5))

### Changed
- Changing the dump and restore method for a PostgreSQL database ([#34293](https://github.com/laravel/framework/pull/34293))


## [v8.7.1 (2020-09-29)](https://github.com/laravel/framework/compare/v8.7.0...v8.7.1)

### Fixed
- Remove type hints ([1b3f62a](https://github.com/laravel/framework/commit/1b3f62aaeced2c9761a6052a7f0d3c1a046851c9))


## [v8.7.0 (2020-09-29)](https://github.com/laravel/framework/compare/v8.6.0...v8.7.0)

### Added
- Added `tg://` protocol in "url" validation rule ([#34464](https://github.com/laravel/framework/pull/34464))
- Allow dynamic factory methods to obey newFactory method on model ([#34492](https://github.com/laravel/framework/pull/34492), [4708e9e](https://github.com/laravel/framework/commit/4708e9ef8f7cde617a5820f07cfd350daaba0e0f))
- Added `no-reload` option to `serve` command ([9cc2622](https://github.com/laravel/framework/commit/9cc2622a9122f5108a694856055c13db8a5f80dc))
- Added `perHour()` and `perDay()` methods to `Illuminate\Cache\RateLimiting\Limit` ([#34530](https://github.com/laravel/framework/pull/34530))
- Added `Illuminate\Http\Client\Response::onError()` ([#34558](https://github.com/laravel/framework/pull/34558), [d034e2c](https://github.com/laravel/framework/commit/d034e2c55c6502fa0c2bebb6cbf99c5e685beaa5))
- Added `X-Message-ID` to `Mailgun` and `Ses Transport` ([#34567](https://github.com/laravel/framework/pull/34567)) 

### Fixed
- Fixed incompatibility with Lumen route function in `Illuminate\Session\Middleware\StartSession` ([#34491](https://github.com/laravel/framework/pull/34491))
- Fixed: Eager loading MorphTo relationship does not honor each models `$keyType` ([#34531](https://github.com/laravel/framework/pull/34531), [c3f44c7](https://github.com/laravel/framework/commit/c3f44c712833d83061452e9a362a5e10fa424863))
- Fixed translation label ("Pagination Navigation") for the Tailwind blade ([#34568](https://github.com/laravel/framework/pull/34568))
- Fixed save keys on increment / decrement in Model ([77db028](https://github.com/laravel/framework/commit/77db028225ccd6ec6bc3359f69482f2e4cc95faf))

### Changed
- Allow modifiers in date format in Model ([#34507](https://github.com/laravel/framework/pull/34507))
- Allow for dynamic calls of anonymous component with varied attributes ([#34498](https://github.com/laravel/framework/pull/34498))
- Cast `Expression` as string so it can be encoded ([#34569](https://github.com/laravel/framework/pull/34569))


## [v8.6.0 (2020-09-22)](https://github.com/laravel/framework/compare/v8.5.0...v8.6.0)

### Added
- Added `Illuminate\Collections\LazyCollection::takeUntilTimeout()` ([0aabf24](https://github.com/laravel/framework/commit/0aabf2472850a9d573907ca092bf5e3cfe26fab3))
- Added `--schema-path` option to `migrate:fresh` command ([#34419](https://github.com/laravel/framework/pull/34419))

### Fixed
- Fixed problems with dots in validator ([#34355](https://github.com/laravel/framework/pull/34355))
- Maintenance mode: Fix empty Retry-After header ([#34412](https://github.com/laravel/framework/pull/34412))
- Fixed bug with error handling in closure scheduled tasks ([#34420](https://github.com/laravel/framework/pull/34420))
- Don't double escape on `ComponentTagCompiler.php` ([12ba0d9](https://github.com/laravel/framework/commit/12ba0d937d54e81eccf8f0a80150f0d70604e1c2))
- Fixed `mysqldump: unknown variable 'column-statistics=0` for MariaDB schema dump ([#34442](https://github.com/laravel/framework/pull/34442))


## [v8.5.0 (2020-09-19)](https://github.com/laravel/framework/compare/v8.4.0...v8.5.0)

### Added
- Allow clearing an SQS queue by `queue:clear` command ([#34383](https://github.com/laravel/framework/pull/34383), [de811ea](https://github.com/laravel/framework/commit/de811ea7f7dc7ecfc686b25fba48e4b0dac473e6))
- Added `Illuminate\Foundation\Auth\EmailVerificationRequest` ([4bde31b](https://github.com/laravel/framework/commit/4bde31b24bf01b4d4a35ad31fafd8e4ca203b0f2))
- Auto handle `Jsonable` values passed to `castAsJson()` ([#34392](https://github.com/laravel/framework/pull/34392))
- Added `crossJoinSub()` method to the query builder ([#34400](https://github.com/laravel/framework/pull/34400))
- Added `Illuminate\Session\Store::passwordConfirmed()` ([fb3f45a](https://github.com/laravel/framework/commit/fb3f45aa0142764c5c29b97e8bcf8328091986e9))

### Changed
- Check for view existence first in `Illuminate\Mail\Markdown::render()` ([5f78c90](https://github.com/laravel/framework/commit/5f78c90a7af118dd07703a78da06586016973a66))
- Guess the model name when using the `make:factory` command ([#34373](https://github.com/laravel/framework/pull/34373))


## [v8.4.0 (2020-09-16)](https://github.com/laravel/framework/compare/v8.3.0...v8.4.0)

### Added
- Added SQLite schema dump support ([#34323](https://github.com/laravel/framework/pull/34323))
- Added `queue:clear` command ([#34330](https://github.com/laravel/framework/pull/34330), [06b378c](https://github.com/laravel/framework/commit/06b378c07b2ea989aa3e947ca003e96ea277153c))

### Fixed
- Fixed `minimal.blade.php` ([#34379](https://github.com/laravel/framework/pull/34379))
- Don't double escape on ComponentTagCompiler.php ([ec75487](https://github.com/laravel/framework/commit/ec75487062506963dd27a4302fe3680c0e3681a3))
- Fixed dots in attribute names in `DynamicComponent` ([2d1d962](https://github.com/laravel/framework/commit/2d1d96272a94bce123676ed742af2d80ba628ba4))

### Changed
- Show warning when view exists when using artisan `make:component` ([#34376](https://github.com/laravel/framework/pull/34376), [0ce75e0](https://github.com/laravel/framework/commit/0ce75e01a66ba4b13bbe4cbed85564f1dc76bb05))
- Call the booting/booted callbacks from the container ([#34370](https://github.com/laravel/framework/pull/34370))


## [v8.3.0 (2020-09-15)](https://github.com/laravel/framework/compare/v8.2.0...v8.3.0)

### Added
- Added `Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase::castAsJson()` ([#34302](https://github.com/laravel/framework/pull/34302))
- Handle array hosts in `Illuminate\Database\Schema\MySqlSchemaState` ([0920c23](https://github.com/laravel/framework/commit/0920c23efb9d7042d074729f2f70acbfec629c14))
- Added `Illuminate\Pipeline\Pipeline::setContainer()` ([#34343](https://github.com/laravel/framework/pull/34343))
- Allow including a closure in a queued batch ([#34333](https://github.com/laravel/framework/pull/34333))

### Fixed
- Fixed broken Seeder ([9e4a866](https://github.com/laravel/framework/commit/9e4a866cfb0420f4ea6cb4e86b1fbd97a4b8c264))

### Changed
- Bumped minimum vlucas/phpdotenv version ([#34336](https://github.com/laravel/framework/pull/34336))
- Pass an instance of the job to queued closures ([#34350](https://github.com/laravel/framework/pull/34350))


## [v8.2.0 (2020-09-14)](https://github.com/laravel/framework/compare/v8.1.0...v8.2.0)

### Added
- Added `Illuminate\Database\Eloquent\Factories\HasFactory::newFactory()` ([4a95372](https://github.com/laravel/framework/commit/4a953728f5e085342d793372329ae534e5885724), [a2cea84](https://github.com/laravel/framework/commit/a2cea84805f311be612fc36c403fcc6f90181ff4))

### Fixed
- Do not used `now` helper in `Illuminate/Cache/DatabaseLock::expiresAt()` ([#34262](https://github.com/laravel/framework/pull/34262))
- Change placeholder in `Illuminate\Database\Schema\MySqlSchemaState::load()` ([#34303](https://github.com/laravel/framework/pull/34303))
- Fixed bug in dynamic attributes `Illuminate\View\ComponentAttributeBag::setAttributes()` ([93f4613](https://github.com/laravel/framework/commit/93f461344051e8d44c4a50748b7bdc0eae18bcac))
- Fixed `Illuminate\View\ComponentAttributeBag::whereDoesntStartWith()` ([#34329](https://github.com/laravel/framework/pull/34329))
- Fixed `Illuminate\Routing\Middleware\ThrottleRequests::handleRequestUsingNamedLimiter()` ([#34325](https://github.com/laravel/framework/pull/34325))

### Changed
- Create Faker when a Factory is created ([#34298](https://github.com/laravel/framework/pull/34298))


## [v8.1.0 (2020-09-11)](https://github.com/laravel/framework/compare/v8.0.4...v8.1.0)

### Added
- Added `Illuminate\Database\Eloquent\Factories\Factory::raw()` ([#34278](https://github.com/laravel/framework/pull/34278))
- Added `Illuminate\Database\Eloquent\Factories\Factory::createMany()` ([#34285](https://github.com/laravel/framework/pull/34285), [69072c7](https://github.com/laravel/framework/commit/69072c7d3efd2784d195cb95e45e4dcb8ef5907f))
- Added the `Countable` interface to `AssertableJsonString` ([#34284](https://github.com/laravel/framework/pull/34284))

### Fixed
- Fixed the new maintenance mode ([#34264](https://github.com/laravel/framework/pull/34264))

### Changed
- Optimize command can also cache view ([#34287](https://github.com/laravel/framework/pull/34287))


## [v8.0.4 (2020-09-11)](https://github.com/laravel/framework/compare/v8.0.3...v8.0.4)

### Changed
- Allow `Illuminate\Collections\Collection::implode()` when instance of `Stringable` ([#34271](https://github.com/laravel/framework/pull/34271))

### Fixed
- Fixed `DatabaseUuidFailedJobProvider::find()` job record structure ([#34251](https://github.com/laravel/framework/pull/34251))
- Cast linkCollection to array in JSON pagination responses ([#34245](https://github.com/laravel/framework/pull/34245))
- Change the placeholder of schema dump according to symfony placeholder in `MySqlSchemaState::dump()` ([#34261](https://github.com/laravel/framework/pull/34261))
- Fixed problems with dots in validator ([8723739](https://github.com/laravel/framework/commit/8723739746a53442a5ec5bdebe649f8a4d9dd3c2))


## [v8.0.3 (2020-09-10)](https://github.com/laravel/framework/compare/v8.0.2...v8.0.3)

### Added
- Added links property to JSON pagination responses ([13751a1](https://github.com/laravel/framework/commit/13751a187834fabe515c14fb3ac1dc008fd23f37))

### Fixed
- Fixed bugs with factory creation in `FactoryMakeCommand` ([c7186e0](https://github.com/laravel/framework/commit/c7186e09204cb3ed72ab24fe9f25a6450c2512bb))


## [v8.0.2 (2020-09-09)](https://github.com/laravel/framework/compare/v8.0.1...v8.0.2)

### Revert
- Revert of ["Fixed for empty fallback_locale in `Illuminate\Translation\Translator`"](https://github.com/laravel/framework/pull/34136) ([7c54eb6](https://github.com/laravel/framework/commit/7c54eb678d58fb9ee7f532a5a5842e6f0e1fe4c9))

### Changed
- Update `Illuminate\Database\Schema\MySqlSchemaState::executeDumpProcess()` ([#34233](https://github.com/laravel/framework/pull/34233))


## [v8.0.1 (2020-09-09)](https://github.com/laravel/framework/compare/v8.0.0...v8.0.1)

### Added
- Support array syntax in `Illuminate\Routing\Route::uses()` ([f80ba11](https://github.com/laravel/framework/commit/f80ba11b698b6130bdbc7ffdcb947519deabbdba))

### Fixed
- Fixed `BatchRepositoryFake` TypeError ([#34225](https://github.com/laravel/framework/pull/34225))
- Fixed dynamic component bug ([4b1e317](https://github.com/laravel/framework/commit/4b1e317c7aec22c2767766bb8b84e059fe4e0802))
  
### Changed
- Give shadow a rounded edge to match content in `tailwind.blade.php` ([#34198](https://github.com/laravel/framework/pull/34198))
- Pass the request to the renderable callback in `Illuminate\Foundation\Exceptions\Handler::render()` ([#34200](https://github.com/laravel/framework/pull/34200))
- Update `Illuminate\Database\Schema\MySqlSchemaState` ([d67be130](https://github.com/laravel/framework/commit/d67be1305bef418d9bdeb8192177202f9d705699), [c87794f](https://github.com/laravel/framework/commit/c87794fc354941729d1f0c4607693c0b8d2cfda2))
- Respect local env in `Illuminate\Foundation\Console\ServeCommand::startProcess()` ([75e792d](https://github.com/laravel/framework/commit/75e792d61871780f75ecb4eb170826b0ba2f305e))


## [v8.0.0 (2020-09-08)](https://github.com/laravel/framework/compare/v7.27.0...v8.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/8.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/8.x/releases).
