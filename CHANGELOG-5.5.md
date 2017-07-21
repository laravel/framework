# Release Notes for 5.5.x

## [Unreleased]

### General
- ⚠️ Require PHP 7+ ([06907a0](https://github.com/laravel/framework/pull/17048/commits/06907a055e3d28c219f6b6ab97902f0be3e8a4ef), [39809ce](https://github.com/laravel/framework/pull/17048/commits/39809cea81a5564d196c16a87cbc25de88dd3d1c))
- ⚠️ Removed deprecated `ServiceProvider::compile()` method ([10da428](https://github.com/laravel/framework/pull/17048/commits/10da428eb344191608474f1c12ee7edb0290e80a))
- ⚠️ Removed deprecated `Str::quickRandom()` method ([2ef257a](https://github.com/laravel/framework/pull/17048/commits/2ef257a4197b7e6efeb0d6ac4a3958f82b7fed39))
- Removed `build` scripts ([7c16b15](https://github.com/laravel/framework/pull/17048/commits/7c16b154ede10ff9a37756e32d7dddf317524634))
- Support callable/invokable objects in `Pipeline` ([#18264](https://github.com/laravel/framework/pull/18264))
- Support for `Responsable` objects ([c0c89fd](https://github.com/laravel/framework/commit/c0c89fd73cebf9ed56e6c5e69ad35106df03d9db), [1229b7f](https://github.com/laravel/framework/commit/1229b7f45d3f574d7e0262cc2d5aec80ccbb1626), [#19614](https://github.com/laravel/framework/pull/19614))
- ⚠️ Prevent access to protected properties using array access on `Model` and `Fluent` ([#18403](https://github.com/laravel/framework/pull/18403))
- ⚠️ Extend `MessageBag` interface from `Arrayable` ([#19768](https://github.com/laravel/framework/pull/19768))
- Added `isNotEmpty()` method to message bags and paginators ([#19944](https://github.com/laravel/framework/pull/19944))
- Throw `RuntimeException` when app key is missing ([#19145](https://github.com/laravel/framework/pull/19145), [8adbaa7](https://github.com/laravel/framework/commit/8adbaa714d37bb7214f29b12c52354900a1c6dc5))
- Autoload package providers ([#19420](https://github.com/laravel/framework/pull/19420), [a5a0f3e](https://github.com/laravel/framework/commit/a5a0f3e7b82a1a4dc00037c5463a31d42c94903a), [2954091](https://github.com/laravel/framework/commit/295409189af589c6389d01e9d55f5568741149ee), [#19455](https://github.com/laravel/framework/pull/19455), [#19561](https://github.com/laravel/framework/pull/19561), [#19646](https://github.com/laravel/framework/pull/19646))
- Use Symfony 3.3 components ([4db7031](https://github.com/laravel/framework/commit/4db70311b1b3813359b250d3f5a58743fa436453), [67a5367](https://github.com/laravel/framework/commit/67a536758d1636935ab5502bb6faedd73b30810f))
- Support registering macros using classes ([#19782](https://github.com/laravel/framework/pull/19782), [353adbd](https://github.com/laravel/framework/commit/353adbd696e36764227e39980272d38147899d14))
- Made `Carbon` macroable ([#19771](https://github.com/laravel/framework/pull/19771))

### Artisan Console
- Added interactive prompt to `vendor:publish` ([#18230](https://github.com/laravel/framework/pull/18230))
- Added `migrate:fresh` command ([f6511d4](https://github.com/laravel/framework/commit/f6511d477f73b3033ef2336257f4cac5f20594a0))
- Added `make:factory` command and added `--factory` to `make:model` ([a6ffd8b](https://github.com/laravel/framework/commit/a6ffd8bfa896844fee4b4c83cc6aed9d0c33fd9d))
- Added `make:rule` command ([76853fd](https://github.com/laravel/framework/commit/76853fd192f8f378ad9b781d64e3e40a9511f737))
- ⚠️ Added `runningInConsole()` method `Application` contract ([#18658](https://github.com/laravel/framework/pull/18658))
- Support default value(s) on command arguments ([#18572](https://github.com/laravel/framework/pull/18572))
- Improved CLI detection for phpdbg ([#18781](https://github.com/laravel/framework/pull/18781))
- ⚠️ Always return array from `RetryCommand::getJobIds()` ([#19232](https://github.com/laravel/framework/pull/19232))
- Support passing absolute paths to `make::listener` ([#19660](https://github.com/laravel/framework/pull/19660))
- ⚠️ Use `handle()` method instead of `fire()` ([#19827](https://github.com/laravel/framework/pull/19827), [#19839](https://github.com/laravel/framework/pull/19839))
- Removed deprecated `--daemon` option from `queue:work` command ([#19914](https://github.com/laravel/framework/pull/19914))

### Assets
- Added frontend preset commands (_too many commits, sorry_)

### Authentication
- ⚠️ Support default user providers and pass user provider to `RequestGuard` ([#18856](https://github.com/laravel/framework/pull/18856))
- Made the user provider parameter on `RequestGuard` optional ([d7f0b26](https://github.com/laravel/framework/commit/d7f0b2603ce0a0a568f84a8861c351a2c00d5613))
- Use `setRememberToken()` in `ResetsPasswords` ([#19189](https://github.com/laravel/framework/pull/19189))
- Added a `PasswordReset` event ([#19188](https://github.com/laravel/framework/pull/19188))
- ⚠️ Support multiword models in `authorizeResource()` ([#19821](https://github.com/laravel/framework/pull/19821))

### Authorization
- Support multiple values in `Gate::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- ⚠️ Prevent policies from being too greedy ([#19120](https://github.com/laravel/framework/pull/19120))
- ⚠️ Added `abilities()` method to `Gate` contract ([#19173](https://github.com/laravel/framework/pull/19173))

### Blade Templates
- Added `Blade::if()` method ([71dfe0f](https://github.com/laravel/framework/commit/71dfe0f0824412f106b80df8dedd7708e66dfb00), [2905364](https://github.com/laravel/framework/commit/2905364f7c9f14b42a7283e56313b38d256ce09d))
- Added `@switch`, `@case`, `@break` and `@default` directives ([#19758](https://github.com/laravel/framework/pull/19758))

### Broadcasting
- ⚠️ Use `AccessDeniedHttpException` instead if `HttpException` ([#19611](https://github.com/laravel/framework/pull/19611))

### Cache
- Don't encrypt database cache values ([f0c72ec](https://github.com/laravel/framework/commit/f0c72ec9bcbdecb7e6267f7ec8f7ecbf8169a388))
- Added support cache locks ([4e6b2e4](https://github.com/laravel/framework/commit/4e6b2e4ecbbec5a4b265f4d5a57ad1399227cf12), [045e6f2](https://github.com/laravel/framework/commit/045e6f25a860763942c928c4e6d8857d59741486), [#19669](https://github.com/laravel/framework/pull/19669))

### Collections
- Support multiple values in `Collection::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- Added `Collection::mapInto()` method ([2642ac7](https://github.com/laravel/framework/commit/2642ac73cc5718a8aebe3d009b143b0fa43be085))
- Added `Collection::dd()` method ([f5fafad](https://github.com/laravel/framework/commit/f5fafad80dbb08353824483f5b849031693cc477))
- Added `Collection::dump()` method ([#19755](https://github.com/laravel/framework/pull/19755))

### Configuration
- Added `Config::getMany()` method ([#19770](https://github.com/laravel/framework/pull/19770))

### Database
- ⚠️ Added `dropAllTables()` to schema builder ([#18484](https://github.com/laravel/framework/pull/18484), [d910bc8](https://github.com/laravel/framework/commit/d910bc8039f3cec2d906797818984e825601a3f5), [#19644](https://github.com/laravel/framework/pull/19644), [#19645](https://github.com/laravel/framework/pull/19645))
- Added precision to `dateTime` and `timestamp` column types ([#18847](https://github.com/laravel/framework/pull/18847), [f85f6db](https://github.com/laravel/framework/commit/f85f6db7c00a43ae45d963d089458477cf3e44b3), [#18962](https://github.com/laravel/framework/pull/18962))
- Pass page number to `chunk()` callback ([#19316](https://github.com/laravel/framework/pull/19316))
- Improve memory usage in `chunk()` and `chunkById()` ([#19345](https://github.com/laravel/framework/pull/19345), [#19369](https://github.com/laravel/framework/pull/19369), [#19368](https://github.com/laravel/framework/pull/19368))
- Fixed `compileColumnListing()` when using PostgreSQL with multiple schemas ([#19553](https://github.com/laravel/framework/pull/19553))
- Allow the seeder to call multiple commands at once ([#19912](https://github.com/laravel/framework/pull/19912))

### Eloquent ORM
- ⚠️ Indicate soft deleted models as existing ([#17613](https://github.com/laravel/framework/pull/17613))
- ⚠️ Added `$localKey` parameter to `HasRelationships::belongsToMany()` and `BelongsToMany` ([#17903](https://github.com/laravel/framework/pull/17903), [7c7c3bc](https://github.com/laravel/framework/commit/7c7c3bc4be3052afe0889fe323230dfd92f81000))
- ⚠️ Renamed `$parent` property to `$pivotParent` in `Pivot` class ([#17933](https://github.com/laravel/framework/pull/17933), [#18150](https://github.com/laravel/framework/pull/18150))
- ⚠️ Don't add `_count` suffix to column name when using `withCount()` with an alias ([#17871](https://github.com/laravel/framework/pull/17871))
- ⚠️ Renamed `$events` to `$dispatchesEvents` ([#17961](https://github.com/laravel/framework/pull/17961), [b6472bf](https://github.com/laravel/framework/commit/b6472bf6fec1af6e76604aaf3f7fed665440ac66), [3dbe12f](https://github.com/laravel/framework/commit/3dbe12f16f470e3bca868576d517d57876bc50af))
- ⚠️ Added `$parentKey` parameter to `belongsToMany()`, `BelongsToMany` and `MorphToMany` ([#17915](https://github.com/laravel/framework/pull/17915), [#18380](https://github.com/laravel/framework/pull/18380))
- ⚠️ Only return query builder when the result is null for `callScope()` ([#18845](https://github.com/laravel/framework/pull/18845))
- Allow setting a factory's attribute to a factory instance ([#18879](https://github.com/laravel/framework/pull/18879))
- Support `null` comparison in `Model::is()` ([#18511](https://github.com/laravel/framework/pull/18511))
- Added `getDirty()` checks for date and castable attributes ([#18400](https://github.com/laravel/framework/pull/18400), [e180e20](https://github.com/laravel/framework/commit/e180e20aa479525b34f77b9cf348148d329a4d2c))
- Show method name in invalid relationship `LogicException` ([#18749](https://github.com/laravel/framework/pull/18749))
- Add support for additional values in `firstOrCreate()` and `firstOrNew()` ([#18878](https://github.com/laravel/framework/pull/18878))
- Added a second local key to `HasManyThrough` ([#19114](https://github.com/laravel/framework/pull/19114))
- Respect casts declaration on custom pivot models ([#19335](https://github.com/laravel/framework/pull/19335))
- Support creating relations without attributes ([#19506](https://github.com/laravel/framework/pull/19506))
- Added `Model::only()` method ([#19459](https://github.com/laravel/framework/pull/19459))
- ⚠️ Support model serialization on non default connection ([#19521](https://github.com/laravel/framework/pull/19521), [dd45f70](https://github.com/laravel/framework/commit/dd45f70519b72aa57bc21cec4e89886917990fa9))
- ⚠️ Support updating nullable dates ([#19672](https://github.com/laravel/framework/pull/19672))
- ⚠️ Make pivot model instantiable ([#20179](https://github.com/laravel/framework/pull/20179))

### Encryption
- Use `openssl_cipher_iv_length()` in `Encrypter` ([#18684](https://github.com/laravel/framework/pull/18684))
- Added `Encrypter::generateKey()` method ([6623996](https://github.com/laravel/framework/commit/6623996212b3d59aa31a374b70311f03fd158075))

### Errors & Logging
- Added default 404, 429 and 500 error pages ([#18483](https://github.com/laravel/framework/pull/18483), [4d8c2c1](https://github.com/laravel/framework/commit/4d8c2c1f53979a669a59793b4ec61c8e60ed5b29))
- ⚠️ Always show custom 500 error page for all exception types when not in debug mode ([#18481](https://github.com/laravel/framework/pull/18481), [3cb7b0f](https://github.com/laravel/framework/commit/3cb7b0f4304274f209ed0f776ef70ccd4f9fe5dd))
- ⚠️ Show 419 error page on `TokenMismatchException` ([#18728](https://github.com/laravel/framework/pull/18728))
- Support `render()` method on exceptions ([ed51160](https://github.com/laravel/framework/commit/ed51160b97d8c4cf16526a0f8ba57ce7cb131b53), [c8a9413](https://github.com/laravel/framework/commit/c8a9413e2dc3bf00c206742e2bc76a88134cba84))
- Support `report()` method on exceptions ([e77f6f7](https://github.com/laravel/framework/commit/e77f6f76049050fd4abced63ffa768432d8974f2))
- ⚠️ Send exceptions as JSON in debug mode if the request wants JSON ([5225389](https://github.com/laravel/framework/commit/5225389dfdf03d656b862bba59cebf1820e0e8f4), [#18732](https://github.com/laravel/framework/pull/18732), [4fe6091](https://github.com/laravel/framework/commit/4fe6091e9fc94817a70c47a6a1c2098d5a1805f8), [9ab58fd](https://github.com/laravel/framework/commit/9ab58fd1a0543b1c728124db7f70738b04dcf362), [#19333](https://github.com/laravel/framework/pull/19333))
- ⚠️ Moved exceptions from `$dontReport` into `$internalDontReport` ([841b36c](https://github.com/laravel/framework/commit/841b36cc005ee5c400f1276175db9e2692d1e167))
- Added `Handler::context()` method, that by default adds some default context to logs ([23b7d6b](https://github.com/laravel/framework/commit/23b7d6b45c675bcd93e9f1fb9cd33e71779142c6))
- ⚠️ Don't set formatter on `ErrorLogHandler` ([a044f17](https://github.com/laravel/framework/commit/a044f17897eeda3ab909ea47eeba3804dabdf9ad))
- Use whoops for errors ([b697272](https://github.com/laravel/framework/commit/b69727243305e0ffa4a68819450716f26396c5e6), [f6b67d4](https://github.com/laravel/framework/commit/f6b67d4e49e6c4de765f4b29b3c36c5d4ff84471), [#19471](https://github.com/laravel/framework/pull/19471))
- Changed how exceptions are logged ([#19698](https://github.com/laravel/framework/pull/19698), [f1971c2](https://github.com/laravel/framework/commit/f1971c2242e4882440162fe504126a1475f7f2b4))
- ⚠️ Return `HttpException` with code `413` from `PostTooLargeException` ([#19773](https://github.com/laravel/framework/pull/19773))

### Events
- ⚠️ Removed calling queue method on handlers ([0360cb1](https://github.com/laravel/framework/commit/0360cb1c6b71ec89d406517b19d1508511e98fb5), [ec96979](https://github.com/laravel/framework/commit/ec969797878f2c731034455af2397110732d14c4), [d9be4bf](https://github.com/laravel/framework/commit/d9be4bfe0367a8e07eed4931bdabf135292abb1b))
- Allow faking only specific events ([#19429](https://github.com/laravel/framework/pull/19429))
- Support self-registering event listeners ([#19917](https://github.com/laravel/framework/pull/19917), [4d557c5](https://github.com/laravel/framework/commit/4d557c5f0aa81fb9cb753d77ffec931c9166a927))

### Filesystem
- ⚠️ Made `Storage::files()` work like `Storage::allFiles()` ([#18874](https://github.com/laravel/framework/pull/18874), [7073457](https://github.com/laravel/framework/commit/7073457041a29ada14e0ed01d7d65f5c76a92689))
- ⚠️ Fixed compatibility between `FilesystemAdapter` and the `Filesystem` interface ([#19389](https://github.com/laravel/framework/pull/19389))

### Helpers
- Added `throw_if()` and `throw_unless()` helpers ([18bb4df](https://github.com/laravel/framework/commit/18bb4dfc77c7c289e9b40c4096816ebeff1cd843), [#19166](https://github.com/laravel/framework/pull/19166), [#19255](https://github.com/laravel/framework/pull/19255))
- Added `dispatch_now()` helper function ([#18668](https://github.com/laravel/framework/pull/18668), [61f2e7b](https://github.com/laravel/framework/commit/61f2e7b4106f8eb0b79603d9792426f7c6a6d273))
- Add `$language` parameter to `str_slug()` helper ([#19011](https://github.com/laravel/framework/pull/19011))
- Handle lower case words better in as `Str::snake()` ([#18764](https://github.com/laravel/framework/pull/18764))
- Removed usages of the `with()` helper ([#17888](https://github.com/laravel/framework/pull/17888))
- Added the `str_before()` helper ([#19940](https://github.com/laravel/framework/pull/19940))

### Localization
- Support language specific characters in `Str` ([#18974](https://github.com/laravel/framework/pull/18974), [#19694](https://github.com/laravel/framework/pull/19694))

### Mail
- Allow mailables to be rendered directly to views ([d9a6dfa](https://github.com/laravel/framework/commit/d9a6dfa4f46a10feceb67921b78c60a905b7c28c))
- Allow for per-mailable theme configuration ([b2c35ca](https://github.com/laravel/framework/commit/b2c35ca9eb769d1a4752a64e936defd7f7099043))
- ⚠️ Removed `$data` and `$callback` parameters from `Mailer` and `MailQueue`
- ⚠️ Made `Markdown` a dependency of `MailChannel` ([#19349](https://github.com/laravel/framework/pull/19349))
- ⚠️ Upgraded to SwiftMailer 6 ([#19356](https://github.com/laravel/framework/pull/19356))
- ⚠️ Added `to()` and `bcc()` to `Mailer` contract ([#19955](https://github.com/laravel/framework/pull/19955))

### Notifications
- Added methods for Slack's `thumb_url` and `unfurl_*` options ([#19150](https://github.com/laravel/framework/pull/19150), [#19200](https://github.com/laravel/framework/pull/19200))

### Queues
- Added support for chainable jobs ([81bcb03](https://github.com/laravel/framework/commit/81bcb03b303707cdc94420983b9d72ed558a2b3d), [94c01b1](https://github.com/laravel/framework/commit/94c01b1f37bfbb8e0d5f133b7dd34040b2bdc065), [91f5357](https://github.com/laravel/framework/commit/91f535704d4f6cff5e8393825dbdf46965234fa3), [434245f](https://github.com/laravel/framework/commit/434245f73e694f90476437da8554b58d54ced25c), [b880ad1](https://github.com/laravel/framework/commit/b880ad19282db768718cfd1629ebbc41054daadc))
- ⚠️ Removed redundant `$queue` parameter from `Queue::createPayload()` ([#17948](https://github.com/laravel/framework/pull/17948))
- Made all `getQueue()` methods `public` ([#18501](https://github.com/laravel/framework/pull/18501))
- Pass connection and queue to `Looping` event ([#19081](https://github.com/laravel/framework/pull/19081))
- ⚠️ Clone Job specific properties ([#19123](https://github.com/laravel/framework/pull/19123))
- ⚠️ Declare missing abstract `Job::getRawBody()` method ([#19677](https://github.com/laravel/framework/pull/19677))
- ⚠️ Fail (or optionally silently delete) job when model is missing during deserialization ([44b1f85](https://github.com/laravel/framework/commit/44b1f859bbaf8f33733c804857cc269de92b1fd4), [bceded6](https://github.com/laravel/framework/commit/bceded6fef79760b9907dbe105829f7d2d62f899))

### Redis
- Removed `PhpRedisConnection::proxyToEval()` method ([#17360](https://github.com/laravel/framework/pull/17360))

### Requests
- ⚠️ Made `Request::has()` work like `Collection::has()` ([#18715](https://github.com/laravel/framework/pull/18715))
- Added `Request::filled()` ([#18715](https://github.com/laravel/framework/pull/18715))
- ⚠️ Made `Request::only()` work like `Collection::only()` ([#18695](https://github.com/laravel/framework/pull/18695))
- ⚠️ Renamed `Request::exists()` to `Collection::filled()` ([#18715](https://github.com/laravel/framework/pull/18715))
- Aliased `Request::exists()` to `Request::has()` ([183bf16](https://github.com/laravel/framework/commit/183bf16a2c939889f4461e237a851b55cf858f8e))
- Allow passing keys to `Request::all()` to behave like old `Request::only()` ([#18754](https://github.com/laravel/framework/pull/18754))
- ⚠️ Removed `Request::intersect()` ([#18695](https://github.com/laravel/framework/pull/18695))
- Return request data from `ValidatesRequests` calls ([#19033](https://github.com/laravel/framework/pull/19033))
- Added a `validate()` macro onto `Request` ([#19063](https://github.com/laravel/framework/pull/19063))
- Added `FormRequest::validated()` method ([#19112](https://github.com/laravel/framework/pull/19112))
- ⚠️ Made `request()` helper and `Request::__get()` consistent ([a6ff272](https://github.com/laravel/framework/commit/a6ff272c54677a9f52718292fc0938ffb1871832))
- Made `Request::routeIs()` work like `Request()::fullUrlIs()` ([#19267](https://github.com/laravel/framework/pull/19267), [bfc5321](https://github.com/laravel/framework/commit/bfc53213f67d50444d3db078737990fa14081d1b), [#19334](https://github.com/laravel/framework/pull/19334))
- Added `Request::hasAny()` method  ([#19367](https://github.com/laravel/framework/pull/19367))
- ⚠️ Throw validation exception from `ValidatesRequests` without formatting response ([#19929](https://github.com/laravel/framework/pull/19929), [6d33675](https://github.com/laravel/framework/commit/6d33675691aae86c71454b731ceed847256b9dac), [ec88362](https://github.com/laravel/framework/commit/ec88362ee06ad418db93eb0e19f6d285eed7e701), [c264807](https://github.com/laravel/framework/commit/c2648070eb2108b0f9a4189bfbabea195282b963))

### Routing
- Support fluent resource options ([#18767](https://github.com/laravel/framework/pull/18767), [bb02fb2](https://github.com/laravel/framework/commit/bb02fb27387a8aeb2a47da1fe5ff2e086920b744))
- Support multiple values in `Router::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- ⚠️ Bind empty optional route parameter to `null` instead of empty model instance ([#17521](https://github.com/laravel/framework/pull/17521))
- ⚠️ Removed `Controller::missingMethod()` ([bf5d221](https://github.com/laravel/framework/commit/bf5d221037d9857a74020f2623839e282035a420))
- Accept patterns on `Route::named()`, `Router::is()` and `Router::currentRouteNamed()` ([#19267](https://github.com/laravel/framework/pull/19267), [bfc5321](https://github.com/laravel/framework/commit/bfc53213f67d50444d3db078737990fa14081d1b))
- Added `domain()` setter/getter to `Route` ([#19245](https://github.com/laravel/framework/pull/19245), [bba04a1](https://github.com/laravel/framework/commit/bba04a1598c44a892e918c4f308407b0d297f217))
- Added `Route::redirect()` method ([#19794](https://github.com/laravel/framework/pull/19794))
- Added `Route::view()` method ([#19835](https://github.com/laravel/framework/pull/19835))
- ⚠️ Improved `ThrottleRequests` middleware ([#19807](https://github.com/laravel/framework/pull/19807), [#19860](https://github.com/laravel/framework/pull/19860))
- ⚠️ Return proper 304 responses ([#19867](https://github.com/laravel/framework/pull/19867))

### Responses
- ⚠️ Ensure `Arrayable` and `Jsonable` return a `JsonResponse` ([#17875](https://github.com/laravel/framework/pull/17875))
- ⚠️ Ensure `Arrayable` objects are also morphed by `Response` ([#17868](https://github.com/laravel/framework/pull/17868))
- Added `SameSite` support to `CookieJar` ([#18040](https://github.com/laravel/framework/pull/18040), [#18059](https://github.com/laravel/framework/pull/18059), [e69d722](https://github.com/laravel/framework/commit/e69d72296cfd9969db569b950721461a521100c4))
- Accept `HeaderBag` in `ResponseTrait::withHeaders()` ([#18161](https://github.com/laravel/framework/pull/18161))
- ⚠️ Reset response content-type in `Response::setContent()` ([#18314](https://github.com/laravel/framework/pull/18314))

### Service Container
- ⚠️ Refactored `Container` ([#19201](https://github.com/laravel/framework/pull/19201))
- ⚠️ Made container PSR-11 compliant ([#19822](https://github.com/laravel/framework/pull/19822), [a6068b0](https://github.com/laravel/framework/commit/a6068b06ba42700f25b613a7bc3036be75d5bc43), [66325c2](https://github.com/laravel/framework/commit/66325c2c5768a5b10376e1838288c5212e3c9c40))
- Return the bound instance from `Container::instance()` ([#19442](https://github.com/laravel/framework/pull/19442))

### Session
- ⚠️ Default value to `true` in `Store::flash()` ([#18136](https://github.com/laravel/framework/pull/18136))
- ⚠️ Store the user password hash when logging in ([#19843](https://github.com/laravel/framework/pull/19843))

### Task Scheduling
- Fire before callbacks on closure-based scheduling events ([#18861](https://github.com/laravel/framework/pull/18861))
- Run after-callbacks even if a callback event failed ([#19573](https://github.com/laravel/framework/pull/19573))
- ⚠️ Fixed bug in `quarterly()` method ([#19600](https://github.com/laravel/framework/pull/19600))

### Testing
- ⚠️ Switched to PHPUnit 6 ([#17755](https://github.com/laravel/framework/pull/17755), [#17864](https://github.com/laravel/framework/pull/17864))
- ⚠️ Renamed authentication assertion methods ([#17924](https://github.com/laravel/framework/pull/17924), [494a177](https://github.com/laravel/framework/commit/494a1774f217f0cd6b4efade63e200e3ac65f201))
- ⚠️ Unify database testing traits into `RefreshDatabase` trait ([79c6f67](https://github.com/laravel/framework/commit/79c6f6774eecf77aef8ed5e2f270551a6f378f1d), [0322e32](https://github.com/laravel/framework/commit/0322e3226196a435db436e2a00c035be892c2466))
- ⚠️ Changed Blade tests namespace to `Illuminate\Tests\View\Blade` ([#19675](https://github.com/laravel/framework/pull/19675))
- Added integration tests for the framework itself ([182027d](https://github.com/laravel/framework/commit/182027d3290e9a2e1bd9e2d52c125177ef6c6af6), [#18438](https://github.com/laravel/framework/pull/18438), [#18780](https://github.com/laravel/framework/pull/18780), [#19001](https://github.com/laravel/framework/pull/19001))
- Allow disabling of specific middleware ([#18673](https://github.com/laravel/framework/pull/18673))
- Added `withoutExceptionHandling()` method ([a171f44](https://github.com/laravel/framework/commit/a171f44594c248afe066fee74fad640765b12da0))
- Support inline eloquent factory states ([#19060](https://github.com/laravel/framework/pull/19060))
- Allow `assertSessionHasErrors()` to look into different error bags ([#19172](https://github.com/laravel/framework/pull/19172), [4287ebc](https://github.com/laravel/framework/commit/4287ebc76025cd31e0ba6730481a95aeb471e305))
- Ensure Redis is available in cache lock tests ([#19791](https://github.com/laravel/framework/pull/19791))
- ⚠️ Clear `Carbon` mock during tear down ([#19934](https://github.com/laravel/framework/pull/19934))

### Validation
- Added support for custom validation rule objects ([#19155](https://github.com/laravel/framework/pull/19155), [2aa5ea8](https://github.com/laravel/framework/commit/2aa5ea8a898bd220015ab9be453b36723ffb186e))

### Views
- ⚠️ Camel case variables names passed to views ([#18083](https://github.com/laravel/framework/pull/18083))
- Added pagination template for Semantic UI ([#18463](https://github.com/laravel/framework/pull/18463))
