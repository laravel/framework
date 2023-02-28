# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v10.1.5...10.x)


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
