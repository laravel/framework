# Release Notes for 5.6.x

## [Unreleased]

### General
- ⚠️ Upgraded to Symfony 4 ([#22450](https://github.com/laravel/framework/pull/22450))
- ⚠️ Added `runningUnitTests()` to `Application` contract ([#21034](https://github.com/laravel/framework/pull/21034))
- ⚠️ Upgraded `cron-expression` to `2.x` ([#21637](https://github.com/laravel/framework/pull/21637))

### Artisan Console
- ⚠️ Removed deprecated `optimize` command ([#20851](https://github.com/laravel/framework/pull/20851))
- Show job id in `queue:work` output ([#21204](https://github.com/laravel/framework/pull/21204))
- Show batch number in `migrate:status` output ([#21391](https://github.com/laravel/framework/pull/21391))
- ⚠️ Added `$outputBuffer` argument to `call()` method in contracts ([#22463](https://github.com/laravel/framework/pull/22463))
- ⚠️ Convert Bootstrap preset to v4 ([#22754](https://github.com/laravel/framework/pull/22754))

### Authentication
- Use Bootstrap v4 in scaffolding ([#22494](https://github.com/laravel/framework/pull/22494))
- Support customizing the mail message building in `ResetPassword::toMail()` ([6535186](https://github.com/laravel/framework/commit/6535186b0f71a6b0cc2d8a821f3de209c05bcf4f))

### Blade Templates
- Added `@csrf` and `@method` directives ([5f19844](https://github.com/laravel/framework/commit/5f1984421af096ef21b7d2011949a233849d4ee3))
- Added `Blade::component()` method for component aliases ([#22796](https://github.com/laravel/framework/pull/22796), [7c3ba0e](https://github.com/laravel/framework/commit/7c3ba0e61eae47d785d34448ca8d1e067dee6af7))

### Broadcasting
- ⚠️ Added support for channel classes ([#22583](https://github.com/laravel/framework/pull/22583), [434b348](https://github.com/laravel/framework/commit/434b348c5dda1b04486ca6134671d83046bd5c96), [043bd5e](https://github.com/laravel/framework/commit/043bd5e446cf737299476ea3a6498483282a9e41))

### Cache
- Removed `$decayMinutes` argument from `RateLimiter::tooManyAttempts()` ([#22202](https://github.com/laravel/framework/pull/22202))

### Collections
- ⚠️ Fixed keyless calls to `uniqueStrict()` ([#21854](https://github.com/laravel/framework/pull/21854))
- Added operator support to `Collection@partition()` ([#22380](https://github.com/laravel/framework/pull/22380))
- Improve performance of `Collection::mapToDictionary()` ([#22774](https://github.com/laravel/framework/pull/22774), [c09a0fd](https://github.com/laravel/framework/commit/c09a0fdb92a4aa42552723b2238713bc9a9b1adb))
- Accept array of keys on `Collection::except()` ([#22814](https://github.com/laravel/framework/pull/22814))

### Database
- ⚠️ Swap the index order of morph type and id ([#21693](https://github.com/laravel/framework/pull/21693))
- Added support for PostgreSQL comments ([#21855](https://github.com/laravel/framework/pull/21855), [#22453](https://github.com/laravel/framework/pull/22453))
- Better enumeration columns support ([#22109](https://github.com/laravel/framework/pull/22109), [9a3d71d](https://github.com/laravel/framework/commit/9a3d71da2278b5582d3a40857a97a905f26b901d))
- Prevent duplicated table prefix in `SQLiteGrammar::compileColumnListing()` ([#22340](https://github.com/laravel/framework/pull/22340), [#22781](https://github.com/laravel/framework/pull/22781))
- Support complex `update()` calls when using SQLite ([#22366](https://github.com/laravel/framework/pull/22366))
- Throws an exception if multiple calls to the underlying SQLite method aren't supported ([#22364](https://github.com/laravel/framework/pull/22364), [c877cb0](https://github.com/laravel/framework/commit/c877cb0cdc44243c691eb8507616a4c21a28599f))
- Made `whereTime()` operator argument optional ([#22378](https://github.com/laravel/framework/pull/22378))
- Changed transaction logic in `DatabaseQueue` ([#22433](https://github.com/laravel/framework/pull/22433))
- Added support for row values in where conditions ([#22446](https://github.com/laravel/framework/pull/22446))
- Fixed serialization of pivot models ([#22786](https://github.com/laravel/framework/pull/22786))

### Eloquent
- ⚠️ Serialize relationships ([#21229](https://github.com/laravel/framework/pull/21229))
- Allow setting custom owner key on polymorphic relationships ([#21310](https://github.com/laravel/framework/pull/21310))
- ⚠️ Sync model after `refresh()` ([#21905](https://github.com/laravel/framework/pull/21905))
- Make `MassAssignmentException` wording clear ([#22565](https://github.com/laravel/framework/pull/22565))
- Changed `HasAttributes::getDateFormat()` visibility to `public` ([#22618](https://github.com/laravel/framework/pull/22618))
- Added `BelongsToMany::getPivotClass()` method ([641d087](https://github.com/laravel/framework/commit/641d0875a25ff153c4b2b7292b1d6c4ea717cb66))
- Ensure Pivot model's `$dateFormat` is used when creating a pivot record ([a433ff8](https://github.com/laravel/framework/commit/a433ff8a9bcd88ddfe2335801a15c71b4d1a0a3a))
- Added `BelongsToMany::withPivotValues()` method ([#22867](https://github.com/laravel/framework/pull/22867))

### Hashing
- ⚠️ Added support for Argon ([#21885](https://github.com/laravel/framework/pull/21885), [68ac51a](https://github.com/laravel/framework/commit/68ac51a3c85d039799d32f53a045328e14debfea), [#22087](https://github.com/laravel/framework/pull/22087))

### Helpers
- ⚠️ Return an empty array from `Arr::wrap()` when called with `null` ([#21745](https://github.com/laravel/framework/pull/21745))
- Return class traits in use order from `class_uses_recursive()` ([#22537](https://github.com/laravel/framework/pull/22537))
- Added `Str::uuid()` and `Str::orderedUuid()` ([3d39604](https://github.com/laravel/framework/commit/3d39604bba72d45dab5b53951af42bbb21110cad))

### Logging
- ⚠️ Refactored Logging component ([#22635](https://github.com/laravel/framework/pull/22635), [106ac2a](https://github.com/laravel/framework/commit/106ac2a7a1b337afd9edd11367039e3511c85f81), [7ba0c22](https://github.com/laravel/framework/commit/7ba0c22133da7ca99d1ec1459630de01f95130c1))
- Use application name as syslog identifier ([#22267](https://github.com/laravel/framework/pull/22267))

### Mail
- ⚠️ Added `$data` property to mail events ([#21804](https://github.com/laravel/framework/pull/21804))
- Added support for setting HTML in emails ([#22809](https://github.com/laravel/framework/pull/22809))

### Notifications
- Pass notification instance to `routeNotificationFor*()` methods ([#22289](https://github.com/laravel/framework/pull/22289))

### Queues
- ⚠️ Added `payload()` and `getJobId()` to `Job` contract ([#21303](https://github.com/laravel/framework/pull/21303))
- Removed unused `Worker::raiseFailedJobEvent()` method ([#21901](https://github.com/laravel/framework/pull/21901))
- Support blocking pop from Redis queues ([#22284](https://github.com/laravel/framework/pull/22284))

### Requests
- ⚠️ Return `false` from `expectsJson()` when requested content type isn't explicit ([#22506](https://github.com/laravel/framework/pull/22506), [3624d27](https://github.com/laravel/framework/commit/3624d2702c783d13bd23b852ce35662bee9a8fea))

### Responses
- Added missing `$raw` and `$sameSite` parameters to `Cookie\Factory` methods ([#21553](https://github.com/laravel/framework/pull/21553))
- ⚠️ Return `201` status if Model was recently created ([#21625](https://github.com/laravel/framework/pull/21625))
- Set original response JSON responses ([#22455](https://github.com/laravel/framework/pull/22455))
- Added `streamDownload()` method ([#22777](https://github.com/laravel/framework/pull/22777))
- ⚠️ Allow insecure cookies when `session.secure` is `true` ([#22812](https://github.com/laravel/framework/pull/22812))

### Routing
- Added `SetCacheHeaders` middleware ([#22389](https://github.com/laravel/framework/pull/22389), [f6f386b](https://github.com/laravel/framework/commit/f6f386ba6456894215b1314c0e33f956026dffec), [df06357](https://github.com/laravel/framework/commit/df06357d78629a479d341329571136d21ae02f6f))

### Service Container
- Support bulk binding in service providers during registration ([#21961](https://github.com/laravel/framework/pull/21961), [81e29b1](https://github.com/laravel/framework/commit/81e29b1f09af7095df219efd18185f0818f5b698))

### Support
- ⚠️ Throw exception if `Manager::driver()` is called with `null` ([#22018](https://github.com/laravel/framework/pull/22018))

### Task Scheduling
- ⚠️ Multi server scheduling cron support ([#22216](https://github.com/laravel/framework/pull/22216), [6563ba6](https://github.com/laravel/framework/commit/6563ba65b65106198095f1d61f91e0ec542e98dd))

### Testing
- ⚠️ Switched to PHPUnit 7 ([#23005](https://github.com/laravel/framework/pull/23005))
- Support fetching specific key when using json helpers ([#22489](https://github.com/laravel/framework/pull/22489))
- Added assertions to verify the order of strings in a response ([#22915](https://github.com/laravel/framework/pull/22915))
- Use `DatabaseTransactions` trait in `RefreshDatabase` ([#22596](https://github.com/laravel/framework/pull/22596))

### Validation
- ⚠️ Ignore SVGs in `validateDimensions()` ([#21390](https://github.com/laravel/framework/pull/21390))
- ⚠️ Renamed `validate()` to `validateResolved()` ([33d8642](https://github.com/laravel/framework/commit/33d864240a770f821df419e2d16d841d94968415))
