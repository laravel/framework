# Release Notes for 6.x

## [Unreleased](https://github.com/laravel/framework/compare/v6.13.1...6.x)

### Added
- Added `Illuminate\Bus\Dispatcher::dispatchAfterResponse()` method ([#31300](https://github.com/laravel/framework/pull/31300), [8a3cdb0](https://github.com/laravel/framework/commit/8a3cdb0622047b1d94b4a754bfe98fb7dc1c174a))

### Fixed
- Used current DB to create Doctrine Connections ([#31278](https://github.com/laravel/framework/pull/31278))

### TODO
- Use SKIP LOCKED for mysql 8.1 and pgsql 9.5 queue workers ([#31287](https://github.com/laravel/framework/pull/31287))
- Dont merge middleware from method and property ([#31301](https://github.com/laravel/framework/pull/31301))
- Fix plucking column name containing a space ([#31299](https://github.com/laravel/framework/pull/31299))
- Split specifyParameter() to external trait ([#31254](https://github.com/laravel/framework/pull/31254))
- Fix bug with wildcard caching in event dispatcher ([#31313](https://github.com/laravel/framework/pull/31313))


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
-  Dont throw exception when session is not set in `AuthenticateSession` middleware ([4de1d24](https://github.com/laravel/framework/commit/4de1d24cf390f07d4f503973e5556f73060fbb31))


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
