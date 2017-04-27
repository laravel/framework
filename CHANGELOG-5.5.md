# Release Notes for 5.5.x

## [Unreleased]

### General
- ⚠️ Require PHP 7+ ([06907a0](https://github.com/laravel/framework/pull/17048/commits/06907a055e3d28c219f6b6ab97902f0be3e8a4ef), [39809ce](https://github.com/laravel/framework/pull/17048/commits/39809cea81a5564d196c16a87cbc25de88dd3d1c))
- ⚠️ Removed deprecated `ServiceProvider::compile()` method ([10da428](https://github.com/laravel/framework/pull/17048/commits/10da428eb344191608474f1c12ee7edb0290e80a))
- ⚠️ Removed deprecated `Str::quickRandom()` method ([2ef257a](https://github.com/laravel/framework/pull/17048/commits/2ef257a4197b7e6efeb0d6ac4a3958f82b7fed39))
- Removed `build` scripts ([7c16b15](https://github.com/laravel/framework/pull/17048/commits/7c16b154ede10ff9a37756e32d7dddf317524634))
- Support callable/invokable objects in `Pipeline` ([#18264](https://github.com/laravel/framework/pull/18264))
- ⚠️ Prevent access to protected properties using array access on `Model` and `Fluent` ([#18403](https://github.com/laravel/framework/pull/18403))

### Artisan Console
- Added interactive prompt to `vendor:publish` ([#18230](https://github.com/laravel/framework/pull/18230))
- Added `migrate:fresh` command ([f6511d4](https://github.com/laravel/framework/commit/f6511d477f73b3033ef2336257f4cac5f20594a0))
- ⚠️ Added `runningInConsole()` method `Application` contract ([#18658](https://github.com/laravel/framework/pull/18658))
- Support default value(s) on command arguments ([#18572](https://github.com/laravel/framework/pull/18572))
- Improved CLI detection for phpdbg ([#18781](https://github.com/laravel/framework/pull/18781))

### Assets
- Added frontend preset commands ([882f525](https://github.com/laravel/framework/commit/882f525a2c46caddfd94cfa7db9fbaf1abb1284b), [463b769](https://github.com/laravel/framework/commit/463b769270d462468e1b1dcc51a7a1144e003157), [34fd458](https://github.com/laravel/framework/commit/34fd458d370a39335810c1f040fad04af418fed4), [34fd458](https://github.com/laravel/framework/commit/34fd458d370a39335810c1f040fad04af418fed4), [d6c7abe](https://github.com/laravel/framework/commit/d6c7abe5e651cda813831afa5943791334361cd7), [0ed20b0](https://github.com/laravel/framework/commit/0ed20b0bb43335933a17972dce64cc63bbb3cc85), [f7f02c5](https://github.com/laravel/framework/commit/f7f02c5792079ab40f8adf7e14c747b4749406b5), [cf871f4](https://github.com/laravel/framework/commit/cf871f4bf40a75bc1713de7ef8a689477e19c677), [bc5084e](https://github.com/laravel/framework/commit/bc5084efe27a576230f16f447e63b9f4e9b1c5e2))

### Authorization
- Support multiple values in `Gate::has()` ([#18758](https://github.com/laravel/framework/pull/18758))

### Cache
- Don't encrypt database cache values ([f0c72ec](https://github.com/laravel/framework/commit/f0c72ec9bcbdecb7e6267f7ec8f7ecbf8169a388))

### Collections
- Support multiple values in `Collection::has()` ([#18758](https://github.com/laravel/framework/pull/18758))

### Database
- ⚠️ Added `dropAllTables()` to schema builder ([#18484](https://github.com/laravel/framework/pull/18484), [d910bc8](https://github.com/laravel/framework/commit/d910bc8039f3cec2d906797818984e825601a3f5))

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


### Encryption
- Use `openssl_cipher_iv_length()` in `Encrypter` ([#18684](https://github.com/laravel/framework/pull/18684))

### Errors & Logging
- Added default 404 and 500 error pages ([#18483](https://github.com/laravel/framework/pull/18483))
- Always show custom 500 error page for all exception types when not in debug mode ([#18481](https://github.com/laravel/framework/pull/18481), [3cb7b0f](https://github.com/laravel/framework/commit/3cb7b0f4304274f209ed0f776ef70ccd4f9fe5dd))
- Show 419 error page on `TokenMismatchException` ([#18728](https://github.com/laravel/framework/pull/18728))
- Support `render()` method on exceptions ([ed51160](https://github.com/laravel/framework/commit/ed51160b97d8c4cf16526a0f8ba57ce7cb131b53), [c8a9413](https://github.com/laravel/framework/commit/c8a9413e2dc3bf00c206742e2bc76a88134cba84))
- Support `report()` method on exceptions ([e77f6f7](https://github.com/laravel/framework/commit/e77f6f76049050fd4abced63ffa768432d8974f2))
- Send exceptions as JSON in debug mode if the request wants JSON ([5225389](https://github.com/laravel/framework/commit/5225389dfdf03d656b862bba59cebf1820e0e8f4), [#18732](https://github.com/laravel/framework/pull/18732), [4fe6091](https://github.com/laravel/framework/commit/4fe6091e9fc94817a70c47a6a1c2098d5a1805f8), [9ab58fd](https://github.com/laravel/framework/commit/9ab58fd1a0543b1c728124db7f70738b04dcf362))
- Added `Handler::context()` method, that by default adds some default context to logs ([23b7d6b](https://github.com/laravel/framework/commit/23b7d6b45c675bcd93e9f1fb9cd33e71779142c6))

### Events
- ⚠️ Removed calling queue method on handlers ([0360cb1](https://github.com/laravel/framework/commit/0360cb1c6b71ec89d406517b19d1508511e98fb5), [ec96979](https://github.com/laravel/framework/commit/ec969797878f2c731034455af2397110732d14c4), [d9be4bf](https://github.com/laravel/framework/commit/d9be4bfe0367a8e07eed4931bdabf135292abb1b))

### Filesystem
- ⚠️ Made `Storage::files()` work like `Storage::allFiles()` ([#18874](https://github.com/laravel/framework/pull/18874))

### Helpers
- Added `throw_if()` and `throw_unless()` helpers ([18bb4df](https://github.com/laravel/framework/commit/18bb4dfc77c7c289e9b40c4096816ebeff1cd843))
- Added `dispatch_now()` helper function ([#18668](https://github.com/laravel/framework/pull/18668), [61f2e7b](https://github.com/laravel/framework/commit/61f2e7b4106f8eb0b79603d9792426f7c6a6d273))
- Handle lower case words better in as `Str::snake()` ([#18764](https://github.com/laravel/framework/pull/18764))
- Removed usages of the `with()` helper ([#17888](https://github.com/laravel/framework/pull/17888))

### Mail
- Allow mailables to be rendered directly to views ([d9a6dfa](https://github.com/laravel/framework/commit/d9a6dfa4f46a10feceb67921b78c60a905b7c28c))
- Allow for per-mailable theme configuration ([b2c35ca](https://github.com/laravel/framework/commit/b2c35ca9eb769d1a4752a64e936defd7f7099043))
- ⚠️ Removed `$data` and `$callback` parameters from `Mailer` and `MailQueue`

### Queues
- Added support for chainable jobs ([81bcb03](https://github.com/laravel/framework/commit/81bcb03b303707cdc94420983b9d72ed558a2b3d), [94c01b1](https://github.com/laravel/framework/commit/94c01b1f37bfbb8e0d5f133b7dd34040b2bdc065), [91f5357](https://github.com/laravel/framework/commit/91f535704d4f6cff5e8393825dbdf46965234fa3))
- ⚠️ Removed redundant `$queue` parameter from `Queue::createPayload()` ([#17948](https://github.com/laravel/framework/pull/17948))
- Made all `getQueue()` methods `public` ([#18501](https://github.com/laravel/framework/pull/18501))

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

### Routing
- Support fluent resource options ([#18767](https://github.com/laravel/framework/pull/18767), [bb02fb2](https://github.com/laravel/framework/commit/bb02fb27387a8aeb2a47da1fe5ff2e086920b744))
- Support multiple values in `Router::has()` ([#18758](https://github.com/laravel/framework/pull/18758))
- ⚠️ Bind empty optional route parameter to `null` instead of empty model instance ([#17521](https://github.com/laravel/framework/pull/17521))

### Responses
- ⚠️ Ensure `Arrayable` and `Jsonable` return a `JsonResponse` ([#17875](https://github.com/laravel/framework/pull/17875))
- ⚠️ Ensure `Arrayable` objects are also morphed by `Response` ([#17868](https://github.com/laravel/framework/pull/17868))
- Added `SameSite` support to `CookieJar` ([#18040](https://github.com/laravel/framework/pull/18040), [#18059](https://github.com/laravel/framework/pull/18059), [e69d722](https://github.com/laravel/framework/commit/e69d72296cfd9969db569b950721461a521100c4))
- Accept `HeaderBag` in `ResponseTrait::withHeaders()` ([#18161](https://github.com/laravel/framework/pull/18161))
- ⚠️ Reset response content-type in `Response::setContent()` ([#18314](https://github.com/laravel/framework/pull/18314))

### Session
- ⚠️ Default value to `true` in `Store::flash()` ([#18136](https://github.com/laravel/framework/pull/18136))

### Task Scheduling
- Fire before callbacks on closure-based scheduling events ([#18861](https://github.com/laravel/framework/pull/18861))

### Testing
- ⚠️ Switched to PHPUnit 6 ([#17755](https://github.com/laravel/framework/pull/17755), [#17864](https://github.com/laravel/framework/pull/17864))
- ⚠️ Renamed authentication assertion methods ([#17924](https://github.com/laravel/framework/pull/17924), [494a177](https://github.com/laravel/framework/commit/494a1774f217f0cd6b4efade63e200e3ac65f201))
- ⚠️ Unify database testing traits into `RefreshDatabase` trait ([79c6f67](https://github.com/laravel/framework/commit/79c6f6774eecf77aef8ed5e2f270551a6f378f1d))
- Added integration tests for the framework itself ([182027d](https://github.com/laravel/framework/commit/182027d3290e9a2e1bd9e2d52c125177ef6c6af6), [#18438](https://github.com/laravel/framework/pull/18438), [#18780](https://github.com/laravel/framework/pull/18780))
- Allow disabling of specific middleware ([#18673](https://github.com/laravel/framework/pull/18673))
- Added `withoutExceptionHandling()` method ([a171f44](https://github.com/laravel/framework/commit/a171f44594c248afe066fee74fad640765b12da0))

### Views
- ⚠️ Camel case variables names passed to views ([#18083](https://github.com/laravel/framework/pull/18083))
- Added pagination template for Semantic UI ([#18463](https://github.com/laravel/framework/pull/18463))
