# Release Notes for 7.x

## [Unreleased](https://github.com/laravel/framework/compare/v7.10.3...7.x)

### Added
- Added support for FILTER_FLAG_EMAIL_UNICODE via "email:filter_unicode" in email validator ([#32711](https://github.com/laravel/framework/pull/32711), [43a1ed1](https://github.com/laravel/framework/commit/43a1ed1ee272b77547d292af7d337c745cccd48a))
- Added `Illuminate\Support\Stringable::split()` ([#32713](https://github.com/laravel/framework/pull/32713), [19c5054](https://github.com/laravel/framework/commit/19c5054eff4d00d234cd928db1e085aaa14c4692))
- Added `orWhereIntegerInRaw()` and `orWhereIntegerNotInRaw()` to `Illuminate\Database\Query\Builder` ([#32710](https://github.com/laravel/framework/pull/32710))
- Added `Illuminate\Cache\DatabaseStore::add()` ([7fc452b](https://github.com/laravel/framework/commit/7fc452bd8d6cebd7e7a0c6cd057aea7d4e9a7fc0))

### Fixed
- Fixed belongsToMany child relationship solving ([c5e88be](https://github.com/laravel/framework/commit/c5e88be082bc690961889812360cd6c5ba983117))
- Allow overriding the MySQL server version for strict mode ([#32708](https://github.com/laravel/framework/pull/32708))
- Added boolean to types that don't need character options ([#32716](https://github.com/laravel/framework/pull/32716))
- Fixed `Illuminate\Foundation\Testing\PendingCommand` that do not resolve 'OutputStyle::class' from the container ([#32687](https://github.com/laravel/framework/pull/32687))
- Clear resolved event facade on `Illuminate\Foundation\Testing\Concerns\MocksApplicationServices::withoutEvents()` ([d1e7f85](https://github.com/laravel/framework/commit/d1e7f85dfd79abbe4f5e01818f620f6ecc67de4d))


## [v7.10.3 (2020-05-06)](https://github.com/laravel/framework/compare/v7.10.2...v7.10.3)

### Added
- Added `Illuminate\Http\Client\Response::failed()` ([#32699](https://github.com/laravel/framework/pull/32699))
- Added SSL SYSCALL EOF as a lost connection message ([#32697](https://github.com/laravel/framework/pull/32697))

### Fixed
- Fixed `FakerGenerator` Unique caching issue ([#32703](https://github.com/laravel/framework/pull/32703))
- Set/reset the select to from.* in `Illuminate/Database/Query/Builder::runPaginationCountQuery()` ([858f454](https://github.com/laravel/framework/commit/858f4544d5672bf277686bdb112b1ce055416413), [98a242e](https://github.com/laravel/framework/commit/98a242e21041462054b965e587c250ac7be4f912))


## [v7.10.2 (2020-05-06)](https://github.com/laravel/framework/compare/v7.10.1...v7.10.2)

### Fixed
- Updated `Illuminate\Database\Query\Builder::runPaginationCountQuery()`  to support groupBy and sub-selects ([#32688](https://github.com/laravel/framework/pull/32688))


## [v7.10.1 (2020-05-05)](https://github.com/laravel/framework/compare/v7.10.0...v7.10.1)

### Fixed
- Fixed `Illuminate\Database\Eloquent\Collection::getQueueableRelations()` ([7b32460](https://github.com/laravel/framework/commit/7b32469420258e9e52b24b2ffa7f491e79a3a870))


## [v7.10.0 (2020-05-05)](https://github.com/laravel/framework/compare/v7.9.2...v7.10.0)

### Added
- Added `artisan make:cast` command ([#32594](https://github.com/laravel/framework/pull/32594))
- Added `Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase::assertDatabaseCount()` ([#32597](https://github.com/laravel/framework/pull/32597))
- Allow configuring the auth_mode for SMTP mail driver ([#32616](https://github.com/laravel/framework/pull/32616))
- Added `hasNamedScope()` function to the Base Model ([#32622](https://github.com/laravel/framework/pull/32622), [#32631](https://github.com/laravel/framework/pull/32631))
- Allow doing truth-test assertions with just a closure ([#32626](https://github.com/laravel/framework/pull/32626), [f69ad90](https://github.com/laravel/framework/commit/f69ad90b9d508b59a017d0e412d8228e71230a51), [22d6fca](https://github.com/laravel/framework/commit/22d6fcafba610364aabb2b8e5c385edf56ae0156))
- Run pagination count as subquery for group by and havings ([#32624](https://github.com/laravel/framework/pull/32624))
- Added Callbacks with Output to Console Schedule ([#32633](https://github.com/laravel/framework/pull/32633), [35a7883](https://github.com/laravel/framework/commit/35a788316a0bc20295abe048a1bc1aa34a729ec7), [8d8d620](https://github.com/laravel/framework/commit/8d8d62024188c870df9dec1eeac428089f44c18e))
- Added `Cache::lock()` support for the database cache driver ([#32639](https://github.com/laravel/framework/pull/32639), [573831b](https://github.com/laravel/framework/commit/573831b5028aa440f555d1072672db5069f306d1))
- Same-session ID request concurrency limiting ([#32636](https://github.com/laravel/framework/pull/32636))
- Add `skipUntil` and `skipWhile` methods to the collections ([#32672](https://github.com/laravel/framework/pull/32672), [#32676](https://github.com/laravel/framework/pull/32676))
- Support delete with limit on sqlsrv ([f16d325](https://github.com/laravel/framework/commit/f16d3256f93be71935ed86951e58f90b83912feb))
- Added `mergeFillable()` and `mergeGuarded()` to `Model` ([#32679](https://github.com/laravel/framework/pull/32679))

### Fixed
- Prevents a memory leak in Faker ([2228233](https://github.com/laravel/framework/commit/222823377c936ab4cceeb1fa42db84821c04bff6))
- Fixed setting component name and attributes ([#32599](https://github.com/laravel/framework/pull/32599), [f8ff3ca](https://github.com/laravel/framework/commit/f8ff3cae1ebf2865ef7263b88559c581d48cde6e))
- Fixed `Illuminate\Foundation\Testing\TestResponse::assertSessionHasInput()` ([f0639fd](https://github.com/laravel/framework/commit/f0639fda45fc2874986fe409d944dde21d42c6f3))
- Set relation connection on eager loaded MorphTo ([#32602](https://github.com/laravel/framework/pull/32602))
- Filtering null's in `hasMorph()` ([#32614](https://github.com/laravel/framework/pull/32614))
- Fixed `Illuminate\Foundation\Console\EventMakeCommand::alreadyExists()` ([7bba4bf](https://github.com/laravel/framework/commit/7bba4bfbedb85ee252464aa932414d5517240722))
- Fixed `Illuminate\Console\Scheduling\Schedule::compileParameters()` ([cfc3ac9](https://github.com/laravel/framework/commit/cfc3ac9c8b0a593d264ae722ab90601fa4882d0e), [36e215d](https://github.com/laravel/framework/commit/36e215dd39cd757a8ffc6b17794de60476b2289d))
- Fixed bug with model name in `Illuminate\Database\Eloquent\RelationNotFoundException::make()` ([f72a166](https://github.com/laravel/framework/commit/f72a1662ab64cc543c532941b1ab1279001af8e9))
- Allow trashed through parents to be included in has many through queries ([#32609](https://github.com/laravel/framework/pull/32609))

### Changed
- Changed `Illuminate/Database/Eloquent/Relations/Concerns/AsPivot::fromRawAttributes()` ([6c502c1](https://github.com/laravel/framework/commit/6c502c1135082e8b25f2720931b19d36eeec8f41))
- Restore оnly common relations ([#32613](https://github.com/laravel/framework/pull/32613), [d82f78b](https://github.com/laravel/framework/commit/d82f78b13631c4a04b9595099da0022ca3d8b94e), [48e4d60](https://github.com/laravel/framework/commit/48e4d602d4f8fe9304e8998c5893206f67504dbf))
- Use single space if plain email is empty in `Illuminate\Mail\Mailer::addContent()` ([0557622](https://github.com/laravel/framework/commit/055762286132d545cbc064dce645562c0d51532f))
- Remove wasted file read when loading package manifest in `Illuminate\Foundation\PackageManifest::getManifest()` ([#32646](https://github.com/laravel/framework/pull/32646))
- Do not change `character` and `collation` for some columns on change ([fccdf7c](https://github.com/laravel/framework/commit/fccdf7c42d5ceb50985b3e8243d7ba650de996d6))
- Use table name when resolving has many through / one relationships ([8d69454](https://github.com/laravel/framework/commit/8d69454575267840643289b8de27d615cfe4bb62))


## [v7.9.2 (2020-04-28)](https://github.com/laravel/framework/compare/v7.9.1...v7.9.2)

### Changed
- Extract `InvokableComponentVariable` class ([f1ef6e6](https://github.com/laravel/framework/commit/f1ef6e6c40028cdafb95fc53e950b6ef73030458))
- Changed argument order in `Illuminate\View\Compilers\ComponentTagCompiler::__construct()` ([520544d](https://github.com/laravel/framework/commit/520544dc24772b421410a2528ba01fd47818eeea))


## [v7.9.1 (2020-04-28)](https://github.com/laravel/framework/compare/v7.9.0...v7.9.1)

### Added
- Added more proxy methods to deferred value from `Illuminate\View\Component::createInvokableVariable()` ([08c4012](https://github.com/laravel/framework/commit/08c40123a438e40ad82582fee7ddaa1ff056bb83))


## [v7.9.0 (2020-04-28)](https://github.com/laravel/framework/compare/v7.8.1...v7.9.0)

### Added
- Add pdo try again as lost connection message ([#32544](https://github.com/laravel/framework/pull/32544))
- Compile Echos Within Blade Component Attributes ([#32558](https://github.com/laravel/framework/pull/32558))
- Parameterless Component Methods Invokable With & Without Parens ([#32560](https://github.com/laravel/framework/pull/32560))

### Fixed
- Fixed `firstWhere` behavior for relations ([#32525](https://github.com/laravel/framework/pull/32525))
- Added check to avoid endless loop in `MailManager::createTransport()` ([#32549](https://github.com/laravel/framework/pull/32549))
- Fixed table prefixes with `compileDropDefaultConstraint()` ([#32554](https://github.com/laravel/framework/pull/32554))
- Fixed boolean value in `Illuminate\Foundation\Testing\TestResponse::assertSessionHasErrors()` ([#32555](https://github.com/laravel/framework/pull/32555))
- Fixed `Model::getOriginal()` with custom casts ([9e22c7c](https://github.com/laravel/framework/commit/9e22c7cfa629773eab981ccad13080c0f4cb81b2))

### Changed
- Added `withName` to `Illuminate\View\Component::ignoredMethods()` ([2e9eef2](https://github.com/laravel/framework/commit/2e9eef20a17a8b78493ae775ee95ed11349455d7))


## [v7.8.1 (2020-04-24)](https://github.com/laravel/framework/compare/v7.8.0...v7.8.1)

### Fixed
- Fixed `Illuminate\Http\Resources\Json\PaginatedResourceResponse::toResponse()` ([d460374](https://github.com/laravel/framework/commit/d4603749c03e03e224de3d867e88458599bb9d58))


## [v7.8.0 (2020-04-24)](https://github.com/laravel/framework/compare/v7.7.1...v7.8.0)

### Added
- Added `signedRoute()` and `temporarySignedRoute()` methods to `Illuminate\Routing\Redirector` ([#32489](https://github.com/laravel/framework/pull/32489))
- Added `takeUntil` and `takeWhile` collection methods ([#32494](https://github.com/laravel/framework/pull/32494), [#32496](https://github.com/laravel/framework/pull/32496))
- Added `Illuminate\Container\ContextualBindingBuilder::giveTagged()` ([#32514](https://github.com/laravel/framework/pull/32514))
- Added methods `withFragment` and `withoutFragment` to `Illuminate\Http\RedirectResponse` ([11d6bef](https://github.com/laravel/framework/commit/11d6befb4ed8b306f7ed40a205539a20d4bebe16), [0099591](https://github.com/laravel/framework/commit/0099591d63c51f9139db957ad42f3e783c1d0d30), [42c67a1](https://github.com/laravel/framework/commit/42c67a156acd6e6d44595e973774ad96fdc03857), [a1e741a](https://github.com/laravel/framework/commit/a1e741a1709b3d4998995b76abd990a6c09a5841))
- Added `exclude_without` validation rule ([4083ae5](https://github.com/laravel/framework/commit/4083ae57c6371c889de94df526bb849040bb895c))

### Fixed
- Fixed compiled route actions without a namespace ([#32512](https://github.com/laravel/framework/pull/32512))
- Reset select bindings when setting select ([#32531](https://github.com/laravel/framework/pull/32531))

### Changed
- Added warn in `Illuminate/Support/Facades/Auth::routes()` when laravel/ui is not installed ([#32482](https://github.com/laravel/framework/pull/32482))
- Added auth to each master on `Illuminate\Redis\Connections\PhpRedisConnection::flushdb()` ([837921b](https://github.com/laravel/framework/commit/837921b23311e875a9d22c296a9193a1cd8205cb))
- Register opis key so it is not tied to a deferred service provider (Illuminate/Encryption/EncryptionServiceProvider.php) ([62d8a07](https://github.com/laravel/framework/commit/62d8a0772553f3dff2d52a3ab062182c5efd75a2))
- Pass status code to schedule finish ([#32516](https://github.com/laravel/framework/pull/32516))
- Check route:list --columns option case insensitively ([#32521](https://github.com/laravel/framework/pull/32521))

### Deprecated
- Deprecate `Illuminate\Support\Traits\EnumeratesValues::until` ([#32517](https://github.com/laravel/framework/pull/32517))


## [v7.7.1 (2020-04-21)](https://github.com/laravel/framework/compare/v7.7.0...v7.7.1)

### Added
- Allow developers to specify accepted keys in array rule ([#32452](https://github.com/laravel/framework/pull/32452))

### Changed
- Add check is_object to `Illuminate\Database\Eloquent\Model::refresh()` ([1b0bdb4](https://github.com/laravel/framework/commit/1b0bdb43062a2792befe6fd754140124a8e4dc35))


## [v7.7.0 (2020-04-21)](https://github.com/laravel/framework/compare/v7.6.2...v7.7.0)

### Added
- Added ArrayAccess support for Http client get requests ([#32401](https://github.com/laravel/framework/pull/32401))
- Added `Illuminate\Http\Client\Factory::assertSentCount()` ([#32407](https://github.com/laravel/framework/pull/32407))
- Added `Illuminate\Database\Schema\Blueprint::rawIndex()` ([#32411](https://github.com/laravel/framework/pull/32411))
- Added getGrammar into passthru in Eloquent builder ([#32412](https://github.com/laravel/framework/pull/32412))
- Added `--relative` option to `storage:link` command ([#32457](https://github.com/laravel/framework/pull/32457), [24b705e](https://github.com/laravel/framework/commit/24b705e105d22df014bee3aab7ff12272457771e))
- Added dynamic `column` key for foreign constraints ([#32449](https://github.com/laravel/framework/pull/32449))
- Added container support for variadic constructor arguments ([#32454](https://github.com/laravel/framework/pull/32454), [1dd6db3](https://github.com/laravel/framework/commit/1dd6db3f2f22b1c65d13b3cbd58561f69aa4b317))
- Added `Illuminate\Http\Client\Request::hasHeaders()` ([#32462](https://github.com/laravel/framework/pull/32462))

### Fixed
- Fixed `MorphPivot::delete()` for models with primary key ([#32421](https://github.com/laravel/framework/pull/32421))
- Throw exception on missing required parameter on Container call method ([#32439](https://github.com/laravel/framework/pull/32439), [44c2a8d](https://github.com/laravel/framework/commit/44c2a8dc527f87f5a7fc59058df0f874a23449fa))
- Fixed Http Client multipart request ([#32428](https://github.com/laravel/framework/pull/32428), [1f163d4](https://github.com/laravel/framework/commit/1f163d471b973b237772bb11cdcb994aadd3d530))
- Fixed `Illuminate\Support\Stringable::isEmpty()` ([#32447](https://github.com/laravel/framework/pull/32447))
- Fixed `whereNull`/`whereNotNull` for json in MySQL ([#32417](https://github.com/laravel/framework/pull/32417), [d3bb329](https://github.com/laravel/framework/commit/d3bb329ce40e716e8e92aa7c27a929be60511a97))
- Fixed `Collection::orderBy()` with callable ([#32471](https://github.com/laravel/framework/pull/32471))

### Changed
- Re-use `Router::newRoute()` inside `CompiledRouteCollection` ([#32416](https://github.com/laravel/framework/pull/32416))
- Make `Illuminate\Queue\InteractsWithQueue.php::$job` public ([2e272ee](https://github.com/laravel/framework/commit/2e272ee6df6ac22675a4645cac8b581017aac53f))
- Catch and report exceptions thrown during schedule run execution ([#32461](https://github.com/laravel/framework/pull/32461))


## [v7.6.2 (2020-04-15)](https://github.com/laravel/framework/compare/v7.6.1...v7.6.2)

### Added
- Added `substrCount()` method to `Stringable` and `Str` ([#32393](https://github.com/laravel/framework/pull/32393))

### Fixed
- Fixed Lazyload `PackageManifest` ([#32391](https://github.com/laravel/framework/pull/32391))
- Fixed email validator ([#32388](https://github.com/laravel/framework/pull/32388))
- Fixed `Illuminate\Mail\Mailable::attachFromStorageDisk()` ([#32394](https://github.com/laravel/framework/pull/32394))

### Changed
- Changed `Illuminate\Translation\Translator::setLocale()` ([e78d24f](https://github.com/laravel/framework/commit/e78d24f31b84cd81c30b5d8837731d77ec089761), [a0094a5](https://github.com/laravel/framework/commit/a0094a57717b1f4c3e2a6feb978cc14f2c4690ff))
- Changed `Illuminate\Mail\Mailable::attachData()` ([#32392](https://github.com/laravel/framework/pull/32392))


## [v7.6.1 (2020-04-14)](https://github.com/laravel/framework/compare/v7.6.0...v7.6.1)

### Fixed
- Fixed `Illuminate\Testing\TestResponse::offsetExists()` ([#32377](https://github.com/laravel/framework/pull/32377))


## [v7.6.0 (2020-04-14)](https://github.com/laravel/framework/compare/v7.5.2...v7.6.0)

### Added
- Added `Collection::until()` method ([#32262](https://github.com/laravel/framework/pull/32262))
- Added `HtmlString::isEmpty()` method ([#32289](https://github.com/laravel/framework/pull/32289), [#32300](https://github.com/laravel/framework/pull/32300))
- Added `Illuminate\Support\Stringable::isNotEmpty()` method ([#32293](https://github.com/laravel/framework/pull/32293))
- Added `ltrim()` and `rtrim()` methods to `Illuminate\Support\Stringable` class ([#32288](https://github.com/laravel/framework/pull/32288))
- Added ability to skip a middleware ([#32347](https://github.com/laravel/framework/pull/32347), [412261c](https://github.com/laravel/framework/commit/412261c180a0ffb561078b7f0647f2a0a5c46c8d))
- Added `Illuminate\Http\Client\Response::object()` method ([#32341](https://github.com/laravel/framework/pull/32341))
- Set component alias name ([#32346](https://github.com/laravel/framework/pull/32346))
- Added `Illuminate\Database\Eloquent\Collection::append()` method ([#32324](https://github.com/laravel/framework/pull/32324))
- Added "between" clauses for BelongsToMany pivot columns ([#32364](https://github.com/laravel/framework/pull/32364))
- Support `retryAfter()` method option on Queued Listeners ([#32370](https://github.com/laravel/framework/pull/32370))
- Added support for the new composer installed.json format ([#32310](https://github.com/laravel/framework/pull/32310))
- Added `uuid` change support in migrations ([#32316](https://github.com/laravel/framework/pull/32316))
- Allowed store resource into postgresql bytea ([#32319](https://github.com/laravel/framework/pull/32319))

### Fixed
- Fixed `*scan` methods for phpredis ([#32336](https://github.com/laravel/framework/pull/32336))
- Fixed `Illuminate\Auth\Notifications\ResetPassword::toMail()` ([#32345](https://github.com/laravel/framework/pull/32345))
- Call setLocale in `Illuminate\Translation\Translator::__construct()` ([1c6a504](https://github.com/laravel/framework/commit/1c6a50424c5558782a55769a226ab834484282e1))
- Used a map to prevent unnecessary array access in `Illuminate\Http\Resources\Json\PaginatedResourceResponse::toResponse()` ([#32296](https://github.com/laravel/framework/pull/32296))
- Prevent timestamp update when pivot is not dirty ([#32311](https://github.com/laravel/framework/pull/32311))
- Fixed CURRENT_TIMESTAMP precision bug in `Illuminate\Database\Schema\Grammars\MySqlGrammar` ([#32298](https://github.com/laravel/framework/pull/32298))

### Changed
- Added default value to `HtmlString` constructor ([#32290](https://github.com/laravel/framework/pull/32290))
- Used `BindingResolutionException` to signal problem with container resolution ([#32349](https://github.com/laravel/framework/pull/32349))
- `Illuminate\Validation\Concerns\ValidatesAttributes.php ::validateUrl()` use Symfony/Validator 5.0.7 regex ([#32315](https://github.com/laravel/framework/pull/32315))

### Depreciated
- Depreciate the `elixir` function ([#32366](https://github.com/laravel/framework/pull/32366))


## [v7.5.2 (2020-04-08)](https://github.com/laravel/framework/compare/v7.5.1...v7.5.2)

### Fixed
- Prevent insecure characters in locale ([c248521](https://github.com/laravel/framework/commit/c248521f502c74c6cea7b0d221639d4aa752d5db))

### Optimization
- Optimize `Arr::set()` method ([#32282](https://github.com/laravel/framework/pull/32282))


## [v7.5.1 (2020-04-07)](https://github.com/laravel/framework/compare/v7.5.0...v7.5.1)

### Fixed
- Fixed Check a request header with an array value in `Illuminate\Http\Client\Request::hasHeader()` ([#32274](https://github.com/laravel/framework/pull/32274))
- Fixed setting mail header ([#32272](https://github.com/laravel/framework/pull/32272))


## [v7.5.0 (2020-04-07)](https://github.com/laravel/framework/compare/v7.4.0...v7.5.0)

### Added
- Added `assertNotSent()` and `assertNothingSent()` methods to  `Illuminate\Http\Client\Factory` ([#32197](https://github.com/laravel/framework/pull/32197))
- Added enum support for `renameColumn()` ([#32205](https://github.com/laravel/framework/pull/32205))
- Support returning an instance of a caster ([#32225](https://github.com/laravel/framework/pull/32225))

### Fixed
- Prevent long URLs from breaking email layouts ([#32189](https://github.com/laravel/framework/pull/32189))
- Fixed camel casing relationship ([#32217](https://github.com/laravel/framework/pull/32217))
- Fixed merging boolean or null attributes in Blade components ([#32245](https://github.com/laravel/framework/pull/32245))
- Fixed Console expectation assertion order ([#32258](https://github.com/laravel/framework/pull/32258))
- Fixed `route` helper with custom binding key ([#32264](https://github.com/laravel/framework/pull/32264))
- Fixed double slashes matching in UriValidator (fix inconsistencies between cached and none cached routes) ([#32260](https://github.com/laravel/framework/pull/32260))
- Fixed setting mail header ([#32272](https://github.com/laravel/framework/pull/32272))

### Optimization
- Optimize `Container::resolve()` method ([#32194](https://github.com/laravel/framework/pull/32194))
- Optimize performance for `data_get()` method ([#32192](https://github.com/laravel/framework/pull/32192))
- Optimize `Str::startsWith()` ([#32243](https://github.com/laravel/framework/pull/32243))


## [v7.4.0 (2020-03-31)](https://github.com/laravel/framework/compare/v7.3.0...v7.4.0)

### Added
- Makes the stubs used for `make:policy` customizable ([#32040](https://github.com/laravel/framework/pull/32040), [9d36a36](https://github.com/laravel/framework/commit/9d36a369d377044d0f468d1f02fa317cbb93571f))
- Implement `HigherOrderWhenProxy` for Collections ([#32148](https://github.com/laravel/framework/pull/32148))
- Added `Illuminate\Testing\PendingCommand::expectsChoice()` ([#32139](https://github.com/laravel/framework/pull/32139))
- Added support for default values for the "props" blade tag ([#32177](https://github.com/laravel/framework/pull/32177))
- Added `Castable` interface ([#32129](https://github.com/laravel/framework/pull/32129), [9cbf908](https://github.com/laravel/framework/commit/9cbf908c218bba74fbf83a83740b5c9f21c13e4e), [651371a](https://github.com/laravel/framework/commit/651371a2a982c06654b4df9af56110b666b2157f))
- Added the ability to remove orders from the query builder ([#32186](https://github.com/laravel/framework/pull/32186))

### Fixed
- Added missing return in the `PendingMailFake::sendNow()` and `PendingMailFake::send()` ([#32093](https://github.com/laravel/framework/pull/32093))
- Fixed custom Model attributes casts ([#32118](https://github.com/laravel/framework/pull/32118))
- Fixed route group prefixing ([#32135](https://github.com/laravel/framework/pull/32135), [870efef](https://github.com/laravel/framework/commit/870efef4c23ff7f151b6e1f267ac18951a3af2f1))
- Fixed component class view reference ([#32132](https://github.com/laravel/framework/pull/32132))

### Changed
- Remove Swift Mailer bindings ([#32165](https://github.com/laravel/framework/pull/32165))
- Publish console stub when running `stub:publish` command ([#32096](https://github.com/laravel/framework/pull/32096))
- Publish rule stub when running `make:rule` command ([#32097](https://github.com/laravel/framework/pull/32097))
- Adding the middleware.stub to the files that will be published when running php artisan `stub:publish` ([#32099](https://github.com/laravel/framework/pull/32099))
- Adding the factory.stub to the files that will be published when running php artisan `stub:publish` ([#32100](https://github.com/laravel/framework/pull/32100))
- Adding the seeder.stub to the files that will be published when running php artisan `stub:publish` ([#32122](https://github.com/laravel/framework/pull/32122))


## [v7.3.0 (2020-03-24)](https://github.com/laravel/framework/compare/v7.2.2...v7.3.0)

### Added
- Added possibility to use `^4.0` versions of `ramsey/uuid` ([#32086](https://github.com/laravel/framework/pull/32086))

### Fixed
- Corrected suggested dependencies ([#32072](https://github.com/laravel/framework/pull/32072), [c01a70e](https://github.com/laravel/framework/commit/c01a70e33198e81d06d4b581e36e25a80acf8a68))
- Avoid deadlock in test when sharing process group ([#32067](https://github.com/laravel/framework/pull/32067))


## [v7.2.2 (2020-03-20)](https://github.com/laravel/framework/compare/v7.2.1...v7.2.2)

### Fixed
- Fixed empty data for blade components ([#32032](https://github.com/laravel/framework/pull/32032))
- Fixed subdirectories when making components by `make:component` ([#32030](https://github.com/laravel/framework/pull/32030))
- Fixed serialization of models when sending notifications ([#32051](https://github.com/laravel/framework/pull/32051))
- Fixed route trailing slash in cached routes matcher ([#32048](https://github.com/laravel/framework/pull/32048))

### Changed
- Throw exception for non existing component alias ([#32036](https://github.com/laravel/framework/pull/32036))
- Don't overwrite published stub files by default in `stub:publish` command ([#32038](https://github.com/laravel/framework/pull/32038))


## [v7.2.1 (2020-03-19)](https://github.com/laravel/framework/compare/v7.2.0...v7.2.1)

### Fixed
- Enabling Windows absolute cache paths normalizing ([#31985](https://github.com/laravel/framework/pull/31985), [adfcb59](https://github.com/laravel/framework/commit/adfcb593fef058a32398d1e84d9083c8c5f893ac))
- Fixed blade newlines ([#32026](https://github.com/laravel/framework/pull/32026))
- Fixed exception rendering in debug mode ([#32027](https://github.com/laravel/framework/pull/32027))
- Fixed route naming issue ([#32028](https://github.com/laravel/framework/pull/32028))


## [v7.2.0 (2020-03-17)](https://github.com/laravel/framework/compare/v7.1.3...v7.2.0)

### Added
- Added `Illuminate\Testing\PendingCommand::expectsConfirmation()` ([#31965](https://github.com/laravel/framework/pull/31965))
- Allowed configuring the timeout for the smtp mail driver ([#31973](https://github.com/laravel/framework/pull/31973))
- Added `Http client` query string support ([#31996](https://github.com/laravel/framework/pull/31996))

### Fixed
- Fixed `cookie` helper signature , matching match `CookieFactory` ([#31974](https://github.com/laravel/framework/pull/31974))
- Added missing `ramsey/uuid` dependency to `Illuminate/Queue/composer.json` ([#31988](https://github.com/laravel/framework/pull/31988))
- Fixed output of component attributes in View ([#31994](https://github.com/laravel/framework/pull/31994))

### Changed
- Publish the form request stub used by RequestMakeCommand ([#31962](https://github.com/laravel/framework/pull/31962))
- Handle prefix update on route level prefix ([449c80](https://github.com/laravel/framework/commit/449c8056cc0f13e7e20428700045339bae6bdca2))
- Ensure SqsQueue queues are only suffixed once ([#31925](https://github.com/laravel/framework/pull/31925))
- Added space after component closing tag for the View ([#32005](https://github.com/laravel/framework/pull/32005))


## [v7.1.3 (2020-03-14)](https://github.com/laravel/framework/compare/v7.1.2...v7.1.3)

### Fixed
- Unset `pivotParent` on `Pivot::unsetRelations()` ([#31956](https://github.com/laravel/framework/pull/31956))

### Changed
- Escape merged attributes by default in `Illuminate\View\ComponentAttributeBag` ([83c8e6e](https://github.com/laravel/framework/commit/83c8e6e6b575d0029ea164ba4b44f4c4895dbb3d))
 

## [v7.1.2 (2020-03-13)](https://github.com/laravel/framework/compare/v7.1.1...v7.1.2)

### Fixed
- Corrected suggested dependencies ([bb0ec42](https://github.com/laravel/framework/commit/bb0ec42b5a55b3ebf3a5a35cc6df01eec290dfa9))
- Fixed null value injected from container in routes ([#31867](https://github.com/laravel/framework/pull/31867), [c666c42](https://github.com/laravel/framework/commit/c666c424e8a60539a8fbd7cb5a3474785d9db22a))

### Changed 
- Escape attributes automatically in some situations in `Illuminate\View\Compilers\ComponentTagCompiler` ([#31945](https://github.com/laravel/framework/pull/31945))


## [v7.1.1 (2020-03-12)](https://github.com/laravel/framework/compare/v7.1.0...v7.1.1)

### Added
- Added `dispatchToQueue()` to `BusFake` ([#31935](https://github.com/laravel/framework/pull/31935))
- Support either order of arguments for symmetry with livewire ([8d558670](https://github.com/laravel/framework/commit/8d5586700ad97b92ac622ea72c1fefe52c359265))

### Fixed
- Bring `--daemon` option back to `queue:work` command ([24c1818](https://github.com/laravel/framework/commit/24c18182a82ee24be62d2ac1c6793c237944cda8))
- Fixed scheduler dependency assumptions ([#31894](https://github.com/laravel/framework/pull/31894))
- Fixed ComponentAttributeBag merge behaviour ([#31932](https://github.com/laravel/framework/pull/31932))

### Changed
- Intelligently drop unnamed prefix name routes when caching ([#31917](https://github.com/laravel/framework/pull/31917))
- Closure jobs needs illuminate/queue ([#31933](https://github.com/laravel/framework/pull/31933)) 
- Have a cache aware interface instead of concrete checks ([#31903](https://github.com/laravel/framework/pull/31903))


## [v7.1.0 (2020-03-10)](https://github.com/laravel/framework/compare/v7.0.8...v7.1.0)

### Added
- Added `Illuminate\Routing\RouteRegistrar::apiResource()` method ([#31857](https://github.com/laravel/framework/pull/31857)) 
- Added optional $table parameter to `ForeignIdColumnDefinition::constrained()` method ([#31853](https://github.com/laravel/framework/pull/31853))

### Fixed
- Fixed phpredis "zadd" and "exists" on cluster ([#31838](https://github.com/laravel/framework/pull/31838))
- Fixed trailing slash in `Illuminate\Routing\CompiledRouteCollection::match()` ([3d58cd9](https://github.com/laravel/framework/commit/3d58cd91d6ec483a43a4c23af9b75ecdd4a358de), [ac6f3a8](https://github.com/laravel/framework/commit/ac6f3a8bd0e94ea1319b6f278ecf7f3f8bada3c2))
- Fixed "srid" mysql schema ([#31852](https://github.com/laravel/framework/pull/31852))
- Fixed Microsoft ODBC lost connection handling ([#31879](https://github.com/laravel/framework/pull/31879))

### Changed
- Fire `MessageLogged` event after the message has been logged (not before) ([#31843](https://github.com/laravel/framework/pull/31843))
- Avoid using array_merge_recursive in HTTP client ([#31858](https://github.com/laravel/framework/pull/31858))
- Expire the jobs cache keys after 1 day ([#31854](https://github.com/laravel/framework/pull/31854))
- Avoid global app() when compiling components ([#31868](https://github.com/laravel/framework/pull/31868))


## [v7.0.8 (2020-03-08)](https://github.com/laravel/framework/compare/v7.0.7...v7.0.8)

### Added
- Added `Illuminate\Mail\Mailable::when()` method ([#31828](https://github.com/laravel/framework/pull/31828))

### Fixed
- Match Symfony's `Command::setHidden` declaration ([#31840](https://github.com/laravel/framework/pull/31840))
- Fixed dynamically adding of routes during caching ([#31829](https://github.com/laravel/framework/pull/31829))

### Changed
- Update the encryption algorithm to provide deterministic encryption sizes ([#31721](https://github.com/laravel/framework/pull/31721))


## [v7.0.7 (2020-03-07)](https://github.com/laravel/framework/compare/v7.0.6...v7.0.7)

### Fixed
- Fixed type hint for `Request::get()` method ([#31826](https://github.com/laravel/framework/pull/31826))
- Add missing public methods to `Illuminate\Routing\RouteCollectionInterface` ([e4f477c](https://github.com/laravel/framework/commit/e4f477c42d3e24f6cdf44a45801c0db476ad2b91))


## [v7.0.6 (2020-03-06)](https://github.com/laravel/framework/compare/v7.0.5...v7.0.6)

### Added
- Added queue suffix for SQS driver ([#31784](https://github.com/laravel/framework/pull/31784))

### Fixed
- Fixed model binding when route cached ([af80685](https://github.com/laravel/framework/commit/af806851931700e8dd8de0ac0333efd853b19f3d))
- Fixed incompatible `Factory` contract for `MailFacade` ([#31809](https://github.com/laravel/framework/pull/31809))

### Changed
- Fixed typehints in `Illuminate\Foundation\Application::handle()` ([#31806](https://github.com/laravel/framework/pull/31806))


## [v7.0.5 (2020-03-06)](https://github.com/laravel/framework/compare/v7.0.4...v7.0.5)

### Fixed
- Fixed `Illuminate\Http\Client\PendingRequest::withCookies()` method ([36d783c](https://github.com/laravel/framework/commit/36d783ce8dbd8736e694ff60ae66e542c62411c3))
- Catch Symfony `MethodNotAllowedException` exception in `CompiledRouteCollection::match()` method ([#31762](https://github.com/laravel/framework/pull/31762))
- Fixed a bug with slash prefix in the route ([#31760](https://github.com/laravel/framework/pull/31760))
- Fixed root URI not showing in the `route:list` ([#31771](https://github.com/laravel/framework/pull/31771))
- Fixed model restoring right after being soft deleting ([#31719](https://github.com/laravel/framework/pull/31719))
- Fixed array lock release behavior ([#31795](https://github.com/laravel/framework/pull/31795))
- Fixed `Illuminate\Support\Str::slug()` method ([e4f22d8](https://github.com/laravel/framework/commit/e4f22d855b429e4141885d542438c859f84bfe49))

### Changed
- Throw exception for duplicate route names in `Illuminate\Routing\AbstractRouteCollection::addToSymfonyRoutesCollection()` method ([#31755](https://github.com/laravel/framework/pull/31755))
- Revert disabling expired views checks ([#31798](https://github.com/laravel/framework/pull/31798))


## [v7.0.4 (2020-03-05)](https://github.com/laravel/framework/compare/v7.0.3...v7.0.4)

### Changed
- Changed of route prefix parameter parsing ([b38e179](https://github.com/laravel/framework/commit/b38e179642d6a76a7713ced1fddde841900ac3ad))


## [v7.0.3 (2020-03-04)](https://github.com/laravel/framework/compare/v7.0.2...v7.0.3)

### Fixed
- Fixed route caching attempt in `Illuminate\Routing\CompiledRouteCollection::newRoute()` ([90b0167](https://github.com/laravel/framework/commit/90b0167d97e61eb06fce9cfc58527f4e09cd2a5e))
- Catch Symfony exception in `CompiledRouteCollection::match()` method ([#31738](https://github.com/laravel/framework/pull/31738))
- Fixed Eloquent model casting ([2b395cd](https://github.com/laravel/framework/commit/2b395cd1f2fe95b67edf97684f09b7c5c4a55152))
- Fixed `UrlGenerator` constructor ([#31740](https://github.com/laravel/framework/pull/31740))

### Changed
- Added message to `Illuminate\Http\Client\RequestException` ([#31720](https://github.com/laravel/framework/pull/31720))


## [v7.0.2 (2020-03-04)](https://github.com/laravel/framework/compare/v7.0.1...v7.0.2)

### Fixed
- Fixed `ascii()` \ `isAscii()` \ `slug()` methods on the `Str` class with null value in the methods ([#31717](https://github.com/laravel/framework/pull/31717))
- Fixed `trim` of the prefix in the `CompiledRouteCollection::newRoute()` ([ce0355c](https://github.com/laravel/framework/commit/ce0355c72bf4defb93ae80c7bf7812bd6532031a), [b842c65](https://github.com/laravel/framework/commit/b842c65ecfe1ea7839d61a46b177b6b5887fd4d2))

### Changed
- remove comments before compiling components in the `BladeCompiler` ([2964d2d](https://github.com/laravel/framework/commit/2964d2dfd3cc50f7a709effee0af671c86587915))


## [v7.0.1 (2020-03-03)](https://github.com/laravel/framework/compare/v7.0.0...v7.0.1)

### Fixed
- Fixed `Illuminate\View\Component::withAttributes()` method ([c81ffad](https://github.com/laravel/framework/commit/c81ffad7ef8d74ebd109f399abbdc5c7ebabff88))


## [v7.0.0 (2020-03-03)](https://github.com/laravel/framework/compare/v6.18.0...v7.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/7.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/7.x/releases).
