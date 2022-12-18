# Release Notes for 8.x

## [Unreleased](https://github.com/laravel/framework/compare/v8.83.27...8.x)


## [v8.83.27 (2022-12-08)](https://github.com/laravel/framework/compare/v8.83.26...v8.83.27)

### Fixed
- Fixed email verification request ([#45227](https://github.com/laravel/framework/pull/45227))


## [v8.83.26 (2022-11-01)](https://github.com/laravel/framework/compare/v8.83.25...v8.83.26)

### Fixed
- Fixes controller computed middleware ([#44454](https://github.com/laravel/framework/pull/44454))


## [v8.83.25 (2022-09-30)](https://github.com/laravel/framework/compare/v8.83.24...v8.83.25)

### Added
- Added `Illuminate/Routing/Route::flushController()` ([#44393](https://github.com/laravel/framework/pull/44393))


## [v8.83.24 (2022-09-22)](https://github.com/laravel/framework/compare/v8.83.23...v8.83.24)

### Fixed
- Avoid Passing null to parameter exception on PHP 8.1 ([#43951](https://github.com/laravel/framework/pull/43951))

### Changed
- Patch for timeless timing attack vulnerability in user login ([#44069](https://github.com/laravel/framework/pull/44069))


## [v8.83.23 (2022-07-26)](https://github.com/laravel/framework/compare/v8.83.22...v8.83.23)

### Fixed
- Fix DynamoDB locks with 0 seconds duration ([#43365](https://github.com/laravel/framework/pull/43365))


## [v8.83.22 (2022-07-22)](https://github.com/laravel/framework/compare/v8.83.21...v8.83.22)

### Revert
- Revert ["Protect against ambiguous columns"](https://github.com/laravel/framework/pull/43278) ([#43362](https://github.com/laravel/framework/pull/43362))


## [v8.83.21 (2022-07-21)](https://github.com/laravel/framework/compare/v8.83.20...v8.83.21)

### Revert
- Revert of ["Prevent double throwing chained exception on sync queue"](https://github.com/laravel/framework/pull/42950) ([#43354](https://github.com/laravel/framework/pull/43354))


## [v8.83.20 (2022-07-19)](https://github.com/laravel/framework/compare/v8.83.19...v8.83.20)

### Fixed
- Fixed transaction attempts counter for sqlsrv ([#43176](https://github.com/laravel/framework/pull/43176))

### Changed
- Clear Facade resolvedInstances in queue worker resetScope callback ([#43215](https://github.com/laravel/framework/pull/43215))
- Protect against ambiguous columns ([#43278](https://github.com/laravel/framework/pull/43278))


## [v8.83.19 (2022-07-13)](https://github.com/laravel/framework/compare/v8.83.18...v8.83.19)

### Fixed
- Fixed forceCreate on MorphMany not returning newly created object ([#42996](https://github.com/laravel/framework/pull/42996))
- Prevent double throwing chained exception on sync queue ([#42950](https://github.com/laravel/framework/pull/42950))

### Changed
- Disable Column Statistics for php artisan schema:dump on MariaDB ([#43027](https://github.com/laravel/framework/pull/43027))


## [v8.83.18 (2022-06-28)](https://github.com/laravel/framework/compare/v8.83.17...v8.83.18)

### Fixed
- Fixed bug on forceCreate on a MorphMay relationship not including morph type ([#42929](https://github.com/laravel/framework/pull/42929))
- Handle cursor paginator when no items are found ([#42963](https://github.com/laravel/framework/pull/42963))
- Fixed Str::Mask() for repeating chars ([#42956](https://github.com/laravel/framework/pull/42956))


## [v8.83.17 (2022-06-21)](https://github.com/laravel/framework/compare/v8.83.16...v8.83.17)

### Added
- Apply where's from union query builder in cursor pagination ([#42651](https://github.com/laravel/framework/pull/42651))
- Handle collection creation around a single enum ([#42839](https://github.com/laravel/framework/pull/42839))

### Fixed
- Fixed Request offsetExists without routeResolver ([#42754](https://github.com/laravel/framework/pull/42754))
- Fixed: Loose comparison causes the value not to be saved ([#42793](https://github.com/laravel/framework/pull/42793))


## [v8.83.16 (2022-06-07)](https://github.com/laravel/framework/compare/v8.83.15...v8.83.16)

### Fixed
- Free reserved memory before handling fatal errors ([#42630](https://github.com/laravel/framework/pull/42630), [#42646](https://github.com/laravel/framework/pull/42646))
- Prevent $mailer being reset when testing mailables that implement ShouldQueue ([#42695](https://github.com/laravel/framework/pull/42695))


## [v8.83.15 (2022-05-31)](https://github.com/laravel/framework/compare/v8.83.14...v8.83.15)

### Reverted
- Revert digits changes in Validator ([c6d1a2d](https://github.com/laravel/framework/commit/c6d1a2da17e3aaaeb0ff5b8cc4879816d214b527), [#42562](https://github.com/laravel/framework/pull/42562))

### Changed
- Retain the original attribute value during validation of an array key with a dot for correct failure message ([#42395](https://github.com/laravel/framework/pull/42395))


## [v8.83.14 (2022-05-24)](https://github.com/laravel/framework/compare/v8.83.13...v8.83.14)

### Fixed
- Add flush handler to output buffer for streamed test response (bugfix) ([#42481](https://github.com/laravel/framework/pull/42481))

### Changed
- Use duplicate instead of createFromBase to clone request when routes are cached ([#42420](https://github.com/laravel/framework/pull/42420))


## [v8.83.13 (2022-05-17)](https://github.com/laravel/framework/compare/v8.83.12...v8.83.13)

### Fixed
- Fix PruneCommand finding its usage within other traits ([#42350](https://github.com/laravel/framework/pull/42350))

### Changed
- Consistency between digits and digits_between validation rules ([#42358](https://github.com/laravel/framework/pull/42358))
- Corrects the use of "failed_jobs" instead of "job_batches" in BatchedTableCommand ([#42389](https://github.com/laravel/framework/pull/42389))


## [v8.83.12 (2022-05-10)](https://github.com/laravel/framework/compare/v8.83.11...v8.83.12)

### Fixed
- Fixed multiple dots for digits_between rule ([#42330](https://github.com/laravel/framework/pull/42330))

### Changed
- Enable to modify HTTP Client request headers when using beforeSending() callback ([#42244](https://github.com/laravel/framework/pull/42244))
- Set relation parent key when using forceCreate on HasOne and HasMany relations ([#42281](https://github.com/laravel/framework/pull/42281))


## [v8.83.11 (2022-05-03)](https://github.com/laravel/framework/compare/v8.83.10...v8.83.11)

### Fixed
- Fix refresh during down in the stub ([#42217](https://github.com/laravel/framework/pull/42217))
- Fix deprecation issue with translator ([#42216](https://github.com/laravel/framework/pull/42216))


## [v8.83.10 (2022-04-27)](https://github.com/laravel/framework/compare/v8.83.9...v8.83.10)

### Fixed
- Fix schedule:work command Artisan binary name ([#42083](https://github.com/laravel/framework/pull/42083))
- Fix array keys from cached routes in Illuminate/Routing/CompiledRouteCollection::getRoutesByMethod() ([#42078](https://github.com/laravel/framework/pull/42078))
- Fix json_last_error issue with Illuminate/Http/JsonResponse::setData ([#42125](https://github.com/laravel/framework/pull/42125))


## [v8.83.9 (2022-04-19)](https://github.com/laravel/framework/compare/v8.83.8...v8.83.9)

### Fixed
- Backport Fix PHP warnings when rendering long blade string ([#41970](https://github.com/laravel/framework/pull/41970))


## [v8.83.8 (2022-04-12)](https://github.com/laravel/framework/compare/v8.83.7...v8.83.8)

### Added
- Added multibyte support to string padding helper functions ([#41899](https://github.com/laravel/framework/pull/41899))

### Fixed
- Fixed seeder property for in-memory tests ([#41869](https://github.com/laravel/framework/pull/41869))


## [v8.83.7 (2022-04-05)](https://github.com/laravel/framework/compare/v8.83.6...v8.83.7)

### Fixed
- Backport - Fix trashed implicitBinding with child with no softdelete ([#41814](https://github.com/laravel/framework/pull/41814))
- Fix assertListening check with auto discovery ([#41820](https://github.com/laravel/framework/pull/41820))


## [v8.83.6 (2022-03-29)](https://github.com/laravel/framework/compare/v8.83.5...v8.83.6)

### Fixed
- Stop throwing LazyLoadingViolationException for recently created model instances ([#41549](https://github.com/laravel/framework/pull/41549))
- Close doctrineConnection on disconnect ([#41584](https://github.com/laravel/framework/pull/41584))
- Fix require fails if is_file cached by opcache ([#41614](https://github.com/laravel/framework/pull/41614))
- Fix collection nth where step <= offset ([#41645](https://github.com/laravel/framework/pull/41645))


## [v8.83.5 (2022-03-15)](https://github.com/laravel/framework/compare/v8.83.4...v8.83.5)

### Fixed
- Backport dynamically access batch options ([#41361](https://github.com/laravel/framework/pull/41361))
- Fixed get and head options in Illuminate/Http/Client/PendingRequest.php ([23ff879](https://github.com/laravel/framework/commit/23ff879c6e5c6c6424b09a8b38c1686a9c89c4a5))


## [v8.83.4 (2022-03-08)](https://github.com/laravel/framework/compare/v8.83.3...v8.83.4)

### Added
- Added `Illuminate/Bus/Batch::__get()` ([#41361](https://github.com/laravel/framework/pull/41361))

### Fixed
- Fixed get and head options in `Illuminate/Http/Client/PendingRequest` ([23ff879](https://github.com/laravel/framework/commit/23ff879c6e5c6c6424b09a8b38c1686a9c89c4a5))


## [v8.83.3 (2022-03-03)](https://github.com/laravel/framework/compare/v8.83.2...v8.83.3)

### Fixed
* $job can be an object in some methods by @villfa in https://github.com/laravel/framework/pull/41244
* Fixes getting the trusted proxies IPs from the configuration file by @nunomaduro in https://github.com/laravel/framework/pull/41322


## [v8.83.2 (2022-02-22)](https://github.com/laravel/framework/compare/v8.83.1...v8.83.2)

### Added
- Added support of Bitwise opperators in query ([#41112](https://github.com/laravel/framework/pull/41112))

### Fixed
- Fixes attempt to log deprecations on mocks ([#41057](https://github.com/laravel/framework/pull/41057))
- Fixed loadAggregate not correctly applying casts ([#41108](https://github.com/laravel/framework/pull/41108))
- Fixed updated with provided qualified updated_at ([#41133](https://github.com/laravel/framework/pull/41133))
- Fixed database migrations $connection property ([#41161](https://github.com/laravel/framework/pull/41161))


## [v8.83.1 (2022-02-15)](https://github.com/laravel/framework/compare/v8.83.0...v8.83.1)

### Added
- Add firstOr() function to BelongsToMany relation ([#40828](https://github.com/laravel/framework/pull/40828))
- Catch suppressed deprecation logs ([#40942](https://github.com/laravel/framework/pull/40942))
- Add doesntContain to higher order proxies ([#41034](https://github.com/laravel/framework/pull/41034))

### Fixed
- Fix replacing request options ([#40954](https://github.com/laravel/framework/pull/40954), [30e341b](https://github.com/laravel/framework/commit/30e341b7fe4e4d9019df42b7eff6c7dfa5ea30e5))
- Fix isRelation() failing to check an Attribute ([#40967](https://github.com/laravel/framework/pull/40967))
- Fix enum casts arrayable behaviour ([#40999](https://github.com/laravel/framework/pull/40999))


## [v8.83.0 (2022-02-08)](https://github.com/laravel/framework/compare/v8.82.0...v8.83.0)

### Added
* Add isolation level configuration for Postgres connector by @rezaamini-ir in https://github.com/laravel/framework/pull/40767
* Add a string helper to swap multiple keywords in a string by @amitmerchant1990 in https://github.com/laravel/framework/pull/40831 & https://github.com/laravel/framework/commit/220f4ac11d462b4ee9ff2cb9b48b93d6f560223a

### Changed
* Make `PendingRequest` `Conditionable` by @phillipfickl in https://github.com/laravel/framework/pull/40762
* Add a BladeCompiler::renderComponent() method to render a component instance by @tobyzerner in https://github.com/laravel/framework/pull/40745
* Doc block tweaks in `BladeCompiler.php` by @JayBizzle in https://github.com/laravel/framework/pull/40772
* Revert Bit operators by @driesvints in https://github.com/laravel/framework/pull/40791
* Improves `Support\Reflector` to support checking interfaces by @hassanhe in https://github.com/laravel/framework/pull/40822
* Support cursor pagination with union query by @deleugpn in https://github.com/laravel/framework/pull/40848
* Consistent `Stringable::swap()` & `Str::swap()` implementations by @derekmd in https://github.com/laravel/framework/pull/40855

### Fixed
* Do not set SYSTEMROOT to false by @Galaxy0419 in https://github.com/laravel/framework/pull/40819


## [v8.82.0 (2022-02-01)](https://github.com/laravel/framework/compare/v8.81.0...v8.82.0)

### Added
- Added class and method to create cross joined sequences for factories ([#40542](https://github.com/laravel/framework/pull/40542))
- Added Transliterate shortcut to the Str helper ([#40681](https://github.com/laravel/framework/pull/40681))
- Added array_keys validation rule ([#40720](https://github.com/laravel/framework/pull/40720))

### Fixed
- Prevent job serialization error in Queue ([#40625](https://github.com/laravel/framework/pull/40625))
- Fixed autoresolving model name from factory ([#40616](https://github.com/laravel/framework/pull/40616))
- Fixed : strtotime Epoch doesn't fit in PHP int ([#40690](https://github.com/laravel/framework/pull/40690))
- Fixed Stringable ucsplit ([#40694](https://github.com/laravel/framework/pull/40694), [#40699](https://github.com/laravel/framework/pull/40699))

### Changed
- Server command: Allow xdebug auto-connect to listener feature ([#40673](https://github.com/laravel/framework/pull/40673))
- respect null driver in `QueueServiceProvider` ([9435827](https://github.com/laravel/framework/commit/9435827014ca289213f2bcf64847f5c5959bb652), [56d433a](https://github.com/laravel/framework/commit/56d433aaec40e8383f28e8f0e835cd977845fcde))
- Allow to push and prepend config values on new keys ([#40723](https://github.com/laravel/framework/pull/40723))


## [v8.81.0 (2022-01-25)](https://github.com/laravel/framework/compare/v8.80.0...v8.81.0)

### Added
- Added `Illuminate/Support/Stringable::scan()` ([#40472](https://github.com/laravel/framework/pull/40472))
- Allow caching to be disabled for virtual attributes accessors that return an object ([#40519](https://github.com/laravel/framework/pull/40519))
- Added better bitwise operators support ([#40529](https://github.com/laravel/framework/pull/40529), [def671d](https://github.com/laravel/framework/commit/def671d4902d9cdd315aee8249199b45fcc2186b))
- Added getOrPut on Collection ([#40535](https://github.com/laravel/framework/pull/40535))
- Improve PhpRedis flushing ([#40544](https://github.com/laravel/framework/pull/40544))
- Added `Illuminate/Support/Str::flushCache()` ([#40620](https://github.com/laravel/framework/pull/40620))

### Fixed
- Fixed Str::headline/Str::studly with unicode and add Str::ucsplit method ([#40499](https://github.com/laravel/framework/pull/40499))
- Fixed forgetMailers with MailFake ([#40495](https://github.com/laravel/framework/pull/40495))
- Pruning Models: Get the default path for the models from a method instead ([#40539](https://github.com/laravel/framework/pull/40539))
- Fix flushdb for predis cluste ([#40446](https://github.com/laravel/framework/pull/40446))
- Avoid undefined array key 0 error ([#40571](https://github.com/laravel/framework/pull/40571))

### Changed
- Allow whitespace in PDO dbname for PostgreSQL ([#40483](https://github.com/laravel/framework/pull/40483))
- Allows authorizeResource method to receive arrays of models and parameters ([#40516](https://github.com/laravel/framework/pull/40516))
- Inverse morphable type and id filter statements to prevent SQL errors ([#40523](https://github.com/laravel/framework/pull/40523))
- Bump voku/portable-ascii to v1.6.1 ([#40588](https://github.com/laravel/framework/pull/40588), [#40610](https://github.com/laravel/framework/pull/40610))


## [v8.80.0 (2022-01-18)](https://github.com/laravel/framework/compare/v8.79.0...v8.80.0)

### Added
- Allow enums as entity_type in morphs ([#40375](https://github.com/laravel/framework/pull/40375))
- Added support for specifying a route group controller ([#40276](https://github.com/laravel/framework/pull/40276))
- Added phpredis serialization and compression config support ([#40282](https://github.com/laravel/framework/pull/40282))
- Added a BladeCompiler::render() method to render a string with Blade ([#40425](https://github.com/laravel/framework/pull/40425))
- Added a method to sort keys in a collection using a callback ([#40458](https://github.com/laravel/framework/pull/40458))

### Changed
- Convert "/" in -e parameter to "\" in `Illuminate/Foundation/Console/ListenerMakeCommand` ([#40383](https://github.com/laravel/framework/pull/40383))

### Fixed
- Throws an error upon make:policy if no model class is configured ([#40348](https://github.com/laravel/framework/pull/40348))
- Fix forwarded call with named arguments in `Illuminate/Filesystem/FilesystemAdapter` ([#40421](https://github.com/laravel/framework/pull/40421))
- Fix 'strstr' function usage based on its signature ([#40457](https://github.com/laravel/framework/pull/40457))


## [v8.79.0 (2022-01-12)](https://github.com/laravel/framework/compare/v8.78.1...v8.79.0)

### Added
- Added onLastPage method to the Paginator ([#40265](https://github.com/laravel/framework/pull/40265))
- Allow method typed variadics dependencies ([#40255](https://github.com/laravel/framework/pull/40255))
- Added `ably/ably-php` to composer.json to suggest ([#40277](https://github.com/laravel/framework/pull/40277))
- Implement Full-Text Search for MySQL & PostgreSQL ([#40129](https://github.com/laravel/framework/pull/40129))
- Added whenContains and whenContainsAll to Stringable ([#40285](https://github.com/laravel/framework/pull/40285))
- Support action_level configuration in LogManager ([#40305](https://github.com/laravel/framework/pull/40305))
- Added whenEndsWith(), whenExactly(), whenStartsWith(), etc to Stringable ([#40320](https://github.com/laravel/framework/pull/40320))
- Makes it easy to add additional options to PendingBatch ([#40333](https://github.com/laravel/framework/pull/40333))
- Added method to MigrationsStarted/MigrationEnded events ([#40334](https://github.com/laravel/framework/pull/40334))

### Fixed
- Fixed failover mailer when used with Mailgun & SES mailers ([#40254](https://github.com/laravel/framework/pull/40254))
- Fixed digits_between with fractions ([#40278](https://github.com/laravel/framework/pull/40278))
- Fixed cursor pagination with HasManyThrough ([#40300](https://github.com/laravel/framework/pull/40300))
- Fixed virtual attributes ([29a6692](https://github.com/laravel/framework/commit/29a6692fb0f0d14e5109ae5f02ed70065f10e966))
- Fixed timezone option in `schedule:list` command ([#40304](https://github.com/laravel/framework/pull/40304))
- Fixed Doctrine type mappings creating too many connections ([#40303](https://github.com/laravel/framework/pull/40303))
- Fixed of resolving Blueprint class out of the container ([#40307](https://github.com/laravel/framework/pull/40307))
- Handle type mismatch in the enum validation rule ([#40362](https://github.com/laravel/framework/pull/40362))

### Changed
- Automatically add event description when scheduling a command ([#40286](https://github.com/laravel/framework/pull/40286))
- Update the Pluralizer Inflector instanciator ([#40336](https://github.com/laravel/framework/pull/40336))


## [v8.78.1 (2022-01-05)](https://github.com/laravel/framework/compare/v8.78.0...v8.78.1)

### Added
- Added pipeThrough collection method ([#40253](https://github.com/laravel/framework/pull/40253))

### Changed
- Run clearstatcache after deleting file and asserting Storage using exists/missing ([#40257](https://github.com/laravel/framework/pull/40257))
- Avoid constructor call when fetching resource JSON options ([#40261](https://github.com/laravel/framework/pull/40261))


## [v8.78.0 (2022-01-04)](https://github.com/laravel/framework/compare/v8.77.1...v8.78.0)

### Added
- Added `schedule:clear-mutex` command ([#40135](https://github.com/laravel/framework/pull/40135))
- Added ability to define extra default password rules ([#40137](https://github.com/laravel/framework/pull/40137))
- Added a `mergeIfMissing` method to the Illuminate Http Request class ([#40116](https://github.com/laravel/framework/pull/40116))
- Added `Illuminate/Support/MultipleInstanceManager` ([40913ac](https://github.com/laravel/framework/commit/40913ac8f8d07cca08c10ea7b4adc6c45b700b10))
- Added `SimpleMessage::lines()` ([#40147](https://github.com/laravel/framework/pull/40147))
- Added `Illuminate/Support/Testing/Fakes/BusFake::assertBatchCount()` ([#40217](https://github.com/laravel/framework/pull/40217))
- Enable only-to-others functionality when using Ably broadcast driver ([#40234](https://github.com/laravel/framework/pull/40234))
- Added ability to customize json options on JsonResource response ([#40208](https://github.com/laravel/framework/pull/40208))
- Added `Illuminate/Support/Stringable::toHtmlString()` ([#40247](https://github.com/laravel/framework/pull/40247))

### Changed
- Improve support for custom Doctrine column types ([#40119](https://github.com/laravel/framework/pull/40119))
- Remove an useless check in Console Application class ([#40145](https://github.com/laravel/framework/pull/40145))
- Sort collections by key when first element of sort operation is string (even if callable) ([#40212](https://github.com/laravel/framework/pull/40212))
- Use first host if multiple in `Illuminate/Database/Console/DbCommand::getConnection()` ([#40226](https://github.com/laravel/framework/pull/40226))
- Improvement in the Reflector class ([#40241](https://github.com/laravel/framework/pull/40241))

### Fixed
- Clear recorded calls when calling Http::fake() ([#40194](https://github.com/laravel/framework/pull/40194))
- Fixed attribute casting ([#40245](https://github.com/laravel/framework/pull/40245), [c0d9735](https://github.com/laravel/framework/commit/c0d97352c46ade8cc254b473580b2655ed474ffc))


## [v8.77.1 (2021-12-21)](https://github.com/laravel/framework/compare/v8.77.0...v8.77.1)

### Fixed
- Fixed prune command with default options ([#40127](https://github.com/laravel/framework/pull/40127))


## [v8.77.0 (2021-12-21)](https://github.com/laravel/framework/compare/v8.76.2...v8.77.0)

### Added
- Attribute Cast / Accessor Improvements ([#40022](https://github.com/laravel/framework/pull/40022))
- Added `Illuminate/View/Factory::renderUnless()` ([#40077](https://github.com/laravel/framework/pull/40077))
- Added datetime parsing to Request instance ([#39945](https://github.com/laravel/framework/pull/39945))
- Make it possible to use prefixes on Predis per Connection ([#40083](https://github.com/laravel/framework/pull/40083))
- Added rule to validate MAC address ([#40098](https://github.com/laravel/framework/pull/40098))
- Added ability to define temporary URL macro for storage ([#40100](https://github.com/laravel/framework/pull/40100))

### Fixed
- Fixed possible out of memory error when deleting values by reference key from cache in Redis driver ([#40039](https://github.com/laravel/framework/pull/40039))
- Added `Illuminate/Filesystem/FilesystemManager::setApplication()` ([#40058](https://github.com/laravel/framework/pull/40058))
- Fixed arg passing in doesntContain ([739d847](https://github.com/laravel/framework/commit/739d8472eb2c97c4a8d8f86eb699c526e42f57fa))
- Translate Enum rule message ([#40089](https://github.com/laravel/framework/pull/40089))
- Fixed date validation ([#40088](https://github.com/laravel/framework/pull/40088))
- Dont allow models and except together in PruneCommand.php ([f62fe66](https://github.com/laravel/framework/commit/f62fe66216ee11bc864e0877d4fd4be4655db4aa))

### Changed
- Passthru Eloquent\Query::explain function to Query\Builder:explain for the ability to use database-specific explain commands ([#40075](https://github.com/laravel/framework/pull/40075))


## [v8.76.2 (2021-12-15)](https://github.com/laravel/framework/compare/v8.76.1...v8.76.2)

### Added
- Added doesntContain method to Collection and LazyCollection ([#40044](https://github.com/laravel/framework/pull/40044), [3e3cbcf](https://github.com/laravel/framework/commit/3e3cbcf4cb4b8116f504a1e8363c7c958067b49a))

### Reverted
- Reverted ["Revert "[8.x] Remove redundant description & localize template"](https://github.com/laravel/framework/pull/39928) ([#40054](https://github.com/laravel/framework/pull/40054))


## [v8.76.1 (2021-12-14)](https://github.com/laravel/framework/compare/v8.76.0...v8.76.1)

### Reverted
- Reverted ["Fixed possible out of memory error when deleting values by reference key from cache in Redis driver"](https://github.com/laravel/framework/pull/39939) ([#40040](https://github.com/laravel/framework/pull/40040))


## [v8.76.0 (2021-12-14)](https://github.com/laravel/framework/compare/v8.75.0...v8.76.0)

### Added
- Added possibility to customize child model route binding resolution ([#39929](https://github.com/laravel/framework/pull/39929))
- Added Illuminate/Http/Client/Response::reason() ([#39972](https://github.com/laravel/framework/pull/39972))
- Added an afterRefreshingDatabase test method ([#39978](https://github.com/laravel/framework/pull/39978))
- Added unauthorized() and forbidden() to Illuminate/Http/Client/Response ([#39979](https://github.com/laravel/framework/pull/39979))
- Publish view-component.stub in stub:publish command ([#40007](https://github.com/laravel/framework/pull/40007))
- Added invisible modifier for MySQL columns ([#40002](https://github.com/laravel/framework/pull/40002))
- Added Str::substrReplace() and Str::of($string)->substrReplace() methods ([#39988](https://github.com/laravel/framework/pull/39988))

### Fixed
- Fixed parent call in view ([#39909](https://github.com/laravel/framework/pull/39909))
- Fixed request dump and dd methods ([#39931](https://github.com/laravel/framework/pull/39931))
- Fixed php 8.1 deprecation in ValidatesAttributes::checkDateTimeOrder ([#39937](https://github.com/laravel/framework/pull/39937))
- Fixed withTrashed on routes check if SoftDeletes is used in Model ([#39958](https://github.com/laravel/framework/pull/39958))
- Fixes model:prune --pretend command for models with SoftDeletes ([#39991](https://github.com/laravel/framework/pull/39991))
- Fixed SoftDeletes force deletion sets "exists" property to false only when deletion succeeded ([#39987](https://github.com/laravel/framework/pull/39987))
- Fixed possible out of memory error when deleting values by reference key from cache in Redis driver ([#39939](https://github.com/laravel/framework/pull/39939))
- Fixed Password validation failure to allow errors after min rule ([#40030](https://github.com/laravel/framework/pull/40030))

### Changed
- Fail enum validation with pure enums ([#39926](https://github.com/laravel/framework/pull/39926))
- Remove redundant description & localize template ([#39928](https://github.com/laravel/framework/pull/39928))
- Fixes reporting deprecations when logger is not ready yet ([#39938](https://github.com/laravel/framework/pull/39938))
- Replace escaped dot with place holder in dependent rules parameters ([#39935](https://github.com/laravel/framework/pull/39935))
- passthru from property to underlying query object ([127334a](https://github.com/laravel/framework/commit/127334acbcb8bb012a4831c9fc17bc520c20e320))


## [v8.75.0 (2021-12-07)](https://github.com/laravel/framework/compare/v8.74.0...v8.75.0)

### Added
- Added `Illuminate/Support/Testing/Fakes/NotificationFake::assertSentTimes()` ([667cca8](https://github.com/laravel/framework/commit/667cca8db300f55cd8fccd575eaa46f5156b0408))
- Added Conditionable trait to ComponentAttributeBag ([#39861](https://github.com/laravel/framework/pull/39861))
- Added scheduler integration tests ([#39862](https://github.com/laravel/framework/pull/39862))
- Added on-demand gate authorization ([#39789](https://github.com/laravel/framework/pull/39789))
- Added countable interface to eloquent factory sequence ([#39907](https://github.com/laravel/framework/pull/39907), [1638472a](https://github.com/laravel/framework/commit/1638472a7a5ee02dc9e808bc203b733785ac1468), [#39915](https://github.com/laravel/framework/pull/39915))
- Added Fulltext index for PostgreSQL ([#39875](https://github.com/laravel/framework/pull/39875))
- Added method filterNulls() to Arr ([#39921](https://github.com/laravel/framework/pull/39921))

### Fixed
- Fixes AsEncrypted traits not respecting nullable columns ([#39848](https://github.com/laravel/framework/pull/39848), [4c32bf8](https://github.com/laravel/framework/commit/4c32bf815c93fe6fb6f78f1f9771e6baac379bd6))
- Fixed http client factory class exists bugfix ([#39851](https://github.com/laravel/framework/pull/39851))
- Fixed calls to Connection::rollBack() with incorrect case ([#39874](https://github.com/laravel/framework/pull/39874))
- Fixed bug where columns would be guarded while filling Eloquent models during unit tests ([#39880](https://github.com/laravel/framework/pull/39880))
- Fixed for dropping columns when using MSSQL as database ([#39905](https://github.com/laravel/framework/pull/39905))

### Changed
- Add proper paging offset when possible to sql server ([#39863](https://github.com/laravel/framework/pull/39863))
- Correct pagination message in src/Illuminate/Pagination/resources/views/tailwind.blade.php ([#39894](https://github.com/laravel/framework/pull/39894))


## [v8.74.0 (2021-11-30)](https://github.com/laravel/framework/compare/v8.73.2...v8.74.0)

### Added
- Added optional `except` parameter to the PruneCommand ([#39749](https://github.com/laravel/framework/pull/39749), [be4afcc](https://github.com/laravel/framework/commit/be4afcc6c2a42402d4404263c6a5ca901d067dd2))
- Added `Illuminate/Foundation/Application::hasDebugModeEnabled()` ([#39755](https://github.com/laravel/framework/pull/39755))
- Added `Illuminate/Support/Facades/Event::fakeExcept()` and `Illuminate/Support/Facades/Event::fakeExceptFor()` ([#39752](https://github.com/laravel/framework/pull/39752))
- Added aggregate method to Eloquent passthru ([#39772](https://github.com/laravel/framework/pull/39772))
- Added `undot()` method to Arr helpers and Collections ([#39729](https://github.com/laravel/framework/pull/39729))
- Added `reverse` method to `Str` ([#39816](https://github.com/laravel/framework/pull/39816))
- Added possibility to customize type column in database notifications using databaseType method ([#39811](https://github.com/laravel/framework/pull/39811))
- Added Fulltext Index ([#39821](https://github.com/laravel/framework/pull/39821))

### Fixed
- Fixed bus service provider when loaded outside of the framework ([#39740](https://github.com/laravel/framework/pull/39740))
- Fixes logging deprecations when null driver do not exist ([#39809](https://github.com/laravel/framework/pull/39809))

### Changed
- Validate connection name before resolve queue connection ([#39751](https://github.com/laravel/framework/pull/39751))
- Bump Symfony to 5.4 ([#39827](https://github.com/laravel/framework/pull/39827))
- Optimize the execution time of the unique method ([#39822](https://github.com/laravel/framework/pull/39822))


## [v8.73.2 (2021-11-23)](https://github.com/laravel/framework/compare/v8.73.1...v8.73.2)

### Added
- Added `Illuminate/Foundation/Testing/Concerns/InteractsWithContainer::forgetMock()` ([#39713](https://github.com/laravel/framework/pull/39713))
- Added custom pagination information in resource ([#39600](https://github.com/laravel/framework/pull/39600))


## [v8.73.1 (2021-11-20)](https://github.com/laravel/framework/compare/v8.73.0...v8.73.1)

### Revert
- Revert of [Use parents to resolve middleware priority in `SortedMiddleware`](https://github.com/laravel/framework/pull/39647) ([#39706](https://github.com/laravel/framework/pull/39706))


## [v8.73.0 (2021-11-19)](https://github.com/laravel/framework/compare/v8.72.0...v8.73.0)

### Added
- Added .phar to blocked PHP extensions in validator ([#39666](https://github.com/laravel/framework/pull/39666))
- Allow a Closure to be passed as a ttl in Cache remember() method ([#39678](https://github.com/laravel/framework/pull/39678))
- Added Prohibits validation rule to dependentRules property ([#39677](https://github.com/laravel/framework/pull/39677))
- Implement lazyById in descending order ([#39646](https://github.com/laravel/framework/pull/39646))

### Fixed
- Fixed `Illuminate/Auth/Notifications/ResetPassword::toMail()` ([969f101](https://github.com/laravel/framework/commit/969f1014ec07efba803f887a33fde29e305c9cb1))
- Fixed assertSoftDeleted & assertNotSoftDeleted ([#39673](https://github.com/laravel/framework/pull/39673))


## [v8.72.0 (2021-11-17)](https://github.com/laravel/framework/compare/v8.71.0...v8.72.0)

### Added
- Added extra method in PasswortReset for reset URL to match the structure of VerifyEmail ([#39652](https://github.com/laravel/framework/pull/39652))
- Added support for countables to the `Illuminate/Support/Pluralizer::plural()` ([#39641](https://github.com/laravel/framework/pull/39641))
- Allow users to specify options for migrate:fresh for DatabaseMigration trait ([#39637](https://github.com/laravel/framework/pull/39637))

### Fixed
- Casts $value to the int only when not null in `Illuminate/Database/Query/Builder::limit()` ([#39644](https://github.com/laravel/framework/pull/39644))

### Changed
- Use parents to resolve middleware priority in `SortedMiddleware` ([#39647](https://github.com/laravel/framework/pull/39647))


## [v8.71.0 (2021-11-16)](https://github.com/laravel/framework/compare/v8.70.2...v8.71.0)

### Added
- Added declined and declined_if validation rules ([#39579](https://github.com/laravel/framework/pull/39579))
- Arrayable/collection support for Collection::splice() replacement param ([#39592](https://github.com/laravel/framework/pull/39592))
- Introduce `@js()` directive ([#39522](https://github.com/laravel/framework/pull/39522))
- Enum casts accept backed values ([#39608](https://github.com/laravel/framework/pull/39608))
- Added a method to the Macroable trait that removes all configured macros. ([#39633](https://github.com/laravel/framework/pull/39633))

### Fixed
- Fixed auto-generated Markdown views ([#39565](https://github.com/laravel/framework/pull/39565))
- DB command: Cope with missing driver parameters for mysql ([#39582](https://github.com/laravel/framework/pull/39582))
- Fixed typo in Connection property name in `Illuminate/Database/Connection` ([#39590](https://github.com/laravel/framework/pull/39590))
- Fixed: prevent re-casting of enum values ([#39597](https://github.com/laravel/framework/pull/39597))
- Casts value to the int in `Illuminate/Database/Query/Builder::limit()` ([62273d2](https://github.com/laravel/framework/commit/62273d20dd13b7e35885436d7327be31e3f54b0e))
- Fix $component not being reverted if component doesn't render ([#39595](https://github.com/laravel/framework/pull/39595))

### Changed
- `make:model --all` flag would auto-fire make:controller with --requests ([#39578](https://github.com/laravel/framework/pull/39578))
- Allow assertion of multiple JSON validation errors. ([#39568](https://github.com/laravel/framework/pull/39568))
- Ensure cache directory permissions ([#39591](https://github.com/laravel/framework/pull/39591))
- Update placeholders for stubs ([#39527](https://github.com/laravel/framework/pull/39527))


## [v8.70.2 (2021-11-10)](https://github.com/laravel/framework/compare/v8.70.1...v8.70.2)

### Changed
- Use all in `Illuminate/Database/Query/Builder::cleanBindings()` ([74dcc02](https://github.com/laravel/framework/commit/74dcc024d5ac78a1c7c23a95c493736c2cd8d5a7))


## [v8.70.1 (2021-11-09)](https://github.com/laravel/framework/compare/v8.70.0...v8.70.1)

### Fixed
- Fixed problem with fallback in Router ([5fda5a3](https://github.com/laravel/framework/commit/5fda5a335bce1527e6796a91bb36ccb48d6807a8))


## [v8.70.0 (2021-11-09)](https://github.com/laravel/framework/compare/v8.69.0...v8.70.0)

### Added
- New flag `--requests` `-R` to `make:controller` and `make:model` Commands ([#39120](https://github.com/laravel/framework/pull/39120), [8fbfc9f](https://github.com/laravel/framework/commit/8fbfc9f16e48b202670e4b21588d8d752c3fbe90))
- Allows Stringable objects as middleware. ([#39439](https://github.com/laravel/framework/pull/39439), [#39449](https://github.com/laravel/framework/pull/39449))
- Introduce `Js` for encoding data to use in JavaScript ([#39389](https://github.com/laravel/framework/pull/39389), [#39460](https://github.com/laravel/framework/pull/39460), [bbf47d5](https://github.com/laravel/framework/commit/bbf47d5507c0ff018763170988284eeca6021fe8))
- Added new lost connection error message for sqlsrv ([#39466](https://github.com/laravel/framework/pull/39466))
- Allow can method to be chained onto route for quick authorization ([#39464](https://github.com/laravel/framework/pull/39464))
- Publish `provider.stub` in stub:publish command ([#39491](https://github.com/laravel/framework/pull/39491))
- Added `Illuminate/Support/NamespacedItemResolver::flushParsedKeys()` ([#39490](https://github.com/laravel/framework/pull/39490))
- Accept enums for insert update and where ([#39492](https://github.com/laravel/framework/pull/39492))
- Fifo support for queue name suffix ([#39497](https://github.com/laravel/framework/pull/39497), [12e47bb](https://github.com/laravel/framework/commit/12e47bb3dad10294268fa3167112b198fd0a2036))

### Changed
- Dont cache ondemand loggers ([5afa0f1](https://github.com/laravel/framework/commit/5afa0f1ee680e66360bc05f293eadca0d558f028), [bc50a9b](https://github.com/laravel/framework/commit/bc50a9b10097e66b59f0dfcabc6e100b8fedc760))
- Enforce implicit Route Model scoping ([#39440](https://github.com/laravel/framework/pull/39440))
- Ensure event mutex is always removed ([#39498](https://github.com/laravel/framework/pull/39498))
- Added missing "flags" to redis zadd options list... ([#39538](https://github.com/laravel/framework/pull/39538))


## [v8.69.0 (2021-11-02)](https://github.com/laravel/framework/compare/v8.68.1...v8.69.0)

### Added
- Improve content negotiation for exception handling ([#39385](https://github.com/laravel/framework/pull/39385))
- Added support for SKIP LOCKED to MariaDB ([#39396](https://github.com/laravel/framework/pull/39396))
- Custom cast string into Stringable ([#39410](https://github.com/laravel/framework/pull/39410))
- Added `Illuminate/Support/Str::mask()` ([#39393](https://github.com/laravel/framework/pull/39393))
- Allow model attributes to be casted to/from an Enum ([#39315](https://github.com/laravel/framework/pull/39315))
- Added an Enum validation rule ([#39437](https://github.com/laravel/framework/pull/39437))
- Auth: Allows to use a callback in credentials array ([#39420](https://github.com/laravel/framework/pull/39420))
- Added success and failure command assertions ([#39435](https://github.com/laravel/framework/pull/39435))

### Fixed
- Fixed CURRENT_TIMESTAMP as default when changing column ([#39377](https://github.com/laravel/framework/pull/39377))
- Make accept header comparison case-insensitive ([#39413](https://github.com/laravel/framework/pull/39413))
- Fixed regression with capitalizing translation params ([#39424](https://github.com/laravel/framework/pull/39424))

### Changed
- Added bound check to env resolving in `Illuminate/Foundation/Application::runningUnitTests()` ([#39434](https://github.com/laravel/framework/pull/39434))


## [v8.68.1 (2021-10-27)](https://github.com/laravel/framework/compare/v8.68.0...v8.68.1)

### Reverted
- Reverted ["Added support for MariaDB to skip locked rows with the database queue driver"](https://github.com/laravel/framework/pull/39311) ([#39386](https://github.com/laravel/framework/pull/39386))

### Fixed
- Fixed code to address different connection strings for MariaDB in the database queue driver ([#39374](https://github.com/laravel/framework/pull/39374))
- Fixed rate limiting unicode issue ([#39375](https://github.com/laravel/framework/pull/39375))
- Fixed bug with closure formatting in `Illuminate/Testing/Fluent/Concerns/Matching::whereContains()` ([37217d5](https://github.com/laravel/framework/commit/37217d56ca38c407395bb98ef2532cafd86efa30))

### Refactoring
- Change whereStartsWith, DocBlock to reflect that array is supported ([#39370](https://github.com/laravel/framework/pull/39370))


## [v8.68.0 (2021-10-26)](https://github.com/laravel/framework/compare/v8.67.0...v8.68.0)

### Added
- Added ThrottleRequestsWithRedis to $middlewarePriority ([#39316](https://github.com/laravel/framework/pull/39316))
- Added `Illuminate/Database/Schema/ForeignKeyDefinition::restrictOnUpdate()` ([#39350](https://github.com/laravel/framework/pull/39350))
- Added `ext-bcmath` as an extension suggestion to the composer.json ([#39360](https://github.com/laravel/framework/pull/39360))
- Added `TestResponse::dd` ([#39359](https://github.com/laravel/framework/pull/39359))

### Fixed
- TaggedCache flush should also remove tags from cache ([#39299](https://github.com/laravel/framework/pull/39299))
- Fixed model serialization on anonymous components ([#39319](https://github.com/laravel/framework/pull/39319))

### Changed
- Changed to Guess database factory model by default ([#39310](https://github.com/laravel/framework/pull/39310))


## [v8.67.0 (2021-10-22)](https://github.com/laravel/framework/compare/v8.66.0...v8.67.0)

### Added
- Added support for MariaDB to skip locked rows with the database queue driver ([#39311](https://github.com/laravel/framework/pull/39311))
- Added PHP 8.1 Support ([#39034](https://github.com/laravel/framework/pull/39034))

### Fixed
- Fixed translation bug ([#39298](https://github.com/laravel/framework/pull/39298))
- Fixed Illuminate/Database/DetectsConcurrencyErrors::causedByConcurrencyError() when code is intager ([#39280](https://github.com/laravel/framework/pull/39280))
- Fixed unique bug in Bus ([#39302](https://github.com/laravel/framework/pull/39302))

### Changed
- Only select related columns by default in CanBeOneOfMany::ofMany ([#39307](https://github.com/laravel/framework/pull/39307))


## [v8.66.0 (2021-10-21)](https://github.com/laravel/framework/compare/v8.65.0...v8.66.0)

### Added
- Added withoutDeprecationHandling to testing ([#39261](https://github.com/laravel/framework/pull/39261))
- Added method for on-demand log creation ([#39273](https://github.com/laravel/framework/pull/39273))
- Added dateTime to columns that don't need character options ([#39269](https://github.com/laravel/framework/pull/39269))
- Added `AssertableJson::hasAny` ([#39265](https://github.com/laravel/framework/pull/39265))
- Added `Arr::isList()` method ([#39277](https://github.com/laravel/framework/pull/39277))
- Apply withoutGlobalScope in CanBeOneOfMany subqueries ([#39295](https://github.com/laravel/framework/pull/39295))
- Added `Illuminate/Support/Testing/Fakes/BusFake::assertNothingDispatched()` ([#39286](https://github.com/laravel/framework/pull/39286))

### Reverted
- Revert ["[8.x] Add gate policy callback"](https://github.com/laravel/framework/pull/39185) ([#39290](https://github.com/laravel/framework/pull/39290))


## [v8.65.0 (2021-10-19)](https://github.com/laravel/framework/compare/v8.64.0...v8.65.0)

### Added
- Allow queueing application and service provider callbacks while callbacks are already being processed ([#39175](https://github.com/laravel/framework/pull/39175), [63dab48](https://github.com/laravel/framework/commit/63dab486a990e26500b1a6520b1493192d6c5104))
- Added ability to validate one of multiple date formats ([#39170](https://github.com/laravel/framework/pull/39170))
- Re-add update from support for PostgreSQL ([#39151](https://github.com/laravel/framework/pull/39151))
- Added `Illuminate/Collections/Traits/EnumeratesValues::reduceSpread()` ([a01e9ed](https://github.com/laravel/framework/commit/a01e9edfadb140559d1bbf9999dda49148bfa5f7))
- Added `Illuminate/Testing/TestResponse::assertRedirectContains()` ([#39233](https://github.com/laravel/framework/pull/39233), [ff340a6](https://github.com/laravel/framework/commit/ff340a6809d07b349aa227c2e4caf3a3ad8f47d5))
- Added gate policy callback ([#39185](https://github.com/laravel/framework/pull/39185))
- Allow Remember Me cookie time to be overriden ([#39186](https://github.com/laravel/framework/pull/39186))
- Adds `--test` and `--pest` options to various `make` commands ([#38997](https://github.com/laravel/framework/pull/38997))
- Added new lost connection message to DetectsLostConnections for Vapor ([#39209](https://github.com/laravel/framework/pull/39209))
- Added `Illuminate/Support/Testing/Fakes/NotificationFake::assertSentOnDemand()` ([#39203](https://github.com/laravel/framework/pull/39203))
- Added Subset in request's collect ([#39191](https://github.com/laravel/framework/pull/39191))
- Added Conditional trait to Eloquent Factory ([#39228](https://github.com/laravel/framework/pull/39228))
- Added a way to skip count check but check $callback at the same time for AssertableJson->has() ([#39224](https://github.com/laravel/framework/pull/39224))
- Added `Illuminate/Support/Str::headline()` ([#39174](https://github.com/laravel/framework/pull/39174))

### Deprecated
- Deprecate `reduceMany` in favor of `reduceSpread` in `Illuminate/Collections/Traits/EnumeratesValues` ([#39201](https://github.com/laravel/framework/pull/39201))

### Fixed
- Fixed HasOneOfMany with callback issue ([#39187](https://github.com/laravel/framework/pull/39187))

### Changed
- Logs deprecations instead of treating them as exceptions ([#39219](https://github.com/laravel/framework/pull/39219))


## [v8.64.0 (2021-10-12)](https://github.com/laravel/framework/compare/v8.63.0...v8.64.0)

### Added
- Added reduceMany to Collections ([#39078](https://github.com/laravel/framework/pull/39078))
- Added `Illuminate/Support/Stringable::stripTags()` ([#39098](https://github.com/laravel/framework/pull/39098))
- Added `Illuminate/Console/OutputStyle::getOutput()` ([#39099](https://github.com/laravel/framework/pull/39099))
- Added `lang_path` helper function ([#39103](https://github.com/laravel/framework/pull/39103))
- Added @aware blade directive ([#39100](https://github.com/laravel/framework/pull/39100))
- New JobRetrying event dispatched ([#39097](https://github.com/laravel/framework/pull/39097))
- Added throwIf method in Client Response ([#39148](https://github.com/laravel/framework/pull/39148))
- Added Illuminate/Collections/Collection::hasAny() ([#39155](https://github.com/laravel/framework/pull/39155))

### Fixed
- Fixed route groups with no prefix on PHP 8.1 ([#39115](https://github.com/laravel/framework/pull/39115))
- Fixed code locating Bearer token in InteractsWithInput ([#39150](https://github.com/laravel/framework/pull/39150))

### Changed
- Refactoring `Illuminate/Log/LogManager::prepareHandler()` ([#39093](https://github.com/laravel/framework/pull/39093))
- Flush component state when done rendering in View ([04fc7c2](https://github.com/laravel/framework/commit/04fc7c2f87372511b0f77e539bc0e2e3357ec200))
- Ignore tablespaces in dump ([#39126](https://github.com/laravel/framework/pull/39126))
- Update SchemaState Process to remove timeout ([#39139](https://github.com/laravel/framework/pull/39139))


## [v8.63.0 (2021-10-05)](https://github.com/laravel/framework/compare/v8.62.0...v8.63.0)

### Added
- Added new lost connection message to DetectsLostConnections ([#39028](https://github.com/laravel/framework/pull/39028))
- Added whereBelongsTo() Eloquent builder method ([#38927](https://github.com/laravel/framework/pull/38927))
- Added Illuminate/Foundation/Testing/Wormhole::minute() ([#39050](https://github.com/laravel/framework/pull/39050))

### Fixed
- Fixed castable value object not serialized correctly ([#39020](https://github.com/laravel/framework/pull/39020))
- Fixed casting to string on PHP 8.1 ([#39033](https://github.com/laravel/framework/pull/39033))
- Mail empty address handling ([#39035](https://github.com/laravel/framework/pull/39035))
- Fixed NotPwnedVerifier failures ([#39038](https://github.com/laravel/framework/pull/39038))
- Fixed LazyCollection#unique() double enumeration ([#39041](https://github.com/laravel/framework/pull/39041))
  
### Changed
- HTTP client: only allow a single User-Agent header ([#39085](https://github.com/laravel/framework/pull/39085))


## [v8.62.0 (2021-09-28)](https://github.com/laravel/framework/compare/v8.61.0...v8.62.0)

### Added
- Added singular syntactic sugar to wormhole ([#38815](https://github.com/laravel/framework/pull/38815))
- Added a few PHP 8.1 related changes ([#38404](https://github.com/laravel/framework/pull/38404), [#38961](https://github.com/laravel/framework/pull/38961))
- Dispatch events when maintenance mode is enabled and disabled ([#38826](https://github.com/laravel/framework/pull/38826))
- Added assertNotSoftDeleted Method ([#38886](https://github.com/laravel/framework/pull/38886))
- Adds new RefreshDatabaseLazily testing trait ([#38861](https://github.com/laravel/framework/pull/38861))
- Added --pretend option for model:prune command ([#38945](https://github.com/laravel/framework/pull/38945))
- Make PendingMail Conditionable ([#38942](https://github.com/laravel/framework/pull/38942))
- Adds --pest option when using the make:test artisan command ([#38966](https://github.com/laravel/framework/pull/38966))

### Reverted
- Reverted ["Added posibility compare custom date/immutable_date using date comparison"](https://github.com/laravel/framework/pull/38720) ([#38993](https://github.com/laravel/framework/pull/38993))

### Fixed
- Fix getDirty method when using AsArrayObject / AsCollection ([#38869](https://github.com/laravel/framework/pull/38869))
- Fix sometimes conditions that add rules for sibling values within an array of data ([#38899](https://github.com/laravel/framework/pull/38899))
- Fixed Illuminate/Validation/Rules/Password::passes() ([#38962](https://github.com/laravel/framework/pull/38962))
- Fixed for custom date castable and database value formatting ([#38994](https://github.com/laravel/framework/pull/38994))

### Changed
- Make mailable assertions fluent ([#38850](https://github.com/laravel/framework/pull/38850))
- Allow request input to be retrieved as a collection ([#38832](https://github.com/laravel/framework/pull/38832))
- Allow index.blade.php views for anonymous components ([#38847](https://github.com/laravel/framework/pull/38847))
- Changed *ofMany to decide relationship name when it is null ([#38889](https://github.com/laravel/framework/pull/38889))
- Ignore trailing delimiter in cache.headers options string ([#38910](https://github.com/laravel/framework/pull/38910))
- Only look for files ending with .php in model:prune ([#38975](https://github.com/laravel/framework/pull/38975))
- Notification assertions respect shouldSend method on notification ([#38979](https://github.com/laravel/framework/pull/38979))
- Convert middleware to array when outputting as JSON in /RouteListCommand ([#38953](https://github.com/laravel/framework/pull/38953))


## [v8.61.0 (2021-09-13)](https://github.com/laravel/framework/compare/v8.60.0...v8.61.0)

### Added
- Added posibility compare custom date/immutable_date using date comparison ([#38720](https://github.com/laravel/framework/pull/38720))
- Added policy option to make:model ([#38725](https://github.com/laravel/framework/pull/38725)
- Allow tests to utilise the null logger ([#38785](https://github.com/laravel/framework/pull/38785))
- Added deleteOrFail to Model ([#38784](https://github.com/laravel/framework/pull/38784))
- Added assertExists testing method ([#38766](https://github.com/laravel/framework/pull/38766))
- Added forwardDecoratedCallTo to Illuminate/Database/Eloquent/Relations/Relation ([#38800](https://github.com/laravel/framework/pull/38800))
- Adding support for using a different Redis DB in a Sentinel setup ([#38764](https://github.com/laravel/framework/pull/38764))

### Changed
- Return on null in `Illuminate/Queue/Queue::getJobBackoff()` ([27bcf13](https://github.com/laravel/framework/commit/27bcf13ce0fb64d7677e1376bf6fde0fc08810a2))
- Provide psr/simple-cache-implementation ([#38767](https://github.com/laravel/framework/pull/38767))
- Use lowercase for hmac hash algorithm ([#38787](https://github.com/laravel/framework/pull/38787))


## [v8.60.0 (2021-09-08)](https://github.com/laravel/framework/compare/v8.59.0...v8.60.0)

### Added
- Added the `valueOfFail()` Eloquent builder method ([#38707](https://github.com/laravel/framework/pull/38707))

### Reverted
- Reverted ["Added the password reset URL to the toMailCallback"](https://github.com/laravel/framework/pull/38552)) ([#38711](https://github.com/laravel/framework/pull/38711))


## [v8.59.0 (2021-09-07)](https://github.com/laravel/framework/compare/v8.58.0...v8.59.0)

### Added
- Allow quiet creation ([e9cd94c](https://github.com/laravel/framework/commit/e9cd94c89f59c833c13d04f32f1e31db419a4c0c))
- Added merge() function to ValidatedInput ([#38640](https://github.com/laravel/framework/pull/38640))
- Added support for disallowing class morphs ([#38656](https://github.com/laravel/framework/pull/38656))
- Added AssertableJson::each() method ([#38684](https://github.com/laravel/framework/pull/38684))
- Added Eloquent builder whereMorphedTo method to streamline finding models morphed to another model ([#38668](https://github.com/laravel/framework/pull/38668))

### Fixed
- Silence Validator Date Parse Warnings ([#38652](https://github.com/laravel/framework/pull/38652))

### Changed
- Remove mapWithKeys from HTTP Client headers() methods ([#38643](https://github.com/laravel/framework/pull/38643))
- Return a new or existing guzzle client based on context in `Illuminate/Http/Client/PendingRequest::buildClient()` ([#38642](https://github.com/laravel/framework/pull/38642))
- Show a pretty diff for assertExactJson() ([#38655](https://github.com/laravel/framework/pull/38655))
- Lowercase cipher name in the Encrypter supported method ([#38693](https://github.com/laravel/framework/pull/38693))


## [v8.58.0 (2021-08-31)](https://github.com/laravel/framework/compare/v8.57.0...v8.58.0)

### Added
- Added updateOrFail method to Model ([#38592](https://github.com/laravel/framework/pull/38592))
- Make mail stubs more configurable ([#38596](https://github.com/laravel/framework/pull/38596))
- Added prohibits validation ([#38612](https://github.com/laravel/framework/pull/38612))

### Changed
- Use lowercase OpenSSL cipher names ([#38594](https://github.com/laravel/framework/pull/38594), [#38600](https://github.com/laravel/framework/pull/38600))


## [v8.57.0 (2021-08-27)](https://github.com/laravel/framework/compare/v8.56.0...v8.57.0)

### Added
- Added exclude validation rule ([#38537](https://github.com/laravel/framework/pull/38537))
- Allow passing when callback to Http client retry method ([#38531](https://github.com/laravel/framework/pull/38531))
- Added `Illuminate/Testing/TestResponse::assertUnprocessable()` ([#38553](https://github.com/laravel/framework/pull/38553))
- Added the password reset URL to the toMailCallback ([#38552](https://github.com/laravel/framework/pull/38552))
- Added a simple where helper for querying relations ([#38499](https://github.com/laravel/framework/pull/38499))
- Allow sync broadcast via method ([#38557](https://github.com/laravel/framework/pull/38557))
- Make $validator->sometimes() item aware to be able to work with nested arrays ([#38443](https://github.com/laravel/framework/pull/38443))

### Fixed
- Fixed Blade component falsy slots ([#38546](https://github.com/laravel/framework/pull/38546))
- Keep backward compatibility with custom ciphers in `Illuminate/Encryption/Encrypter::generateKey()` ([#38556](https://github.com/laravel/framework/pull/38556))
- Fixed bug discarding input fields with empty validation rules ([#38563](https://github.com/laravel/framework/pull/38563))

### Changed
- Don't iterate over all collection in Collection::firstOrFail ([#38536](https://github.com/laravel/framework/pull/38536))
- Error out when detecting incompatible DBAL version ([#38543](https://github.com/laravel/framework/pull/38543))


## [v8.56.0 (2021-08-24)](https://github.com/laravel/framework/compare/v8.55.0...v8.56.0)

### Added
- Added firstOrFail to Illuminate\Support\Collections and Illuminate\Support\LazyCollections ([#38420](https://github.com/laravel/framework/pull/38420))
- Support route caching with trashed bindings ([c3ec2f2](https://github.com/laravel/framework/commit/c3ec2f2d2ad15f2e35cebaa6fcf242ce22af2f8a))
- Allow only keys directly on safe in FormRequest ([5e4ded8](https://github.com/laravel/framework/commit/5e4ded83bacc64e5604b6f71496734071c53b221))
- Added default rules in conditional rules ([#38450](https://github.com/laravel/framework/pull/38450))
- Added fullUrlWithoutQuery method to Request ([#38482](https://github.com/laravel/framework/pull/38482))
- Added --implicit (and -i) option to make:rule ([#38480](https://github.com/laravel/framework/pull/38480))
- Added colon port support in serve command host option ([#38522](https://github.com/laravel/framework/pull/38522))

### Changed
- Testing: Access component properties from the return value of $this->component() ([#38396](https://github.com/laravel/framework/pull/38396), [42a71fd](https://github.com/laravel/framework/commit/42a71fded8b552321f1a1b962cb17e273c7cdf24))
- Update InteractsWithInput::bearerToken() ([#38426](https://github.com/laravel/framework/pull/38426))
- Minor improvements to validation assertions API ([#38422](https://github.com/laravel/framework/pull/38422))
- Blade component slot attributes ([#38372](https://github.com/laravel/framework/pull/38372))
- Convenient methods for rate limiting ([2f93c49](https://github.com/laravel/framework/commit/2f93c4949b60e9b13a3a2d9e5ebb096bd1ae98a9))
- Run event:clear on optimize:clear ([a61b24c2](https://github.com/laravel/framework/commit/a61b24c2d266aee6000f9e768df8c1a7be8fd9d1))
- Remove unnecessary double MAC for AEAD ciphers ([#38475](https://github.com/laravel/framework/pull/38475))
- Adds Response authorization to Form Requests ([#38489](https://github.com/laravel/framework/pull/38489))
- Make TestResponse::getCookie public so it can be directly used in tests ([#38524](https://github.com/laravel/framework/pull/38524))


## [v8.55.0 (2021-08-17)](https://github.com/laravel/framework/compare/v8.54.0...v8.55.0)

### Added
- Added stringable support for isUuid ([#38330](https://github.com/laravel/framework/pull/38330))
- Allow for closure reflection on all MailFake assertions ([#38328](https://github.com/laravel/framework/pull/38328))
- Added `Illuminate/Support/Testing/Fakes/MailFake::assertNothingOutgoing()` ([363af47](https://github.com/laravel/framework/commit/363af4793bfac97f2d846f5fa6bb985ce6a5642e))
- Added `Illuminate/Support/Testing/Fakes/MailFake::assertNotOutgoing()` ([a3658c9](https://github.com/laravel/framework/commit/a3658c93695b79b3f9a8fc72c04c6d928dcc51a9))
- Added Support withTrashed on routes ([#38348](https://github.com/laravel/framework/pull/38348))
- Added Failover Swift Transport driver ([#38344](https://github.com/laravel/framework/pull/38344))
- Added Conditional rules ([#38361](https://github.com/laravel/framework/pull/38361))
- Added assertRedirectToSignedRoute() method for testing responses ([#38349](https://github.com/laravel/framework/pull/38349))
- Added Validated subsets ([#38366](https://github.com/laravel/framework/pull/38366))
- Share handler instead of client between requests in pool to ensure ResponseReceived events are dispatched in async HTTP Request ([#38380](https://github.com/laravel/framework/pull/38380))
- Support union types on event discovery ([#38383](https://github.com/laravel/framework/pull/38383))
- Added Assert invalid in testResponse ([#38384](https://github.com/laravel/framework/pull/38384))
- Add qualifyColumns method to Model class ([#38403](https://github.com/laravel/framework/pull/38403))
- Added ability to throw a custom validation exception ([#38406](https://github.com/laravel/framework/pull/38406))
- Support shorter subscription syntax ([#38408](https://github.com/laravel/framework/pull/38408))

### Fixed
- Handle exceptions in batch callbacks ([#38327](https://github.com/laravel/framework/pull/38327))
- Bump AWS PHP SDK ([#38297](https://github.com/laravel/framework/pull/38297))
- Fixed firstOrCreate and firstOrNew should merge attributes correctly ([#38346](https://github.com/laravel/framework/pull/38346))
- Check for incomplete class to prevent unexpected error when class cannot be loaded in retry command ([#38379](https://github.com/laravel/framework/pull/38379))

### Changed
- Update the ParallelRunner to allow for a custom Runner to be resolved ([#38374](https://github.com/laravel/framework/pull/38374))
- Use Fluent instead of array on Rule::when() ([#38397](https://github.com/laravel/framework/pull/38397))


## [v8.54.0 (2021-08-10)](https://github.com/laravel/framework/compare/v8.53.1...v8.54.0)

### Added
- Added support for GCM encryption ([#38190](https://github.com/laravel/framework/pull/38190), [827bc1d](https://github.com/laravel/framework/commit/827bc1de8b400fd7cc3edd3391124dc9003f1ddc))
- Added exception as parameter to the missing() callbacks in `Illuminate/Routing/Middleware/SubstituteBindings.php` ([#38289](https://github.com/laravel/framework/pull/38289))
- Implement TrustProxies middleware ([#38295](https://github.com/laravel/framework/pull/38295))
- Added bitwise not operator to `Illuminate/Database/Query/Builder.php` ([#38316](https://github.com/laravel/framework/pull/38316))
- Adds attempt method to RateLimiter ([#38313](https://github.com/laravel/framework/pull/38313))
- Added withoutTrashed on Exists rule ([#38314](https://github.com/laravel/framework/pull/38314))

### Changed
- Wraps column name inside subQuery of hasOneOfMany-relationship ([#38263](https://github.com/laravel/framework/pull/38263))
- Change Visibility of the Markdown property in Mailable ([#38320](https://github.com/laravel/framework/pull/38320))
- Swap multiple logical OR for in_array when checking date casting ([#38307](https://github.com/laravel/framework/pull/38307))

### Fixed
- Fixed out of bounds shift and pop behavior in Collection ([bd89575](https://github.com/laravel/framework/commit/bd89575218afd14cbc12fde4be56607e40aeded9))
- Fixed schedule timezone when using CarbonImmutable ([#38297](https://github.com/laravel/framework/pull/38297))
- Fixed isDateCastable for the new immutable_date and immutable_datetime casts ([#38294](https://github.com/laravel/framework/pull/38294))
- Fixed Factory hasMany method ([#38319](https://github.com/laravel/framework/pull/38319))


## [v8.53.1 (2021-08-05)](https://github.com/laravel/framework/compare/v8.53.0...v8.53.1)

### Added
- Added placeholders replace for accepted_if validation message ([#38240](https://github.com/laravel/framework/pull/38240))

### Fixed
- Use type hints in cast.stub to match interface ([#38234](https://github.com/laravel/framework/pull/38234))
- Some PHP 8.1 fixes ([#38245](https://github.com/laravel/framework/pull/38245))
- Fixed aliasing with cursor pagination ([#38251](https://github.com/laravel/framework/pull/38251))
- Fixed signed routes ([#38249](https://github.com/laravel/framework/pull/38249))


## [v8.53.0 (2021-08-03)](https://github.com/laravel/framework/compare/v8.52.0...v8.53.0)

### Added
- Added cache_locks table to cache stub ([#38152](https://github.com/laravel/framework/pull/38152))
- Added queue:monitor command ([#38168](https://github.com/laravel/framework/pull/38168))
- Added twiceDailyAt schedule frequency ([#38174](https://github.com/laravel/framework/pull/38174))
- Added immutable date and datetime casting ([#38199](https://github.com/laravel/framework/pull/38199))
- Allow the php web server to run multiple workers ([#38208](https://github.com/laravel/framework/pull/38208))
- Added accepted_if validation rule ([#38210](https://github.com/laravel/framework/pull/38210))

### Fixed
- Fixed signed routes with expires parameter ([#38111](https://github.com/laravel/framework/pull/38111), [732c0e0](https://github.com/laravel/framework/commit/732c0e0f64b222e7fc7daef6553f8e99007bb32c))
- Remove call to deleted method in `Illuminate/Testing/TestResponse::statusMessageWithException()` ([cde3662](https://github.com/laravel/framework/commit/cde36626376e014390713ab03a01eb4dfe6488ce))
- Fixed previous column for cursor pagination ([#38203](https://github.com/laravel/framework/pull/38203))

### Changed
- Prevent assertStatus() invalid JSON exception for valid JSON response content ([#38192](https://github.com/laravel/framework/pull/38192))
- Bump AWS SDK to `^3.186.4` ([#38216](https://github.com/laravel/framework/pull/38216))
- Implement `ReturnTypeWillChange` for some place ([#38221](https://github.com/laravel/framework/pull/38221), [#38212](https://github.com/laravel/framework/pull/38212), [#38226](https://github.com/laravel/framework/pull/38226))
- Use actual countable interface on MessageBag ([#38227](https://github.com/laravel/framework/pull/38227))

### Refactoring
- Remove hardcoded Carbon reference from scheduler event ([#38063](https://github.com/laravel/framework/pull/38063))


## [v8.52.0 (2021-07-27)](https://github.com/laravel/framework/compare/v8.51.0...v8.52.0)

### Added
- Allow shift() and pop() to take multiple items from a collection ([#38093](https://github.com/laravel/framework/pull/38093))
- Added hook to configure broadcastable model event ([5ca5768](https://github.com/laravel/framework/commit/5ca5768db439887217c86031ff7dd3bdf56cc466), [aca6f90](https://github.com/laravel/framework/commit/aca6f90b7177361b8d1f4ca6eecea78403f32583))
- Support a proxy URL for mix hot ([#38118](https://github.com/laravel/framework/pull/38118))
- Added `Illuminate/Validation/Rules/Unique::withoutTrashed()` ([#38124](https://github.com/laravel/framework/pull/38124))
- Support job middleware on queued listeners ([#38128](https://github.com/laravel/framework/pull/38128))
- Model Broadcasting - Adding broadcastWith() and broadcastAs() support ([#38137](https://github.com/laravel/framework/pull/38137))
- Allow parallel testing without database creation ([#38143](https://github.com/laravel/framework/pull/38143))

### Fixed
- Fixed display of validation errors occurred when asserting status ([#38088](https://github.com/laravel/framework/pull/38088))
- Developer friendly message if no Prunable Models found ([#38108](https://github.com/laravel/framework/pull/38108))
- Fix running schedule:test on CallbackEvent ([#38146](https://github.com/laravel/framework/pull/38146))

### Changed
- BelongsToMany->sync() will support touching for pivots when the result contains detached items ([#38085](https://github.com/laravel/framework/pull/38085))
- Ability to specify the broadcaster to use when broadcasting an event ([#38086](https://github.com/laravel/framework/pull/38086))
- Password Validator should inherit custom error message and attribute ([#38114](https://github.com/laravel/framework/pull/38114))


## [v8.51.0 (2021-07-20)](https://github.com/laravel/framework/compare/v8.50.0...v8.51.0)

### Added
- Allow dynamically customizing connection for queued event listener ([#38005](https://github.com/laravel/framework/pull/38005), [ebc3ce4](https://github.com/laravel/framework/commit/ebc3ce49fb99e85fc2b5695fd9d88b95429bc5a0))
- Added `@class` Blade directive ([#38016](https://github.com/laravel/framework/pull/38016))
- Accept closure for retry() sleep ([#38035](https://github.com/laravel/framework/pull/38035))
- The controller can directly return the stdClass object ([#38033](https://github.com/laravel/framework/pull/38033))
- Make FilesystemAdapter macroable ([#38030](https://github.com/laravel/framework/pull/38030))
- Track exceptions and display them on failed status checks for dx ([#38025](https://github.com/laravel/framework/pull/38025))
- Display unexpected validation errors when asserting status ([#38046](https://github.com/laravel/framework/pull/38046))
- Ability to return the default value of a request whenHas and whenFilled methods ([#38060](https://github.com/laravel/framework/pull/38060))
- Added `Filesystem::replaceInFile()` method ([#38069](https://github.com/laravel/framework/pull/38069))

### Fixed
- Fixed passing cursor to pagination methods ([#37996](https://github.com/laravel/framework/pull/37996))
- Fixed issue with cursor pagination and Json resources ([#38026](https://github.com/laravel/framework/pull/38026))
- ErrorException: Undefined array key "exception" ([#38059](https://github.com/laravel/framework/pull/38059))
- Fixed unvalidated array keys without implicit attributes ([#38052](https://github.com/laravel/framework/pull/38052))

### Changed
- Passthrough excluded uri's in maintenance mode ([#38041](https://github.com/laravel/framework/pull/38041))
- Allow for named arguments via dispatchable trait ([#38066](https://github.com/laravel/framework/pull/38066))


## [v8.50.0 (2021-07-13)](https://github.com/laravel/framework/compare/v8.49.2...v8.50.0)

### Added
- Added ability to cancel notifications immediately prior to sending ([#37930](https://github.com/laravel/framework/pull/37930))
- Added the possibility of having "Prunable" models ([#37889](https://github.com/laravel/framework/pull/37889))
- Added support for both CommonMark 1.x and 2.x ([#37954](https://github.com/laravel/framework/pull/37954))
- Added `Illuminate/Validation/Factory::excludeUnvalidatedArrayKeys()` ([#37943](https://github.com/laravel/framework/pull/37943))

### Fixed
- Fixed `Illuminate/Bus/PendingBatch::add()` ([108385b](https://github.com/laravel/framework/commit/108385b4f98cacfc1ef1d6e323f57b1c2df3180f))
- Cursor pagination fixes ([#37915](https://github.com/laravel/framework/pull/37915))

### Changed
- Mixed orders in cursor paginate ([#37762](https://github.com/laravel/framework/pull/37762))
- Clear config after dumping auto-loaded files ([#37985](https://github.com/laravel/framework/pull/37985))


## [v8.49.2 (2021-07-07)](https://github.com/laravel/framework/compare/v8.49.1...v8.49.2)

### Added
- Adds ResponseReceived events to async requests of HTTP Client ([#37917](https://github.com/laravel/framework/pull/37917))

### Fixed
- Fixed edge case causing a BadMethodCallExceptions to be thrown when using loadMissing() ([#37871](https://github.com/laravel/framework/pull/37871))


## [v8.49.1 (2021-07-02)](https://github.com/laravel/framework/compare/v8.49.0...v8.49.1)

### Reverted 
- Reverted [Bind mock instances as singletons so they are not overwritten](https://github.com/laravel/framework/pull/37746) ([#37892](https://github.com/laravel/framework/pull/37892))

### Fixed
- Fixed undefined array key in SqlServerGrammar when using orderByRaw ([#37859](https://github.com/laravel/framework/pull/37859))
- Fixed facade isMock to recognise LegacyMockInterface ([#37882](https://github.com/laravel/framework/pull/37882))

### Changed
- Reset the log context after each worker loop ([#37865](https://github.com/laravel/framework/pull/37865))
- Improve pretend run Doctrine failure message ([#37879](https://github.com/laravel/framework/pull/37879))


## [v8.49.0 (2021-07-02)](https://github.com/laravel/framework/compare/v8.48.2...v8.49.0)

### Added
- Add context to subsequent logs ([#37847](https://github.com/laravel/framework/pull/37847))


## [v8.48.2 (2021-06-26)](https://github.com/laravel/framework/compare/v8.48.1...v8.48.2)

### Added
- Added parameter casting for cursor paginated items ([#37785](https://github.com/laravel/framework/pull/37785), [31ebfc8](https://github.com/laravel/framework/commit/31ebfc86e5c707954b88c43fbe872cb06bc76d28))
- Added `Illuminate/Http/ResponseTrait::statusText()` ([#37795](https://github.com/laravel/framework/pull/37795))
- Track a loop variable for sequence and pass it with count to closure ([#37799](https://github.com/laravel/framework/pull/37799))
- Added "precedence" order to route:list command ([#37824](https://github.com/laravel/framework/pull/37824))

### Fixed
- Remove ksort in pool results that modifies intended original order ([#37775](https://github.com/laravel/framework/pull/37775))
- Make sure availableIn returns positive values in `/Illuminate/Cache/RateLimiter::availableIn()` ([#37809](https://github.com/laravel/framework/pull/37809))-
- Ensure alias is rebound when mocking items in the container in tests ([#37810](https://github.com/laravel/framework/pull/37810))
- Move primary after collate in `/MySqlGrammar.php` modifiers ([#37815](https://github.com/laravel/framework/pull/37815)))


## [v8.48.1 (2021-06-23)](https://github.com/laravel/framework/compare/v8.48.0...v8.48.1)

### Fixed
- Order of Modifiers Amended in MySqlGrammar ([#37782](https://github.com/laravel/framework/pull/37782))


## [v8.48.0 (2021-06-23)](https://github.com/laravel/framework/compare/v8.47.0...v8.48.0)

### Added
- Added a queue:prune-failed command ([#37696](https://github.com/laravel/framework/pull/37696), [7aca658](https://github.com/laravel/framework/commit/7aca65833887d0760fc61e320bc46b80c9cb3398))
- Added `Illuminate/Filesystem/FilesystemManager::build()` ([#37720](https://github.com/laravel/framework/pull/37720), [c21fc12](https://github.com/laravel/framework/commit/c21fc126dc87ff357c7ae5c79014135f693d0ffe))
- Allow customising the event.stub file ([#37761](https://github.com/laravel/framework/pull/37761))
- Added `Illuminate/Collections/Collection::sliding()` and `Illuminate/Collections/LazyCollection::sliding()` ([#37751](https://github.com/laravel/framework/pull/37751))
- Make `Illuminate\Http\Client\Request` macroable ([#37744](https://github.com/laravel/framework/pull/37744))
- Added GIF, WEBP, WBMP, BMP support to FileFactory::image() ([#37743](https://github.com/laravel/framework/pull/37743))
- Dispatch 'connection failed' event in http client ([#37740](https://github.com/laravel/framework/pull/37740))

### Fixed
- Adds a small fix for unicode with blade echo handlers ([#37697](https://github.com/laravel/framework/pull/37697))
- Solve the Primary Key issue in databases with sql_require_primary_key enabled ([#37715](https://github.com/laravel/framework/pull/37715))

### Changed
- Removed unnecessary checks in RequiredIf validation, fixed tests ([#37700](https://github.com/laravel/framework/pull/37700))
- Replace non ASCII apostrophe in the email notification template ([#37709](https://github.com/laravel/framework/pull/37709))
- Change the order of the bindings for a Sql Server query with a offset and a subquery order by ([#37728](https://github.com/laravel/framework/pull/37728), [401928b](https://github.com/laravel/framework/commit/401928b4ba2be400687fdd3c81830b260b51500b))
- Bind mock instances as singletons so they are not overwritten ([#37746](https://github.com/laravel/framework/pull/37746))
- Encode objects when casting as JSON ([#37759](https://github.com/laravel/framework/pull/37759))
- Call on_stats handler in Http stub callbacks ([#37738](https://github.com/laravel/framework/pull/37738))


## [v8.47.0 (2021-06-16)](https://github.com/laravel/framework/compare/v8.46.0...v8.47.0)

### Added
- Introduce scoped instances ([#37521](https://github.com/laravel/framework/pull/37521), [2971b64](https://github.com/laravel/framework/commit/2971b64ac29bec9e65afe683ab4fcd461c565fe5))
- Added whereContains AssertableJson method ([#37631](https://github.com/laravel/framework/pull/37631), [2d2d108](https://github.com/laravel/framework/commit/2d2d108a21b21a149c797cb3995c3a25ac9b4be4))
- Added `Illuminate/Database/Connection::setRecordModificationState()` ([ee1e6b4](https://github.com/laravel/framework/commit/ee1e6b4db76ff11505deb9e5faba3a04de424e97))
- Added `match()` and `matchAll()` methods to `Illuminate/Support/Str.php` ([#37642](https://github.com/laravel/framework/pull/37642))
- Copy password rule to current_password ([#37650](https://github.com/laravel/framework/pull/37650))
- Allow tap() on Paginator ([#37682](https://github.com/laravel/framework/pull/37682))

### Revert
- Revert of ["Columns in the order by list must be unique"](https://github.com/laravel/framework/pull/37582) ([#37649](https://github.com/laravel/framework/pull/37649))

### Fixed
- Remove illuminate/foundation dependency from Password validation ([#37648](https://github.com/laravel/framework/pull/37648))
- Fixed callable password defaults in validator ([0b1610f](https://github.com/laravel/framework/commit/0b1610f7a934787856b141205a9f178f33e17f8b))
- Fixed dns_get_record loose check of A records for active_url rule ([#37675](https://github.com/laravel/framework/pull/37675))
- Type hinted arguments for Illuminate\Validation\Rules\RequiredIf ([#37688](https://github.com/laravel/framework/pull/37688))
- Fixed when passed object as parameters to scopes method ([#37692](https://github.com/laravel/framework/pull/37692))


## [v8.46.0 (2021-06-08)](https://github.com/laravel/framework/compare/v8.45.1...v8.46.0)

### Added
- Allow Custom Notification Stubs ([#37584](https://github.com/laravel/framework/pull/37584))
- Added methods for indicating the write connection should be used ([94dbf76](https://github.com/laravel/framework/commit/94dbf768fa46917cb012a05b38cbc889dbd2e8a0))
- Added timestamp reference to schedule:run artisan command output ([#37591](https://github.com/laravel/framework/pull/37591))
- Columns in the order by list must be unique ([#37582](https://github.com/laravel/framework/pull/37582))

### Changed
- Fire a trashed model event and listen to it for broadcasting events ([#37618](https://github.com/laravel/framework/pull/37618))
- Cast JSON strings containing single quotes ([#37619](https://github.com/laravel/framework/pull/37619))

### Fixed
- Fixed for cloning issues with PendingRequest object ([#37596](https://github.com/laravel/framework/pull/37596), [96518b9](https://github.com/laravel/framework/commit/96518b9bbbc6e984f879c535502c199ef022f52a))
- Makes the retrieval of Http client transferStats safe ([#37597](https://github.com/laravel/framework/pull/37597))
- Fixed inconsistency in table names in validator ([#37606](https://github.com/laravel/framework/pull/37606))
- Fixes for Stringable for views ([#37613](https://github.com/laravel/framework/pull/37613))
- Fixed one-of-many bindings ([#37616](https://github.com/laravel/framework/pull/37616))
- Fixed infinity loop on transaction committed ([#37626](https://github.com/laravel/framework/pull/37626))
- Added missing fix to DatabaseRule::resolveTableName fix #37580 ([#37621](https://github.com/laravel/framework/pull/37621))


## [v8.45.1 (2021-06-03)](https://github.com/laravel/framework/compare/v8.45.0...v8.45.1)

### Revert
- Revert of ["Columns in the order by list must be unique"](https://github.com/laravel/framework/pull/37550) ([dc2f0bb](https://github.com/laravel/framework/commit/dc2f0bb02c3eb4b27669d626bb3e810db8e7749d))


## [v8.45.0 (2021-06-03)](https://github.com/laravel/framework/compare/v8.44.0...v8.45.0)

### Added
- Introduce Conditional trait ([#37504](https://github.com/laravel/framework/pull/37504), [45ff23c](https://github.com/laravel/framework/commit/45ff23c6174416f63ea7dbd77bc7fe8aafced86b), [#37561](https://github.com/laravel/framework/pull/37561))
- Allow multiple SES configuration with IAM Role authentication ([#37523](https://github.com/laravel/framework/pull/37523))
- Adds class handling for Blade echo statements ([#37478](https://github.com/laravel/framework/pull/37478))
- Added `Illuminate/Session/DatabaseSessionHandler::setContainer()` ([7a71c29](https://github.com/laravel/framework/commit/7a71c292c0ae656c622cff883638e77de6f0bfde))
- Allow connecting to read or write connections with the db command ([#37548](https://github.com/laravel/framework/pull/37548))
- Added assertDownloadOffered test method to TestResponse class ([#37532](https://github.com/laravel/framework/pull/37532))
- Added `Illuminate/Http/Client/Response::close()` ([#37566](https://github.com/laravel/framework/pull/37566))
- Allow setting middleware on queued Mailables ([#37568](https://github.com/laravel/framework/pull/37568))
- Adds new RequestSent and ResponseReceived events to the HTTP Client ([#37572](https://github.com/laravel/framework/pull/37572))

### Changed
- Rename protected method `Illuminate/Foundation/Console/StorageLinkCommand::removableSymlink()` to `Illuminate/Foundation/Console/StorageLinkCommand::isRemovableSymlink()` ([#37508](https://github.com/laravel/framework/pull/37508))
- Correct minimum Predis version to 1.1.2 ([#37554](https://github.com/laravel/framework/pull/37554))
- Columns in the order by list must be unique ([#37550](https://github.com/laravel/framework/pull/37550))
- More Convenient Model Broadcasting ([#37491](https://github.com/laravel/framework/pull/37491))

### Fixed
- Get queueable relationship when collection has non-numeric keys ([#37556](https://github.com/laravel/framework/pull/37556))


## [v8.44.0 (2021-05-27)](https://github.com/laravel/framework/compare/v8.43.0...v8.44.0)

### Added
- Delegate lazy loading violation to method ([#37480](https://github.com/laravel/framework/pull/37480))
- Added `force` option to `Illuminate/Foundation/Console/StorageLinkCommand` ([#37501](https://github.com/laravel/framework/pull/37501), [3e547d2](https://github.com/laravel/framework/commit/3e547d2f276f9242d3856ff9cb02418560ae9a1b))

### Fixed
- Fixed aggregates with having ([#37487](https://github.com/laravel/framework/pull/37487), [c986e12](https://github.com/laravel/framework/commit/c986e12b00e9569cca5e24e5072e7770ffc25efa))
- Bugfix passing errorlevel when command is run in background ([#37479](https://github.com/laravel/framework/pull/37479))

### Changed
- Init the traits when the model is being unserialized ([#37492](https://github.com/laravel/framework/pull/37492))
- Relax the lazy loading restrictions ([#37503](https://github.com/laravel/framework/pull/37503))


## [v8.43.0 (2021-05-25)](https://github.com/laravel/framework/compare/v8.42.1...v8.43.0)

### Added
- Added `Illuminate\Auth\Authenticatable::getAuthIdentifierForBroadcasting()` ([#37408](https://github.com/laravel/framework/pull/37408))
- Added eloquent strict loading mode ([#37363](https://github.com/laravel/framework/pull/37363))
- Added default timeout to NotPwnedVerifier validator ([#37440](https://github.com/laravel/framework/pull/37440), [45567e0](https://github.com/laravel/framework/commit/45567e0c0707bb2b418a4218e62fa85e478a68d9))
- Added beforeQuery to base query builder ([#37431](https://github.com/laravel/framework/pull/37431))
- Added `Illuminate\Queue\Jobs\Job::shouldFailOnTimeout()` ([#37450](https://github.com/laravel/framework/pull/37450))
- Added `ValidatorAwareRule` interface ([#37442](https://github.com/laravel/framework/pull/37442))
- Added model support for database assertions ([#37459](https://github.com/laravel/framework/pull/37459))

### Fixed
- Fixed eager loading one-of-many relationships with multiple aggregates ([#37436](https://github.com/laravel/framework/pull/37436))

### Changed
- Improve signed url signature verification ([#37432](https://github.com/laravel/framework/pull/37432))
- Improve one-of-many performance ([#37451](https://github.com/laravel/framework/pull/37451)) 
- Update `Illuminate/Pagination/Cursor::parameter()` ([#37458](https://github.com/laravel/framework/pull/37458))
- Reconnect the correct connection when using ::read or ::write ([#37471](https://github.com/laravel/framework/pull/37471), [d1a32f9](https://github.com/laravel/framework/commit/d1a32f9acb225b6b7b360736f3c717461220dac9))


## [v8.42.1 (2021-05-19)](https://github.com/laravel/framework/compare/v8.42.0...v8.42.1)

### Added
- Add default "_of_many" to join alias when relation name is table name ([#37411](https://github.com/laravel/framework/pull/37411))

### Changed
- Allow dababase password to be null in `MySqlSchemaState` ([#37418](https://github.com/laravel/framework/pull/37418))
- Accept any instance of Rule and not just Password in password rule ([#37407](https://github.com/laravel/framework/pull/37407))

### Fixed
- Fixed aggregates (e.g.: withExists) for one of many relationships ([#37413](https://github.com/laravel/framework/pull/37413), [498e1a0](https://github.com/laravel/framework/commit/498e1a064f0a60b68047a1d3f7c544d14c356503))


## [v8.42.0 (2021-05-18)](https://github.com/laravel/framework/compare/v8.41.0...v8.42.0)

### Added
- Support views in SQLServerGrammar ([#37348](https://github.com/laravel/framework/pull/37348))
- Added new assertDispatchedSync methods to BusFake ([#37350](https://github.com/laravel/framework/pull/37350), [414f382](https://github.com/laravel/framework/commit/414f38247a084fad3dd63b2106968eb119a3d447))
- Added withExists method to QueriesRelationships ([#37302](https://github.com/laravel/framework/pull/37302))
- Added ability to define default Password Rule ([#37387](https://github.com/laravel/framework/pull/37387), [f7e5b1c](https://github.com/laravel/framework/commit/f7e5b1c105dec980b3206c0b9bc7db735756b8d5))
- Allow sending a refresh header with maintenance mode response ([#37385](https://github.com/laravel/framework/pull/37385))
- Added loadExists on Model and Eloquent Collection ([#37388](https://github.com/laravel/framework/pull/37388))
- Added one-of-many relationship (inner join) ([#37362](https://github.com/laravel/framework/pull/37362))

### Changed
- Avoid deprecated guzzle code ([#37349](https://github.com/laravel/framework/pull/37349))
- Make AssertableJson easier to extend by replacing self with static ([#37380](https://github.com/laravel/framework/pull/37380))
- Raise ScheduledBackgroundTaskFinished event to signal when a run in background task finishes ([#37377](https://github.com/laravel/framework/pull/37377))


## [v8.41.0 (2021-05-11)](https://github.com/laravel/framework/compare/v8.40.0...v8.41.0)

### Added
- Added `Illuminate\Database\Eloquent\Model::updateQuietly()` ([#37169](https://github.com/laravel/framework/pull/37169))
- Added `Illuminate\Support\Str::replace()` ([#37186](https://github.com/laravel/framework/pull/37186))
- Added Model key extraction to id on whereKey() and whereKeyNot() ([#37184](https://github.com/laravel/framework/pull/37184))
- Added support for Pusher 6.x ([#37223](https://github.com/laravel/framework/pull/37223), [819db15](https://github.com/laravel/framework/commit/819db15a79621a93f26b4790dc944a74f7a04489))
- Added `Illuminate/Foundation/Http/Kernel::getMiddlewarePriority()` ([#37271](https://github.com/laravel/framework/pull/37271))
- Added cursor pagination (aka keyset pagination) ([#37216](https://github.com/laravel/framework/pull/37216), [#37315](https://github.com/laravel/framework/pull/37315))
- Support mass assignment to SQL Server views ([#37307](https://github.com/laravel/framework/pull/37307))
- Added `Illuminate/Support/Stringable::unless()` ([#37326](https://github.com/laravel/framework/pull/37326))

### Fixed
- Fixed `Illuminate\Database\Query\Builder::offset()` with non numbers $value ([#37164](https://github.com/laravel/framework/pull/37164))
- Treat missing UUID in failed Queue Job as empty string (failed driver = database) ([#37251](https://github.com/laravel/framework/pull/37251))
- Fixed fields not required with required_unless ([#37262](https://github.com/laravel/framework/pull/37262))
- SqlServer Grammar: Bugfixes for hasTable and dropIfExists / support for using schema names in these functions ([#37280](https://github.com/laravel/framework/pull/37280))
- Fix PostgreSQL dump and load for Windows ([#37320](https://github.com/laravel/framework/pull/37320))

### Changed
- Add fallback when migration is not anonymous class ([#37166](https://github.com/laravel/framework/pull/37166))
- Ably expects clientId as string in `Illuminate\Broadcasting\Broadcasters\AblyBroadcaster::validAuthenticationResponse()` ([#37249](https://github.com/laravel/framework/pull/37249))
- Computing controller middleware before getting excluding middleware ([#37259](https://github.com/laravel/framework/pull/37259))
- Update mime extension check ([#37332](https://github.com/laravel/framework/pull/37332))
- Added exception to chunkById() when last id cannot be determined ([#37294](https://github.com/laravel/framework/pull/37294))


## [v8.40.0 (2021-04-28)](https://github.com/laravel/framework/compare/v8.39.0...v8.40.0)

### Added
- Added `Illuminate\Database\Eloquent\Builder::withOnly()` ([#37144](https://github.com/laravel/framework/pull/37144))
- Added `Illuminate\Bus\PendingBatch::add()` ([#37151](https://github.com/laravel/framework/pull/37151))

### Fixed
- Fixed Cache store with a name other than 'dynamodb' ([#37145](https://github.com/laravel/framework/pull/37145))

### Changed
- Added has environment variable to startProcess method in `ServeCommand` ([#37142](https://github.com/laravel/framework/pull/37142))
- Some cast to int in `Illuminate\Database\Query\Grammars\SqlServerGrammar` ([09bf145](https://github.com/laravel/framework/commit/09bf1457e9df53e172e6fd5929cbafb539677c7c))


## [v8.39.0 (2021-04-27)](https://github.com/laravel/framework/compare/v8.38.0...v8.39.0)

### Added
- Added `Illuminate\Collections\Collection::sole()` method ([#37034](https://github.com/laravel/framework/pull/37034))
- Support `url` for php artisan db command ([#37064](https://github.com/laravel/framework/pull/37064))
- Added `Illuminate\Foundation\Bus\DispatchesJobs::dispatchSync()` ([#37063](https://github.com/laravel/framework/pull/37063))
- Added `Illuminate\Cookie\CookieJar::expire()` ([#37072](https://github.com/laravel/framework/pull/37072), [fa3a14f](https://github.com/laravel/framework/commit/fa3a14f4da763a9a95162dc4092d5ab7356e0cb8))
- Added `Illuminate\Database\DatabaseManager::setApplication()` ([#37068](https://github.com/laravel/framework/pull/37068))
- Added `Illuminate\Support\Stringable::whenNotEmpty()` ([#37080](https://github.com/laravel/framework/pull/37080))
- Added `Illuminate\Auth\SessionGuard::attemptWhen()` ([#37090](https://github.com/laravel/framework/pull/37090), [e3fcd97](https://github.com/laravel/framework/commit/e3fcd97d16a064d39c419201937fcc299d6bfa2e))
- Added password validation rule ([#36960](https://github.com/laravel/framework/pull/36960))

### Fixed
- Fixed `JsonResponse::fromJsonString()` double encoding string ([#37076](https://github.com/laravel/framework/pull/37076))
- Fallback to primary key if owner key doesnt exist on model at all in `MorphTo` relation ([a011109](https://github.com/laravel/framework/commit/a0111098c039c27a76df4b4dd555f351ee3c81eb))
- Fixes for PHP 8.1 ([#37087](https://github.com/laravel/framework/pull/37087), [#37101](https://github.com/laravel/framework/pull/37101))
- Do not execute beforeSending callbacks twice in HTTP client ([#37116](https://github.com/laravel/framework/pull/37116))
- Fixed nullable values for required_if ([#37128](https://github.com/laravel/framework/pull/37128), [86fd558](https://github.com/laravel/framework/commit/86fd558b4e5d8d7d45cf457cd1a72d54334297a1))

### Changed
- Schedule list timezone command ([#37117](https://github.com/laravel/framework/pull/37117))


## [v8.38.0 (2021-04-20)](https://github.com/laravel/framework/compare/v8.37.0...v8.38.0)

### Added
- Added a `wordCount()` string helper ([#36990](https://github.com/laravel/framework/pull/36990))
- Allow anonymous and class based migration coexisting ([#37006](https://github.com/laravel/framework/pull/37006))
- Added `Illuminate\Broadcasting\Broadcasters\PusherBroadcaster::setPusher()` ([#37033](https://github.com/laravel/framework/pull/37033))

### Fixed
- Fixed required_if boolean validation ([#36969](https://github.com/laravel/framework/pull/36969))
- Correctly merge object payload data in `Illuminate\Queue\Queue::createObjectPayload()` ([#36998](https://github.com/laravel/framework/pull/36998))
- Allow the use of temporary views for Blade testing on Windows machines ([#37044](https://github.com/laravel/framework/pull/37044))
- Fixed `Http::withBody()` not being sent ([#37057](https://github.com/laravel/framework/pull/37057))


## [v8.37.0 (2021-04-13)](https://github.com/laravel/framework/compare/v8.36.2...v8.37.0)

### Added
- Allow to retry jobs by queue name ([#36898](https://github.com/laravel/framework/pull/36898), [f2d9b59](https://github.com/laravel/framework/commit/f2d9b595e51d564c5e1390eb42438c632e0daf36), [c351a30](https://github.com/laravel/framework/commit/c351a309f1a02098f9a7ee24a8a402e9ce06fead))
- Added strings to the `DetectsLostConnections.php` ([4210258](https://github.com/laravel/framework/commit/42102589bc7f7b8533ee1b815ef0cc18017d4e45))
- Allow testing of Blade components that return closures ([#36919](https://github.com/laravel/framework/pull/36919))
- Added anonymous migrations ([#36906](https://github.com/laravel/framework/pull/36906))
- Added `Session\Store::missing()` method ([#36937](https://github.com/laravel/framework/pull/36937))
- Handle concurrent asynchronous requests in the HTTP client ([#36948](https://github.com/laravel/framework/pull/36948), [245a712](https://github.com/laravel/framework/commit/245a7125076e52da7ce55b494c1c01f0f28df55d))
- Added tinyText data type to Blueprint and to available database grammars ([#36949](https://github.com/laravel/framework/pull/36949))
- Added a method to remove a resolved view engine ([#36955](https://github.com/laravel/framework/pull/36955))
- Added `Illuminate\Database\Eloquent\Model::getAttributesForInsert()` protected method ([9a9f59f](https://github.com/laravel/framework/commit/9a9f59fcc6e7b93465ce9848b52a473477dff64a), [314bf87](https://github.com/laravel/framework/commit/314bf875ba5d37c056ccea5148181fcb0517f596))

### Fixed
- Fixed clone() on EloquentBuilder ([#36924](https://github.com/laravel/framework/pull/36924))

### Changed
- `Model::delete()` throw LogicException not Exception ([#36914](https://github.com/laravel/framework/pull/36914))
- Make pagination linkCollection() method public ([#36959](https://github.com/laravel/framework/pull/36959))


## [v8.36.2 (2021-04-07)](https://github.com/laravel/framework/compare/v8.36.1...v8.36.2)

### Revert
- Revert blade changes ([#36902](https://github.com/laravel/framework/pull/36902))


## [v8.36.1 (2021-04-07)](https://github.com/laravel/framework/compare/v8.36.0...v8.36.1)

### Fixed
- Fixed escaping within quoted strings in blade ([#36893](https://github.com/laravel/framework/pull/36893))

### Changed
- Call transaction callbacks after updating the transaction level ([#36890](https://github.com/laravel/framework/pull/36890), [#36892](https://github.com/laravel/framework/pull/36892))
- Support maxExceptions option on queued listeners ([#36891](https://github.com/laravel/framework/pull/36891))


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
