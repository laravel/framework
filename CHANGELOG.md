# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v9.52.0...10.x)

## [v10.0.0 (2023-02-14)](https://github.com/laravel/framework/compare/v10.0.0...10.x)

Please consult the [upgrade guide](https://laravel.com/docs/10.x/upgrade) and [release notes](https://laravel.com/docs/10.x/releases) in the official Laravel documentation.

## [v9.52.0](https://github.com/laravel/framework/compare/v10.0.0..10.x...v9.52.0) - 2023-02-14

### Added

- Added methods to Enumerable contract ([#46021](https://github.com/laravel/framework/pull/46021))
- Added new mailer transport for AWS SES V2 API ([#45977](https://github.com/laravel/framework/pull/45977))
- Add S3 temporaryUploadUrl method to AwsS3V3Adapter ([#45753](https://github.com/laravel/framework/pull/45753))
- Add index hinting support to query builder ([#46063](https://github.com/laravel/framework/pull/46063))
- Add mailer name to data for SentMessage and MessageSending events ([#46079](https://github.com/laravel/framework/pull/46079))
- Added --pending option to migrate:status ([#46089](https://github.com/laravel/framework/pull/46089))

### Fixed

- Fixed pdo exception when rollbacking without active transaction ([#46017](https://github.com/laravel/framework/pull/46017))
- Fix duplicated columns on select ([#46049](https://github.com/laravel/framework/pull/46049))
- Fixes memory leak on anonymous migrations ([№46073](https://github.com/laravel/framework/pull/46073))
- Fixed race condition in locks issued by the file cache driver ([#46011](https://github.com/laravel/framework/pull/46011))

### Changed

- Allow choosing tables to truncate in `Illuminate/Foundation/Testing/DatabaseTruncation::truncateTablesForConnection()` ([#46025](https://github.com/laravel/framework/pull/46025))
- Update afterPromptingForMissingArguments method ([#46052](https://github.com/laravel/framework/pull/46052))
- Accept closure in bus assertion helpers ([#46075](https://github.com/laravel/framework/pull/46075))
- Avoid mutating the $expectedLitener between loops on Event::assertListening ([#46095](https://github.com/laravel/framework/pull/46095))
