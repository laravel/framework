# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v10.9.0...10.x)


## [v10.9.0 (2023-04-25)](https://github.com/laravel/framework/compare/v10.8.0...v10.9.0)

### Added
- Add new HTTP status assertions ([#46841](https://github.com/laravel/framework/pull/46841))
- Allow pruning all cancelled and unfinished queue batches ([#46833](https://github.com/laravel/framework/pull/46833))
- Added `IGNITION_LOCAL_SITES_PATH` to `$passthroughVariables` in `ServeCommand.php` ([#46857](https://github.com/laravel/framework/pull/46857))
- Added named static methods for middleware ([#46362](https://github.com/laravel/framework/pull/46362))

### Fixed
- Fix date_format rule throw ValueError ([#46824](https://github.com/laravel/framework/pull/46824))

### Changed
- Allow separate directory for locks on filestore ([#46811](https://github.com/laravel/framework/pull/46811))
- Allow to whereMorphedTo work with null model ([#46821](https://github.com/laravel/framework/pull/46821))
- Use pivot model fromDateTime instead of assuming Carbon in `Illuminate/Database/Eloquent/Relations/Concerns/InteractsWithPivotTable::addTimestampsToAttachment()` ([#46822](https://github.com/laravel/framework/pull/46822))
- Make rules method in FormRequest optional ([#46846](https://github.com/laravel/framework/pull/46846))
- Throw LogicException when calling FileFactory@image() if mimetype is not supported ([#46859](https://github.com/laravel/framework/pull/46859))
- Improve job release method to accept date instance ([#46854](https://github.com/laravel/framework/pull/46854))
- Use foreignUlid if model uses HasUlids trait when call foreignIdFor ([#46876](https://github.com/laravel/framework/pull/46876))


## [v10.8.0 (2023-04-18)](https://github.com/laravel/framework/compare/v10.7.1...v10.8.0)

### Added
- Added syntax sugar to the Process::pipe method ([#46745](https://github.com/laravel/framework/pull/46745))
- Allow specifying index name when calling ForeignIdColumnDefinition@constrained() ([#46746](https://github.com/laravel/framework/pull/46746))
- Allow to customise redirect URL in AuthenticateSession Middleware ([#46752](https://github.com/laravel/framework/pull/46752))
- Added Class based after validation rules ([#46757](https://github.com/laravel/framework/pull/46757))
- Added max exceptions to broadcast event ([#46800](https://github.com/laravel/framework/pull/46800))

### Fixed
- Fixed compiled view file ends with .php ([#46755](https://github.com/laravel/framework/pull/46755))
- Fix validation rule names ([#46768](https://github.com/laravel/framework/pull/46768))
- Fixed validateDecimal() ([#46809](https://github.com/laravel/framework/pull/46809))

### Changed
- Add headers to exception in `Illuminate/Foundation/Application::abourd()` ([#46780](https://github.com/laravel/framework/pull/46780))
- Minor skeleton slimming (framework edition) ([#46786](https://github.com/laravel/framework/pull/46786))
- Release lock for job implementing ShouldBeUnique that is dispatched afterResponse() ([#46806](https://github.com/laravel/framework/pull/46806))


## [v10.7.1 (2023-04-11)](https://github.com/laravel/framework/compare/v10.7.0...v10.7.1)

### Changed
- Changed `Illuminate/Process/Factory::pipe()` method. It will be run pipes immediately ([e34ab39](https://github.com/laravel/framework/commit/e34ab392800bfc175334c90e9321caa7261c2d65))

## [v10.7.0 (2023-04-11)](https://github.com/laravel/framework/compare/v10.6.2...v10.7.0)

### Added
- Allow `Illuminate/Foundation/Testing/WithFaker` to be used when app is not bound ([#46529](https://github.com/laravel/framework/pull/46529))
- Allow Event::assertListening to check for invokable event listeners ([#46683](https://github.com/laravel/framework/pull/46683))
- Added `Illuminate/Process/Factory::pipe()` ([#46527](https://github.com/laravel/framework/pull/46527))
- Added `Illuminate/Validation/Validator::setValue` ([#46716](https://github.com/laravel/framework/pull/46716))

### Fixed
- PHP 8.0 fix for Closure jobs ([#46505](https://github.com/laravel/framework/pull/46505))
- Fix preg_split error when there is a slash in the attribute in `Illuminate/Validation/ValidationData` ([#46549](https://github.com/laravel/framework/pull/46549))
- Fixed Cache::spy incompatibility with Cache::get ([#46689](https://github.com/laravel/framework/pull/46689))
- server command: Fixed server Closing output on invalid $requestPort ([#46726](https://github.com/laravel/framework/pull/46726))
- Fix nested join when not JoinClause instance ([#46712](https://github.com/laravel/framework/pull/46712))
- Fix query builder whereBetween method with carbon date period ([#46720](https://github.com/laravel/framework/pull/46720))

### Changed
- Removes unnecessary parameters in `creatable()` / `destroyable()` methods in `Illuminate/Routing/PendingSingletonResourceRegistration` ([#46677](https://github.com/laravel/framework/pull/46677))
- Return non-zero exit code for uncaught exceptions ([#46541](https://github.com/laravel/framework/pull/46541))

## [v10.6.2 (2023-04-05)](https://github.com/laravel/framework/compare/v10.6.1...v10.6.2)

### Added
- Added trait `Illuminate/Foundation/Testing/WithConsoleEvents` ([#46694](https://github.com/laravel/framework/pull/46694))

### Changed
- Added missing ignored methods to `Illuminate/View/Component` ([#46692](https://github.com/laravel/framework/pull/46692))
- console.stub: remove void return type from handle ([#46697](https://github.com/laravel/framework/pull/46697))

## [v10.6.1 (2023-04-04)](https://github.com/laravel/framework/compare/v10.6.0...v10.6.1)

### Reverted
- Reverted ["Set container instance on session manager"Set container instance on session manager](https://github.com/laravel/framework/pull/46621) ([#46691](https://github.com/laravel/framework/pull/46691))

## [v10.6.0 (2023-04-04)](https://github.com/laravel/framework/compare/v10.5.1...v10.6.0)

### Added
- Added ability to set a custom class for the AsCollection and AsEncryptedCollection casts ([#46619](https://github.com/laravel/framework/pull/46619))

### Changed
- Set container instance on session manager ([#46621](https://github.com/laravel/framework/pull/46621))
- Added empty string definition to Str::squish function ([#46660](https://github.com/laravel/framework/pull/46660))
- Allow $sleepMilliseconds parameter receive a Closure in retry method from PendingRequest ([#46653](https://github.com/laravel/framework/pull/46653))
- Support contextual binding on first class callables ([de8d515](https://github.com/laravel/framework/commit/de8d515fc6d1fabc8f14450342554e0eb67df725), [e511a3b](https://github.com/laravel/framework/commit/e511a3bdb15c294866428b4fe665a4ad14540038))

## [v10.5.1 (2023-03-29)](https://github.com/laravel/framework/compare/v10.5.0...v10.5.1)

### Added
- Added methods to determine if API resource has pivot loaded ([#46555](https://github.com/laravel/framework/pull/46555))
- Added caseSensitive flag to Stringable replace function ([#46578](https://github.com/laravel/framework/pull/46578))
- Allow insert..select (insertUsing()) to have empty $columns ([#46605](https://github.com/laravel/framework/pull/46605), [399bff9](https://github.com/laravel/framework/commit/399bff9331252e64a3439ea43e05f87f901dad55))
- Added `Illuminate/Database/Connection::selectResultSets()` ([#46592](https://github.com/laravel/framework/pull/46592))

### Changed
- Make sure pivot model has previously defined values ([#46559](https://github.com/laravel/framework/pull/46559))
- Move SetUniqueIds to run before the creating event ([#46622](https://github.com/laravel/framework/pull/46622))

## [v10.5.0 (2023-03-28)](https://github.com/laravel/framework/compare/v10.4.1...v10.5.0)

### Added
- Added `Illuminate/Cache/CacheManager::setApplication()` ([#46594](https://github.com/laravel/framework/pull/46594))

### Fixed
- Fix infinite loading on batches list on Horizon ([#46536](https://github.com/laravel/framework/pull/46536))
- Fix whereNull queries with raw expressions for the MySql grammar ([#46538](https://github.com/laravel/framework/pull/46538))
- Fix getDirty method when using AsEnumArrayObject / AsEnumCollection ([#46561](https://github.com/laravel/framework/pull/46561))

### Changed
- Skip `Illuminate/Support/Reflector::isParameterBackedEnumWithStringBackingType` for non ReflectionNamedType ([#46511](https://github.com/laravel/framework/pull/46511))
- Replace Deprecated DBAL Comparator creation with schema aware Comparator ([#46517](https://github.com/laravel/framework/pull/46517))
- Added Storage::json() method to read and decode a json file ([#46548](https://github.com/laravel/framework/pull/46548))
- Force cast json decoded failed_job_ids to array in DatabaseBatchRepository ([#46581](https://github.com/laravel/framework/pull/46581))
- Handle empty arrays for DynamoDbStore multi-key operations ([#46579](https://github.com/laravel/framework/pull/46579))
- Stop adding constraints twice on *Many to *One relationships via one() ([#46575](https://github.com/laravel/framework/pull/46575))
- allow override of the Builder paginate() total ([#46415](https://github.com/laravel/framework/pull/46415))
- Add a possibility to set a custom on_stats function for the Http Facade ([#46569](https://github.com/laravel/framework/pull/46569))

## [v10.4.1 (2023-03-18)](https://github.com/laravel/framework/compare/v10.4.0...v10.4.1)

### Changed
- Move Symfony events dispatcher registration to Console\Kernel ([#46508](https://github.com/laravel/framework/pull/46508))

## [v10.4.0 (2023-03-17)](https://github.com/laravel/framework/compare/v10.3.3...v10.4.0)

### Added
- Added `Illuminate/Testing/Concerns/AssertsStatusCodes::assertUnsupportedMediaType()` ([#46426](https://github.com/laravel/framework/pull/46426))
- Added curl_error_code: 77 to DetectsLostConnections ([#46429](https://github.com/laravel/framework/pull/46429))
- Allow for converting a HasMany to HasOne && MorphMany to MorphOne ([#46443](https://github.com/laravel/framework/pull/46443))
- Add option to create macroable method for paginationInformation ([#46461](https://github.com/laravel/framework/pull/46461))
- Added `Illuminate/Filesystem/Filesystem::json()` ([#46481](https://github.com/laravel/framework/pull/46481))

### Fixed
- Fix parsed input arguments for command events using dispatcher rerouting ([#46442](https://github.com/laravel/framework/pull/46442))
- Fix enums uses with optional implicit parameters ([#46483](https://github.com/laravel/framework/pull/46483))
- Fix deprecations for embedded images in symfony mailer ([#46488](https://github.com/laravel/framework/pull/46488))

### Changed
- Added alternative database port in Postgres DSN ([#46403](https://github.com/laravel/framework/pull/46403))
- Allow calling getControllerClass on closure-based routes ([#46411](https://github.com/laravel/framework/pull/46411))
- Remove obsolete method_exists(ReflectionClass::class, 'isEnum') call ([#46445](https://github.com/laravel/framework/pull/46445))
- Convert eloquent builder to base builder in whereExists ([#46460](https://github.com/laravel/framework/pull/46460))
- Refactor shared static methodExcludedByOptions method to trait ([#46498](https://github.com/laravel/framework/pull/46498))

## [v10.3.3 (2023-03-09)](https://github.com/laravel/framework/compare/v10.3.2...v10.3.3)

### Reverted
- Reverted ["Allow override of the Builder paginate() total"](https://github.com/laravel/framework/pull/46336) ([#46406](https://github.com/laravel/framework/pull/46406))

## [v10.3.2 (2023-03-08)](https://github.com/laravel/framework/compare/v10.3.1...v10.3.2)

### Reverted
- Reverted ["FIX on CanBeOneOfMany trait giving erroneous results"](https://github.com/laravel/framework/pull/46309) ([#46402](https://github.com/laravel/framework/pull/46402))

### Fixed
- Fixes Expression no longer implements Stringable ([#46395](https://github.com/laravel/framework/pull/46395))

## [v10.3.1 (2023-03-08)](https://github.com/laravel/framework/compare/v10.3.0...v10.3.1)

### Reverted
- Reverted ["Use fallback when previous URL is the same as the current in `Illuminate/Routing/UrlGenerator::previous()`"](https://github.com/laravel/framework/pull/46234) ([#46392](https://github.com/laravel/framework/pull/46392))

## [v10.3.0 (2023-03-07)](https://github.com/laravel/framework/compare/v10.2.0...v10.3.0)

### Added
- Adding Pipeline Facade ([#46271](https://github.com/laravel/framework/pull/46271))
- Add Support for SaveQuietly and Upsert with UUID/ULID Primary Keys ([#46161](https://github.com/laravel/framework/pull/46161))
- Add charAt method to both Str and Stringable ([#46349](https://github.com/laravel/framework/pull/46349), [dfb59bc2](https://github.com/laravel/framework/commit/dfb59bc263a4e28ac8992deeabd2ccd9392d1681))
- Adds Countable to the InvokedProcessPool class ([#46346](https://github.com/laravel/framework/pull/46346))
- Add processors to logging (placeholders) ([#46344](https://github.com/laravel/framework/pull/46344))

### Fixed
- Fixed `Illuminate/Mail/Mailable::buildMarkdownView()` ([791f8ea7](https://github.com/laravel/framework/commit/791f8ea70b5872ae4483a32f6aeb28dd2ed4b8d7))
- FIX on CanBeOneOfMany trait giving erroneous results ([#46309](https://github.com/laravel/framework/pull/46309))

### Changed
- Use fallback when previous URL is the same as the current in `Illuminate/Routing/UrlGenerator::previous()` ([#46234](https://github.com/laravel/framework/pull/46234))
- Allow override of the Builder paginate() total ([#46336](https://github.com/laravel/framework/pull/46336))

## [v10.2.0 (2023-03-02)](https://github.com/laravel/framework/compare/v10.1.5...v10.2.0)

### Added
- Adding `Conditionable` train to Logger ([#46259](https://github.com/laravel/framework/pull/46259))
- Added "dot" method to Illuminate\Support\Collection class ([#46265](https://github.com/laravel/framework/pull/46265))
- Added a "channel:list" command ([#46248](https://github.com/laravel/framework/pull/46248))
- Added JobPopping and JobPopped events ([#46220](https://github.com/laravel/framework/pull/46220))
- Add isMatch method to Str and Stringable helpers ([#46303](https://github.com/laravel/framework/pull/46303))
- Add ArrayAccess to Stringable ([#46279](https://github.com/laravel/framework/pull/46279))

### Reverted
- Revert "[10.x] Fix custom themes not reseting on Markdown renderer" ([#46328](https://github.com/laravel/framework/pull/46328))

### Fixed
- Fix typo in function `createMissingSqliteDatbase` name in `src/Illuminate/Database/Console/Migrations/MigrateCommand.php` ([#46326](https://github.com/laravel/framework/pull/46326))

### Changed
- Generate default command name based on class name in `ConsoleMakeCommand` ([#46256](https://github.com/laravel/framework/pull/46256))
- Do not mutate underlying values on redirect ([#46281](https://github.com/laravel/framework/pull/46281))
- Do not use null to initialise $lastExecutionStartedAt in `ScheduleWorkCommand` ([#46285](https://github.com/laravel/framework/pull/46285))
- Remove obsolete function_exists('enum_exists') calls ([#46319](https://github.com/laravel/framework/pull/46319))
- Cast json decoded failed_job_ids to array in DatabaseBatchRepository::toBatch ([#46329](https://github.com/laravel/framework/pull/46329))

## [v10.1.5 (2023-02-24)](https://github.com/laravel/framework/compare/v10.1.4...v10.1.5)

### Fixed
- Fixed `Illuminate/Foundation/Testing/Concerns/InteractsWithDatabase::expectsDatabaseQueryCount()` $connection parameter ([#46228](https://github.com/laravel/framework/pull/46228))
- Fixed Facade Fake ([#46257](https://github.com/laravel/framework/pull/46257))

### Changed
- Remove autoload dumping from make:migration ([#46215](https://github.com/laravel/framework/pull/46215))

## [v10.1.4 (2023-02-23)](https://github.com/laravel/framework/compare/v10.1.3...v10.1.4)

### Changed
- Improve Facade Fake Awareness ([#46188](https://github.com/laravel/framework/pull/46188), [#46232](https://github.com/laravel/framework/pull/46232))

## [v10.1.3 (2023-02-22)](https://github.com/laravel/framework/compare/v10.1.2...v10.1.3)

### Added
- Added protected method `Illuminate/Http/Resources/Json/JsonResource::newCollection()` for simplifies collection customisation ([#46217](https://github.com/laravel/framework/pull/46217))

### Fixed
- Fixes constructable migrations ([#46223](https://github.com/laravel/framework/pull/46223))

### Changes
- Accept time when generating ULID in `Str::ulid()` ([#46201](https://github.com/laravel/framework/pull/46201))

## [v10.1.2 (2023-02-22)](https://github.com/laravel/framework/compare/v10.1.1...v10.1.2)

### Reverted
- Revert changes from `Arr::random()` ([cf3eb90](https://github.com/laravel/framework/commit/cf3eb90a6473444bb7a78d1a3af1e9312a62020d))

## [v10.1.1 (2023-02-21)](https://github.com/laravel/framework/compare/v10.1.0...v10.1.1)

### Added
- Add the ability to re-resolve cache drivers ([#46203](https://github.com/laravel/framework/pull/46203))

### Fixed
- Fixed `Illuminate/Collections/Arr::shuffle()` for empty array ([0c6cae0](https://github.com/laravel/framework/commit/0c6cae0ef647158b9554cad05ff39db7e7ad0d33))

## [v10.1.0 (2023-02-21)](https://github.com/laravel/framework/compare/v10.0.3...v10.1.0)

### Fixed
- Fixing issue where 0 is discarded as a valid timestamp ([#46158](https://github.com/laravel/framework/pull/46158))
- Fix custom themes not reseting on Markdown renderer ([#46200](https://github.com/laravel/framework/pull/46200))

### Changed
- Use secure randomness in Arr:random and Arr:shuffle ([#46105](https://github.com/laravel/framework/pull/46105))
- Use mixed return type on controller stubs ([#46166](https://github.com/laravel/framework/pull/46166))
- Use InteractsWithDictionary in Eloquent collection ([#46196](https://github.com/laravel/framework/pull/46196))

## [v10.0.3 (2023-02-17)](https://github.com/laravel/framework/compare/v10.0.2...v10.0.3)

### Added
- Added missing expression support for pluck in Builder ([#46146](https://github.com/laravel/framework/pull/46146))

## [v10.0.2 (2023-02-16)](https://github.com/laravel/framework/compare/v10.0.1...v10.0.2)

### Added
- Register policies automatically to the gate ([#46132](https://github.com/laravel/framework/pull/46132))

## [v10.0.1 (2023-02-16)](https://github.com/laravel/framework/compare/v10.0.0...v10.0.1)

### Added
- Standard Input can be applied to PendingProcess ([#46119](https://github.com/laravel/framework/pull/46119))

### Fixed
- Fix Expression string casting ([#46137](https://github.com/laravel/framework/pull/46137))

### Changed
- Add AddQueuedCookiesToResponse to middlewarePriority so it is handled in the right place ([#46130](https://github.com/laravel/framework/pull/46130))
- Show queue connection in MonitorCommand ([#46122](https://github.com/laravel/framework/pull/46122))

## [v10.0.0 (2023-02-14)](https://github.com/laravel/framework/compare/v10.0.0...10.x)

Please consult the [upgrade guide](https://laravel.com/docs/10.x/upgrade) and [release notes](https://laravel.com/docs/10.x/releases) in the official Laravel documentation.
