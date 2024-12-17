# Release Notes for 11.x

## [Unreleased](https://github.com/laravel/framework/compare/v11.36.0...11.x)

## [v11.36.0](https://github.com/laravel/framework/compare/v11.35.1...v11.36.0) - 2024-12-17

* [11.x] Update `config/mail.php` with supported configuration by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53874
* [11.x] Allows `enum_value()` to be use in standalone `illuminate/collections` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53871
* [11.x] `Uri` and `UriQueryString` implement `Stringable` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53873
* [11.x] Prefer `new Stringable` over `Str::of` and `str()` by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53883
* [11.x] No need to redeclare variables by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53887
* [11.x] Add PHP 8.4 with herd to passthrough variables by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53885
* Add new `Uri` class to default, global aliases by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/53884
* [11.x] Fix attribute mutator access in `loadMissing` by [@SychO9](https://github.com/SychO9) in https://github.com/laravel/framework/pull/53879
* [11.x] Fix `numericAggregate`  on eloquent builder by [@AmirRezaM75](https://github.com/AmirRezaM75) in https://github.com/laravel/framework/pull/53880
* [11.x] Prefer `new Fluent` over `fluent()` helper by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53890
* Patch by [@angelej](https://github.com/angelej) in https://github.com/laravel/framework/pull/53869
* [11.x] `Collection::wrap` by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53891
* [11.x] Bump minimum league/commonmark by [@ah-rahimi](https://github.com/ah-rahimi) in https://github.com/laravel/framework/pull/53899
* [11.x] `Collection::range` by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53895
* [11.x] Added an event that reports files being deleted when calling the `schema:dump --prune` command by [@andrey-helldar](https://github.com/andrey-helldar) in https://github.com/laravel/framework/pull/53870
* [11.x] fix: allows injection using multiple interfaces with the same concrete implementation by [@jamiethorpe](https://github.com/jamiethorpe) in https://github.com/laravel/framework/pull/53275
* [11.x] Early return in Factory::modelName() by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53912
* [11.x] Prevent `blank` Helper from Serializing Eloquent Models by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/53911
* [11.x] Add word-break to mail links by [@seblavoie](https://github.com/seblavoie) in https://github.com/laravel/framework/pull/53906
* Preserve dynamic database connections on reconnect by [@nickakitch](https://github.com/nickakitch) in https://github.com/laravel/framework/pull/53914
* Fix mutexName inconsistency caused by different PHP binary paths on multiple servers by [@waska14](https://github.com/waska14) in https://github.com/laravel/framework/pull/53811
* [11.x] Add `Fluent::set` method by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53946
* [11.x] Fix inspecting columns of raw indexes by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/53945
* [11.x] Allow easier overriding of the exception thrown by invalid ID in route binding by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53944
* [11.x] Fix client path value in file uploads by [@gyaaniguy](https://github.com/gyaaniguy) in https://github.com/laravel/framework/pull/53941

## [v11.35.1](https://github.com/laravel/framework/compare/v11.35.0...v11.35.1) - 2024-12-12

* [11.x] Fix incorrect typechange in `Illuminate\Database\Query\Builder` by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/53841
* Fixed Enum usage in whereHasMorph condition when morph attribute casting by [@Outsidaz](https://github.com/Outsidaz) in https://github.com/laravel/framework/pull/53839
* [11.x] Fix unescaped table names issue of `DatabaseTruncation` trait by introducing `Connection::withoutTablePrefix()` method by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/53842
* Specify node when sending scan to RedisCluster by [@bentleyo](https://github.com/bentleyo) in https://github.com/laravel/framework/pull/53837
* [11.x] fix: cast session lifetime to int by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/53846
* [11.x] allow sub second timeout value for http requests by [@mihaileu](https://github.com/mihaileu) in https://github.com/laravel/framework/pull/53850
* Revert "set schema to smtps if MAIL_ENCRYPTION === tls" by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53863

## [v11.35.0](https://github.com/laravel/framework/compare/v11.34.2...v11.35.0) - 2024-12-10

* [11.x] Supports Symfony 7.2 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53585
* [11.x] Fix database reconnecting logic by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/53693
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53708
* [11.x] Fix foreignIdFor() when the foreign key is a non-incrementing integer other than ULID by [@edgrosvenor](https://github.com/edgrosvenor) in https://github.com/laravel/framework/pull/53696
* [11.x] Allow sorting routes by precedence in artisan routes:list. by [@mathieutu](https://github.com/mathieutu) in https://github.com/laravel/framework/pull/53706
* [11.x] Update the message for the schedule:work command. by [@AbdelElrafa](https://github.com/AbdelElrafa) in https://github.com/laravel/framework/pull/53710
* [11.x] Support auto-discovery of PSR-17 implementations by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/53711
* [11.x] Improve Error Handler in the ProcessDriver by [@WillTorres10](https://github.com/WillTorres10) in https://github.com/laravel/framework/pull/53712
* [11.x] Comment grammar fixes by [@nexxai](https://github.com/nexxai) in https://github.com/laravel/framework/pull/53714
* [11.x] Replace get_called_class with static::class by [@fernandokbs](https://github.com/fernandokbs) in https://github.com/laravel/framework/pull/53725
* [11.x] Add the pivot's related model when creating from attributes by [@alexwass-lr](https://github.com/alexwass-lr) in https://github.com/laravel/framework/pull/53694
* [11.x] use a consistent alias for `Illuminate\Database\Eloquent\Collection` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53730
* [11.x] switch `Collection::make()` for `new Collection()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53733
* [11.x] always alias the `Illuminate\Database\Eloquent\Collection` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53735
* [11.x] convert `collect()` helper to `new Collection()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53726
* [11.x] Improves `Collection` support for enums using `firstWhere()` and `value()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53777
* [11.x] Add Conditionable Trait to Request by [@ahmeti](https://github.com/ahmeti) in https://github.com/laravel/framework/pull/53775
* [11.x] Ignore health endpoint when in maintenance mode by [@joshmanders](https://github.com/joshmanders) in https://github.com/laravel/framework/pull/53772
* [11.x] Add ability to transform `Http\Client\Response` into `Fluent` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53771
* set schema to smtps if MAIL_ENCRYPTION === tls by [@danielrona](https://github.com/danielrona) in https://github.com/laravel/framework/pull/53749
* [11.x] more consistent and readable chaining by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53748
* [11.x] Fix the RateLimiter issue when using dynamic keys by [@MilesChou](https://github.com/MilesChou) in https://github.com/laravel/framework/pull/53763
* [11.x] Add ability to customize or disable `Http\Client\RequestException` message truncation by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53734
* [11.x] Include the initial value in the return types of `reduce()` by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/53798
* [11.x] Add pingOnSuccessIf & pingOnFailureIf to Schedule handling by [@lucacastelnuovo](https://github.com/lucacastelnuovo) in https://github.com/laravel/framework/pull/53795
* [11.x] Improve PHPDoc for nullable properties in `Illuminate\Database\Query\Builder` class by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/53793
* [11.x] Remove usage of `compact()` in Container by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/53789
* [11.x] Make `Exceptions@dontTruncateRequestExceptions()` fluent by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53786
* [11.x] Make mailables tappable by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/53788
* URI by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53731
* [11.x] Require `laravel/serializable-closure` on Database component by [@patrickcarlohickman](https://github.com/patrickcarlohickman) in https://github.com/laravel/framework/pull/53822
* [11.x] use new PHP 8 `str_` functions by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53817
* [11.x] handle `password_hash()` failures better by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53821
* [11.x] remove unnecessary `return` statement by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53816
* [11.x] simplify passing arguments to `when()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53815
* [11.x] remove redundant `array_values` call by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53814
* [11.x] prefer assignment over `array_push` for 1 element by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53813
* [11.x] fix `chopStart` and `chopEnd` tests by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53812
* [11.x] remove temporary variables by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53810
* [11.x] fix `$events` docblock type by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53808
* [11.x] Fix docblock for URI by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53804
* Bump nanoid from 3.3.7 to 3.3.8 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/53831
* [11.x] use promoted properties by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53807
* Revert "[11.x] use promoted properties" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53832
* Using throw config of filesystem disks when faking by [@emulgeator](https://github.com/emulgeator) in https://github.com/laravel/framework/pull/53779
* [11.x] Fix schema names on `DatabaseTruncation` trait (PostgreSQL and SQLServer) by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/53787

## [v11.34.2](https://github.com/laravel/framework/compare/v11.34.1...v11.34.2) - 2024-11-27

* Revert "[11.x] Add non-static JsonResource wrapping" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53686
* [11.x] groupBy() return type phpdoc by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/53684
* [11.x] Fix `withoutOverlapping` for grouped scheduled closures by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/53680
* [11.x] Fix `ResendTransport` missing custom headers by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53687

## [v11.34.1](https://github.com/laravel/framework/compare/v11.34.0...v11.34.1) - 2024-11-26

* Configure cloud log socket by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53666

## [v11.34.0](https://github.com/laravel/framework/compare/v11.33.2...v11.34.0) - 2024-11-26

* [10.x] Fix append and prepend batch to chain by [@Bencute](https://github.com/Bencute) in https://github.com/laravel/framework/pull/53455
* [11.x] Allow `BackedEnum` when using `fromRoute()` in `MakesHttpRequests` by [@wietsewarendorff](https://github.com/wietsewarendorff) in https://github.com/laravel/framework/pull/53593
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53586
* [11.x] Move `$ownerKey` check for `null` to `MorphTo` as `BelongsTo` relationship will always return a `string` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53592
* Unset eloquent model's cached cast attribute by [@adamthehutt](https://github.com/adamthehutt) in https://github.com/laravel/framework/pull/53583
* [11.x] Add non-static JsonResource wrapping by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/53543
* [10.x] PHP 8.4 Code Compatibility by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53612
* Add assertCount() for fake storage by [@ahmadreza1383](https://github.com/ahmadreza1383) in https://github.com/laravel/framework/pull/53620
* Fix Paginator __construct parameter typehint for phpstan by [@Afrowson](https://github.com/Afrowson) in https://github.com/laravel/framework/pull/53615
* [11.x] Add generics for Arr::last() by [@talkinnl](https://github.com/talkinnl) in https://github.com/laravel/framework/pull/53619
* Add typed closure to bootstrappers in console application by [@MatusBoa](https://github.com/MatusBoa) in https://github.com/laravel/framework/pull/53613
* refactor: Some minor performance & readability enhancements by [@dshafik](https://github.com/dshafik) in https://github.com/laravel/framework/pull/53596
* [11.x] Improve doc blocks for interacting with enum inputs by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/53625
* [11.x] Skip object construction if no rebound callbacks are set by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/53502
* Make the bearerToken method case-insensitive by [@samtlewis](https://github.com/samtlewis) in https://github.com/laravel/framework/pull/53627
* [11.x] Fix attribute inheritance for nested scheduled groups by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/53626
* [11.x] Supports PHP 8.4 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53468
* ☁️ by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53623
* [11.x] Fix typo in docblock by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/53636
* Add Number::spellOrdinal() to spell ordinals as words. by [@joelstein](https://github.com/joelstein) in https://github.com/laravel/framework/pull/53661
* [11.x] Add PausePrompt fallback by [@jwpage](https://github.com/jwpage) in https://github.com/laravel/framework/pull/53660
* [11.x] Fix `SyntaxError` on Vite prefetch with empty assets by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/53659
* [11.x] Improved `class-string` types by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53657
* [11.x] Use `never` type for methods that always throws by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/53643
* [11.x] Adds conditional to routes by [@Boorinio](https://github.com/Boorinio) in https://github.com/laravel/framework/pull/53654
* [11.x] Make `withoutDefer` also return `$this` by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/53644
* Add shorthands for fake HTTP responses by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/53663
* Use the environment method instead of the isLocal method by [@NaokiTsuchiya](https://github.com/NaokiTsuchiya) in https://github.com/laravel/framework/pull/53638
* [11.x] Fix: Ensure generated policies return boolean values by [@Aluisio-Pires](https://github.com/Aluisio-Pires) in https://github.com/laravel/framework/pull/53632
* Bus assertempty by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/53664
* [11.x] Improve schedule group behaviors by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/53641
* [11.x] Add `Request::fluent` method by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53662
* [11.x] Support named in-memory SQLite connections by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/53635
* event name & listener callback types by [@rudiedirkx](https://github.com/rudiedirkx) in https://github.com/laravel/framework/pull/53642
* Test cleanup from #53664 by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/53672
* [11.x] Fix: Prevent invalid AWS credentials options being created by [@robchett](https://github.com/robchett) in https://github.com/laravel/framework/pull/53633
* [11.x] Expand `Support\Fluent` data access and transformation capabilities by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53665

## [v11.33.2](https://github.com/laravel/framework/compare/v11.33.1...v11.33.2) - 2024-11-19

* Support ObservedBy on parent model classes by [@adamthehutt](https://github.com/adamthehutt) in https://github.com/laravel/framework/pull/53579
* Revert "[11.x] Support DB aggregate by group" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53582
* [11.x] Fix: Improve Request Port Extraction Handling in ServeCommand.php to Prevent Artisan Command Failures  by [@ahmad-cit22](https://github.com/ahmad-cit22) in https://github.com/laravel/framework/pull/53538

## [v11.33.1](https://github.com/laravel/framework/compare/v11.33.0...v11.33.1) - 2024-11-19

* Marking password params in Database connector with SensitiveParameter attribute by [@philo23](https://github.com/philo23) in https://github.com/laravel/framework/pull/53580

## [v11.33.0](https://github.com/laravel/framework/compare/v11.32.0...v11.33.0) - 2024-11-19

* [11.x] Add "createQuietly" method by [@bramr94](https://github.com/bramr94) in https://github.com/laravel/framework/pull/53558
* [11.x] Trim log channel names by [@mathieutu](https://github.com/mathieutu) in https://github.com/laravel/framework/pull/53554
* [11.x] Fix `withoutOverlapping` via `PendingEventAttributes` proxy  by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/53553
* [11.x] Update docblocks using latest documenter by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53550
* fix: use qualifyColumn rather than assuming format by [@willtj](https://github.com/willtj) in https://github.com/laravel/framework/pull/53559
* [11.x] Add `Request::enums` method to retrieve an array of enums by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53540
* [11.x] hash the token going into the cache by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53561
* Output clean error page on health check route by [@chrispage1](https://github.com/chrispage1) in https://github.com/laravel/framework/pull/53528
* [11.x] Extract `ShowModelCommand` functionality to separate class by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53541
* Add Collection/Generator generic types to public query builder methods by [@rudiedirkx](https://github.com/rudiedirkx) in https://github.com/laravel/framework/pull/53567
* [11.x] consistent multiline constructors by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53566
* [11.x] prefer `new Collection()` over `collect()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53563
* [11.x] Add builder and collection to `ModelInspector` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53565
* [11.x] Support DB aggregate by group by [@GromNaN](https://github.com/GromNaN) in https://github.com/laravel/framework/pull/53209
* [11.x] add ability to disable relationships in factories by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53450
* Revert "fix: use qualifyColumn rather than assuming format (#53559)"  by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53568
* Bump cross-spawn from 7.0.3 to 7.0.6 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/53569
* [11.x] Removing unused var assignment in Illuminate Router by [@Carnicero90](https://github.com/Carnicero90) in https://github.com/laravel/framework/pull/53575
* [11.x] PHP 8.4 Code compatibility by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53571
* [11.x] Supports `laravel/serializable-closure` 2 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53552
* [11.x] Use getQualifiedOwnerKeyName in relations by [@willtj](https://github.com/willtj) in https://github.com/laravel/framework/pull/53573

## [v11.32.0](https://github.com/laravel/framework/compare/v11.31.0...v11.32.0) - 2024-11-15

* [11.x] Http Client: fake connection exception by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/53485
* [11.x] update the docblock of the runCommand method. by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/53490
* [11.x] Fix extensions of contextual bindings by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/53514
* Update the docblock of the fluentCommands property in the MySqlGrammar by [@tisuchi](https://github.com/tisuchi) in https://github.com/laravel/framework/pull/53509
* [11.x] Don't overwrite custom replacements for count in `trans_choice` by [@patrickrobrecht](https://github.com/patrickrobrecht) in https://github.com/laravel/framework/pull/53517
* [11.x] Allow BackedEnum when asserting redirect-routes by [@wietsewarendorff](https://github.com/wietsewarendorff) in https://github.com/laravel/framework/pull/53498
* Updates docblock for duplicates collection method to correct its return type by [@gms8994](https://github.com/gms8994) in https://github.com/laravel/framework/pull/53499
* [11.x] Add support for syncing associations with array or base collection of models by [@diaafares](https://github.com/diaafares) in https://github.com/laravel/framework/pull/53495
* [11.x] SqsQueue pushRaw options: pass to sendMessage by [@Niush](https://github.com/Niush) in https://github.com/laravel/framework/pull/53507
* [11.x] Allow BackedEnum when using redirectToRoute in ResponseFactory by [@wietsewarendorff](https://github.com/wietsewarendorff) in https://github.com/laravel/framework/pull/53518
* Improve type saftey for Config/Repository.php by [@tisuchi](https://github.com/tisuchi) in https://github.com/laravel/framework/pull/53520
* Fix issue where overwriting middleware variable when setting middleware priority by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/53504
* [11.x] Introduce method `Blueprint::rawColumn()` by [@Jacobs63](https://github.com/Jacobs63) in https://github.com/laravel/framework/pull/53496
* [11.x] Introduce Schedule Grouping by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/53427
* [11.x] Added generics to paginators by [@EranNL](https://github.com/EranNL) in https://github.com/laravel/framework/pull/53512
* Fix `unless` code comment by [@fritz-c](https://github.com/fritz-c) in https://github.com/laravel/framework/pull/53529
* [11.x] Add "head" slot to email layout by [@hivokas](https://github.com/hivokas) in https://github.com/laravel/framework/pull/53531
* [11.x] Http client: record request when faking connection exception by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/53530

## [v11.31.0](https://github.com/laravel/framework/compare/v11.30.0...v11.31.0) - 2024-11-12

* [11.x] Refactor: return Command::FAILURE by [@fernandokbs](https://github.com/fernandokbs) in https://github.com/laravel/framework/pull/53354
* Allow the Batch and Chain onQueue method to accept Backed Enums by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/53359
* Add transaction generics by [@MatusBoa](https://github.com/MatusBoa) in https://github.com/laravel/framework/pull/53357
* Add laravel default exception blade files to view:cache by [@SamuelWei](https://github.com/SamuelWei) in https://github.com/laravel/framework/pull/53353
* [11.x] Added `useCascadeTruncate` method for `PostgresGrammar` by [@korkoshko](https://github.com/korkoshko) in https://github.com/laravel/framework/pull/53343
* Add Application::removeDeferredServices method by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/53362
* Add the ability to append and prepend middleware priority from the application builder by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/53326
* Fix typo in Translator code comment by [@caendesilva](https://github.com/caendesilva) in https://github.com/laravel/framework/pull/53366
* [11.x] Handle HtmlString constructed with a null by [@sperelson](https://github.com/sperelson) in https://github.com/laravel/framework/pull/53367
* [11.x] Add `URL::forceHttps()` to enforce HTTPS scheme for URLs by [@dasundev](https://github.com/dasundev) in https://github.com/laravel/framework/pull/53381
* [11.x] Refactor and add remaining test cases for the DatabaseUuidFailedJobProviderTest class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/53408
* [11.X] Postgres Aurora failover - DetectsLostConnections by [@vifer](https://github.com/vifer) in https://github.com/laravel/framework/pull/53404
* `whereFullText` case consistency by [@parth391](https://github.com/parth391) in https://github.com/laravel/framework/pull/53395
* [11.x] Add `HasFactory` trait to `make:model` generation command using `--all` options by [@adel007gh](https://github.com/adel007gh) in https://github.com/laravel/framework/pull/53391
* Introduce support for popping items from a stackable context item by [@denjaland](https://github.com/denjaland) in https://github.com/laravel/framework/pull/53403
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53414
* [11.x] Add ability to dynamically build mailers on-demand using `Mail::build` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53411
* [11.x] Refactor and add remaining test cases for the DatabaseFailedJobProviderTest class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/53409
* [11.x] Fix error event listener in Vite prefetching by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/53439
* [11.x] Ensure datetime cache durations account for script execution time by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53431
* [11.x] Fix fluent syntax for HasManyThrough when combining HasMany followed by HasOne by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/53335
* Correct parameter type of Collection::diffKeys() and Collection::diffKeysUsing() by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/53441
* Correct parameter type of Collection::intersectByKeys() by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/53444
* Fix schema foreign ID support for tables with non-standard primary key by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/53442
* [11.x] Cache token repository by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53428
* Fix validation message when there is a parameter with escaped dot "." by [@mdmahbubhelal](https://github.com/mdmahbubhelal) in https://github.com/laravel/framework/pull/53416
* [11.x] add optional prefix for cache key by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53448
* [11.x] Do not overwrite existing link header(s) in `AddLinkHeadersForPreloadedAssets` middleware by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/53463
* [11.x] use assertTrue and assertFalse method, instead of using assertE… by [@iamyusuf](https://github.com/iamyusuf) in https://github.com/laravel/framework/pull/53453
* [11.x] Add `DB::build` method by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53464
* [11.x] Add ability to dynamically build cache repositories on-demand using `Cache::build` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/53454
* [11.x] Skip the number of connections transacting while testing to run callbacks by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/53377

## [v11.30.0](https://github.com/laravel/framework/compare/v11.29.0...v11.30.0) - 2024-10-30

* Add `$bind` parameter to `Blade::directive` by [@hossein-zare](https://github.com/hossein-zare) in https://github.com/laravel/framework/pull/53279
* [11.x] Fix `trans_choice()` when translation replacement include `|` separator by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53331
* [11.x] Allow the authorize method to accept Backed Enums directly by [@johanvanhelden](https://github.com/johanvanhelden) in https://github.com/laravel/framework/pull/53330
* [11.x] use `exists()` instead of `count()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53328
* [11.x] Docblock Improvements by [@mtlukaszczyk](https://github.com/mtlukaszczyk) in https://github.com/laravel/framework/pull/53325
* Allow for custom Postgres operators to be added by [@boris-glumpler](https://github.com/boris-glumpler) in https://github.com/laravel/framework/pull/53324
* [11.x] Support Optional Dimensions for `vector` Column Type by [@akr4m](https://github.com/akr4m) in https://github.com/laravel/framework/pull/53316
* [11.x] Test Improvements by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/53306
* [11.x] Added `dropColumnsIfExists`, `dropColumnIfExists` and `dropForeignIfExists` by [@eusonlito](https://github.com/eusonlito) in https://github.com/laravel/framework/pull/53305
* [11.x] Provide an error message for PostTooLargeException by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/53301
* [11.x] Fix integrity constraint violation on failed_jobs_uuid_unique by [@bytestream](https://github.com/bytestream) in https://github.com/laravel/framework/pull/53264
* Revert "[11.x] Added `dropColumnsIfExists`, `dropColumnIfExists` and `dropForeignIfExists`" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53338
* [11.x] Introduce `HasUniqueStringIds` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53280
* [11.x] Refactor: check for contextual attribute before getting parameter class name by [@korkoshko](https://github.com/korkoshko) in https://github.com/laravel/framework/pull/53339
* [11.x] Pick up existing views and markdowns when creating mails by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/53308
* [11.x] Add withoutDefer and withDefer testing helpers by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53340

## [v11.29.0](https://github.com/laravel/framework/compare/v11.28.1...v11.29.0) - 2024-10-22

* [10.x] Ensure headers are only attached to illuminate responses by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53019
* [11.x] Component name guessing with prefix by [@royduin](https://github.com/royduin) in https://github.com/laravel/framework/pull/53183
* [11.x] Allow list of rate limiters without requiring unique keys by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53177
* Add directive [@bool](https://github.com/bool) to Blade by [@david-valdivia](https://github.com/david-valdivia) in https://github.com/laravel/framework/pull/53179
* [11.x] Fixes handling `Js::from(collect());` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53206
* [11.x] fix PHPDoc for \Illuminate\Redis\Connections\Connection::$events by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/53211
* [11.x] fix PHPDoc for \Illuminate\Database\Connection by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/53212
* [11.x] Include class-string generics for Validator::$exception by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53210
* [11.x] Remove a few useless return void statements. by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/53225
* [11.x] Fixes phpdoc type of Number::forHumans() by [@toarupg0318](https://github.com/toarupg0318) in https://github.com/laravel/framework/pull/53218
* [11.x] Fix handling exceptions thrown in eval()'d code by [@jlabedo](https://github.com/jlabedo) in https://github.com/laravel/framework/pull/53204
* [11.x] Allow using `castAsJson()` on non default db connection during test by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53256
* Improve query builder tests by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53251
* [11.x] Fix incorrect bindings in DB::update when using a collection as a value by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53254
* fix: EloquentCollection find and unique generics by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/53239
* [11.x] Add getConnection() Method to Factory Class for Retrieving Database Connection by [@jonathanpmartins](https://github.com/jonathanpmartins) in https://github.com/laravel/framework/pull/53237
* [11.x] Add `waitUntil` method to `Process` by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/53236
* Allow Vite entry points to be merged by [@JackWH](https://github.com/JackWH) in https://github.com/laravel/framework/pull/53233
* [11.x] Add helper method to determine stray request prevention state by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/53232
* [11.x] Fix typo `$previousLCurrency` to `$previousCurrency` for clarity and consistency by [@mdariftiens](https://github.com/mdariftiens) in https://github.com/laravel/framework/pull/53261

## [v11.28.1](https://github.com/laravel/framework/compare/v11.28.0...v11.28.1) - 2024-10-16

* [11.x] Fix trim getting discarded in `ViewMakeCommand` by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/53174
* [11.x] Discard `PHP_CLI_SERVER_WORKERS` on Windows environment by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53178
* [11.x] Improves PHP 8.4 compatibility by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53182
* [11.x] Fix handling empty values passed to `enum_value()` function instead of only empty string by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53181

## [v11.28.0](https://github.com/laravel/framework/compare/v11.27.2...v11.28.0) - 2024-10-15

* [11.x] Update Authorizable methods with BackedEnum support by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/53079
* [11.x] Use `null` as default cursor value for PHP Redis by [@jayan-blutui](https://github.com/jayan-blutui) in https://github.com/laravel/framework/pull/53095
* [11.x] PHPDoc Improvements by [@schulerj89](https://github.com/schulerj89) in https://github.com/laravel/framework/pull/53097
* [11.x] Fix resource not escaped correctly in substituteBindingsIntoRawSql() by [@aedart](https://github.com/aedart) in https://github.com/laravel/framework/pull/53100
* [11.x] feat: add useful defaultLocale and defaultCurrency helpers to Number facade by [@sts-ryan-holton](https://github.com/sts-ryan-holton) in https://github.com/laravel/framework/pull/53101
* [11.x] Fix determining pivot timestamp column name(s) when parent relation missing one or both of timestamps by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/53103
* [11.x] Add phpstan assertions for last in Collection isEmpty and isNotEmpty by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/53107
* feat: interactive env:encrypt & env:decrypt by [@hhermsen](https://github.com/hhermsen) in https://github.com/laravel/framework/pull/53081
* [11.x] PHPDoc Improvements by [@schulerj89](https://github.com/schulerj89) in https://github.com/laravel/framework/pull/53109
* [11.x] Feat: remove HasFactory in model when not required by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/53104
* [11.x] Add `Illuminate\Support\enum_value` to resolve `BackedEnum` or `UnitEnum` to scalar by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53096
* [11.x] allow guessing of nested component by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/52669
* [11.x] Introduce RouteParameter attribute by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/53080
* [11.x] Refactored to use enum_value() in castBinding() by [@toarupg0318](https://github.com/toarupg0318) in https://github.com/laravel/framework/pull/53131
* [11.x] Test Improvements remove code duplication by [@toarupg0318](https://github.com/toarupg0318) in https://github.com/laravel/framework/pull/53128
* Revert "[11.x] Test Improvements remove code duplication" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/53132
* [11.x] Fix HasManyThrough::one() by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/53119
* [11.x] Console supports Laravel Prompts 0.3+ by [@edjw](https://github.com/edjw) in https://github.com/laravel/framework/pull/53136
* [11.x] PHPDoc Improvements by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/53139
* fix: make model command with folder path - factory incorrect import path by [@JeRabix](https://github.com/JeRabix) in https://github.com/laravel/framework/pull/53142
* [11.x] feat: refine return type for `throw_if` and `throw_unless` to reflect actual behavior with "falsey" values by [@crishoj](https://github.com/crishoj) in https://github.com/laravel/framework/pull/53154
* [11.x] Ensure `where` with array respects boolean by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53147
* [11.x] Gracefully handle null passwords when verifying credentials by [@gbradley](https://github.com/gbradley) in https://github.com/laravel/framework/pull/53156
* [11.x] feat: restore type-narrowing bahavior for `throw_*` helpers by [@crishoj](https://github.com/crishoj) in https://github.com/laravel/framework/pull/53164
* [11.x] Add CollectedBy attribute by [@alsterholm](https://github.com/alsterholm) in https://github.com/laravel/framework/pull/53122
* [11.x] Add successful and failed methods to `ProcessPoolResults` by [@Riley19280](https://github.com/Riley19280) in https://github.com/laravel/framework/pull/53160
* Issue with constrained() method used after foreignIdFor(), instead of table name when $table parameter is not passed uses column name by [@granitibrahimi](https://github.com/granitibrahimi) in https://github.com/laravel/framework/pull/53144

## [v11.27.2](https://github.com/laravel/framework/compare/v11.27.1...v11.27.2) - 2024-10-09

* [11.x] Fixes regression with `queue:work` Command by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53076
* [11.x] Fixes parameter declaration for `ServiceProvider::optimizes()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53074

## [v11.27.1](https://github.com/laravel/framework/compare/v11.27.0...v11.27.1) - 2024-10-08

* [11.x] Fix border overflow on theme switcher when hovering by [@mezotv](https://github.com/mezotv) in https://github.com/laravel/framework/pull/53064
* [11.x] Optimize commands registry by [@erikgaal](https://github.com/erikgaal) in https://github.com/laravel/framework/pull/52928
* [11.x] Fix laravel/framework#53071 by [@it-can](https://github.com/it-can) in https://github.com/laravel/framework/pull/53072

## [v11.27.0](https://github.com/laravel/framework/compare/v11.26.0...v11.27.0) - 2024-10-08

* [11.x] feat: narrow types for throw_if and throw_unless by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/53005
* [11.x]  Prevent calling tries() twice by [@themsaid](https://github.com/themsaid) in https://github.com/laravel/framework/pull/53010
* [11.x] Improve PHPDoc  by [@schulerj89](https://github.com/schulerj89) in https://github.com/laravel/framework/pull/53009
* [11.x] Utilise `Illuminate\Support\php_binary()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53008
* [11.x] Set HasAttributes[@casts](https://github.com/casts)() array generics by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/53024
* [11.x] Improve `Schema::hasTable()` performance by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/53006
* [11.x] Always inherit parent attributes by [@royduin](https://github.com/royduin) in https://github.com/laravel/framework/pull/53011
* [11.x] feat: introduce option to change default Number currency by [@sts-ryan-holton](https://github.com/sts-ryan-holton) in https://github.com/laravel/framework/pull/53022
* [11.x] feat: add Str::doesntContain() method and supporting tests by [@sts-ryan-holton](https://github.com/sts-ryan-holton) in https://github.com/laravel/framework/pull/53035
* [11.x] Str: Add extension support for `Str::inlineMarkdown()` by [@ryangjchandler](https://github.com/ryangjchandler) in https://github.com/laravel/framework/pull/53033
* Fix: Correct typehint on repository retrieval methods by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/53025
* [11.x] Test for forgetting non-flexible keys for file driver by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/53018
* Add metadata to mailable view data by [@TobMoeller](https://github.com/TobMoeller) in https://github.com/laravel/framework/pull/53042
* [11.x] PHPDoc Improvements by [@schulerj89](https://github.com/schulerj89) in https://github.com/laravel/framework/pull/53054
* [11.x] Test Improvements by [@toarupg0318](https://github.com/toarupg0318) in https://github.com/laravel/framework/pull/53057
* [11.x] PHPDoc Improvements by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/53053
* Add Exception Handling for jsonOptions() Method by [@shamimulalam](https://github.com/shamimulalam) in https://github.com/laravel/framework/pull/53056
* [11.x] Fixes `make:model` for Form Requests by [@joshmanders](https://github.com/joshmanders) in https://github.com/laravel/framework/pull/53052
* [11.x] Fixes validation using `shouldConvertToBoolean` when parameter uses dot notation by [@bytestream](https://github.com/bytestream) in https://github.com/laravel/framework/pull/53048
* [11.x] Add methods to the HTTP kernel to append middleware relative to other middleware by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/52897
* [11.x] Add `--json` flag to `queue:work` command for structured logging by [@josecl](https://github.com/josecl) in https://github.com/laravel/framework/pull/52887
* [11.x] Improve performance of Redis queue block_for when a worker has multiple queues to service by [@michael-scinocca](https://github.com/michael-scinocca) in https://github.com/laravel/framework/pull/52826

## [v11.26.0](https://github.com/laravel/framework/compare/v11.25.0...v11.26.0) - 2024-10-01

* [11.x] Fix PHPDoc typo by [@LucaRed](https://github.com/LucaRed) in https://github.com/laravel/framework/pull/52960
* Add stop() method to Process and Pool by [@MiniCodeMonkey](https://github.com/MiniCodeMonkey) in https://github.com/laravel/framework/pull/52959
* [11.x] Improve PHPDoc by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/52949
* [11.x] Fix crash of method PreventsCircularRecursion::withoutRecursion() on mocked models by [@maximetassy](https://github.com/maximetassy) in https://github.com/laravel/framework/pull/52943
* [11.x] Document callable types for `Enumerable::implode()` by [@devfrey](https://github.com/devfrey) in https://github.com/laravel/framework/pull/52937
* [11.x] Allows Unit & Backed Enums for registering named `RateLimiter` & `RateLimited` middleware by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/52935
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52933
* [11.x] Fixes trust proxy `REMOTE_ADDR` not working in Swoole by [@chuoke](https://github.com/chuoke) in https://github.com/laravel/framework/pull/52889
* [11.x] Fixes function loading conflicts when using `[@include](https://github.com/include)('vendor/autoload.php')` via Laravel Envoy by [@s-damian](https://github.com/s-damian) in https://github.com/laravel/framework/pull/52974
* [11.x] Support Laravel Prompts 0.3+ by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52993
* Allow mass assignment with mutators when using model::guarded by [@Apfelfrisch](https://github.com/Apfelfrisch) in https://github.com/laravel/framework/pull/52962
* [11.x] Add `make:job-middleware` artisan command by [@dshafik](https://github.com/dshafik) in https://github.com/laravel/framework/pull/52965
* [11.x] Auto discover Events outside app namespace when folder name is in kebab-case by [@xizprodev](https://github.com/xizprodev) in https://github.com/laravel/framework/pull/52976
* [11.x] Feat: factory generic in make:model command by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/52855

## [v11.25.0](https://github.com/laravel/framework/compare/v11.24.1...v11.25.0) - 2024-09-26

* [11.x] Fix make:listener command by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/52924
* [11.x] Fix incorrect PHPDoc for KeyBy and GroupBy by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/52918
* [11.x] Fix PHPDoc for TestResponse's `Response` Type to \Symfony\Component\HttpFoundation\Response by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/52915
* [11.x] Docblock Improvements by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/52909
* [11.x] Add vector column support to migrations by [@Jim-Webfox](https://github.com/Jim-Webfox) in https://github.com/laravel/framework/pull/52884
* [11.x] Revert auto-discovering `routes/console.php` as this will cause breaking change with the default `withRouting($console)` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52942
* [11.x] Fixed docblock on typeVector method in Grammer by [@flavio-schoute](https://github.com/flavio-schoute) in https://github.com/laravel/framework/pull/52927

## [v11.24.1](https://github.com/laravel/framework/compare/v11.24.0...v11.24.1) - 2024-09-25

* [11.x] Fixes `defer()` function return type by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/52910
* [11.x] Fixes missing `ExecutableFinder` import by [@InfinityXTech](https://github.com/InfinityXTech) in https://github.com/laravel/framework/pull/52912
* [11.x] Fix for not automatically registering commands in App\Console\Commands by [@SamuelNitsche](https://github.com/SamuelNitsche) in https://github.com/laravel/framework/pull/52903

## [v11.24.0](https://github.com/laravel/framework/compare/v11.23.5...v11.24.0) - 2024-09-24

* [11.x] Fix issue where `$name` variable in non base config file becomes it's key by [@rojtjo](https://github.com/rojtjo) in https://github.com/laravel/framework/pull/52738
* [11.x] PHPDoc Improvements by [@amitmerchant1990](https://github.com/amitmerchant1990) in https://github.com/laravel/framework/pull/52797
* [11.x] Remove a unused import and fix docblock for DeferredCallbackCollection by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52808
* [11.x] Handle SQLSTATE[HY000] [2002] Operation now in progress in DetectsLostConnection trait #52759 by [@webartisan10](https://github.com/webartisan10) in https://github.com/laravel/framework/pull/52805
* [11.x] Add prependLocation method to View Factory by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/52806
* [11.x] add nullOnUpdate() method to ForeignKeyDefinition by [@gisuNasr](https://github.com/gisuNasr) in https://github.com/laravel/framework/pull/52798
* [11.x] Allow `BackedEnum` to be passed to `Route::can()`  by [@Omegadela](https://github.com/Omegadela) in https://github.com/laravel/framework/pull/52792
* [11.x] Ensure headers are only attached to illuminate responses by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52789
* [11.x] feat: improve Collection groupBy, keyBy generics by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52787
* [11.x] Using Correct `Concurrency` Configuration Index Name by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/52788
* [11.x] Ensure `withoutPretending` method properly resets state after callback execution by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/52794
* [11.x] delegate `ProcessDriver[@defer](https://github.com/defer)()` to `ProcessDriver[@run](https://github.com/run)()` method by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/52807
* [11.x] Use command string instead of array on `Concurrency\ProcessDriver` by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/52813
* [11.x] Allows Laravel Framework to correctly resolve PHP binary when running via Laravel Herd by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52791
* [11.x] Move Defer classes to Support component and add `Illuminate\Support\defer` function by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52801
* [11.x] Suggest `laravel/serializable-closure` on Database component by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52835
* Bump vite from 5.2.10 to 5.2.14 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/52834
* [11.x] Update Concurrency component's composer dependencies by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/52836
* Add result shorthands for `Process` fakes by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/52840
* Update SerializesCastableAttributes to include array generics by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/52841
* [11.x] CI Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52850
* [11.x] Supports `laravel/prompts` v0.2 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52849
* [11.x] Handle allows null parameter instead of requiring default value by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52866
* [11.x] Adds `[@throws](https://github.com/throws)` section to Concurrency manager doc block by [@rnambaale](https://github.com/rnambaale) in https://github.com/laravel/framework/pull/52856
* Update stub to remove unused trait imports by [@lombervid](https://github.com/lombervid) in https://github.com/laravel/framework/pull/52877
* [11.x] Fix validation rule type hints by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/52870
* [11.x] Support multiple batch IDs when retrying failed batch jobs by [@skegel13](https://github.com/skegel13) in https://github.com/laravel/framework/pull/52873
* [11.x] Remove unused namespaces from DatabaseInspectionCommand and LocalFileSystemAdapter by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/52868
* [11.x] Auto-register commands in `routes/console.php` by [@SamuelNitsche](https://github.com/SamuelNitsche) in https://github.com/laravel/framework/pull/52867
* [11.x] Prevent infinite recursion on `touchesParents()` for chaperoned models by [@samlev](https://github.com/samlev) in https://github.com/laravel/framework/pull/52883
* Bump rollup from 4.17.1 to 4.22.4 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/52892
* [11.x] `Cache::flexible` improvements by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52891
* [11.x] Add `\DateTimeInterface` and `\DateInterval` to type for `Cache::flexible()` by [@bram-pkg](https://github.com/bram-pkg) in https://github.com/laravel/framework/pull/52888
* [11.x] CI Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52851
* [11.x] Do not trigger missing translation key handling when checking existence of translation key by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52895

## [v11.23.5](https://github.com/laravel/framework/compare/v11.23.4...v11.23.5) - 2024-09-13

* allow recursive Model::withoutTimestamps calls by [@m1guelpf](https://github.com/m1guelpf) in https://github.com/laravel/framework/pull/52768
* [11.x] Fixes out of memory issue running `route:cache` with ServeFile by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52781

## [v11.23.4](https://github.com/laravel/framework/compare/v11.23.2...v11.23.4) - 2024-09-12

* [10.x] Fixes `whereDate`, `whereDay`, `whereMonth`, `whereTime`, `whereYear` and `whereJsonLength` to ignore invalid `$operator` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52704
* [11.x] Fixing Concurrency Facade Docblocks by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/52764
* [11.x] add lazy default to when helper by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/52747
* Fix arguments passed to artisan commands that start with 'env' by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/52748

## [v11.23.2](https://github.com/laravel/framework/compare/v11.23.1...v11.23.2) - 2024-09-11

## [v11.23.1](https://github.com/laravel/framework/compare/v11.23.0...v11.23.1) - 2024-09-11

## [v11.23.0](https://github.com/laravel/framework/compare/v11.22.0...v11.23.0) - 2024-09-11

* [11.x] Fix $fail closure type in docblocks for validation rules by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/52644
* [11.x] Add MSSQL 2017 and PGSQL 10 builds by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/52631
* Update `everyThirtyMinutes` cron expression by [@SamuelNitsche](https://github.com/SamuelNitsche) in https://github.com/laravel/framework/pull/52662
* Bump micromatch from 4.0.5 to 4.0.8 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/52664
* [11.x] apply excludeUnvalidatedArrayKeys to list validation by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/52658
* [11.x] Adding minRatio & maxRatio rules on Dimension validation ruleset by [@CamKem](https://github.com/CamKem) in https://github.com/laravel/framework/pull/52482
* [11.x] Add BackedEnum support to Authorize middleware by [@diaafares](https://github.com/diaafares) in https://github.com/laravel/framework/pull/52679
* [11.x] Add BackedEnum support to Gate methods by [@diaafares](https://github.com/diaafares) in https://github.com/laravel/framework/pull/52677
* [11.x] Suggest serializable-closure by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/52673
* [11.x] Fix alter table expressions on SQLite by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/52678
* [11.x] Add Exceptions\Handler::mapLogLevel(...) so the logic can be easily overridden by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/52666
* [11.x] Bugfix for calling pluck() on chaperoned relations. by [@samlev](https://github.com/samlev) in https://github.com/laravel/framework/pull/52680
* [11.x] Fix build failures due to enum collide After adding BackedEnum support to Gate by [@diaafares](https://github.com/diaafares) in https://github.com/laravel/framework/pull/52683
* Fixing Str::trim to remove the default trim/ltrim/rtim characters " \n\r\t\v\0"  by [@mathiasgrimm](https://github.com/mathiasgrimm) in https://github.com/laravel/framework/pull/52684
* [11.x] Add `Skip` middleware for Queue Jobs by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/52645
* [11.x] Fix etag headers for binary file responses by [@wouterrutgers](https://github.com/wouterrutgers) in https://github.com/laravel/framework/pull/52705
* [11.x] add `withoutDelay()` to PendingDispatch by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/52696
* [11.x] Refactor `Container::getInstance()` to use null coalescing assignment by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/52693
* [11.x] Removed unnecessary call to setAccessible(true) by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/52691
* [11.x] Add `Eloquent\Collection::findOrFail` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/52690
* [11.x] PHPStan Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52712
* [11.x] Fix Collection PHPDoc by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/52724
* [11.x] Add optional parameter for `confirmed` validator rule by [@jwpage](https://github.com/jwpage) in https://github.com/laravel/framework/pull/52722
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52718
* [11.x] Fix incorrect variable-length argument `$guards` from array to string by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/52719
* Allow testing of relative signed routes by [@shealavington](https://github.com/shealavington) in https://github.com/laravel/framework/pull/52726
* [11.x] fix: Builder::with closure types by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52729
* Laracon 2024 by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/52710
* Add `Tag` attribute by [@TijmenWierenga](https://github.com/TijmenWierenga) in https://github.com/laravel/framework/pull/52743
* [11.x] Adds BackedEnum to PendingDispatch's phpDoc for onQueue, allOnQueue, onConnection, allOnConnection methods by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/52739
* New when() helper. by [@danmatthews](https://github.com/danmatthews) in https://github.com/laravel/framework/pull/52665
* [11.x] Add `fromUrl()` to Attachment by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/52688

## [v11.22.0](https://github.com/laravel/framework/compare/v11.21.0...v11.22.0) - 2024-09-03

* [11.x] Fix FoundationServiceProvider docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52542
* [11.x] Fix ReflectionParameter [@param](https://github.com/param) type on Util::getContextualAttributeFromDependency() by [@samsonasik](https://github.com/samsonasik) in https://github.com/laravel/framework/pull/52541
* [11.x] More specific parameter type in CastsInboundAttributes by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/52536
* [11.x] Unify prefetch API by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52550
* [11.x] Add PDO subclass support for PHP 8.4 by [@ju5t](https://github.com/ju5t) in https://github.com/laravel/framework/pull/52538
* [11.x] Handle circular references in model serialization by [@samlev](https://github.com/samlev) in https://github.com/laravel/framework/pull/52461
* [11.x] Eloquent inverse relations by [@samlev](https://github.com/samlev) in https://github.com/laravel/framework/pull/51582
* [11.x] Feature/whereany closures by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/52555
* [11.x] Update remaining workflows to run on latest possible ubuntu version by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/52566
* Correct comments to better represent the updated method functionality by [@dropweb](https://github.com/dropweb) in https://github.com/laravel/framework/pull/52564
* [11.x] Support CSP nonce by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52558
* [11.x] Allow enums to be passed to routes by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/52561
* [11.x] SORT_NATURAL on Collection no longer throws warning for nulls by [@Chaplinski](https://github.com/Chaplinski) in https://github.com/laravel/framework/pull/52557
* [11.x] Allow prefetch to start on custom event by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52574
* [11.x] Fix regression in database assertions with custom model connections by [@devfrey](https://github.com/devfrey) in https://github.com/laravel/framework/pull/52581
* [11] Update DetectsLostConnections.php by [@webartisan10](https://github.com/webartisan10) in https://github.com/laravel/framework/pull/52614
* Fix docblock for `Model::getEventDispatcher()` by [@inmula](https://github.com/inmula) in https://github.com/laravel/framework/pull/52602
* [11.x] Restore Request::HEADER_X_FORWARDED_PREFIX in TrustProxies by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/52598
* [11.x] Accepts BackedEnum for onQueue, onConnection, allOnQueue, and allOnConnection methods in the Queueable trait by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/52604
* [11.x] Use the same parameter type for 'throwUnless' as used for 'throwIf' by [@pataar](https://github.com/pataar) in https://github.com/laravel/framework/pull/52626
* [11.x] Pass iterable keys to `withProgressBar` in InteractsWithIO by [@robinmoisson](https://github.com/robinmoisson) in https://github.com/laravel/framework/pull/52623
* [11.x] Fix docblock for Filesystem::hash() by [@sunaoka](https://github.com/sunaoka) in https://github.com/laravel/framework/pull/52630
* Fix Apostrophe Handling in SeeInOrder.php and Enhance Test Coverage by [@nomitoor](https://github.com/nomitoor) in https://github.com/laravel/framework/pull/52627
* [11.x] SQLite Error: "General error: 1 no such table" after adding a foreign key when using a table prefix. by [@incrize](https://github.com/incrize) in https://github.com/laravel/framework/pull/52578

## [v11.21.0](https://github.com/laravel/framework/compare/v11.20.0...v11.21.0) - 2024-08-20

* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52402
* [11.x] Fix docblock for the event dispatcher by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52411
* [11.x] fix: Update text email template by [@tranvanhieu01012002](https://github.com/tranvanhieu01012002) in https://github.com/laravel/framework/pull/52417
* [11.x] Make `expectsChoice` assertion more intuitive with associative arrays. by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/52408
* [11.x]  Add `resource()` method to Illuminate\Http\Client\Response by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52412
* [10.x] fix: prevent casting empty string to array from triggering json error by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52415
* [11.x] Add ResponseInterface mixin to `Illuminate\Http\Client\Response` by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52410
* [11.x] Don't touch BelongsTo relationship when it doesn't exist by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/52407
* [11.x] Fix `Factory::afterCreating` callable argument type by [@villfa](https://github.com/villfa) in https://github.com/laravel/framework/pull/52424
* [11.x] Auto-secure cookies by [@fabricecw](https://github.com/fabricecw) in https://github.com/laravel/framework/pull/52422
* fix: add missing phpdoc types for Model::$table and Model::$dateFormat by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/52425
* [11.x] Add `withoutHeaders` method by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/52435
* Checking availability before calling Log::flushSharedContext() method by [@ajaxray](https://github.com/ajaxray) in https://github.com/laravel/framework/pull/52470
* [11.x] MessageBag errors out when custom rules are created and the class is left out of the message array by [@DanteB918](https://github.com/DanteB918) in https://github.com/laravel/framework/pull/52451
* Create Notification make command markdown name placeholder from Notif… by [@hosseinakbari-liefermia](https://github.com/hosseinakbari-liefermia) in https://github.com/laravel/framework/pull/52465
* [11.x] Add `forceDestroy` to `SoftDeletes` by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/52432
* Make SQLiteProcessor cope with '/' in column names by [@vroomfondle](https://github.com/vroomfondle) in https://github.com/laravel/framework/pull/52490
* [11.x] Improve Cookie Testing Coverage by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/52472
* [11.x] Fix for #52436 artisan schema:dump infinite recursion by [@rust17](https://github.com/rust17) in https://github.com/laravel/framework/pull/52492
* Run prepareNestedBatches on append/prependToChain & chain by [@SabatinoMasala](https://github.com/SabatinoMasala) in https://github.com/laravel/framework/pull/52486
* [11.x] Enhance DB inspection commands by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/52501
* [11.x] Constrain key when asserting database has a model by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/52464
* Add `between` to `AssertableJson` by [@rudashi](https://github.com/rudashi) in https://github.com/laravel/framework/pull/52479
* [11.x] Eager asset prefetching strategies for Vite by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52462
* [11.x] Support attributes in `app()->call()` by [@innocenzi](https://github.com/innocenzi) in https://github.com/laravel/framework/pull/52428
* [11.x] Applying `value` Function into the `$default` value of `transform` helper by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/52510
* [11.x] Enhanced typing for `HigherOrderCollectionProxy` by [@Voltra](https://github.com/Voltra) in https://github.com/laravel/framework/pull/52484
* [11.x] Add `expectsSearch()` assertion for testing prompts that use `search()` and `multisearch()` functions by [@JayBizzle](https://github.com/JayBizzle) in https://github.com/laravel/framework/pull/51669
* [11.x] revert #52510 which added a unneeded function call by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/52526

## [v11.20.0](https://github.com/laravel/framework/compare/v11.19.0...v11.20.0) - 2024-08-06

* Update testcase for `whereNone` method by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52351
* Improve `Lock->block` method by [@RedmarBakker](https://github.com/RedmarBakker) in https://github.com/laravel/framework/pull/52349
* [11.x] Use correct pluralization rules in trans_choice for fallback strings by [@stefanvdlugt](https://github.com/stefanvdlugt) in https://github.com/laravel/framework/pull/52343
* [11.x] Replace dead link in Security Policy by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/52338
* Add compatible query type to `Model::resolveRouteBindingQuery` by [@sebj54](https://github.com/sebj54) in https://github.com/laravel/framework/pull/52339
* [10.x] Fix `Factory::afterCreating` callable argument type by [@villfa](https://github.com/villfa) in https://github.com/laravel/framework/pull/52335
* [11.x] Remove undefined class PreventRequestsDuringMaintenance by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52322
* [11.x] Add middleware before sending request and dispatching events by [@eduance](https://github.com/eduance) in https://github.com/laravel/framework/pull/52323
* Add `collapseWithKeys` to `Collection` by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/52347
* [11.x] Inverse Fake Queue Interactions: `assertNotDeleted`, `assertNotFailed`, and `assertNotReleased` by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52320
* Add `deduplicate` to strings by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/52350
* [11.x] feat: make `Facade::isFake()` public by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52357
* [11.x] Ask about markdown template for notification command with no initial input by [@christophrumpel](https://github.com/christophrumpel) in https://github.com/laravel/framework/pull/52355
* [11.x] allow custom view path when making components by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/52219
* [11.x] chore: update to PHPStan Level 1 by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51956
* [11.x] Support passing default as named parameter in whenLoaded, whenAggregated, whenCounted by [@hn-seoai](https://github.com/hn-seoai) in https://github.com/laravel/framework/pull/51342
* Declare exceptions unreportable using the ShouldntReport interface by [@chrispage1](https://github.com/chrispage1) in https://github.com/laravel/framework/pull/52337
* [11.x] Enable extension of connection inspection methods by [@GromNaN](https://github.com/GromNaN) in https://github.com/laravel/framework/pull/52231
* [11.x] Add `whenExistsLoaded` method to conditionally include relationship existence attribute by [@CodeWithKyrian](https://github.com/CodeWithKyrian) in https://github.com/laravel/framework/pull/52295
* [11.x] Add `in()` and `inHidden()` functions to Context Stacks by [@lessevv](https://github.com/lessevv) in https://github.com/laravel/framework/pull/52346
* [11.x] Use Command::fail() method for single error messages by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52387
* [11.x] Rework `Context::stackContains` with Closures. by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52381
* [11.x] Allow enums to be passed to AssertableJson where methods by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/52360
* [11.x] Made `list` validation rule as array for "size rules" in validation messages by [@siarheipashkevich](https://github.com/siarheipashkevich) in https://github.com/laravel/framework/pull/52385
* [11.x] Add contextual attributes to resolve drivers by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/52265
* [11.x] Fix docblocks for where(All|Any|None) query methods by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52388
* [10.x] backport #52204 by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52389
* [11.x] Fix Http Client Pool requests that have no response by [@andrewbroberg](https://github.com/andrewbroberg) in https://github.com/laravel/framework/pull/52393
* [11.x] Introduce MixFileNotFoundException for handling missing Mix files by [@Ex10Dios](https://github.com/Ex10Dios) in https://github.com/laravel/framework/pull/52400
* [10.x] In MySQL, harvest last insert ID immediately after query is executed by [@piurafunk](https://github.com/piurafunk) in https://github.com/laravel/framework/pull/52390

## [v11.19.0](https://github.com/laravel/framework/compare/v11.18.1...v11.19.0) - 2024-07-30

* fix [@return](https://github.com/return) typehint in Illuminate\Contracts\Process\InvokedProcess::wait method by [@mdmahbubhelal](https://github.com/mdmahbubhelal) in https://github.com/laravel/framework/pull/52304
* [11.x] Add php doc for ServiceProvider bindings and singletons properties by [@Anton5360](https://github.com/Anton5360) in https://github.com/laravel/framework/pull/52298
* [10.x] backport #52188 by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52293
* Update docblock to accept an Expression for whereLike methods by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/52299
* [11.x] Fix Bcrypt/Argon/Argon2I Hashers not checking database field for nullish value before checking hash compatibility by [@localpath](https://github.com/localpath) in https://github.com/laravel/framework/pull/52297
* [11.x] Method to trim '0' digits after decimal point of a given number by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52284
* [11.x] Add `whereNone` method to the query builder by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52260
* [11.x] Fix flat array parameter for relation upsert by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/52289
* [11.x] `assertSeeHtml`, `assertDontSeeHtml` and `assertSeeHtmlInOrder` testing methods by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52285
* [11.x] Fully qualify morph columns when using WHERE clauses by [@maartenpaauw](https://github.com/maartenpaauw) in https://github.com/laravel/framework/pull/52227
* [10.x] Fix runPaginationCountQuery not working properly for union queries by [@chinleung](https://github.com/chinleung) in https://github.com/laravel/framework/pull/52314
* [11.x] Add `assertExactJsonStructure` method by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52311
* [11.x] Add `withoutHeader()` test method by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52309
* [11.x] Widen typehints in base service provider by [@devfrey](https://github.com/devfrey) in https://github.com/laravel/framework/pull/52308
* [11.X] "Model::preventAccessingMissingAttributes()" Causes Exception During Pagination with ResourceCollection by [@Katalam](https://github.com/Katalam) in https://github.com/laravel/framework/pull/52305
* [11.x] Fixes through() relationship by [@leobeal](https://github.com/leobeal) in https://github.com/laravel/framework/pull/52318
* [11.x] Add new `success` Method to the Docblock of `Illuminate\Console\View\Components\Factory` by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/52310
* [11.x] Fix tests in wrong file by [@christophrumpel](https://github.com/christophrumpel) in https://github.com/laravel/framework/pull/52329

## [v11.18.1](https://github.com/laravel/framework/compare/v11.18.0...v11.18.1) - 2024-07-26

* [11.x] Fix variable typo at Terminating Event test by [@chu121su12](https://github.com/chu121su12) in https://github.com/laravel/framework/pull/52282
* Revert "[11.x] Declare bindings and singletons properties in Service Provider" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/52288

## [v11.18.0](https://github.com/laravel/framework/compare/v11.17.0...v11.18.0) - 2024-07-26

* Added completeWords flag to limit str method by [@itsmewes](https://github.com/itsmewes) in https://github.com/laravel/framework/pull/52245
* [11.x] Fix missing * in phpdoc by [@pb30](https://github.com/pb30) in https://github.com/laravel/framework/pull/52277
* [11.x] Fix SQLite schema dumps missing most tables by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/52275
* [11.x] Access dispatchedBatches via BusFake by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52270
* [11.x] Adds terminating event by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52259
* [11.x] Declare bindings and singletons properties in Service Provider by [@Anton5360](https://github.com/Anton5360) in https://github.com/laravel/framework/pull/52256
* [11.x] Fix explicit route binding for broadcast routes by [@ccharz](https://github.com/ccharz) in https://github.com/laravel/framework/pull/52280
* Revert "[11.x] Allow non-`ContextualAttribute` attributes to have an `after` callback" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/52281
* [11.x] Apply relation constraitns on upsert by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/52239

## [v11.17.0](https://github.com/laravel/framework/compare/v11.16.0...v11.17.0) - 2024-07-23

* [10.x] Fix PHP_CLI_SERVER_WORKERS warning by suppressing it by [@pelomedusa](https://github.com/pelomedusa) in https://github.com/laravel/framework/pull/52094
* [11.x] Use `Command::FAILURE` for `db:wipe` command by [@siarheipashkevich](https://github.com/siarheipashkevich) in https://github.com/laravel/framework/pull/52152
* [11.x] Update outdated config files by [@TENIOS](https://github.com/TENIOS) in https://github.com/laravel/framework/pull/52150
* [11.x] Fix 'pushProcessor method not found on LoggerInterface' error by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/52117
* [11.x] Use `Command::FAILURE` for `migrate:fresh` command by [@siarheipashkevich](https://github.com/siarheipashkevich) in https://github.com/laravel/framework/pull/52153
* Improve accuracy of `Collection::isEmpty` and `isNotEmpty` assertions by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/framework/pull/52184
* [11.x] Fix return for ApplicationBuilder:: withCommandRouting method by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52181
* [11.x] Refactor: Replace get_called_class() with static::class for consistency by [@fernandokbs](https://github.com/fernandokbs) in https://github.com/laravel/framework/pull/52173
* [11.x] Improve readability of SQLite schema dumps by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/52172
* [11.x] Allow non-`ContextualAttribute` attributes to have an `after` callback by [@innocenzi](https://github.com/innocenzi) in https://github.com/laravel/framework/pull/52167
* [11.x] Ignoring column definitions when determining if a blueprint has a create command by [@kingsven](https://github.com/kingsven) in https://github.com/laravel/framework/pull/52177
* Add specify exceptions for exceptions handling the vite manifest file by [@SamuelWei](https://github.com/SamuelWei) in https://github.com/laravel/framework/pull/52169
* [11.x] fix: Model newCollection generics; feat: add HasCollection trait by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52171
* Add whereLike clause to query builder by [@einar-hansen](https://github.com/einar-hansen) in https://github.com/laravel/framework/pull/52147
* [11.x] Implement HasV7Uuids to use with MariaDB native uuid data type by [@Karem-sobhy](https://github.com/Karem-sobhy) in https://github.com/laravel/framework/pull/52029
* [11.x] Rename `Model::$collection` to `$collectionClass` by [@GromNaN](https://github.com/GromNaN) in https://github.com/laravel/framework/pull/52186
* [11.x] Allow microsecond travel by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52190
* [11.x] fix: Model/JsonResource::toJson should not fail with prior json errors by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52188
* [11.x] Fix SQL Server tests by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/52222
* [11.x] Inspect exception of assertThrows by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/52224
* [10.x]  Backport #51615  by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/52215
* [11.x] fix: Request::json() json errors when decoding empty string by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52204
* [11.x] Reduce the number of queries with `Cache::many` and `Cache::putMany` methods in  the database driver by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/52209
* Add method `QueryExecuted::toRawSql()` by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/framework/pull/52192
* [11.x] Support lower version of Carbon by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/52233
* [11.x] Prevent bug (🐛) emoji on `Collection`/`Dumpable` `dd` method by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/52234

## [v11.16.0](https://github.com/laravel/framework/compare/v11.15.0...v11.16.0) - 2024-07-16

* [11.x] Fix expected/actual argument order for test assertion by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/52084
* [11.x] Fix Moving Files in Sorted Order in vendor:publish by [@lmottasin](https://github.com/lmottasin) in https://github.com/laravel/framework/pull/52078
* [11.x] Fix docblock for \Illuminate\Validation\ClosureValidationRule::message() by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52077
* [11.x] Allow passing Enum casts to `Rule::enum()->only()` and `->except()` by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/52073
* [11.x] Include ConnectionException in ConnectionFailed events by [@alexbowers](https://github.com/alexbowers) in https://github.com/laravel/framework/pull/52069
* [11.x] Document returned array shape for sync methods by [@devfrey](https://github.com/devfrey) in https://github.com/laravel/framework/pull/52070
* [11.x] Add ability to configure SQLite `busy_timeout`, `journal_mode`, and `synchronous` pragmas by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/52052
* [11.x] Allow view content dependent mail callbacks by [@MaxTingle](https://github.com/MaxTingle) in https://github.com/laravel/framework/pull/51990
* Install Laravel Reverb version 1.0 instead of [@beta](https://github.com/beta) by [@lucasRolff](https://github.com/lucasRolff) in https://github.com/laravel/framework/pull/52096
* [11.x] fix: dont use web middleware on health endpoint by [@joshmanders](https://github.com/joshmanders) in https://github.com/laravel/framework/pull/52088
* [11.x] Add an option to replace configs recursively by [@felixbessler](https://github.com/felixbessler) in https://github.com/laravel/framework/pull/52087
* [11.x] Fixes generator tests by [@buismaarten](https://github.com/buismaarten) in https://github.com/laravel/framework/pull/52118
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52122
* [11.x] Fix SQLite schema dumps containing internal `sqlite_*` objects by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/52135
* Fix typo in `<!DOCTYPE>` declaration by [@TENIOS](https://github.com/TENIOS) in https://github.com/laravel/framework/pull/52134
* [11.x] fix: pluck generics by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52109
* [11.x] Include 'success' console component by [@lewislarsen](https://github.com/lewislarsen) in https://github.com/laravel/framework/pull/52112
* [11.x] Fix dumping migrations table with schema or prefixed name by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/52098
* Add `assertSentTo` shorthand by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/52083
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52139
* [11.x] Update the docblock for the constructor of the FileFailedJobProvider class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/52149
* [11.x] Update the docblock for the explode method of the Stringable class. by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/52148
* Refactor PHPStan configurations by [@TENIOS](https://github.com/TENIOS) in https://github.com/laravel/framework/pull/52145
* [11.x] Fix docblock for RoutingServiceProvider by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52144

## [v11.15.0](https://github.com/laravel/framework/compare/v11.14.0...v11.15.0) - 2024-07-09

* [10.x] Set previous exception on `HttpResponseException` by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51986
* [11.x] feat: add generics to Eloquent Builder and Relations by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51851
* add phpstan assertions to Collection isEmpty and isNotEmpty by [@johanrosenson](https://github.com/johanrosenson) in https://github.com/laravel/framework/pull/51998
* [11.x] Add support for mime types in Resend mail transport by [@jayanratna](https://github.com/jayanratna) in https://github.com/laravel/framework/pull/52006
* [11.x] feat: add virtual methods to SoftDeletes trait by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52001
* [11.x] Fix service container docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52000
* [10.x] Fix Http::retry so that throw is respected for call signature Http::retry([1,2], throw: false) by [@paulyoungnb](https://github.com/paulyoungnb) in https://github.com/laravel/framework/pull/52002
* [10.x] Set application_name and character set as PostgreSQL DSN string by [@sunaoka](https://github.com/sunaoka) in https://github.com/laravel/framework/pull/51985
* [11.x] Fix GeneratorCommand docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52014
* [11.x] Enhance database migrations by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51373
* [11.x] Run MySQL 9 Database Integration Tests nightly by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/52027
* [11.x] Enhance doc blocks of the Migrator class by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/52033
* [11.x] Use nullsafe operator for event dispatcher by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52024
* [11.x] Fix PasswordBroker constructor docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/52023
* [11.x] Add test `testMultiplyIsLazy` to ensure LazyCollection's `multiply` method's lazy behaviour by [@lmottasin](https://github.com/lmottasin) in https://github.com/laravel/framework/pull/52020
* [11.x] Allow `MultipleInstanceManager` to have studly creators by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/52030
* [11.x] Adds `$config` property to `MultipleInstanceManager` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/52028
* [11.x] fix: findOr and firstOr generics by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52037
* [11.x] Make `Router` `Tappable` by [@mabdullahsari](https://github.com/mabdullahsari) in https://github.com/laravel/framework/pull/52051
* [11.x] feat: improve Factory generics, add generics to HasFactory by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/52005
* [11.x] Ask About View Next To Name For Create Mail Command by [@christophrumpel](https://github.com/christophrumpel) in https://github.com/laravel/framework/pull/52057
* [11.x] Added [@throws](https://github.com/throws) docblock for `block` method for `LockTimeoutException` by [@siarheipashkevich](https://github.com/siarheipashkevich) in https://github.com/laravel/framework/pull/52063

## [v11.14.0](https://github.com/laravel/framework/compare/v11.13.0...v11.14.0) - 2024-07-02

* Adding Pest stubs to publish command by [@bartdenhoed](https://github.com/bartdenhoed) in https://github.com/laravel/framework/pull/51933
* [11.x] Added attempts() method to FakeJob by [@JamesFreeman](https://github.com/JamesFreeman) in https://github.com/laravel/framework/pull/51951
* [11.x] Run all Workflows on Ubuntu 24.04 by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/51946
* [11.x] Improve PHPDoc for `mapSpread` Method in `Arr` Class & Remove Warning from IDE by [@lmottasin](https://github.com/lmottasin) in https://github.com/laravel/framework/pull/51952
* Bump braces from 3.0.2 to 3.0.3 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/51955
* [11.x] Remove unreachable code in AssertableJsonString by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51943
* [11.x] Fix TestResponseAssert docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51942
* [11.x] feat: add more specific types and tests for helpers by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51938
* [11.x] Mark sensitive params with `SensitiveParameter` attribute by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/51940
* [11.x] Adds support for Markdown extensions to the `Stringable` class. by [@lukeraymonddowning](https://github.com/lukeraymonddowning) in https://github.com/laravel/framework/pull/51932
* [11.x] Add secret method declaration to Components\Factory class by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51949
* [11.x] Run Workflows on Windows 2022 and with bash instead of powershell by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/51958
* [11.x] Fix duplicated return type PHPDoc by [@chu121su12](https://github.com/chu121su12) in https://github.com/laravel/framework/pull/51965
* [11.x] Fix test failure message by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/51974
* [11.x] Update tests to ensure mail Message implements the fluent interface pattern by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51969
* [11.x] Set previous exception on `HttpResponseException` by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51968
* [11.x] Fix typo in SupportCollectionTest by [@zbundy](https://github.com/zbundy) in https://github.com/laravel/framework/pull/51966
* [11.x] Improvements for the ServeCommand (add more loves & elevate DX) by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/51957
* [11.x] Adds support for using castAsJson with a MariaDb connection by [@haniha](https://github.com/haniha) in https://github.com/laravel/framework/pull/51963
* [11.x] Add support for acting on attributes through container by [@innocenzi](https://github.com/innocenzi) in https://github.com/laravel/framework/pull/51934
* [11.x] Fix Component::resolveComponentsUsing test by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51988
* [11.x] Update composer.json files to provide PSR implementations by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51983
* [11.x] add queued closure type for soft delete events by [@hpiaia](https://github.com/hpiaia) in https://github.com/laravel/framework/pull/51982
* [11.x] Fix using container nesting to make the same 'abstract' in different context by [@guiqibusixin](https://github.com/guiqibusixin) in https://github.com/laravel/framework/pull/51989
* [11.x] Fix sync is running touch query twice by [@Tofandel](https://github.com/Tofandel) in https://github.com/laravel/framework/pull/51984

## [v11.13.0](https://github.com/laravel/framework/compare/v11.12.0...v11.13.0) - 2024-06-27

* [11.x] Add Support for Extensions in Str::markdown Method by [@tnylea](https://github.com/tnylea) in https://github.com/laravel/framework/pull/51907
* [11.x] Update config:show command by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51902
* [11.x] Fix console prompt docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51913
* [11.x] Fix prohibit docblock by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51916
* [11.x] Mark `$queue` as nullable by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/51912
* use `Macroable` trait on TokenGuard by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/51922
* [11.x] Update Command::fail() dockblock and tests by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51914
* Revert and add test by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/51924
* [11.x] Display view creation messages by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/51925
* [11.x] Introduce `Str::chopStart` and `Str::chopEnd` by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/51910
* feat: Add Number::pairs by [@hotmeteor](https://github.com/hotmeteor) in https://github.com/laravel/framework/pull/51904
* [11.x] Fixes escaping path via Process given commands as array by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51926
* [11.x] Make MultipleInstanceManager driver field customizable by [@princejohnsantillan](https://github.com/princejohnsantillan) in https://github.com/laravel/framework/pull/51905
* [11.x] Account for long strings on new Laravel error page by [@shengslogar](https://github.com/shengslogar) in https://github.com/laravel/framework/pull/51880

## [v11.12.0](https://github.com/laravel/framework/compare/v11.11.1...v11.12.0) - 2024-06-25

* [10.x] Fix typo in return comment of createSesTransport method by [@zds-s](https://github.com/zds-s) in https://github.com/laravel/framework/pull/51688
* [10.x] Fix collection shift less than one item by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51686
* [10.x] Turn `Enumerable unless()`  $callback parameter optional by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51701
* Revert "[10.x] Turn `Enumerable unless()`  $callback parameter optional" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/51707
* [10.x] Fixes unable to call another command as a initialized instance of `Command` class by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51824
* [10.x] fix handle `shift()` on an empty collection by [@Treggats](https://github.com/Treggats) in https://github.com/laravel/framework/pull/51841
* [10.x] Ensure`schema:dump` will dump the migrations table only if it exists by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/51827
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51847
* [11.x] Test application storage path by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51848
* [11.x] Fix PHP_MAXPATHLEN check for strings slightly smaller then PHP_MAXPATHLEN by [@joshuaruesweg](https://github.com/joshuaruesweg) in https://github.com/laravel/framework/pull/51850
* [11.x] Improve Bus::assertNothingDispatched(), Event::assertNothingDispatched(), Mail::assertNothingSent(), Notification::assertNothingSent() error messages by [@macbookandrew](https://github.com/macbookandrew) in https://github.com/laravel/framework/pull/51846
* [11.x] Update error page to show GET <path> by [@chu121su12](https://github.com/chu121su12) in https://github.com/laravel/framework/pull/51837
* [11.x] Remove deprecated `type` attributes in the exception renderer by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/51866
* [11.x] Import classes in the exception templates by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/51863
* [11.x] Collection before/after optimization by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/51876
* [11.x] Add multiply to collection by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/51870
* [11.x] Add addEventDiscoveryPaths to EventServiceProvider by [@ya-cha](https://github.com/ya-cha) in https://github.com/laravel/framework/pull/51896
* [11.x] Fix validation attributes when translations are empty or missing by [@owenandrews](https://github.com/owenandrews) in https://github.com/laravel/framework/pull/51890
* [11.x] feat: add generics to tap() helper by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51881

## [v11.11.1](https://github.com/laravel/framework/compare/v11.11.0...v11.11.1) - 2024-06-20

* [11.x] Remove useless variable assignment by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51838
* [11.x] Fix event dispatcher typing in cache repository by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/51835
* Chop PHP extension when passed to `make` commands by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/51842
* [11.x] Simplify `.php` extension chopping in `getNameInput` by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/51843
* [11.x] fix: improve performance and robustness of Relation::getMorphAlias() by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51845
* Revert "[11.x] Change scope for `afterCreating` and `afterMaking` callbacks" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/51858

## [v11.11.0](https://github.com/laravel/framework/compare/v11.10.0...v11.11.0) - 2024-06-18

* [11.x] Add `get`, `write` and `forget` cache events by [@stayallive](https://github.com/stayallive) in https://github.com/laravel/framework/pull/51560
* [11.x] Add test for Arr::sortRecursiveDesc() method. by [@lmottasin](https://github.com/lmottasin) in https://github.com/laravel/framework/pull/51716
* [11.x] Fix missing table name in `db:table` command by [@benholmen](https://github.com/benholmen) in https://github.com/laravel/framework/pull/51710
* Ensure files exist for `install:broadcasting` by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/51719
* [11.x] Restore exceptions/errors to test assertion failure messages by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/51725
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51723
* [11.x] Add tests for accessible and take method by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51724
* Increment the `totalJobs` property for the `BatchFake` when add some jobs by [@yankewei](https://github.com/yankewei) in https://github.com/laravel/framework/pull/51742
* [11.x] Give session ID retrieval the Laravel treatment by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/51732
* [11.x] Fix the chunk method to an integer type in the splitIn method by [@rookiexxk](https://github.com/rookiexxk) in https://github.com/laravel/framework/pull/51733
* Update:update name method and doc by [@mehdi-fathi](https://github.com/mehdi-fathi) in https://github.com/laravel/framework/pull/51744
* [11.x] Fixes `config:publish` with `dontMergeFrameworkConfiguration()` set to `true` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51751
* Updated phpdoc for Builder::from() by [@boris-glumpler](https://github.com/boris-glumpler) in https://github.com/laravel/framework/pull/51767
* [11.x] Fixed pop on default Beankstalkd queue when not specifically added by [@rinocs](https://github.com/rinocs) in https://github.com/laravel/framework/pull/51759
* [11.x] Add `before` and `after` methods to Collection by [@avosalmon](https://github.com/avosalmon) in https://github.com/laravel/framework/pull/51752
* [11.x] Change scope for `afterCreating` and `afterMaking` callbacks by [@jacob418](https://github.com/jacob418) in https://github.com/laravel/framework/pull/51772
* Use numeric literal separator in file rule validation by [@AmirKhalifehSoltani](https://github.com/AmirKhalifehSoltani) in https://github.com/laravel/framework/pull/51781
* [11.x] Import Model class for Renderer\Exception by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51778
* [11.x] About command improvement by [@AmirKhalifehSoltani](https://github.com/AmirKhalifehSoltani) in https://github.com/laravel/framework/pull/51791
* [11.x] Test abort behavior by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51800
* [11.x] Container shares fixed values/initialized instances instead of singleton closure resolutions by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51804
* [11.x] Fix altering a table that has a column with `default 0` on SQLite by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51803
* [11.x] Fix typo in `VendorPublishCommand` by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/51812
* [11.x] Fix some typos in the tests by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/51811
* [11.x] Add unprocessableContent and update unprocessableEntity by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/51815
* [11.x] Improve Queue::assertNothingPushed() error message by [@SjorsO](https://github.com/SjorsO) in https://github.com/laravel/framework/pull/51814
* [11.x] Add `Relation::getMorphAlias()` by [@pxlrbt](https://github.com/pxlrbt) in https://github.com/laravel/framework/pull/51809
* [11.x] Support third-party relations in `model:show` command by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51807
* [11.x] Fix nested rules custom attribute names by [@owenandrews](https://github.com/owenandrews) in https://github.com/laravel/framework/pull/51805
* [11.x] Fix docblock of \Illuminate\Http\Response by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/51823

## [v11.10.0](https://github.com/laravel/framework/compare/v11.9.2...v11.10.0) - 2024-06-04

* [11.x] Fix typo in filename by [@Henridv](https://github.com/Henridv) in https://github.com/laravel/framework/pull/51643
* [11.x] Add Vite auto refresh to error page by [@riasvdv](https://github.com/riasvdv) in https://github.com/laravel/framework/pull/51635
* [11.x] Add test for join_paths by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/51621
* [11.x] Preload base options for missing config files by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/51619
* [11.x] Add option to disable merging of base configuration by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/51579
* [11.x] Allow callback to be passed to `updateOrInsert()` to pass different `$values` if the record already exists by [@Markshall](https://github.com/Markshall) in https://github.com/laravel/framework/pull/51566
* [11.x] Fix `join_paths` issue with segment '0' by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/51649
* [11.x] Remove extra double quote in the error page by [@nicolus](https://github.com/nicolus) in https://github.com/laravel/framework/pull/51670
* [11.x] Add tests to improve test coverage for `HtmlString` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51666
* [11.x] Add tests to improve test coverage for `Arr::whereNotNull` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51661
* [11.x] Add tests for FileSystem class by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/51654
* [11.x] Update OptimizeClearCommand.php by [@nathanpurcell](https://github.com/nathanpurcell) in https://github.com/laravel/framework/pull/51667
* [11.x] Support soft deleted models when using explicit route model binding by [@gbradley](https://github.com/gbradley) in https://github.com/laravel/framework/pull/51651
* [11.x] Add tests for `Arr::divide` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51673
* [11.x] Prune should be a flag option by [@riasvdv](https://github.com/riasvdv) in https://github.com/laravel/framework/pull/51694
* [11.x] Avoid using Laravel new error page if `app.debug` changes to `true` at runtime by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51705

## [v11.9.2](https://github.com/laravel/framework/compare/v11.9.1...v11.9.2) - 2024-05-30

* [11.x] Fix new exception renderer compatibility with closure middleware by [@ifox](https://github.com/ifox) in https://github.com/laravel/framework/pull/51614
* [11.x] Fix double-quoted string literals on SQLite by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51615
* [11.x] Allow setting Resend api key in mailer specific config by [@riasvdv](https://github.com/riasvdv) in https://github.com/laravel/framework/pull/51618
* [11.x] Fix only number as session key will result in numbered session keys by [@Katalam](https://github.com/Katalam) in https://github.com/laravel/framework/pull/51611

## [v11.9.1](https://github.com/laravel/framework/compare/v11.9.0...v11.9.1) - 2024-05-28

* [11.x] Fixes missing route context by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/51602

## [v11.9.0](https://github.com/laravel/framework/compare/v11.8.0...v11.9.0) - 2024-05-28

* [11.x] Optimize boostrap time by using hashtable to store providers by [@sarven](https://github.com/sarven) in https://github.com/laravel/framework/pull/51343
* [11.x] Prevent destructive commands from running by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/51376
* [11.x] renamed left `has` to `contains` by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/51532
* [10.x] Fix typo by [@Issei0804-ie](https://github.com/Issei0804-ie) in https://github.com/laravel/framework/pull/51535
* [11.x] Fixes doc block in Timebox.php by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51537
* [11.x] Rename test function to match prohibit action by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51534
* [11.x] Fix LazilyRefreshDatabase when using Laravel BrowserKit Testing by [@MaxGiting](https://github.com/MaxGiting) in https://github.com/laravel/framework/pull/51538
* [10.x] Fix SQL Server detection in database store by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51547
* [11.x] Display test creation messages by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/51546
* [11.x] Detect Cockroach DB connection loss by [@saschaglo](https://github.com/saschaglo) in https://github.com/laravel/framework/pull/51559
* [11.x] Fix type tests by [@stayallive](https://github.com/stayallive) in https://github.com/laravel/framework/pull/51558
* [11.x] Add `withoutDelay()` to the `Queueable` trait by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/51555
* [11.x] Add an option to remove the original environment file after encrypting by [@riasvdv](https://github.com/riasvdv) in https://github.com/laravel/framework/pull/51556
* [10.x] - Fix batch list loading in Horizon when serialization error by [@jeffortegad](https://github.com/jeffortegad) in https://github.com/laravel/framework/pull/51551
* [10.x] Fixes explicit route binding with `BackedEnum` by [@CAAHS](https://github.com/CAAHS) in https://github.com/laravel/framework/pull/51586
* [11.x] Add `Macroable` to `PendingCommand` by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/framework/pull/51572
* [11.x] Improves errors by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/51261
* [11.x] Add RELEASE.md to .gitattributes by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/51598
* [11.x] Fixes exception rendering by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/51587

## [v11.8.0](https://github.com/laravel/framework/compare/v11.7.0...v11.8.0) - 2024-05-21

* [11.x] Update PendingRequest.php by [@foremtehan](https://github.com/foremtehan) in https://github.com/laravel/framework/pull/51338
* Add unshift method to Collection by [@timkelty](https://github.com/timkelty) in https://github.com/laravel/framework/pull/51344
* [11.x] Synchronizing cache configuration file with updated laravel v11.0.7 by [@dvlpr91](https://github.com/dvlpr91) in https://github.com/laravel/framework/pull/51336
* [11.x] Utilize `null-safe` operator instead of conditional check by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51328
* [11.x] Add the events to be displayed on the model:show command by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/51324
* [11.x] fix: remove use of Redis::COMPRESSION_ZSTD_MIN by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51346
* [10.x] Backport: Fix SesV2Transport to use correct `EmailTags` argument by [@Tietew](https://github.com/Tietew) in https://github.com/laravel/framework/pull/51352
* [11.x] feat: use phpredis 6 in ci by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51347
* [11.x] create new "has" validation rule by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/51348
* [11.x] Add support for previous apps keys in signed URL verification by [@Krisell](https://github.com/Krisell) in https://github.com/laravel/framework/pull/51222
* [11.x] Allow setting exit code in migrate:status --pending by [@brecht-vermeersch](https://github.com/brecht-vermeersch) in https://github.com/laravel/framework/pull/51341
* [11.x] Fix array rule typehint by [@erik-perri](https://github.com/erik-perri) in https://github.com/laravel/framework/pull/51372
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51365
* [10.x] Fix PHPDoc typo by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51390
* [11.x] Fix return type hint of resolveRouteBindingQuery by [@philbates35](https://github.com/philbates35) in https://github.com/laravel/framework/pull/51392
* [11.x] Allow adding array or string for web and api routes in bootstrap/app.php by [@mrthito](https://github.com/mrthito) in https://github.com/laravel/framework/pull/51356
* [ 11.x ] Adds ability to manually fail a command from outside the handle() method by [@ProjektGopher](https://github.com/ProjektGopher) in https://github.com/laravel/framework/pull/51435
* [10.x] Fix `apa` on non ASCII characters by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51428
* [11.x] Compare lowercased column names in getColumnType by [@chady](https://github.com/chady) in https://github.com/laravel/framework/pull/51431
* [11.x] Use contracts instead of concrete type for `resolveRouteBindingQuery()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51425
* [11.x] Set the value of `$this` in macro closures by [@simonwelsh](https://github.com/simonwelsh) in https://github.com/laravel/framework/pull/51401
* [11.x] Add missing roundrobin transport driver config by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/framework/pull/51400
* [11.x] Remove unused namespace by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51436
* [11.x] Fixes doc block in `Connector.php` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51440
* [10.x] Fixes view engine resolvers leaking memory by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/51450
* [11.x] Add some tests to `SupportStrTest` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51437
* [11.x] Add isCurrentlyOwnedBy function to lock by [@gazben](https://github.com/gazben) in https://github.com/laravel/framework/pull/51393
* [11.x] Collection average/avg optimization by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/51512
* [11.x] Introduce `MixManifestNotFoundException` for handling missing Mix manifests by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/51502
* [11.x] MailMakeCommand: Add new `--view` option by [@ryangjchandler](https://github.com/ryangjchandler) in https://github.com/laravel/framework/pull/51411
* [11.x] Replace all backed enums with values when building URLs by [@stefanvdlugt](https://github.com/stefanvdlugt) in https://github.com/laravel/framework/pull/51524
* [10.x] Do not use `app()` Foundation helper on `ViewServiceProvider` by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/51522
* Fixes explicit route binding with `BackedEnum` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51525
* [11.x] Add query method to UrlGenerator contract docblock by [@hjanos-bc](https://github.com/hjanos-bc) in https://github.com/laravel/framework/pull/51515

## [v11.7.0](https://github.com/laravel/framework/compare/v11.6.0...v11.7.0) - 2024-05-07

* [11.x] Fix SesV2Transport to use correct `EmailTags` argument by @Tietew in https://github.com/laravel/framework/pull/51265
* [11.x] Add Databases nightly workflow by @Jubeki in https://github.com/laravel/framework/pull/51218
* [11.x] update "min" and "max" rule comments by @browner12 in https://github.com/laravel/framework/pull/51274
* [11.x] Fix namespace and improvement PSR in `ClassMakeCommandTest.php` by @saMahmoudzadeh in https://github.com/laravel/framework/pull/51280
* [11.x] improvement test coverage for view components. by @saMahmoudzadeh in https://github.com/laravel/framework/pull/51271
* [11.x] Introduce method `Rule::array()` by @Jacobs63 in https://github.com/laravel/framework/pull/51250
* [11.x] Fix docblock for collection pluck methods by @SanderMuller in https://github.com/laravel/framework/pull/51295
* [11.x] Add tests for handling non-baked enum and empty string requests by @hrant1020 in https://github.com/laravel/framework/pull/51289
* blank and filled now support stringable by @lava83 in https://github.com/laravel/framework/pull/51300
* [11.x] Fix ratio validation for high ratio images by @ahmedbally in https://github.com/laravel/framework/pull/51296
* [11.x] Add int|float support to e method by @trippo in https://github.com/laravel/framework/pull/51314
* [11.x] Add release notes by @driesvints in https://github.com/laravel/framework/pull/51310
* [11.x] `Stringable` is also an interface of symfony by @lava83 in https://github.com/laravel/framework/pull/51309
* [11.x] Add some tests and improvement test coverage for `Str::camel` by @saMahmoudzadeh in https://github.com/laravel/framework/pull/51308
* [11.x] Using the `??` Operator (Null Coalescing Operator)  by @saMahmoudzadeh in https://github.com/laravel/framework/pull/51305
* [11.x] Add ability to override the default loading cached Routes for application by @ahmedabdel3al in https://github.com/laravel/framework/pull/51292
* [11.x] Add ->whereJsonOverlaps() for mysql by @parkourben99 in https://github.com/laravel/framework/pull/51288
* [11.x] Add `InteractsWithInput` methods to `ValidatedInput` by @aydinfatih in https://github.com/laravel/framework/pull/51316
* [11.x] Adding PasswordResetLinkSent event by @Muffinman in https://github.com/laravel/framework/pull/51253

## [v11.6.0](https://github.com/laravel/framework/compare/v11.5.0...v11.6.0) - 2024-04-30

* [11.x] github: mariadb database healthcheck+naming by @grooverdan in https://github.com/laravel/framework/pull/51192
* Add support for PHPUnit 11.1 by @crynobone in https://github.com/laravel/framework/pull/51197
* Move whitespace in front of verbatim block in Blade templates by @Sjord in https://github.com/laravel/framework/pull/51195
* [11.x] Trim trailing `?` from generated URL without query params by @onlime in https://github.com/laravel/framework/pull/51191
* Add some tests on route:list sort command by @fgaroby in https://github.com/laravel/framework/pull/51202
* [10.x] Improve releases flow by @driesvints in https://github.com/laravel/framework/pull/51213
* Fix return types of `firstWhere` and `first` of `BelongsToMany` and `HasManyThrough` by @SanderMuller in https://github.com/laravel/framework/pull/51219
* [10.x] Fix typo in signed URL tampering tests by @Krisell in https://github.com/laravel/framework/pull/51238
* [10.x] Add "Server has gone away" to DetectsLostConnection by @Jubeki in https://github.com/laravel/framework/pull/51241
* [11.x] Add  some tests in `SupportStrTest` class  by @saMahmoudzadeh in https://github.com/laravel/framework/pull/51235
* [10.x] Fix support for the LARAVEL_STORAGE_PATH env var (#51238) by @dunglas in https://github.com/laravel/framework/pull/51243
* [11.x] Add replaceable tags to translations by @LegendEffects in https://github.com/laravel/framework/pull/51190
* [10.x] fix: Factory::createMany creating n^2 records by @calebdw in https://github.com/laravel/framework/pull/51225

## [v11.5.0](https://github.com/laravel/framework/compare/v11.4.0...v11.5.0) - 2024-04-23

* [11.x] Add namespace for `make:trait` and `make:interface` command by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/51083
* [11.x] Ability to generate URL's with query params by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/51075
* [11.x] Adds anonymous broadcasting by [@joedixon](https://github.com/joedixon) in https://github.com/laravel/framework/pull/51082
* [10.x] Binding order is incorrect when using cursor paginate with multiple unions with a where by [@thijsvdanker](https://github.com/thijsvdanker) in https://github.com/laravel/framework/pull/50884
* [10.x] Fix cursor paginate with union and column alias by [@thijsvdanker](https://github.com/thijsvdanker) in https://github.com/laravel/framework/pull/50882
* [11.x] Fix typo in tests by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/51093
* Fix argument type in `Cache\Store` by [@GromNaN](https://github.com/GromNaN) in https://github.com/laravel/framework/pull/51100
* Correct comment's grammatical and semantic errors by [@javadihugo](https://github.com/javadihugo) in https://github.com/laravel/framework/pull/51101
* [11.x] Replace matches typehint fix by [@henzeb](https://github.com/henzeb) in https://github.com/laravel/framework/pull/51095
* [11.x] Exclude `laravel_through_key` when replicating model, fixes #51097 by [@levu42](https://github.com/levu42) in https://github.com/laravel/framework/pull/51098
* [11.x] Add enum types to static Rule methods by [@erik-perri](https://github.com/erik-perri) in https://github.com/laravel/framework/pull/51090
* [11.x] Add decrement method to the rate limiter class by [@AlexJump24](https://github.com/AlexJump24) in https://github.com/laravel/framework/pull/51102
* [11.x] Remove dead code by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/51106
* [11.x] Fix support for other hashing implementations when using `hashed` cast by [@j3j5](https://github.com/j3j5) in https://github.com/laravel/framework/pull/51112
* Revert "[11.x] Adds support for `int` backed enums to implicit `Enum` route binding" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/51119
* [11.x] Add support for enums in `whereIn` route constraints by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/51121
* Clarify that \Illuminate\Http\Request::replace replace all input values by [@treyssatvincent](https://github.com/treyssatvincent) in https://github.com/laravel/framework/pull/51123
* [11.x] Fix db:show's --counts option by [@xuchunyang](https://github.com/xuchunyang) in https://github.com/laravel/framework/pull/51140
* Update RuntimeException message when no data has been found by [@mikemeijer](https://github.com/mikemeijer) in https://github.com/laravel/framework/pull/51133
* [11] Update DetectsLostConnections.php by [@it-can](https://github.com/it-can) in https://github.com/laravel/framework/pull/51127
* [11.x] Reset connection after migrate for FreshCommand by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/51167
* [10.x] Address Null Parameter Deprecations in UrlGenerator by [@aldobarr](https://github.com/aldobarr) in https://github.com/laravel/framework/pull/51148
* [11.x] Provide context for NestedRules by [@imahmood](https://github.com/imahmood) in https://github.com/laravel/framework/pull/51160
* [11.x] Fix renaming columns with `NULL` as default on legacy MariaDB/MySQL by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51177
* [11.x] Supercharge Blade by [@assertchris](https://github.com/assertchris) in https://github.com/laravel/framework/pull/51143
* [11.x] Allow implicit binding to have optional backed enums by [@Neol3108](https://github.com/Neol3108) in https://github.com/laravel/framework/pull/51178
* [11.x] Blade Component Loop Speed Improvement by [@lonnylot](https://github.com/lonnylot) in https://github.com/laravel/framework/pull/51158
* [11.x] Fix normalizedNameCache by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/51185
* [11.x] GenericUser use `getAuthPasswordName` instead of hardcoded column name by [@Daniel-H123](https://github.com/Daniel-H123) in https://github.com/laravel/framework/pull/51186

## [v11.4.0](https://github.com/laravel/framework/compare/v11.3.1...v11.4.0) - 2024-04-16

* [11.x] Apc Cache - Remove long-time gone apc_* functions by [@serpentblade](https://github.com/serpentblade) in https://github.com/laravel/framework/pull/51010
* [11.x] Allowing Usage of Livewire Wire Boolean Style Directives by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/51007
* [11.x] Introduces `Exceptions` facade by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50704
* [11.x] `afterQuery` hook by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/50587
* Fix computed columns mapping to wrong tables by [@maddhatter](https://github.com/maddhatter) in https://github.com/laravel/framework/pull/51009
* [11.x] improvement test for string title by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51015
* [11.x] Fix failing `afterQuery` method tests when using sql server by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/51016
* [11.x] Fix: Apply database connection  before checking if the repository exist by [@sjspereira](https://github.com/sjspereira) in https://github.com/laravel/framework/pull/51021
* [10.x] Fix error when using `orderByRaw()` in query before using `cursorPaginate()` by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/51023
* [11.x] Add RequiredIfDeclined validation rule by [@timmydhooghe](https://github.com/timmydhooghe) in https://github.com/laravel/framework/pull/51030
* [11.x] Adds support for enums on `mapInto` collection method by [@lukeraymonddowning](https://github.com/lukeraymonddowning) in https://github.com/laravel/framework/pull/51027
* [11.x] Fix prompt fallback return value when using numeric keys by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/50995
* [11.x] Adds support for `int` backed enums to implicit `Enum` route binding by [@monurakkaya](https://github.com/monurakkaya) in https://github.com/laravel/framework/pull/51029
* [11.x] Configuration to disable events on Cache Repository by [@serpentblade](https://github.com/serpentblade) in https://github.com/laravel/framework/pull/51032
* Revert "[11.x] Name of job set by displayName() must be honoured by S… by [@RobertBoes](https://github.com/RobertBoes) in https://github.com/laravel/framework/pull/51034
* chore: fix some typos in comments by [@laterlaugh](https://github.com/laterlaugh) in https://github.com/laravel/framework/pull/51037
* Name of job set by displayName() must be honoured by Schedule by [@SCIF](https://github.com/SCIF) in https://github.com/laravel/framework/pull/51038
* Fix more typos by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/51039
* [11.x] Fix some doc blocks by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/51043
* [11.x] Add [@throws](https://github.com/throws) ConnectionException tag on Http methods for IDE support by [@masoudtajer](https://github.com/masoudtajer) in https://github.com/laravel/framework/pull/51066
* [11.x] Add Prompts `textarea` fallback for tests and add assertion tests by [@lioneaglesolutions](https://github.com/lioneaglesolutions) in https://github.com/laravel/framework/pull/51055
* Validate MAC per key by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/51063
* [11.x] Add `throttle` method to `LazyCollection` by [@JosephSilber](https://github.com/JosephSilber) in https://github.com/laravel/framework/pull/51060
* [11.x] Pass decay seconds or minutes like hour and day by [@jimmypuckett](https://github.com/jimmypuckett) in https://github.com/laravel/framework/pull/51054
* [11.x] Consider after_commit config in SyncQueue by [@hansnn](https://github.com/hansnn) in https://github.com/laravel/framework/pull/51071
* [10.x] Database layer fixes by [@saadsidqui](https://github.com/saadsidqui) in https://github.com/laravel/framework/pull/49787
* [11.x] Fix context helper always requiring `$key` value by [@nikspyratos](https://github.com/nikspyratos) in https://github.com/laravel/framework/pull/51080
* [11.x] Fix `expectsChoice` assertion with optional `multiselect` prompts. by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/51078

## [v11.3.1](https://github.com/laravel/framework/compare/v11.3.0...v11.3.1) - 2024-04-10

* [11.x] Name of job set by displayName() must be honoured by Schedule by [@SCIF](https://github.com/SCIF) in https://github.com/laravel/framework/pull/50973
* Add Conditionable trait to Testing\PendingCommand.php by [@tobz-nz](https://github.com/tobz-nz) in https://github.com/laravel/framework/pull/50988
* Allow sorting of route:list by multiple column/factors using a comma by [@fredbradley](https://github.com/fredbradley) in https://github.com/laravel/framework/pull/50998
* [10.x] Added eachById and chunkByIdDesc to BelongsToMany by [@lonnylot](https://github.com/lonnylot) in https://github.com/laravel/framework/pull/50991

## [v11.3.0](https://github.com/laravel/framework/compare/v11.2.0...v11.3.0) - 2024-04-09

* [10.x] Prevent Redis connection error report flood on queue worker by [@kasus](https://github.com/kasus) in https://github.com/laravel/framework/pull/50812
* [11.x] Optimize SetCacheHeaders to ensure error responses aren't cached by [@MinaWilliam](https://github.com/MinaWilliam) in https://github.com/laravel/framework/pull/50903
* [11.x] Add session `hasAny` method by [@mahmoudmohamedramadan](https://github.com/mahmoudmohamedramadan) in https://github.com/laravel/framework/pull/50897
* [11.x] Add option to report throttled exception in ThrottlesExceptions middleware by [@JaZo](https://github.com/JaZo) in https://github.com/laravel/framework/pull/50896
* [11.x] Add DeleteWhenMissingModels attribute by [@Neol3108](https://github.com/Neol3108) in https://github.com/laravel/framework/pull/50890
* [11.x] Allow customizing TrimStrings::$except by [@grohiro](https://github.com/grohiro) in https://github.com/laravel/framework/pull/50901
* [11.x] Add pull methods to Context by [@renegeuze](https://github.com/renegeuze) in https://github.com/laravel/framework/pull/50904
* [11.x] Remove redundant code from MariaDbGrammar by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/50907
* [11.x] Explicit nullable parameter declarations to fix PHP 8.4 deprecation by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/50922
* [11.x] Add setters to cache stores by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/50912
* [10.x] Laravel 10x optional withSize for hasTable by [@apspan](https://github.com/apspan) in https://github.com/laravel/framework/pull/50888
* [11.x] Fix prompting for missing array arguments on artisan command by [@macocci7](https://github.com/macocci7) in https://github.com/laravel/framework/pull/50850
* [11.x] Add strict-mode safe hasAttribute method to Eloquent by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/50909
* [11.x] add function to get faked events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/50905
* [11.x] `retry` func - catch "Throwable" instead of Exception by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/50944
* chore: remove repetitive words by [@findseat](https://github.com/findseat) in https://github.com/laravel/framework/pull/50943
* [10.x] Add `serializeAndRestore()` to `NotificationFake` by [@dbpolito](https://github.com/dbpolito) in https://github.com/laravel/framework/pull/50935
* [11.x] Prevent crash when handling ConnectionException in HttpClient retry logic by [@shinsenter](https://github.com/shinsenter) in https://github.com/laravel/framework/pull/50955
* [11.x] Remove unknown parameters by [@naopusyu](https://github.com/naopusyu) in https://github.com/laravel/framework/pull/50965
* [11.x] Fixed typo in PHPDoc `[@param](https://github.com/param)` by [@naopusyu](https://github.com/naopusyu) in https://github.com/laravel/framework/pull/50967
* [11.x] Fix dockblock by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50979
* [11.x] Allow time to be faked in database lock by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/50981
* [11.x] Introduce method `Http::createPendingRequest()` by [@Jacobs63](https://github.com/Jacobs63) in https://github.com/laravel/framework/pull/50980
* [11.x] Add [@throws](https://github.com/throws) to some doc blocks by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50969
* [11.x] Fix PHP_MAXPATHLEN check for existing check of files for views by [@joshuaruesweg](https://github.com/joshuaruesweg) in https://github.com/laravel/framework/pull/50962
* [11.x] Allow to remove scopes from BelongsToMany relation by [@plumthedev](https://github.com/plumthedev) in https://github.com/laravel/framework/pull/50953
* [11.x] Throw exception if named rate limiter and model property do not exist by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/50908

## [v11.2.0](https://github.com/laravel/framework/compare/v11.1.1...v11.2.0) - 2024-04-02

* [11.x] Fix: update `[@param](https://github.com/param)` in some doc block by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50827
* [11.x] Fix: update [@return](https://github.com/return) in some doc blocks by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50826
* [11.x] Fix retrieving generated columns on legacy PostgreSQL by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/50834
* [11.x] Trim invisible characters by [@dasundev](https://github.com/dasundev) in https://github.com/laravel/framework/pull/50832
* [11.x] Add default value for `get` and `getHidden` on `Context` by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50824
* [11.x] Improves `serve` Artisan command by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50821
* [11.x] Rehash user passwords when logging in once by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/50843
* [11.x] Do not wipe database if it does not exists by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50838
* [11.x] Better database creation failure handling by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50836
* [11.x] Use Default Schema Name on SQL Server by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/50855
* Correct typing for startedAs and virtualAs database column definitions by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/50851
* Allow passing query Expression as column in Many-to-Many relationship by [@plumthedev](https://github.com/plumthedev) in https://github.com/laravel/framework/pull/50849
* [11.x] Fix `Middleware::trustHosts(subdomains: true)` by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/50877
* [11.x] Modify doc blocks for getGateArguments by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50874
* [11.x] Add `[@throws](https://github.com/throws)` to doc block for resolve method by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50873
* [11.x] Str trim methods by [@patrickomeara](https://github.com/patrickomeara) in https://github.com/laravel/framework/pull/50822
* [11.x] Add fluent helper by [@PhiloNL](https://github.com/PhiloNL) in https://github.com/laravel/framework/pull/50848
* [11.x] Add a new helper for context by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50878
* [11.x] `assertChain` and `assertNoChain` on job instance by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/50858
* [11.x] Remove redundant `getDefaultNamespace` method in some classes (class, interface and trait commands) by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50880
* [11.x] Remove redundant implementation of ConnectorInterface in MariaDbConnector by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50881
* [11.X] Fix: error when using `orderByRaw` in query before using `cursorPaginate` by [@ngunyimacharia](https://github.com/ngunyimacharia) in https://github.com/laravel/framework/pull/50887

## [v11.1.1](https://github.com/laravel/framework/compare/v11.1.0...v11.1.1) - 2024-03-28

* [11.x] Fix: update `[@param](https://github.com/param)` in doc blocks by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50791
* [11.x] Fix query builder `whereBetween` with CarbonPeriod and Carbon 3 by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/50792
* [11.x] Allows asserting no output in Artisan commands by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50702
* fix typo by [@elguitarraverde](https://github.com/elguitarraverde) in https://github.com/laravel/framework/pull/50808
* [11.x] Make DB::usingConnection() respect read/write type by [@SajtiDH](https://github.com/SajtiDH) in https://github.com/laravel/framework/pull/50806
* [11.x] Fix deprecation warning caused by Carbon 3.2 by [@JackWH](https://github.com/JackWH) in https://github.com/laravel/framework/pull/50813

## [v11.1.0](https://github.com/laravel/framework/compare/v11.0.8...v11.1.0) - 2024-03-26

* [11.x] MySQL transaction isolation level fix by [@mwikberg-virta](https://github.com/mwikberg-virta) in https://github.com/laravel/framework/pull/50689
* [11.x] Add ListManagementOptions in SES mail transport by [@arifszn](https://github.com/arifszn) in https://github.com/laravel/framework/pull/50660
* [11.x] Accept non-backed enum in database queries by [@gbalduzzi](https://github.com/gbalduzzi) in https://github.com/laravel/framework/pull/50674
* [11.x] Add `Conditionable` trait to `Context` by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50707
* [11.x] Adds `[@throws](https://github.com/throws)` section to the Context's doc blocks by [@rnambaale](https://github.com/rnambaale) in https://github.com/laravel/framework/pull/50715
* [11.x] Test modifying nullable columns by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/50708
* [11.x] Introduce HASH_VERIFY env var by [@valorin](https://github.com/valorin) in https://github.com/laravel/framework/pull/50718
* [11.x] Apply default timezone when casting unix timestamps by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/50751
* [11.x] Fixes `ApplicationBuilder::withCommandRouting()` usage by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50742
* [11.x] Register console commands, paths and routes after the app is booted by [@plumthedev](https://github.com/plumthedev) in https://github.com/laravel/framework/pull/50738
* [11.x] Enhance malformed request handling by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/50735
* [11.x] Adds `withSchedule` to `bootstrap/app.php` file by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50755
* [11.x] Fix dock block for create method in `InvalidArgumentException.php` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50762
* [11.x] signature typo by [@abrahamgreyson](https://github.com/abrahamgreyson) in https://github.com/laravel/framework/pull/50766
* [11.x] Simplify `ApplicationBuilder::withSchedule()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50765

## [v11.0.8](https://github.com/laravel/framework/compare/v11.0.7...v11.0.8) - 2024-03-21

* [11.x] Change typehint for enum rule from string to class-string by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/50603
* [11.x] Fixed enum and enum.backed stub paths after publish by [@haroon-mahmood-4276](https://github.com/haroon-mahmood-4276) in https://github.com/laravel/framework/pull/50629
* [11.x] Fix(ScheduleListCommand): fix doc block for listEvent method by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50638
* [11.x] Re: Fix issue with missing 'js/' directory in broadcasting installation command by [@alnahian2003](https://github.com/alnahian2003) in https://github.com/laravel/framework/pull/50657
* [11.x] Remove `$except` property from `ExcludesPaths` trait by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/50644
* [11.x] Fix command alias registration and usage. by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/50617
* [11.x] Fixed make:session-table Artisan command cannot be executed if a migration exists by [@naopusyu](https://github.com/naopusyu) in https://github.com/laravel/framework/pull/50615
* [11.x] Fix(src\illuminate\Queue): update doc block, Simplification of the code in RedisManager by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50635
* [11.x] Add `--without-reverb` and `--without-node` arguments to `install:broadcasting` command by [@duncanmcclean](https://github.com/duncanmcclean) in https://github.com/laravel/framework/pull/50662
* [11.x] Fixed `trait` stub paths after publish by [@haroon-mahmood-4276](https://github.com/haroon-mahmood-4276) in https://github.com/laravel/framework/pull/50678
* [11.x] Fixed `class` and `class.invokable` stub paths after publish by [@haroon-mahmood-4276](https://github.com/haroon-mahmood-4276) in https://github.com/laravel/framework/pull/50676
* [10.x] Fix `Collection::concat()` return type by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/50669
* [11.x] Fix adding multiple bootstrap providers with opcache by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/50665
* [11.x] Allow `BackedEnum` and `UnitEnum` in `Rule::in` and `Rule::notIn` by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/framework/pull/50680
* [10.x] Fix command alias registration and usage by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50695

## [v11.0.7](https://github.com/laravel/framework/compare/v11.0.6...v11.0.7) - 2024-03-15

* [11.x] Re-add translations for ValidationException by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50546
* [11.x] Removes unused Dumpable trait by [@OussamaMater](https://github.com/OussamaMater) in https://github.com/laravel/framework/pull/50559
* [11.x] Fix withRouting docblock type by [@santigarcor](https://github.com/santigarcor) in https://github.com/laravel/framework/pull/50563
* [11.x] Fix docblock in FakeInvokedProcess.php by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50568
* [11.x] fix: Add missing InvalidArgumentException import to Database/Schema/SqlServerBuilder by [@ayutaya](https://github.com/ayutaya) in https://github.com/laravel/framework/pull/50573
* [11.x] Improved translation for displaying the count of errors in the validation message by [@andrey-helldar](https://github.com/andrey-helldar) in https://github.com/laravel/framework/pull/50560
* [11.x] Fix retry_after to be an integer by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50580
* [11.x] Use available `getPath()` instead of using `app_path()` to detect if base controller exists by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50583
* [11.x] Fix doc block: `[@return](https://github.com/return) static` has been modified to `[@return](https://github.com/return) void` by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50592
* accept attributes for channels by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/commit/398f49485e305756409b52af64837c784fd30de9

## [v11.0.6](https://github.com/laravel/framework/compare/v11.0.5...v11.0.6) - 2024-03-14

* [11.x] Fix version constraints for illuminate/process by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/50524
* [11.x] Update Broadcasting Install Command With Bun Support by [@HDVinnie](https://github.com/HDVinnie) in https://github.com/laravel/framework/pull/50525
* [11.x] Allows to comment `web` and `health` routes by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50533
* [11.x] Add generics for Arr::first() by [@phh](https://github.com/phh) in https://github.com/laravel/framework/pull/50514
* Change default collation for MySQL by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50555
* [11.x] Fixes install:broadcasting command by [@joedixon](https://github.com/joedixon) in https://github.com/laravel/framework/pull/50550
* [11.x] Fix crash when configuration directory is non-existing by [@buismaarten](https://github.com/buismaarten) in https://github.com/laravel/framework/pull/50537

## [v11.0.5](https://github.com/laravel/framework/compare/v11.0.4...v11.0.5) - 2024-03-13

* [11.x] Improves broadcasting install by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50519
* [11.x] Improved exception message on 'ensure' method by [@fgaroby](https://github.com/fgaroby) in https://github.com/laravel/framework/pull/50517
* [11.x] Add hasValidRelativeSignatureWhileIgnoring macro by [@br13an](https://github.com/br13an) in https://github.com/laravel/framework/pull/50511
* [11.x] Prevents database redis options of being merged by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50523

## [v11.0.4](https://github.com/laravel/framework/compare/v11.0.3...v11.0.4) - 2024-03-13

* [11.x] Add class_exists check for `Spark`'s `subscribed` default alias Middleware by [@akr4m](https://github.com/akr4m) in https://github.com/laravel/framework/pull/50489
* [11.x] Fix: Removed TTY mode to resolve Windows compatibility issue  by [@yourchocomate](https://github.com/yourchocomate) in https://github.com/laravel/framework/pull/50495
* [11.x] Check for password before storing hash in session by [@valorin](https://github.com/valorin) in https://github.com/laravel/framework/pull/50507
* [11.x] Fix an issue with missing controller class by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50505
* [11.x] Add default empty config when creating repository within CacheManager by [@noefleury](https://github.com/noefleury) in https://github.com/laravel/framework/pull/50510

## [v11.0.3](https://github.com/laravel/framework/compare/v11.0.2...v11.0.3) - 2024-03-12

* [11.x] Arr helper map spread by [@bilfeldt](https://github.com/bilfeldt) in https://github.com/laravel/framework/pull/50474
* [11.x] add `list` rule by [@medilies](https://github.com/medilies) in https://github.com/laravel/framework/pull/50454
* [11.x] Fixes installation of passport by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50488

## [v11.0.2](https://github.com/laravel/framework/compare/v11.0.1...v11.0.2) - 2024-03-12

* [11.x] Adds `--graceful` to `php artisan migrate` by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/50486

## [v11.0.1](https://github.com/laravel/framework/compare/v11.0.0..v11.0.1) - 2024-03-12

* [10.x] Update mockery conflict to just disallow the broken version by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/50472
* [10.x] Conflict with specific release by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50473
* [10.x] Fix for attributes being escaped on Dynamic Blade Components by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/framework/pull/50471
* [10.x] Revert PR 50403 by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50482

## v11.0.0 - 2024-03-12

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/11.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/11.x/releases).
