# Release Notes for 5.3.x

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
- Improved handling of failed file uploads during validation ([#15166](https://github.com/laravel/framework/pull/15166))
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
