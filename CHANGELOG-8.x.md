# Release Notes for 8.x

## [Unreleased](https://github.com/laravel/framework/compare/v8.14.0...8.x)


## [v8.14.0 (2020-11-10)](https://github.com/laravel/framework/compare/v8.13.0...v8.14.0)

### Added
- Added ability to dispatch unique jobs ([#35042](https://github.com/laravel/framework/pull/35042), [2123e60](https://github.com/laravel/framework/commit/2123e603af027e7590974864715c028357ea4969))
- Added `Model::encryptUsing()` ([#35080](https://github.com/laravel/framework/pull/35080))
- Added support to MySQL dump and import using socket ([#35083](https://github.com/laravel/framework/pull/35083), [c43054b](https://github.com/laravel/framework/commit/c43054b9decad4f66937c229e4ef0f32760c8611))
- Allow custom broadcastWith in notification broadcast channel ([#35142](https://github.com/laravel/framework/pull/35142))
- Added `Illuminate\Routing\CreatesRegularExpressionRouteConstraints::whereAlphaNumeric()` ([#35154](https://github.com/laravel/framework/pull/35154))

### Fixed
- Fixed typo in `make:seeder` command name inside ModelMakeCommand ([#35107](https://github.com/laravel/framework/pull/35107))
- Fix SQL Server grammar for upsert (missing semicolon) ([#35112](https://github.com/laravel/framework/pull/35112))
- Respect migration table name in config when dumping schema ([110eb15](https://github.com/laravel/framework/commit/110eb15a77f84da0d83ebc2bb123eec08ecc19ca))
- Respect them when previewing notification ([ed4411d](https://github.com/laravel/framework/commit/ed4411d310f259f75e95e882b748ba9d76d7cfad))
- Fix appendable attributes in Blade components ([#35131](https://github.com/laravel/framework/pull/35131)) 
- Remove decrypting array cookies from cookie decrypting ([#35130](https://github.com/laravel/framework/pull/35130)) 
- Turn the eloquent collection into a base collection if mapWithKeys loses models ([#35129](https://github.com/laravel/framework/pull/35129))

### Changed
- Move dispatching of DatabaseRefreshed event to fire before seeders are run ([#35091](https://github.com/laravel/framework/pull/35091))
- Handle returning false from reportable callback ([55f0b5e](https://github.com/laravel/framework/commit/55f0b5e7449b87b7340a761bf9e6456fdc8ffc4d))
- Update `Illuminate\Database\Schema\Grammars\MySqlGrammar::typeTimestamp()` ([#35143](https://github.com/laravel/framework/pull/35143))
- Remove expectedTables after converting to expectedOutput in PendingCommand ([#35163](https://github.com/laravel/framework/pull/35163)) 
- Change SQLite schema command environment variables to work on Windows ([#35164](https://github.com/laravel/framework/pull/35164))
  
  
## [v8.13.0 (2020-11-03)](https://github.com/laravel/framework/compare/v8.12.3...v8.13.0)

### Added
- Added `loadMax()` | `loadMin()` | `loadSum()` | `loadAvg()` methods to `Illuminate\Database\Eloquent\Collection`. Added `loadMax()` | `loadMin()` | `loadSum()` | `loadAvg()` | `loadMorphMax()` | `loadMorphMin()` | `loadMorphSum()` | `loadMorphAvg()` methods to `Illuminate\Database\Eloquent\Model` ([#35029](https://github.com/laravel/framework/pull/35029))
- Modify `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::has()` method to support MorphTo relations ([#35050](https://github.com/laravel/framework/pull/35050))
- Added `Illuminate\Support\Stringable::chunk()` ([#35038](https://github.com/laravel/framework/pull/35038))

### Fixed
- Fixed a few issues in `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::withAggregate()` ([#35061](https://github.com/laravel/framework/pull/35061), [#35063](https://github.com/laravel/framework/pull/35063))

### Changed
- Set chain `queue` | `connection` | `delay` only when explicitly configured in ([#35047](https://github.com/laravel/framework/pull/35047))

### Refactoring
- Remove redundant unreachable return statements in some places ([#35053](https://github.com/laravel/framework/pull/35053))


## [v8.12.3 (2020-10-30)](https://github.com/laravel/framework/compare/v8.12.2...v8.12.3)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Concerns\QueriesRelationships::withAggregate()` ([20b0c6e](https://github.com/laravel/framework/commit/20b0c6e19b635466f776502b3f1260c7c51b04ae))


## [v8.12.2 (2020-10-29)](https://github.com/laravel/framework/compare/v8.12.1...v8.12.2)

### Fixed
- [Add some fixes](https://github.com/laravel/framework/compare/v8.12.1...v8.12.2) 


## [v8.12.1 (2020-10-29)](https://github.com/laravel/framework/compare/v8.12.0...v8.12.1)

### Fixed
- Fixed alias usage in `Eloquent` ([6091048](https://github.com/laravel/framework/commit/609104806b8b639710268c75c22f43034c2b72db))
- Fixed `Illuminate\Support\Reflector::isCallable()` ([a90f344](https://github.com/laravel/framework/commit/a90f344c66f0a5bb1d718f8bbd20c257d4de9e02))


## [v8.12.0 (2020-10-29)](https://github.com/laravel/framework/compare/v8.11.2...v8.12.0)

### Added
- Added ability to create observers with custom path via `make:observer` command ([#34911](https://github.com/laravel/framework/pull/34911))
- Added `Illuminate\Database\Eloquent\Factories\Factory::lazy()` ([#34923](https://github.com/laravel/framework/pull/34923))
- Added ability to make cast with custom stub file via `make:cast` command ([#34930](https://github.com/laravel/framework/pull/34930))
- ADDED: Custom casts can implement increment/decrement logic  ([#34964](https://github.com/laravel/framework/pull/34964))
- Added encrypted Eloquent cast ([#34937](https://github.com/laravel/framework/pull/34937), [#34948](https://github.com/laravel/framework/pull/34948))
- Added `DatabaseRefreshed` event to be emitted after database refreshed ([#34952](https://github.com/laravel/framework/pull/34952), [f31bfe2](https://github.com/laravel/framework/commit/f31bfe2fb83829a900f75fccd12af4b69ffb6275))
- Added `withMax()`|`withMin()`|`withSum()`|`withAvg()` methods to `Illuminate/Database/Eloquent/Concerns/QueriesRelationships` ([#34965](https://github.com/laravel/framework/pull/34965), [f4e4d95](https://github.com/laravel/framework/commit/f4e4d95c8d4c2f63f9bd80c2a4cfa6b2c78bab1b), [#35004](https://github.com/laravel/framework/pull/35004))
- Added `explain()` to `Query\Builder` and `Eloquent\Builder` ([#34969](https://github.com/laravel/framework/pull/34969))
- Make `multiple_of` validation rule handle non-integer values ([#34971](https://github.com/laravel/framework/pull/34971))
- Added `setKeysForSelectQuery` method and use it when refreshing model data in Models ([#34974](https://github.com/laravel/framework/pull/34974))
- Full PHP 8.0 Support ([#33388](https://github.com/laravel/framework/pull/33388))
- Added `Illuminate\Support\Reflector::isCallable()` ([#34994](https://github.com/laravel/framework/pull/34994), [8c16891](https://github.com/laravel/framework/commit/8c16891c6e7a4738d63788f4447614056ab5136e), [31917ab](https://github.com/laravel/framework/commit/31917abcfa0db6ec6221bb07fc91b6e768ff5ec8), [11cfa4d](https://github.com/laravel/framework/commit/11cfa4d4c92bf2f023544d58d51b35c5d31dece0), [#34999](https://github.com/laravel/framework/pull/34999))
- Added route regex registration methods ([#34997](https://github.com/laravel/framework/pull/34997), [3d405cc](https://github.com/laravel/framework/commit/3d405cc2eb66bba97433b46abaca52623c64c94b), [c2df0d5](https://github.com/laravel/framework/commit/c2df0d5faddeb7e58d1832c1c1f0f309619969af))
- Added dontRelease option to RateLimited and RateLimitedWithRedis job middleware ([#35010](https://github.com/laravel/framework/pull/35010))

### Fixed
- Fixed check of file path in `Illuminate\Database\Schema\PostgresSchemaState::load()` ([268237f](https://github.com/laravel/framework/commit/268237fcda420e5c26ab2f0fbdb9b8783c276ff8))
- Fixed: `PhpRedis (v5.3.2)` cluster - set default connection context to `null` ([#34935](https://github.com/laravel/framework/pull/34935))
- Fixed Eloquent Model `loadMorph` and `loadMorphCount` methods ([#34972](https://github.com/laravel/framework/pull/34972))
- Fixed ambigious column on many to many with select load ([5007986](https://github.com/laravel/framework/commit/500798623d100a9746b2931ae6191cb756521f05))
- Fixed Postgres Dump ([#35018](https://github.com/laravel/framework/pull/35018))

### Changed
- Changed `make:factory` command ([#34947](https://github.com/laravel/framework/pull/34947), [4f38176](https://github.com/laravel/framework/commit/4f3817654a6376a2f6cd59dc5fb529ebad1d951f))
- Make assertSee, assertSeeText, assertDontSee and assertDontSeeText accept an array ([#34982](https://github.com/laravel/framework/pull/34982), [2b98bcc](https://github.com/laravel/framework/commit/2b98bcca598eb919b2afd61e5fb5cb86aec4c706))


## [v8.11.2 (2020-10-20)](https://github.com/laravel/framework/compare/v8.11.1...v8.11.2)

### Revert
- Revert ["Change loadRoutesFrom to accept $attributes](https://github.com/laravel/framework/pull/34866)" ([#34909](https://github.com/laravel/framework/pull/34909))


## [v8.11.1 (2020-10-20)](https://github.com/laravel/framework/compare/v8.11.0...v8.11.1)

### Fixed
- Fixed `bound()` method ([a7759d7](https://github.com/laravel/framework/commit/a7759d70e15b0be946569b8299ac694c08a35d7e))


## [v8.11.0 (2020-10-20)](https://github.com/laravel/framework/compare/v8.10.0...v8.11.0)

### Added
- Added job middleware to prevent overlapping jobs ([#34794](https://github.com/laravel/framework/pull/34794), [eed05b4](https://github.com/laravel/framework/commit/eed05b41097cfe62766d4086ede8dee97c057c29))
- Bring Rate Limiters to Jobs ([#34829](https://github.com/laravel/framework/pull/34829), [ae00294](https://github.com/laravel/framework/commit/ae00294c418e431372bad0d09ac15d15925247f7))
- Added `multiple_of` custom replacer in validator ([#34858](https://github.com/laravel/framework/pull/34858))
- Preserve eloquent collection type after calling ->fresh() ([#34848](https://github.com/laravel/framework/pull/34848))
- Provisional support for PHP 8.0 for 6.x (Changed some code in 8.x) ([#34884](https://github.com/laravel/framework/pull/34884), [28bb76e](https://github.com/laravel/framework/commit/28bb76efbcfc5fee57307ffa062b67ff709240dc))

### Fixed
- Fixed `fresh()` and `refresh()` on pivots and morph pivots ([#34836](https://github.com/laravel/framework/pull/34836))
- Fixed config `batching` typo ([#34852](https://github.com/laravel/framework/pull/34852))
- Fixed `Illuminate\Queue\Console\RetryBatchCommand` for un-found batch id ([#34878](https://github.com/laravel/framework/pull/34878))

### Changed
- Change `loadRoutesFrom()` to accept group $attributes ([#34866](https://github.com/laravel/framework/pull/34866))


## [v8.10.0 (2020-10-13)](https://github.com/laravel/framework/compare/v8.9.0...v8.10.0)

### Added
- Allow for chains to be added to batches ([#34612](https://github.com/laravel/framework/pull/34612), [7b4a9ec](https://github.com/laravel/framework/commit/7b4a9ec6c58906eb73957015e4c78f73e780e944))
- Added `is()` method to 1-1 relations for model comparison ([#34693](https://github.com/laravel/framework/pull/34693), [7ba2577](https://github.com/laravel/framework/commit/7ba257732d2342175a6ffe7db7a4ca847ca1d353))
- Added `upsert()` to Eloquent and Base Query Builders ([#34698](https://github.com/laravel/framework/pull/34698), [#34712](https://github.com/laravel/framework/pull/34712), [58a0e1b](https://github.com/laravel/framework/commit/58a0e1b7e2bb6df3923883c4fc8cf13b1bce7322))
- Support psql and pg_restore commands in schema load ([#34711](https://github.com/laravel/framework/pull/34711))
- Added `Illuminate\Database\Schema\Builder::dropColumns()` method on the schema class ([#34720](https://github.com/laravel/framework/pull/34720))
- Added `yearlyOn()` method to scheduler ([#34728](https://github.com/laravel/framework/pull/34728))
- Added `restrictOnDelete()` method to ForeignKeyDefinition class ([#34752](https://github.com/laravel/framework/pull/34752))
- Added `newLine()` method to `InteractsWithIO` trait ([#34754](https://github.com/laravel/framework/pull/34754))
- Added `isNotEmpty()` method to HtmlString ([#34774](https://github.com/laravel/framework/pull/34774))
- Added `delay()` to PendingChain ([#34789](https://github.com/laravel/framework/pull/34789))
- Added "multiple_of" validation rule ([#34788](https://github.com/laravel/framework/pull/34788))
- Added custom methods proxy support for jobs `dispatch()` ([#34781](https://github.com/laravel/framework/pull/34781))
- Added `QueryBuilder::clone()` ([#34780](https://github.com/laravel/framework/pull/34780))
- Support bus chain on fake ([a952ac24](https://github.com/laravel/framework/commit/a952ac24f34b832270a2f80cd425c2afe4c61fc1))
- Added missing force flag to `queue:clear` command ([#34809](https://github.com/laravel/framework/pull/34809))
- Added `dropConstrainedForeignId()` to `Blueprint ([#34806](https://github.com/laravel/framework/pull/34806))
- Implement `supportsTags()` on the Cache Repository ([#34820](https://github.com/laravel/framework/pull/34820))
- Added `canAny` to user model ([#34815](https://github.com/laravel/framework/pull/34815))
- Added `when()` and `unless()` methods to MailMessage ([#34814](https://github.com/laravel/framework/pull/34814))

### Fixed
- Fixed collection wrapping in `BelongsToManyRelationship` ([9245807](https://github.com/laravel/framework/commit/9245807f8a1132a30ce669513cf0e99e9e078267))
- Fixed `LengthAwarePaginator` translations issue ([#34714](https://github.com/laravel/framework/pull/34714))

### Changed
- Improve `schedule:work` command ([#34736](https://github.com/laravel/framework/pull/34736), [bbddba2](https://github.com/laravel/framework/commit/bbddba279bc781fc2868a6967430943de636614f))
- Guard against invalid guard in `make:policy` ([#34792](https://github.com/laravel/framework/pull/34792))
- Fixed router inconsistency for namespaced route groups ([#34793](https://github.com/laravel/framework/pull/34793))


## [v8.9.0 (2020-10-06)](https://github.com/laravel/framework/compare/v8.8.0...v8.9.0)

### Added
- Added support `times()` with `raw()` from `Illuminate\Database\Eloquent\Factories\Factory` ([#34667](https://github.com/laravel/framework/pull/34667))
- Added `Illuminate\Pagination\AbstractPaginator::through()` ([#34657](https://github.com/laravel/framework/pull/34657))
- Added `extendsFirst()` method similar to `includesFirst()` to view ([#34648](https://github.com/laravel/framework/pull/34648))
- Allowed `Illuminate\Http\Client\PendingRequest::attach()` method to accept many files ([#34697](https://github.com/laravel/framework/pull/34697), [1bb7ad6](https://github.com/laravel/framework/commit/1bb7ad664a3607f719af2d91c3f95cf71662dcd2))
- Allowed serializing custom casts when converting a model to an array ([#34702](https://github.com/laravel/framework/pull/34702))

### Fixed
- Added missed RESET_THROTTLED constant to Password Facade ([#34641](https://github.com/laravel/framework/pull/34641))
- Fixed queue clearing when blocking ([#34659](https://github.com/laravel/framework/pull/34659))
- Fixed missing import in TestView.php ([#34677](https://github.com/laravel/framework/pull/34677))
- Use `getRealPath` to ensure console command class names are generated correctly in `Illuminate\Foundation\Console\Kernel` ([#34653](https://github.com/laravel/framework/pull/34653))
- Added `pg_dump --no-owner` and `--no-acl` to avoid owner/permission issues in `Illuminate\Database\Schema\PostgresSchemaState::baseDumpCommand()` ([#34689](https://github.com/laravel/framework/pull/34689))
- Fixed `queue:failed` command when Class not exists ([#34696](https://github.com/laravel/framework/pull/34696))

### Performance
- Increase performance of `Str::before()` by over 60% ([#34642](https://github.com/laravel/framework/pull/34642))


## [v8.8.0 (2020-10-02)](https://github.com/laravel/framework/compare/v8.7.1...v8.8.0)

### Added
- Proxy URL Generation in `VerifyEmail` ([#34572](https://github.com/laravel/framework/pull/34572))
- Added `Illuminate\Collections\Traits\EnumeratesValues::pipeInto()` ([#34600](https://github.com/laravel/framework/pull/34600))
- Added `Illuminate\Http\Client\PendingRequest::withUserAgent()` ([#34611](https://github.com/laravel/framework/pull/34611))
- Added `schedule:work` command ([#34618](https://github.com/laravel/framework/pull/34618))
- Added support for appendable (prepends) component attributes ([09b887b](https://github.com/laravel/framework/commit/09b887b85614d3e2539e74f40d7aa9c1c9f903d3), [53fbc9f](https://github.com/laravel/framework/commit/53fbc9f3768f611c960a5d891a1abb259163978a))

### Fixed
- Fixed `Illuminate\Http\Client\Response::throw()` ([#34597](https://github.com/laravel/framework/pull/34597))
- Fixed breaking change in migrate command ([b2a3641](https://github.com/laravel/framework/commit/b2a36411a774dba218fa312b8fd3bcf4be44a4e5))

### Changed
- Changing the dump and restore method for a PostgreSQL database ([#34293](https://github.com/laravel/framework/pull/34293))


## [v8.7.1 (2020-09-29)](https://github.com/laravel/framework/compare/v8.7.0...v8.7.1)

### Fixed
- Remove type hints ([1b3f62a](https://github.com/laravel/framework/commit/1b3f62aaeced2c9761a6052a7f0d3c1a046851c9))


## [v8.7.0 (2020-09-29)](https://github.com/laravel/framework/compare/v8.6.0...v8.7.0)

### Added
- Added `tg://` protocol in "url" validation rule ([#34464](https://github.com/laravel/framework/pull/34464))
- Allow dynamic factory methods to obey newFactory method on model ([#34492](https://github.com/laravel/framework/pull/34492), [4708e9e](https://github.com/laravel/framework/commit/4708e9ef8f7cde617a5820f07cfd350daaba0e0f))
- Added `no-reload` option to `serve` command ([9cc2622](https://github.com/laravel/framework/commit/9cc2622a9122f5108a694856055c13db8a5f80dc))
- Added `perHour()` and `perDay()` methods to `Illuminate\Cache\RateLimiting\Limit` ([#34530](https://github.com/laravel/framework/pull/34530))
- Added `Illuminate\Http\Client\Response::onError()` ([#34558](https://github.com/laravel/framework/pull/34558), [d034e2c](https://github.com/laravel/framework/commit/d034e2c55c6502fa0c2bebb6cbf99c5e685beaa5))
- Added `X-Message-ID` to `Mailgun` and `Ses Transport` ([#34567](https://github.com/laravel/framework/pull/34567)) 

### Fixed
- Fixed incompatibility with Lumen route function in `Illuminate\Session\Middleware\StartSession` ([#34491](https://github.com/laravel/framework/pull/34491))
- Fixed: Eager loading MorphTo relationship does not honor each models `$keyType` ([#34531](https://github.com/laravel/framework/pull/34531), [c3f44c7](https://github.com/laravel/framework/commit/c3f44c712833d83061452e9a362a5e10fa424863))
- Fixed translation label ("Pagination Navigation") for the Tailwind blade ([#34568](https://github.com/laravel/framework/pull/34568))
- Fixed save keys on increment / decrement in Model ([77db028](https://github.com/laravel/framework/commit/77db028225ccd6ec6bc3359f69482f2e4cc95faf))

### Changed
- Allow modifiers in date format in Model ([#34507](https://github.com/laravel/framework/pull/34507))
- Allow for dynamic calls of anonymous component with varied attributes ([#34498](https://github.com/laravel/framework/pull/34498))
- Cast `Expression` as string so it can be encoded ([#34569](https://github.com/laravel/framework/pull/34569))


## [v8.6.0 (2020-09-22)](https://github.com/laravel/framework/compare/v8.5.0...v8.6.0)

### Added
- Added `Illuminate\Collections\LazyCollection::takeUntilTimeout()` ([0aabf24](https://github.com/laravel/framework/commit/0aabf2472850a9d573907ca092bf5e3cfe26fab3))
- Added `--schema-path` option to `migrate:fresh` command ([#34419](https://github.com/laravel/framework/pull/34419))

### Fixed
- Fixed problems with dots in validator ([#34355](https://github.com/laravel/framework/pull/34355))
- Maintenance mode: Fix empty Retry-After header ([#34412](https://github.com/laravel/framework/pull/34412))
- Fixed bug with error handling in closure scheduled tasks ([#34420](https://github.com/laravel/framework/pull/34420))
- Don't double escape on `ComponentTagCompiler.php` ([12ba0d9](https://github.com/laravel/framework/commit/12ba0d937d54e81eccf8f0a80150f0d70604e1c2))
- Fixed `mysqldump: unknown variable 'column-statistics=0` for MariaDB schema dump ([#34442](https://github.com/laravel/framework/pull/34442))


## [v8.5.0 (2020-09-19)](https://github.com/laravel/framework/compare/v8.4.0...v8.5.0)

### Added
- Allow clearing an SQS queue by `queue:clear` command ([#34383](https://github.com/laravel/framework/pull/34383), [de811ea](https://github.com/laravel/framework/commit/de811ea7f7dc7ecfc686b25fba48e4b0dac473e6))
- Added `Illuminate\Foundation\Auth\EmailVerificationRequest` ([4bde31b](https://github.com/laravel/framework/commit/4bde31b24bf01b4d4a35ad31fafd8e4ca203b0f2))
- Auto handle `Jsonable` values passed to `castAsJson()` ([#34392](https://github.com/laravel/framework/pull/34392))
- Added `crossJoinSub()` method to the query builder ([#34400](https://github.com/laravel/framework/pull/34400))
- Added `Illuminate\Session\Store::passwordConfirmed()` ([fb3f45a](https://github.com/laravel/framework/commit/fb3f45aa0142764c5c29b97e8bcf8328091986e9))

### Changed
- Check for view existence first in `Illuminate\Mail\Markdown::render()` ([5f78c90](https://github.com/laravel/framework/commit/5f78c90a7af118dd07703a78da06586016973a66))
- Guess the model name when using the `make:factory` command ([#34373](https://github.com/laravel/framework/pull/34373))


## [v8.4.0 (2020-09-16)](https://github.com/laravel/framework/compare/v8.3.0...v8.4.0)

### Added
- Added SQLite schema dump support ([#34323](https://github.com/laravel/framework/pull/34323))
- Added `queue:clear` command ([#34330](https://github.com/laravel/framework/pull/34330), [06b378c](https://github.com/laravel/framework/commit/06b378c07b2ea989aa3e947ca003e96ea277153c))

### Fixed
- Fixed `minimal.blade.php` ([#34379](https://github.com/laravel/framework/pull/34379))
- Don't double escape on ComponentTagCompiler.php ([ec75487](https://github.com/laravel/framework/commit/ec75487062506963dd27a4302fe3680c0e3681a3))
- Fixed dots in attribute names in `DynamicComponent` ([2d1d962](https://github.com/laravel/framework/commit/2d1d96272a94bce123676ed742af2d80ba628ba4))

### Changed
- Show warning when view exists when using artisan `make:component` ([#34376](https://github.com/laravel/framework/pull/34376), [0ce75e0](https://github.com/laravel/framework/commit/0ce75e01a66ba4b13bbe4cbed85564f1dc76bb05))
- Call the booting/booted callbacks from the container ([#34370](https://github.com/laravel/framework/pull/34370))


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
