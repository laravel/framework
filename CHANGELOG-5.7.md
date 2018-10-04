# Release Notes for 5.7.x

## [v5.7.8 (2018-10-04)](https://github.com/laravel/framework/compare/v5.7.7...v5.7.8)

### Added
- Add `--step` to `migrate:fresh` command ([#25897](https://github.com/laravel/framework/pull/25897))
- Allow `destroy` method in `Model` to accept a collection of ids ([#25878](https://github.com/laravel/framework/pull/25878))
- Add AsPivot trait ([#25851](https://github.com/laravel/framework/pull/25851))

### Fixed
- Fixed wrap table for sql server ([#25896](https://github.com/laravel/framework/pull/25896))

### Changed
- Use "optimize:clear" in "app:name" command ([#25922](https://github.com/laravel/framework/pull/25922))
- Revert of "html string support in translator" ([e626ab32a4afec90f80641fbcd00e6b79d15cd3a](https://github.com/laravel/framework/commit/e626ab32a4afec90f80641fbcd00e6b79d15cd3a))

### Changed (only realization)
- Simplify code for contextual binding ([e2476c1cdfeffd1c4432ec8dc1f733815f70c000](https://github.com/laravel/framework/commit/e2476c1cdfeffd1c4432ec8dc1f733815f70c000))


## [v5.7.7 (2018-10-02)](https://github.com/laravel/framework/compare/v5.7.6...v5.7.7)

### Added
- Allow array callables to be passed to Gate::before() ([#25817](https://github.com/laravel/framework/pull/25817))
- Mail recipient and notifiable can set preferred locale ([#25752](https://github.com/laravel/framework/pull/25752))
- Always show seeder info ([#25872](https://github.com/laravel/framework/pull/25872))
- Support JSON UPDATE queries on PostgreSQL ([#25797](https://github.com/laravel/framework/pull/25797))
- Makes sure changing a database field to JSON does not include a collation ([#25741](https://github.com/laravel/framework/pull/25741))
- Added Queued Closures ([#25777](https://github.com/laravel/framework/pull/25777))
- Add the ability to skip algorithm checking ([#25468](https://github.com/laravel/framework/pull/25468), [5fd4b899cc42d266fab34ee2d5f92fb47ca34fd0](https://github.com/laravel/framework/commit/5fd4b899cc42d266fab34ee2d5f92fb47ca34fd0))
- Add queue create payload hook ([3f68cbe3df82990c69e34309901fcefefdb65c95](https://github.com/laravel/framework/commit/3f68cbe3df82990c69e34309901fcefefdb65c95))
- Authorize Middleware Accept String Parameters ([#25763](https://github.com/laravel/framework/pull/25763))

### Fixed
- Fix `each` method on BelongsToMany relationships ([#25832](https://github.com/laravel/framework/pull/25832))
- Fix prefixed table indexes ([#25867](https://github.com/laravel/framework/pull/25867))
- Fix `be` method in `InteractsWithAuthentication` trait ([#25873](https://github.com/laravel/framework/pull/25873))
- Fixes the error when $resource is null ([#25838](https://github.com/laravel/framework/pull/25838))
- Attach all disk attachments and not only first one in the `Mail/Mailable.php` ([#25793](https://github.com/laravel/framework/pull/25793))
- Fixed: in case if one job throw exception, than we will proceed to next one ([#25820](https://github.com/laravel/framework/pull/25820))

### Changed
- Trim model class name when passing in `Authorize.php` middleware ([#25849](https://github.com/laravel/framework/pull/25849))
- Improve JSON UPDATE queries on MySQL ([#25794](https://github.com/laravel/framework/pull/25794))
- Don't print the generated application key ([#25802](https://github.com/laravel/framework/pull/25802))
- Improve "exists" validation with array values ([#25819](https://github.com/laravel/framework/pull/25819))
- Only escape trans parameters ([98046cb0c81b418fb4046ade034f3d33a4172239](https://github.com/laravel/framework/commit/98046cb0c81b418fb4046ade034f3d33a4172239))
- Added type check for assertExitCode(0) ([#25847](https://github.com/laravel/framework/pull/25847))

### Changed (only realization)
- Simplify `save` method `MorphOneOrMany` relation ([#25864](https://github.com/laravel/framework/pull/25864))


## [v5.7.6 (2018-09-25)](https://github.com/laravel/framework/compare/v5.7.5...v5.7.6)

### Added
- Support MorphTo eager loading with selected columns ([#25662](https://github.com/laravel/framework/pull/25662))
- Added possibility to define a complex condition (overwrite `shouldAddXsrfTokenCookie` method) for add cookie to response in `Middleware/VerifyCsrfToken.php`

### Fixed
- Fixed tag cache clearing when using Redis ([#25744](https://github.com/laravel/framework/pull/25744))
- Fixed broken email subcopy template escaping ([#25723](https://github.com/laravel/framework/pull/25723))
- Fixed MethodNotAllowedHTTPException on Intended Redirect ([#25739](https://github.com/laravel/framework/pull/25739))

### Changed
- Use url() function instead of plain url in `views/illustrated-layout.blade.php` ([25725](https://github.com/laravel/framework/pull/25725))


## [v5.7.5 (2018-09-20)](https://github.com/laravel/framework/compare/v5.7.4...v5.7.5)

### Added
- Add callback hook for building mailable data in `\Illuminate\Mail\Mailable` ([7dc3d8d35ad8bcd3b18334a44320e3162b9f6dc1](https://github.com/laravel/framework/commit/7dc3d8d35ad8bcd3b18334a44320e3162b9f6dc1))

### Fixed
- Make any column searchable with `like` in PostgreSQL ([#25698](https://github.com/laravel/framework/pull/25698))
- Remove trailing newline from hot url in `mix` helper ([#25699](https://github.com/laravel/framework/pull/25699))

### Changed 
- Revert of "Remove `Hash::check()` for password verification" ([2e78bf472832cd68ef7d80c73dbb722a62ee1429](https://github.com/laravel/framework/commit/2e78bf472832cd68ef7d80c73dbb722a62ee1429)) 
 
 
## [v5.7.4 (2018-09-18)](https://github.com/laravel/framework/compare/v5.7.3...v5.7.4)

### Added
- Add 'verified' session boolean in `VerifiesEmails::verify` action ([#25638](https://github.com/laravel/framework/pull/25638))
- Add Nelson Mandela to Inspirational Quotes ([#25599](https://github.com/laravel/framework/pull/25599))
- Add `streamedContent` to `TestResponse` class ([#25469](https://github.com/laravel/framework/pull/25469), [#b3f583cd5efbc9e1b9482b00a7c22b00324e936e](https://github.com/laravel/framework/commit/b3f583cd5efbc9e1b9482b00a7c22b00324e936e))

### Fixed
- Fix app stub when register route option is set to false ([#25582](https://github.com/laravel/framework/pull/25582))
- Fix artisan PendingCommand run method return value ([#25577](https://github.com/laravel/framework/pull/25577))
- Support custom accessor on `whenPivotLoaded()` ([#25661](https://github.com/laravel/framework/pull/25661))

### Changed
- Remove `Hash::check()` for password verification ([#25677](https://github.com/laravel/framework/pull/25677))


## [v5.7.3 (2018-09-11)](https://github.com/laravel/framework/compare/v5.7.2...v5.7.3)

### Changed
- `__toString` method in `Illuminate/Auth/Access/Response.php` ([#25539](https://github.com/laravel/framework/pull/25539))
- Do not pass the guard instance to the authentication events ([#25568](https://github.com/laravel/framework/pull/25568))
- Call Pending artisan command immediately ([#25574](https://github.com/laravel/framework/pull/25574), [#d54ffa594b968b6c9a7cf716f5c73758a7d36824](https://github.com/laravel/framework/commit/d54ffa594b968b6c9a7cf716f5c73758a7d36824))
- Use `request()` method when we called Guzzle ClientInterface ([#25490](https://github.com/laravel/framework/pull/25490))
- Replace all placeholders for comparison rules (`gt`/`gte`/`lt`/`lte`) properly ([#25513](https://github.com/laravel/framework/pull/25513))

### Added
- Add `storeOutput` method to `Illuminate/Console/Scheduling/Event.php` ([#70a72fcac9d8852fc1a4ce11eb47842774c11876](https://github.com/laravel/framework/commit/70a72fcac9d8852fc1a4ce11eb47842774c11876))
- Add `ensureOutputIsBeingCaptured` method to `Illuminate/Console/Scheduling/Event.php`
- Add options for SES Mailer ([#25536](https://github.com/laravel/framework/pull/25536))
- Add Ability to disable register route ([#25556](https://github.com/laravel/framework/pull/25556))

### Fixed
- Fix database cache on PostgreSQL ([#25530](https://github.com/laravel/framework/pull/25530))
- Fix bug with invokables in `Illuminate/Console/Scheduling/CallbackEvent.php` ([#eaac77bfb878b49f2ceff4fb09198e437d38683d](https://github.com/laravel/framework/commit/eaac77bfb878b49f2ceff4fb09198e437d38683d)) 
- Stop sending email verification if user already verified ([#25540](https://github.com/laravel/framework/pull/25540))
- Fix `withoutMockingConsoleOutput` in `Illuminate/Foundation/Testing/Concerns/InteractsWithConsole.php` ([#25499](https://github.com/laravel/framework/pull/25499))
- Fix DurationLimiter not using Redis connection proxy to call eval command ([#25505](https://github.com/laravel/framework/pull/25505))

### Deprecated
- Make `ensureOutputIsBeingCapturedForEmail` method deprecated in `Illuminate/Console/Scheduling/Event.php`
 
 
## [v5.7.2 (2018-09-06)](https://github.com/laravel/framework/compare/v5.7.1...v5.7.2)

### Added
- Added `moontoast/math` suggestion to `Support` module ([#79edf5c70c9a54c75e17da62ba3649f24b874e09](https://github.com/laravel/framework/commit/79edf5c70c9a54c75e17da62ba3649f24b874e09))
- Send an event when the user's email is verified ([#045cbfd95c611928aef1b877d1a3dc60d5f19580](https://github.com/laravel/framework/commit/045cbfd95c611928aef1b877d1a3dc60d5f19580))
- Allow email verification middleware to work with API routes ([#0e23b6afa4d1d8b440ce7696a23fa770b4f7e5e3](https://github.com/laravel/framework/commit/0e23b6afa4d1d8b440ce7696a23fa770b4f7e5e3))
- Add Builder::whereJsonLength() ([#5e33a96cd5fe9f5bea953a3e07ec827d5f19a9a3](https://github.com/laravel/framework/commit/5e33a96cd5fe9f5bea953a3e07ec827d5f19a9a3), [#f149fbd0fede21fc3a8c0347d1ab9ee858727bb4](https://github.com/laravel/framework/commit/f149fbd0fede21fc3a8c0347d1ab9ee858727bb4))
- Pass configuration key parameter to updatePackageArray in Preset ([#25457](https://github.com/laravel/framework/pull/25457))
- Let the WorkCommand specify whether to stop when queue is empty ([#2524c5ee89a0c5e6e4e65c13d5f9945075bb299c](https://github.com/laravel/framework/commit/2524c5ee89a0c5e6e4e65c13d5f9945075bb299c))

### Changed
- Make email verification scaffolding translatable ([#25473](https://github.com/laravel/framework/pull/25473))
- Do not mock console output by default ([#b4339702dbdc5f1f55f30f1e6576450f6277e3ae](https://github.com/laravel/framework/commit/b4339702dbdc5f1f55f30f1e6576450f6277e3ae))
- Allow daemon to stop when there is no more jobs in the queue ([#157a15080b95b26b2ccb0677dceab4964e25f18d](https://github.com/laravel/framework/commit/157a15080b95b26b2ccb0677dceab4964e25f18d))
  
### Fixed
- Do not send email verification if user is already verified ([#25450](https://github.com/laravel/framework/pull/25450))
- Fixed required carbon version ([#394f79f9a6651b103f6e065cb4470b4b347239ea](https://github.com/laravel/framework/commit/394f79f9a6651b103f6e065cb4470b4b347239ea))


## [v5.7.1 (2018-09-04)](https://github.com/laravel/framework/compare/v5.7.0...v5.7.1)

### Fixed
- Fixed an issue with basic auth when no field is defined

### Changed
- Remove X-UA-Compatible meta tag ([#25442](https://github.com/laravel/framework/pull/25442))
- Added default array value for redis config ([#25443](https://github.com/laravel/framework/pull/25443))

## [v5.7.0 (2018-09-04)](https://github.com/laravel/framework/compare/5.6...v5.7.0)

Check the upgrade guide in the [Official Laravel Documentation](https://laravel.com/docs/5.7/upgrade).
