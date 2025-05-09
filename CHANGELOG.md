# Release Notes for 12.x

## [Unreleased](https://github.com/laravel/framework/compare/v12.13.0...12.x)

## [v12.13.0](https://github.com/laravel/framework/compare/v12.12.0...v12.13.0) - 2025-05-07

* [12.x] fix no arguments return type in request class by [@olivernybroe](https://github.com/olivernybroe) in https://github.com/laravel/framework/pull/55631
* [12.x] Add support for callback evaluation in containsOneItem method by [@fernandokbs](https://github.com/fernandokbs) in https://github.com/laravel/framework/pull/55622
* [12.x] add generics to aggregate related methods and properties by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55628
* [12.x] Fix typo in PHPDoc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55636
* [12.x] Allow naming queued closures by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/55634
* [12.x] Add `assertRedirectBack` assertion method by [@ryangjchandler](https://github.com/ryangjchandler) in https://github.com/laravel/framework/pull/55635
* [12.x] Typehints for bindings by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55633
* [12.x] add PHP Doc types to arrays for methods in Database\Grammar by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55629
* fix trim null arg deprecation by [@apreiml](https://github.com/apreiml) in https://github.com/laravel/framework/pull/55649
* [12.x] Support predis/predis 3.x by [@gabrielrbarbosa](https://github.com/gabrielrbarbosa) in https://github.com/laravel/framework/pull/55641
* Bump vite from 5.4.18 to 5.4.19 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/55655
* [12.x] Fix predis versions by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/55654
* [12.x] Bump minimum league/commonmark by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/55659
* [12.x] Fix typo in MemoizedStoreTest by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/55662
* [12.x] Queue event listeners with enum values by [@wgriffioen](https://github.com/wgriffioen) in https://github.com/laravel/framework/pull/55656
* [12.x] Implement releaseAfter method in RateLimited middleware by [@adamjgriffith](https://github.com/adamjgriffith) in https://github.com/laravel/framework/pull/55671
* [12.x] Improve Cache Tests by [@nuernbergerA](https://github.com/nuernbergerA) in https://github.com/laravel/framework/pull/55670
* [12.x] Only pass model IDs to Eloquent `whereAttachedTo` method by [@ashleyshenton](https://github.com/ashleyshenton) in https://github.com/laravel/framework/pull/55666
* feat(bus): allow adding multiple jobs to chain by [@dallyger](https://github.com/dallyger) in https://github.com/laravel/framework/pull/55668
* [12.x] add generics to QueryBuilder’s column related methods by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55663

## [v12.12.0](https://github.com/laravel/framework/compare/v12.11.1...v12.12.0) - 2025-05-01

* [12.x] Make Blueprint Resolver Statically by [@finagin](https://github.com/finagin) in https://github.com/laravel/framework/pull/55607
* [12.x] Allow limiting number of assets to preload by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55618
* [12.x] Set job instance on "failed" command instance by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/55617

## [v12.11.1](https://github.com/laravel/framework/compare/v12.11.0...v12.11.1) - 2025-04-30

* Revert "[12.x]`ScheduledTaskFailed` not dispatched on scheduled task failing" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55612
* [12.x] Resolve issue with BelongsToManyRelationship factory by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/55608

## [v12.11.0](https://github.com/laravel/framework/compare/v12.10.2...v12.11.0) - 2025-04-29

* Add payload creation and original delay info to job payload by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55529
* Add config option to ignore view cache timestamps by [@pizkaz](https://github.com/pizkaz) in https://github.com/laravel/framework/pull/55536
* [12.x] Dispatch NotificationFailed when sending fails by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/55507
* [12.x] Option to disable dispatchAfterResponse in a test by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55456
* [12.x] Pass flags to custom Json::$encoder by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/55548
* [12.x] Use pendingAttributes of relationships when creating relationship models via model factories by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55558
* [12.x] Fix double query in model relation serialization by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55547
* [12.x] Improve circular relation check in Automatic Relation Loading by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/55542
* [12.x] Prevent relation autoload context from being serialized by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/55582
* Remove `@internal` Annotation from `$components` Property in `InteractsWithIO` by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/55580
* Ensure fake job implements job contract by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55574
* [12.x] Fix `AnyOf` constructor parameter type by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/55577
* Sync changes to Illuminate components before release by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/55591
* [12.x] Set class-string generics on `Enum` rule by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55588
* [12.x] added detailed doc types to bindings related methods by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55576
* [12.x] Improve [@use](https://github.com/use) directive to support function and const modifiers by [@rodolfosrg](https://github.com/rodolfosrg) in https://github.com/laravel/framework/pull/55583
* 12.x scheduled task failed not dispatched on scheduled task failing by [@achrafAa](https://github.com/achrafAa) in https://github.com/laravel/framework/pull/55572
* [12.x] Introduce Reflector methods for accessing class attributes by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/55568
* [12.x] Typed getters for Arr helper by [@tibbsa](https://github.com/tibbsa) in https://github.com/laravel/framework/pull/55567

## [v12.10.2](https://github.com/laravel/framework/compare/v12.10.1...v12.10.2) - 2025-04-24

* [12.x] Address Model@relationLoaded when relation is null by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/55531

## [v12.10.1](https://github.com/laravel/framework/compare/v12.10.0...v12.10.1) - 2025-04-23

* Revert "Use value() helper in 'when' method to simplify code" #55465 by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55514
* [12.x] Use xxh128 when comparing views for changes by [@shawnlindstrom](https://github.com/shawnlindstrom) in https://github.com/laravel/framework/pull/55517
* [12.x] Ensure related models is iterable on `HasRelationships@relationLoaded()` by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/55519
* [12.x] Add Enum support for assertJsonPath in AssertableJsonString.php by [@azim-kordpour](https://github.com/azim-kordpour) in https://github.com/laravel/framework/pull/55516

## [v12.10.0](https://github.com/laravel/framework/compare/v12.9.2...v12.10.0) - 2025-04-22

* Use value() helper in 'when' method by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55465
* [12.x] Test `@use` directive without quotes by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/55462
* [12.x] Enhance Broadcast Events Test Coverage by [@roshandelpoor](https://github.com/roshandelpoor) in https://github.com/laravel/framework/pull/55458
* [12.x] Add `Conditionable` Trait to `Fluent`  by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/55455
* [12.x] Fix relation auto loading with manually set relations by [@patrickweh](https://github.com/patrickweh) in https://github.com/laravel/framework/pull/55452
* Add missing types to RateLimiter by [@ClaudioEyzaguirre](https://github.com/ClaudioEyzaguirre) in https://github.com/laravel/framework/pull/55445
* [12.x] Fix for global autoload relationships not working  in certain cases by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/55443
* [12.x] Fix adding `setTags` method on new cache flush events by [@erikn69](https://github.com/erikn69) in https://github.com/laravel/framework/pull/55405
* Fix: Unique lock not being released after transaction rollback in ShouldBeUnique jobs with afterCommit() by [@toshitsuna-otsuka](https://github.com/toshitsuna-otsuka) in https://github.com/laravel/framework/pull/55420
* [12.x] Extends `AsCollection` to map items into objects or other values by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/55383
* [12.x] Fix group imports in Blade `@use` directive by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/55461
* chore(tests): align test names with idiomatic naming style by [@kauffinger](https://github.com/kauffinger) in https://github.com/laravel/framework/pull/55496
* Update compiled views only if they actually changed by [@pizkaz](https://github.com/pizkaz) in https://github.com/laravel/framework/pull/55450
* Improve performance of Arr::dot method - 300x in some cases by [@cyppe](https://github.com/cyppe) in https://github.com/laravel/framework/pull/55495
* [12.x] Add tests for `CacheBasedSessionHandler` by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55487
* [12.x] Add tests for `FileSessionHandler` by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55484
* [12.x] Add tests for `DatabaseSessionHandler` by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55485
* [12.x] Fix many to many detach without IDs broken with custom pivot class by [@amir9480](https://github.com/amir9480) in https://github.com/laravel/framework/pull/55490
* [12.x] Support nested relations on `relationLoaded` method by [@tmsperera](https://github.com/tmsperera) in https://github.com/laravel/framework/pull/55471
* Bugfix for Cache::memo()->many() returning the wrong value with an integer key type by [@bmckay959](https://github.com/bmckay959) in https://github.com/laravel/framework/pull/55503
* [12.x] Allow Container to build `Migrator` from class name by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55501

## [v12.9.2](https://github.com/laravel/framework/compare/v12.9.1...v12.9.2) - 2025-04-16

* [12.x] Fixed a bug in using `illuminate/console` in external apps by [@andrey-helldar](https://github.com/andrey-helldar) in https://github.com/laravel/framework/pull/55430
* Disable SQLServer 2017 CI as `ubuntu-20.24` has been removed by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55425

## [v12.9.1](https://github.com/laravel/framework/compare/v12.9.0...v12.9.1) - 2025-04-16

* [12.x] Forward only passed arguments into Illuminate\Database\Eloquent\Collection::partition method by [@MarekVikartovsky](https://github.com/MarekVikartovsky) in https://github.com/laravel/framework/pull/55422
* [12.x] Add test for complex context manipulation in Logger by [@roshandelpoor](https://github.com/roshandelpoor) in https://github.com/laravel/framework/pull/55423
* [12.x] Remove unused var from `DumpCommand` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55431
* [12.x] Fix the serve command sometimes fails to destructure the request pool array by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/55427
* [12.x] Changes to `package-lock.json` should trigger `npm run build` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55426

## [v12.9.0](https://github.com/laravel/framework/compare/v12.8.1...v12.9.0) - 2025-04-15

* Add types to ViewErrorBag by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/55329
* Add types to MessageBag by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/55327
* [12.x] add generics to commonly used methods in Schema/Builder by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55330
* Return frozen time for easier testing by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/55323
* Enhance DetectsLostConnections to Support AWS Aurora Credential Rotation Scenario by [@msaifmfz](https://github.com/msaifmfz) in https://github.com/laravel/framework/pull/55331
* [12.x] Rename test method of failedRequest() by [@LKaemmerling](https://github.com/LKaemmerling) in https://github.com/laravel/framework/pull/55332
* feat: Add a callback to be called on transaction failure by [@dshafik](https://github.com/dshafik) in https://github.com/laravel/framework/pull/55338
* [12.x]  Add withRelationshipAutoloading method to model by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/55344
* [12.x] Enable HTTP client retries when middleware throws an exception by [@27pchrisl](https://github.com/27pchrisl) in https://github.com/laravel/framework/pull/55343
* [12.x] Fix Closure serialization error in automatic relation loading by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/55345
* Add test for Unique validation rule with WhereIn constraints by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55351
* Add [@throws](https://github.com/throws) in doc-blocks by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55361
* [12.x] Update `propagateRelationAutoloadCallbackToRelation` method doc-block by [@derian-all-win-software](https://github.com/derian-all-win-software) in https://github.com/laravel/framework/pull/55363
* [12.x]  - Redis - Establish connection first, before set the options by [@alexmontoanelli](https://github.com/alexmontoanelli) in https://github.com/laravel/framework/pull/55370
* [12.x] Fix translation FileLoader overrides with a missing key by [@fabio-ivona](https://github.com/fabio-ivona) in https://github.com/laravel/framework/pull/55342
* [12.x] Fix pivot model events not working when using the `withPivotValue` by [@amir9480](https://github.com/amir9480) in https://github.com/laravel/framework/pull/55280
* [12.x] Introduce memoized cache driver by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55304
* [12.x] Add test for Filesystem::lastModified() method by [@roshandelpoor](https://github.com/roshandelpoor) in https://github.com/laravel/framework/pull/55389
* [12.x] Supports `pda/pheanstalk` 7 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55397
* [12.x] Add comprehensive filesystem operation tests to FilesystemTest by [@roshandelpoor](https://github.com/roshandelpoor) in https://github.com/laravel/framework/pull/55399
* Bump vite from 5.4.17 to 5.4.18 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/55402
* Add descriptive error messages to assertViewHas() by [@3Descape](https://github.com/3Descape) in https://github.com/laravel/framework/pull/55392
* Use Generic Types Annotations for LazyCollection Methods by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55380
* [12.x] Add test coverage for Process sequence with multiple env variables by [@roshandelpoor](https://github.com/roshandelpoor) in https://github.com/laravel/framework/pull/55406
* [12.x] Fix cc/bcc/replyTo address merging in `MailMessage` by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/55404
* [12.x] Add a `make` function in the `Fluent` by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/55417

## [v12.8.1](https://github.com/laravel/framework/compare/v12.8.0...v12.8.1) - 2025-04-08

## [v12.8.0](https://github.com/laravel/framework/compare/v12.7.2...v12.8.0) - 2025-04-08

* [12.x] only check for soft deletes once when mass-pruning by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55274
* [12.x] Add createMany mass-assignment variants to `HasOneOrMany` relation by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/55262
* cosmetic: include is_array() case in match construct of getArrayableItems by [@epic-64](https://github.com/epic-64) in https://github.com/laravel/framework/pull/55275
* Add tests for InvokeSerializedClosureCommand by [@Amirhf1](https://github.com/Amirhf1) in https://github.com/laravel/framework/pull/55281
* [12.x] Temporarily prevents PHPUnit 12.1 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55297
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55306
* Bump vite from 5.4.12 to 5.4.17 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/55301
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55307
* [12.x] add generics to array types for Schema Grammars by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55314
* [12.x] fix missing nullable for Query/Grammar::compileInsertGetId by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55311
* [12.x] Adds `fromJson()` to Collection by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/55310
* [12.x] Fix `illuminate/database` usage as standalone package by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55309
* Correct array key in InteractsWithInput by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/55287
* [12.x] Fix support for adding custom observable events from traits by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/55286
* [12.x] Added Automatic Relation Loading (Eager Loading) Feature by [@litvinchuk](https://github.com/litvinchuk) in https://github.com/laravel/framework/pull/53655
* [12.x] Modify PHPDoc for Collection::chunkWhile functions to support preserving keys by [@jsvdvis](https://github.com/jsvdvis) in https://github.com/laravel/framework/pull/55324
* [12.x] Introduce Rule::anyOf() for Validating Against Multiple Rule Sets by [@brianferri](https://github.com/brianferri) in https://github.com/laravel/framework/pull/55191

## [v12.7.2](https://github.com/laravel/framework/compare/v12.7.1...v12.7.2) - 2025-04-03

## [v12.7.1](https://github.com/laravel/framework/compare/v12.7.0...v12.7.1) - 2025-04-03

## [v12.7.0](https://github.com/laravel/framework/compare/v12.6.0...v12.7.0) - 2025-04-03

* [12.x] `AbstractPaginator` should implement `CanBeEscapedWhenCastToString` by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55256
* [12.x] Add `whereAttachedTo()` Eloquent builder method by [@bakerkretzmar](https://github.com/bakerkretzmar) in https://github.com/laravel/framework/pull/55245
* Make Illuminate\Support\Uri Macroable by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/55260
* [12.x] Add resource helper functions to Model/Collections by [@TimKunze96](https://github.com/TimKunze96) in https://github.com/laravel/framework/pull/55107
* [12.x]: Use char(36) for uuid type on MariaDB < 10.7.0 by [@boedah](https://github.com/boedah) in https://github.com/laravel/framework/pull/55197
* [12.x] Introducing `toArray` to `ComponentAttributeBag` class by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55258

## [v12.6.0](https://github.com/laravel/framework/compare/v12.5.0...v12.6.0) - 2025-04-02

* [12.x] Dont stop pruning if pruning one model fails by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55237
* [12.x] Update Date Facade Docblocks by [@fdalcin](https://github.com/fdalcin) in https://github.com/laravel/framework/pull/55235
* Make `db:seed` command prohibitable by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/framework/pull/55238
* [12.x] Introducing `Rules\Password::appliedRules` Method by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55206
* [12.x] Allowing merging model attributes before insert via `Model::fillAndInsert()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55038
* [12.x] Fix type hints for DateTimeZone and DateTimeInterface on DateFactory by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55243
* [12.x] Fix DateFactory docblock type hints by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55244
* List missing `migrate:rollback` in DB::prohibitDestructiveCommands PhpDoc by [@spawnia](https://github.com/spawnia) in https://github.com/laravel/framework/pull/55252
* [12.x] Add `Http::requestException()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55241
* New: Uri `pathSegments()` helper method by [@chester-sykes](https://github.com/chester-sykes) in https://github.com/laravel/framework/pull/55250
* [12.x] Do not require returning a Builder instance from a local scope method by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55246

## [v12.5.0](https://github.com/laravel/framework/compare/v12.4.1...v12.5.0) - 2025-04-01

* Correct misspellings by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/55218
* [12.x] Add ability to flush state on Vite helper by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55228
* [12.x] Support taggeable store flushed cache events by [@erikn69](https://github.com/erikn69) in https://github.com/laravel/framework/pull/55223
* Revert "[12.x] Support taggeable store flushed cache events" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55232
* [12.x] Allow configuration of retry period for RoundRobin and Failover mail transports by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/55222
* [12.x] Add --json option to EventListCommand by [@hotsaucejake](https://github.com/hotsaucejake) in https://github.com/laravel/framework/pull/55207

## [v12.4.1](https://github.com/laravel/framework/compare/v12.4.0...v12.4.1) - 2025-03-30

* [12.x] Add `Expression` type to param `$value` of `QueryBuilder` `orHaving()` method by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/55202
* [12.x] Fix URL generation with optional parameters (regression in #54811) by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/55213
* [12.x] Fix failing tests on windows OS by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55210

## [v12.4.0](https://github.com/laravel/framework/compare/v12.3.0...v12.4.0) - 2025-03-29

* [12.x] Reset PHP’s peak memory usage when resetting scope for queue worker by [@TimWolla](https://github.com/TimWolla) in https://github.com/laravel/framework/pull/55069
* [12.x] Add `AsHtmlString` cast by [@ralphjsmit](https://github.com/ralphjsmit) in https://github.com/laravel/framework/pull/55071
* [12.x] Add `Arr::sole()` method by [@ralphjsmit](https://github.com/ralphjsmit) in https://github.com/laravel/framework/pull/55070
* Improve warning message in `ApiInstallCommand` by [@sajjadhossainshohag](https://github.com/sajjadhossainshohag) in https://github.com/laravel/framework/pull/55081
* [12.x] use already determined `related` property by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55075
* [12.x] use "class-string" where appropriate in relations by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55074
* [12.x] `QueueFake::listenersPushed()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55063
* [12.x] Added except() method to Model class for excluding attributes by [@vishal2931](https://github.com/vishal2931) in https://github.com/laravel/framework/pull/55072
* [12.x] fix: add TPivotModel default and define pivot property in {Belongs,Morph}ToMany by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55086
* [12.x] remove `@return` docblocks on constructors by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55076
* [12.x] Add NamedScope attribute by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/54450
* [12.x] Improve syntax highlighting for stub type files by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/55094
* [12.x] Prefer `new Collection` over `Collection::make` by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55091
* [12.x] Fix except() method to support casted values by [@vishal2931](https://github.com/vishal2931) in https://github.com/laravel/framework/pull/55124
* [12.x] Add testcase for findSole method by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/55115
* [12.x] Types: PasswordBroker::reset by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/55109
* [12.x] assertThrowsNothing by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55100
* [12.x] Fix type nullability on PasswordBroker.events property by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/55097
* [12.x] Fix return type annotation in decrementPendingJobs method by [@shane-zeng](https://github.com/shane-zeng) in https://github.com/laravel/framework/pull/55133
* [12.x] Fix return type annotation in compile method by [@shane-zeng](https://github.com/shane-zeng) in https://github.com/laravel/framework/pull/55132
* [12.x] feat: Add `whereNull` and `whereNotNull` to `Assertablejson` by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/55131
* [12.x] fix: use contextual bindings in class dependency resolution by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55090
* Better return types for `Illuminate\Queue\Jobs\Job::getJobId()` and `Illuminate\Queue\Jobs\DatabaseJob::getJobId()` methods by [@petrknap](https://github.com/petrknap) in https://github.com/laravel/framework/pull/55138
* Remove remaining [@return](https://github.com/return) tags from constructors by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55136
* [12.x] Various URL generation bugfixes by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/54811
* Add an optional `shouldRun` method to migrations. by [@danmatthews](https://github.com/danmatthews) in https://github.com/laravel/framework/pull/55011
* [12.x] `Uri` prevent empty query string by [@rojtjo](https://github.com/rojtjo) in https://github.com/laravel/framework/pull/55146
* [12.x] Only call the ob_flush function if there is active buffer in eventStream by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/55141
* [12.x] Add CacheFlushed Event by [@tech-wolf-tw](https://github.com/tech-wolf-tw) in https://github.com/laravel/framework/pull/55142
* [12.x] Update DateFactory method annotations for Carbon v3 compatibility by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/55151
* [12.x] Improve docblocks for file related methods of InteractsWithInput by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/55156
* [12.x] Enhance `FileViewFinder` doc-blocks by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55183
* Support using null-safe operator with `null` value by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/55175
* [12.x] Fix: Make Paginated Queries Consistent Across Pages by [@tomchkk](https://github.com/tomchkk) in https://github.com/laravel/framework/pull/55176
* [12.x] Add `pipe` method query builders by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55171
* [12.x] fix: one of many subquery constraints by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55168
* [12.x] fix(postgres): missing parentheses in whereDate/whereTime for json columns by [@saibotk](https://github.com/saibotk) in https://github.com/laravel/framework/pull/55159
* Fix factory creation through attributes  by [@davidstoker](https://github.com/davidstoker) in https://github.com/laravel/framework/pull/55190
* [12.x] Fix Concurrency::run to preserve callback result order by [@chaker2710](https://github.com/chaker2710) in https://github.com/laravel/framework/pull/55161
* [12.x] Log: Add optional keys parameter to `Log::withoutContext` to remove selected context from future logs by [@mattroylloyd](https://github.com/mattroylloyd) in https://github.com/laravel/framework/pull/55181
* [12.x] Add `Expression` type to param `$value` of `QueryBuilder` `having()` method by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/55200
* [12.x] Add flag to disable where clauses for `withAttributes` method on Eloquent Builder  by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55199

## [v12.3.0](https://github.com/laravel/framework/compare/v12.2.0...v12.3.0) - 2025-03-18

* [12.x] fixes https://github.com/laravel/octane/issues/1010 by [@mihaileu](https://github.com/mihaileu) in https://github.com/laravel/framework/pull/55008
* Added the missing 'trashed' event to getObservablesEvents() by [@duemti](https://github.com/duemti) in https://github.com/laravel/framework/pull/55004
* [12.x] Enhance PHPDoc for Manager classes with `@param-closure-this` by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/55002
* [12.x] Fix `PendingRequest` typehints for `post`, `patch`, `put`, `delete` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54998
* [12.x] Add test for untested methods in LazyCollection by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54996
* [12.x] fix indentation by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/54995
* [12.x] apply final Pint fixes by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55014
* Enhance validation tests: Add test for connection name detection in Unique rule by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54993
* [12.x] Add json:unicode cast to support JSON_UNESCAPED_UNICODE encoding by [@fuwasegu](https://github.com/fuwasegu) in https://github.com/laravel/framework/pull/54992
* [12.x] Add “Storage Linked” to the `about` command by [@adampatterson](https://github.com/adampatterson) in https://github.com/laravel/framework/pull/54949
* [12.x] Add support for native JSON/JSONB column types in SQLite Schema builder by [@fuwasegu](https://github.com/fuwasegu) in https://github.com/laravel/framework/pull/54991
* [12.x] Fix `LogManager::configurationFor()` typehint by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55016
* [12.x] Add missing tests for LazyCollection methods by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/55022
* [12.x] Refactor: Structural improvement for clarity by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55018
* Improve `toKilobytes` to handle spaces and case-insensitive units by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/55019
* [12.x] Fix mistake in `asJson` call in `HasAttributes.php` that was recently introduced by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55017
* [12.x] reapply Pint style changes by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55015
* Add validation test for forEach with null and empty array values by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/55047
* [12.x] Types: EnumeratesValues Sum by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/55044
* [12.x] Ensure Consistent Formatting in Generated Invokable Classes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55034
* Add element type to return array in Filesystem by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/55031
* [12.x] Add support for PostgreSQL "unique nulls not distinct" by [@thierry2015](https://github.com/thierry2015) in https://github.com/laravel/framework/pull/55025
* [12.x] standardize multiline ternaries by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55056
* [12.x] improved readability for `aliasedPivotColumns` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55055
* [12.x] remove progress bar from PHPStan output by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55054
* [12.x] Fixes how the fluent Date rule builder handles `date_format` by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/55052
* Adding SSL encryption and support for MySQL connection by [@mdiktushar](https://github.com/mdiktushar) in https://github.com/laravel/framework/pull/55048
* Revert "Adding SSL encryption and support for MySQL connection" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55057
* Ensure queue property is nullable by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55058
* [12.x] return `$this` for chaining by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55060
* [12.x] prefer `new Collection` over `collect()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55059
* [12.x] use "class-string" type for `using` pivot model by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55053
* [12.x] multiline chaining on Collections by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/55061

## [v12.2.0](https://github.com/laravel/framework/compare/v12.1.1...v12.2.0) - 2025-03-12

* Add dates to allowed PHPDoc types of Builder::having() by [@miken32](https://github.com/miken32) in https://github.com/laravel/framework/pull/54899
* [11.x] Fix double negative in `whereNotMorphedTo()` query by [@owenvoke](https://github.com/owenvoke) in https://github.com/laravel/framework/pull/54902
* Add test for Arr::partition by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/54913
* [11.x] Expose process checkTimeout method by [@mattmcdev](https://github.com/mattmcdev) in https://github.com/laravel/framework/pull/54912
* [12.x] Compilable for Validation Contract by [@peterfox](https://github.com/peterfox) in https://github.com/laravel/framework/pull/54882
* [11.x] Backport "Change `paginate()` method return types to `\Illuminate\Pagination\LengthAwarePaginator`" by [@carestad](https://github.com/carestad) in https://github.com/laravel/framework/pull/54917
* [11.x] Revert faulty change to `EnumeratesValues::ensure()` doc block by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/54919
* Ensure ValidationEmailRuleTest skips tests requiring the intl extension when unavailable by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54918
* ✅ Ensure Enum validation is case-sensitive by adding a new test case. by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54922
* [12.x] Feature: Collection chunk without preserving keys by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/54916
* [12.x] Add test coverage for Uri::withQueryIfMissing method by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54923
* Fix issue with using RedisCluster with compression or serialization by [@rzv-me](https://github.com/rzv-me) in https://github.com/laravel/framework/pull/54934
* [12.x] Add test coverage for Str::replaceMatches method  by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54930
* [12.x] Types: Collection chunk without preserving keys  by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/54924
* [12.x] Add `ddBody` method to TestResponse for dumping various response payloads by [@Sammyjo20](https://github.com/Sammyjo20) in https://github.com/laravel/framework/pull/54933
* [11.x] Backport "Fix issue with using `RedisCluster` with compression or serialization" by [@rzv-me](https://github.com/rzv-me) in https://github.com/laravel/framework/pull/54935
* [12.x] feat: add `CanBeOneOfMany` support to `HasOneThrough` by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/54759
* [12.x] Hotfix - Add function_exists check to ddBody in TestResponse by [@Sammyjo20](https://github.com/Sammyjo20) in https://github.com/laravel/framework/pull/54937
* [12.x] Refactor: Remove unnecessary variables in Str class methods by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54963
* Add Tests for Str::pluralPascal Method by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54957
* [12.x] Fix visibility of setUp and tearDown in tests by [@naopusyu](https://github.com/naopusyu) in https://github.com/laravel/framework/pull/54950
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54944
* Fix missing return in `assertOnlyInvalid` by [@parth391](https://github.com/parth391) in https://github.com/laravel/framework/pull/54941
* Handle case when migrate:install command is called and table exists by [@joe-tito](https://github.com/joe-tito) in https://github.com/laravel/framework/pull/54938
* [11.x] Fix callOnce in Seeder so it handles arrays properly by [@lbovit](https://github.com/lbovit) in https://github.com/laravel/framework/pull/54985
* Change "exceptoin" spelling mistake to "exception" by [@hvlucas](https://github.com/hvlucas) in https://github.com/laravel/framework/pull/54979
* [12.x] Add test for after method in LazyCollection by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54978
* [12.x] Add `increment` and `decrement` methods to `Context` by [@mattmcdev](https://github.com/mattmcdev) in https://github.com/laravel/framework/pull/54976
* Ensure ExcludeIf correctly rejects a null value as an invalid condition by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54973
* [12.x] apply Pint rule "no_spaces_around_offset" by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/54970
* [12.x] apply Pint rule "single_line_comment_style" by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/54969
* [12.x] do not use mix of newline and inline formatting by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/54967
* [12.x] use single indent for multiline ternaries by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/54971

## [v12.1.1](https://github.com/laravel/framework/compare/v12.1.0...v12.1.1) - 2025-03-05

* [11.x] Add valid values to ensure method by [@lancepioch](https://github.com/lancepioch) in https://github.com/laravel/framework/pull/54840
* Fix attribute name used on `Validator` instance within certain rule classes by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54845
* [11.x] Fix `Application::interBasePath()` fails to resolve application when project name is "vendor" by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54871
* [11.x] Test improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54879
* [12.x] DocBlock: Changed typehint for `Arr::partition` method by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/54896
* Enhance Email and Image Dimensions Validation Tests by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54897
* [12.x] Apply default styling rules to the notification stub by [@ahinkle](https://github.com/ahinkle) in https://github.com/laravel/framework/pull/54895

## [v12.1.0](https://github.com/laravel/framework/compare/v12.0.1...v12.1.0) - 2025-03-04

* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54782
* [12.x] Fix incorrect typehints in `BuildsWhereDateClauses` traits by [@mohprilaksono](https://github.com/mohprilaksono) in https://github.com/laravel/framework/pull/54784
* [12.x] Improve queries readablility by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/54791
* [12.x] Enhance eventStream to Support Custom Events and Start Messages by [@devhammed](https://github.com/devhammed) in https://github.com/laravel/framework/pull/54776
* [12.x] Make the PendingCommand class tappable. by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/54801
* [12.x] Add missing union type in event stream docblock by [@devhammed](https://github.com/devhammed) in https://github.com/laravel/framework/pull/54800
* Change return types of `paginage()` methods to `\Illuminate\Pagination\LengthAwarePaginator` by [@carestad](https://github.com/carestad) in https://github.com/laravel/framework/pull/54826
* [12.x] Check if internal `Hasher::verifyConfiguration()` method exists on driver before forwarding call by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/54833
* [11.x] Fix using `AsStringable` cast on Notifiable's key by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54818
* Add Tests for Handling Null Primary Keys and Special Values in Unique Validation Rule by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54823
* Improve docblock for with() method to clarify it adds to existing eag… by [@igorlealantunes](https://github.com/igorlealantunes) in https://github.com/laravel/framework/pull/54838
* [12.x] Fix dropping schema-qualified prefixed tables by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/54834
* [12.x] Add `Context::scope()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54799
* Allow Http requests to be recorded without requests being faked by [@kemp](https://github.com/kemp) in https://github.com/laravel/framework/pull/54850
* [12.x] Adds a new method "getRawSql" (with embedded bindings) to the QueryException class by [@erickcomp](https://github.com/erickcomp) in https://github.com/laravel/framework/pull/54849
* Update Inspiring.php by [@ju-gow](https://github.com/ju-gow) in https://github.com/laravel/framework/pull/54846
* [12.x] Correct use of named argument in `Date` facade and fix a return type.  by [@lmottasin](https://github.com/lmottasin) in https://github.com/laravel/framework/pull/54847
* Add additional tests for Rule::array validation scenarios by [@alikhosravidev](https://github.com/alikhosravidev) in https://github.com/laravel/framework/pull/54844
* [12.x] Remove return statement  by [@mohprilaksono](https://github.com/mohprilaksono) in https://github.com/laravel/framework/pull/54842
* Fix typos by [@co63oc](https://github.com/co63oc) in https://github.com/laravel/framework/pull/54839
* [12.x] Do not loop through middleware when excluded is empty by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54837
* Add test for Arr::reject method in Illuminate Support by [@mohammadrasoulasghari](https://github.com/mohammadrasoulasghari) in https://github.com/laravel/framework/pull/54863
* [12.x] Feature: Array partition by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/54859
* [12.x] Introduce `ContextLogProcessor` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54851

## [v12.0.1](https://github.com/laravel/framework/compare/v12.0.0...v12.0.1) - 2025-02-24

## [v12.0.0](https://github.com/laravel/framework/compare/v11.44.0..v12.0.0...v12.0.0) - 2025-02-24

* [12.x] Prep Laravel v12 by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50406
* [12.x] Make `Str::is()` match multiline strings by [@SjorsO](https://github.com/SjorsO) in https://github.com/laravel/framework/pull/51196
* [12.x] Use native MariaDB CLI commands by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51505
* [12.x] Adds missing streamJson() to ResponseFactory contract by [@wilsenhc](https://github.com/wilsenhc) in https://github.com/laravel/framework/pull/51544
* [12.x] Preserve numeric keys on the first level of the validator rules by [@Tofandel](https://github.com/Tofandel) in https://github.com/laravel/framework/pull/51516
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/52248
* [12.x] mergeIfMissing allows merging with nested arrays by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/52242
* [12.x] Fix chunked queries not honoring user-defined limits and offsets by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/52093
* [12.x] Replace md5 with much faster xxhash by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/52301
* [12.x] Switch models to UUID v7 by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/52433
* [12.x] Improved algorithm for Number::pairs() by [@hotmeteor](https://github.com/hotmeteor) in https://github.com/laravel/framework/pull/52641
* Removed Duplicated Prefix on DynamoDbStore.php by [@felipehertzer](https://github.com/felipehertzer) in https://github.com/laravel/framework/pull/52986
* [12.x] feat: configure default datetime precision on per-grammar basis by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/51821
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53150
* [12.x] Fix laravel/prompt dependency version constraint for illuminate/console by [@wouterj](https://github.com/wouterj) in https://github.com/laravel/framework/pull/53146
* [12.x] Add generic return type to Container::instance() by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/53161
* Map output of concurrecy calls to the index of the input by [@ovp87](https://github.com/ovp87) in https://github.com/laravel/framework/pull/53135
* Change Composer hasPackage to public by [@buihanh2304](https://github.com/buihanh2304) in https://github.com/laravel/framework/pull/53282
* [12.x] force `Eloquent\Collection::partition` to return a base `Collection` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53304
* [12.x] Better support for multi-dbs in the `RefreshDatabase` trait by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/53231
* [12.x] Validate UUID's version optionally by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53341
* [12.x] Validate UUID version 2 and max by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53368
* [12.x] Add step parameter to LazyCollection range method by [@Ashot1995](https://github.com/Ashot1995) in https://github.com/laravel/framework/pull/53473
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53524
* [12.x] Avoid breaking change `RefreshDatabase::usingInMemoryDatabase()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53587
* [12.x] fix: container resolution order when resolving class dependencies by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/53522
* [12.x] Change the default for scheduled command `emailOutput()` to only send email if output exists by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/53774
* [12.x] Add `hasMorePages()` to `CursorPaginator` contract by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/53762
* [12.x] modernize `DatabaseTokenRepository` and make consistent with `CacheTokenRepository` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53746
* [12.x] chore: remove support for Carbon v2 by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/53825
* [12.x] use promoted properties for Auth events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53847
* [12.x] use promoted properties for Database events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53848
* [12.x] use promoted properties for Console events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53851
* [12.x] use promoted properties for Mail events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53852
* [12.x] use promoted properties for Notification events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53853
* [12.x] use promoted properties for Routing events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53854
* [12.x] use promoted properties for Queue events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53855
* [12.x] Restore database token repository property documentation by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53908
* [12.x] Use reject() instead of a negated filter() by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53925
* [12.x] Use first-class callable syntax to improve static analysis by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/53924
* [12.x] add type declarations for Console Events by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53947
* [12.x] use type declaration on property by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/53970
* [12.x] Update Symfony and PHPUnit dependencies by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54019
* [12.x] Allow `when()` helper to accept Closure condition parameter by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/54005
* [12.x] Add test for collapse in collections by [@amirmohammadnajmi](https://github.com/amirmohammadnajmi) in https://github.com/laravel/framework/pull/54032
* [12.x] Add test for benchmark utilities by [@amirmohammadnajmi](https://github.com/amirmohammadnajmi) in https://github.com/laravel/framework/pull/54055
* [12.x] Fix once() cache when used in extended static class by [@FrittenKeeZ](https://github.com/FrittenKeeZ) in https://github.com/laravel/framework/pull/54094
* [12.x] Ignore querystring parameters using closure when validating signed url  by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/54104
* Make `dropForeignIdFor` method complementary to `foreignIdFor` by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/54102
* Allow scoped disks to be scoped from other scoped disks by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/54124
* [12.x] Add test for Util::getParameterClassName() by [@amirmohammadnajmi](https://github.com/amirmohammadnajmi) in https://github.com/laravel/framework/pull/54209
* Improve eloquent attach parameter consistency by [@fabpl](https://github.com/fabpl) in https://github.com/laravel/framework/pull/54225
* [12.x] Enhance multi-database support by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/54274
* [12.x] Fix Session's `getCookieExpirationDate` incompatibility with Carbon 3 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54313
* [12.x] Update minimum PHPUnit versions by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54323
* [12.x] Prevent XSS vulnerabilities by excluding SVGs by default in image validation by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/54331
* [12.x] Convert interfaces from docblock to method by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54348
* [12.x] Validate paths for UTF-8 characters by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/54370
* [12.x] Fix aggregate alias when using  expression by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/54418
* Added flash method to Session interface to fix IDE issues by [@eldair](https://github.com/eldair) in https://github.com/laravel/framework/pull/54421
* Adding the withQueryString method to the paginator interface. by [@dvlpr91](https://github.com/dvlpr91) in https://github.com/laravel/framework/pull/54462
* [12.x] feat: --memory=0 should mean skip memory exceeded verification (Breaking Change) by [@mathiasgrimm](https://github.com/mathiasgrimm) in https://github.com/laravel/framework/pull/54393
* Auto-discover nested policies following conventional, parallel hierarchy by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/54493
* [12.x] Reintroduce PHPUnit 10.5 supports by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54490
* [12.x] Allow limiting bcrypt hashing to 72 bytes to prevent insecure hashes. by [@waxim](https://github.com/waxim) in https://github.com/laravel/framework/pull/54509
* [12.x] Fix accessing `Connection` property in `Grammar` classes by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/54487
* [12.x] Configure connection on SQLite connector by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/54588
* [12.x] Introduce Job@resolveQueuedJobClass() by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54613
* [12.x] Bind abstract from concrete's return type  by [@peterfox](https://github.com/peterfox) in https://github.com/laravel/framework/pull/54628
* [12.x] Query builder PDO fetch modes by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/54443
* [12.x] Fix Illuminate components `composer.json` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54700
* [12.x] Bump minimum `brick/math` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54694
* [11.x] Fix parsing `PHP_CLI_SERVER_WORKERS` as `string` instead of `int` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54724
* [11.x] Rename Redis parse connection for cluster test method to follow naming conventions by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/54721
* [11.x] Allow `readAt` method to use in database channel by [@utsavsomaiya](https://github.com/utsavsomaiya) in https://github.com/laravel/framework/pull/54729
* [11.x] Fix: Custom Exceptions with Multiple Arguments does not properly rein… by [@pandiselvamm](https://github.com/pandiselvamm) in https://github.com/laravel/framework/pull/54705
* [11.x] Update ConcurrencyTest exception reference to use namespace by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/54732
* [11.x] Deprecate `Factory::$modelNameResolver` by [@samlev](https://github.com/samlev) in https://github.com/laravel/framework/pull/54736
* Update `config/app.php` to reflect laravel/laravel change for compatibility by [@askdkc](https://github.com/askdkc) in https://github.com/laravel/framework/pull/54752
* [11x.] Improved typehints for `InteractsWithDatabase` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54748
* [11.x] Improved typehints for `InteractsWithExceptionHandling` && `ExceptionHandlerFake` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54747
* Add Env::extend to support custom adapters when loading environment variables by [@andrii-androshchuk](https://github.com/andrii-androshchuk) in https://github.com/laravel/framework/pull/54756
* [12.x] Sync `filesystem.disk.local` configurations by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54764
