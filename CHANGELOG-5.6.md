# Release Notes for 5.6.x

## [Unreleased]

### Added
- Added support for custom SparkPost endpoint ([#23910](https://github.com/laravel/framework/pull/23910))


## v5.6.18 (2018-04-26)

### Added
- Added support for MySQL 8 ([#23948](https://github.com/laravel/framework/pull/23948))
- Added support for custom filesystem drivers URLs ([#23964](https://github.com/laravel/framework/pull/23964))
- Added more PostgreSQL operators ([#23945](https://github.com/laravel/framework/pull/23945))
- Added support for JSONP callback when broadcasting using Pusher ([#24018](https://github.com/laravel/framework/pull/24018), [b9ab427](https://github.com/laravel/framework/commit/b9ab4272192d079539c32787d66a35a31a7815ce))

### Changed
- Support chaining using `$this->be()` helper ([#23919](https://github.com/laravel/framework/pull/23919))
- Improved pagination accessibility ([#23962](https://github.com/laravel/framework/pull/23962))
- Changed response code of `ValidationException` in `ThrottlesLogins` to `429` ([#24002](https://github.com/laravel/framework/pull/24002))
- Throw exception if called command doesn't exist ([#23942](https://github.com/laravel/framework/pull/23942))
- Made notification email translatable ([#23903](https://github.com/laravel/framework/pull/23903))

### Fixed
- Fixed saving timestamp columns on pivots without parent ([#23917](https://github.com/laravel/framework/pull/23917))
- Quote collation names in MySQL migrations ([#23989](https://github.com/laravel/framework/pull/23989))
- Fixed sending plain-text only emails ([#23981](https://github.com/laravel/framework/pull/23981))
- Fixed counting the number of jobs on `Queue::fake()` ([#23933](https://github.com/laravel/framework/pull/23933))


## v5.6.17 (2018-04-17)

### Added
- Added helpers for subquery joins ([#23818](https://github.com/laravel/framework/pull/23818))

### Changed
- Allow `PendingResourceRegistration` to be fluently registered ([#23890](https://github.com/laravel/framework/pull/23890))
- Allow asserting an integer with `assertSee*()` ([#23892](https://github.com/laravel/framework/pull/23892))
- Allow passing `Collection` to `Rule::in()` and `Rule::notIn()` ([#23875](https://github.com/laravel/framework/pull/23875))

### Fixed
- Lock Carbon version at `1.25.*` ([27b8844](https://github.com/laravel/framework/commit/27b88449805c1e9903fe4088f303c0858336b23b))

### Removed
- Removed form error for password confirmation ([#23887](https://github.com/laravel/framework/pull/23887))


## v5.6.16 (2018-04-09)

### Added
- Support executing artisan commands using class names ([#23764](https://github.com/laravel/framework/pull/23764))
- Make `View` macroable ([#23787](https://github.com/laravel/framework/pull/23787))
- Added database `Connection::unsetEventDispatcher()` method ([#23832](https://github.com/laravel/framework/pull/23832))
- Support IAM role session token to be used with SES ([#23766](https://github.com/laravel/framework/pull/23766))

### Changed
- Added displayable value to `required_unless` rule ([#23833](https://github.com/laravel/framework/pull/23833))

### Fixed
- Fixed `RedisQueue::blockingPop()` check when using PhpRedis ([#23757](https://github.com/laravel/framework/pull/23757))


## v5.6.15 (2018-03-30)

### Fixed
- Fixed variable reference in `RedisTaggedCache::decrement()` ([#23736](https://github.com/laravel/framework/pull/23736))
- Check `updated_at` column existence in `HasOneOrMany::update()` ([#23747](https://github.com/laravel/framework/pull/23747))

### Security
- Check `iv` length in `Encrypter::validPayload()` ([886d261](https://github.com/laravel/framework/commit/886d261df0854426b4662b7ed5db6a1c575a4279))


## v5.6.14 (2018-03-28)

### Added
- Added `SlackMessage::info()` method ([#23711](https://github.com/laravel/framework/pull/23711))
- Added `SessionGuard::logoutOtherDevices()` method ([9c51e49](https://github.com/laravel/framework/commit/9c51e49a56ff15fc47ac1a6bf232c32c25d14fd0))

### Changed
- Replaced Blade's `or` operator with null-coalescing operator ([13f732e](https://github.com/laravel/framework/commit/13f732ed617e41608e4ae021efc9d13e43375a26))

### Fixed
- Get Blade compiler from engine resolver ([#23710](https://github.com/laravel/framework/pull/23710))
- Default to an empty string when validating the URL signatures ([#23721](https://github.com/laravel/framework/pull/23721))


## v5.6.13 (2018-03-26)

### Added
- Added `view:cache` command ([9fd1273](https://github.com/laravel/framework/commit/9fd1273ad79a46bb3aa006129109c6bc72766e4b), [2ab8acf](https://github.com/laravel/framework/commit/2ab8acfef5d7e784148b2367b5bcf083a0d0d024))
- Added `min()` and `max()` to as higher order proxies ([#23560](https://github.com/laravel/framework/pull/23560))
- Added `@elseauth` and `@elseguest` Blade directives ([#23569](https://github.com/laravel/framework/pull/23569))
- Added support for hashing configuration ([#23573](https://github.com/laravel/framework/pull/23573), [d6e3ca9](https://github.com/laravel/framework/commit/d6e3ca97ff4175ff6a9b270b65b04c0d836a7bec))
- Allow tagged cache keys to be incremented/decremented ([#23578](https://github.com/laravel/framework/pull/23578))
- Added `SeeInOrder` constraint to avoid risky test notices ([#23594](https://github.com/laravel/framework/pull/23594), [ca39449](https://github.com/laravel/framework/commit/ca39449c83b0f8d42e1ad1b4086239584fda0967))
- Support higher order `groupBy()` ([#23608](https://github.com/laravel/framework/pull/23608))
- Support disabling setting `created_at` in models ([#23667](https://github.com/laravel/framework/pull/23667))
- Added callback support to `optional()` helper ([#23688](https://github.com/laravel/framework/pull/23688))
- Added `Eloquent\Collection::loadMorph()` method ([#23626](https://github.com/laravel/framework/pull/23626))

### Changed
- Support generating a signed route with a `UrlRoutable` parameter ([#23584](https://github.com/laravel/framework/pull/23584))
- Use `DIRECTORY_SEPARATOR` in `Application::environmentFilePath()` ([#23596](https://github.com/laravel/framework/pull/23596))
- Support states on model factory after callbacks ([#23551](https://github.com/laravel/framework/pull/23551), [#23676](https://github.com/laravel/framework/pull/23676))
- Use `hash_equals()` for verifying URL signatures ([#23618](https://github.com/laravel/framework/pull/23618))
- Refactored `Exceptions/Handler` ([f9162c9](https://github.com/laravel/framework/commit/f9162c9898c58be18f166e1832699b83602404b1), [6c5d971](https://github.com/laravel/framework/commit/6c5d9717224f970d542333813901220a3e950fad))
- Changed status code of `InvalidSignatureException` from `401` to `403` ([#23662](https://github.com/laravel/framework/pull/23662), [c99911f](https://github.com/laravel/framework/commit/c99911f45432440beee2a9b6d7b5a19ef8d50997))

### Fixed
- Revered breaking changes in `ManagesLoops` ([d0a2613](https://github.com/laravel/framework/commit/d0a2613f5af223b67db79d59c21aba33b5cc9cdf))
- Set exit status in serve command ([#23689](https://github.com/laravel/framework/pull/23689))


## v5.6.12 (2018-03-14)

### Added
- Added `fromSub()` and `fromRaw()` methods to query builder ([#23476](https://github.com/laravel/framework/pull/23476))
- Added "Not Regex" validation rule ([#23475](https://github.com/laravel/framework/pull/23475))
- Added seed parameter to `Arr::shuffle()` ([#23490](https://github.com/laravel/framework/pull/23490))
- Added after callback to model factories ([#23495](https://github.com/laravel/framework/pull/23495), [d79509d](https://github.com/laravel/framework/commit/d79509dfb82a8518ca0a0ccb9d4986cfa632b1ab))
- Added `Request::anyFilled()` method ([#23499](https://github.com/laravel/framework/pull/23499), [896d817](https://github.com/laravel/framework/commit/896d817a13bcf9bc879e53e4f8b7b5b15c27ee86))
- Added support for signed routes ([#23519](https://github.com/laravel/framework/pull/23519))
- Added `assertNotFound()` and `assertForbidden()` methods to `TestResponse` ([#23526](https://github.com/laravel/framework/pull/23526))
- Added test helpers to assert that a job has been queued with a chain ([#23531](https://github.com/laravel/framework/pull/23531), [696f4d8](https://github.com/laravel/framework/commit/696f4d88c132ac39a3a805dbe490b3b754c9ce5f))

### Changed
- Only set id on `NotificationFake` if there is no id set ([#23470](https://github.com/laravel/framework/pull/23470))
- Check whether `fetch()` method exists in `Application::output()` ([#23471](https://github.com/laravel/framework/pull/23471))
- Improve asset loading in `app.stub` ([#23479](https://github.com/laravel/framework/pull/23479))
- Support ignoring a model during a unique validation check ([#23524](https://github.com/laravel/framework/pull/23524))
- Support multiple model observers ([#23507](https://github.com/laravel/framework/pull/23507))
- `LogManager` driver capable of producing logger with any Monolog handler ([#23527](https://github.com/laravel/framework/pull/23527), [d499617](https://github.com/laravel/framework/commit/d4996170ec0ea2d5189db213c51ebcf4f526ab6d))
- Support passing model instance to `updateExistingPivot()` ([#23535](https://github.com/laravel/framework/pull/23535))
- Allow for custom `TokenGuard` fields ([#23542](https://github.com/laravel/framework/pull/23542))

### Fixed
- Fixed clearing the cache without a cache directory ([#23538](https://github.com/laravel/framework/pull/23538))


## v5.6.11 (2018-03-09)

### Fixed
- Fix for Carbon 1.24.1 ([#23464](https://github.com/laravel/framework/pull/23464))


## v5.6.10 (2018-03-09)

### Added
- Added `Blueprint::dropMorphs()` ([#23431](https://github.com/laravel/framework/pull/23431))
- Added `Mailable::attachFromStorage()` methods ([0fa361d](https://github.com/laravel/framework/commit/0fa361d0e2e111a1a684606a675b414ebd471257))
- Added `orWhere*()` builder methods for day, month and year ([#23449](https://github.com/laravel/framework/pull/23449))

### Changed
- Added `v-pre` to dropdown link in `app.stub` ([98fdbb0](https://github.com/laravel/framework/commit/98fdbb098cf52a74441fe949be121c18e3dbbe6a))
- Handle more JSON errors gracefully when `JSON_PARTIAL_OUTPUT_ON_ERROR` is set ([#23410](https://github.com/laravel/framework/pull/23410), [972b82a](https://github.com/laravel/framework/commit/972b82a67c6dd09fa01bf5e0b349a547ece33666))
- Add bubble, permission and locking config to single/daily log ([#23439](https://github.com/laravel/framework/pull/23439))
- Use `Str::contains()` instead of `str_contains()` ([ae4cb28](https://github.com/laravel/framework/commit/ae4cb28d040dca8db9a678978efd9ab63c6ea9fd))

### Fixed
- Fixed `unique()` call in `Validator::validate()` ([#23432](https://github.com/laravel/framework/pull/23432))
- Fix for Carbon 1.24.0 ([67d8a4b](https://github.com/laravel/framework/commit/67d8a4b15ffdeeacc2c27efad05735a59dba1c44))


## v5.6.9 (2018-03-07)

### Changed
- Regenerate token when regenerating the session ([20e8419](https://github.com/laravel/framework/commit/20e84191d5ef21eb5c015908c11eabf8e81d6212))

### Fixed
- Fixed an issue with resources when loading a single merge value with an associative array ([#23414](https://github.com/laravel/framework/pull/23414))


## v5.6.8 (2018-03-06)

### Added
- Added support for MySQL’s sounds-like operator ([#23351](https://github.com/laravel/framework/pull/23351))
- Added `ThrottleRequestsException` exception ([#23358](https://github.com/laravel/framework/pull/23358)
- Added `@dump` Blade directive ([#23364](https://github.com/laravel/framework/pull/23364))
- Added `Collection::whereInstanceOfMethod()` ([78b5b92](https://github.com/laravel/framework/commit/78b5b9298d48a5199ad494a4a7cc411dacd84256))
- Added `Dispatchable::dispatchNow()` ([#23399](https://github.com/laravel/framework/pull/23399))

### Changed
- Allow extension of `DatabaseNotification` model attributes ([#23337](https://github.com/laravel/framework/pull/23337))
- Made auth scaffolding translatable ([#23342](https://github.com/laravel/framework/pull/23342))
- Use `getKeyName()` in `getForeignKey()` ([#23362](https://github.com/laravel/framework/pull/23362))
- Sort `FileSystem` files and directories by name ([#23387](https://github.com/laravel/framework/pull/23387))
- Return validated data from `Validator::validate()` ([#23397](https://github.com/laravel/framework/pull/23397), [3657d66](https://github.com/laravel/framework/commit/3657d66b0be6623bbbd69ed2f2667ac76c36dea3))

### Fixed
- Fixed `serve` command escaping ([#23348](https://github.com/laravel/framework/pull/23348))
- Fixed an issue with multiple select statements in combination with `withCount()` ([#23357](https://github.com/laravel/framework/pull/23357))
- Fixed conditional loading issues  ([#23369](https://github.com/laravel/framework/pull/23369))
- Prevent considering arrays as `callable` while building model factories ([#23372](https://github.com/laravel/framework/pull/23372))
- Move `tightenco/collect` to Composer’s `conflict` ([#23379](https://github.com/laravel/framework/pull/23379))
- Set up loop variable correctly on all `Traversable` objects ([#23388](https://github.com/laravel/framework/pull/23388), [49770ec](https://github.com/laravel/framework/commit/49770eca4e2e780d4e8cdc762e2adbcab8b924fa))
- Removed attribute filling from pivot model ([#23401](https://github.com/laravel/framework/pull/23401))


## v5.6.7 (2018-02-28)

### Added
- Added SFTP filesystem driver ([#23308](https://github.com/laravel/framework/pull/23308))

### Changed
- Pass parent model to `withDefault()` callback ([#23334](https://github.com/laravel/framework/pull/23334))
- Upgrade Parsedown to 1.7.0 ([816f893](https://github.com/laravel/framework/commit/816f893c30152e95b14c4ae9d345f53168e5a20e))

### Fixed
- Fixed `PostgresGrammar::whereTime()` casting ([#23323](https://github.com/laravel/framework/pull/23323))
- Fixed `SQLiteGrammar::whereTime()` correct ([#23321](https://github.com/laravel/framework/pull/23321))


## v5.6.6 (2018-02-27)

### Added
- Added `sortKeys()` and `sortKeysDesc()` methods to `Collection` ([#23286](https://github.com/laravel/framework/pull/23286))

### Changed
- Return `null` from `optional()` helper if object property is undefined ([#23267](https://github.com/laravel/framework/pull/23267))
- Cache event wildcard listeners ([#23299](https://github.com/laravel/framework/pull/23299), [82099cb](https://github.com/laravel/framework/commit/82099cb3fdfe79f3f4f17008daf169f13fefffc0))
- Changed `morphs()` and `nullableMorphs()` to use `unsignedBigInteger()` ([#23320](https://github.com/laravel/framework/pull/23320))

### Fixed
- Prevent delayed jobs in v5.5 fail to run in v5.6 ([#23287](https://github.com/laravel/framework/pull/23287))
- `Queue::bulk()` fake now properly pushes expected jobs ([#23294](https://github.com/laravel/framework/pull/23294))
- Fixed the list of packages removed when the "none" preset is installed ([#23305](https://github.com/laravel/framework/pull/23305))
- Fixed an issue with `orHaving()` arguments ([e7f13be](https://github.com/laravel/framework/commit/e7f13be6a5dd8c348243a5f5dce488359160937c))


## v5.6.5 (2018-02-22)

### Added
- Added model reference to `MassAssignmentException` ([#23229](https://github.com/laravel/framework/pull/23229))
- Added support for setting the locale on `Mailable` ([#23178](https://github.com/laravel/framework/pull/23178), [a432d9e](https://github.com/laravel/framework/commit/a432d9e1fabe14cebecdf9d9637a3d4b8167b478))
- Added new udiff methods to the `Collection` ([#23107](https://github.com/laravel/framework/pull/23107))

### Fixed
- Fixed an issue with `orWhere*()` arguments ([e5042e1](https://github.com/laravel/framework/commit/e5042e10f940579b4457c99a51319887cd0a7b6f), [33739f9](https://github.com/laravel/framework/commit/33739f9887413f9855fb93a04211009256d5d904))


## v5.6.4 (2018-02-21)

### Added
- Added the ability to set message ID right hand side ([#23181](https://github.com/laravel/framework/pull/23181))
- Support callbacks as custom log drivers ([#23184](https://github.com/laravel/framework/pull/23184))
- Added `Blade::include()` method for include aliases ([#23172](https://github.com/laravel/framework/pull/23172))
- Added `broadcastType()` method to notifications ([#23236](https://github.com/laravel/framework/pull/23236), [4227bd7](https://github.com/laravel/framework/commit/4227bd78d5ab2743e694bfd34784a5ccced20bef))

### Changed
- Moved clone logic from `FormRequestServiceProvider` to `Request` ([b0c2459](https://github.com/laravel/framework/commit/b0c2459d7e55519d1c61927ab526e489a3a52eaf))
- Changed pagination arrow symbols ([#23127](https://github.com/laravel/framework/pull/23127))
- Update React version in preset ([#23134](https://github.com/laravel/framework/pull/23134))
- Added an empty error bag when rendering HTTP exception views ([#23139](https://github.com/laravel/framework/pull/23139))
- Normalized actions when using `route:list` command ([#23148](https://github.com/laravel/framework/pull/23148))
- Updated required Carbon version ([201bbec](https://github.com/laravel/framework/commit/201bbec1e2eec0ecc1dfeece05fbc4196058028a))
- Improved `BadMethodCallException` messages ([#23232](https://github.com/laravel/framework/pull/23232))
- Support date validation rules when comparison has relative time ([#23211](https://github.com/laravel/framework/pull/23211))

### Fixed
- Returns same `Logger` instance from `LogManager` ([#23118](https://github.com/laravel/framework/pull/23118))
- Register missing `hash.driver` DI ([#23114](https://github.com/laravel/framework/pull/23114))
- Fixed an issue with starting two database transactions in tests ([#23132](https://github.com/laravel/framework/pull/23132))
- Don't replace `tightenco/collect` ([#23147](https://github.com/laravel/framework/pull/23147), [#23153](https://github.com/laravel/framework/pull/23153), [#23160](https://github.com/laravel/framework/pull/23160))
- Catch `InvalidFileException` when loading invalid environment file ([#23149](https://github.com/laravel/framework/pull/23149), [5695079](https://github.com/laravel/framework/commit/569507941594075c36893445dd22374efbe48305))
- Fixed an issue with `assertRedirect()` ([#23176](https://github.com/laravel/framework/pull/23176))
- Fixed dropdown accessibility ([#23191](https://github.com/laravel/framework/pull/23191))
- Fixed `--force` flag on `GeneratorCommand` ([#23230](https://github.com/laravel/framework/pull/23230))

### Removed
- Removed Bootstrap 3 leftovers ([#23129](https://github.com/laravel/framework/pull/23129), [#23173](https://github.com/laravel/framework/pull/23173))


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
