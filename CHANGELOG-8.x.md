# Release Notes for 8.x

## [Unreleased](https://github.com/laravel/framework/compare/v8.0.3...8.x)


## [v8.0.3 (2020-09-10)](https://github.com/laravel/framework/compare/v8.0.2...v8.0.3)

### Added
- Added links property to JSON pagination responses ([13751a1](https://github.com/laravel/framework/commit/13751a187834fabe515c14fb3ac1dc008fd23f37))

### Fixed
- Fixed bugs with factory creation in `FactoryMakeCommand` ([c7186e0](https://github.com/laravel/framework/commit/c7186e09204cb3ed72ab24fe9f25a6450c2512bb))


## [v8.0.2 (2020-09-09)](https://github.com/laravel/framework/compare/v8.0.1...v8.0.2)

### Revert
- Revert of ["Fixed for empty fallback_locale in `Illuminate\Translation\Translator`"](https://github.com/laravel/framework/pull/34136) ([7c54eb6](https://github.com/laravel/framework/commit/7c54eb678d58fb9ee7f532a5a5842e6f0e1fe4c9))

### Changed
- Update `Illuminate\Database\Schema\MySqlSchemaState::executeDumpProcess()` ([#34233](https://github.com/laravel/framework/pull/34233))


## [v8.0.1 (2020-09-09)](https://github.com/laravel/framework/compare/v8.0.0...v8.0.1)

### Added
- Support array syntax in `Illuminate\Routing\Route::uses()` ([f80ba11](https://github.com/laravel/framework/commit/f80ba11b698b6130bdbc7ffdcb947519deabbdba))

### Fixed
- Fixed `BatchRepositoryFake` TypeError ([#34225](https://github.com/laravel/framework/pull/34225))
- Fixed dynamic component bug ([4b1e317](https://github.com/laravel/framework/commit/4b1e317c7aec22c2767766bb8b84e059fe4e0802))
  
### Changed
- Give shadow a rounded edge to match content in `tailwind.blade.php` ([#34198](https://github.com/laravel/framework/pull/34198))
- Pass the request to the renderable callback in `Illuminate\Foundation\Exceptions\Handler::render()` ([#34200](https://github.com/laravel/framework/pull/34200))
- Update `Illuminate\Database\Schema\MySqlSchemaState` ([d67be130](https://github.com/laravel/framework/commit/d67be1305bef418d9bdeb8192177202f9d705699), [c87794f](https://github.com/laravel/framework/commit/c87794fc354941729d1f0c4607693c0b8d2cfda2))
- Respect local env in `Illuminate\Foundation\Console\ServeCommand::startProcess()` ([75e792d](https://github.com/laravel/framework/commit/75e792d61871780f75ecb4eb170826b0ba2f305e))


## [v8.0.0 (2020-09-08)](https://github.com/laravel/framework/compare/v7.27.0...v8.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/8.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/8.x/releases).
