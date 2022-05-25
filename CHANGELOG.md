# Release Notes for 9.x

## [Unreleased](https://github.com/laravel/framework/compare/v9.14.0...9.x)


## [v9.14.0](https://github.com/laravel/framework/compare/v9.13.0...v9.14.0) - 2022-05-24

### Added
- Added ability to add table comments for MySQL and Postgres ([#42401](https://github.com/laravel/framework/pull/42401))
- Added dynamic trashed factory state ([#42414](https://github.com/laravel/framework/pull/42414))
- Added Illuminate/Collections/Arr::prependKeysWith() ([#42448](https://github.com/laravel/framework/pull/42448))
- Added bootable traits to TestCase ([#42394](https://github.com/laravel/framework/pull/42394))

### Fixed
- Fix clone issue on updateOrCreate and firstOrCreate ([#42434](https://github.com/laravel/framework/pull/42434))
- Prevent double sanitized key in RateLimiter@tooManyAttempts ([#42462](https://github.com/laravel/framework/pull/42462))
- Add flush handler to output buffer for streamed test response (bugfix) ([#42481](https://github.com/laravel/framework/pull/42481))

### Changed
- Adds attaches a concise error message to SES exceptions ([#42426](https://github.com/laravel/framework/pull/42426))
- Use duplicate instead of createFromBase to clone request when routes are cached ([#42420](https://github.com/laravel/framework/pull/42420))
- Use model route key when route parameter does not specifiy custom binding field but a different parameter does ([#42425](https://github.com/laravel/framework/pull/42425))
- Adds ability to have paginate() $perPage parameter as callable with access to $total ([#42429](https://github.com/laravel/framework/pull/42429))
- Extract ServeCommand env list to static property ([#42444](https://github.com/laravel/framework/pull/42444))
- Use route parameters in view ([#42461](https://github.com/laravel/framework/pull/42461))


## [v9.13.0](https://github.com/laravel/framework/compare/v9.12.2...v9.13.0) - 2022-05-17

### Added
- Added Illuminate/Collections/Traits/EnumeratesValues::value() ([#42257](https://github.com/laravel/framework/pull/42257))
- Added new TestResponse helper: assertJsonMissingPath ([#42361](https://github.com/laravel/framework/pull/42361))
- Added Illuminate/Support/Testing/Fakes/NotificationFake::assertCount() ([#42366](https://github.com/laravel/framework/pull/42366))
- Added new DetectLostConnections ([#42377](https://github.com/laravel/framework/pull/42377), [#42382](https://github.com/laravel/framework/pull/42382))
- Added Illuminate/Testing/TestResponse::collect() ([#42384](https://github.com/laravel/framework/pull/42384))
- Added full callable support to schedule:list ([#42400](https://github.com/laravel/framework/pull/42400))
- Added `Illuminate/Collections/Arr::map()` ([#42398](https://github.com/laravel/framework/pull/42398))

### Fixed
- Fixed PruneCommand finding its usage within other traits ([#42350](https://github.com/laravel/framework/pull/42350))
- Fix assert that exception is thrown without message ([#42360](https://github.com/laravel/framework/pull/42360))

### Changed
- Skip parameter parsing for raw post body in HTTP Client ([#42364](https://github.com/laravel/framework/pull/42364))
- Consistency between digits and digits_between validation rules ([#42358](https://github.com/laravel/framework/pull/42358))
- Corrects the use of "failed_jobs" instead of "job_batches" in BatchedTableCommand ([#42389](https://github.com/laravel/framework/pull/42389))
- Update email.blade.php ([#42388](https://github.com/laravel/framework/pull/42388))
- Remove old monolog 1.x compat code ([#42392](https://github.com/laravel/framework/pull/42392))
- SesTransport: use correct Tags argument ([#42390](https://github.com/laravel/framework/pull/42390))
- Implement robust handling of forwarding of exception codes ([#42393](https://github.com/laravel/framework/pull/42393))


## [v9.12.2](https://github.com/laravel/framework/compare/v9.12.1...v9.12.2) - 2022-05-11

### Fixed
- Factory fails to eval models and factories when returned from closure ([#42344](https://github.com/laravel/framework/pull/42344))

### Changed
- Added is_string check to QueriesRelationships@requalifyWhereTables ([#42341](https://github.com/laravel/framework/pull/42341))


## [v9.12.1](https://github.com/laravel/framework/compare/v9.12.0...v9.12.1) - 2022-05-10

### Fixed
- Fix TypeError in DeadlockException ([#42337](https://github.com/laravel/framework/pull/42337))
- Fixed type mismatch on Pusher::validate_channels() ([#42340](https://github.com/laravel/framework/pull/42340))

### Changed
- Add custom segments on "remember me" for session rebuild ([#42316](https://github.com/laravel/framework/pull/42316))


## [v9.12.0](https://github.com/laravel/framework/compare/v9.11.0...v9.12.0) - 2022-05-10

### Added

- Added closure based exceptions testing ([#42155](https://github.com/laravel/framework/pull/42155))
- Allow forcing requests made via the Http client to be faked ([#42230](https://github.com/laravel/framework/pull/42230))
- Added 'throwIf' method to PendingRequest ([#42260](https://github.com/laravel/framework/pull/42260))
- Allow passing key/value arrays to getArguments and getOptions ([#42268](https://github.com/laravel/framework/pull/42268))
- Add whereNotMorphedTo, orWhereNotMorphedTo ([#42264](https://github.com/laravel/framework/pull/42264))
- Add method to extend localeArray generation ([#42275](https://github.com/laravel/framework/pull/42275))
- Added ability to set delay per channel based on notifiable instance ([#42239](https://github.com/laravel/framework/pull/42239))
- Added Illuminate/Pagination/CursorPaginator::onLastPage() ([#42301](https://github.com/laravel/framework/pull/42301))
- Added findOr method to Query/Builder ([#42290](https://github.com/laravel/framework/pull/42290))

### Fixed

- Fix too many channels with pusher broadcasting ([#42287](https://github.com/laravel/framework/pull/42287))
- Fix Str::Mask() for repeating chars ([#42295](https://github.com/laravel/framework/pull/42295))
- Fix EventFake::assertListening() for asserting string-based observer listeners ([#42289](https://github.com/laravel/framework/pull/42289))
- Fixed Loose comparison causes the value not to be saved ([#41337](https://github.com/laravel/framework/pull/41337))
- Fix multiple dots for digits_between rule ([#42330](https://github.com/laravel/framework/pull/42330))

### Changed

- Enable to modify HTTP Client request headers when using beforeSending() callback ([#42244](https://github.com/laravel/framework/pull/42244))
- Make throttle lock acquisition retry configurable for concurrency limiter ([#42242](https://github.com/laravel/framework/pull/42242))
- Defers expanding callables on Factories ([#42241](https://github.com/laravel/framework/pull/42241))
- Add wherehas soft deleting scopes ([#42100](https://github.com/laravel/framework/pull/42100))
- Improve password checks ([#42248](https://github.com/laravel/framework/pull/42248))
- Set relation parent key when using forceCreate on HasOne and HasMany relations ([#42281](https://github.com/laravel/framework/pull/42281))
- Make sure the prefix override behaviours are the same between phpredis and predis drivers ([#42279](https://github.com/laravel/framework/pull/42279))
- Share logging context across channels and stacks ([#42276](https://github.com/laravel/framework/pull/42276))

## [v9.11.0](https://github.com/laravel/framework/compare/v9.10.1...v9.11.0) - 2022-05-03

### Added

- Added Illuminate/Collections/Arr::join() ([#42197](https://github.com/laravel/framework/pull/42197))
- Added has and missing methods to ValidatedInput ([#42184](https://github.com/laravel/framework/pull/42184))
- Added deprecation stack trace config option ([#42235](https://github.com/laravel/framework/pull/42235))

### Fixed

- Fix deprecation issue with translator and empty rules ([#42216](https://github.com/laravel/framework/pull/42216), [#42213](https://github.com/laravel/framework/pull/42213))

### Changed

- Improve EventFake::assertListening() support for subscribers ([#42193](https://github.com/laravel/framework/pull/42193))

## [v9.10.1](https://github.com/laravel/framework/compare/v9.10.0...v9.10.1) - 2022-04-28

### Revert

- Revert of ["Illuminate/Routing/Router::middlewareGroup() will support array of the middlewares"](https://github.com/laravel/framework/pull/42004) ([7563912](https://github.com/laravel/framework/commit/75639121cc55d4390fd75a0f422c7f0a626ece1e))

## [v9.10.0](https://github.com/laravel/framework/compare/v9.9.0...v9.10.0) - 2022-04-27

### Added

- Add the ability to use alias when performing upsert via MySQL ([#42053](https://github.com/laravel/framework/pull/42053))
- Illuminate/Routing/Router::middlewareGroup() will support array of the middlewares ([#42004](https://github.com/laravel/framework/pull/42004), [e6b84fb](https://github.com/laravel/framework/commit/e6b84fb0f1f1c82ce1a486643e2b20974522cda6))
- Added Missing AsCommand attribute on schedule:list ([#42069](https://github.com/laravel/framework/pull/42069))
- Add the beforeRefreshingDatabase function to the Testing/RefreshDatabase trait ([#42073](https://github.com/laravel/framework/pull/42073))
- Added doesntExpectOutputToContain assertion method ([#42096](https://github.com/laravel/framework/pull/42096))
- Added a findOr method to Eloquent ([#42092](https://github.com/laravel/framework/pull/42092))
- Allow extension in `Illuminate/View/Compilers/Compiler.php` ([68e41fd](https://github.com/laravel/framework/commit/68e41fd3691b9aa5548e07c5caf38696c4082513))
- Support 'IS' and 'IS NOT' PostgreSQL operators ([#42123](https://github.com/laravel/framework/pull/42123))
- Added `str` and `string` methods to Illuminate/Http/Concerns/InteractsWithInput ([c9d34b7](https://github.com/laravel/framework/commit/c9d34b7be0611d26f3e46669934cf542cc5e9e21))
- Added methods to append and prepend jobs to existing chain ([#42138](https://github.com/laravel/framework/pull/42138))

### Fixes

- Make it so non-existent jobs run down the failed path instead of crashing ([#42079](https://github.com/laravel/framework/pull/42079))
- Fix schedule:work command Artisan binary name ([#42083](https://github.com/laravel/framework/pull/42083))
- Fix TrimStrings middleware with non-UTF8 characters ([#42065](https://github.com/laravel/framework/pull/42065))
- Copy locale and defaultLocale from original request in Request::createFrom() ([#42080](https://github.com/laravel/framework/pull/42080))
- Fix ViewErrorBag for JSON session serialization ([#42090](https://github.com/laravel/framework/pull/42090))
- Fix array keys from cached routes in CompiledRouteCollection::getRoutesByMethod() ([#42078](https://github.com/laravel/framework/pull/42078))
- Fix json_last_error issue with JsonResponse::setData ([#42125](https://github.com/laravel/framework/pull/42125))
- Fix bug in BelongsToMany where non-related rows are returned ([#42087](https://github.com/laravel/framework/pull/42087))
- Fix HasAttributes::mutateAttributeForArray when accessing non-cached attribute ([#42130](https://github.com/laravel/framework/pull/42130))

### Changed

- Make password rule errors translatable ([#42060](https://github.com/laravel/framework/pull/42060))
- Redesign of the event:list Command. ([#42068](https://github.com/laravel/framework/pull/42068))
- Changed event:list command ([#42084](https://github.com/laravel/framework/pull/42084))
- Throw LostDbConnectionException instead of LogicException ([#42102](https://github.com/laravel/framework/pull/42102))
- Throw deadlock exception ([#42129](https://github.com/laravel/framework/pull/42129))
- Support Arr::forget() for nested ArrayAccess objects ([#42142](https://github.com/laravel/framework/pull/42142))
- Allow Illuminate/Collections/Enumerable::jsonSerialize() to return other types ([#42133](https://github.com/laravel/framework/pull/42133))
- Update schedule:list colouring output  ([#42153](%5B#42153%5D(https://github.com/laravel/framework/pull/42153))

## [v9.9.0](https://github.com/laravel/framework/compare/v9.8.1...v9.9.0) - 2022-04-19

### Added

- Add getAllTables support for SQLite and SQLServer schema builders ([#41896](https://github.com/laravel/framework/pull/41896))
- Added withoutEagerLoads() method to Builder ([#41950](https://github.com/laravel/framework/pull/41950))
- Added 'throw' method to PendingRequest ([#41953](https://github.com/laravel/framework/pull/41953))
- Configurable pluralizer language and uncountables ([#41941](https://github.com/laravel/framework/pull/41941))

### Fixed

- Fixed Error in Illuminate/Routing/Exceptions/StreamedResponseException ([#41955](https://github.com/laravel/framework/pull/41955))
- Fix PHP warnings when rendering long blade string ([#41956](https://github.com/laravel/framework/pull/41956))
- Fix ExcludeIf regression to use Closure over is_callable() ([#41969](https://github.com/laravel/framework/pull/41969))
- Fixes applying replacements to multi-level localization arrays ([#42022](https://github.com/laravel/framework/pull/42022))

### Changed

- Improved Illuminate/Foundation/Http/Middleware/TrimStrings.php and Str::squish() ([#41949](https://github.com/laravel/framework/pull/41949), [#41971](https://github.com/laravel/framework/pull/41971))
- Use config session domain for maintenance cookie ([#41961](https://github.com/laravel/framework/pull/41961))
- Revert lazy command forcing ([#41982](https://github.com/laravel/framework/pull/41982))

## [v9.8.1](https://github.com/laravel/framework/compare/v9.8.0...v9.8.1) - 2022-04-12

### Reverted

- Revert "Standardize withCount() & withExists() eager loading aggregates ([#41943](https://github.com/laravel/framework/pull/41943))

## [v9.8.0](https://github.com/laravel/framework/compare/v9.7.0...v9.8.0) - 2022-04-12

### Added

- Added inbound option to CastMakeCommand ([#41838](https://github.com/laravel/framework/pull/41838))
- Added a way to retrieve the first column of the first row from a query ([#41858](https://github.com/laravel/framework/pull/41858))
- Make DatabaseManager Macroable ([#41868](https://github.com/laravel/framework/pull/41868))
- Improve Str::squish() ([#41877](https://github.com/laravel/framework/pull/41877), [#41924](https://github.com/laravel/framework/pull/41924))
- Added option to disable cached view ([#41859](https://github.com/laravel/framework/pull/41859))
- Make Connection Class Macroable ([#41865](https://github.com/laravel/framework/pull/41865))
- Added possibility to discover anonymous Blade components in other folders ([#41637](https://github.com/laravel/framework/pull/41637))
- Added `Illuminate/Database/Eloquent/Factories/Factory::set()` ([#41890](https://github.com/laravel/framework/pull/41890))
- Added multibyte support to string padding helper functions ([#41899](https://github.com/laravel/framework/pull/41899))
- Allow to use custom log level in exception handler reporting ([#41925](https://github.com/laravel/framework/pull/41925))

### Fixed

- Illuminate/Support/Stringable::exactly() with Stringable value ([#41846](https://github.com/laravel/framework/pull/41846))
- Fixed afterCommit and RefreshDatabase ([#41782](https://github.com/laravel/framework/pull/41782))
- Fix null name for email address in `Illuminate/Mail/Message` ([#41870](https://github.com/laravel/framework/pull/41870))
- Fix seeder property for in-memory tests ([#41869](https://github.com/laravel/framework/pull/41869))
- Fix empty paths for server.php ([#41933](https://github.com/laravel/framework/pull/41933))
- Fix ExcludeIf constructor ([#41931](https://github.com/laravel/framework/pull/41931))

### Changed

- Set custom host to the serve command with environment variable ([#41831](https://github.com/laravel/framework/pull/41831))
- Add handling of object being passed into old method in Model ([#41842](https://github.com/laravel/framework/pull/41842))
- Catch permission exception when creating directory ([#41871](https://github.com/laravel/framework/pull/41871))
- Restore v8 behaviour of base query for relations ([#41918](https://github.com/laravel/framework/pull/41918), [#41923](https://github.com/laravel/framework/pull/41923))
- Standardize withCount() & withExists() eager loading aggregates ([#41914](https://github.com/laravel/framework/pull/41914))

## [v9.7.0](https://github.com/laravel/framework/compare/v9.6.0...v9.7.0) - 2022-04-05

### Added

- Make whereBelongsTo accept Collection ([#41733](https://github.com/laravel/framework/pull/41733))
- Database queries containing JSON paths support array index braces ([#41767](https://github.com/laravel/framework/pull/41767))
- Fire event before route matched ([#41765](https://github.com/laravel/framework/pull/41765))
- Added to `Illuminate/Http/Resources/ConditionallyLoadsAttributes::whenNotNull` method ([#41769](https://github.com/laravel/framework/pull/41769))
- Added "whereIn" route parameter constraint method ([#41794](https://github.com/laravel/framework/pull/41794))
- Added `Illuminate/Queue/BeanstalkdQueue::bulk()` ([#41789](https://github.com/laravel/framework/pull/41789))
- Added `Illuminate/Queue/SqsQueue::bulk()` ([#41788](https://github.com/laravel/framework/pull/41788))
- Added String::squish() helper ([#41791](https://github.com/laravel/framework/pull/41791))
- Added query builder method whereJsonContainsKey() ([#41802](https://github.com/laravel/framework/pull/41802))
- Enable dispatchAfterResponse for batch ([#41787](https://github.com/laravel/framework/pull/41787))

### Fixed

- Factory generation fixes ([#41688](https://github.com/laravel/framework/pull/41688))
- Fixed Http Client throw boolean parameter of retry method ([#41762](https://github.com/laravel/framework/pull/41762), [#41792](https://github.com/laravel/framework/pull/41792))
- Ignore empty redis username string in PhpRedisConnector ([#41773](https://github.com/laravel/framework/pull/41773))
- Fixed support of nullable type for AsArrayObject/AsCollection ([#41797](https://github.com/laravel/framework/pull/41797), [05846e7](https://github.com/laravel/framework/commit/05846e7ba5cecc12a3ab8a3238272e9c1dd4e07f))
- Fixed adding jobs from iterable to the pending batch ([#41786](https://github.com/laravel/framework/pull/41786))
- Http client: fix retry handling of connection exception ([#41811](https://github.com/laravel/framework/pull/41811))

### Changed

- Enable batch jobs delay for database queue ([#41758](https://github.com/laravel/framework/pull/41758))
- Enable batch jobs delay for redis queue ([#41783](https://github.com/laravel/framework/pull/41783))
- Http client: dispatch "response received" event for every retry attempt ([#41793](https://github.com/laravel/framework/pull/41793))
- Http Client: provide pending request to retry callback ([#41779](https://github.com/laravel/framework/pull/41779))
- Allow non length limited strings and char for postgresql ([#41800](https://github.com/laravel/framework/pull/41800))
- Revert some Carbon::setTestNow() removals ([#41810](https://github.com/laravel/framework/pull/41810))
- Allow cleanup of databases when using parallel tests ([#41806](https://github.com/laravel/framework/pull/41806))

## [v9.6.0](https://github.com/laravel/framework/compare/v9.5.1...v9.6.0) - 2022-03-29

### Added

- Added whenTableHasColumn and whenTableDoesntHaveColumn on Schema Builder ([#41517](https://github.com/laravel/framework/pull/41517))
- Added Illuminate/Mail/Mailable::hasSubject() ([#41575](https://github.com/laravel/framework/pull/41575))
- Added `Illuminate/Filesystem/Filesystem::hasSameHash()` ([#41586](https://github.com/laravel/framework/pull/41586))

### Fixed

- Fixed deprecation warning in `Str::exists()` ([d39d92d](https://github.com/laravel/framework/commit/d39d92df9b3c509d40b971207f03eb7f04087370))
- Fix artisan make:seeder command nested namespace and class name problem ([#41534](https://github.com/laravel/framework/pull/41534))
- Fixed Illuminate/Redis/Connections/PhpRedisConnection::handle() ([#41546](https://github.com/laravel/framework/pull/41546))
- Stop throwing LazyLoadingViolationException for recently created model instances ([#41549](https://github.com/laravel/framework/pull/41549))
- Close doctrineConnection on disconnect ([#41584](https://github.com/laravel/framework/pull/41584))

### Changed

- Make throttle lock acquisition retry time configurable ([#41516](https://github.com/laravel/framework/pull/41516))
- Allows object instead of array when adding to PendingBatch ([#41475](https://github.com/laravel/framework/pull/41475))
- Exactly match scheduled command --name in schedule:test ([#41528](https://github.com/laravel/framework/pull/41528))
- Handle Symfony defaultName deprecation ([#41555](https://github.com/laravel/framework/pull/41555), [#41595](https://github.com/laravel/framework/pull/41595))
- Improve ScheduleListCommand ([#41552](https://github.com/laravel/framework/pull/41552), [#41535](https://github.com/laravel/framework/pull/41535), [#41494](https://github.com/laravel/framework/pull/41494))
- Remove useless if statement in Str::mask() ([#41570](https://github.com/laravel/framework/pull/41570))

## [v9.5.1](https://github.com/laravel/framework/compare/v9.5.0...v9.5.1) - 2022-03-15

### Reverted

- Revert "Fix the guard instance used." ([#41491](https://github.com/laravel/framework/pull/41491))

## [v9.5.0](https://github.com/laravel/framework/compare/v9.4.1...v9.5.0) - 2022-03-15

### Added

- Added callback support on implode Collection method. ([#41405](https://github.com/laravel/framework/pull/41405))
- Added `Illuminate/Filesystem/FilesystemAdapter::assertDirectoryEmpty()` ([#41398](https://github.com/laravel/framework/pull/41398))
- Implement email "metadata" for SesTransport ([#41422](https://github.com/laravel/framework/pull/41422))
- Make assertPath() accepts Closure ([#41409](https://github.com/laravel/framework/pull/41409))
- Added callable support to operatorForWhere on Collection ([#41414](https://github.com/laravel/framework/pull/41414), [#41424](https://github.com/laravel/framework/pull/41424))
- Added partial queue faking ([#41425](https://github.com/laravel/framework/pull/41425))
- Added --name option to schedule:test command ([#41439](https://github.com/laravel/framework/pull/41439))
- Define `Illuminate/Database/Eloquent/Concerns/HasRelationships::newRelatedThroughInstance()` ([#41444](https://github.com/laravel/framework/pull/41444))
- Added `Illuminate/Support/Stringable::wrap()` ([#41455](https://github.com/laravel/framework/pull/41455))
- Adds "freezeTime" helper for tests ([#41460](https://github.com/laravel/framework/pull/41460))
- Allow for callables with beforeSending in`Illuminate/Http/Client/PendingRequest.php::runBeforeSendingCallbacks()` ([#41489](https://github.com/laravel/framework/pull/41489))

### Fixed

- Fixed deprecation warnings from route:list when filtering on name or domain ([#41421](https://github.com/laravel/framework/pull/41421))
- Fixes HTTP::pool response when a URL returns a null status code ([#41412](https://github.com/laravel/framework/pull/41412))
- Fixed recaller name resolution in `Illuminate/Session/Middleware/AuthenticateSession.php` ([#41429](https://github.com/laravel/framework/pull/41429))
- Fixed the guard instance used in /Illuminate/Session/Middleware/AuthenticateSession.php ([#41447](https://github.com/laravel/framework/pull/41447))
- Fixed route:list --except-vendor hiding Route::view() & Route::redirect() ([#41465](https://github.com/laravel/framework/pull/41465))

### Changed

- Add null typing to connection property in \Illuminate\Database\Eloquent\Factories\Factory ([#41418](https://github.com/laravel/framework/pull/41418))
- Update reserved names in GeneratorCommand ([#41441](https://github.com/laravel/framework/pull/41441))
- Redesign php artisan schedule:list Command ([#41445](https://github.com/laravel/framework/pull/41445))
- Extend eloquent higher order proxy properties ([#41449](https://github.com/laravel/framework/pull/41449))
- Allow passing named arguments to dynamic scopes ([#41478](https://github.com/laravel/framework/pull/41478))
- Throw if tag is passed but is not supported in `Illuminate/Encryption/Encrypter.php` ([#41479](https://github.com/laravel/framework/pull/41479))
- Update PackageManifest::$vendorPath initialisation for cases, when composer vendor dir is not in project director ([#41463](https://github.com/laravel/framework/pull/41463))

## [v9.4.1](https://github.com/laravel/framework/compare/v9.4.0...v9.4.1) - 2022-03-08

### Fixed

- Fixed version of laravel

## [v9.4.0](https://github.com/laravel/framework/compare/v9.4.0...v9.4.0) - 2022-03-08

### Added

- Support modifying a char column type ([#41320](https://github.com/laravel/framework/pull/41320))
- Add "Mutex" column to 'schedule:list' command ([#41338](https://github.com/laravel/framework/pull/41338))
- Allow eloquent whereNot() and orWhereNot() to work on column and value ([#41296](https://github.com/laravel/framework/pull/41296))
- Allow VerifyCsrfToken's CSRF cookie to be extended ([#41342](https://github.com/laravel/framework/pull/41342))
- Added `soleValue()` to query builder ([#41368](https://github.com/laravel/framework/pull/41368))
- Added `lcfirst()` to `Str` and `Stringable` ([#41384](https://github.com/laravel/framework/pull/41384))
- Added retryUntil method to queued mailables ([#41393](https://github.com/laravel/framework/pull/41393))

### Fixed

- Fixed middleware sorting for authenticating sessions ([50b46db](https://github.com/laravel/framework/commit/50b46db563e11ba52a53e3046c23e92878aed395))
- Fixed takeUntilTimeout method of LazyCollection ([#41354](https://github.com/laravel/framework/pull/41354), [#41370](https://github.com/laravel/framework/pull/41370))
- Fixed directory for nested markdown files for mailables ([#41366](https://github.com/laravel/framework/pull/41366))
- Prevent serializing default values of queued jobs ([#41348](https://github.com/laravel/framework/pull/41348))
- Fixed get() and head() in `Illuminate/Http/Client/PendingRequest.php` ([a54f481](https://github.com/laravel/framework/commit/a54f48102deea2864071e510172fe0b22a1c1d5a))

### Changed

- Don't use global tap helper ([#41326](https://github.com/laravel/framework/pull/41326))
- Allow chaining of `Illuminate/Console/Concerns/InteractsWithIO::newLine` ([#41327](https://github.com/laravel/framework/pull/41327))
- set destinations since bcc missing from raw message in Mail SesTransport ([8ca43f4](https://github.com/laravel/framework/commit/8ca43f4c2a531ff9d28b86a7e366eef8adf8de84))

## [v9.3.1](https://github.com/laravel/framework/compare/v9.3.0...v9.3.1) - 2022-03-03

### Added

- Optionally cascade thrown Flysystem exceptions by @driesvints in https://github.com/laravel/framework/pull/41308

### Changed

- Allow overriding transport type on Mailgun transporter by @jnoordsij in https://github.com/laravel/framework/pull/41309
- Change how Laravel handles strict morph map with pivot classes by @crynobone in https://github.com/laravel/framework/pull/41304

### Fixed

- $job can be an object in some methods by @villfa in https://github.com/laravel/framework/pull/41244
- Fix docblock on Batch class by @yoeriboven in https://github.com/laravel/framework/pull/41295
- Correct `giveConfig` param doc by @Neol3108 in https://github.com/laravel/framework/pull/41314
- Fix MySqlSchemaState does not add --ssl-ca to mysql cli  by @DeepDiver1975 in https://github.com/laravel/framework/pull/41315
- Do not prepend baseUrl to absolute urls by @JaZo in https://github.com/laravel/framework/pull/41307
- Fixes getting the trusted proxies IPs from the configuration file by @nunomaduro in https://github.com/laravel/framework/pull/41322

## [v9.3.0 (2022-03-02)](https://github.com/laravel/framework/compare/v9.2.0...v9.3.0)

### Added

- Add NotificationFake::assertNothingSentTo() by @axlon ([#41232](https://github.com/laravel/framework/pull/41232))
- Support --ssl-ca on schema load and dump by @DeepDiver1975 ([#40931](https://github.com/laravel/framework/pull/40931))
- Add whereNot() to Query Builder and Eloquent Builder by @marcovo ([#41096](https://github.com/laravel/framework/pull/41096))
- Added support for index and position placeholders in array validation messages by @Bird87ZA ([#41123](https://github.com/laravel/framework/pull/41123))
- Add resource binding by @aedart ([#41233](https://github.com/laravel/framework/pull/41233))
- Add ability to push additional pipes onto a pipeline via chain($pipes) by @stevebauman ([#41256](https://github.com/laravel/framework/pull/41256))
- Add option to filter out routes defined in vendor packages in route:list command by @amiranagram ([#41254](https://github.com/laravel/framework/pull/41254))

### Fixed

- Query PostgresBuilder fixes for renamed config 'search_path' by @derekmd ([#41215](https://github.com/laravel/framework/pull/41215))
- Improve doctypes for Eloquent Factory guessing methods by @bastien-phi ([#41245](https://github.com/laravel/framework/pull/41245))
- Fix Conditional::when and Conditional::unless when called with invokable by @bastien-phi ([#41270](https://github.com/laravel/framework/pull/41270))
- Improves Support\Collection reduce method type definition by @fdalcin ([#41272](https://github.com/laravel/framework/pull/41272))
- Fix inconsistent results of firstOrNew() when using withCasts() by @Attia-Ahmed ([#41257](https://github.com/laravel/framework/pull/41257))
- Fix implicitBinding and withTrashed route with child with no SoftDeletes trait by @stein-j ([#41282](https://github.com/laravel/framework/pull/41282))

### Changed

- Unset Connection Resolver extended callback by @emrancu ([#41216](https://github.com/laravel/framework/pull/41216))
- Update Mailgun transport type by @driesvints ([#41255](https://github.com/laravel/framework/pull/41255))

## [v9.2.0 (2022-02-22)](https://github.com/laravel/framework/compare/v9.1.0...v9.2.0)

### Added

- Added `Illuminate/Database/Eloquent/Casts/Attribute::make()` ([#41014](https://github.com/laravel/framework/pull/41014))
- Added `Illuminate/Collections/Arr::keyBy()` ([#41029](https://github.com/laravel/framework/pull/41029))
- Added expectsOutputToContain to the PendingCommand. ([#40984](https://github.com/laravel/framework/pull/40984))
- Added ability to supply HTTP client methods with JsonSerializable instances ([#41055](https://github.com/laravel/framework/pull/41055))
- Added `Illuminate/Filesystem/AwsS3V3Adapter::getClient()` ([#41079](https://github.com/laravel/framework/pull/41079))
- Added Support for enum in Builder::whereRelation ([#41091](https://github.com/laravel/framework/pull/41091))
- Added X headers when using Mail::alwaysTo ([#41101](https://github.com/laravel/framework/pull/41101))
- Added of support Bitwise operators in query ([#41112](https://github.com/laravel/framework/pull/41112))
- Integrate Laravel CORS into framework ([#41137](https://github.com/laravel/framework/pull/41137))
- Added `Illuminate/Support/Str::betweenFirst()` ([#41144](https://github.com/laravel/framework/pull/41144))
- Allow specifiying custom messages for Rule objects ([#41145](https://github.com/laravel/framework/pull/41145))

### Fixed

- Fixed Queue Failed_jobs insert issue with Exception contain UNICODE ([#41020](https://github.com/laravel/framework/pull/41020))
- Fixes attempt to log deprecations on mocks ([#41057](https://github.com/laravel/framework/pull/41057))
- Fixed loadAggregate not correctly applying casts ([#41050](https://github.com/laravel/framework/pull/41050))
- Do not transform JsonSerializable instances to array in HTTP client methods ([#41077](https://github.com/laravel/framework/pull/41077))
- Fix parsing config('database.connections.pgsql.search_path') ([#41088](https://github.com/laravel/framework/pull/41088))
- Eloquent: firstWhere returns Object instead of NULL ([#41099](https://github.com/laravel/framework/pull/41099))
- Fixed updated with provided qualified updated_at ([#41133](https://github.com/laravel/framework/pull/41133))
- Fix setPriority Call for MailChannel ([#41120](https://github.com/laravel/framework/pull/41120))
- Fixed route:list command output ([#41177](https://github.com/laravel/framework/pull/41177))
- Fix database migrations $connection property ([#41161](https://github.com/laravel/framework/pull/41161))

### Changed

- Cursor pagination: convert original column to expression ([#41003](https://github.com/laravel/framework/pull/41003))
- Cast $perPage to integer on Paginator ([#41073](https://github.com/laravel/framework/pull/41073))
- Restore S3 client extra options ([#41097](https://github.com/laravel/framework/pull/41097))
- Use `latest()` within `notifications()` in `Illuminate/Notifications/HasDatabaseNotifications.php` ([#41095](https://github.com/laravel/framework/pull/41095))
- Remove duplicate queries to find batch ([#41121](https://github.com/laravel/framework/pull/41121))
- Remove redundant check in FormRequest::validated() ([#41115](https://github.com/laravel/framework/pull/41115))
- Illuminate/Support/Facades/Storage::fake() changed ([#41113](https://github.com/laravel/framework/pull/41113))
- Use coalesce equal as provided by PHP >= 7.4 ([#41174](https://github.com/laravel/framework/pull/41174))
- Simplify some conditions with is_countable() ([#41168](https://github.com/laravel/framework/pull/41168))
- Pass AWS temporary URL options to createPresignedRequest method ([#41156](https://github.com/laravel/framework/pull/41156))
- Let Multiple* exceptions hold the number of records and items found ([#41164](https://github.com/laravel/framework/pull/41164))

## [v9.1.0 (2022-02-15)](https://github.com/laravel/framework/compare/v9.0.2...v9.1.0)

### Added

- Added the ability to use the uniqueFor method for Jobs by @andrey-helldar in https://github.com/laravel/framework/pull/40974
- Add filtering of route:list by domain by @Synchro in https://github.com/laravel/framework/pull/40970
- Added dropForeignIdFor method to match foreignIdFor method by @bretto36 in https://github.com/laravel/framework/pull/40950
- Adds `Str::excerpt` by @nunomaduro in https://github.com/laravel/framework/pull/41000
- Make:model --morph flag to generate MorphPivot model by @michael-rubel in https://github.com/laravel/framework/pull/41011
- Add doesntContain to higher order proxies by @edemots in https://github.com/laravel/framework/pull/41034

### Changed

- Improve types on model factory methods by @axlon in https://github.com/laravel/framework/pull/40902
- Add support for passing array as the second parameter for the group method. by @hossein-zare in https://github.com/laravel/framework/pull/40945
- Makes `ExceptionHandler::renderForConsole` internal on contract by @nunomaduro in https://github.com/laravel/framework/pull/40956
- Put the error message at the bottom of the exceptions by @nshiro in https://github.com/laravel/framework/pull/40886
- Expose next and previous cursor of cursor paginator by @gdebrauwer in https://github.com/laravel/framework/pull/41001

### Fixed

- Fix FTP root config by @driesvints in https://github.com/laravel/framework/pull/40939
- Allows tls encryption to be used with port different than 465 with starttls by @nicolalazzaro in https://github.com/laravel/framework/pull/40943
- Catch suppressed deprecation logs by @nunomaduro in https://github.com/laravel/framework/pull/40942
- Fix typo in method documentation by @shadman-ahmed in https://github.com/laravel/framework/pull/40951
- Patch regex rule parsing due to `Rule::forEach()` by @stevebauman in https://github.com/laravel/framework/pull/40941
- Fix replacing request options by @driesvints in https://github.com/laravel/framework/pull/40954
- Fix `MessageSent` event by @driesvints in https://github.com/laravel/framework/pull/40963
- Add firstOr() function to BelongsToMany relation by @r-kujawa in https://github.com/laravel/framework/pull/40828
- Fix `isRelation()` failing to check an `Attribute` by @rodrigopedra in https://github.com/laravel/framework/pull/40967
- Fix default pivot attributes by @iamgergo in https://github.com/laravel/framework/pull/40947
- Fix enum casts arrayable behaviour by @diegotibi in https://github.com/laravel/framework/pull/40885
- Solve exception error: Undefined array key "", in artisan route:list command by @manuglopez in https://github.com/laravel/framework/pull/41031
- Fix Duplicate Route Namespace by @moisish in https://github.com/laravel/framework/pull/41021
- Fix the error message when no routes are detected by @LukeTowers in https://github.com/laravel/framework/pull/41017
- Fix mails with tags and metadata are not queuable by @joostdebruijn in https://github.com/laravel/framework/pull/41028

## [v9.0.2 (2022-02-10)](https://github.com/laravel/framework/compare/v9.0.1...v9.0.2)

### Added

- Add disabled directive by @belzaaron in https://github.com/laravel/framework/pull/40900

### Changed

- Widen the type of `Collection::unique` `$key` parameter by @NiclasvanEyk in https://github.com/laravel/framework/pull/40903
- Makes `ExceptionHandler::renderForConsole` internal by @nunomaduro in https://github.com/laravel/framework/pull/40936
- Removal of Google Font integration from default exception templates by @bashgeek in https://github.com/laravel/framework/pull/40926
- Allow base JsonResource class to be collected by @jwohlfert23 in https://github.com/laravel/framework/pull/40896

### Fixed

- Fix Support\Collection reject method type definition by @joecampo in https://github.com/laravel/framework/pull/40899
- Fix SpoofCheckValidation namespace change by @eduardor2k in https://github.com/laravel/framework/pull/40923
- Fix notification email recipient by @driesvints in https://github.com/laravel/framework/pull/40921
- Fix publishing visibility by @driesvints in https://github.com/laravel/framework/pull/40918
- Fix Mailable->priority() by @giggsey in https://github.com/laravel/framework/pull/40917

## [v9.0.1 (2022-02-09)](https://github.com/laravel/framework/compare/v9.0.0...v9.0.1)

### Changed

- Improves `Support\Collection` each method type definition by @zingimmick in https://github.com/laravel/framework/pull/40879

### Fixed

- Update Mailable.php by @rentalhost in https://github.com/laravel/framework/pull/40868
- Switch to null coalescing operator in Conditionable by @inxilpro in https://github.com/laravel/framework/pull/40888
- Bring back old return behaviour by @ankurk91 in https://github.com/laravel/framework/pull/40880

## [v9.0.0 (2022-02-08)](https://github.com/laravel/framework/compare/8.x...v9.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/9.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/9.x/releases).
