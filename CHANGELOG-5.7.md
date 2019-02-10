# Release Notes for 5.7.x

## [Unreleased](https://github.com/laravel/framework/compare/v5.7.25...5.7)

### Added
- Added `Illuminate\Pipeline\Pipeline::thenReturn()` ([#27429](https://github.com/laravel/framework/pull/27429)) 
- Added `Illuminate\Cache\TaggedCache::getTags()` ([#27445](https://github.com/laravel/framework/pull/27445))

### TODO:
- https://github.com/laravel/framework/pull/27419, https://github.com/laravel/framework/commit/5c5d6b24f6156768575ae49aa84d7b1d004f23fe
- https://github.com/laravel/framework/pull/27430
- https://github.com/laravel/framework/pull/27450


## [v5.7.25 (2019-02-05)](https://github.com/laravel/framework/compare/v5.7.24...v5.7.25)

### Added
- Allowed specifying custom translation for date relative messages ([#27341](https://github.com/laravel/framework/pull/27341))
- Add computed support to SQL Server schema grammar ([#27346](https://github.com/laravel/framework/pull/27346), [1c74d7f](https://github.com/laravel/framework/commit/1c74d7fe595df223279de55dffc7ae6fc8ac9ca6))
- Allowed `ENV` to control paths of `cache files` for `services`, `packages` and `routes` ([#27389](https://github.com/laravel/framework/pull/27389))

### Fixed
- Fixed `BelongsToMany` pivot relationship child with loaded relations wakeup ([#27358](https://github.com/laravel/framework/pull/27358))
- Fixed wrong class being used when eager loading nullable `MorphTo` with `withDefault()` ([#27411](https://github.com/laravel/framework/pull/27411))

### Changed
- Removed `php_network_getaddresses: getaddrinfo failed: Name or service not known` in `DetectsLostConnections` trait ([#27418](https://github.com/laravel/framework/pull/27418))


## [v5.7.24 (2019-01-30)](https://github.com/laravel/framework/compare/v5.7.23...v5.7.24)

### Fixed
- Fixed `ResetPassword` notification ([#27351](https://github.com/laravel/framework/pull/27351), [b130771](https://github.com/laravel/framework/commit/b13077164bc6b4cb032b9eec7f5209402b5260eb))


## [v5.7.23 (2019-01-29)](https://github.com/laravel/framework/compare/v5.7.22...v5.7.23)

### Added
- Added `AbstractPaginator::getOptions()` method ([#27273](https://github.com/laravel/framework/pull/27273))
- Added `Communication link failure` to `DetectsLostConnections` trait ([#27307](https://github.com/laravel/framework/pull/27307))
- Added `orWhere()` `proxy` for `scopes` to `EloquentBuilder` ([#27281](https://github.com/laravel/framework/pull/27281), [2e6fe85](https://github.com/laravel/framework/commit/2e6fe855c7d7d9d3cbf34e1fbea17c8059640c5c))
- Allow the `app path` to be configured ([#27332](https://github.com/laravel/framework/pull/27332), [d73e672](https://github.com/laravel/framework/commit/d73e6729cefb26c2fbcb16e47daefc2ba86b9697))
- Added config for using `WhatFailureGroupHandler` when `StackDriver` created for Logger ([#27308](https://github.com/laravel/framework/pull/27308) ,[6a92651](https://github.com/laravel/framework/commit/6a926519e8e3905013569e7b3fcdd598ec7cece3))

### Fixed
- Fixed `QueueableCollection` serialization of Eloquent Models when using `Binary IDs` ([#27271](https://github.com/laravel/framework/pull/27271))
- Replaced `newModelQuery()` with `newQueryWithoutRelationships()` for `UPDATE`/`DELETE` queries ([#27277](https://github.com/laravel/framework/pull/27277))

### Changed
- Apply parameters to entire localization array ([#27254](https://github.com/laravel/framework/pull/27254))
- Added line about expiring password reset in notification email ([#27324](https://github.com/laravel/framework/pull/27324), [80c5aec](https://github.com/laravel/framework/commit/80c5aecb443e7a55e868b66b9e0a93b7dfec08e8))
- "Go Home" link will redirect to the `home` route on exception page ([#27343](https://github.com/laravel/framework/pull/27343))


## [v5.7.22 (2019-01-22)](https://github.com/laravel/framework/compare/v5.7.21...v5.7.22)

### Fixed
- Fixed `TestResponse::assertJsonValidationErrors()` when there are no errors ([#27190](https://github.com/laravel/framework/pull/27190))

### Changed
- Allowed `TestResponse::assertJsonMissingValidationErrors()` to be called without an argument ([#27176](https://github.com/laravel/framework/pull/27176))
- Updated vue preset's vue-stubs for laravel-mix 4 compatibility ([#27229](https://github.com/laravel/framework/pull/27229))
- Updated preset to use `@babel/preset-react` ([#27235](https://github.com/laravel/framework/pull/27235))
- Used `config` to resolve the database value during tests. ([#27240](https://github.com/laravel/framework/pull/27240))


## [v5.7.21 (2019-01-15)](https://github.com/laravel/framework/compare/v5.7.20...v5.7.21)

### Fixed
- Fixed `Blueprint::removeColumn()` ([#27115](https://github.com/laravel/framework/pull/27115), [#27122](https://github.com/laravel/framework/pull/27122))
- Fixed allowing of null broadcast connection driver ([#27135](https://github.com/laravel/framework/pull/27135))
- Fixed `ModelMakeCommand::handle()` should always return `bool` value ([#27156](https://github.com/laravel/framework/pull/27156))
- Fixed `TestResponse::assertSessionDoesntHaveErrors()` when there are no errors ([#27145](https://github.com/laravel/framework/pull/27145))
- Fixed default message is localization twice in `403.blade.php` error page ([4a08120](https://github.com/laravel/framework/commit/4a081204d65a6e01959d795e71770079588bad21))

### Changed
- Replaced `get_called_class()` to `static::class` ([#27146](https://github.com/laravel/framework/pull/27146))
- Re-throw `NoMatchingExpectationException` from `PendingCommand` ([#27158](https://github.com/laravel/framework/pull/27158))


## [v5.7.20 (2019-01-08)](https://github.com/laravel/framework/compare/v5.7.19...v5.7.20)

### Added
- Added `chunkById` support in relations ([#26919](https://github.com/laravel/framework/pull/26919))
- Added `Collection::whereNotBetween` method ([#27028](https://github.com/laravel/framework/pull/27028))
- Allowed predefined log channels to change formatter from config ([#26895](https://github.com/laravel/framework/pull/26895))
- Allowed storage assertions (`FilesystemAdapter::assertExists` / `FilesystemAdapter::assertMissing`) to handle multiple files at once ([#26975](https://github.com/laravel/framework/pull/26975))
- Added `Adaptive Server connection failed` to `DetectsLostConnections` trait ([#27055](https://github.com/laravel/framework/pull/27055))
- Added `Route::originalParameters()` ([#27056](https://github.com/laravel/framework/pull/27056))
- Added `QueueFake::pushedJobs()` ([#27089](https://github.com/laravel/framework/pull/27089), [695ffa1](https://github.com/laravel/framework/commit/695ffa1247a7a44a79ba85442ad9ea311413feae))

### Fixed
- Prevents unnecessary queries when lazy loading empty relationships ([#26992](https://github.com/laravel/framework/pull/26992))
- Fixed broken `Command::setHidden` method ([#27005](https://github.com/laravel/framework/pull/27005))
- Fixed `Str::slug` method ([#27002](https://github.com/laravel/framework/pull/27002))
- Ignore `--seed` option for `artisan migrate --pretend` ([#27015](https://github.com/laravel/framework/pull/27015)) 
- Fixed `previousUrl` in the session if the call is `prefetch` ([#27017](https://github.com/laravel/framework/pull/27017))
- Fixed nullable `MorphTo` touching ([#27031](https://github.com/laravel/framework/pull/27031))
- Fixed `Collection::loadMissing()` with duplicate relation names ([#27040](https://github.com/laravel/framework/pull/27040))
- Fixed some commands ([#27020](https://github.com/laravel/framework/pull/27020))
- Ensured the command `context` is forwarded to calls ([#27012](https://github.com/laravel/framework/pull/27012), [#27065](https://github.com/laravel/framework/pull/27065))
- Fixed `Collection::loadMorph()` issue relations loading issue ([#27081](https://github.com/laravel/framework/pull/27081))

### Changed
- Removed `HasOneOrMany::update()` since `Builder::update()` already adds the `UPDATED_AT` timestamp. ([#27026](https://github.com/laravel/framework/pull/27026))
- Changed `Name or service not known` to `php_network_getaddresses: getaddrinfo failed: Name or service not known` in `DetectsLostConnections` trait ([#27054](https://github.com/laravel/framework/pull/27054), [5459ac1](https://github.com/laravel/framework/commit/5459ac15b56cdee8e176827ddbb30357119ceabb))
- Changed Eloquent `ApiResource merge()` methods to accept `JsonResource` object  ([#27068](https://github.com/laravel/framework/pull/27068))
- Stop email re-verification with same link ([#27070](https://github.com/laravel/framework/pull/27070)) 


## [v5.7.19 (2018-12-18)](https://github.com/laravel/framework/compare/v5.7.18...v5.7.19)

### Added
- Added `Illuminate\Support\Collection::whereBetween` method ([#26888](https://github.com/laravel/framework/pull/26888))

### Fixed
- <strong> Reverted changes related to [`app()->call()`](https://github.com/laravel/framework/pull/26852) </strong> ([fefaf46](https://github.com/laravel/framework/commit/fefaf46dd147a4caf1dea1712f9797f3db49fea4))
- Reset doctrineConnection property on Database/Connection when reconnecting ([#26890](https://github.com/laravel/framework/pull/26890))


## [v5.7.18 (2018-12-17)](https://github.com/laravel/framework/compare/v5.7.17...v5.7.18)

### Added
- Added missing `starts_with` validation message ([#26822](https://github.com/laravel/framework/pull/26822))
- Added `Facade::resolved()` method to register pending callback until the service is available. ([#26824](https://github.com/laravel/framework/pull/26824))
- Added env var `APP_CONFIG_CACHE` to control cache config path ([578bc83](https://github.com/laravel/framework/commit/578bc83f0247b97ec87fefe39a8da7e9bbfd4a66))

### Changed
- Changed `TransportManager::createMailDriver` ([#26846](https://github.com/laravel/framework/pull/26846))

### Fixed
- Fixed of using `illuminate/mail` outside of Laravel with driver log ([#26842](https://github.com/laravel/framework/pull/26842))
- Fixed some bugs for `app()->call()` ([#26852](https://github.com/laravel/framework/pull/26852))
- Added workaround for PHP-bug related to [incorrect variable values when Opcache enabled in PHP v 7.3.0](https://github.com/laravel/framework/issues/26819) ([36d3436](https://github.com/laravel/framework/commit/36d343682d25570946ff22397a720727e0c1dcd7))


## [v5.7.17 (2018-12-12)](https://github.com/laravel/framework/compare/v5.7.16...v5.7.17)

### Added
- Added `Database\Query\Builder::insertUsing` method ([#26732](https://github.com/laravel/framework/pull/26732), [8216b46](https://github.com/laravel/framework/commit/8216b4607152f9b01f26efba6b045add5382c625)) 
- Added `Database\Query\Builder::havingBetween` method ([#26758](https://github.com/laravel/framework/pull/26758))
- Added `Packets out of order. Expected` string to `DetectsLostConnections` trait ([#26760](https://github.com/laravel/framework/pull/26760))
- Added `NOT VALID` option for skipping validation when adding postgres foreign keys ([#26775](https://github.com/laravel/framework/pull/26775))

### Fixed
- Fixed: Using `store` on an uploaded file when you push an empty file ([#26809](https://github.com/laravel/framework/pull/26809))
- Fixed hiding for hidden commands ([#26781](https://github.com/laravel/framework/pull/26781))


## [v5.7.16 (2018-12-05)](https://github.com/laravel/framework/compare/v5.7.15...v5.7.16)

### Added
- Added localization for `403.blade.php` and `503.blade.php` ([#26751](https://github.com/laravel/framework/pull/26751))
- Changing the Migrator to accept not only migration directory paths, but migration file paths too ([#26642](https://github.com/laravel/framework/pull/26642), [c4b13bf](https://github.com/laravel/framework/commit/c4b13bfd115bcfd54588ad2a5809fea2222d1cdb))

### Fixed
- Fixed self-referencing HasManyThrough existence queries ([#26662](https://github.com/laravel/framework/pull/26662))
- Fixed HasManyThrough existence queries with same parent and through parent table ([#26676](https://github.com/laravel/framework/pull/26676))
- Fixed breaking eager loading with "incrementing" string keys ([#26688](https://github.com/laravel/framework/pull/26688))
- Remove the Register `<li>` when the route doesn't exist in `app.stub` ([#26708](https://github.com/laravel/framework/pull/26708))
- Fixed `Collection::some` method ([#26696](https://github.com/laravel/framework/pull/26696))
- <strong> Revert breaking change in `TestResponse::decodeResponseJson` method </strong> ([#26713](https://github.com/laravel/framework/pull/26713))
- Fixed `PhpRedisConnection::mget` ([#26716](https://github.com/laravel/framework/pull/26716))
- Fixed `Eloquent\Collection::loadCount` attribute syncing ([#26714](https://github.com/laravel/framework/pull/26714))
- Fixed `Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase::seed` for array accepting ([#26734](https://github.com/laravel/framework/pull/26734)) 
- Fixed `FormRequest` validation triggering twice ([#26731](https://github.com/laravel/framework/pull/26731))

### Changed
- Changed markdown on auth stub view (`Auth/Console/stubs/make/views/auth/login.stub`) ([#26648](https://github.com/laravel/framework/pull/26648))
- Moved Slack and Nexmo notification channels to the own packages `laravel/nexmo-notification-channel`, `laravel/slack-notification-channel` ([#26689](https://github.com/laravel/framework/pull/26689), [#26727](https://github.com/laravel/framework/pull/26727))

### Deprecated
- `$cachedSchema` property in `UrlGenerator` is deprecated. Will be renamed to the `$cachedScheme` in 5.8 ([#26640](https://github.com/laravel/framework/pull/26640))


## [v5.7.15 (2018-11-26)](https://github.com/laravel/framework/compare/v5.7.14...v5.7.15)

### Added
- Added `date_equals` validation message ([#26584](https://github.com/laravel/framework/pull/26584))
- Added `starts_with` validation rule ([#26612](https://github.com/laravel/framework/pull/26612))
- Added relationship getters `BelongsToMany::getParentKeyName`, `BelongsToMany::getRelatedKeyName`, `HasManyThrough::getFirstKeyName`, `HasManyThrough::getForeignKeyName`, `HasManyThrough::getSecondLocalKeyName`, `HasOneOrMany::getLocalKeyName`, `MorphToMany::getInverse` ([#26607](https://github.com/laravel/framework/pull/26607))
- Make `ResourceCollection` countable ([#26595](https://github.com/laravel/framework/pull/26595))

### Fixed
- Fixed duplicate validation issue in `FormRequest::validated` method ([#26604](https://github.com/laravel/framework/pull/26604))
- <strong> Prevent breaking eager loading with string keys </strong> ([#26622](https://github.com/laravel/framework/pull/26622))


## [v5.7.14 (2018-11-21)](https://github.com/laravel/framework/compare/v5.7.13...v5.7.14)

### Added
- Added `Macroable` trait to `Illuminate\Cookie\CookieJar` ([#26445](https://github.com/laravel/framework/pull/26445))
- Added ability to disable password reset route ([#26459](https://github.com/laravel/framework/pull/26459))
- Added ability to publish error views ([#26460](https://github.com/laravel/framework/pull/26460))
- Added ability to set notifcation tries and timeout ([#26493](https://github.com/laravel/framework/pull/26493))
- Added `mail.log_channel` config for make `log` for mail driver configurable ([#26510](https://github.com/laravel/framework/pull/26510))
- Allowed `asset` root urls to be configurable via `app.asset_url` ([9172a67](https://github.com/laravel/framework/commit/9172a67b783952c1d3e15452d9c8646dc0b3eb6d))
- Added `Error while sending QUERY packet` string to `DetectsLostConnections` trait ([#26233](https://github.com/laravel/framework/pull/26560))
- Added env override for running in console ([a36906a](https://github.com/laravel/framework/commit/a36906ab8a141f1f497a0667196935e41970ae51), [19f2245](https://github.com/laravel/framework/commit/19f2245c6d7c87daf784f94b169f0dd4d98f0ca4))

### Fixed
- Fixed `UNION` aggregate queries with columns ([#26466](https://github.com/laravel/framework/pull/26466))
- Allowed migration table name to be guessed without `_table` suffix ([#26429](https://github.com/laravel/framework/pull/26429))
- Fixed `TestResponse::assertExactJson` for empty JSON objects ([#26353](https://github.com/laravel/framework/pull/26353), [e6ebc8d](https://github.com/laravel/framework/commit/e6ebc8d239e53e6daf16c869de3897ffbce6c751), [621d91d](https://github.com/laravel/framework/commit/621d91d802016ab4a64acc5c65f81cb9f5e5f779), [#26508](https://github.com/laravel/framework/pull/26508))
- Fixed cache repository for PHP from 7.2.12v ([#26495]( https://github.com/laravel/framework/pull/26495))
- Fixed user authorization check for Email Verification ([#26528](https://github.com/laravel/framework/pull/26528))
- Fixed nested JOINs on SQLite ([#26567](https://github.com/laravel/framework/pull/26567))

### Changed
- Improved eager loading performance ([#26434](https://github.com/laravel/framework/pull/26434), [#26453](https://github.com/laravel/framework/pull/26453), [3992140](https://github.com/laravel/framework/commit/3992140064307ef82d23328995e7c59045c231f2), [#26471](https://github.com/laravel/framework/pull/26471), [a3738cf](https://github.com/laravel/framework/commit/a3738cf4e133a4475c56b51f521a12db78e2ecbb), [#26531](https://github.com/laravel/framework/pull/26531))
- Adjusted `mix` missing asset exceptions ([#26431](https://github.com/laravel/framework/pull/26431))
- Used `asset` helper to generate full path urls in exception views ([#26411](https://github.com/laravel/framework/pull/26411))
- Changed `Illuminate\Foundation\Testing\Concerns\MocksApplicationServices::withoutJobs` method ([#26437](https://github.com/laravel/framework/pull/26437))
- Cached `distinct` validation rule data ([#26509](https://github.com/laravel/framework/pull/26509))
- Improved DNS Prefetching in view files ([#26552](https://github.com/laravel/framework/pull/26552))


## [v5.7.13 (2018-11-07)](https://github.com/laravel/framework/compare/v5.7.12...v5.7.13)

### Added
- Added ability to return an array of messages in a custom validation rule ([#26327](https://github.com/laravel/framework/pull/26327))
- Added `whenEmpty`/ `whenNotEmpty` / `unlessEmpty` / `unlessNotEmpty` methods to `Collection` ([#26345](https://github.com/laravel/framework/pull/26345))
- Added `Illuminate\Support\Collection::some` method ([#26376](https://github.com/laravel/framework/pull/26376), [8f7e647](https://github.com/laravel/framework/commit/8f7e647dcee5fe13d7fc33a1e0e1ce531ea9f49e))
- Added `Illuminate\Cache\Repository::missing` method ([#26351](https://github.com/laravel/framework/pull/26351)) 
- Added `Macroable` trait to `Illuminate\View\Factory` ([#26361](https://github.com/laravel/framework/pull/26361))
- Added support for UNION aggregate queries ([#26365](https://github.com/laravel/framework/pull/26365))

### Changed
- Updated `AbstractPaginator::appends` to handle null ([#26326](https://github.com/laravel/framework/pull/26326))
- Added "guzzlehttp/guzzle": "^6.3", to `composer.json` ([#26328](https://github.com/laravel/framework/pull/26328))
- Showed exception message on 403 error page when message is available ([#26356](https://github.com/laravel/framework/pull/26356))
- Don't run TransformsRequest twice on ?query= parameters ([#26366](https://github.com/laravel/framework/pull/26366))
- Added missing logging options to slack log driver ([#26360](https://github.com/laravel/framework/pull/26360))
- Use cascade when truncating table in PostgreSQL ([#26389](https://github.com/laravel/framework/pull/26389))
- Allowed pass absolute parameter in has valid signature request macro ([#26397](https://github.com/laravel/framework/pull/26397))

### Changed realization
- Used `Request::validate` macro in Auth traits ([#26314](https://github.com/laravel/framework/pull/26314))


## [v5.7.12 (2018-10-30)](https://github.com/laravel/framework/compare/v5.7.11...v5.7.12)

### Added
- Added `CacheManager::forgetDriver` method ([#26264](https://github.com/laravel/framework/pull/26264), [fd9ef49](https://github.com/laravel/framework/commit/fd9ef492faefff96deab5285e30bc1b675211bcb))
- Added `Illuminate\Foundation\Http\Kernel::getMiddlewareGroups` method ([#26268](https://github.com/laravel/framework/pull/26268))
- Added an sqlite config option (`foreign_key_constraints`) to enable / disable foreign key constraints ([#26298](https://github.com/laravel/framework/pull/26298), [674f8be](https://github.com/laravel/framework/commit/674f8befc57f1e9fe8d064b475903431de39f41c), [#26306](https://github.com/laravel/framework/pull/26306))

### Fixed
- Checked `$absolute` parameter in `UrlGenerator::signedRoute` ([#26265](https://github.com/laravel/framework/pull/26265))
- Fixed error in resource building after running `php artisan preset none` command ([41becda](https://github.com/laravel/framework/pull/26244/commits/41becda26a6bfcfaf9754beb9106b6ca0f328a61), [#26244](https://github.com/laravel/framework/pull/26244))
- Fixed `whereDoesntHave()` and `doesntHave()` with nested relationships ([#26228](https://github.com/laravel/framework/pull/26228))
- Fixed filesystem locking hangs in `PackageManifest::build()` ([#26254](https://github.com/laravel/framework/pull/26254))

### Changed
- Made expectation closure optional for `InteractsWithContainer::mock` and `InteractsWithContainer::spy` ([#26242](https://github.com/laravel/framework/pull/26242))
- Allowed multiple `createPayloadCallback` on queues ([#26250](https://github.com/laravel/framework/pull/26250), [6e3d568](https://github.com/laravel/framework/commit/6e3d568757a8e4124b49bf9ac94f1db7a66437a1))
- Changed wording on default 403 view ([#26258](https://github.com/laravel/framework/pull/26258))
- Bump `vue.js` to `^2.5.17` in `artisan preset vue` command ([89f56bf](https://github.com/laravel/framework/pull/26244/commits/89f56bf8f9abb310bf985045c13103cb73a40351), [#26244](https://github.com/laravel/framework/pull/26244))
- Allowed adding additional `$manyMethods` when extending the model class ([#26307](https://github.com/laravel/framework/pull/26307))


## [v5.7.11 (2018-10-24)](https://github.com/laravel/framework/compare/v5.7.10...v5.7.11)

### Added
- Added `decimal:<num>` cast to Model ([#26173](https://github.com/laravel/framework/pull/26173))
- Allowed updateExistingPivot to receive an arrayable item ([#26167](https://github.com/laravel/framework/pull/26167))
- Added `setIntendedUrl` method to `Routing/Redirector.php` ([#26227](https://github.com/laravel/framework/pull/26227))
- Added `ORA-03114` string to `DetectsLostConnections` trait ([#26233](https://github.com/laravel/framework/pull/26233))

### Fixed
- Fixed an issue where the worker process would not be killed by the listener when the timeout is exceeded ([#25981](https://github.com/laravel/framework/pull/25981))

### Changed
- Reverted filesystem changes which were done in [#26010](https://github.com/laravel/framework/pull/26010) ([#26231](https://github.com/laravel/framework/pull/26231))


## [v5.7.10 (2018-10-23)](https://github.com/laravel/framework/compare/v5.7.9...v5.7.10)

### Added
- Added loadCount method to eloquent collections ([#25997](https://github.com/laravel/framework/pull/25997))
- Added support for identity columns in PostgreSQL 10+ ([#26096](https://github.com/laravel/framework/pull/26096))
- Allowed passing a model instance directly to `assertSoftDeleted` method in `Foundation/Testing/Concerns/InteractsWithDatabase.php` ([#26133](https://github.com/laravel/framework/pull/26133) , [#26148](https://github.com/laravel/framework/pull/26148))
- Added possibility to define exclude methods on registered `apiResource` ([#26149](https://github.com/laravel/framework/pull/26149))
- Added `filp/whoops` to `suggest` in `composer.json` ([#26180](https://github.com/laravel/framework/pull/26180))
- Added `mock` and `spy` methods to `Foundation/Testing/Concerns/InteractsWithContainer.php` ([#26171](https://github.com/laravel/framework/pull/26171), [b50f9f3](https://github.com/laravel/framework/commit/b50f9f3bc8c1ee03c22ee8cc0ac37179fb28a1c9))
- Added `uuid` validation rule to validator ([#26135](https://github.com/laravel/framework/pull/26135))
- NotificationFake can assert preferred locale ([#26205](https://github.com/laravel/framework/pull/26205))

### Fixed
- Fixed `whereHas` and `$withCount` bindings from `polymorphic relationships` ([#26145](https://github.com/laravel/framework/pull/26145))
- Fixed `getTable` method in Model ([#26085](https://github.com/laravel/framework/pull/26085))
- Fixed filesystem locking hangs in `PackageManifest::build()` ([#26010](https://github.com/laravel/framework/pull/26010), [98b8256](https://github.com/laravel/framework/commit/98b8256f350d468cfc6b9fe2c2b0efb4103810a4))
- Fixed `Illuminate/Http/Testing/File.php` for Symfony 4.1 components ([#26080](https://github.com/laravel/framework/pull/26080))
- Fixed URL in `Notifications/resources/views/email.blade.php` ([22ca105](https://github.com/laravel/framework/commit/22ca105c0b1759c95f79e553c1977ffd2a013d05))
- Fixed `hasValidSignature` method when someone send a `null` signature in `UrlGenerator.php` ([#26132](https://github.com/laravel/framework/pull/26132))
- Fixed autocomplete for container in ServiceProvider for cases when someone developed packages ([#26063](https://github.com/laravel/framework/pull/26063))
- Fixed `ColumnDefinition::default` typehint ([#26041](https://github.com/laravel/framework/pull/26041))

### Changed
- Define mix as const in `react-stubs/webpack.mix.js` and `vue-stubs/webpack.mix.js` presets ([#26119](https://github.com/laravel/framework/pull/26119))
- Make `assertSessionHasNoErrors` in `TestResponse.php` print the unexpected errors  ([#26039](https://github.com/laravel/framework/pull/26039), [e6bdf8a](https://github.com/laravel/framework/commit/e6bdf8af7790db485856ecde0448b353d0cb15ca))
- Replaced the remaining occurrences of `newQuery()` to `newModelQuery()` in UPDATE/DELETE queries. ([#26158](https://github.com/laravel/framework/pull/26158))
- Improved `findOrFail()` exceptions in `BelongsToMany.php` and `HasManyThrough.php` relations ([#26182](https://github.com/laravel/framework/pull/26182))

### Changed realization
- Reversed ternary condition in `Arr::wrap` to make it clearer ([#26150](https://github.com/laravel/framework/pull/26150))
- Simplified `formatAction` in `UrlGenerator.php` ([#26121](https://github.com/laravel/framework/pull/26121))
- Simplified `isChainOfObjects` method in `Support/Testing/Fakes/QueueFake.php` ([#26151](https://github.com/laravel/framework/pull/26151))
- Deleted unneeded code ([#26053](https://github.com/laravel/framework/pull/26053), [#26162](https://github.com/laravel/framework/pull/26162), [#26160](https://github.com/laravel/framework/pull/26160), [#26159](https://github.com/laravel/framework/pull/26159), [#26152](https://github.com/laravel/framework/pull/26152))
- Prefer stricter comparison ([#26139](https://github.com/laravel/framework/pull/26139), [#26157](https://github.com/laravel/framework/pull/26157))
- Removed duplicated code from `Router::updateGroupStack` method ([#26206](https://github.com/laravel/framework/pull/26206), [6debff6](https://github.com/laravel/framework/commit/6debff6affba9224c778e32cc3c00e00a66cb9dd))


## [v5.7.9 (2018-10-09)](https://github.com/laravel/framework/compare/v5.7.8...v5.7.9)

### Added
- Support custom user provider names in generator commands ([#25681](https://github.com/laravel/framework/pull/25681))
- Added 401 Exception view ([#26002](https://github.com/laravel/framework/pull/26002))
- Added `Categorical imperative` quote to `Inspiring.php` ([#25968](https://github.com/laravel/framework/pull/25968))
- Mailable `render` method respects `Mailable@locale` property ([#25990](https://github.com/laravel/framework/pull/25990))
- Added some meta data to the notification mails ([477273c](https://github.com/laravel/framework/commit/477273c72be8b253b6421c69f3e37b5bf4c3a185))
- Added `Macroable` trait to `PendingResourceRegistration` ([#25947](https://github.com/laravel/framework/pull/25947))
- Added `assertSessionDoesntHaveErrors` method to `TestResponse.php` ([#25949](https://github.com/laravel/framework/pull/25949), [3005706](https://github.com/laravel/framework/commit/3005706abb411d1468adbff6627ff26351afe446))
- Enable passing options to custom presets ([#25930](https://github.com/laravel/framework/pull/25930))

### Fixed
- Fix missing `illuminate/support` dependency in `illuminate/container` ([#25955](https://github.com/laravel/framework/pull/25955))
- Extend grammar ([#25944](https://github.com/laravel/framework/pull/25944))

### Changed
- Improved PSR-11 implementation ([#25870](https://github.com/laravel/framework/pull/25870))
- Changed the sentence of error 403 view from unauthorised to forbidden ([#26002](https://github.com/laravel/framework/pull/26002))
- Revert email lang template changes ([#25963](https://github.com/laravel/framework/pull/25963))
- Added model checking in `assertViewHas` ([#26012](https://github.com/laravel/framework/pull/26012))

### Changed realization
- Inline `Arr::pluck()` in `data_get()` ([#25938](https://github.com/laravel/framework/pull/25938))

## [v5.7.8 (2018-10-04)](https://github.com/laravel/framework/compare/v5.7.7...v5.7.8)

### Added
- Add `--step` to `migrate:fresh` command ([#25897](https://github.com/laravel/framework/pull/25897))
- Allow `destroy` method in `Model` to accept a collection of ids ([#25878](https://github.com/laravel/framework/pull/25878))
- Add AsPivot trait ([#25851](https://github.com/laravel/framework/pull/25851))

### Fixed
- Fixed wrap table for sql server ([#25896](https://github.com/laravel/framework/pull/25896))

### Changed
- Use "optimize:clear" in "app:name" command ([#25922](https://github.com/laravel/framework/pull/25922))
- Revert of "html string support in translator" ([e626ab3](https://github.com/laravel/framework/commit/e626ab32a4afec90f80641fbcd00e6b79d15cd3a))

### Changed (only realization)
- Simplify code for contextual binding ([e2476c1](https://github.com/laravel/framework/commit/e2476c1cdfeffd1c4432ec8dc1f733815f70c000))


## [v5.7.7 (2018-10-02)](https://github.com/laravel/framework/compare/v5.7.6...v5.7.7)

### Added
- Allow array callables to be passed to Gate::before() ([#25817](https://github.com/laravel/framework/pull/25817))
- Mail recipient and notifiable can set preferred locale ([#25752](https://github.com/laravel/framework/pull/25752))
- Always show seeder info ([#25872](https://github.com/laravel/framework/pull/25872))
- Support JSON UPDATE queries on PostgreSQL ([#25797](https://github.com/laravel/framework/pull/25797))
- Makes sure changing a database field to JSON does not include a collation ([#25741](https://github.com/laravel/framework/pull/25741))
- Added Queued Closures ([#25777](https://github.com/laravel/framework/pull/25777))
- Add the ability to skip algorithm checking ([#25468](https://github.com/laravel/framework/pull/25468), [5fd4b89](https://github.com/laravel/framework/commit/5fd4b899cc42d266fab34ee2d5f92fb47ca34fd0))
- Add queue create payload hook ([3f68cbe](https://github.com/laravel/framework/commit/3f68cbe3df82990c69e34309901fcefefdb65c95))
- Authorize Middleware Accept String Parameters ([#25763](https://github.com/laravel/framework/pull/25763))

### Fixed
- Fix `each` method on BelongsToMany relationships ([#25832](https://github.com/laravel/framework/pull/25832))
- Fix prefixed table indexes ([#25867](https://github.com/laravel/framework/pull/25867))
- Fix `be` method in `InteractsWithAuthentication` trait ([#25873](https://github.com/laravel/framework/pull/25873))
- Fixes the error when $resource is null ([#25838](https://github.com/laravel/framework/pull/25838))
- Attach all disk attachments and not only first one in the `Mail/Mailable.php` ([#25793](https://github.com/laravel/framework/pull/25793))
- Fixed: in case if one job throw exception, than we will proceed to next one ([#25820](https://github.com/laravel/framework/pull/25820))

### Changed
- Trim model class name when passing in `Authorize.php` middleware ([#25849](https://github.com/laravel/framework/pull/25849))
- Improve JSON UPDATE queries on MySQL ([#25794](https://github.com/laravel/framework/pull/25794))
- Don't print the generated application key ([#25802](https://github.com/laravel/framework/pull/25802))
- Improve "exists" validation with array values ([#25819](https://github.com/laravel/framework/pull/25819))
- Only escape trans parameters ([98046cb](https://github.com/laravel/framework/commit/98046cb0c81b418fb4046ade034f3d33a4172239))
- Added type check for assertExitCode(0) ([#25847](https://github.com/laravel/framework/pull/25847))

### Changed (only realization)
- Simplify `save` method `MorphOneOrMany` relation ([#25864](https://github.com/laravel/framework/pull/25864))


## [v5.7.6 (2018-09-25)](https://github.com/laravel/framework/compare/v5.7.5...v5.7.6)

### Added
- Support MorphTo eager loading with selected columns ([#25662](https://github.com/laravel/framework/pull/25662))
- Added possibility to define a complex condition (overwrite `shouldAddXsrfTokenCookie` method) for add cookie to response in `Middleware/VerifyCsrfToken.php`

### Fixed
- Fixed tag cache clearing when using Redis ([#25744](https://github.com/laravel/framework/pull/25744))
- Fixed broken email subcopy template escaping ([#25723](https://github.com/laravel/framework/pull/25723))
- Fixed MethodNotAllowedHTTPException on Intended Redirect ([#25739](https://github.com/laravel/framework/pull/25739))

### Changed
- Use url() function instead of plain url in `views/illustrated-layout.blade.php` ([25725](https://github.com/laravel/framework/pull/25725))


## [v5.7.5 (2018-09-20)](https://github.com/laravel/framework/compare/v5.7.4...v5.7.5)

### Added
- Add callback hook for building mailable data in `\Illuminate\Mail\Mailable` ([7dc3d8d](https://github.com/laravel/framework/commit/7dc3d8d35ad8bcd3b18334a44320e3162b9f6dc1))

### Fixed
- Make any column searchable with `like` in PostgreSQL ([#25698](https://github.com/laravel/framework/pull/25698))
- Remove trailing newline from hot url in `mix` helper ([#25699](https://github.com/laravel/framework/pull/25699))

### Changed 
- Revert of "Remove `Hash::check()` for password verification" ([2e78bf4](https://github.com/laravel/framework/commit/2e78bf472832cd68ef7d80c73dbb722a62ee1429)) 
 
 
## [v5.7.4 (2018-09-18)](https://github.com/laravel/framework/compare/v5.7.3...v5.7.4)

### Added
- Add 'verified' session boolean in `VerifiesEmails::verify` action ([#25638](https://github.com/laravel/framework/pull/25638))
- Add Nelson Mandela to Inspirational Quotes ([#25599](https://github.com/laravel/framework/pull/25599))
- Add `streamedContent` to `TestResponse` class ([#25469](https://github.com/laravel/framework/pull/25469), [b3f583c](https://github.com/laravel/framework/commit/b3f583cd5efbc9e1b9482b00a7c22b00324e936e))

### Fixed
- Fix app stub when register route option is set to false ([#25582](https://github.com/laravel/framework/pull/25582))
- Fix artisan PendingCommand run method return value ([#25577](https://github.com/laravel/framework/pull/25577))
- Support custom accessor on `whenPivotLoaded()` ([#25661](https://github.com/laravel/framework/pull/25661))

### Changed
- Remove `Hash::check()` for password verification ([#25677](https://github.com/laravel/framework/pull/25677))


## [v5.7.3 (2018-09-11)](https://github.com/laravel/framework/compare/v5.7.2...v5.7.3)

### Changed
- `__toString` method in `Illuminate/Auth/Access/Response.php` ([#25539](https://github.com/laravel/framework/pull/25539))
- Do not pass the guard instance to the authentication events ([#25568](https://github.com/laravel/framework/pull/25568))
- Call Pending artisan command immediately ([#25574](https://github.com/laravel/framework/pull/25574), [d54ffa5](https://github.com/laravel/framework/commit/d54ffa594b968b6c9a7cf716f5c73758a7d36824))
- Use `request()` method when we called Guzzle ClientInterface ([#25490](https://github.com/laravel/framework/pull/25490))
- Replace all placeholders for comparison rules (`gt`/`gte`/`lt`/`lte`) properly ([#25513](https://github.com/laravel/framework/pull/25513))

### Added
- Add `storeOutput` method to `Illuminate/Console/Scheduling/Event.php` ([70a72fc](https://github.com/laravel/framework/commit/70a72fcac9d8852fc1a4ce11eb47842774c11876))
- Add `ensureOutputIsBeingCaptured` method to `Illuminate/Console/Scheduling/Event.php`
- Add options for SES Mailer ([#25536](https://github.com/laravel/framework/pull/25536))
- Add Ability to disable register route ([#25556](https://github.com/laravel/framework/pull/25556))

### Fixed
- Fix database cache on PostgreSQL ([#25530](https://github.com/laravel/framework/pull/25530))
- Fix bug with invokables in `Illuminate/Console/Scheduling/CallbackEvent.php` ([eaac77b](https://github.com/laravel/framework/commit/eaac77bfb878b49f2ceff4fb09198e437d38683d)) 
- Stop sending email verification if user already verified ([#25540](https://github.com/laravel/framework/pull/25540))
- Fix `withoutMockingConsoleOutput` in `Illuminate/Foundation/Testing/Concerns/InteractsWithConsole.php` ([#25499](https://github.com/laravel/framework/pull/25499))
- Fix DurationLimiter not using Redis connection proxy to call eval command ([#25505](https://github.com/laravel/framework/pull/25505))

### Deprecated
- Make `ensureOutputIsBeingCapturedForEmail` method deprecated in `Illuminate/Console/Scheduling/Event.php`
 
 
## [v5.7.2 (2018-09-06)](https://github.com/laravel/framework/compare/v5.7.1...v5.7.2)

### Added
- Added `moontoast/math` suggestion to `Support` module ([79edf5c](https://github.com/laravel/framework/commit/79edf5c70c9a54c75e17da62ba3649f24b874e09))
- Send an event when the user's email is verified ([045cbfd](https://github.com/laravel/framework/commit/045cbfd95c611928aef1b877d1a3dc60d5f19580))
- Allow email verification middleware to work with API routes ([0e23b6a](https://github.com/laravel/framework/commit/0e23b6afa4d1d8b440ce7696a23fa770b4f7e5e3))
- Add Builder::whereJsonLength() ([5e33a96](https://github.com/laravel/framework/commit/5e33a96cd5fe9f5bea953a3e07ec827d5f19a9a3), [f149fbd](https://github.com/laravel/framework/commit/f149fbd0fede21fc3a8c0347d1ab9ee858727bb4))
- Pass configuration key parameter to updatePackageArray in Preset ([#25457](https://github.com/laravel/framework/pull/25457))
- Let the WorkCommand specify whether to stop when queue is empty ([2524c5e](https://github.com/laravel/framework/commit/2524c5ee89a0c5e6e4e65c13d5f9945075bb299c))

### Changed
- Make email verification scaffolding translatable ([#25473](https://github.com/laravel/framework/pull/25473))
- Do not mock console output by default ([b433970](https://github.com/laravel/framework/commit/b4339702dbdc5f1f55f30f1e6576450f6277e3ae))
- Allow daemon to stop when there is no more jobs in the queue ([157a150](https://github.com/laravel/framework/commit/157a15080b95b26b2ccb0677dceab4964e25f18d))
  
### Fixed
- Do not send email verification if user is already verified ([#25450](https://github.com/laravel/framework/pull/25450))
- Fixed required carbon version ([394f79f](https://github.com/laravel/framework/commit/394f79f9a6651b103f6e065cb4470b4b347239ea))


## [v5.7.1 (2018-09-04)](https://github.com/laravel/framework/compare/v5.7.0...v5.7.1)

### Fixed
- Fixed an issue with basic auth when no field is defined

### Changed
- Remove X-UA-Compatible meta tag ([#25442](https://github.com/laravel/framework/pull/25442))
- Added default array value for redis config ([#25443](https://github.com/laravel/framework/pull/25443))

## [v5.7.0 (2018-09-04)](https://github.com/laravel/framework/compare/5.6...v5.7.0)

Check the upgrade guide in the [Official Laravel Documentation](https://laravel.com/docs/5.7/upgrade).
