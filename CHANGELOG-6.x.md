# Release Notes for 6.x

## [Unreleased](https://github.com/laravel/framework/compare/v6.20.44...6.x)


## [v6.20.44 (2022-01-12)](https://github.com/laravel/framework/compare/v6.20.43...v6.20.44)

### Fixed
- Fixed digits_between with fractions ([#40278](https://github.com/laravel/framework/pull/40278))


## [v6.20.43 (2021-12-14)](https://github.com/laravel/framework/compare/v6.20.42...v6.20.43)

### Fixed
- Fixed inconsistent escaping of artisan argument ([#39953](https://github.com/laravel/framework/pull/39953))

### Changed
- Do not return anything `Illuminate/Foundation/Application::afterLoadingEnvironment()`


## [v6.20.42 (2021-12-07)](https://github.com/laravel/framework/compare/v6.20.41...v6.20.42)

### Fixed
- Fixed for dropping columns when using MSSQL as  ([#39905](https://github.com/laravel/framework/pull/39905))
- Fixed parent call in View ([#39908](https://github.com/laravel/framework/pull/39908))


## [v6.20.41 (2021-11-23)](https://github.com/laravel/framework/compare/v6.20.40...v6.20.41)

### Added
- Added phar to list of shouldBlockPhpUpload() in validator ([2d1f76a](https://github.com/laravel/framework/commit/2d1f76ab752ced011da05cf139799eab2a36ef90))


## [v6.20.40 (2021-11-17)](https://github.com/laravel/framework/compare/v6.20.39...v6.20.40)

### Fixed
- Fixes `Illuminate/Database/Query/Builder::limit()` to only cast integer when given other than null ([#39644](https://github.com/laravel/framework/pull/39644))


## [v6.20.39 (2021-11-16)](https://github.com/laravel/framework/compare/v6.20.38...v6.20.39)

### Fixed
- Fixed $value in `Illuminate/Database/Query/Builder::limit()` ([ddfa71e](https://github.com/laravel/framework/commit/ddfa71ee9f101394b4ff682471bc31a7ba6de5cf))


## [v6.20.38 (2021-11-09)](https://github.com/laravel/framework/compare/v6.20.37...v6.20.38)

### Added
- Added new lost connection error message for sqlsrv ([#39466](https://github.com/laravel/framework/pull/39466))


## [v6.20.37 (2021-11-02)](https://github.com/laravel/framework/compare/v6.20.36...v6.20.37)

### Fixed
- Fixed rate limiting unicode issue ([#39375](https://github.com/laravel/framework/pull/39375))


## [v6.20.36 (2021-10-19)](https://github.com/laravel/framework/compare/v6.20.35...v6.20.36)

### Fixed
- Add new lost connection message to DetectsLostConnections for Vapor ([#39209](https://github.com/laravel/framework/pull/39209))


## [v6.20.35 (2021-10-05)](https://github.com/laravel/framework/compare/v6.20.34...v6.20.35)

### Added
- Added new lost connection message to DetectsLostConnections ([#39028](https://github.com/laravel/framework/pull/39028))


## [v6.20.34 (2021-09-07)](https://github.com/laravel/framework/compare/v6.20.33...v6.20.34)

### Fixed
- Silence validator date parse warnings ([#38670](https://github.com/laravel/framework/pull/38670))


## [v6.20.33 (2021-08-31)](https://github.com/laravel/framework/compare/v6.20.32...v6.20.33)

### Changed
- Error out when detecting incompatible DBAL version ([#38543](https://github.com/laravel/framework/pull/38543))


## [v6.20.32 (2021-08-10)](https://github.com/laravel/framework/compare/v6.20.31...v6.20.32)

### Fixed
- Bump AWS PHP SDK ([#38297](https://github.com/laravel/framework/pull/38297))


## [v6.20.31 (2021-08-03)](https://github.com/laravel/framework/compare/v6.20.30...v6.20.31)
 
### Fixed
- Fixed signed routes with expires parameter ([#38111](https://github.com/laravel/framework/pull/38111), [732c0e0](https://github.com/laravel/framework/commit/732c0e0f64b222e7fc7daef6553f8e99007bb32c))

### Refactoring
- Remove hardcoded Carbon reference from scheduler event ([#38063](https://github.com/laravel/framework/pull/38063))


## [v6.20.30 (2021-07-07)](https://github.com/laravel/framework/compare/v6.20.29...v6.20.30)

### Fixed
- Fix edge case causing a BadMethodCallExceptions to be thrown when using loadMissing() ([#37871](https://github.com/laravel/framework/pull/37871))


## [v6.20.29 (2021-06-22)](https://github.com/laravel/framework/compare/v6.20.28...v6.20.29)

### Changed
- Removed unnecessary checks in RequiredIf validation, fixed tests ([#37700](https://github.com/laravel/framework/pull/37700))


## [v6.20.28 (2021-06-15)](https://github.com/laravel/framework/compare/v6.20.27...v6.20.28)

### Fixed
- Fixed dns_get_record loose check of A records for active_url rule ([#37675](https://github.com/laravel/framework/pull/37675))
- Type hinted arguments for Illuminate\Validation\Rules\RequiredIf ([#37688](https://github.com/laravel/framework/pull/37688))
- Fixed when passed object as parameters to scopes method ([#37692](https://github.com/laravel/framework/pull/37692))


## [v6.20.27 (2021-05-11)](https://github.com/laravel/framework/compare/v6.20.26...v6.20.27)

### Added
- Support mass assignment to SQL Server views ([#37307](https://github.com/laravel/framework/pull/37307))

### Fixed
- Fixed `Illuminate\Database\Query\Builder::offset()` with non numbers $value ([#37164](https://github.com/laravel/framework/pull/37164))
- Fixed unless rules ([#37291](https://github.com/laravel/framework/pull/37291))

### Changed
- Allow reporting reportable exceptions with the default logger ([#37235](https://github.com/laravel/framework/pull/37235))


## [v6.20.26 (2021-04-28)](https://github.com/laravel/framework/compare/v6.20.25...v6.20.26)

### Fixed
- Fixed Cache store with a name other than 'dynamodb' ([#37145](https://github.com/laravel/framework/pull/37145))

### Changed
- Some cast to int in `Illuminate\Database\Query\Grammars\SqlServerGrammar` ([09bf145](https://github.com/laravel/framework/commit/09bf1457e9df53e172e6fd5929cbafb539677c7c))


## [v6.20.25 (2021-04-27)](https://github.com/laravel/framework/compare/v6.20.24...v6.20.25)

### Fixed
- Fixed nullable values for required_if ([#37128](https://github.com/laravel/framework/pull/37128), [86fd558](https://github.com/laravel/framework/commit/86fd558b4e5d8d7d45cf457cd1a72d54334297a1))


## [v6.20.24 (2021-04-20)](https://github.com/laravel/framework/compare/v6.20.23...v6.20.24)

### Fixed
- Fixed required_if boolean validation ([#36969](https://github.com/laravel/framework/pull/36969))


## [v6.20.23 (2021-04-13)](https://github.com/laravel/framework/compare/v6.20.22...v6.20.23)

### Added 
- Added strings to the `DetectsLostConnections.php` ([4210258](https://github.com/laravel/framework/commit/42102589bc7f7b8533ee1b815ef0cc18017d4e45))


## [v6.20.22 (2021-03-31)](https://github.com/laravel/framework/compare/v6.20.21...v6.20.22)

### Fixed
- Fixed setting DynamoDB credentials ([#36822](https://github.com/laravel/framework/pull/36822))


## [v6.20.21 (2021-03-30)](https://github.com/laravel/framework/compare/v6.20.20...v6.20.21)

### Added
- Added support of DynamoDB in CI suite ([#36749](https://github.com/laravel/framework/pull/36749))
- Support username parameter for predis ([#36762](https://github.com/laravel/framework/pull/36762))

### Changed
- Use qualified column names in pivot query ([#36720](https://github.com/laravel/framework/pull/36720))


## [v6.20.20 (2021-03-23)](https://github.com/laravel/framework/compare/v6.20.19...v6.20.20)

### Added
- Added WSREP communication link failure for lost connection detection ([#36668](https://github.com/laravel/framework/pull/36668))

### Fixed
- Fixes the issue using cache:clear with PhpRedis and a clustered Redis instance. ([#36665](https://github.com/laravel/framework/pull/36665))


## [v6.20.19 (2021-03-16)](https://github.com/laravel/framework/compare/v6.20.18...v6.20.19)

### Added
- Added broken pipe exception as lost connection error ([#36601](https://github.com/laravel/framework/pull/36601))


## [v6.20.18 (2021-03-09)](https://github.com/laravel/framework/compare/v6.20.17...v6.20.18)

### Fixed
- Fix validator treating null as true for (required|exclude)_(if|unless) due to loose `in_array()` check ([#36504](https://github.com/laravel/framework/pull/36504))

### Changed
- Delete existing links that are broken in `Illuminate\Foundation\Console\StorageLinkCommand` ([#36470](https://github.com/laravel/framework/pull/36470))


## [v6.20.17 (2021-03-02)](https://github.com/laravel/framework/compare/v6.20.16...v6.20.17)

### Added
- Added new line to `DetectsLostConnections` ([#36373](https://github.com/laravel/framework/pull/36373))


## [v6.20.16 (2021-02-02)](https://github.com/laravel/framework/compare/v6.20.15...v6.20.16)

### Fixed
- Fixed `Illuminate\View\ViewException::report()` ([#36110](https://github.com/laravel/framework/pull/36110))
- Fixed `Illuminate\Redis\Connections\PhpRedisConnection::spop()` ([#36106](https://github.com/laravel/framework/pull/36106))

### Changed
- Typecast page number as integer in `Illuminate\Pagination\AbstractPaginator::resolveCurrentPage()` ([#36055](https://github.com/laravel/framework/pull/36055))


## [v6.20.15 (2021-01-26)](https://github.com/laravel/framework/compare/v6.20.14...v6.20.15)

### Changed
- Pipe new through render and report exception methods ([#36037](https://github.com/laravel/framework/pull/36037))


## [v6.20.14 (2021-01-21)](https://github.com/laravel/framework/compare/v6.20.13...v6.20.14)

### Fixed
- Fixed type error in `Illuminate\Http\Concerns\InteractsWithContentTypes::isJson()` ([#35956](https://github.com/laravel/framework/pull/35956))
- Limit expected bindings ([#35972](https://github.com/laravel/framework/pull/35972), [006873d](https://github.com/laravel/framework/commit/006873df411d28bfd03fea5e7f91a2afe3918498))


## [v6.20.13 (2021-01-19)](https://github.com/laravel/framework/compare/v6.20.12...v6.20.13)

### Fixed
- Fixed empty html mail ([#35941](https://github.com/laravel/framework/pull/35941))


## [v6.20.12 (2021-01-13)](https://github.com/laravel/framework/compare/v6.20.11...v6.20.12)


## [v6.20.11 (2021-01-13)](https://github.com/laravel/framework/compare/v6.20.10...v6.20.11)

### Fixed
- Limit expected bindings ([#35865](https://github.com/laravel/framework/pull/35865))


## [v6.20.10 (2021-01-12)](https://github.com/laravel/framework/compare/v6.20.9...v6.20.10)

### Added
- Added new line to `DetectsLostConnections` ([#35790](https://github.com/laravel/framework/pull/35790))

### Fixed
- Fixed error from missing null check on PHP 8 in `Illuminate\Validation\Concerns\ValidatesAttributes::validateJson()` ([#35797](https://github.com/laravel/framework/pull/35797))


## [v6.20.9 (2021-01-05)](https://github.com/laravel/framework/compare/v6.20.8...v6.20.9)

### Added
- [Updated Illuminate\Database\DetectsLostConnections with new strings](https://github.com/laravel/framework/compare/v6.20.8...v6.20.9) 


## [v6.20.8 (2020-12-22)](https://github.com/laravel/framework/compare/v6.20.7...v6.20.8)

### Fixed
- Fixed `Illuminate\Validation\Concerns\ValidatesAttributes::validateJson()` for PHP8 ([#35646](https://github.com/laravel/framework/pull/35646))
- Catch DecryptException with invalid X-XSRF-TOKEN in `Illuminate\Foundation\Http\Middleware\VerifyCsrfToken` ([#35671](https://github.com/laravel/framework/pull/35671))


## [v6.20.7 (2020-12-08)](https://github.com/laravel/framework/compare/v6.20.6...v6.20.7)

### Fixed
- Backport for fix issue with polymorphic morphMaps with literal 0 ([#35487](https://github.com/laravel/framework/pull/35487))
- Fixed mime validation for jpeg files ([#35518](https://github.com/laravel/framework/pull/35518))


## [v6.20.6 (2020-12-01)](https://github.com/laravel/framework/compare/v6.20.5...v6.20.6)

### Fixed
- Backport Redis context option ([#35370](https://github.com/laravel/framework/pull/35370))
- Fixed validating image/jpeg images after Symfony/Mime update ([#35419](https://github.com/laravel/framework/pull/35419))


## [v6.20.5 (2020-11-24)](https://github.com/laravel/framework/compare/v6.20.4...v6.20.5)

### Fixed
- Fixing BroadcastException message in PusherBroadcaster@broadcast ([#35290](https://github.com/laravel/framework/pull/35290))
- Fixed generic DetectsLostConnection string ([#35323](https://github.com/laravel/framework/pull/35323))

### Changed
- Updated `aws/aws-sdk-php` suggest to `^3.155` ([#35267](https://github.com/laravel/framework/pull/35267))


## [v6.20.4 (2020-11-17)](https://github.com/laravel/framework/compare/v6.20.3...v6.20.4)

### Fixed
- Fixed pivot restoration ([#35218](https://github.com/laravel/framework/pull/35218))


## [v6.20.3 (2020-11-10)](https://github.com/laravel/framework/compare/v6.20.2...v6.20.3)

### Fixed
- Turn the eloquent collection into a base collection if mapWithKeys loses models ([#35129](https://github.com/laravel/framework/pull/35129))


## [v6.20.2 (2020-10-29)](https://github.com/laravel/framework/compare/v6.20.1...v6.20.2)

### Fixed
- [Add some fixes](https://github.com/laravel/framework/compare/v6.20.1...v6.20.2) 


## [v6.20.1 (2020-10-29)](https://github.com/laravel/framework/compare/v6.20.0...v6.20.1)

### Fixed
- Fixed alias usage in `Eloquent` ([6091048](https://github.com/laravel/framework/commit/609104806b8b639710268c75c22f43034c2b72db))
- Fixed `Illuminate\Support\Reflector::isCallable()` ([a90f344](https://github.com/laravel/framework/commit/a90f344c66f0a5bb1d718f8bbd20c257d4de9e02))


## [v6.20.0 (2020-10-28)](https://github.com/laravel/framework/compare/v6.19.1...v6.20.0)

### Added
- Full PHP 8.0 Support ([#33388](https://github.com/laravel/framework/pull/33388))
- Added `Illuminate\Support\Reflector::isCallable()` ([#34994](https://github.com/laravel/framework/pull/34994), [8c16891](https://github.com/laravel/framework/commit/8c16891c6e7a4738d63788f4447614056ab5136e), [31917ab](https://github.com/laravel/framework/commit/31917abcfa0db6ec6221bb07fc91b6e768ff5ec8), [11cfa4d](https://github.com/laravel/framework/commit/11cfa4d4c92bf2f023544d58d51b35c5d31dece0), [#34999](https://github.com/laravel/framework/pull/34999))

### Changed
- Bump minimum PHP version to v7.2.5 ([#34928](https://github.com/laravel/framework/pull/34928))

### Fixed
- Fixed ambigious column on many to many with select load ([5007986](https://github.com/laravel/framework/commit/500798623d100a9746b2931ae6191cb756521f05))
  

## [v6.19.1 (2020-10-20)](https://github.com/laravel/framework/compare/v6.19.0...v6.19.1)

### Fixed
- Fixed `bound()` method ([a7759d7](https://github.com/laravel/framework/commit/a7759d70e15b0be946569b8299ac694c08a35d7e))


## [v6.19.0 (2020-10-20)](https://github.com/laravel/framework/compare/v6.18.43...v6.19.0)

### Added
- Provisional support for PHP 8.0 ([#34884](https://github.com/laravel/framework/pull/34884), [28bb76e](https://github.com/laravel/framework/commit/28bb76efbcfc5fee57307ffa062b67ff709240dc))


## [v6.18.43 (2020-10-13)](https://github.com/laravel/framework/compare/v6.18.42...v6.18.43)

### Fixed
- Matched `symfony/debug` version with other symfony reqs ([6ce02a2](https://github.com/laravel/framework/commit/6ce02a21cf736f28beda2529d1e28849e86b0944))


## [v6.18.42 (2020-10-06)](https://github.com/laravel/framework/compare/v6.18.41...v6.18.42)

### Fixed
- Added missed RESET_THROTTLED constant to Password Facade ([#34641](https://github.com/laravel/framework/pull/34641))


## [v6.18.41 (2020-09-29)](https://github.com/laravel/framework/compare/v6.18.40...v6.18.41)

### Fixed
- Added support for stream reads in FileManager for S3 driver ([#34480](https://github.com/laravel/framework/pull/34480))


## [v6.18.40 (2020-09-09)](https://github.com/laravel/framework/compare/v6.18.39...v6.18.40)

### Revert
- Revert of ["Fixed for empty fallback_locale in `Illuminate\Translation\Translator`"](https://github.com/laravel/framework/pull/34136) ([7c54eb6](https://github.com/laravel/framework/commit/7c54eb678d58fb9ee7f532a5a5842e6f0e1fe4c9))


## [v6.18.39 (2020-09-08)](https://github.com/laravel/framework/compare/v6.18.38...v6.18.39)

### Fixed
- Fixed for empty fallback_locale in `Illuminate\Translation\Translator` ([#34136](https://github.com/laravel/framework/pull/34136))


## [v6.18.38 (2020-09-01)](https://github.com/laravel/framework/compare/v6.18.37...v6.18.38)

### Changed
- Changed postgres processor ([#34055](https://github.com/laravel/framework/pull/34055))


## [v6.18.37 (2020-08-27)](https://github.com/laravel/framework/compare/v6.18.36...v6.18.37)

### Fixed
- Fixed offset error on invalid remember token ([#34020](https://github.com/laravel/framework/pull/34020))
- Only prepend scheme to PhpRedis host when necessary ([#34017](https://github.com/laravel/framework/pull/34017))
- Fixed `whereKey` and `whereKeyNot` in `Illuminate\Database\Eloquent\Builder` ([#34031](https://github.com/laravel/framework/pull/34031))


## [v6.18.36 (2020-08-25)](https://github.com/laravel/framework/compare/v6.18.35...v6.18.36)

### Fixed
- Fix dimension ratio calculation in `Illuminate\Validation\Concerns\ValidatesAttributes::failsRatioCheck()` ([#34003](https://github.com/laravel/framework/pull/34003))

### Changed
- Normalize scheme in Redis connections ([#33892](https://github.com/laravel/framework/pull/33892))
- Check no-interaction flag exists and is true for Artisan commands ([#33950](https://github.com/laravel/framework/pull/33950))


## [v6.18.35 (2020-08-07)](https://github.com/laravel/framework/compare/v6.18.34...v6.18.35)

### Changed
- Verify column names are actual columns when using guarded ([#33777](https://github.com/laravel/framework/pull/33777))


## [v6.18.34 (2020-08-06)](https://github.com/laravel/framework/compare/v6.18.33...v6.18.34)

### Fixed
- Fixed `Illuminate\Support\Arr::query()` ([c6f9ae2](https://github.com/laravel/framework/commit/c6f9ae2b6fdc3c1716938223de731b97f6a5a255))
- Don't allow mass filling with table names ([9240404](https://github.com/laravel/framework/commit/9240404b22ef6f9e827577b3753e4713ddce7471), [f5fa6e3](https://github.com/laravel/framework/commit/f5fa6e3a0fbf9a93eab45b9ae73265b4dbfc3ad7))


## [v6.18.33 (2020-08-06)](https://github.com/laravel/framework/compare/v6.18.32...v6.18.33)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Concerns\GuardsAttributes::isGuarded()` ([1b70bef](https://github.com/laravel/framework/commit/1b70bef5fd7cc5da74abcdf79e283f830fa3b0a4), [624d873](https://github.com/laravel/framework/commit/624d873733388aa2246553a3b465e38554953180), [b70876a](https://github.com/laravel/framework/commit/b70876ac80759fbf168c91cdffd7a2b2305e27cb))
- Fixed escaping quotes ([687df01](https://github.com/laravel/framework/commit/687df01fa19c99546c1ae1dd53c2a465459b50dc))


## [v6.18.32 (2020-08-04)](https://github.com/laravel/framework/compare/v6.18.31...v6.18.32)

### Changed
- Ignore numeric field names in validators ([#33712](https://github.com/laravel/framework/pull/33712))
- Fixed validation rule 'required_unless' when other field value is boolean. ([#33715](https://github.com/laravel/framework/pull/33715))


## [v6.18.31 (2020-07-27)](https://github.com/laravel/framework/compare/v6.18.30...v6.18.31)

### Update
- Update cookies encryption ([release](https://github.com/laravel/framework/compare/v6.18.30...v6.18.31))


## [v6.18.30 (2020-07-27)](https://github.com/laravel/framework/compare/v6.18.29...v6.18.30)

### Update
- Update cookies encryption ([release](https://github.com/laravel/framework/compare/v6.18.29...v6.18.30))


## [v6.18.29 (2020-07-27)](https://github.com/laravel/framework/compare/v6.18.28...v6.18.29)

### Fixed
- Fixed cookie issues encryption ([c9ce261](https://github.com/laravel/framework/commit/c9ce261a9f7b8e07c9ebc8a7d45651ee1cf86215), [5786aa4](https://github.com/laravel/framework/commit/5786aa4a388adfcc62862573275bd37d49aa07d7))


## [v6.18.28 (2020-07-27)](https://github.com/laravel/framework/compare/v6.18.27...v6.18.28)

### Fixed
- Fixed cookie issues ([bb9db21](https://github.com/laravel/framework/commit/bb9db21af137344feffa192fcabe4e439c8b0f60))


## [v6.18.27 (2020-07-27)](https://github.com/laravel/framework/compare/v6.18.26...v6.18.27)

### Fixed
- Don't decrement transaction below 0 in `Illuminate\Database\Concerns\ManagesTransactions::handleCommitTransactionException()` ([7681795](https://github.com/laravel/framework/commit/768179578e5492b5f80c391bd43b233938e16e27))
- Fixed transaction problems on closure transaction ([c4cdfc7](https://github.com/laravel/framework/commit/c4cdfc7c54127b772ef10f37cfc9ef8e9d6b3227))
- Prevent to serialize uninitialized properties ([#33644](https://github.com/laravel/framework/pull/33644))
- Fixed missing statement preventing deletion in `Illuminate\Database\Eloquent\Relations\MorphPivot::delete()` ([#33648](https://github.com/laravel/framework/pull/33648))

### Changed
- Improve cookie encryption ([#33662](https://github.com/laravel/framework/pull/33662))

This change will invalidate all existing cookies. Please see [this security bulletin](https://blog.laravel.com/laravel-cookie-security-releases) for more information.


## [v6.18.26 (2020-07-21)](https://github.com/laravel/framework/compare/v6.18.25...v6.18.26)

### Fixed
- Align (fix) nested arrays support for `assertViewHas` & `assertViewMissing` in `Illuminate\Testing\TestResponse` ([#33566](https://github.com/laravel/framework/pull/33566))


## [v6.18.25 (2020-07-10)](https://github.com/laravel/framework/compare/v6.18.24...v6.18.25)

### Fixed
- Fixed `Illuminate\Cache\FileStore::flush()` ([#33458](https://github.com/laravel/framework/pull/33458))
- Fixed auto creating model by class name ([#33481](https://github.com/laravel/framework/pull/33481))
- Don't return nested data from validator when failing an exclude rule ([#33435](https://github.com/laravel/framework/pull/33435))
- Fixed validation nested error messages ([6615371](https://github.com/laravel/framework/commit/6615371d7c0a7431372244d21eae54696b3c19f2))
- Fixed `Illuminate\Support\Reflector` to handle parent ([#33502](https://github.com/laravel/framework/pull/33502))
  
### Revert
- Revert [Improve SQL Server last insert id retrieval](https://github.com/laravel/framework/pull/33453) ([#33496](https://github.com/laravel/framework/pull/33496))


## [v6.18.24 (2020-07-07)](https://github.com/laravel/framework/compare/v6.18.23...v6.18.24)

### Fixed
- Fixed notifications database channel for anonymous notifiables ([#33409](https://github.com/laravel/framework/pull/33409))
- Added float comparison null checks ([#33421](https://github.com/laravel/framework/pull/33421))
- Improve SQL Server last insert id retrieval ([#33453](https://github.com/laravel/framework/pull/33453))


## [v6.18.23 (2020-06-30)](https://github.com/laravel/framework/compare/v6.18.22...v6.18.23)

### Fixed
- Fixed `ConfigurationUrlParser` query decoding ([#33340](https://github.com/laravel/framework/pull/33340))
- Correct implementation of float casting comparison ([#33322](https://github.com/laravel/framework/pull/33322))


## [v6.18.22 (2020-06-24)](https://github.com/laravel/framework/compare/v6.18.21...v6.18.22)

### Revert
- Revert "Fixed `Model::originalIsEquivalent()` with floats ([#33259](https://github.com/laravel/framework/pull/33259), [d68d915](https://github.com/laravel/framework/commit/d68d91516db6d1b9cba8a72f99b2c7e8223e988f))" [bf3cb6f](https://github.com/laravel/framework/commit/bf3cb6f6979df2d6965d2e0aa731724d0e2b15e5)


## [v6.18.21 (2020-06-23)](https://github.com/laravel/framework/compare/v6.18.20...v6.18.21)

### Fixed
- Fixed `Model::originalIsEquivalent()` with floats ([#33259](https://github.com/laravel/framework/pull/33259), [d68d915](https://github.com/laravel/framework/commit/d68d91516db6d1b9cba8a72f99b2c7e8223e988f))


## [v6.18.20 (2020-06-16)](https://github.com/laravel/framework/compare/v6.18.19...v6.18.20)

### Changed
- Improved the reflector ([#33184](https://github.com/laravel/framework/pull/33184))


## [v6.18.19 (2020-06-09)](https://github.com/laravel/framework/compare/v6.18.18...v6.18.19)

### Fixed
- Fixed `Model::withoutEvents()` not registering listeners inside boot() ([#33149](https://github.com/laravel/framework/pull/33149), [4bb32ae](https://github.com/laravel/framework/commit/4bb32aea50eec4c3cc8b77f463e4a96213a0af09))


## [v6.18.18 (2020-06-03)](https://github.com/laravel/framework/compare/v6.18.17...v6.18.18)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Relations\MorphToMany::getCurrentlyAttachedPivots()` ([110b129](https://github.com/laravel/framework/commit/110b129531df172f03bf163f561c71123fac6296))


## [v6.18.17 (2020-06-02)](https://github.com/laravel/framework/compare/v6.18.16...v6.18.17)

### Added
- Support PHP 8's reflection API ([#33039](https://github.com/laravel/framework/pull/33039))

### Fixed
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` ([00e9ed7](https://github.com/laravel/framework/commit/00e9ed76483ea6ad1264676e7b1095b23e16a433))
- Fixed bug with update existing pivot and polymorphic many to many ([684208b](https://github.com/laravel/framework/commit/684208b10460b49fa34354cc42f33b9b7135814f))


## [v6.18.15 (2020-05-19)](https://github.com/laravel/framework/compare/v6.18.14...v6.18.15)

### Added
- Added `Illuminate\Http\Middleware\TrustHosts` ([9229264](https://github.com/laravel/framework/commit/92292649621f2aadc84ab94376244650a9f55696))

### Fixed
- Revert of ["Remove `strval` from `Illuminate/Validation/ValidationRuleParser::explodeWildcardRules()`"](https://github.com/laravel/framework/commit/1c76a6f3a80fa8f756740566dffd9fa1be65c123) ([52940cf](https://github.com/laravel/framework/commit/52940cf3275cfebd47ec008fd8ae5bc6d6a42dfd))
- Fixed Queued Mail MessageSent Listener With Attachments ([#32795](https://github.com/laravel/framework/pull/32795))
- Added error clearing before sending in `Illuminate\Mail\Mailer::sendSwiftMessage()` ([#32799](https://github.com/laravel/framework/pull/32799))
- Avoid foundation function call in the auth component ([#32805](https://github.com/laravel/framework/pull/32805))

### Changed
- Added explicit `symfony/polyfill-php73` dependency ([5796b1e](https://github.com/laravel/framework/commit/5796b1e43dfe14914050a7e5dd24ddf803ec99b8))
- Set `Cache\FileStore` file permissions only once ([#32845](https://github.com/laravel/framework/pull/32845), [11c533b](https://github.com/laravel/framework/commit/11c533b9aa062f4cba1dd0fe3673bf33d275480f))


## [v6.18.14 (2020-05-12)](https://github.com/laravel/framework/compare/v6.18.13...v6.18.14)

### Added
- Added SSL SYSCALL EOF as a lost connection message ([#32697](https://github.com/laravel/framework/pull/32697))

### Fixed
- Fixed `FakerGenerator` Unique caching issue ([#32703](https://github.com/laravel/framework/pull/32703))
- Added boolean to types that don't need character options ([#32716](https://github.com/laravel/framework/pull/32716))
- Fixed `Illuminate\Foundation\Testing\PendingCommand` that do not resolve 'OutputStyle::class' from the container ([#32687](https://github.com/laravel/framework/pull/32687))
- Clear resolved event facade on `Illuminate\Foundation\Testing\Concerns\MocksApplicationServices::withoutEvents()` ([d1e7f85](https://github.com/laravel/framework/commit/d1e7f85dfd79abbe4f5e01818f620f6ecc67de4d))
- Fixed deprecated "Doctrine/Common/Inflector/Inflector" class ([#32734](https://github.com/laravel/framework/pull/32734))

### Changed
- Remove the undocumented dot keys support in validators ([#32764](https://github.com/laravel/framework/pull/32764))
- Remove `strval` from `Illuminate/Validation/ValidationRuleParser::explodeWildcardRules()` [1c76a6f](https://github.com/laravel/framework/commit/1c76a6f3a80fa8f756740566dffd9fa1be65c123)


## [v6.18.13 (2020-05-05)](https://github.com/laravel/framework/compare/v6.18.12...v6.18.13)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` ([7b32460](https://github.com/laravel/framework/commit/7b32469420258e9e52b24b2ffa7f491e79a3a870))


## [v6.18.12 (2020-05-05)](https://github.com/laravel/framework/compare/v6.18.11...v6.18.12)

### Added
- Add pdo try again as lost connection message ([#32605](https://github.com/laravel/framework/pull/32605))

### Fixed
- Fixed `Illuminate\Foundation\Testing\TestResponse::assertSessionHasInput()` ([f0639fd](https://github.com/laravel/framework/commit/f0639fda45fc2874986fe409d944dde21d42c6f3))
- Set relation connection on eager loaded MorphTo ([#32602](https://github.com/laravel/framework/pull/32602))
- Fixed `Illuminate\Database\Schema\Grammars\SqlServerGrammar::compileDropDefaultConstraint()` was ignoring Table prefixes ([#32606](https://github.com/laravel/framework/pull/32606))
- Filtering null's in `hasMorph()` ([#32614](https://github.com/laravel/framework/pull/32614))
- Fixed `Illuminate\Console\Scheduling\Schedule::compileParameters()` ([cfc3ac9](https://github.com/laravel/framework/commit/cfc3ac9c8b0a593d264ae722ab90601fa4882d0e), [36e215d](https://github.com/laravel/framework/commit/36e215dd39cd757a8ffc6b17794de60476b2289d))
- Fixed bug with model name in `Illuminate\Database\Eloquent\RelationNotFoundException::make()` ([f72a166](https://github.com/laravel/framework/commit/f72a1662ab64cc543c532941b1ab1279001af8e9))
- Fixed `Illuminate\Foundation\Testing\TestResponse::assertJsonCount()` not accepting falsey keys ([#32655](https://github.com/laravel/framework/pull/32655))

### Changed
- Changed `Illuminate/Database/Eloquent/Relations/Concerns/AsPivot::fromRawAttributes()` ([6c502c1](https://github.com/laravel/framework/commit/6c502c1135082e8b25f2720931b19d36eeec8f41))
- Restore оnly common relations ([#32613](https://github.com/laravel/framework/pull/32613), [d82f78b](https://github.com/laravel/framework/commit/d82f78b13631c4a04b9595099da0022ca3d8b94e), [48e4d60](https://github.com/laravel/framework/commit/48e4d602d4f8fe9304e8998c5893206f67504dbf))
- Use single space if plain email is empty in `Illuminate\Mail\Mailer::addContent()` ([0557622](https://github.com/laravel/framework/commit/055762286132d545cbc064dce645562c0d51532f))
- Remove wasted file read when loading package manifest in `Illuminate\Foundation\PackageManifest::getManifest()` ([#32646](https://github.com/laravel/framework/pull/32646))
- Cache `FakerGenerator` instances ([#32585](https://github.com/laravel/framework/pull/32585))
- Do not change `character` and `collation` for some columns on change ([fccdf7c](https://github.com/laravel/framework/commit/fccdf7c42d5ceb50985b3e8243d7ba650de996d6))


## [v6.18.11 (2020-04-28)](https://github.com/laravel/framework/compare/v6.18.10...v6.18.11)

### Fixed
- Auth with each master on flushdb ([d0afa58](https://github.com/laravel/framework/commit/d0afa5846ca1d85ca07cdb580d3b9e9768ebdf41))
- Clear resolved facades earlier ([f2ea1a2](https://github.com/laravel/framework/commit/f2ea1a23fdac94d3f0818e7ff514fbaed3f45643))
- Register opis key so it is not tied to a deferred service provider ([a4574ea](https://github.com/laravel/framework/commit/a4574ea973bab9bd6a2ba34d36dfb8f9b55d5a4a))
- Pass status code to schedule finish ([b815dc6](https://github.com/laravel/framework/commit/b815dc6c1b1c595f3241c493255f0fbfd67a6131))
- Fix firstWhere behavior for relations ([#32525](https://github.com/laravel/framework/pull/32525))
- Fixed boolean value in `Illuminate\Foundation\Testing\TestResponse::assertSessionHasErrors()` ([#32555](https://github.com/laravel/framework/pull/32555))


## [v6.18.10 (2020-04-21)](https://github.com/laravel/framework/compare/v6.18.9...v6.18.10)

### Fixed
- Check if object ([1b0bdb4](https://github.com/laravel/framework/commit/1b0bdb43062a2792befe6fd754140124a8e4dc35))


## [v6.18.9 (2020-04-21)](https://github.com/laravel/framework/compare/v6.18.8...v6.18.9)

### Fixed
- Fix `refresh()` to support `AsPivot` trait ([#32420](https://github.com/laravel/framework/pull/32420))
- Fix orderBy with callable ([#32471](https://github.com/laravel/framework/pull/32471))


## [v6.18.8 (2020-04-15)](https://github.com/laravel/framework/compare/v6.18.7...v6.18.8)

### Fixed
- Removed dots ([e78d24f](https://github.com/laravel/framework/commit/e78d24f31b84cd81c30b5d8837731d77ec089761))
- Duplicated mailable in-memory data attachments with different names ([#32392](https://github.com/laravel/framework/pull/32392))
- Fix a regression caused by #32315 ([#32388](https://github.com/laravel/framework/pull/32388))
- Duplicated mailable storage attachments with different names ([#32394](https://github.com/laravel/framework/pull/32394))


## [v6.18.7 (2020-04-14)](https://github.com/laravel/framework/compare/v6.18.6...v6.18.7)

### Fixed
- Call setlocale ([1c6a504](https://github.com/laravel/framework/commit/1c6a50424c5558782a55769a226ab834484282e1))
- Use a map to prevent unnecessary array access ([#32296](https://github.com/laravel/framework/pull/32296))
- Prevent timestamp update when pivot is not dirty ([#32311](https://github.com/laravel/framework/pull/32311))
- Add support for the new composer installed.json format ([#32310](https://github.com/laravel/framework/pull/32310))
- ValidatesAttributes::validateUrl use Symfony/Validator 5.0.7 regex ([#32315](https://github.com/laravel/framework/pull/32315))
- Fix *scan methods for phpredis ([#32336](https://github.com/laravel/framework/pull/32336))
- Use the router for absolute urls ([#32345](https://github.com/laravel/framework/pull/32345))


## [v6.18.6 (2020-04-08)](https://github.com/laravel/framework/compare/v6.18.5...v6.18.6)

### Security
- Prevent insecure characters in locale ([c248521](https://github.com/laravel/framework/commit/c248521f502c74c6cea7b0d221639d4aa752d5db))


## [v6.18.5 (2020-04-07)](https://github.com/laravel/framework/compare/v6.18.4...v6.18.5)

### Fixed
- Revert "Fix setting mail header" ([#32278](https://github.com/laravel/framework/pull/32278))


## [v6.18.4 (2020-04-07)](https://github.com/laravel/framework/compare/v6.18.3...v6.18.4)

### Fixed
- Added missing return in the sendNow pending mail fake ([#32095](https://github.com/laravel/framework/pull/32095))
- Prevent long URLs from breaking email layouts ([#32189](https://github.com/laravel/framework/pull/32189))
- Fix setting mail header ([#32272](https://github.com/laravel/framework/pull/32272))


## [v6.18.3 (2020-03-24)](https://github.com/laravel/framework/compare/v6.18.2...v6.18.3)

### Fixed
- Corrected suggested dependencies ([#32072](https://github.com/laravel/framework/pull/32072), [c01a70e](https://github.com/laravel/framework/commit/c01a70e33198e81d06d4b581e36e25a80acf8a68))
- Avoid deadlock in test when sharing process group ([#32067](https://github.com/laravel/framework/pull/32067))


## [v6.18.2 (2020-03-17)](https://github.com/laravel/framework/compare/v6.18.1...v6.18.2)

### Fixed
- Fixed scheduler dependency assumptions ([#31894](https://github.com/laravel/framework/pull/31894))
- Corrected suggested dependencies ([bb0ec42](https://github.com/laravel/framework/commit/bb0ec42b5a55b3ebf3a5a35cc6df01eec290dfa9))
- Unset `pivotParent` on `Pivot::unsetRelations()` ([#31956](https://github.com/laravel/framework/pull/31956))
- Fixed `cookie` helper signature , matching match `CookieFactory` ([#31974](https://github.com/laravel/framework/pull/31974))


## [v6.18.1 (2020-03-10)](https://github.com/laravel/framework/compare/v6.18.0...v6.18.1)

### Fixed
- Fixed array lock release behavior ([#31795](https://github.com/laravel/framework/pull/31795))
- Fixed model restoring right after being soft deleting ([#31719](https://github.com/laravel/framework/pull/31719))
- Fixed phpredis "zadd" and "exists" on cluster ([#31838](https://github.com/laravel/framework/pull/31838))
- Fixed "srid" mysql schema ([#31852](https://github.com/laravel/framework/pull/31852))
- Fixed Microsoft ODBC lost connection handling ([#31879](https://github.com/laravel/framework/pull/31879))


## [v6.18.0 (2020-03-03)](https://github.com/laravel/framework/compare/v6.17.1...v6.18.0)

### Added
- Added `Arr::hasAny()` method ([#31636](https://github.com/laravel/framework/pull/31636))

### Fixed
- Use correct locale when resolving Faker from the container ([#31615](https://github.com/laravel/framework/pull/31615))
- Fixed loading deferred providers for binding interfaces and implementations ([#31629](https://github.com/laravel/framework/pull/31629), [1764ff7](https://github.com/laravel/framework/commit/1764ff762966083a12dd2c9b522cec5f1bbda967))

### Changed
- Make `newPivotQuery()` method public ([#31677](https://github.com/laravel/framework/pull/31677))
- Allowed easier customization of the queued mailable job ([#31684](https://github.com/laravel/framework/pull/31684))
- Expose Notification Id within Message Data in `Illuminate\Notifications\Channels\MailChannel` ([#31632](https://github.com/laravel/framework/pull/31632))


## [v6.17.1 (2020-02-26)](https://github.com/laravel/framework/compare/v6.17.0...v6.17.1)

### Changed
- Don`t do chmod in File cache in case if permission not set ([#31593](https://github.com/laravel/framework/pull/31593))


## [v6.17.0 (2020-02-25)](https://github.com/laravel/framework/compare/v6.16.0...v6.17.0)

### Added
- Allowed private-encrypted pusher channels ([#31559](https://github.com/laravel/framework/pull/31559), [ceabaef](https://github.com/laravel/framework/commit/ceabaef88741c0c6a891166cf14eb967fdf4e8ee), [8215e0d](https://github.com/laravel/framework/commit/8215e0dc66bf71a7ff4e9bf260380cf4a26f28a6))
- Added file `permission` config option for the File cache store ([#31579](https://github.com/laravel/framework/pull/31579))
- Added `Connection refused` and `running with the --read-only option so it cannot execute this statement` to `DetectsLostConnections` ([#31539](https://github.com/laravel/framework/pull/31539))

### Reverted
- Reverted ["Fixed memory usage on downloading large files"](https://github.com/laravel/framework/pull/31163) ([#31587](https://github.com/laravel/framework/pull/31587))

### Fixed
- Fixed issue `Content Type not specified` ([#31533](https://github.com/laravel/framework/pull/31533))

### Changed
- Allowed `cache` helper to have an optional `expiration` parameter ([#31554](https://github.com/laravel/framework/pull/31554))
- Allowed passing of strings to `TestResponse::dumpSession()` method ([#31583](https://github.com/laravel/framework/pull/31583))
- Consider mailto: and tel: links in the subcopy actionUrl label in emails ([#31523](https://github.com/laravel/framework/pull/31523), [641a7cd](https://github.com/laravel/framework/commit/641a7cda8280ecd3035616d4ce6434434b116624))
- Exclude mariaDB from database queue support for new SKIP LOCKED ([fff96e7](https://github.com/laravel/framework/commit/fff96e7df7de470e162a6b7f6dd528e6fe17aadc))


## [v6.16.0 (2020-02-18)](https://github.com/laravel/framework/compare/v6.15.1...v6.16.0)

### Added
- Added Guzzle 7 support ([#31484](https://github.com/laravel/framework/pull/31484))
- Added `Illuminate\Database\Query\Builder::groupByRaw()` ([#31498](https://github.com/laravel/framework/pull/31498))
- Added SQLite JSON update support with json_patch ([#31492](https://github.com/laravel/framework/pull/31492))

### Fixed
- Fixed `appendRow` on console table ([#31469](https://github.com/laravel/framework/pull/31469))
- Fixed password check in `EloquentUserProvider::retrieveByCredentials()` ([4436662](https://github.com/laravel/framework/commit/4436662a1ee19fc5e9eb76a0651d0de1aedb3ee2))

### Revert
- Revert table feature in the console output ([4094d78](https://github.com/laravel/framework/commit/4094d785269ce7849557b792f650fb278d48978e))

### Changed
- Change MySql nullable modifier to allow generated columns to be not null ([#31452](https://github.com/laravel/framework/pull/31452))
- Throw exception on empty collection in `assertSentTo()` \ `assertNotSentTo()` methods in `NotificationFake` class ([#31471](https://github.com/laravel/framework/pull/31471))


## [v6.15.1 (2020-02-12)](https://github.com/laravel/framework/compare/v6.15.0...v6.15.1)

### Added
- Added `whereNull` and `whereNotNull` to `Collection` ([#31425](https://github.com/laravel/framework/pull/31425))
- Added `Illuminate\Foundation\Testing\MockStream` class ([#31447](https://github.com/laravel/framework/pull/31447))

### Fixed
- Fixed `event:list` command for shows non-registered events ([#31444](https://github.com/laravel/framework/pull/31444))
- Fixed postgres grammar for nested json arrays with  ([#31448](https://github.com/laravel/framework/pull/31448), [b3d0da1](https://github.com/laravel/framework/commit/b3d0da164bdf3d5d829384025476ca1b2065c97e))


## [v6.15.0 (2020-02-11)](https://github.com/laravel/framework/compare/v6.14.0...v6.15.0)

### Added
- Added `Illuminate\Auth\Events\Validated` event ([#31357](https://github.com/laravel/framework/pull/31357), [7ddac28](https://github.com/laravel/framework/commit/7ddac28bc08b99ee248b1e4aa559efc3a8bfec8c))
- Make `Blueprint` support Grammar's `macro` ([#31365](https://github.com/laravel/framework/pull/31365))
- Added `Macroable` trait to `Illuminate\Console\Scheduling\Schedule` class ([#31354](https://github.com/laravel/framework/pull/31354))
- Added support `dispatchAfterResponse` in `BusFake` ([#31418](https://github.com/laravel/framework/pull/31418), [e59597f](https://github.com/laravel/framework/commit/e59597f13af3ee6e6467bdb8593844f9db6bb789)) 
- Added `Illuminate\Foundation\Exceptions\Handler::getHttpExceptionView()` ([#31420](https://github.com/laravel/framework/pull/31420))
- Allowed appending of rows to Artisan tables ([#31426](https://github.com/laravel/framework/pull/31426))

### Fixed
- Fixed `locks` for `sqlsrv` queue ([5868066](https://github.com/laravel/framework/commit/58680668102282fcc4215a48e8947c2c1b051201))
- Fixed `Illuminate\Events\Dispatcher::hasListeners()` ([#31403](https://github.com/laravel/framework/pull/31403), [c80302e](https://github.com/laravel/framework/commit/c80302e6e9403f9fad71f114d94e758ee0fcbff7))
- Fixed testing with unencrypted cookies ([#31390](https://github.com/laravel/framework/pull/31390))

### Changed
- Allowed multiple paths to be passed to migrate fresh and migrate refresh commands ([#31381](https://github.com/laravel/framework/pull/31381))
- Split Console InteractsWithIO to external trait ([#31376](https://github.com/laravel/framework/pull/31376))
- Added sms link as valid URL in `UrlGenerator::isValid()` method ([#31382](https://github.com/laravel/framework/pull/31382))
- Upgrade CommonMark and use the bundled table extension ([#31411](https://github.com/laravel/framework/pull/31411))
- Ensure `Application::$terminatingCallbacks` are reset on `Application::flush()` ([#31413](https://github.com/laravel/framework/pull/31413))
- Remove serializer option in `PhpRedisConnector::createClient()` ([#31417](https://github.com/laravel/framework/pull/31417))


## [v6.14.0 (2020-02-04)](https://github.com/laravel/framework/compare/v6.13.1...v6.14.0)

### Added
- Added `Illuminate\Bus\Dispatcher::dispatchAfterResponse()` method ([#31300](https://github.com/laravel/framework/pull/31300), [8a3cdb0](https://github.com/laravel/framework/commit/8a3cdb0622047b1d94b4a754bfe98fb7dc1c174a))
- Added `Illuminate\Support\Testing\Fakes\QueueFake::assertPushedWithoutChain()` method ([#31332](https://github.com/laravel/framework/pull/31332), [7fcc6b5](https://github.com/laravel/framework/commit/7fcc6b5feb004f57aa61a0fa93c340694ae6a980))
- Added `Macroable` trait to the `Illuminate\Events\Dispatcher` ([#31317](https://github.com/laravel/framework/pull/31317))
- Added `NoPendingMigrations` event ([#31289](https://github.com/laravel/framework/pull/31289), [739fcea](https://github.com/laravel/framework/commit/739fcea5cfcc9079d3ca8e5aa9673f706741418e))

### Fixed
- Used current DB to create Doctrine Connections ([#31278](https://github.com/laravel/framework/pull/31278))
- Removed duplicate output when publishing tags in `vendor:publish` command ([#31333](https://github.com/laravel/framework/pull/31333))
- Fixed plucking column name containing a space ([#31299](https://github.com/laravel/framework/pull/31299))
- Fixed bug with wildcard caching in event dispatcher ([#31313](https://github.com/laravel/framework/pull/31313))
- Fixed infinite value for RedisStore ([#31348](https://github.com/laravel/framework/pull/31348))
- Fixed dropping columns in SQLServer with default value ([#31341](https://github.com/laravel/framework/pull/31341))

### Changed
- Use SKIP LOCKED for mysql 8.1 and pgsql 9.5 queue workers ([#31287](https://github.com/laravel/framework/pull/31287))
- Don't merge middleware from method and property in `Illuminate\Bus\Queueable::middleware()` ([#31301](https://github.com/laravel/framework/pull/31301))
- Split `specifyParameter()` from `Illuminate\Console\Command` to `HasParameters` trait ([#31254](https://github.com/laravel/framework/pull/31254))
- Make sure changing a database field to json does not include charset ([#31343](https://github.com/laravel/framework/pull/31343))


## [v6.13.1 (2020-01-28)](https://github.com/laravel/framework/compare/v6.13.0...v6.13.1)

### Fixed
- Fixed error on `queue:work` database on Windows ([#31277](https://github.com/laravel/framework/pull/31277))


## [v6.13.0 (2020-01-28)](https://github.com/laravel/framework/compare/v6.12.0...v6.13.0)

### Added
- Added `--api` option to the `make:model` command ([#31197](https://github.com/laravel/framework/pull/31197), [#31222](https://github.com/laravel/framework/pull/31222))
- Added `PendingResourceRegistration::shallow()` method ([#31208](https://github.com/laravel/framework/pull/31208), [104c539](https://github.com/laravel/framework/commit/104c539c342d395e2f3c4ba7339df095f83f6352))
- Allowed formatting an implicit attribute using a closure ([#31246](https://github.com/laravel/framework/pull/31246))
- Added `Filesystem::ensureDirectoryExists()` method ([8a8eed4](https://github.com/laravel/framework/commit/8a8eed4d157102ef77527891ac1d8f8e85e7afee))
- Added support to `Storage::url()` for the Ftp driver ([#31258](https://github.com/laravel/framework/pull/31258), [b8790e5](https://github.com/laravel/framework/commit/b8790e56bb7333943db799e6ff6e21a7b01218e0))

### Fixed
- Fixed laravel migrations when migrating to sql server (dropColumn with default value) ([#31229](https://github.com/laravel/framework/pull/31229))
- Fixed `handleBeginTransactionException()` method calling pdo property instead of getPdo() method ([#31233](https://github.com/laravel/framework/pull/31233))
- Fixed channel names when broadcasting via redis ([#31261](https://github.com/laravel/framework/pull/31261))
- Replace asterisks before validation ([#31257](https://github.com/laravel/framework/pull/31257))

### Changed
- Reset timeout handler after worker loop ([#31198](https://github.com/laravel/framework/pull/31198))


## [v6.12.0 (2020-01-21)](https://github.com/laravel/framework/compare/v6.11.0...v6.12.0)

### Added
- Added `ServiceProvider::loadFactoriesFrom()` method ([#31133](https://github.com/laravel/framework/pull/31133))
- Added `TestResponse::dumpSession()` method ([#31131](https://github.com/laravel/framework/pull/31131))
- Added `Str::isUuid()` method ([#31148](https://github.com/laravel/framework/pull/31148))
- Restored phpunit 7 support ([#31113](https://github.com/laravel/framework/pull/31113))
- Added `Request::boolean()` method ([#31160](https://github.com/laravel/framework/pull/31160))
- Added `Database\Eloquent\FactoryBuilder::createMany()` ([#31171](https://github.com/laravel/framework/pull/31171), [6553d59](https://github.com/laravel/framework/commit/6553d5923959bd947b49eb089053cd430d8968d4))
- Added missing options for PhpRedis ([#31182](https://github.com/laravel/framework/pull/31182))

### Fixed
- Fixed `Cache\RedisLock::acquire()` ([#31168](https://github.com/laravel/framework/pull/31168), [8683a3d](https://github.com/laravel/framework/commit/8683a3d721f92e512a83a3e5feb3d0a9bb682560))
- Fixed database url parsing for connections with no database specified ([#31185](https://github.com/laravel/framework/pull/31185))
- Prevent ambiguous column with table name prefix ([#31174](https://github.com/laravel/framework/pull/31174))

### Optimization
- Fixed memory usage on downloading large files ([#31163](https://github.com/laravel/framework/pull/31163))

### Changed
- Replace Event Dispatcher in resolved cache repositories when `Event::fake()` is used ([#31119](https://github.com/laravel/framework/pull/31119), [0a70beb](https://github.com/laravel/framework/commit/0a70bebd5ecfd51185a312bbfb60ee7f8ff7eb09))


## [v6.11.0 (2020-01-14)](https://github.com/laravel/framework/compare/v6.10.1...v6.11.0)

### Added
- Added `Illuminate\Database\Eloquent\Builder::firstWhere()` method ([#31089](https://github.com/laravel/framework/pull/31089)) 
- Redis Broadcaster: Broadcast to multiple channels at once ([#31108](https://github.com/laravel/framework/pull/31108))

### Fixed
- Fixed undefined property in `WithFaker::makeFaker()` ([#31083](https://github.com/laravel/framework/pull/31083))
- Fixed `Str::afterLast()` method ([#31095](https://github.com/laravel/framework/pull/31095))
- Fixed insert float into MySQL with PHP 7.3 ([#31100](https://github.com/laravel/framework/pull/31100))
- Fixed refresh on Model with customized pivot attribute name ([#31125](https://github.com/laravel/framework/pull/31125), [678b26b](https://github.com/laravel/framework/commit/678b26b1a9cd0d8a6bef85932420e67a1b20e677))

### Changed
- Remove all indentation in blade templates ([917ee51](https://github.com/laravel/framework/commit/917ee514d4bbd4162b6ddb385c643df97dcfa7d3))
- Added mailable names to assertion messages in `MailFake::assertNothingSent()` and `MailFake::assertNothingQueued()` ([#31106](https://github.com/laravel/framework/pull/31106))
- Search for similar results in `assertDatabaseHas()` ([#31042](https://github.com/laravel/framework/pull/31042), [2103eb7](https://github.com/laravel/framework/commit/2103eb7ccfbb6798e9078d82e0ebffcf87d95b14))


## [v6.10.1 (2020-01-08)](https://github.com/laravel/framework/compare/v6.10.0...v6.10.1)

### Changed
- Updated some blade templates ([f17e347](https://github.com/laravel/framework/commit/f17e347b15e8d27b4e775a8f961bda083326ee8f))


## [v6.10.0 (2020-01-07)](https://github.com/laravel/framework/compare/v6.9.0...v6.10.0)

### Added
- Added `withoutMix()` and `withMix()` test helpers ([#30900](https://github.com/laravel/framework/pull/30900))
- Added `validateWithBag()` macro to `Request` ([#30896](https://github.com/laravel/framework/pull/30896))
- Added PHPUnit 9 support ([#30947](https://github.com/laravel/framework/pull/30947), [#30989](https://github.com/laravel/framework/pull/30989))
- Added `exclude_if` and `exclude_unless` validation rules ([#30835](https://github.com/laravel/framework/pull/30835), [c0fdb56](https://github.com/laravel/framework/commit/c0fdb566831b7ebf34a15bbdfec81dd0039c76f0))
- Added generated columns (virtual/stored) support for PostgreSQL ([#30971](https://github.com/laravel/framework/pull/30971))
- Added `mixin` support to Eloquent builder ([#30978](https://github.com/laravel/framework/pull/30978), [28fa74e](https://github.com/laravel/framework/commit/28fa74e8222a57118ae1b590101a35f63b964f81))
- Make the Redis Connection `Macroable` ([#31020](https://github.com/laravel/framework/pull/31020))
- Added `PackageManifest::config()` method ([#31039](https://github.com/laravel/framework/pull/31039), [9b73540](https://github.com/laravel/framework/commit/9b73540cbe7ebb67b0a0a127743791511e5ae8fe))
- Added `redis.connection` aliases in container ([#31034](https://github.com/laravel/framework/pull/31034))
- Extracted `CallsCommands` feature from `Illuminate\Console\Command` ([#31026](https://github.com/laravel/framework/pull/31026), [ef72716](https://github.com/laravel/framework/commit/ef72716db85f36e003fb92d2625adabbf94d5afe))
- Allowed absolute file path for `Storage::putFile()` ([#31040](https://github.com/laravel/framework/pull/31040))

### Changed
- Handled passing too many arguments to `@slot` ([#30893](https://github.com/laravel/framework/pull/30893), [878f159](https://github.com/laravel/framework/commit/878f15922523e748bfbfdf50f40269f8ffe20d9d))
- Make `ThrottleRequestsException` extend `TooManyRequestsHttpException` ([#30943](https://github.com/laravel/framework/pull/30943))
- Used `league/commonmark` instead of `erusev/parsedown` for mail markdown ([#30982](https://github.com/laravel/framework/pull/30982))
- Regenerate token on logout ([b2af428](https://github.com/laravel/framework/commit/b2af428e60188ea55fb06f3a1e0b0b0c690bbe86))
- Make `RedisQueue::getConnection()` public ([#31016](https://github.com/laravel/framework/pull/31016))
- Resolve `Faker\Generator` out of the container if it is bound ([#30992](https://github.com/laravel/framework/pull/30992))

### Fixed
- Fixed `float` database types in `Blueprint` ([#30891](https://github.com/laravel/framework/pull/30891))
- Fixed code that depended on `getenv()` ([#30924](https://github.com/laravel/framework/pull/30924))
- Prevented making actual pdo connections while reconnecting ([#30998](https://github.com/laravel/framework/pull/30998))
- Fixed `exclude_if` \ `exclude_unless` validation rules for nested data ([#31006](https://github.com/laravel/framework/pull/31006))
- Update `dev-master` branch aliases from `6.0-dev` to `6.x-dev` ([d06cc79](https://github.com/laravel/framework/commit/d06cc79d92c18b0ff423466554eeed0aea09ae51))
- Utilize Symfony’s PSR Factory. Fixed [#31017](https://github.com/laravel/framework/issues/31017) ([#31018](https://github.com/laravel/framework/pull/31018), [#31027](https://github.com/laravel/framework/pull/31027))
- Used model connection by default in the database validators ([#31037](https://github.com/laravel/framework/pull/31037))

### Optimization
- Optimize Service Provider registration ([#30960](https://github.com/laravel/framework/pull/30960))
- Optimize `runningInConsole` method ([#30922](https://github.com/laravel/framework/pull/30922))
- Delay instantiation of translator and view factory ([#31009](https://github.com/laravel/framework/pull/31009))

### Deprecated
- Deprecate `PendingMail::sendNow()` and remove unneeded check ([#30999](https://github.com/laravel/framework/pull/30999))

### Reverted
- Reverted [TransactionCommitted event doesn’t contain transaction level I’d expect it to](https://github.com/laravel/framework/pull/30883) ([#31051](https://github.com/laravel/framework/pull/31051))

### Refactoring
- Refactoring of `BladeCompiler::compileString()` method ([08887f9](https://github.com/laravel/framework/commit/08887f99d05bb85affd3cbc6f7fdbc32a9297eea))


## [v6.9.0 (2019-12-19)](https://github.com/laravel/framework/compare/v6.8.0...v6.9.0)

### Added
- Added `MIME` type argument to `Testing/FileFactory::create()` ([#30870](https://github.com/laravel/framework/pull/30870))
- Added `seed` to `all` option when creating the model (`make:model` command) ([#30874](https://github.com/laravel/framework/pull/30874))
- Allowed configurable emergency logger ([#30873](https://github.com/laravel/framework/pull/30873))
- Added `prependMiddlewareToGroup()` / `appendMiddlewareToGroup()` / `prependToMiddlewarePriority()` / `appendToMiddlewarePriority()` to `Kernal` for manipulating middleware ([6f33feb](https://github.com/laravel/framework/commit/6f33feba124d4a7ff2af4f3ed18583d67fb68f7c))

### Reverted
- Reverted [Added `Model::setRawAttribute()`](https://github.com/laravel/framework/pull/30853) ([#30885](https://github.com/laravel/framework/pull/30885))

### Fixed
- Fixed `Builder::withCount()` binding error when a scope is added into related model with binding in a sub-select ([#30869](https://github.com/laravel/framework/pull/30869))

### Changed
-  Don't throw exception when session is not set in `AuthenticateSession` middleware ([4de1d24](https://github.com/laravel/framework/commit/4de1d24cf390f07d4f503973e5556f73060fbb31))


## [v6.8.0 (2019-12-17)](https://github.com/laravel/framework/compare/v6.7.0...v6.8.0)

### Added
- Allowed packages to use custom markdown mail themes ([#30814](https://github.com/laravel/framework/pull/30814), [2206d52](https://github.com/laravel/framework/commit/2206d5223606f5a24e7e3bf0ba1f25b343dfcc6b))
- Added more quotes to `Inspiring` ([4a7d566](https://github.com/laravel/framework/commit/4a7d566ff4a330970cfaa03df4c988c580804a7f), [9693ced](https://github.com/laravel/framework/commit/9693cedbfc1fb0e38a8e688375e5b2ce5273b75f))
- Added support for nested arrays in `TestResponse::assertViewHas()` ([#30837](https://github.com/laravel/framework/pull/30837))
- Added `Model::setRawAttribute()` ([#30853](https://github.com/laravel/framework/pull/30853))
- Added `--force` option to the `make:controller` resource ([#30856](https://github.com/laravel/framework/pull/30856))
- Allowed passing an array to `Resource::collection()` ([#30800](https://github.com/laravel/framework/pull/30800))
- Implemented ArrayAccess on `JsonResponse` and `TestResponse` ([#30817](https://github.com/laravel/framework/pull/30817))
- Added `--seed` option to the `make::model` resource ([#30828](https://github.com/laravel/framework/pull/30828), [2cd9417](https://github.com/laravel/framework/commit/2cd9417064123fd6c9114788d331659ede568dbf))

### Fixed
- Fixed two index creation instead of one when using `change()` ([#30843](https://github.com/laravel/framework/pull/30843))
- Prevent duplicate attachments in the `Mailable` ([3c8ccc2](https://github.com/laravel/framework/commit/3c8ccc2fb4ec03572076e6df71608f6bbb7d71e1))
- Fixed `ServiceProvider` for PHP 7.4 in `Lumen` ([#30819](https://github.com/laravel/framework/pull/30819))
- Fixed non-eloquent model validation in database validation rules ([#30840](https://github.com/laravel/framework/pull/30840))

### Changed
- Changed `rescue()` helper ([#30838](https://github.com/laravel/framework/pull/30838))
- Added previous exception to `EntryNotFoundException` thrown in `Container.php` ([#30862](https://github.com/laravel/framework/pull/30862))
- Changed `DatabaseNotification::$keyType` to match `uuid` ([#30823](https://github.com/laravel/framework/pull/30823))


## [v6.7.0 (2019-12-10)](https://github.com/laravel/framework/compare/v6.6.2...v6.7.0)

### Added
- Added `getQualifiedCreatedAtColumn()` and `getQualifiedUpdatedAtColumn()` methods to `HasTimestamps` concern ([#30792](https://github.com/laravel/framework/pull/30792))
- Added `exceptionContext()` method to the `Exceptions\Handler` ([#30780](https://github.com/laravel/framework/pull/30780))
- Added ability for postmark transport to throw errors ([#30799](https://github.com/laravel/framework/pull/30799), [4320b82](https://github.com/laravel/framework/commit/4320b82f848d63d41df95860ed3bf595202873a9))
- Added `withoutRelations()` and `unsetRelations()` methods to `HasRelationships` ([#30802](https://github.com/laravel/framework/pull/30802))
- Added `ResourceCollection::preserveQueryParameters()` for preserve query parameters on paginated api resources ([#30745](https://github.com/laravel/framework/pull/30745), [e92a708](https://github.com/laravel/framework/commit/e92a70800671187cc30a39e965144101d5db169a))

### Fixed
- Fixed explicit models in string-based database validation rules ([#30790](https://github.com/laravel/framework/pull/30790))
- Fixed `Routing\RedirectController()` ([#30783](https://github.com/laravel/framework/pull/30783))

### Changed
- Reconnect `PhpRedisConnection` on connection missing ([#30778](https://github.com/laravel/framework/pull/30778))
- Improved ShouldBroadcastNow performance ([#30797](https://github.com/laravel/framework/pull/30797), [5b3cc97](https://github.com/laravel/framework/commit/5b3cc9752d873be96ac34d9062cc35aa9c95bd59))


## [v6.6.2 (2019-12-05)](https://github.com/laravel/framework/compare/v6.6.1...v6.6.2)

### Added
- Added `Illuminate\Support\Facades\Facade::partialMock()` method ([#30754](https://github.com/laravel/framework/pull/30754))
- Added of support `retryAfter` option on queued listeners ([#30743](https://github.com/laravel/framework/pull/30743))

### Fixed
- Fixed zero parameter for routes ([#30768](https://github.com/laravel/framework/pull/30768))

### Changed
- Changed `getAllViews()` method visibility from `protected` to `public` in all schema builders ([#30757](https://github.com/laravel/framework/pull/30757))


## [v6.6.1 (2019-12-03)](https://github.com/laravel/framework/compare/v6.6.0...v6.6.1)

### Added
- Added `setInput()` and `setOutput()` methods to `Illuminate\Console\Command` ([#30706](https://github.com/laravel/framework/pull/30706))

### Fixed
- Fixed RouteUrlGenerator with empty string for required parameter ([#30714](https://github.com/laravel/framework/pull/30714))

### Changed
- Force usage getting timestamps columns in model ([#30697](https://github.com/laravel/framework/pull/30697))

### Reverted
- Revert [Added `Illuminate\Routing\Router::head()`](https://github.com/laravel/framework/pull/30646) ([#30710](https://github.com/laravel/framework/pull/30710))


## [v6.6.0 (2019-11-26)](https://github.com/laravel/framework/compare/v6.5.2...v6.6.0)

### Added
- Allowed explicit Model definitions in database rules ([#30653](https://github.com/laravel/framework/pull/30653), [9beceac](https://github.com/laravel/framework/commit/9beceacb1a1b8ba37cd0f775cb2fb81e21ba4c31))
- Allowed `ResponseFactory::view()` to return first view ([#30651](https://github.com/laravel/framework/pull/30651))
- Added `Foundation\Testing\Concerns\InteractsWithDatabase::assertDeleted()` method ([#30648](https://github.com/laravel/framework/pull/30648))
- Added `Illuminate\Routing\Router::head()` ([#30646](https://github.com/laravel/framework/pull/30646))
- Added `wherePivotNotIn()` and `orWherePivotNotIn()` methods to `BelongsToMany` ([#30671](https://github.com/laravel/framework/pull/30671))
- Added options in `SqlServerConnector` to encrypt data with Azure Key vault ([#30636](https://github.com/laravel/framework/pull/30636))

### Fixed
- Fixed errors in `Illuminate\Http\Testing\FileFactory::create()` ([#30632](https://github.com/laravel/framework/pull/30632))
- Fixed routing bug that causes missing parameters to be ignored ([#30659](https://github.com/laravel/framework/pull/30659))

### Changed
- Updated error message in `PhpRedisConnector::createClient()` if redis extension is not loaded ([#30673](https://github.com/laravel/framework/pull/30673), [184a0f4](https://github.com/laravel/framework/commit/184a0f45bc9959ebadf36a7dd6966c2bfcb96191))
- Updated `windows_os()` helper to use PHP_OS_FAMILY ([#30660](https://github.com/laravel/framework/pull/30660))


## [v6.5.2 (2019-11-19)](https://github.com/laravel/framework/compare/v6.5.1...v6.5.2)

### Added
- Allowed model serialization on jobs for typed properties ([#30604](https://github.com/laravel/framework/pull/30604), [#30605](https://github.com/laravel/framework/pull/30605), [920c364](https://github.com/laravel/framework/commit/920c3640269b7c1dd0f26e5b6f765ca9b7f99366))
- Allowed fallback when facade root accessor has previously been resolved ([#30616](https://github.com/laravel/framework/pull/30616))
- Added support for separation between `geometry` and `geography` types for `Postgres` ([#30545](https://github.com/laravel/framework/pull/30545))
- Added `createWithContent()` method to `Illuminate\Http\Testing\File` and `Illuminate\Http\Testing\FileFactory` ([2cc6fa3](https://github.com/laravel/framework/commit/2cc6fa33732118cc71c74209b02382b989689b63), [181db51](https://github.com/laravel/framework/commit/181db51595d546cbd24b3fac0cb276255e147286))

### Refactoring
- Improved `PostgresGrammar::formatPostGisType()` method readability ([#30593](https://github.com/laravel/framework/pull/30593))

### Changed
- Added `symfony/debug` dependency to `illuminate/pipeline` ([#30611](https://github.com/laravel/framework/pull/30611))
- Override `BelongsToMany::cursor()` to hydrate pivot relations ([#30580](https://github.com/laravel/framework/pull/30580))
- Ignore Redis prefix when verifying channel access in RedisBroadcaster ([#30597](https://github.com/laravel/framework/pull/30597), [d77ce36](https://github.com/laravel/framework/commit/d77ce36917510d5a6800dd4116a4e18b7bf720b3))


## [v6.5.1 (2019-11-12)](https://github.com/laravel/framework/compare/v6.5.0...v6.5.1)

### Added
- Added `includeUnless` Blade directive ([#30538](https://github.com/laravel/framework/pull/30538))

### Fixed
- Fixed default value for $count in `PhpRedisConnection::spop()` method ([#30546](https://github.com/laravel/framework/pull/30546))
- Fixed breaking compatibility with multi-schema postgres ([#30562](https://github.com/laravel/framework/pull/30562), [6460d2b](https://github.com/laravel/framework/commit/6460d2b1bd89f470a76f5c2c3bddd390fe430e0f))
- Fixed `Model::isDirty()` with `collection` / `object` casts ([#30565](https://github.com/laravel/framework/pull/30565))
- Fixed `bcc` in `MailgunTransport::send()` ([#30569](https://github.com/laravel/framework/pull/30569))

### Changed
- Remove `illuminate/support` dependency from `Container` package  ([#30518](https://github.com/laravel/framework/pull/30518), [#30528](https://github.com/laravel/framework/pull/30528))


## [v6.5.0 (2019-11-05)](https://github.com/laravel/framework/compare/v6.4.1...v6.5.0)

### Added
- Added `LazyCollection::remember()` method ([#30443](https://github.com/laravel/framework/pull/30443))
- Added `Str::afterLast()` and `Str::beforeLast()` methods ([#30507](https://github.com/laravel/framework/pull/30507))
- Added `existsOr()` and `doesntExistOr()` methods to the query builder ([#30495](https://github.com/laravel/framework/pull/30495))
- Added `unless` condition to Blade custom `if` directives ([#30492](https://github.com/laravel/framework/pull/30492))

### Changed
- Added reconnect if missing connection when beginning transaction ([#30474](https://github.com/laravel/framework/pull/30474))
- Set Redis cluster prefix with PhpRedis ([#30461](https://github.com/laravel/framework/pull/30461))


## [v6.4.1 (2019-10-29)](https://github.com/laravel/framework/compare/v6.4.0...v6.4.1)

### Added
- Added `ScheduledTaskSkipped` event when a scheduled command was filtered from running ([#30407](https://github.com/laravel/framework/pull/30407))
- Added `Login timeout expired` to `DetectsLostConnections` ([#30362](https://github.com/laravel/framework/pull/30362))
- Added `missing` method to `Illuminate\Filesystem\Filesystem` and `Illuminate\Filesystem\FilesystemAdapter` classes ([#30441](https://github.com/laravel/framework/pull/30441))

### Changed
- Make `vendor:publish` command more informative ([#30408](https://github.com/laravel/framework/pull/30408), [65d040d](https://github.com/laravel/framework/commit/65d040d44f1cef3830748ec59c0056bc2418dca6))
- Accepted underscores URL in the `URL` validator ([#30417](https://github.com/laravel/framework/pull/30417))
- Updated `artisan down` output to be consistent with `artisan up` ([#30422](https://github.com/laravel/framework/pull/30422))
- Changed `!empty` to `isset` for changing redis database ([#30420](https://github.com/laravel/framework/pull/30420))
- Throw an exception when signing route got in parameter keys `signature` ([#30444](https://github.com/laravel/framework/pull/30444), [71af732](https://github.com/laravel/framework/commit/71af732b6b00ab148cd23b95aca4e05bcb86c242))

### Fixed
- Fixed of retrieving view config in `ServiceProvider::loadViewsFrom()` for Lumen ([#30404](https://github.com/laravel/framework/pull/30404))


## [v6.4.0 (2019-10-23)](https://github.com/laravel/framework/compare/v6.3.0...v6.4.0)

### Added
- Added `missing()` method to `Request` class ([#30320](https://github.com/laravel/framework/pull/30320))
- Added `Pipeline::pipes()` method ([#30346](https://github.com/laravel/framework/pull/30346))
- Added `TestResponse::assertCreated()` method ([#30368](https://github.com/laravel/framework/pull/30368)) 

### Changed
- Added `connection is no longer usable` to `DetectsLostConnections` ([#30362](https://github.com/laravel/framework/pull/30362))
- Implemented parse ID on find method for many to many relation ([#30359](https://github.com/laravel/framework/pull/30359))
- Improvements on subqueries ([#30307](https://github.com/laravel/framework/pull/30307), [3f3b621](https://github.com/laravel/framework/commit/3f3b6214cc3353156a490d88fc8f0c148da400d5))
- Pass mail data to theme css in `Markdown::render()` method ([#30376](https://github.com/laravel/framework/pull/30376))
- Handle ajax requests in RequirePassword middleware ([#30390](https://github.com/laravel/framework/pull/30390), [331c354](https://github.com/laravel/framework/commit/331c354e586a5a27a9edc9b9a49d23aa872e4b32))

### Fixed
- Fixed `retry()` with `$times` value less then 1 ([#30356](https://github.com/laravel/framework/pull/30356))
- Fixed `last_modified` option in `SetCacheHeader` ([#30335](https://github.com/laravel/framework/pull/30335))
- Fixed the Filesystem manager's exception on unsupported driver ([#30331](https://github.com/laravel/framework/pull/30331), [#30369](https://github.com/laravel/framework/pull/30369))
- Fixed `shouldQueue()` check for bound event listeners ([#30378](https://github.com/laravel/framework/pull/30378))
- Used exit code `1` when migration table not found ([#30321](https://github.com/laravel/framework/pull/30321))
- Alleviate breaking change introduced by password confirm feature ([#30389](https://github.com/laravel/framework/pull/30389))

### Security:
- Password Reset Security fix ([23041e9](https://github.com/laravel/framework/commit/23041e99833630d93cc7672bd7087eaa350c3a59), [a934160](https://github.com/laravel/framework/commit/a9341609705e2f8febcd356cdfa33391ec6538c7))


## [v6.3.0 (2019-10-15)](https://github.com/laravel/framework/compare/v6.2.0...v6.3.0)

### Added
- Added ability to override `setUserPassword` on password reset ([#30218](https://github.com/laravel/framework/pull/30218))
- Added firing `deleting` / `deleted` events in `MorphPivot` ([#30229](https://github.com/laravel/framework/pull/30229))
- Added locking mechanism for the array cache driver ([#30253](https://github.com/laravel/framework/pull/30253))
- Added `dropAllViews` functionality to the SQL Server builder ([#30222](https://github.com/laravel/framework/pull/30222))

### Optimization
- Optimize eager loading memory handling ([#30248](https://github.com/laravel/framework/pull/30248))

### Fixed
- Fixed extra `?` for empty query string in `RouteUrlGenerator::getRouteQueryString()` ([#30280](https://github.com/laravel/framework/pull/30280))

### Changed
- Updated list of URI schemes for `Url` validator ([#30220](https://github.com/laravel/framework/pull/30220))
- Added schema name when dropping all FKs in SQL Server ([#30221](https://github.com/laravel/framework/pull/30221))
- Used contracts in `RequirePassword` middleware ([#30215](https://github.com/laravel/framework/pull/30215))
- Added ability to return array in `receivesBroadcastNotificationsOn` if `channelName` is array ([#30242](https://github.com/laravel/framework/pull/30242), [2faadcd](https://github.com/laravel/framework/commit/2faadcd288cdc86cf7a1a3644e68e5e0ce641a8b))


## [v6.2.0 (2019-10-08)](https://github.com/laravel/framework/compare/v6.1.0...v6.2.0)

### Added
- Added support for callable objects in `Container::call()` ([#30156](https://github.com/laravel/framework/pull/30156))
- Add multipolygonz type for postgreSQL ([#30173](https://github.com/laravel/framework/pull/30173))
- Add "unauthenticated" method in auth middleware ([#30177](https://github.com/laravel/framework/pull/30177))
- Add partialMock shorthand ([#30202](https://github.com/laravel/framework/pull/30202))
- Allow Storage::put to accept a Psr StreamInterface ([#30179](https://github.com/laravel/framework/pull/30179))
- Implement new password rule and password confirmation ([#30214](https://github.com/laravel/framework/pull/30214))

### Changed
- Remove unnecessary param passed to updatePackageArray method ([#30155](https://github.com/laravel/framework/pull/30155))
- Add optional connection name to DatabaseUserProvider ([#30154](https://github.com/laravel/framework/pull/30154))
- Remove brackets arround URL php artisan serve ([#30168](https://github.com/laravel/framework/pull/30168))
- Apply limit to database rather than collection ([#30148](https://github.com/laravel/framework/pull/30148))
- Allow to use scoped macro in nested queries ([#30127](https://github.com/laravel/framework/pull/30127))
- Added array to json conversion for sqlite ([#30133](https://github.com/laravel/framework/pull/30133))
- Use the `policies()` method instead of the property policies ([#30189](https://github.com/laravel/framework/pull/30189))
- Split hasValidSignature method ([#30208](https://github.com/laravel/framework/pull/30208))

### Fixed
- `validateDimensions()` handle `image/svg` MIME ([#30204](https://github.com/laravel/framework/pull/30204))


## [v6.1.0 (2019-10-01)](https://github.com/laravel/framework/compare/v6.0.4...v6.1.0)

### Added
- Added `Illuminate\Support\LazyCollection::eager()` method ([#29832](https://github.com/laravel/framework/pull/29832))
- Added `forgetChannel()` and `getChannels()` methods to `Illuminate\Log\LogManager` ([#30132](https://github.com/laravel/framework/pull/30132), [a52a0dd](https://github.com/laravel/framework/commit/a52a0dd239262f31edfaefe9a99213cccefc2f36))
- Added `Illuminate\Foundation\Testing\TestResponse::assertNoContent()` method ([#30125](https://github.com/laravel/framework/pull/30125))
- Added `InteractsWithQueue` to `SendQueueNotifications` ([#30140](https://github.com/laravel/framework/pull/30140))
- Added `SendQueueNotifications::retryUntil()` method ([#30141](https://github.com/laravel/framework/pull/30141))
- Added methods for sending cookies with test requests ([#30101](https://github.com/laravel/framework/pull/30101))
- Added support of job middleware for queued notifications ([#30070](https://github.com/laravel/framework/pull/30070))

### Fixed
- Fixed migration class duplicate check in `make:migration` command ([#30095](https://github.com/laravel/framework/pull/30095))
- Fixed monolog v2 handler preparation ([#30123](https://github.com/laravel/framework/pull/30123))
- Fixed return of callback value for DurationLimiter ([#30143](https://github.com/laravel/framework/pull/30143))

### Changed
- Added runtime information output for seeders ([#30086](https://github.com/laravel/framework/pull/30086))
- Added strict parameter to `Illuminate\Foundation\Testing\TestResponse::assertJsonPath()` ([#30142](https://github.com/laravel/framework/pull/30142))
- Added `deletedAtColumn` optional parameter to `Foundation\Testing\Concerns\InteractsWithDatabase::assertSoftDeleted()` ([#30111](https://github.com/laravel/framework/pull/30111))

### Improved
- Improved `AuthServiceProvider::registerEventRebindHandler()` in case if guard is not initialized ([#30105](https://github.com/laravel/framework/pull/30105))


## [v6.0.4 (2019-09-24)](https://github.com/laravel/framework/compare/v6.0.3...v6.0.4)

### Added
- Added `TestResponse::assertJsonPath()` method ([#29957](https://github.com/laravel/framework/pull/29957))
- Added `hasMacro` / `getGlobalMacro` / `hasGlobalMacro` methods to `Eloquent Builder` ([#30008](https://github.com/laravel/framework/pull/30008))
- Added `Illuminate\Database\Eloquent\Relations\BelongsToMany::getPivotColumns()` method ([#30049](https://github.com/laravel/framework/pull/30049)) 
- Added `ScheduledTaskFinished` / `ScheduledTaskStarting` events to signal when scheduled task runs ([#29888](https://github.com/laravel/framework/pull/29888))
- Allowing adding command arguments and options with `InputArgument` / `InputOption` objects ([#29987](https://github.com/laravel/framework/pull/29987))

### Fixed
- Fixed `__()` with `null` parameter ([#29967](https://github.com/laravel/framework/pull/29967)) 
- Fixed modifying `updated_at` column on custom pivot model ([#29970](https://github.com/laravel/framework/pull/29970))
- Fixed `Illuminate\Redis\Limiters\ConcurrencyLimiter` ([#30005](https://github.com/laravel/framework/pull/30005))
- Fixed `VerifyCsrfToken` middleware when response object instance of `Responsable` interface ([#29972](https://github.com/laravel/framework/pull/29972))
- Fixed Postgresql column creation without optional precision ([#29873](https://github.com/laravel/framework/pull/29873))
- Fixed migrations orders with multiple path with certain filenames ([#29996](https://github.com/laravel/framework/pull/29996))
- Fixed adding `NotFoundHttpException` to "allowed" exceptions in tests ([#29975](https://github.com/laravel/framework/pull/29975))

### Changed
- Make it possible to disable encryption via `0` / `false` ([#29985](https://github.com/laravel/framework/pull/29985))
- Allowed a symfony file instance in validate dimensions ([#30009](https://github.com/laravel/framework/pull/30009))
- Create storage fakes with custom configuration ([#29999](https://github.com/laravel/framework/pull/29999))
- Set locale in `PendingMail` only if locale present conditionally ([dd1e0a6](https://github.com/laravel/framework/commit/dd1e0a604713ddae21e6a893e4f605a6777300e8))
- Improved sorting of imports alphabetically on class generation from stub ([#29951](https://github.com/laravel/framework/pull/29951))

### Refactoring
- Changed imports to Alpha ordering in stubs ([#29954](https://github.com/laravel/framework/pull/29954), [#29958](https://github.com/laravel/framework/pull/29958))
- Used value helper where possible ([#29959](https://github.com/laravel/framework/pull/29959))
- Improved readability in `auth.throttle` translation ([#30011](https://github.com/laravel/framework/pull/30011), [#30017](https://github.com/laravel/framework/pull/30017))


## [v6.0.3 (2019-09-10)](https://github.com/laravel/framework/compare/v6.0.2...v6.0.3)

### Reverted
- Reverted [Wrapped `MySQL` default values in parentheses](https://github.com/laravel/framework/pull/29878) ([#29943](https://github.com/laravel/framework/pull/29943))

### Refactoring
- Converted `call_user_func` where appropriate to native calls ([#29932](https://github.com/laravel/framework/pull/29932))
- Changed imports to Alpha ordering ([#29933](https://github.com/laravel/framework/pull/29933))


## [v6.0.2 (2019-09-10)](https://github.com/laravel/framework/compare/v6.0.1...v6.0.2)

### Changed
- Used `Application::normalizeCachePath()` method to define cache path`s ([#29890](https://github.com/laravel/framework/pull/29890), [ac9dbf6](https://github.com/laravel/framework/commit/ac9dbf6beaded2ad86f5595958c75e3c4b1147ae))
- Wrapped `MySQL` default values in parentheses ([#29878](https://github.com/laravel/framework/pull/29878))

### Fixed
- Prevent `event auto discovery` from crashing when trying to instantiate files without php classes ([#29895](https://github.com/laravel/framework/pull/29895))
- Fix resolving class command via container ([#29869](https://github.com/laravel/framework/pull/29869))


## [v6.0.1 (2019-09-06)](https://github.com/laravel/framework/compare/v6.0.0...v6.0.1)

### Fixed
- Fixed `Schedule::runInBackground()` not fired on Windows ([#29826](https://github.com/laravel/framework/pull/29826))

### Changed
- Throw `Symfony\Component\Routing\Exception\RouteNotFoundException` instead of `InvalidArgumentException` in `UrlGenerator::route()` ([#29861](https://github.com/laravel/framework/pull/29861))

### Reverted
- Reverted: [`Extract registered event and login to registered method`](https://github.com/laravel/framework/pull/27807) ([#29875](https://github.com/laravel/framework/pull/29875))


## [v6.0.0 (2019-09-03)](https://github.com/laravel/framework/compare/5.8...v6.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/6.0/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/6.x/releases).
