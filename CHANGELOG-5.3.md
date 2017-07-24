# Release Notes for 5.3.x

## v5.3.30 (2017-01-26)

### Added
- Added `read()` and `unread()` methods to `DatabaseNotification` ([#17243](https://github.com/laravel/framework/pull/17243))

### Changed
- Show seed output prior to running, instead of after ([#17318](https://github.com/laravel/framework/pull/17318))
- Support starting slash in `elixir()` helper ([#17359](https://github.com/laravel/framework/pull/17359))

### Fixed
- Use regex in `KeyGenerateCommand` to match `APP_KEY` ([#17151](https://github.com/laravel/framework/pull/17151))
- Fixed integrity constraints for database session driver ([#17301](https://github.com/laravel/framework/pull/17301))


## v5.3.29 (2017-01-06)

### Added
- Added `Blueprint::nullableMorphs()` ([#16879](https://github.com/laravel/framework/pull/16879))
- Support `BaseCollection` in `BelongsToMany::sync()` ([#16882](https://github.com/laravel/framework/pull/16882))
- Added `--model` flag to `make:controller` command ([#16787](https://github.com/laravel/framework/pull/16787))
- Allow notifications to be broadcasted now instead of using the queue ([#16867](https://github.com/laravel/framework/pull/16867), [40f30f1](https://github.com/laravel/framework/commit/40f30f1a2131904eb4f6e6c456823e7b2cb726eb))
- Support `redirectTo()` in `RedirectsUsers` ([#16896](https://github.com/laravel/framework/pull/16896))
- Added `ArrayTransport` to mail component to store Swift messages in memory ([#16906](https://github.com/laravel/framework/pull/16906), [69d3d04](https://github.com/laravel/framework/commit/69d3d0463cf6bd114d2beecd8480556efb168678))
- Added fallback to `SlackAttachment` notification ([#16912](https://github.com/laravel/framework/pull/16912))
- Added `Macroable` trait to `RedirectResponse` ([#16929](https://github.com/laravel/framework/pull/16929))
- Support namespaces when using `make:policy --model` ([#16981](https://github.com/laravel/framework/pull/16981))
- Added `HourlyAt()` option for scheduled events ([#17168](https://github.com/laravel/framework/pull/17168))

### Changed
- Allow SparkPost transport transmission metadata to be set at runtime ([#16838](https://github.com/laravel/framework/pull/16838))
- Pass keys to `Collection::unique()` callback ([#16883](https://github.com/laravel/framework/pull/16883))
- Support calling `MailFake::send()` when `build()` has dependencies ([#16918](https://github.com/laravel/framework/pull/16918))
- Changed `Mailable` properties visibility to public ([#16916](https://github.com/laravel/framework/pull/16916))
- Bind `serve` command to `127.0.0.1` instead of `localhost` ([#16937](https://github.com/laravel/framework/pull/16937))
- Added `old('remember')` call to `login.stub` ([#16944](https://github.com/laravel/framework/pull/16944))
- Check for `db` before setting presence verifier in `ValidationServiceProvider` ([038840d](https://github.com/laravel/framework/commit/038840d477e606735f9179d97eeb20639450e8ae))
- Make Eloquent's `getTimeZone()` method call adhere to `DateTimeInterface` ([#16955](https://github.com/laravel/framework/pull/16955))
- Support customizable response in `SendsPasswordResetEmails` ([#16982](https://github.com/laravel/framework/pull/16982))
- Stricter comparison when replacing URL for `LocalAdapter` ([#17097](https://github.com/laravel/framework/pull/17097))
- Use `notification()` relationship in `HasDatabaseNotifications` ([#17093](https://github.com/laravel/framework/pull/17093))
- Allow float value as expiration in Memcached cache store ([#17106](https://github.com/laravel/framework/pull/17106))

### Fixed
- Fixed a wildcard issue with `sometimes` validation rule ([#16826](https://github.com/laravel/framework/pull/16826))
- Prevent error when SqlServer port is empty ([#16824](https://github.com/laravel/framework/pull/16824))
- Reverted false-positive fix for `date_format` validation [#16692](https://github.com/laravel/framework/pull/16692) ([#16845](https://github.com/laravel/framework/pull/16845))
- Fixed `withCount()` aliasing using multiple tables ([#16853](https://github.com/laravel/framework/pull/16853))
- Fixed broken event interface listening ([#16877](https://github.com/laravel/framework/pull/16877))
- Fixed empty model creation ([#16864](https://github.com/laravel/framework/pull/16864))
- Fixed column overlapping on using `withCount()` on `BelongsToMany` ([#16895](https://github.com/laravel/framework/pull/16895))
- Fixed `Unique::ignore()` issue ([#16948](https://github.com/laravel/framework/pull/16948))
- Fixed logic in `ChannelManager::sendNow()` if `$channels` is `null` ([#17068](https://github.com/laravel/framework/pull/17068))
- Fixed validating distinct for nested keys ([#17102](https://github.com/laravel/framework/pull/17102))
- Fixed `HasManyThrough::updateOrCreate()` ([#17105](https://github.com/laravel/framework/pull/17105))

### Security
- Changed SwiftMailer version to `~5.4` ([#17131](https://github.com/laravel/framework/pull/17131))


## v5.3.28 (2016-12-15)

### Changed
- Refactored `ControllerMakeCommand` class ([59a1ce2](https://github.com/laravel/framework/commit/59a1ce21413221131aaf0086cd1eb7c887c701c0))

### Fixed
- Fixed implicit Router binding through IoC ([#16802](https://github.com/laravel/framework/pull/16802))
- `Collection::min()` incorrectly excludes `0` when calculating minimum ([#16821](https://github.com/laravel/framework/pull/16821))


## v5.3.27 (2016-12-15)

### Added
- Added `Authenticatable::$rememberTokenName` ([#16617](https://github.com/laravel/framework/pull/16617), [38612c0](https://github.com/laravel/framework/commit/38612c0e88a48cca5744cc464a764b976f79a46d))
- Added `Collection::partition()` method ([#16627](https://github.com/laravel/framework/pull/16627), [#16644](https://github.com/laravel/framework/pull/16644))
- Added resource routes translations ([#16429](https://github.com/laravel/framework/pull/16429), [e91f04b](https://github.com/laravel/framework/commit/e91f04b52603194dbc90dbbaee730e171bee1449))
- Allow `TokenGuard` API token to be sent through as input ([#16766](https://github.com/laravel/framework/pull/16766))
- Added `Collection::isNotEmpty()` ([#16797](https://github.com/laravel/framework/pull/16797))
- Added "evidence" to the list of uncountable words ([#16788](https://github.com/laravel/framework/pull/16788))
- Added `reply_to` to mailer config ([#16810](https://github.com/laravel/framework/pull/16810), [dc2ce4f](https://github.com/laravel/framework/commit/dc2ce4f9efb831a304e1c2674aae1dfd819b9c56))

### Changed
- Added missing `$useReadPdo` argument to `Connection::selectOne()` ([#16625](https://github.com/laravel/framework/pull/16625))
- Preload some files required by already listed files ([#16648](https://github.com/laravel/framework/pull/16648))
- Clone query for chunking ([53f97a0](https://github.com/laravel/framework/commit/53f97a014da380dc85fb4b0d826475e562d78dcc), [32d0f16](https://github.com/laravel/framework/commit/32d0f164424ab5b4a2bff2ed927812ae49bd8051))
- Return a regular `PDO` object if a persistent connection is requested ([#16702](https://github.com/laravel/framework/pull/16702), [6b413d5](https://github.com/laravel/framework/commit/6b413d5b416c1e0b629a3036e6c3ad84b3b76a6e))
- Global `to` address now also applied for the `cc` and `bcc` options of an email ([#16705](https://github.com/laravel/framework/pull/16705))
- Don't report exceptions inside queue worker signal handler ([#16738](https://github.com/laravel/framework/pull/16738))
- Kill timed out queue worker process ([#16746](https://github.com/laravel/framework/pull/16746))
- Removed unnecessary check in `ScheduleRunCommand::fire()` ([#16752](https://github.com/laravel/framework/pull/16752))
- Only guess the ability's name if no fully qualified class name was given ([#16807](https://github.com/laravel/framework/pull/16807), [f79839e](https://github.com/laravel/framework/commit/f79839e4b72999a67d5503bbb8437547cab87236))
- Remove falsy values from array in `min()` and `max()` on `Collection` ([e2d317e](https://github.com/laravel/framework/commit/e2d317efcebbdf6651d89100c0b5d80a925bb2f1))

### Fixed
- Added file existence check to `AppNameCommand::replaceIn()` to fix [#16575](https://github.com/laravel/framework/pull/16575) ([#16592](https://github.com/laravel/framework/pull/16592))
- Check for `null` in `seeJsonStructure()` ([#16642](https://github.com/laravel/framework/pull/16642))
- Reverted [#15264](https://github.com/laravel/framework/pull/15264) ([#16660](https://github.com/laravel/framework/pull/16660))
- Fixed misleading credentials exception when `ExceptionHandler` is not bound in container ([#16666](https://github.com/laravel/framework/pull/16666))
- Use `sync` as queue name for Sync Queues ([#16681](https://github.com/laravel/framework/pull/16681))
- Fixed `storedAs()` and `virtualAs()` issue ([#16683](https://github.com/laravel/framework/pull/16683))
- Fixed false-positive `date_format` validation ([#16692](https://github.com/laravel/framework/pull/16692))
- Use translator `trans()` method in `Validator` ([#16778](https://github.com/laravel/framework/pull/16778))
- Fixed runtime error in `RouteServiceProvider` when `Route` facade is not available ([#16775](https://github.com/laravel/framework/pull/16775))

### Removed
- Removed hard coded prose from scheduled task email subject ([#16790](https://github.com/laravel/framework/pull/16790))


## v5.3.26 (2016-11-30)

### Changed
- Replaced deprecated `DefaultFinder` class ([#16602](https://github.com/laravel/framework/pull/16602))

### Fixed
- Reverted [#16506](https://github.com/laravel/framework/pull/16506) ([#16607](https://github.com/laravel/framework/pull/16607))


## v5.3.25 (2016-11-29)

### Added
- Added `before_or_equal` and `after_or_equal` validation rules ([#16490](https://github.com/laravel/framework/pull/16490))
- Added fluent builder for `SlackMessageAttachmentField` ([#16535](https://github.com/laravel/framework/pull/16535), [db4879a](https://github.com/laravel/framework/commit/db4879ae84a3a1959729ac2732ae42cfe377314c))
- Added the possibility to set and get file permissions in `Filesystem` ([#16560](https://github.com/laravel/framework/pull/16560))

### Changed
- Added additional `keyType` check to avoid using an invalid type for eager load constraints ([#16452](https://github.com/laravel/framework/pull/16452))
- Always debug Pusher in `PusherBroadcaster::broadcast()` ([#16493](https://github.com/laravel/framework/pull/16493))
- Don't pluralize "metadata" ([#16518](https://github.com/laravel/framework/pull/16518))
- Always pass a collection to `LengthAwarePaginator` from `paginate()` methods ([#16547](https://github.com/laravel/framework/pull/16547))
- Avoid unexpected connection timeouts when flushing tagged caches on Redis ([#16568](https://github.com/laravel/framework/pull/16568))
- Enabled unicode support to `NexmoSmsChannel` ([#16577](https://github.com/laravel/framework/pull/16577), [3001640](https://github.com/laravel/framework/commit/30016408a6911afba4aa7739d69948d13612ea06))

### Fixed
- Fixed view compilation bug when using " or " in strings ([#16506](https://github.com/laravel/framework/pull/16506))


## v5.3.24 (2016-11-21)

### Added
- Added `AuthenticateSession` middleware ([fc302a6](https://github.com/laravel/framework/commit/fc302a6667f9dcce53395d01d8e6ba752ea62955))
- Support arrays in `HasOne::withDefault()` ([#16382](https://github.com/laravel/framework/pull/16382))
- Define route basename for resources ([#16352](https://github.com/laravel/framework/pull/16352))
- Added `$fallback` parameter to `Redirector::back()` ([#16426](https://github.com/laravel/framework/pull/16426))
- Added support for footer and markdown in `SlackAttachment` ([#16451](https://github.com/laravel/framework/pull/16451))
- Added password change feedback auth stubs ([#16461](https://github.com/laravel/framework/pull/16461))
- Added `name` to default register route ([#16480](https://github.com/laravel/framework/pull/16480))
- Added `ServiceProvider::loadRoutesFrom()` method ([#16483](https://github.com/laravel/framework/pull/16483))

### Changed
- Use `getKey()` instead of `$id` in `PusherBroadcaster` ([#16438](https://github.com/laravel/framework/pull/16438))

### Fixed
- Pass `PheanstalkJob` to Pheanstalk's `delete()` method ([#16415](https://github.com/laravel/framework/pull/16415))
- Don't call PDO callback in `reconnectIfMissingConnection()` until it is needed ([#16422](https://github.com/laravel/framework/pull/16422))
- Don't timeout queue if `--timeout` is set to `0` ([#16465](https://github.com/laravel/framework/pull/16465))
- Respect `--force` option of `queue:work` in maintenance mode ([#16468](https://github.com/laravel/framework/pull/16468))


## v5.3.23 (2016-11-14)

### Added
- Added database slave failover ([#15553](https://github.com/laravel/framework/pull/15553), [ed28c7f](https://github.com/laravel/framework/commit/ed28c7fa11d3754d618606bf8fc2f00690cfff66))
- Added `Arr::shuffle($array)` ([ed28c7f](https://github.com/laravel/framework/commit/ed28c7fa11d3754d618606bf8fc2f00690cfff66))
- Added `prepareForValidation()` method to `FormRequests` ([#16238](https://github.com/laravel/framework/pull/16238))
- Support SparkPost transports options to be set at runtime ([#16254](https://github.com/laravel/framework/pull/16254))
- Support setting `$replyTo` for email notifications ([#16277](https://github.com/laravel/framework/pull/16277))
- Support `url` configuration parameter to generate filesystem disk URL ([#16281](https://github.com/laravel/framework/pull/16281), [dcff158](https://github.com/laravel/framework/commit/dcff158c63093523eadffc34a9ba8c1f8d4e53c0))
- Allow `SerializesModels` to restore models excluded by global scope ([#16301](https://github.com/laravel/framework/pull/16301))
- Allow loading specific columns while eager-loading Eloquent relationships ([#16327](https://github.com/laravel/framework/pull/16327))
- Allow Eloquent `HasOne` relationships to return a "default model" ([#16198](https://github.com/laravel/framework/pull/16198), [9b59f67](https://github.com/laravel/framework/commit/9b59f67daeb63bad11af9b70b4a35c6435240ff7))
- Allow `SlackAttachment` color override ([#16360](https://github.com/laravel/framework/pull/16360))
- Allow chaining factory calls to `define()` and `state()` ([#16389](https://github.com/laravel/framework/pull/16389))

### Changed
- Dried-up console parser and extract token parsing ([#16197](https://github.com/laravel/framework/pull/16197))
- Support empty array for query builder `orders` property ([#16225](https://github.com/laravel/framework/pull/16225))
- Properly handle filling JSON attributes on Eloquent models ([#16228](https://github.com/laravel/framework/pull/16228))
- Use `forceReconnection()` method in `Mailer` ([#16298](https://github.com/laravel/framework/pull/16298))
- Double-quote MySQL JSON expressions ([#16308](https://github.com/laravel/framework/pull/16308))
- Moved login attempt code to separate method ([#16317](https://github.com/laravel/framework/pull/16317))
- Escape the RegExp delimiter in `Validator::getExplicitKeys()` ([#16309](https://github.com/laravel/framework/pull/16309))
- Use default Slack colors in `SlackMessage` ([#16345](https://github.com/laravel/framework/pull/16345))
- Support presence channels names containing the words `private-` or `presence-` ([#16353](https://github.com/laravel/framework/pull/16353))
- Fail test, instead of throwing an exception when `seeJson()` fails ([#16350](https://github.com/laravel/framework/pull/16350))
- Call `sendPerformed()` for all mail transports ([#16366](https://github.com/laravel/framework/pull/16366))
- Add `X-SES-Message-ID` header in `SesTransport::send()` ([#16366](https://github.com/laravel/framework/pull/16366))
- Throw `BroadcastException` when `PusherBroadcaster::broadcast()` fails ([#16398](https://github.com/laravel/framework/pull/16398))

### Fixed
- Catch errors when handling a failed job ([#16212](https://github.com/laravel/framework/pull/16212))
- Return array from `Translator::sortReplacements()` ([#16221](https://github.com/laravel/framework/pull/16221))
- Don't use multi-byte functions in `UrlGenerator::to()` ([#16081](https://github.com/laravel/framework/pull/16081))
- Support configuration files as symbolic links ([#16080](https://github.com/laravel/framework/pull/16080))
- Fixed wrapping and escaping in SQL Server `dropIfExists()` ([#16279](https://github.com/laravel/framework/pull/16279))
- Throw `ManuallyFailedException` if `InteractsWithQueue::fail()` is called manually ([#16318](https://github.com/laravel/framework/pull/16318), [a20fa97](https://github.com/laravel/framework/commit/a20fa97445be786f9f5f09e2e9b905a00064b2da))
- Catch `Throwable` in timezone validation ([#16344](https://github.com/laravel/framework/pull/16344))
- Fixed `Auth::onceUsingId()` by reversing the order of retrieving the id in `SessionGuard` ([#16373](https://github.com/laravel/framework/pull/16373))
- Fixed bindings on update statements with advanced joins ([#16368](https://github.com/laravel/framework/pull/16368))


## v5.3.22 (2016-11-01)

### Added
- Added support for carbon-copy in mail notifications ([#16152](https://github.com/laravel/framework/pull/16152))
- Added `-r` shortcut to `make:controller` command ([#16141](https://github.com/laravel/framework/pull/16141))
- Added `HasDatabaseNotifications::readNotifications()` method ([#16164](https://github.com/laravel/framework/pull/16164))
- Added `broadcastOn()` method to allow notifications to be broadcasted to custom channels ([#16170](https://github.com/laravel/framework/pull/16170))

### Changed
- Avoid extraneous database query when last `chunk()` is partial ([#16180](https://github.com/laravel/framework/pull/16180))
- Return unique middleware stack from `Route::gatherMiddleware()` ([#16185](https://github.com/laravel/framework/pull/16185))
- Return early when `Collection::chunk()` size zero or less ([#16206](https://github.com/laravel/framework/pull/16206), [46ebd7f](https://github.com/laravel/framework/commit/46ebd7fa1f35eeb37af891abfc611f7262c91c29))

### Fixed
- Bind `double` as `PDO::PARAM_INT` on MySQL connections ([#16069](https://github.com/laravel/framework/pull/16069))


## v5.3.21 (2016-10-26)

### Added
- Added `ResetsPasswords::validationErrorMessages()` method ([#16111](https://github.com/laravel/framework/pull/16111))

### Changed
- Use `toString()` instead of `(string)` on UUIDs for notification ids ([#16109](https://github.com/laravel/framework/pull/16109))

### Fixed
- Don't hydrate files in `Validator` ([#16105](https://github.com/laravel/framework/pull/16105))

### Removed
- Removed `-q` shortcut from `make:listener` command ([#16110](https://github.com/laravel/framework/pull/16110))


## v5.3.20 (2016-10-25)

### Added
- Added `--resource` (or `-r`) option to `make:model` command ([#15993](https://github.com/laravel/framework/pull/15993))
- Support overwriting channel name for broadcast notifications ([#16018](https://github.com/laravel/framework/pull/16018), [4e30db5](https://github.com/laravel/framework/commit/4e30db5fbc556f7925130f9805f2dec47592719e))
- Added `Macroable` trait to `Rule` ([#16028](https://github.com/laravel/framework/pull/16028))
- Added `Session::remember()` helper ([#16041](https://github.com/laravel/framework/pull/16041))
- Added option shorthands to `make:listener` command ([#16038](https://github.com/laravel/framework/pull/16038))
- Added `RegistersUsers::registered()` method ([#16036](https://github.com/laravel/framework/pull/16036))
- Added `ResetsPasswords::rules()` method ([#16060](https://github.com/laravel/framework/pull/16060))
- Added `$page` parameter to `simplePaginate()` in `BelongsToMany` and `HasManyThrough` ([#16075](https://github.com/laravel/framework/pull/16075))

### Changed
- Catch `dns_get_record()` exceptions in `validateActiveUrl()` ([#15979](https://github.com/laravel/framework/pull/15979))
- Allow reconnect during database transactions ([#15931](https://github.com/laravel/framework/pull/15931))
- Use studly case for controller names generated by `make:model` command ([#15988](https://github.com/laravel/framework/pull/15988))
- Support objects that are castable to strings in `Collection::keyBy()` ([#16001](https://github.com/laravel/framework/pull/16001))
- Switched to using a static object to collect console application bootstrappers that need to run on Artisan starting ([#16012](https://github.com/laravel/framework/pull/16012))
- Return unique middleware stack in `SortedMiddleware::sortMiddleware()` ([#16034](https://github.com/laravel/framework/pull/16034))
- Allow methods inside `@foreach` and `@forelse` expressions ([#16087](https://github.com/laravel/framework/pull/16087))
- Improved Scheduler parameter escaping ([#16088](https://github.com/laravel/framework/pull/16088))

### Fixed
- Fixed `session_write_close()` on PHP7 ([#15968](https://github.com/laravel/framework/pull/15968))
- Fixed ambiguous id issues when restoring models with eager loaded / joined query ([#15983](https://github.com/laravel/framework/pull/15983))
- Fixed integer and double support in `JsonExpression` ([#16068](https://github.com/laravel/framework/pull/16068))
- Fixed UUIDs when queueing notifications ([18d26df](https://github.com/laravel/framework/commit/18d26df24f1f3b17bd20c7244d9b85d273138d79))
- Fixed empty session issue when the session file is being accessed simultaneously ([#15998](https://github.com/laravel/framework/pull/15998))

### Removed
- Removed `Requests` import from controller stubs ([#16011](https://github.com/laravel/framework/pull/16011))
- Removed unnecessary validation feedback for password confirmation field ([#16100](https://github.com/laravel/framework/pull/16100))


## v5.3.19 (2016-10-17)

### Added
- Added `--controller` (or `-c`) option to `make:model` command ([#15795](https://github.com/laravel/framework/pull/15795))
- Added object based `dimensions` validation rule ([#15852](https://github.com/laravel/framework/pull/15852))
- Added object based `in` and `not_in` validation rule ([#15923](https://github.com/laravel/framework/pull/15923), [#15951](https://github.com/laravel/framework/pull/15951), [336a807](https://github.com/laravel/framework/commit/336a807ee56de27adcb3f9d34b337300520568ac))
- Added `clear-compiled` command success message ([#15868](https://github.com/laravel/framework/pull/15868))
- Added `SlackMessage::http()` to specify additional `headers` or `proxy` options ([#15882](https://github.com/laravel/framework/pull/15882))
- Added a name to the logout route ([#15889](https://github.com/laravel/framework/pull/15889))
- Added "feedback" to `Pluralizer::uncountable()` ([#15895](https://github.com/laravel/framework/pull/15895))
- Added `FormRequest::withValidator($validator)` hook ([#15918](https://github.com/laravel/framework/pull/15918), [bf8a36a](https://github.com/laravel/framework/commit/bf8a36ac3df03a2c889cbc9aa535e5cf9ff48777))
- Add missing `ClosureCommand::$callback` property ([#15956](https://github.com/laravel/framework/pull/15956))

### Changed
- Total rewrite of middleware sorting logic ([6b69fb8](https://github.com/laravel/framework/commit/6b69fb81fc7c36e9e129a0ce2e56a824cc907859), [9cc5334](https://github.com/laravel/framework/commit/9cc5334d00824441ccce5e9d2979723e41b2fc05))
- Wrap PostgreSQL database schema changes in a transaction ([#15780](https://github.com/laravel/framework/pull/15780), [#15962](https://github.com/laravel/framework/pull/15962))
- Expect `array` on `Validator::explodeRules()` ([#15838](https://github.com/laravel/framework/pull/15838))
- Return `null` if an empty key was passed to `Model::getAttribute()` ([#15874](https://github.com/laravel/framework/pull/15874))
- Support multiple `LengthAwarePaginator` on a single page with different `$pageName` properties ([#15870](https://github.com/laravel/framework/pull/15870))
- Pass ids to `ModelNotFoundException` ([#15896](https://github.com/laravel/framework/pull/15896))
- Improved database transaction logic ([7a0832b](https://github.com/laravel/framework/commit/7a0832bb44057f1060c96c2e01652aae7c583323))
- Use `name()` method instead of `getName()` ([#15955](https://github.com/laravel/framework/pull/15955))
- Minor syntax improvements ([#15953](https://github.com/laravel/framework/pull/15953), [#15954](https://github.com/laravel/framework/pull/15954), [4e9c9fd](https://github.com/laravel/framework/commit/4e9c9fd98b4dff71f449764e87c52577e2634587))

### Fixed
- Fixed `migrate:status` using another connection ([#15824](https://github.com/laravel/framework/pull/15824))
- Fixed calling closure based commands ([#15873](https://github.com/laravel/framework/pull/15873))
- Split `SimpleMessage` by all possible EOLs ([#15921](https://github.com/laravel/framework/pull/15921))
- Ensure that the search and the creation/update of Eloquent instances happens on the same connection ([#15958](https://github.com/laravel/framework/pull/15958))


## v5.3.18 (2016-10-07)

### Added
- Added object based `unique` and `exists` validation rules ([#15809](https://github.com/laravel/framework/pull/15809))

### Changed
- Added primary key to `migrations` table ([#15770](https://github.com/laravel/framework/pull/15770))
- Simplified `route:list` command code ([#15802](https://github.com/laravel/framework/pull/15802), [cb2eb79](https://github.com/laravel/framework/commit/cb2eb7963b29aafe63c87e1d2b1e633ecd0c25b0))

### Fixed
- Use eloquent collection for proper serialization of [#15789](https://github.com/laravel/framework/pull/15789) ([1c78e00](https://github.com/laravel/framework/commit/1c78e00ef3815e7b0bf710037b52faefb464e97d))
- Reverted [#15722](https://github.com/laravel/framework/pull/15722) ([#15813](https://github.com/laravel/framework/pull/15813))


## v5.3.17 (2016-10-06)

### Added
- Added model factory "states" ([#14241](https://github.com/laravel/framework/pull/14241))

### Changed
- `Collection::only()` now returns all items if `$keys` is `null` ([#15695](https://github.com/laravel/framework/pull/15695))

### Fixed
- Added workaround for Memcached 3 on PHP7 when using `many()` ([#15739](https://github.com/laravel/framework/pull/15739))
- Fixed bug in `Validator::hydrateFiles()` when removing the files array ([#15663](https://github.com/laravel/framework/pull/15663))
- Fixed model factory bug when `$amount` is zero ([#15764](https://github.com/laravel/framework/pull/15764), [#15779](https://github.com/laravel/framework/pull/15779))
- Prevent multiple notifications getting sent out when using the `Notification` facade ([#15789](https://github.com/laravel/framework/pull/15789))


## v5.3.16 (2016-10-04)

### Added
- Added "furniture" and "wheat" to `Pluralizer::uncountable()` ([#15703](https://github.com/laravel/framework/pull/15703))
- Allow passing `$keys` to `Model::getAttributes()` ([#15722](https://github.com/laravel/framework/pull/15722))
- Added database blueprint for soft deletes with timezone ([#15737](https://github.com/laravel/framework/pull/15737))
- Added given guards to `AuthenticationException` ([#15745](https://github.com/laravel/framework/pull/15745))
- Added [Seneca](https://en.wikipedia.org/wiki/Seneca_the_Younger) quote to `Inspire` command ([#15747](https://github.com/laravel/framework/pull/15747))
- Added `div#app` to auth layout stub ([08bcbdb](https://github.com/laravel/framework/commit/08bcbdbe70b69330943cc45625b160877b37341a))
- Added PHP 7.1 timeout handler to queue worker ([cc9e1f0](https://github.com/laravel/framework/commit/cc9e1f09683fd23cf8e973e84bf310f7ce1304a2))

### Changed
- Changed visibility of `Route::getController()` to public ([#15678](https://github.com/laravel/framework/pull/15678))
- Changed notifications `id` column type to `uuid` ([#15719](https://github.com/laravel/framework/pull/15719))

### Fixed
- Fixed PDO bindings when using `whereHas()` ([#15740](https://github.com/laravel/framework/pull/15740))


## v5.3.15 (2016-09-29)

### Changed
- Use granular notification queue jobs ([#15681](https://github.com/laravel/framework/pull/15681), [3a5e510](https://github.com/laravel/framework/commit/3a5e510af5e92ab2eaa25d728b8c74d9cf8833c2))
- Reverted recent changes to the queue ([d8dc8dc](https://github.com/laravel/framework/commit/d8dc8dc4bde56f63d8b1eacec3f3d4d68cc51894))


## v5.3.14 (2016-09-29)

### Fixed
- Fixed `DaemonCommand` command name ([b681bff](https://github.com/laravel/framework/commit/b681bffc247ebac1fbb4afcec03e2ce12627e0cc))


## v5.3.13 (2016-09-29)

### Added
- Added `serialize()` and `unserialize()` on `RedisStore` ([#15657](https://github.com/laravel/framework/pull/15657))

### Changed
- Use `$signature` command style on `DaemonCommand` and `WorkCommand` ([#15677](https://github.com/laravel/framework/pull/15677))


## v5.3.12 (2016-09-29)

### Added
- Added support for priority level in mail notifications ([#15651](https://github.com/laravel/framework/pull/15651))
- Added missing `$minutes` property on `CookieSessionHandler` ([#15664](https://github.com/laravel/framework/pull/15664))

### Changed
- Removed forking and PCNTL requirements while still supporting timeouts ([#15650](https://github.com/laravel/framework/pull/15650))
- Set exception handler first thing in `WorkCommand::runWorker()` ([99994fe](https://github.com/laravel/framework/commit/99994fe23c1215d5a8e798da03947e6a5502b8f9))


## v5.3.11 (2016-09-27)

### Added
- Added `Kernel::setArtisan()` method ([#15531](https://github.com/laravel/framework/pull/15531))
- Added a default method for validation message variable replacing ([#15527](https://github.com/laravel/framework/pull/15527))
- Added support for a schema array in Postgres config ([#15535](https://github.com/laravel/framework/pull/15535))
- Added `SoftDeletes::isForceDeleting()` method ([#15580](https://github.com/laravel/framework/pull/15580))
- Added support for tasks scheduling using command classes instead of signatures ([#15591](https://github.com/laravel/framework/pull/15591))
- Added support for passing array of emails/user-objects to `Mailable::to()` ([#15603](https://github.com/laravel/framework/pull/15603))
- Add missing interface methods in `Registrar` contract ([#15616](https://github.com/laravel/framework/pull/15616))

### Changed
- Let the queue worker sleep for 1s when app is down for maintenance ([#15520](https://github.com/laravel/framework/pull/15520))
- Improved validator messages for implicit attributes errors ([#15538](https://github.com/laravel/framework/pull/15538))
- Use `Carbon::now()->getTimestamp()` instead of `time()` in various places ([#15544](https://github.com/laravel/framework/pull/15544), [#15545](https://github.com/laravel/framework/pull/15545), [c5984af](https://github.com/laravel/framework/commit/c5984af3757e492c6e79cef161169ea09b5b9c7a), [#15549](https://github.com/laravel/framework/pull/15549))
- Removed redundant condition from `updateOrInsert()` ([#15540](https://github.com/laravel/framework/pull/15540))
- Throw `LogicException` on container alias loop ([#15548](https://github.com/laravel/framework/pull/15548))
- Handle empty `$files` in `Request::duplicate()` ([#15558](https://github.com/laravel/framework/pull/15558))
- Support exact matching of custom validation messages ([#15557](https://github.com/laravel/framework/pull/15557))

### Fixed
- Decode URL in `Request::segments()` and `Request::is()` ([#15524](https://github.com/laravel/framework/pull/15524))
- Replace only the first instance of the app namespace in Generators ([#15575](https://github.com/laravel/framework/pull/15575))
- Fixed artisan `--env` issue where environment file wasn't loaded ([#15629](https://github.com/laravel/framework/pull/15629))
- Fixed migration with comments using `ANSI_QUOTE` SQL mode ([#15620](https://github.com/laravel/framework/pull/15620))
- Disabled queue worker process forking until it works with AWS SQS ([23c1276](https://github.com/laravel/framework/commit/23c12765557ebc5e3c35ad024d645620f7b907d6))


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
- Try to reconnect if connection is lost during database transaction ([#15511](https://github.com/laravel/framework/pull/15511))
- Renamed `InteractsWithQueue::failed()` to `fail()` ([e1d60e0](https://github.com/laravel/framework/commit/e1d60e0fe120a7898527fb997aa2fb9de263190c))

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
