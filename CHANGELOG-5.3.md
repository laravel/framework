# Release Notes for 5.3.x

## v5.3.10 (2016-09-20)

### Added
- Fire `Registered` event when a user registers ([#15401](https://github.com/laravel/framework/pull/15401))
- Added `Container::factory()` method  ([#15415](https://github.com/laravel/framework/pull/15415))
- Added `$default` parameter to query/eloquent builder `when()` method ([#15428](https://github.com/laravel/framework/pull/15428), [#15442](https://github.com/laravel/framework/pull/15442))
- Added missing `$notifiable` parameter to `ResetPassword::toMail()` ([#15448](https://github.com/laravel/framework/pull/15448))

### Changed
- Updated `ServiceProvider` to use `resourcePath()` over `basePath()` ([#15400](https://github.com/laravel/framework/pull/15400))
- Throw `RuntimeException` if `pcntl_fork()` doesn't exists ([#15393](https://github.com/laravel/framework/pull/15393))
- Changed visibility of `Container::getAlias()` to public ([#15444](https://github.com/laravel/framework/pull/15444))
- Changed visibility of `VendorPublishCommand::publishTag()` to protected ([#15461](https://github.com/laravel/framework/pull/15461))
- Changed visibility of `TestCase::afterApplicationCreated()` to public ([#15493](https://github.com/laravel/framework/pull/15493))
- Prevent calling `Model` methods when calling them as attributes ([#15438](https://github.com/laravel/framework/pull/15438))
- Default `$callback` to `null` in eloquent builder `whereHas()` ([#15475](https://github.com/laravel/framework/pull/15475))
- Support newlines in Blade's `@foreach` ([#15485](https://github.com/laravel/framework/pull/15485))

### Fixed
- Reverted "Allow passing a `Closure` to `View::share()` [#15312](https://github.com/laravel/framework/pull/15312)" ([#15312](https://github.com/laravel/framework/pull/15312))
- Resolve issues with multi-value select elements ([#15436](https://github.com/laravel/framework/pull/15436))
- Fixed issue with `X-HTTP-METHOD-OVERRIDE` spoofing in `Request` ([#15410](https://github.com/laravel/framework/pull/15410))

### Removed
- Removed unused `SendsPasswordResetEmails::resetNotifier()` method ([#15446](https://github.com/laravel/framework/pull/15446))
- Removed uninstantiable `Seeder` class ([#15450](https://github.com/laravel/framework/pull/15450))
- Removed unnecessary variable in `AuthenticatesUsers::login()` ([#15507](https://github.com/laravel/framework/pull/15507))


## v5.3.9 (2016-09-12)

### Changed
- Optimized performance of `Str::startsWith()` and `Str::endsWith()` ([#15380](https://github.com/laravel/framework/pull/15380), [#15397](https://github.com/laravel/framework/pull/15397))

### Fixed
- Fixed queue job without `--tries` option marks jobs failed ([#15370](https://github.com/laravel/framework/pull/15370), [#15390](https://github.com/laravel/framework/pull/15390))


## v5.3.8 (2016-09-09)

### Added
- Added missing `MailableMailer::later()` method ([#15364](https://github.com/laravel/framework/pull/15364))
- Added missing `$queue` parameter on `SyncJob` ([#15368](https://github.com/laravel/framework/pull/15368))
- Added SSL options for PostgreSQL DSN ([#15371](https://github.com/laravel/framework/pull/15371))
- Added ability to disable touching of parent when toggling relation ([#15263](https://github.com/laravel/framework/pull/15263))
- Added username, icon and channel options for Slack Notifications ([#14910](https://github.com/laravel/framework/pull/14910))

### Changed
- Renamed methods in `NotificationFake` ([69b08f6](https://github.com/laravel/framework/commit/69b08f66fbe70b4df8332a8f2a7557a49fd8c693))
- Minor code improvements ([#15369](https://github.com/laravel/framework/pull/15369))

### Fixed
- Fixed catchable fatal error introduced [#15250](https://github.com/laravel/framework/pull/15250) ([#15350](https://github.com/laravel/framework/pull/15350))


## v5.3.7 (2016-09-08)

### Added
- Added missing translation for `mimetypes` validation ([#15209](https://github.com/laravel/framework/pull/15209), [#3921](https://github.com/laravel/laravel/pull/3921))
- Added ability to check if between two times when using scheduler ([#15216](https://github.com/laravel/framework/pull/15216), [#15306](https://github.com/laravel/framework/pull/15306))
- Added `X-RateLimit-Reset` header to throttled responses ([#15275](https://github.com/laravel/framework/pull/15275))
- Support aliases on `withCount()` ([#15279](https://github.com/laravel/framework/pull/15279))
- Added `Filesystem::isReadable()` ([#15289](https://github.com/laravel/framework/pull/15289))
- Added `Collection::split()` method ([#15302](https://github.com/laravel/framework/pull/15302))
- Allow passing a `Closure` to `View::share()` ([#15312](https://github.com/laravel/framework/pull/15312))
- Added support for `Mailable` messages in `MailChannel` ([#15318](https://github.com/laravel/framework/pull/15318))
- Added `with*()` syntax to `Mailable` class ([#15316](https://github.com/laravel/framework/pull/15316))
- Added `--path` option for `migrate:rollback/refresh/reset` ([#15251](https://github.com/laravel/framework/pull/15251))
- Allow numeric keys on `morphMap()` ([#15332](https://github.com/laravel/framework/pull/15332))
- Added fakes for bus, events, mail, queue and notifications ([5deab59](https://github.com/laravel/framework/commit/5deab59e89b85e09b2bd1642e4efe55e933805ca))

### Changed
- Update `Model::save()` to return `true` when no error occurs ([#15236](https://github.com/laravel/framework/pull/15236))
- Optimized performance of `Arr::first()` ([#15213](https://github.com/laravel/framework/pull/15213))
- Swapped `drop()` for `dropIfExists()` in all stubs ([#15230](https://github.com/laravel/framework/pull/15230))
- Allow passing object instance to `class_uses_recursive()` ([#15223](https://github.com/laravel/framework/pull/15223))
- Improved handling of failed file uploads during validation ([#15166](https://github.com/laravel/framework/pull/15166))
- Hide pagination if it does not have multiple pages ([#15246](https://github.com/laravel/framework/pull/15246))
- Cast Pusher message to JSON in `validAuthentiactoinResponse()` ([#15262](https://github.com/laravel/framework/pull/15262))
- Throw exception if queue failed to create payload ([#15284](https://github.com/laravel/framework/pull/15284))
- Call `getUrl()` first in `FilesystemAdapter::url()` ([#15291](https://github.com/laravel/framework/pull/15291))
- Consider local key in `HasManyThrough` relationships ([#15303](https://github.com/laravel/framework/pull/15303))
- Fail faster by checking Route Validators in likely fail order ([#15287](https://github.com/laravel/framework/pull/15287))
- Make the `FilesystemAdapter::delete()` behave like `FileSystem::delete()` ([#15308](https://github.com/laravel/framework/pull/15308))
- Don't call `floor()` in `Collection::median()` ([#15343](https://github.com/laravel/framework/pull/15343))
- Always return number from aggregate method `sum()` ([#15345](https://github.com/laravel/framework/pull/15345))

### Fixed
- Reverted "Hide empty paginators" [#15125](https://github.com/laravel/framework/pull/15125) ([#15241](https://github.com/laravel/framework/pull/15241))
- Fixed empty `multifile` uploads ([#15250](https://github.com/laravel/framework/pull/15250))
- Fixed regression in `save(touch)` option ([#15264](https://github.com/laravel/framework/pull/15264))
- Fixed lower case model names in policy classes ([15270](https://github.com/laravel/framework/pull/15270))
- Allow models with global scopes to be refreshed ([#15282](https://github.com/laravel/framework/pull/15282))
- Fix `ChannelManager::getDefaultDriver()` implementation ([#15288](https://github.com/laravel/framework/pull/15288))
- Fire `illuminate.queue.looping` event before running daemon ([#15290](https://github.com/laravel/framework/pull/15290))
- Check attempts before firing queue job ([#15319](https://github.com/laravel/framework/pull/15319))
- Fixed `morphTo()` naming inconsistency ([#15334](https://github.com/laravel/framework/pull/15334))


## v5.3.6 (2016-09-01)

### Added
- Added `required` attributes to auth scaffold ([#15087](https://github.com/laravel/framework/pull/15087))
- Support custom recipient(s) in `MailMessage` notifications ([#15100](https://github.com/laravel/framework/pull/15100))
- Support custom greeting in `SimpleMessage` notifications ([#15108](https://github.com/laravel/framework/pull/15108))
- Added `prependLocation()` method to `FileViewFinder` ([#15103](https://github.com/laravel/framework/pull/15103))
- Added fluent email priority setter ([#15178](https://github.com/laravel/framework/pull/15178))
- Added `send()` and `sendNow()` to notification factory contract ([0066b5d](https://github.com/laravel/framework/commit/0066b5da6f009275348ab71904da2376c6c47281))

### Changed
- Defer resolving of PDO connection until needed ([#15031](https://github.com/laravel/framework/pull/15031))
- Send plain text email along with HTML email notifications ([#15016](https://github.com/laravel/framework/pull/15016), [#15092](https://github.com/laravel/framework/pull/15092), [#15115](https://github.com/laravel/framework/pull/15115))
- Stop further validation if a `required` rule fails ([#15089](https://github.com/laravel/framework/pull/15089))
- Swaps `drop()` for `dropIfExists()` in migration stub ([#15113](https://github.com/laravel/framework/pull/15113))
- The `resource_path()` helper now relies on `Application::resourcePath()` ([#15095](https://github.com/laravel/framework/pull/15095))
- Optimized performance of `Str::random()` ([#15112](https://github.com/laravel/framework/pull/15112))
- Show `app.name` in auth stub ([#15138](https://github.com/laravel/framework/pull/15138))
- Switched from `htmlentities()` to `htmlspecialchars()` in `e()` helper ([#15159](https://github.com/laravel/framework/pull/15159))
- Hide empty paginators ([#15125](https://github.com/laravel/framework/pull/15125))

### Fixed
- Fixed `migrate:rollback` with `FETCH_ASSOC` enabled ([#15088](https://github.com/laravel/framework/pull/15088))
- Fixes query builder not considering raw expressions in `whereIn()` ([#15078](https://github.com/laravel/framework/pull/15078))
- Fixed notifications serialization mistake in `ChannelManager` ([#15106](https://github.com/laravel/framework/pull/15106))
- Fixed session id collisions ([#15206](https://github.com/laravel/framework/pull/15206))
- Fixed extending cache expiration time issue in `file` cache ([#15164](https://github.com/laravel/framework/pull/15164))

### Removed
- Removed data transformation in `Response::json()` ([#15137](https://github.com/laravel/framework/pull/15137))


## v5.3.4 (2016-08-26)

### Added
- Added ability to set from address for email notifications ([#15055](https://github.com/laravel/framework/pull/15055))

### Changed
- Support implicit keys in `MessageBag::get()` ([#15063](https://github.com/laravel/framework/pull/15063))
- Allow passing of closures to `assertViewHas()` ([#15074](https://github.com/laravel/framework/pull/15074))
- Strip protocol from Route group domains parameters ([#15070](https://github.com/laravel/framework/pull/15070))
- Support dot notation as callback in `Arr::sort()` ([#15050](https://github.com/laravel/framework/pull/15050))
- Use Redis database interface instead of implementation ([#15041](https://github.com/laravel/framework/pull/15041))
- Allow closure middleware to be registered from the controller constructor ([#15080](https://github.com/laravel/framework/pull/15080), [abd85c9](https://github.com/laravel/framework/commit/abd85c916df0cc0a6dc55de943a39db8b7eb4e0d))

### Fixed
- Fixed plural form of Emoji ([#15068](https://github.com/laravel/framework/pull/15068))


## v5.3.3 (2016-08-26)

### Fixed
- Fixed testing of Eloquent model events ([#15052](https://github.com/laravel/framework/pull/15052))


## v5.3.2 (2016-08-24)

### Fixed
- Revert changes to Eloquent `Builder` that breaks `firstOr*` methods ([#15018](https://github.com/laravel/framework/pull/15018))


## v5.3.1 (2016-08-24)

### Changed
- Support unversioned assets in `elixir()` function ([#14987](https://github.com/laravel/framework/pull/14987))
- Changed visibility of `BladeCompiler::stripParentheses()` to `public` ([#14986](https://github.com/laravel/framework/pull/14986))
- Use getter instead of accessing the properties directly in `JoinClause::__construct()` ([#14984](https://github.com/laravel/framework/pull/14984))
- Replaced manual comparator with `asort` in `Collection::sort()` ([#14980](https://github.com/laravel/framework/pull/14980))
- Use `query()` instead of `input()` for key lookup in `TokenGuard::getTokenForRequest()` ([#14985](https://github.com/laravel/framework/pull/14985))

### Fixed
- Check if exact key exists before assuming the dot notation represents segments in `Arr::has()` ([#14976](https://github.com/laravel/framework/pull/14976))
- Revert aggregate changes in [#14793](https://github.com/laravel/framework/pull/14793) ([#14994](https://github.com/laravel/framework/pull/14994))
- Prevent infinite recursion with closure based console commands ([26eaa35](https://github.com/laravel/framework/commit/26eaa35c0dbd988084e748410a31c8b01fc1993a))
- Fixed `transaction()` method for SqlServer ([f4588f8](https://github.com/laravel/framework/commit/f4588f8851aab1129f77d87b7dc1097c842390db))
