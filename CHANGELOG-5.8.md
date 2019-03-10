# Release Notes for 5.8.x

## [Unreleased](https://github.com/laravel/framework/compare/v5.8.3...5.8)

### Added
- Added `Illuminate\Support\Collection::join()` method ([#27723](https://github.com/laravel/framework/pull/27723))


## [v5.8.3 (2019-03-05)](https://github.com/laravel/framework/compare/v5.8.2...v5.8.3)

### Added
- Added `Collection::countBy` ([#27770](https://github.com/laravel/framework/pull/27770))
- Added protected `EloquentUserProvider::newModelQuery()` ([#27734](https://github.com/laravel/framework/pull/27734), [9bb7685](https://github.com/laravel/framework/commit/9bb76853403fcb071b9454f1dc0369a8b42c3257))
- Added protected `StartSession::saveSession()` method ([#27771](https://github.com/laravel/framework/pull/27771), [76c7126](https://github.com/laravel/framework/commit/76c7126641e781fa30d819834f07149dda4e01e6))
- Allow `belongsToMany` to take `Model/Pivot` class name as a second parameter ([#27774](https://github.com/laravel/framework/pull/27774))

### Fixed
- Fixed environment variable parsing ([#27706](https://github.com/laravel/framework/pull/27706))
- Fixed guessed policy names when using `Gate::forUser` ([#27708](https://github.com/laravel/framework/pull/27708))
- Fixed `via` as `string` in the `Notification` ([#27710](https://github.com/laravel/framework/pull/27710))
- Fixed `StartSession` middleware ([499e4fe](https://github.com/laravel/framework/commit/499e4fefefc4f8c0fe6377297b575054ec1d476f))
- Fixed `stack` channel's bug related to the `level` ([#27726](https://github.com/laravel/framework/pull/27726), [bc884bb](https://github.com/laravel/framework/commit/bc884bb30e3dc12545ab63cea1f5a74b33dab59c))
- Fixed `email` validation for not string values ([#27735](https://github.com/laravel/framework/pull/27735))

### Changed
- Check if `MessageBag` is empty before checking keys exist in the `MessageBag` ([#27719](https://github.com/laravel/framework/pull/27719))


## [v5.8.2 (2019-02-27)](https://github.com/laravel/framework/compare/v5.8.1...v5.8.2)

### Fixed
- Fixed quoted environment variable parsing ([#27691](https://github.com/laravel/framework/pull/27691))


## [v5.8.1 (2019-02-27)](https://github.com/laravel/framework/compare/v5.8.0...v5.8.1)

### Added
- Added `Illuminate\View\FileViewFinder::setPaths()` ([#27678](https://github.com/laravel/framework/pull/27678))

### Changed
- Return fake objects from facades ([#27680](https://github.com/laravel/framework/pull/27680))

### Reverted
- reverted changes related to the `Facade` ([63d87d7](https://github.com/laravel/framework/commit/63d87d78e08cc502947f07ebbfa4993955339c5a))


## [v5.8.0 (2019-02-26)](https://github.com/laravel/framework/compare/5.7...v5.8.0)

Check the upgrade guide in the [Official Laravel Documentation](https://laravel.com/docs/5.8/upgrade).
