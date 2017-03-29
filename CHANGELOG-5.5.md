# Release Notes for 5.5.x

## [Unreleased]

### General
- ⚠️ Require PHP 7+ ([06907a0](https://github.com/laravel/framework/pull/17048/commits/06907a055e3d28c219f6b6ab97902f0be3e8a4ef), [39809ce](https://github.com/laravel/framework/pull/17048/commits/39809cea81a5564d196c16a87cbc25de88dd3d1c))
- ⚠️ Removed deprecated `ServiceProvider::compile()` method ([10da428](https://github.com/laravel/framework/pull/17048/commits/10da428eb344191608474f1c12ee7edb0290e80a))
- ⚠️ Removed deprecated `Str::quickRandom()` method ([2ef257a](https://github.com/laravel/framework/pull/17048/commits/2ef257a4197b7e6efeb0d6ac4a3958f82b7fed39))
- Removed `build` scripts ([7c16b15](https://github.com/laravel/framework/pull/17048/commits/7c16b154ede10ff9a37756e32d7dddf317524634))
- Removed usages of the `with()` helper ([#17888](https://github.com/laravel/framework/pull/17888))
- Support callable/invokable objects in `Pipeline` ([#18264](https://github.com/laravel/framework/pull/18264))
- ⚠️ Prevent access to protected properties using array access on `Model` and `Fluent` ([#18403](https://github.com/laravel/framework/pull/18403))
- Show custom 500 error page for all exception types when not in debug mode ([#18481](https://github.com/laravel/framework/pull/18481), [3cb7b0f](https://github.com/laravel/framework/commit/3cb7b0f4304274f209ed0f776ef70ccd4f9fe5dd))

### Console
- Add interactive prompt to `vendor:publish` ([#18230](https://github.com/laravel/framework/pull/18230))

### Eloquent
- ⚠️ Indicate soft deleted models as existing ([#17613](https://github.com/laravel/framework/pull/17613))
- ⚠️ Added `$localKey` parameter to `HasRelationships::belongsToMany()` and `BelongsToMany` ([#17903](https://github.com/laravel/framework/pull/17903), [7c7c3bc](https://github.com/laravel/framework/commit/7c7c3bc4be3052afe0889fe323230dfd92f81000))
- ⚠️ Renamed `$parent` property to `$pivotParent` in `Pivot` class ([#17933](https://github.com/laravel/framework/pull/17933), [#18150](https://github.com/laravel/framework/pull/18150))
- ⚠️ Don't add `_count` suffix to column name when using `withCount()` with an alias ([#17871](https://github.com/laravel/framework/pull/17871))
- ⚠️ Renamed `$events` to `$dispatchesEvents` ([#17961](https://github.com/laravel/framework/pull/17961), [b6472bf](https://github.com/laravel/framework/commit/b6472bf6fec1af6e76604aaf3f7fed665440ac66), [3dbe12f](https://github.com/laravel/framework/commit/3dbe12f16f470e3bca868576d517d57876bc50af))
- ⚠️ Added `$parentKey` parameter to `belongsToMany()`, `BelongsToMany` and `MorphToMany` ([#17915](https://github.com/laravel/framework/pull/17915), [#18380](https://github.com/laravel/framework/pull/18380))

### Events
- ⚠️ Removed calling queue method on handlers ([0360cb1](https://github.com/laravel/framework/commit/0360cb1c6b71ec89d406517b19d1508511e98fb5), [ec96979](https://github.com/laravel/framework/commit/ec969797878f2c731034455af2397110732d14c4), [d9be4bf](https://github.com/laravel/framework/commit/d9be4bfe0367a8e07eed4931bdabf135292abb1b))

### HTTP
- ⚠️ Ensure `Arrayable` and `Jsonable` return a `JsonResponse` ([#17875](https://github.com/laravel/framework/pull/17875))
- ⚠️ Ensure `Arrayable` objects are also morphed by `Response` ([#17868](https://github.com/laravel/framework/pull/17868))
- Added `SameSite` support to `CookieJar` ([#18040](https://github.com/laravel/framework/pull/18040), [#18059](https://github.com/laravel/framework/pull/18059), [e69d722](https://github.com/laravel/framework/commit/e69d72296cfd9969db569b950721461a521100c4))
- Accept `HeaderBag` in `ResponseTrait::withHeaders()` ([#18161](https://github.com/laravel/framework/pull/18161))
- ⚠️ Reset response content-type in `Response::setContent()` ([#18314](https://github.com/laravel/framework/pull/18314))

### Mail
- Allow mailables to be rendered directly to views ([d9a6dfa](https://github.com/laravel/framework/commit/d9a6dfa4f46a10feceb67921b78c60a905b7c28c))
- Allow for per-mailable theme configuration ([b2c35ca](https://github.com/laravel/framework/commit/b2c35ca9eb769d1a4752a64e936defd7f7099043))

### Queue
- ⚠️ Removed redundant `$queue` parameter from `Queue::createPayload()` ([#17948](https://github.com/laravel/framework/pull/17948))

### Redis
- Removed `PhpRedisConnection::proxyToEval()` method ([#17360](https://github.com/laravel/framework/pull/17360))

### Routing
- ⚠️ Bind empty optional route parameter to `null` instead of empty model instance ([#17521](https://github.com/laravel/framework/pull/17521))

### Session
- ⚠️ Default value to `true` in `Store::flash()` ([#18136](https://github.com/laravel/framework/pull/18136))

### Testing
- ⚠️ Switched to PHPUnit 6 ([#17755](https://github.com/laravel/framework/pull/17755), [#17864](https://github.com/laravel/framework/pull/17864))
- ⚠️ Renamed authentication assertion methods ([#17924](https://github.com/laravel/framework/pull/17924), [494a177](https://github.com/laravel/framework/commit/494a1774f217f0cd6b4efade63e200e3ac65f201))
- Added POC of integration testing the framework itself ([182027d](https://github.com/laravel/framework/commit/182027d3290e9a2e1bd9e2d52c125177ef6c6af6), [#18438](https://github.com/laravel/framework/pull/18438))

### Views
- ⚠️ Camel case variables names passed to views ([#18083](https://github.com/laravel/framework/pull/18083))
- Added pagination template for Semantic UI ([#18463](https://github.com/laravel/framework/pull/18463))
