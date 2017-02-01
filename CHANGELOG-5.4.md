# Release Notes for 5.4.x

## [Unreleased]

### Changed
- Use `route()` helper instead of `url()` in authentication component ([#17718](https://github.com/laravel/framework/pull/17718))


## v5.4.8 (2017-02-01)

### Added
- Added `TestResponse::assertJsonStructure()` ([#17700](https://github.com/laravel/framework/pull/17700))
- Added `Macroable` trait to Eloquent `Relation` class ([#17707](https://github.com/laravel/framework/pull/17707))

### Changed
- Move `shouldKill()` check from `daemon()` to `stopIfNecessary()` ([8403b34](https://github.com/laravel/framework/commit/8403b34a6de212e5cd98d40333fd84d112fbb9f7))
- Removed `isset()` check from `validateSame()` ([#17708](https://github.com/laravel/framework/pull/17708))

### Fixed
- Added `force` option to `queue:listen` signature ([#17716](https://github.com/laravel/framework/pull/17716))
- Fixed missing `return` in `HasManyThrough::find()` and `HasManyThrough::findMany()` ([#17717](https://github.com/laravel/framework/pull/17717))


## v5.4.7 (2017-01-31)

### Added
- Added `Illuminate\Support\Facades\Schema` to `notifications.stub` ([#17664](https://github.com/laravel/framework/pull/17664))
- Added support for numeric arguments to `@break` and `@continue` ([#17603](https://github.com/laravel/framework/pull/17603))

### Changed
- Use `usesTimestamps()` in Eloquent traits ([#17612](https://github.com/laravel/framework/pull/17612))
- Default to `null` if amount isn't set in `factory()` helper ([#17614](https://github.com/laravel/framework/pull/17614))
- Normalize PhpRedis GET/MGET results ([#17196](https://github.com/laravel/framework/pull/17196))
- Changed visibility of `Validator::addRules()` from `protected` to `public` ([#17654](https://github.com/laravel/framework/pull/17654))
- Gracefully handle `SIGTERM` signal in queue worker ([b38ba01](https://github.com/laravel/framework/commit/b38ba016283c6491d6e525caeb6206b2b04321fc), [819888c](https://github.com/laravel/framework/commit/819888ca1776581225d57e00fdc4ba709cbcc5d0))
- Support inspecting multiple events of same type using `Event` fake ([55be2ea](https://github.com/laravel/framework/commit/55be2ea35ccd2e450f9ffad23fd7ac446c035013))
- Replaced hard-coded year in plain-text markdown emails ([#17684](https://github.com/laravel/framework/pull/17684))
- Made button component in plain-text markdown emails easier to read ([#17683](https://github.com/laravel/framework/pull/17683))

### Fixed
- Set `Command::$name` in `Command::configureUsingFluentDefinition()` ([#17610](https://github.com/laravel/framework/pull/17610))
- Support post size `0` (unlimited) in `ValidatePostSize` ([#17607](https://github.com/laravel/framework/pull/17607))
- Fixed method signature issues in `PhpRedisConnection` ([#17627](https://github.com/laravel/framework/pull/17627))
- Fixed `BelongsTo` not accepting id of `0` in foreign relations ([#17668](https://github.com/laravel/framework/pull/17668))
- Support double quotes with `@section()` ([#17677](https://github.com/laravel/framework/pull/17677))
- Fixed parsing explicit validator rules ([#17681](https://github.com/laravel/framework/pull/17681))
- Fixed `SessionGuard::recaller()` when request is `null` ([#17688](https://github.com/laravel/framework/pull/17688), [565456d](https://github.com/laravel/framework/commit/565456d89e8d6378c213edb7e9d0724fa8a5f473))
- Added missing `force` and `tries` options for `queue:listen` ([#17687](https://github.com/laravel/framework/pull/17687))
- Fixed how reservation works when queue is paused ([9d348c5](https://github.com/laravel/framework/commit/9d348c5d57873bfcca86cff1987e22472d1f0e5b))


## v5.4.6 (2017-01-27)

### Added
- Generate non-existent models with `make:controller` ([#17587](https://github.com/laravel/framework/pull/17587), [382b78c](https://github.com/laravel/framework/commit/382b78ca12282c580ff801a00b2e52faf50c6d38))
- Added `TestResponse::dump()` method ([#17600](https://github.com/laravel/framework/pull/17600))

### Changed
- Switch to `ViewFactory` contract in `Mail/Markdown` ([#17591](https://github.com/laravel/framework/pull/17591))
- Use implicit binding when generating controllers with `make:model` ([#17588](https://github.com/laravel/framework/pull/17588))
- Made PhpRedis method signatures compatibility with Predis ([#17488](https://github.com/laravel/framework/pull/17488))
- Use `config('app.name')` in `markdown/message.blade.php` ([#17604](https://github.com/laravel/framework/pull/17604))
- Use `getStatusCode()` instead of `status()` in `TestResponse::fromBaseResponse()` ([#17590](https://github.com/laravel/framework/pull/17590))

### Fixed
- Fixed loading of `.env.testing` when running PHPUnit ([#17596](https://github.com/laravel/framework/pull/17596))


## v5.4.5 (2017-01-26)

### Fixed
- Fixed database session data not persisting ([#17584](https://github.com/laravel/framework/pull/17584))


## v5.4.4 (2017-01-26)

### Added
- Add `hasMiddlewareGroup()` and `getMiddlewareGroups()` method to `Router` ([#17576](https://github.com/laravel/framework/pull/17576))

### Fixed
- Fixed `--database` option on `migrate` commands ([#17574](https://github.com/laravel/framework/pull/17574))
- Fixed `$sequence` being always overwritten in `PostgresGrammar::compileInsertGetId()` ([#17570](https://github.com/laravel/framework/pull/17570))

### Removed
- Removed various unused parameters from view compilers ([#17554](https://github.com/laravel/framework/pull/17554))
- Removed superfluous `ForceDelete` extension from `SoftDeletingScope` ([#17552](https://github.com/laravel/framework/pull/17552))


## v5.4.3 (2017-01-25)

### Added
- Mock `dispatch()` method in `MocksApplicationServices` ([#17543](https://github.com/laravel/framework/pull/17543), [d974a88](https://github.com/laravel/framework/commit/d974a8828221ba8673cc4f6d9124d1d33f3de447))

### Changed
- Moved `$forElseCounter` property from `BladeCompiler` to `CompilesLoops` ([#17538](https://github.com/laravel/framework/pull/17538))

### Fixed
- Fixed bug in `Router::pushMiddlewareToGroup()` ([1054fd2](https://github.com/laravel/framework/commit/1054fd2523913e59e980553b5411a22f16ecf817))
- Fixed indentation in `Notifications/resources/views/email.blade.php` ([0435cfc](https://github.com/laravel/framework/commit/0435cfcf171908432d88e447fe4021998e515b9f))


## v5.4.2 (2017-01-25)

### Fixed
- Fixed removal of reset tokens after password reset ([#17524](https://github.com/laravel/framework/pull/17524))


## v5.4.1 (2017-01-24)

### Fixed
- Fixed view parent placeholding ([64f7e9c](https://github.com/laravel/framework/commit/64f7e9c4e37637df7b0820b11d5fcee1c1cca58d))

## v5.4.0 (2017-01-24)

### General
- Added real-time facades 😈 ([feb52bf](https://github.com/laravel/framework/commit/feb52bf966c0ea517ec0cf688b5a2534b50a8268))
- Added `retry()` helper ([e3bd359](https://github.com/laravel/framework/commit/e3bd359d52cee0ba8db9673e45a8221c1c1d95d6), [52e9381](https://github.com/laravel/framework/commit/52e9381d3d64631f2842c1d86fee2aa64a6c73ac))
- Added `array_wrap()` helper function ([0f76617](https://github.com/laravel/framework/commit/0f766177e4ac42eceb00aa691634b00a77b18b59))
- Added default 503 error page into framework ([855a8aa](https://github.com/laravel/framework/commit/855a8aaca2903015e3fe26f756e73af9f1b98374), [#16848](https://github.com/laravel/framework/pull/16848))
- Added `Encrypter::encryptString()` to bypass serialization ([9725a8e](https://github.com/laravel/framework/commit/9725a8e7d0555474114f5cad9249fe8fe556836c))
- Removed compiled class file generation and deprecated `ServiceProvider::compiles()` ([#17003](https://github.com/laravel/framework/pull/17003), [733d829](https://github.com/laravel/framework/commit/733d829d6551dde2f290b6c26543dd08956e82e7))
- Renamed `DetectEnvironment` to `LoadEnvironmentVariables` ([c36874d](https://github.com/laravel/framework/commit/c36874dda29d8eb9f9364c4bd308c7ee10060c25))
- Switched to `::class` notation across the codebase ([#17357](https://github.com/laravel/framework/pull/17357))

### Authentication
- Secured password reset tokens against timing attacks and compromised databases ([#16850](https://github.com/laravel/framework/pull/16850), [9d674b0](https://github.com/laravel/framework/commit/9d674b053145968ff9060b930a644ddd7851d66f))
- Refactored authentication component ([7b48bfc](https://github.com/laravel/framework/commit/7b48bfccf9ed12c71461651bbf52a3214b58d82e), [5c4541b](https://github.com/laravel/framework/commit/5c4541bc43f22b0d99c5cc6db38781060bff836f))
- Added names to password reset routes ([#16988](https://github.com/laravel/framework/pull/16988))
- Stopped touching the user timestamp when updating the `remember_token` ([#17135](https://github.com/laravel/framework/pull/17135))

### Authorization
- Consider interfaces and extended classes in `Gate::resolvePolicyCallback()` ([#15757](https://github.com/laravel/framework/pull/15757))

### Blade
- Added Blade components and slots ([e8d2a45](https://github.com/laravel/framework/commit/e8d2a45479abd2ba6b524293ce5cfb599c8bf910), [a00a201](https://github.com/laravel/framework/commit/a00a2016a4ff6518b60845745fc0533058f6adc6))
- Refactored Blade component ([7cdb6a6](https://github.com/laravel/framework/commit/7cdb6a6f6b77c91906c4ad0c6110b30042a43277), [5e394bb](https://github.com/laravel/framework/commit/5e394bb2c4b20833ea07b052823fe744491bdbd5))
- Refactored View component ([#17018](https://github.com/laravel/framework/pull/17018), [bb998dc](https://github.com/laravel/framework/commit/bb998dc23e7f4da5820b61fb5eb606fe4a654a2a))
- Refactored Blade `@parent` compilation ([#16033](https://github.com/laravel/framework/pull/16033), [16f72a5](https://github.com/laravel/framework/commit/16f72a5a580b593ac804bc0b2fdcc6eb278e55b2))
- Added support for translation blocks in Blade templates ([7179935](https://github.com/laravel/framework/commit/71799359b7e74995be862e498d1b21841ff55fbc))
- Don't reverse the order of `@push`ed data ([#16325](https://github.com/laravel/framework/pull/16325))
- Allow view data to be passed Paginator methods ([#17331](https://github.com/laravel/framework/pull/17331))
- Add `mix()` helper method ([6ea4997](https://github.com/laravel/framework/commit/6ea4997fcf7cf0ae4c18bce9418817ff00e4727f))
- Escape inline sections content ([#17453](https://github.com/laravel/framework/pull/17453))

### Broadcasting
- Added model binding in broadcasting channel definitions ([#16120](https://github.com/laravel/framework/pull/16120), [515d97c](https://github.com/laravel/framework/commit/515d97c1f3ad4797876979d450304684012142d6))
- Added `Dispatchable::broadcast()` [0fd8f8d](https://github.com/laravel/framework/commit/0fd8f8de75545b5701e59f51c88d02c12528800a)
- Switched to broadcasting events using new style jobs ([#17433](https://github.com/laravel/framework/pull/17433))

### Cache
- Added `RedisStore::add()` to store an item in the cache if the key doesn't exist ([#15877](https://github.com/laravel/framework/pull/15877))
- Added `cache:forget` command ([#16201](https://github.com/laravel/framework/pull/16201), [7644977](https://github.com/laravel/framework/commit/76449777741fa1d7669028973958a7e4a5e64f71))
- Refactored cache events ([b7454f0](https://github.com/laravel/framework/commit/b7454f0e67720c702d8f201fd6ca81db6837d461), [#17120](https://github.com/laravel/framework/pull/17120))
- `Cache::flush()` now returns boolean ([#15831](https://github.com/laravel/framework/pull/15831), [057492d](https://github.com/laravel/framework/commit/057492d31c569e96a3ba2f99722112a9762c6071))

### Collections
- Added higher-order messages for the collections ([#16267](https://github.com/laravel/framework/pull/16267), [e276b3d](https://github.com/laravel/framework/commit/e276b3d4bf2a124c4eb5975a8a2724b8c806139a), [2b7ab30](https://github.com/laravel/framework/commit/2b7ab30e0ec56ac4e4093d7f2775da98086c8000), [#16274](https://github.com/laravel/framework/pull/16274), [724950a](https://github.com/laravel/framework/commit/724950a42c225c7b53c56283c01576b050fea37a), [#17000](https://github.com/laravel/framework/pull/17000))
- Allow collection macros to be proxied ([#16749](https://github.com/laravel/framework/pull/16749))
- Added operator support to `Collection::contains()` method ([#16791](https://github.com/laravel/framework/pull/16791))
- Added `Collection::every()` method ([#16777](https://github.com/laravel/framework/pull/16777))
- Allow passing an array to `Collection::find()` ([#16849](https://github.com/laravel/framework/pull/16849))
- Always return a collection when calling `Collection::random()` with a parameter ([#16865](https://github.com/laravel/framework/pull/16865))
- Don't renumber the keys and keep the input array order in `mapWithKeys()` ([#16564](https://github.com/laravel/framework/pull/16564))

### Console
- Added `--model` to `make:controller` command to generate resource controller with type-hinted model ([#16787](https://github.com/laravel/framework/pull/16787))
- Require confirmation for `key:generate` command in production ([#16804](https://github.com/laravel/framework/pull/16804))
- Added `ManagesFrequencies` trait ([e238299](https://github.com/laravel/framework/commit/e238299f12ee91a65ac021feca29b870b05f5dd7))
- Added `Queueable` to queued listener stub ([dcd64b6](https://github.com/laravel/framework/commit/dcd64b6c36d1e545c1c2612764ec280c47fdea97))
- Switched from file to cache based Schedule overlap locking ([#16196](https://github.com/laravel/framework/pull/16196), [5973f6c](https://github.com/laravel/framework/commit/5973f6c54ccd0d99e15f055c5a16b19b8c45db91))
- Changed namespace generation in `GeneratorCommand` ([de9e03d](https://github.com/laravel/framework/commit/de9e03d5bd80d32a936d30ab133d2df0a3fa1d8d))
- Added `Command::$hidden` and `ScheduleFinishCommand` ([#16806](https://github.com/laravel/framework/pull/16806))
- Moved all framework command registrations into `ArtisanServiceProvider` ([954a333](https://github.com/laravel/framework/commit/954a33371bd7f7597eae6fce2ed1d391a2268099), [baa6054](https://github.com/laravel/framework/commit/baa605424a4448ab4f1c6068d8755ecf83bde665), [87bd2a9](https://github.com/laravel/framework/commit/87bd2a9e6c79715a9c73ca6134074919ede1a0e7))
- Support passing output buffer to `Artisan::call()` ([#16930](https://github.com/laravel/framework/pull/16930))
- Moved `tinker` into an external package ([#17002](https://github.com/laravel/framework/pull/17002))
- Refactored queue commands ([07a9402](https://github.com/laravel/framework/commit/07a9402f5d1b2fb5dedc22751a59914ebcf41562), [a82a25f](https://github.com/laravel/framework/commit/a82a25f58252eab6831a0efde35a17403710abdc), [f2beb2b](https://github.com/laravel/framework/commit/f2beb2bbce11433283ba52744dbe7134aa55cbfa))
- Allow tasks to be scheduled on weekends ([#17085](https://github.com/laravel/framework/pull/17085))
- Allow console events to be macroable ([#17107](https://github.com/laravel/framework/pull/17107))

### Container
- Added `Container::factory()` method to the Container contract ([#15430](https://github.com/laravel/framework/pull/15430))
- Added support for binding methods to the container ([#16800](https://github.com/laravel/framework/pull/16800), [1fa8ea0](https://github.com/laravel/framework/commit/1fa8ea02c096d09bea909b7bffa24b861dc76240))
- Trigger callback when binding an extension or resolving callback to an alias ([c99098f](https://github.com/laravel/framework/commit/c99098fc85c9633db578f70fba454184609c515d))
- Support contextual binding with aliases ([c99098f](https://github.com/laravel/framework/commit/c99098fc85c9633db578f70fba454184609c515d))
- Removed `$parameters` from `Application::make()` and `app()`/`resolve()` helpers ([#17071](https://github.com/laravel/framework/pull/17071), [#17060](https://github.com/laravel/framework/pull/17060))
- Removed `Container::share()` ([1a1969b](https://github.com/laravel/framework/commit/1a1969b6e6f793c3b2a479362641487ee9cbf736))
- Removed `Container::normalize()` ([ff993b8](https://github.com/laravel/framework/commit/ff993b806dcb21ba8a5367594e87d113338c1670))

### DB
- Refactored all database components (_too many commits, sorry_)
- Allow rolling back to a given transaction save-point ([#15876](https://github.com/laravel/framework/pull/15876))
- Added `$values` parameter to `Builder::firstOrNew()` ([#15567](https://github.com/laravel/framework/pull/15567))
- Allow dependency injection on database seeders `run()` method ([#15959](https://github.com/laravel/framework/pull/15959))
- Added support for joins when deleting deleting records using SqlServer ([#16618](https://github.com/laravel/framework/pull/16618))
- Added collation support to `SQLServerGrammar` ([#16227](https://github.com/laravel/framework/pull/16227))
- Don't rollback to save-points on deadlock (nested transaction) ([#15932](https://github.com/laravel/framework/pull/15932))
- Improve `Connection::selectOne()` performance by switching to `array_shift()` ([#16188](https://github.com/laravel/framework/pull/16188))
- Added `having()` shortcut ([#17160](https://github.com/laravel/framework/pull/17160))
- Added customer connection resolver ([#17248](https://github.com/laravel/framework/pull/17248))
- Support aliasing database names with spaces ([#17312](https://github.com/laravel/framework/pull/17312))
- Support column aliases using `chunkById()` ([#17034](https://github.com/laravel/framework/pull/17034))
- Execute queries with locks only on write connection ([#17386](https://github.com/laravel/framework/pull/17386))
- Added `compileLock()` method to `SqlServerGrammar` ([#17424](https://github.com/laravel/framework/pull/17424))

### Eloquent
- Refactored Eloquent (_too many commits, sorry_)
- Added support for object-based events for native Eloquent events ([e7a724d](https://github.com/laravel/framework/commit/e7a724d3895f2b24b98c0cafb1650f2193351d83), [9770d1a](https://github.com/laravel/framework/commit/9770d1a64c1010daf845fcebfcc4695a30d8df2d))
- Added custom class support for pivot models ([#14293](https://github.com/laravel/framework/pull/14293), [5459777](https://github.com/laravel/framework/commit/5459777c90ff6d0888bd821027c417d57cc89981))
- Use the model's primary key instead of `id` in `Model::getForeignKey()` ([#16396](https://github.com/laravel/framework/pull/16396))
- Made `date` and `datetime` cast difference more explicit ([#16799](https://github.com/laravel/framework/pull/16799))
- Use `getKeyType()` instead of `$keyType` in `Model` ([#16608](https://github.com/laravel/framework/pull/16608))
- Only detach all associations if no parameter is passed to `BelongsToMany::detach()` ([#16144](https://github.com/laravel/framework/pull/16144))
- Return a database collection from `HasOneOrMany::createMany()` ([#15944](https://github.com/laravel/framework/pull/15944))
- Throw `JsonEncodingException` when `Model::toJson()` fails ([#16159](https://github.com/laravel/framework/pull/16159), [0bda866](https://github.com/laravel/framework/commit/0bda866a475de524eeff3e7f7471031dd64cf2d3))
- Default foreign key for `belongsTo()` relationship is now dynamic ([#16847](https://github.com/laravel/framework/pull/16847))
- Added `whereKey()` method ([#16558](https://github.com/laravel/framework/pull/16558))
- Use parent connection if related model doesn't specify one ([#16103](https://github.com/laravel/framework/pull/16103))
- Enforce an `orderBy` clause for `chunk()` ([#16283](https://github.com/laravel/framework/pull/16283), [#16513](https://github.com/laravel/framework/pull/16513))
- Added `$connection` parameter to `create()` and `forceCreate()` ([#17392](https://github.com/laravel/framework/pull/17392))

### Events
- Removed event priorities ([dbbfc62](https://github.com/laravel/framework/commit/dbbfc62beff1625b0d45bbf39650d047555cf4fa), [#17245](https://github.com/laravel/framework/pull/17245), [f83edc1](https://github.com/laravel/framework/commit/f83edc1cb820523fd933c3d3c0430a1f63a073ec))
- Allow queued handlers to specify their queue and connection ([fedd4cd](https://github.com/laravel/framework/commit/fedd4cd4d900656071d44fc1ee9c83e6de986fa8))
- Converted `locale.changed` event into `LocaleUpdated` class ([3385fdc](https://github.com/laravel/framework/commit/3385fdc0f8e4890ab57261755bcbbf79f9ec828d))
- Unified wording ([2dcde69](https://github.com/laravel/framework/commit/2dcde6983ffbb4faf1c238544b51831b33c3a857))
- Allow chaining queueable methods onto trait dispatch ([9fde549](https://github.com/laravel/framework/commit/9fde54954c326b9021476aa87c96bf43a7885bcc))
- Removed `Queueable` trait from event listeners subs ([2a90ef4](https://github.com/laravel/framework/commit/2a90ef46a2121783f8c5c8f20274634cde115612))

### Filesystem
- Use UUID instead of `md5()` for generating file names in `FileHelpers` ([#16193](https://github.com/laravel/framework/pull/16193))
- Allow array of options on `Filesystem` operations ([481f760](https://github.com/laravel/framework/commit/481f76000c861e3e2540dcdda986fb44622ccbbe))

### HTTP
- Refactored session component ([66976ba](https://github.com/laravel/framework/commit/66976ba3f559ee6ede4cc865ea995996cd42ee1b), [d9e0a6a](https://github.com/laravel/framework/commit/d9e0a6a03891d16ed6a71151354445fbdc9e6f50))
- Added `Illuminate\Http\Request\Concerns` traits ([4810e9d](https://github.com/laravel/framework/commit/4810e9d1bc118367f3d70cd6f64f1d4c4acf85ca))
- Use variable-length method signature for `CookieJar::queue()` ([#16290](https://github.com/laravel/framework/pull/16290), [ddabaaa](https://github.com/laravel/framework/commit/ddabaaa6a8ce16876ddec36be1391eae14649aea))
- Added `FormRequestServiceProvider` ([b892805](https://github.com/laravel/framework/commit/b892805124ecdf4821c2dac7aea4f829ce2248bc))
- Renamed `Http/Exception` namespace to `Http/Exceptions` ([#17398](https://github.com/laravel/framework/pull/17398))
- Renamed `getJsonOptions()` to `getEncodingOptions()` on `JsonResponse` ([e689b2a](https://github.com/laravel/framework/commit/e689b2aa06d1d35d2593ffa77f8a56df314f7e49))
- Renamed `VerifyPostSize` middleware to `ValidatePostSize` ([893a044](https://github.com/laravel/framework/commit/893a044fb10c87095e99081de4d1668bc1e19997))
- Converted `kernel.handled` event into `RequestHandled` class ([43a5e5f](https://github.com/laravel/framework/commit/43a5e5f341cc8affd52e77019f50e2d96feb94a5))
- Throw `AuthorizationException` in `FormRequest` ([1a75409](https://github.com/laravel/framework/commit/1a7540967ca36f875a262a22b76c2a094b9ba3b4))
- Use `Str::random` instead of UUID in `FileHelpers` ([#17046](https://github.com/laravel/framework/pull/17046))
- Moved `getOriginalContent()` to `ResponseTrait` ([#17137](https://github.com/laravel/framework/pull/17137))
- Added JSON responses to the `AuthenticatesUsers` and `ThrottlesLogins` ([#17369](https://github.com/laravel/framework/pull/17369))
- Added middleware to trim strings and convert empty strings to null ([f578bbc](https://github.com/laravel/framework/commit/f578bbce25843492fc996ac96797e0395e16cf2e))

### Logging
- Added `LogServiceProvider` to defer loading of logging code ([#15451](https://github.com/laravel/framework/pull/15451), [6550153](https://github.com/laravel/framework/commit/6550153162b4d54d03d37dd9adfd0c95ca0383a9), [#15794](https://github.com/laravel/framework/pull/15794))
- The `Log` facade now uses `LoggerInterface` instead of the log writer ([#15855](https://github.com/laravel/framework/pull/15855))
- Converted `illuminate.log` event into `MessageLogged` class ([57c82d0](https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072))

### Mail
- Added support for Markdown emails and notifications ([#16768](https://github.com/laravel/framework/pull/16768), [b876759](https://github.com/laravel/framework/commit/b8767595e762d241a52607123da5922899bf65e1), [cd569f0](https://github.com/laravel/framework/commit/cd569f074fd566f30d3eb760c3c9027203da3850), [5325385](https://github.com/laravel/framework/commit/5325385f32331c44c5050cdd790dfbdfe943357b))
- Refactored Mail component and removed `SuperClosure` dependency ([50ab994](https://github.com/laravel/framework/commit/50ab994b5b9c2675eb6cc24412672df5aefd248c), [5dace8f](https://github.com/laravel/framework/commit/5dace8f0d6f6e67b4862abbbae376dcd8a641f00))
- Allow `Mailer` to email `HtmlString` objects ([882ea28](https://github.com/laravel/framework/commit/882ea283045a7a231ca86c75058ebdea1d160fda))
- Added `hasTo()`, `hasCc()` and `hasBcc()` to `Mailable` ([fb29b38](https://github.com/laravel/framework/commit/fb29b38d7c04c59e1f442b0d89fc6108c8671a08))

### Notifications
- Added `NotificationSender` class ([5f93133](https://github.com/laravel/framework/commit/5f93133170c40b203f0922fd29eb22e1ee20be21))
- Removed `to` and `cc` from mail `MailMessage` ([ff68549](https://github.com/laravel/framework/commit/ff685491f4739b899dbe91e5fb1683c28e2dc5e1))
- Add salutation option to `SimpleMessage` notification ([#17429](https://github.com/laravel/framework/pull/17429))

### Queue
- Support job-based queue options ([#16257](https://github.com/laravel/framework/pull/16257), [2382dc3](https://github.com/laravel/framework/commit/2382dc3f374bee7ad966d11ecb35a1429d9a09e8), [ee385fa](https://github.com/laravel/framework/commit/ee385fa5eab0c4642f47636f0e033e982d402bb9))
- Fixed manually failing jobs and added `FailingJob` class ([707a3bc](https://github.com/laravel/framework/commit/707a3bc84ce82bbe44e2c722ede24b5edb194b6b), [55afe12](https://github.com/laravel/framework/commit/55afe12977b55dbafda940e18102bb52276ca569))
- Converted `illuminate.queue.looping` event into `Looping` class ([57c82d0](https://github.com/laravel/framework/commit/57c82d095c356a0fe0f9381536afec768cdcc072))
- Refactored Queue component ([9bc8ca5](https://github.com/laravel/framework/commit/9bc8ca502687f29761b9eb78f70db6e3c3f0a09e), [e030231](https://github.com/laravel/framework/commit/e030231604479d0326ad9bfb56a2a36229d78ff4), [a041fb5](https://github.com/laravel/framework/commit/a041fb5ec9fc775d1a3efb6b647604da2b02b866), [7bb15cf](https://github.com/laravel/framework/commit/7bb15cf40a182bed3d00bc55de55798e58bf1ed0), [5505728](https://github.com/laravel/framework/commit/55057285b321b4b668d12fade330b0d196f9514a), [fed36bd](https://github.com/laravel/framework/commit/fed36bd7e09658009d36d9dd568f19ddcb75172e))
- Refactored how queue connection names are set ([4c600fb](https://github.com/laravel/framework/commit/4c600fb7af855747b6b44a194a5d0061d6294488))
- Let queue worker exit ungracefully on `memoryExceeded()` ([#17302](https://github.com/laravel/framework/pull/17302))
- Support pause and continue signals in queue worker ([827d075](https://github.com/laravel/framework/commit/827d075fb06b516eea992393279fc5ec2adabcf8))

### Redis
- Added support for [PhpRedis](https://github.com/phpredis/phpredis) ([#15160](https://github.com/laravel/framework/pull/15160), [01ed1c8](https://github.com/laravel/framework/commit/01ed1c8348a8e69ad213c95dd8d24e652154e6f0), [1ef8b9c](https://github.com/laravel/framework/commit/1ef8b9c3f156c7d4debc6c6f67b73b032d8337d5))
- Added support for multiple Redis clusters ([#16696](https://github.com/laravel/framework/pull/16696), [464075d](https://github.com/laravel/framework/commit/464075d3c5f152dfc4fc9287595d62dbdc3c6347))
- Added `RedisQueue::laterRaw()` method ([7fbac1c](https://github.com/laravel/framework/commit/7fbac1c6c09080da698f4c3256356cb896465692))
- Return migrated jobs from `RedisQueue::migrateExpiredJobs()` ([f21e942](https://github.com/laravel/framework/commit/f21e942ae8a4104ffdf42c231601efe8759c4c10))
- Send full job back into `RedisQueue` ([16e862c](https://github.com/laravel/framework/commit/16e862c1e22795acab869fa01ec5f8bcd7d400b3))

### Routing
- Added support for fluent routes ([#16647](https://github.com/laravel/framework/pull/16647), [#16748](https://github.com/laravel/framework/pull/16748))
- Removed `RouteServiceProvider::loadRoutesFrom()` ([0f2b3be](https://github.com/laravel/framework/commit/0f2b3be9b8753ba2813595f9191aa8d8c31886b1))
- Allow route groups to be loaded directly from a file ([#16707](https://github.com/laravel/framework/pull/16707), [#16792](https://github.com/laravel/framework/pull/16792))
- Added named parameters to `UrlGenerator` ([#16736](https://github.com/laravel/framework/pull/16736), [ce4d86b](https://github.com/laravel/framework/commit/ce4d86b48732a707e3909dbc553a2c349c8ecae7))
- Refactored Route component ([b75aca6](https://github.com/laravel/framework/commit/b75aca6a203590068161835945213fd1a39c7080), [9d3ff16](https://github.com/laravel/framework/commit/9d3ff161fd3929f9a106f007ce63fffdd118d490), [c906ed9](https://github.com/laravel/framework/commit/c906ed933713df22e4356cf4ea274f19b15d1ab7), [0f7985c](https://github.com/laravel/framework/commit/0f7985c888abb0a1824e87b32ab3d8feaca5fecf), [0f7985c](https://github.com/laravel/framework/commit/0f7985c888abb0a1824e87b32ab3d8feaca5fecf), [3f4221f](https://github.com/laravel/framework/commit/3f4221fe07c3e9d12eb814c144c1ffca09b577da))
- Refactored Router component ([eecf6ec](https://github.com/laravel/framework/commit/eecf6eca8b4a0cfdf8ec2b0148ee726b8b67c6bb), [b208a4f](https://github.com/laravel/framework/commit/b208a4fc3b35da167a2dcb9b581d9e072d20ec92), [21de409](https://github.com/laravel/framework/commit/21de40971cd81712b398ef3895357843fd34250d), [e75730e](https://github.com/laravel/framework/commit/e75730ec192bb2927a46f37ef854ba8c7372cac6))
- Refactored Router URL generator component ([39e8c83](https://github.com/laravel/framework/commit/39e8c83af778d8086b0b5e8f4f2e21331b015b39), [098da0d](https://github.com/laravel/framework/commit/098da0d6b4c20104c60b969b9a7f10ac5ff50c8e))
- Removed `RouteDependencyResolverTrait::callWithDependencies()` ([f7f13fa](https://github.com/laravel/framework/commit/f7f13fab9a451bc2249fc0709b6cf1fa6b7c795a))
- `UrlGenerator` improvements ([f0b9858](https://github.com/laravel/framework/commit/f0b985831f72a896735d02bf14b1c6680e3d7092), [4f96f42](https://github.com/laravel/framework/commit/4f96f429b22b1b09de6a263bd7d50eda18075b52))
- Compile routes only once ([c8ed0c3](https://github.com/laravel/framework/commit/c8ed0c3a11bf7d8180982a3d32a60364594bbfe1), [b11fbcc](https://github.com/laravel/framework/commit/b11fbcc209b8a57501bac6221728e7ed6c7a82a2))

### Testing
- Simplified built-in testing for Dusk ([#16667](https://github.com/laravel/framework/pull/16667), [126adb7](https://github.com/laravel/framework/commit/126adb781c204129600363f243b9d73e202d229e), [b6dec26](https://github.com/laravel/framework/commit/b6dec2602d4a7aa1e61667c02c301c8011267a19), [939264f](https://github.com/laravel/framework/commit/939264f91edc5d33da5ce6cf95a271a6f4a2e1f2))
- Improve database testing methods ([#16679](https://github.com/laravel/framework/pull/16679), [14e9dad](https://github.com/laravel/framework/commit/14e9dad05d09429fab244e2d8f6c49e679a3a975), [f23ac64](https://github.com/laravel/framework/commit/f23ac640fa403ca8d4131c36367b53e123b6b852))
- Refactored `MailFake` ([b1d8f81](https://github.com/laravel/framework/commit/b1d8f813d13960096493f3adc3bc32ace66ba2e6))
- Namespaced all tests ([#17058](https://github.com/laravel/framework/pull/17058), [#17148](https://github.com/laravel/framework/pull/17148))
- Allow chaining of response assertions ([#17330](https://github.com/laravel/framework/pull/17330))
- Return `TestResponse` from `MakesHttpRequests::json()` ([#17341](https://github.com/laravel/framework/pull/17341))
- Always return collection from factory when `$amount` is set ([#17493](https://github.com/laravel/framework/pull/17493))

### Translations
- Added JSON loader for translations and `__()` helper ([#16424](https://github.com/laravel/framework/pull/16424), [#16470](https://github.com/laravel/framework/pull/16470), [9437244](https://github.com/laravel/framework/commit/94372447b9de48f5c174db2cf7c81dffb3c0c692))
- Replaced Symfony's translator ([#15563](https://github.com/laravel/framework/pull/15563))
- Added `namespaces()` method to translation loaders ([#16664](https://github.com/laravel/framework/pull/16664), [fe7bbf7](https://github.com/laravel/framework/commit/fe7bbf727834a748b04fcf5145b1137dd45ac4b7))
- Switched to `trans()` helper in `AuthenticatesUsers` ([#17202](https://github.com/laravel/framework/pull/17202))

### Validation
- Refactored Validation component ([#17005](https://github.com/laravel/framework/pull/17005), [9e98e7a](https://github.com/laravel/framework/commit/9e98e7a5120f14e942bd00a1439e1a049440eea8), [9b817f1](https://github.com/laravel/framework/commit/9b817f1d03b3a7b3379723a32ab818aa3860060a))
- Removed files hydration in `Validator` ([#16017](https://github.com/laravel/framework/pull/16017))
- Added IPv4 and IPv6 validators ([#16545](https://github.com/laravel/framework/pull/16545))
- Made `date_format` validation more precise ([#16858](https://github.com/laravel/framework/pull/16858))
- Add place-holder replacers for `*_or_equal` rules ([#17030](https://github.com/laravel/framework/pull/17030))
- Made `sometimes()` chainable ([#17241](https://github.com/laravel/framework/pull/17241))
- Support wildcards in `MessageBag::first()` ([#15217](https://github.com/laravel/framework/pull/15217))
- Support implicit keys in `MessageBag::first()` and `MessageBag::first()` ([#17001](https://github.com/laravel/framework/pull/17001))
- Support arrays with empty string as key ([#17427](https://github.com/laravel/framework/pull/17427))
- Add type check to `validateUrl()` ([#17504](https://github.com/laravel/framework/pull/17504))
