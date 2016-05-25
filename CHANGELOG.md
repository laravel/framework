# Release Notes

## v5.2.32 (2016-05-17)

### Added
- Allow user to enable/disable foreign key checks dynamically ([#13333](https://github.com/laravel/framework/pull/13333))
- Added `file` validation rule ([#13371](https://github.com/laravel/framework/pull/13371))
- Added `guestMiddleware()` method to get guest middleware with guard parameter ([#13384](https://github.com/laravel/framework/pull/13384))
- Added `Pivot::fromRawAttributes()` to create a new pivot model from raw values returned from a query ([f356419](https://github.com/laravel/framework/commit/f356419fa6f6b6fbc3322ca587b0bc1e075ba8d2))
- Added `Builder::withCount()` to add a relationship subquery count ([#13414](https://github.com/laravel/framework/pull/13414))
- Support `reply_to` field when using SparkPost ([#13410](https://github.com/laravel/framework/pull/13410))
- Added validation rule for image dimensions ([#13428](https://github.com/laravel/framework/pull/13428))
- Added "Generated Columns" support to MySQL grammar ([#13430](https://github.com/laravel/framework/pull/13430))
- Added `Response::throwResponse()` ([#13473](https://github.com/laravel/framework/pull/13473))
- Added `page` parameter to the `simplePaginate()` method ([#13502](https://github.com/laravel/framework/pull/13502))
- Added `whereColumn()` method to Query Builder ([#13549](https://github.com/laravel/framework/pull/13549))
- Allow `File::allFiles()` to show hidden dot files ([#13555](https://github.com/laravel/framework/pull/13555))

### Changed
- Return `null` instead of `0` for a default `BelongsTo` key ([#13378](https://github.com/laravel/framework/pull/13378))
- Avoid useless logical operation ([#13397](https://github.com/laravel/framework/pull/13397))
- Stop using `{!! !!}` for `csrf_field()` ([#13398](https://github.com/laravel/framework/pull/13398))
- Improvements for `SessionGuard` methods `loginUsingId()` and `onceUsingId()` ([#13393](https://github.com/laravel/framework/pull/13393))
- Added Work-around due to lack of `lastInsertId()` for ODBC for MSSQL ([#13423](https://github.com/laravel/framework/pull/13423))
- Ensure `MigrationCreator::create()` receives `$create` as boolean ([#13439](https://github.com/laravel/framework/pull/13439))
- Allow custom validators to be called with out function name ([#13444](https://github.com/laravel/framework/pull/13444))
- Moved the `payload` column of jobs table to the end ([#13469](https://github.com/laravel/framework/pull/13469))
- Stabilized table aliases for self joins by adding count ([#13401](https://github.com/laravel/framework/pull/13401))
- Account for `__isset` changes in PHP 7 ([#13509](https://github.com/laravel/framework/pull/13509))
- Bring back support for `Carbon` instances to `before` and `after` validators ([#13494](https://github.com/laravel/framework/pull/13494))
- Allow method chaining for `MakesHttpRequest` trait ([#13529](https://github.com/laravel/framework/pull/13529))
- Allow `Request::intersect()` to accept argument list ([#13515](https://github.com/laravel/framework/pull/13515))

### Fixed
- Accept != and <> as operators while value is null ([#13370](https://github.com/laravel/framework/pull/13370))
- Fixed SparkPost BCC issue ([#13361](https://github.com/laravel/framework/pull/13361))
- Fixed fatal error with optional `morphTo` relationship ([#13360](https://github.com/laravel/framework/pull/13360))
- Fixed using `onlyTrashed()` and `withTrashed()` with `whereHas()` ([#13396](https://github.com/laravel/framework/pull/13396))
- Fixed automatic scope nesting ([#13413](https://github.com/laravel/framework/pull/13413))
- Fixed scheduler issue when using `user()` and `withoutOverlapping()` combined ([#13412](https://github.com/laravel/framework/pull/13412))
- Fixed SqlServer grammar issue when table name is equal to a reserved keyword ([#13458](https://github.com/laravel/framework/pull/13458))
- Fixed replacing route default parameters ([#13514](https://github.com/laravel/framework/pull/13514))
- Fixed missing model attribute on `ModelNotFoundException` ([#13537](https://github.com/laravel/framework/pull/13537))
- Decrement transaction count when `beginTransaction()` errors ([#13551](https://github.com/laravel/framework/pull/13551))
- Fixed `seeJson()` issue when comparing two equal arrays ([#13531](https://github.com/laravel/framework/pull/13531))
- Fixed a Scheduler issue where would no longer run in background ([#12628](https://github.com/laravel/framework/issues/12628))
- Fixed sending attachments with SparkPost ([#13577](https://github.com/laravel/framework/pull/13577))


## v5.2.31 (2016-04-27)

### Added
- Added missing suggested dependency `SuperClosure` ([09a793f](https://git.io/vwZx4))
- Added ODBC connection support for SQL Server ([#13298](https://github.com/laravel/framework/pull/13298))
- Added `Request::hasHeader()` method ([#13271](https://github.com/laravel/framework/pull/13271))
- Added `@elsecan` and `@elsecannot` Blade directives ([#13256](https://github.com/laravel/framework/pull/13256))
- Support booleans in `required_if` Validator rule ([#13327](https://github.com/laravel/framework/pull/13327))

### Changed
- Simplified `Translator::parseLocale()` method ([#13244](https://github.com/laravel/framework/pull/13244))
- Simplified `Builder::shouldRunExistsQuery()` method ([#13321](https://github.com/laravel/framework/pull/13321))
- Use `Gate` contract instead of Facade ([#13260](https://github.com/laravel/framework/pull/13260))
- Return result in `SoftDeletes::forceDelete()` ([#13272](https://github.com/laravel/framework/pull/13272))

### Fixed
- Fixed BCC for SparkPost ([#13237](https://github.com/laravel/framework/pull/13237))
- Use Carbon for everything time related in `DatabaseTokenRepository` ([#13234](https://github.com/laravel/framework/pull/13234))
- Fixed an issue with `data_set()` affecting the Validator ([#13224](https://github.com/laravel/framework/pull/13224))
- Fixed setting nested namespaces with `app:name` command ([#13208](https://github.com/laravel/framework/pull/13208))
- Decode base64 encoded keys before using it in `PasswordBrokerManager` ([#13270](https://github.com/laravel/framework/pull/13270))
- Prevented race condition in `RateLimiter` ([#13283](https://github.com/laravel/framework/pull/13283))
- Use `DIRECTORY_SEPARATOR` to create path for migrations ([#13254](https://github.com/laravel/framework/pull/13254))
- Fixed adding implicit rules via `sometimes()` method ([#12976](https://github.com/laravel/framework/pull/12976))
- Fixed `Schema::hasTable()` when using PostgreSQL ([#13008](https://github.com/laravel/framework/pull/13008))
- Allow `seeAuthenticatedAs()` to be called with any user object ([#13308](https://github.com/laravel/framework/pull/13308))

### Removed
- Removed unused base64 decoding from `Encrypter` ([#13291](https://github.com/laravel/framework/pull/13291))


## v5.2.30 (2016-04-19)

### Added
- Added messages and custom attributes to the password reset validation ([#12997](https://github.com/laravel/framework/pull/12997))
- Added `Before` and `After` dependent rules array ([#13025](https://github.com/laravel/framework/pull/13025))
- Exposed token methods to user in password broker ([#13054](https://github.com/laravel/framework/pull/13054))
- Added array support on `Cache::has()` ([#13028](https://github.com/laravel/framework/pull/13028))
- Allow objects to be passed as pipes ([#13024](https://github.com/laravel/framework/pull/13024))
- Adding alias for `FailedJobProviderInterface` ([#13088](https://github.com/laravel/framework/pull/13088))
- Allow console commands registering from `Kernel` class ([#13097](https://github.com/laravel/framework/pull/13097))
- Added the ability to get routes keyed by method ([#13146](https://github.com/laravel/framework/pull/13146))
- Added PostgreSQL specific operators for `jsonb` type ([#13161](https://github.com/laravel/framework/pull/13161))
- Added `makeHidden()` method to the Eloquent collection ([#13152](https://github.com/laravel/framework/pull/13152))
- Added `intersect()` method to `Request` ([#13167](https://github.com/laravel/framework/pull/13167))
- Allow disabling of model observers in tests ([#13178](https://github.com/laravel/framework/pull/13178))
- Allow `ON` clauses on cross joins ([#13159](https://github.com/laravel/framework/pull/13159))

### Changed
- Use relation setter when setting relations ([#13001](https://github.com/laravel/framework/pull/13001))
- Use `isEmpty()` to check for empty message bag in `Validator::passes()` ([#13014](https://github.com/laravel/framework/pull/13014))
- Refresh `remember_token` when resetting password ([#13016](https://github.com/laravel/framework/pull/13016))
- Use multibyte string functions in `Str` class ([#12953](https://github.com/laravel/framework/pull/12953))
- Use CloudFlare CDN and use SRI checking for assets ([#13044](https://github.com/laravel/framework/pull/13044))
- Enabling array on method has() ([#13028](https://github.com/laravel/framework/pull/13028))
- Allow unix timestamps to be numeric in `Validator` ([da62677](https://git.io/vVi3M))
- Reverted forcing middleware uniqueness ([#13075](https://github.com/laravel/framework/pull/13075))
- Forget keys that contain periods ([#13121](https://github.com/laravel/framework/pull/13121))
- Don't limit column selection while chunking by id ([#13137](https://github.com/laravel/framework/pull/13137))
- Prefix table name on `getColumnType()` call ([#13136](https://github.com/laravel/framework/pull/13136))
- Moved ability map in `AuthorizesResources` trait to a method ([#13214](https://github.com/laravel/framework/pull/13214))
- Make sure `unguarded()` does not change state on exception ([#13186](https://github.com/laravel/framework/pull/13186))
- Return `$this` in `InteractWithPages::within()` to allow method chaining ([13200](https://github.com/laravel/framework/pull/13200))

### Fixed
- Fixed a empty value case with `Arr:dot()` ([#13009](https://github.com/laravel/framework/pull/13009))
- Fixed a Scheduler issues on Windows ([#13004](https://github.com/laravel/framework/issues/13004))
- Prevent crashes with bad `Accept` headers ([#13039](https://github.com/laravel/framework/pull/13039), [#13059](https://github.com/laravel/framework/pull/13059))
- Fixed explicit depending rules when the explicit keys are non-numeric ([#13058](https://github.com/laravel/framework/pull/13058))
- Fixed an issue with fluent routes with `uses()` ([#13076](https://github.com/laravel/framework/pull/13076))
- Prevent generating listeners for listeners ([3079175](https://git.io/vVNdg))

### Removed
- Removed unused parameter call in `Filesystem::exists()` ([#13102](https://github.com/laravel/framework/pull/13102))
- Removed duplicate "[y/N]" from confirmable console commands ([#13203](https://github.com/laravel/framework/pull/13203))
- Removed unused parameter in `route()` helper ([#13206](https://github.com/laravel/framework/pull/13206))


## v5.2.29 (2016-04-02)

### Fixed
- Fixed `Arr::get()` when given array is empty ([#12975](https://github.com/laravel/framework/pull/12975))
- Add backticks around JSON selector field names in PostgreSQL query builder ([#12978](https://github.com/laravel/framework/pull/12978))
- Reverted #12899 ([#12991](https://github.com/laravel/framework/pull/12991))


## v5.2.28 (2016-04-01)

### Added
- Added `Authorize` middleware ([#12913](https://git.io/vVLel), [0c48ba4](https://git.io/vVlib), [183f8e1](https://git.io/vVliF))
- Added `UploadedFile::clientExtension()` ([75a7c01](https://git.io/vVO7I))
- Added cross join support for query builder ([#12950](https://git.io/vVZqP))
- Added `ThrottlesLogins::secondsRemainingOnLockout()` ([#12963](https://git.io/vVc1Z), [7c2c098](https://git.io/vVli9))

### Changed
- Optimized validation performance of large arrays ([#12651](https://git.io/v2xhi))
- Never retry database query, if failed within transaction ([#12929](https://git.io/vVYUB))
- Allow customization of email sent by `ResetsPasswords::sendResetLinkEmail()` ([#12935](https://git.io/vVYKE), [aae873e](https://git.io/vVliD))
- Improved file system tests ([#12940](https://git.io/vVsTV), [#12949](https://git.io/vVGjP), [#12970](https://git.io/vVCBq))
- Allowing merging an array of rules ([a5ea1aa](https://git.io/vVli1))
- Consider implicit attributes while guessing column names in validator ([#12961](https://git.io/vVcgA), [a3827cf](https://git.io/vVliX))
- Reverted [#12307](https://git.io/vgQeJ) ([#12928](https://git.io/vVqni))

### Fixed
- Fixed elixir manifest caching to detect different build paths ([#12920](https://git.io/vVtJR))
- Fixed `Str::snake()` to work with UTF-8 strings ([#12923](https://git.io/vVtVp))
- Trim the input name in the generator commands ([#12933](https://git.io/vVY4a))
- Check for non-string values in validation rules ([#12973](https://git.io/vVWew))
- Add backticks around JSON selector field names in MySQL query builder ([#12964](https://git.io/vVc9n))
- Fixed terminable middleware assigned to controller ([#12899](https://git.io/vVTnt), [74b0636](https://git.io/vVliP))


## v5.2.27 (2016-03-29)
### Added
- Allow ignoring an id using an array key in the `unique` validation rule ([#12612](https://git.io/v29rH))
- Added `InteractsWithSession::assertSessionMissing()` ([#12860](https://git.io/vajXr))
- Added `chunkById()` method to query builder for faster chunking of large sets of data ([#12861](https://git.io/vajSd))
- Added Blade `@hasSection` directive to determine whether something can be yielded ([#12866](https://git.io/vVem5))
- Allow optional query builder calls via `when()` method ([#12878](https://git.io/vVflh))
- Added IP and MAC address column types ([#12884](https://git.io/vVJsj))
- Added Collections `union` method for true unions of two collections ([#12910](https://git.io/vVIzh))

### Changed
- Allow array size validation of implicit attributes ([#12640](https://git.io/v2Nzl))
- Separated logic of Blade `@push` and `@section` directives ([#12808](https://git.io/vaD8n))
- Ensured that middleware is applied only once ([#12911](https://git.io/vVIr2))

### Fixed
- Reverted improvements to Redis cache tagging ([#12897](https://git.io/vVUD5))
- Removed route group from `make:auth` stub ([#12903](https://git.io/vVkHI))


## v5.2.26 (2016-03-25)
### Added
- Added support for Base64 encoded `Encrypter` keys ([370ae34](https://git.io/vapFX))
- Added `EncryptionServiceProvider::getEncrypterForKeyAndCipher()` ([17ce4ed](https://git.io/vahbo))
- Added `Application::environmentFilePath()` ([370ae34](https://git.io/vapFX))

### Fixed
- Fixed mock in `ValidationValidatorTest::testValidateMimetypes()` ([7f35988](https://git.io/vaxfB))


## v5.2.25 (2016-03-24)
### Added
- Added bootstrap Composer scripts to avoid loading of config/compiled files ([#12827](https://git.io/va5ja))

### Changed
- Use `File::guessExtension()` instead of `UploadedFile::guessClientExtension()` ([87e6175](https://git.io/vaAxC))

### Fixed
- Fix an issue with explicit custom validation attributes ([#12822](https://git.io/vaQbD))
- Fix an issue where a view would run the `BladeEngine` instead of the `PhpEngine` ([#12830](https://git.io/vad1X))
- Prevent wrong auth driver from causing unexpected end of execution ([#12821](https://git.io/vajFq))
