# Release Notes for 8.x

## [Unreleased](https://github.com/laravel/framework/compare/v8.3.0...8.x)


## [v8.3.0 (2020-09-15)](https://github.com/laravel/framework/compare/v8.2.0...v8.3.0)

### Added
- Added `Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase::castAsJson()` ([#34302](https://github.com/laravel/framework/pull/34302))
- Handle array hosts in `Illuminate\Database\Schema\MySqlSchemaState` ([0920c23](https://github.com/laravel/framework/commit/0920c23efb9d7042d074729f2f70acbfec629c14))
- Added `Illuminate\Pipeline\Pipeline::setContainer()` ([#34343](https://github.com/laravel/framework/pull/34343))
- Allow including a closure in a queued batch ([#34333](https://github.com/laravel/framework/pull/34333))

### Fixed
- Fixed broken Seeder ([9e4a866](https://github.com/laravel/framework/commit/9e4a866cfb0420f4ea6cb4e86b1fbd97a4b8c264))

### Changed
- Bumped minimum vlucas/phpdotenv version ([#34336](https://github.com/laravel/framework/pull/34336))
- Pass an instance of the job to queued closures ([#34350](https://github.com/laravel/framework/pull/34350))


## [v8.2.0 (2020-09-14)](https://github.com/laravel/framework/compare/v8.1.0...v8.2.0)

### Added
- Added `Illuminate\Database\Eloquent\Factories\HasFactory::newFactory()` ([4a95372](https://github.com/laravel/framework/commit/4a953728f5e085342d793372329ae534e5885724), [a2cea84](https://github.com/laravel/framework/commit/a2cea84805f311be612fc36c403fcc6f90181ff4))

### Fixed
- Do not used `now` helper in `Illuminate/Cache/DatabaseLock::expiresAt()` ([#34262](https://github.com/laravel/framework/pull/34262))
- Change placeholder in `Illuminate\Database\Schema\MySqlSchemaState::load()` ([#34303](https://github.com/laravel/framework/pull/34303))
- Fixed bug in dynamic attributes `Illuminate\View\ComponentAttributeBag::setAttributes()` ([93f4613](https://github.com/laravel/framework/commit/93f461344051e8d44c4a50748b7bdc0eae18bcac))
- Fixed `Illuminate\View\ComponentAttributeBag::whereDoesntStartWith()` ([#34329](https://github.com/laravel/framework/pull/34329))
- Fixed `Illuminate\Routing\Middleware\ThrottleRequests::handleRequestUsingNamedLimiter()` ([#34325](https://github.com/laravel/framework/pull/34325))

### Changed
- Create Faker when a Factory is created ([#34298](https://github.com/laravel/framework/pull/34298))


## [v8.1.0 (2020-09-11)](https://github.com/laravel/framework/compare/v8.0.4...v8.1.0)

### Added
- Added `Illuminate\Database\Eloquent\Factories\Factory::raw()` ([#34278](https://github.com/laravel/framework/pull/34278))
- Added `Illuminate\Database\Eloquent\Factories\Factory::createMany()` ([#34285](https://github.com/laravel/framework/pull/34285), [69072c7](https://github.com/laravel/framework/commit/69072c7d3efd2784d195cb95e45e4dcb8ef5907f))
- Added the `Countable` interface to `AssertableJsonString` ([#34284](https://github.com/laravel/framework/pull/34284))

### Fixed
- Fixed the new maintenance mode ([#34264](https://github.com/laravel/framework/pull/34264))

### Changed
- Optimize command can also cache view ([#34287](https://github.com/laravel/framework/pull/34287))


## [v8.0.4 (2020-09-11)](https://github.com/laravel/framework/compare/v8.0.3...v8.0.4)

### Changed
- Allow `Illuminate\Collections\Collection::implode()` when instance of `Stringable` ([#34271](https://github.com/laravel/framework/pull/34271))

### Fixed
- Fixed `DatabaseUuidFailedJobProvider::find()` job record structure ([#34251](https://github.com/laravel/framework/pull/34251))
- Cast linkCollection to array in JSON pagination responses ([#34245](https://github.com/laravel/framework/pull/34245))
- Change the placeholder of schema dump according to symfony placeholder in `MySqlSchemaState::dump()` ([#34261](https://github.com/laravel/framework/pull/34261))
- Fixed problems with dots in validator ([8723739](https://github.com/laravel/framework/commit/8723739746a53442a5ec5bdebe649f8a4d9dd3c2))


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
