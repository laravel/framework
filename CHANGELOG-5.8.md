# Release Notes for 5.8.x

## [Unreleased](https://github.com/laravel/framework/compare/v5.8.2...5.8)

### Fixed
- Fixed environment variable parsing ([#27706](https://github.com/laravel/framework/pull/27706))
- Fixed guessed policy names when using `Gate::forUser` ([#27708](https://github.com/laravel/framework/pull/27708))
- Fixed `via` as `string` in the `Notification` ([#27710](https://github.com/laravel/framework/pull/27710))

### Changed
- Check if `MessageBag` is empty before checking keys exist in the `MessageBag` ([#27719](https://github.com/laravel/framework/pull/27719))

### TODO
- https://github.com/laravel/framework/pull/27726, https://github.com/laravel/framework/commit/bc884bb30e3dc12545ab63cea1f5a74b33dab59c


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
