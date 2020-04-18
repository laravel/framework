# Release Notes for 7.x

## [Unreleased](https://github.com/laravel/framework/compare/v7.6.2...7.x)

### Added
- Added ArrayAccess support for Http client get requests ([#32401](https://github.com/laravel/framework/pull/32401))
- Added `Illuminate\Http\Client\Factory::assertSentCount()` ([#32407](https://github.com/laravel/framework/pull/32407))
- Added `Illuminate\Database\Schema\Blueprint::rawIndex()` ([#32411](https://github.com/laravel/framework/pull/32411))
- Added getGrammar into passthru in Eloquent builder ([#32412](https://github.com/laravel/framework/pull/32412))

### Fixed
- Fixed `MorphPivot::delete()` for models with primary key ([#32421](https://github.com/laravel/framework/pull/32421))

### Changed
- Re-use `Router::newRoute()` inside `CompiledRouteCollection` ([#32416](https://github.com/laravel/framework/pull/32416))
- Make `Illuminate\Queue\InteractsWithQueue.php::$job` public ([2e272ee](https://github.com/laravel/framework/commit/2e272ee6df6ac22675a4645cac8b581017aac53f))


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
