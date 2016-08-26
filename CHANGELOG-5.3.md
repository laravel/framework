# Release Notes for 5.3.x

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
