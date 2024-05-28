# Release Notes for 11.x

## [Unreleased](https://github.com/laravel/framework/compare/v11.9.0...11.x)

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
