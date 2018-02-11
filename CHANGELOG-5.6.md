# Release Notes for 5.6.x

## [Unreleased]

### Changed
- Moved clone logic from `FormRequestServiceProvider` to `Request` ([b0c2459](https://github.com/laravel/framework/commit/b0c2459d7e55519d1c61927ab526e489a3a52eaf))

### Fixed
- Returns same `Logger` instance from `LogManager` ([#23118](https://github.com/laravel/framework/pull/23118))
- Register missing `hash.driver` DI ([#23114](https://github.com/laravel/framework/pull/23114))


## v5.6.3 (2018-02-09)

### Fixed
- Fixed an issue in `TestResponse::assertSessionHasErrors()` ([#23093](https://github.com/laravel/framework/pull/23093))
- Update Vue and React presets to Bootstrap v4 ([8a9c5c4](https://github.com/laravel/framework/commit/8a9c5c45388fda18aaa5564be131a3144c38b9ce))


## v5.6.2 (2018-02-08)

### Changed
- Support customization of schedule mutex cache store ([20e2919](https://github.com/laravel/framework/commit/20e29199365a11b31e35179bbfe3e83485e05a03))

### Fixed
- Reverted changes to `TestResponse::assertSessionHasErrors()` [#23055](https://github.com/laravel/framework/pull/23055) ([0362a90](https://github.com/laravel/framework/commit/0362a90fca47de6c283d8ef8c68affefc7b410cf))


## v5.6.1 (2018-02-08)

### Added
- Added Slack attachment pretext attribute ([#23075](https://github.com/laravel/framework/pull/23075))

### Changed
- Added missing nested joins in `Grammar::compileJoins()` ([#23059](https://github.com/laravel/framework/pull/23059))
- Improved session errors assertions in `TestResponse::assertSessionHasErrors()` ([#23055](https://github.com/laravel/framework/pull/23055))

### Fixed
- Fixed `BelongsToMany` pivot relation wakeup ([#23081](https://github.com/laravel/framework/pull/23081))

### Removed
- Removed monolog configurator ([#23078](https://github.com/laravel/framework/pull/23078))


## v5.6.0 (2018-02-07)

### General
- ⚠️ Upgraded to Symfony 4 ([#22450](https://github.com/laravel/framework/pull/22450))
- ⚠️ Upgraded to Bootstrap 4 ([#22754](https://github.com/laravel/framework/pull/22754), [#22494](https://github.com/laravel/framework/pull/22494), [25559cd](https://github.com/laravel/framework/commit/25559cdc14066566658d6c9a7efd8a0e1d0ffccd), [12d789d](https://github.com/laravel/framework/commit/12d789de8472dbbd763cb680e896b3d419f954c0))
- ⚠️ Added `runningUnitTests()` to `Application` contract ([#21034](https://github.com/laravel/framework/pull/21034))
- ⚠️ Upgraded `cron-expression` to `2.x` ([#21637](https://github.com/laravel/framework/pull/21637))

### Artisan Console
- ⚠️ Removed deprecated `optimize` command ([#20851](https://github.com/laravel/framework/pull/20851))
- Show job id in `queue:work` output ([#21204](https://github.com/laravel/framework/pull/21204))
- Show batch number in `migrate:status` output ([#21391](https://github.com/laravel/framework/pull/21391))
- ⚠️ Added `$outputBuffer` argument to `call()` method in contracts ([#22463](https://github.com/laravel/framework/pull/22463))
- Added `--realpath` argument to migration commands ([#22852](https://github.com/laravel/framework/pull/22852), [98842da](https://github.com/laravel/framework/commit/98842da800f08c45577dbad13d0c8456370ecd8e))
- Added `--api` argument to `make:controller` ([#22996](https://github.com/laravel/framework/pull/22996), [dcc6123](https://github.com/laravel/framework/commit/dcc6123453e792084d3eda186898ea7a1f536faa))

### Authentication
- Support customizing the mail message building in `ResetPassword::toMail()` ([6535186](https://github.com/laravel/framework/commit/6535186b0f71a6b0cc2d8a821f3de209c05bcf4f))
- Added `AuthServiceProvider::policies()` method ([6d8e530](https://github.com/laravel/framework/commit/6d8e53082c188c89f765bf016d1e4bca7802b025))

### Blade Templates
- Added `@csrf` and `@method` directives ([5f19844](https://github.com/laravel/framework/commit/5f1984421af096ef21b7d2011949a233849d4ee3), [#22912](https://github.com/laravel/framework/pull/22912))
- Added `Blade::component()` method for component aliases ([#22796](https://github.com/laravel/framework/pull/22796), [7c3ba0e](https://github.com/laravel/framework/commit/7c3ba0e61eae47d785d34448ca8d1e067dee6af7))
- ⚠️ Made double encoding the default ([7c82ff4](https://github.com/laravel/framework/commit/7c82ff408432c56a324524712723a93df637936e))

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
- Fixed serialization of pivot models ([#22786](https://github.com/laravel/framework/pull/22786), [8fad785](https://github.com/laravel/framework/commit/8fad785de66ffaa18e7d8b9e9cd7c4465e60daac), [351e3b7](https://github.com/laravel/framework/commit/351e3b7694a804e8d6a613288419ccabd22bc012))
- ⚠️ Accept `Throwable` in `DetectsLostConnections` ([#22948](https://github.com/laravel/framework/pull/22948))

### Eloquent
- ⚠️ Serialize relationships ([#21229](https://github.com/laravel/framework/pull/21229))
- Allow setting custom owner key on polymorphic relationships ([#21310](https://github.com/laravel/framework/pull/21310))
- ⚠️ Sync model after `refresh()` ([#21905](https://github.com/laravel/framework/pull/21905))
- Make `MassAssignmentException` wording clear ([#22565](https://github.com/laravel/framework/pull/22565))
- Changed `HasAttributes::getDateFormat()` visibility to `public` ([#22618](https://github.com/laravel/framework/pull/22618))
- Added `BelongsToMany::getPivotClass()` method ([641d087](https://github.com/laravel/framework/commit/641d0875a25ff153c4b2b7292b1d6c4ea717cb66))
- Ensure Pivot model's `$dateFormat` is used when creating a pivot record ([a433ff8](https://github.com/laravel/framework/commit/a433ff8a9bcd88ddfe2335801a15c71b4d1a0a3a))
- Added `BelongsToMany::withPivotValues()` method ([#22867](https://github.com/laravel/framework/pull/22867))
- Added `forceDeleted` event ([497a907](https://github.com/laravel/framework/commit/497a90749312b0b75fc185246c94e6150a502773))
- ⚠️ Relocate the existence check for factory definitions to `FactoryBuilder::getRawAttributes()` ([#22936](https://github.com/laravel/framework/pull/22936))
- ⚠️ Change `Resource` name away from soft-reserved name ([#22969](https://github.com/laravel/framework/pull/22969), [aad6089](https://github.com/laravel/framework/commit/aad6089702a2bbe89b6971b3feb3e202fea9f4d9))
- Added support for casting to custom date formats ([#22989](https://github.com/laravel/framework/pull/22989), [1f902c8](https://github.com/laravel/framework/commit/1f902c84b25f8799cc4f781ad549158db4167110))

### Hashing
- ⚠️ Added support for Argon ([#21885](https://github.com/laravel/framework/pull/21885), [68ac51a](https://github.com/laravel/framework/commit/68ac51a3c85d039799d32f53a045328e14debfea), [#22087](https://github.com/laravel/framework/pull/22087), [9b46485](https://github.com/laravel/framework/commit/9b4648523debeb6c8ef70811d778b9be64312bd3))

### Helpers
- ⚠️ Return an empty array from `Arr::wrap()` when called with `null` ([#21745](https://github.com/laravel/framework/pull/21745))
- Return class traits in use order from `class_uses_recursive()` ([#22537](https://github.com/laravel/framework/pull/22537))
- Added `Str::uuid()` and `Str::orderedUuid()` ([3d39604](https://github.com/laravel/framework/commit/3d39604bba72d45dab5b53951af42bbb21110cad))

### Logging
- ⚠️ Refactored Logging component ([#22635](https://github.com/laravel/framework/pull/22635), [106ac2a](https://github.com/laravel/framework/commit/106ac2a7a1b337afd9edd11367039e3511c85f81), [7ba0c22](https://github.com/laravel/framework/commit/7ba0c22133da7ca99d1ec1459630de01f95130c1), [03f870c](https://github.com/laravel/framework/commit/03f870cb0b0eefde363b8985843aba68446a407c), [e691230](https://github.com/laravel/framework/commit/e691230578b010fe753f1973d5ab218a6510c0e9))
- Use application name as syslog identifier ([#22267](https://github.com/laravel/framework/pull/22267))

### Mail
- ⚠️ Added `$data` property to mail events ([#21804](https://github.com/laravel/framework/pull/21804))
- ⚠️ Call message сustomization callbacks before building content/attachments ([#22995](https://github.com/laravel/framework/pull/22995))
- Added support for setting HTML in emails ([#22809](https://github.com/laravel/framework/pull/22809))

### Notifications
- Pass notification instance to `routeNotificationFor*()` methods ([#22289](https://github.com/laravel/framework/pull/22289))

### Queues
- ⚠️ Added `payload()` and `getJobId()` to `Job` contract ([#21303](https://github.com/laravel/framework/pull/21303))
- Removed unused `Worker::raiseFailedJobEvent()` method ([#21901](https://github.com/laravel/framework/pull/21901))
- Support blocking pop from Redis queues ([#22284](https://github.com/laravel/framework/pull/22284), [dbad055](https://github.com/laravel/framework/commit/dbad05599b2d2059e45c480fac8817d1135d5da1), [5923416](https://github.com/laravel/framework/commit/59234169c3b3b7a7164fda206778224311e06fe2))

### Requests
- ⚠️ Return `false` from `expectsJson()` when requested content type isn't explicit ([#22506](https://github.com/laravel/framework/pull/22506), [3624d27](https://github.com/laravel/framework/commit/3624d2702c783d13bd23b852ce35662bee9a8fea))
- Added `Request::getSession()` method ([e546a5b](https://github.com/laravel/framework/commit/e546a5b83aa9fb5bbcb8e80db0c263c09b5d5dd6))
- Accept array of keys on `Request::hasAny()` ([#22952](https://github.com/laravel/framework/pull/22952))

### Responses
- Added missing `$raw` and `$sameSite` parameters to `Cookie\Factory` methods ([#21553](https://github.com/laravel/framework/pull/21553))
- ⚠️ Return `201` status if Model was recently created ([#21625](https://github.com/laravel/framework/pull/21625))
- Set original response JSON responses ([#22455](https://github.com/laravel/framework/pull/22455))
- Added `streamDownload()` method ([#22777](https://github.com/laravel/framework/pull/22777))
- ⚠️ Allow insecure cookies when `session.secure` is `true` ([#22812](https://github.com/laravel/framework/pull/22812))

### Routing
- Added `SetCacheHeaders` middleware ([#22389](https://github.com/laravel/framework/pull/22389), [f6f386b](https://github.com/laravel/framework/commit/f6f386ba6456894215b1314c0e33f956026dffec), [df06357](https://github.com/laravel/framework/commit/df06357d78629a479d341329571136d21ae02f6f))
- Support pulling rate limit from the user instance in `ThrottleRequests` ([c9e6100](https://github.com/laravel/framework/commit/c9e61007d38f0cd5434551ebd7bf9c2a139f4e61))

### Service Container
- Support bulk binding in service providers during registration ([#21961](https://github.com/laravel/framework/pull/21961), [81e29b1](https://github.com/laravel/framework/commit/81e29b1f09af7095df219efd18185f0818f5b698))

### Session
- Support dot notation in `Session::exists()` ([#22935](https://github.com/laravel/framework/pull/22935))

### Support
- ⚠️ Throw exception if `Manager::driver()` is called with `null` ([#22018](https://github.com/laravel/framework/pull/22018))
- ⚠️ Added `hasCommandHandler()`, `getCommandHandler()` and `map()` to `Bus\Dispatcher` contract ([#22958](https://github.com/laravel/framework/pull/22958), [#22986](https://github.com/laravel/framework/pull/22986))
- Added `useBootstrapThree()` helper to paginators ([c919402](https://github.com/laravel/framework/commit/c919402d5847830c1b2a39529cac90251f838709))

### Task Scheduling
- ⚠️ Multi server scheduling cron support ([#22216](https://github.com/laravel/framework/pull/22216), [6563ba6](https://github.com/laravel/framework/commit/6563ba65b65106198095f1d61f91e0ec542e98dd))

### Testing
- ⚠️ Switched to PHPUnit 7 ([#23005](https://github.com/laravel/framework/pull/23005))
- Support fetching specific key when using json helpers ([#22489](https://github.com/laravel/framework/pull/22489))
- Use `DatabaseTransactions` trait in `RefreshDatabase` ([#22596](https://github.com/laravel/framework/pull/22596))
- Added `assertSeeInOrder()` and `assertSeeTextInOrder()` methods ([#22915](https://github.com/laravel/framework/pull/22915), [#23038](https://github.com/laravel/framework/pull/23038))

### Validation
- ⚠️ Ignore SVGs in `validateDimensions()` ([#21390](https://github.com/laravel/framework/pull/21390))
- ⚠️ Renamed `validate()` to `validateResolved()` ([33d8642](https://github.com/laravel/framework/commit/33d864240a770f821df419e2d16d841d94968415))
