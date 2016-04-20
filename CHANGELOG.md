# Release Notes

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
- Added PostgreSQL specific operators for `jsonb` type ([13161](https://github.com/laravel/framework/pull/13161))
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
- Enabling array on method has() ([]())
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
