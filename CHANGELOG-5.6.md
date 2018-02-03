# Release Notes for 5.6.x

## [Unreleased]

### General
- ⚠️ Added `runningUnitTests()` to `Application` contract ([#21034](https://github.com/laravel/framework/pull/21034))
- ⚠️ Upgraded `cron-expression` to `2.x` ([#21637](https://github.com/laravel/framework/pull/21637))

### Artisan Console
- ⚠️ Removed deprecated `optimize` command ([#20851](https://github.com/laravel/framework/pull/20851))
- Show job id in `queue:work` output ([#21204](https://github.com/laravel/framework/pull/21204))
- Show batch number in `migrate:status` output ([#21391](https://github.com/laravel/framework/pull/21391))

### Blade Templates
- Added `@csrf` and `@method` directives ([5f19844](https://github.com/laravel/framework/commit/5f1984421af096ef21b7d2011949a233849d4ee3))

### Cache
- Removed `$decayMinutes` argument from `RateLimiter::tooManyAttempts()` ([#22202](https://github.com/laravel/framework/pull/22202))

### Collections
- ⚠️ Fixed keyless calls to `uniqueStrict()` ([#21854](https://github.com/laravel/framework/pull/21854))
- Added operator support to `Collection@partition()` ([#22380](https://github.com/laravel/framework/pull/22380))

### Database
- ⚠️ Swap the index order of morph type and id ([#21693](https://github.com/laravel/framework/pull/21693))
- Added support for PostgreSQL comments ([#21855](https://github.com/laravel/framework/pull/21855))
- Better enumeration columns support ([#22109](https://github.com/laravel/framework/pull/22109), [9a3d71d](https://github.com/laravel/framework/commit/9a3d71da2278b5582d3a40857a97a905f26b901d))
- Prevent duplicated table prefix in `SQLiteGrammar::compileColumnListing()` ([#22340](https://github.com/laravel/framework/pull/22340))
- Support complex `update()` calls when using SQLite ([#22366](https://github.com/laravel/framework/pull/22366))
- Throws an exception if multiple calls to the underlying SQLite method aren't supported ([#22364](https://github.com/laravel/framework/pull/22364), [c877cb0](https://github.com/laravel/framework/commit/c877cb0cdc44243c691eb8507616a4c21a28599f))
- Made `whereTime()` operator argument optional ([#22378](https://github.com/laravel/framework/pull/22378))

### Eloquent
- ⚠️ Serialize relationships ([#21229](https://github.com/laravel/framework/pull/21229))
- Allow setting custom owner key on polymorphic relationships ([#21310](https://github.com/laravel/framework/pull/21310))
- ⚠️ Sync model after `refresh()` ([#21905](https://github.com/laravel/framework/pull/21905))

### Hashing
- ⚠️ Added support for Argon ([#21885](https://github.com/laravel/framework/pull/21885), [68ac51a](https://github.com/laravel/framework/commit/68ac51a3c85d039799d32f53a045328e14debfea), [#22087](https://github.com/laravel/framework/pull/22087))

### Helpers
- ⚠️ Return an empty array from `Arr::wrap()` when called with `null` ([#21745](https://github.com/laravel/framework/pull/21745))

### Logging
- Use application name as syslog identifier ([#22267](https://github.com/laravel/framework/pull/22267))

### Mail
- ⚠️ Added `$data` property to mail events ([#21804](https://github.com/laravel/framework/pull/21804))

### Notifications
- Pass notification instance to `routeNotificationFor*()` methods ([#22289](https://github.com/laravel/framework/pull/22289))

### Queues
- ⚠️ Added `payload()` and `getJobId()` to `Job` contract ([#21303](https://github.com/laravel/framework/pull/21303))
- Removed unused `Worker::raiseFailedJobEvent()` method ([#21901](https://github.com/laravel/framework/pull/21901))
- Support blocking pop from Redis queues ([#22284](https://github.com/laravel/framework/pull/22284))

### Routing
- Added `SetCacheHeaders` middleware ([#22389](https://github.com/laravel/framework/pull/22389), [f6f386b](https://github.com/laravel/framework/commit/f6f386ba6456894215b1314c0e33f956026dffec), [df06357](https://github.com/laravel/framework/commit/df06357d78629a479d341329571136d21ae02f6f))

### Responses
- Added missing `$raw` and `$sameSite` parameters to `Cookie\Factory` methods ([#21553](https://github.com/laravel/framework/pull/21553))
- ⚠️ Return `201` status of Model was recently created ([#21625](https://github.com/laravel/framework/pull/21625))

### Service Container
- Support bulk binding in service providers during registration ([#21961](https://github.com/laravel/framework/pull/21961), [81e29b1](https://github.com/laravel/framework/commit/81e29b1f09af7095df219efd18185f0818f5b698))

### Support
- ⚠️ Throw exception if `Manager::driver()` is called with `null` ([#22018](https://github.com/laravel/framework/pull/22018))

### Task Scheduling
- ⚠️ Multi server scheduling cron support ([#22216](https://github.com/laravel/framework/pull/22216), [6563ba6](https://github.com/laravel/framework/commit/6563ba65b65106198095f1d61f91e0ec542e98dd))

### Testing
- ⚠️ Switched to PHPUnit 7 ([#23005](https://github.com/laravel/framework/pull/23005))

### Validation
- ⚠️ Ignore SVGs in `validateDimensions()` ([#21390](https://github.com/laravel/framework/pull/21390))
