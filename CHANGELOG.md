# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v10.3.3...10.x)


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
