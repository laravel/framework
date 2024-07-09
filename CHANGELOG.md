# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v10.48.16...10.x)

## [v10.48.16](https://github.com/laravel/framework/compare/v10.48.15...v10.48.16) - 2024-07-09

* [10.x] Fix Http::retry so that throw is respected for call signature Http::retry([1,2], throw: false) by [@paulyoungnb](https://github.com/paulyoungnb) in https://github.com/laravel/framework/pull/52002
* [10.x] Set application_name and character set as PostgreSQL DSN string by [@sunaoka](https://github.com/sunaoka) in https://github.com/laravel/framework/pull/51985

## [v10.48.15](https://github.com/laravel/framework/compare/v10.48.14...v10.48.15) - 2024-07-02

* [10.x] Set previous exception on `HttpResponseException` by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/51986

## [v10.48.14](https://github.com/laravel/framework/compare/v10.48.13...v10.48.14) - 2024-06-21

* [10.x] Fixes unable to call another command as a initialized instance of `Command` class by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/51824
* [10.x] fix handle `shift()` on an empty collection by [@Treggats](https://github.com/Treggats) in https://github.com/laravel/framework/pull/51841
* [10.x] Ensure`schema:dump` will dump the migrations table only if it exists by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/51827

## [v10.48.13](https://github.com/laravel/framework/compare/v10.48.12...v10.48.13) - 2024-06-18

* [10.x] Fix typo in return comment of createSesTransport method by [@zds-s](https://github.com/zds-s) in https://github.com/laravel/framework/pull/51688
* [10.x] Fix collection shift less than one item by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51686
* [10.x] Turn `Enumerable unless()`  $callback parameter optional by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51701
* Revert "[10.x] Turn `Enumerable unless()`  $callback parameter optional" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/51707

## [v10.48.12](https://github.com/laravel/framework/compare/v10.48.11...v10.48.12) - 2024-05-28

* [10.x] Fix typo by [@Issei0804-ie](https://github.com/Issei0804-ie) in https://github.com/laravel/framework/pull/51535
* [10.x] Fix SQL Server detection in database store by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51547
* [10.x] - Fix batch list loading in Horizon when serialization error by [@jeffortegad](https://github.com/jeffortegad) in https://github.com/laravel/framework/pull/51551
* [10.x] Fixes explicit route binding with `BackedEnum` by [@CAAHS](https://github.com/CAAHS) in https://github.com/laravel/framework/pull/51586

## [v10.48.11](https://github.com/laravel/framework/compare/v10.48.10...v10.48.11) - 2024-05-21

* [10.x] Backport: Fix SesV2Transport to use correct `EmailTags` argument by [@Tietew](https://github.com/Tietew) in https://github.com/laravel/framework/pull/51352
* [10.x] Fix PHPDoc typo by [@staudenmeir](https://github.com/staudenmeir) in https://github.com/laravel/framework/pull/51390
* [10.x] Fix `apa` on non ASCII characters by [@faissaloux](https://github.com/faissaloux) in https://github.com/laravel/framework/pull/51428
* [10.x] Fixes view engine resolvers leaking memory by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/51450
* [10.x] Do not use `app()` Foundation helper on `ViewServiceProvider` by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/51522

## [v10.48.10](https://github.com/laravel/framework/compare/v10.48.9...v10.48.10) - 2024-04-30

* [10.x] Fix typo in signed URL tampering tests by @Krisell in https://github.com/laravel/framework/pull/51238
* [10.x] Add "Server has gone away" to DetectsLostConnection by @Jubeki in https://github.com/laravel/framework/pull/51241
* [10.x] Fix support for the LARAVEL_STORAGE_PATH env var (#51238) by @dunglas in https://github.com/laravel/framework/pull/51243

## [v10.48.9](https://github.com/laravel/framework/compare/v10.48.8...v10.48.9) - 2024-04-23

* [10.x] Binding order is incorrect when using cursor paginate with multiple unions with a where by [@thijsvdanker](https://github.com/thijsvdanker) in https://github.com/laravel/framework/pull/50884
* [10.x] Fix cursor paginate with union and column alias by [@thijsvdanker](https://github.com/thijsvdanker) in https://github.com/laravel/framework/pull/50882
* [10.x] Address Null Parameter Deprecations in UrlGenerator by [@aldobarr](https://github.com/aldobarr) in https://github.com/laravel/framework/pull/51148

## [v10.48.8](https://github.com/laravel/framework/compare/v10.48.7...v10.48.8) - 2024-04-17

* [10.x] Fix error when using `orderByRaw()` in query before using `cursorPaginate()` by @axlon in https://github.com/laravel/framework/pull/51023
* [10.x] Database layer fixes by @saadsidqui in https://github.com/laravel/framework/pull/49787

## [v10.48.7](https://github.com/laravel/framework/compare/v10.48.6...v10.48.7) - 2024-04-10

* Fix more query builder methods by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/commit/95ef230339b15321493a08327f250c0760c95376

## [v10.48.6](https://github.com/laravel/framework/compare/v10.48.5...v10.48.6) - 2024-04-10

* [10.x] Added eachById and chunkByIdDesc to BelongsToMany by [@lonnylot](https://github.com/lonnylot) in https://github.com/laravel/framework/pull/50991

## [v10.48.5](https://github.com/laravel/framework/compare/v10.48.4...v10.48.5) - 2024-04-09

* [10.x] Prevent Redis connection error report flood on queue worker by [@kasus](https://github.com/kasus) in https://github.com/laravel/framework/pull/50812
* [10.x] Laravel 10x optional withSize for hasTable by [@apspan](https://github.com/apspan) in https://github.com/laravel/framework/pull/50888
* [10.x] Add `serializeAndRestore()` to `NotificationFake` by [@dbpolito](https://github.com/dbpolito) in https://github.com/laravel/framework/pull/50935

## [v10.48.4](https://github.com/laravel/framework/compare/v10.48.3...v10.48.4) - 2024-03-21

* [10.x] Fix `Collection::concat()` return type by @axlon in https://github.com/laravel/framework/pull/50669
* [10.x] Fix command alias registration and usage by @crynobone in https://github.com/laravel/framework/pull/50695

## [v10.48.3](https://github.com/laravel/framework/compare/v10.48.2...v10.48.3) - 2024-03-15

- Re-tag version

## [v10.48.2](https://github.com/laravel/framework/compare/v10.48.1...v10.48.2) - 2024-03-12

* [10.x] Update mockery conflict to just disallow the broken version by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/50472
* [10.x] Conflict with specific release by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50473
* [10.x] Fix for attributes being escaped on Dynamic Blade Components by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/framework/pull/50471
* [10.x] Revert PR 50403 by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50482

## [v10.48.1](https://github.com/laravel/framework/compare/v10.48.0...v10.48.1) - 2024-03-12

* [10.x] Add conflict for Mockery v1.6.8 by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50468

## [v10.48.0](https://github.com/laravel/framework/compare/v10.47.0...v10.48.0) - 2024-03-12

* fix: allow null, string and string array as allowed tags by [@maartenpaauw](https://github.com/maartenpaauw) in https://github.com/laravel/framework/pull/50409
* [10.x] Allow `Expression` at more places in Query Builder by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/framework/pull/50402
* [10.x] Sleep syncing by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/50392
* [10.x] Cleaning Trait on multi-lines by [@gcazin](https://github.com/gcazin) in https://github.com/laravel/framework/pull/50413
* fix: incomplete type for Builder::from property by [@sebj54](https://github.com/sebj54) in https://github.com/laravel/framework/pull/50426
* [10.x] After commit callback throwing an exception causes broken transactions afterwards by [@oprypkhantc](https://github.com/oprypkhantc) in https://github.com/laravel/framework/pull/50423
* [10.x] Anonymous component bound attribute values are evaluated twice by [@danharrin](https://github.com/danharrin) in https://github.com/laravel/framework/pull/50403
* [10.x] Fix for sortByDesc ignoring multiple attributes by [@TWithers](https://github.com/TWithers) in https://github.com/laravel/framework/pull/50431
* [10.x] Allow sync with carbon to be set from fake method by [@abenerd](https://github.com/abenerd) in https://github.com/laravel/framework/pull/50450
* [10.x] Improves `Illuminate\Mail\Mailables\Envelope` docblock by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50448
* [10.x] Incorrect return in `FileSystem.php` by [@gcazin](https://github.com/gcazin) in https://github.com/laravel/framework/pull/50459
* [10.x] fix return types by [@imahmood](https://github.com/imahmood) in https://github.com/laravel/framework/pull/50461
* fix: phpstan issue - right side of || always false by [@Carnicero90](https://github.com/Carnicero90) in https://github.com/laravel/framework/pull/50453

## [v10.47.0](https://github.com/laravel/framework/compare/v10.46.0...v10.47.0) - 2024-03-05

* [10.x] Allow for relation key to be an enum by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/50311
* FIx for "empty" strings passed to Str::apa() by [@tiagof](https://github.com/tiagof) in https://github.com/laravel/framework/pull/50335
* [10.x] Fixed header mail text component to not use markdown by [@dmyers](https://github.com/dmyers) in https://github.com/laravel/framework/pull/50332
* [10.x] Add test for the "empty strings in `Str::apa()`" fix by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/50340
* [10.x] Fix the cache cannot expire cache with `0` TTL by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/50359
* [10.x] Add fail on timeout to queue listener by [@saeedhosseiinii](https://github.com/saeedhosseiinii) in https://github.com/laravel/framework/pull/50352
* [10.x] Support sort option flags on sortByMany Collections by [@TWithers](https://github.com/TWithers) in https://github.com/laravel/framework/pull/50269
* [10.x] Add `whereAll` and `whereAny` methods to the query builder by [@musiermoore](https://github.com/musiermoore) in https://github.com/laravel/framework/pull/50344
* [10.x] Adds Reverb broadcasting driver by [@joedixon](https://github.com/joedixon) in https://github.com/laravel/framework/pull/50088

## [v10.46.0](https://github.com/laravel/framework/compare/v10.45.1...v10.46.0) - 2024-02-27

* [10.x] Ensure lazy-loading for trashed morphTo relations works by [@nuernbergerA](https://github.com/nuernbergerA) in https://github.com/laravel/framework/pull/50176
* [10.x] Arr::select not working when $keys is a string by [@Sicklou](https://github.com/Sicklou) in https://github.com/laravel/framework/pull/50169
* [10.x] Added passing loaded relationship to value callback by [@dkulyk](https://github.com/dkulyk) in https://github.com/laravel/framework/pull/50167
* [10.x] Fix optional charset and collation when creating database by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/50168
* [10.x] update doc block in PendingProcess.php by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50198
* [10.x] Fix Accepting nullable Parameters, updated doc block, and null pointer exception handling in batchable trait by [@saMahmoudzadeh](https://github.com/saMahmoudzadeh) in https://github.com/laravel/framework/pull/50209
* Make GuardsAttributes fillable property DocBlock more specific by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/50229
* [10.x] Add only and except methods to Enum validation rule by [@Anton5360](https://github.com/Anton5360) in https://github.com/laravel/framework/pull/50226
* [10.x] Fixes on nesting operations performed while applying scopes. by [@Guilhem-DELAITRE](https://github.com/Guilhem-DELAITRE) in https://github.com/laravel/framework/pull/50207
* [10.x] Custom RateLimiter increase by [@khepin](https://github.com/khepin) in https://github.com/laravel/framework/pull/50197
* [10.x] Add Lateral Join to Query Builder by [@Bakke](https://github.com/Bakke) in https://github.com/laravel/framework/pull/50050
* [10.x] Update return type by [@AmirRezaM75](https://github.com/AmirRezaM75) in https://github.com/laravel/framework/pull/50252
* [10.x] Fix dockblock by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50259
* [10.x] Add `Conditionable` in enum rule by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/50257
* [10.x] Update Facade::$app to nullable by [@villfa](https://github.com/villfa) in https://github.com/laravel/framework/pull/50260
* [10.x] Truncate sqlite table name with prefix by [@kitloong](https://github.com/kitloong) in https://github.com/laravel/framework/pull/50251
* Correction comment for Str::orderedUuid() - https://github.com/larave… by [@wq9578](https://github.com/wq9578) in https://github.com/laravel/framework/pull/50268

## [v10.45.1](https://github.com/laravel/framework/compare/v10.45.0...v10.45.1) - 2024-02-21

* Fix typehint for ResetPassword::toMailUsing() by [@KKSzymanowski](https://github.com/KKSzymanowski) in https://github.com/laravel/framework/pull/50163
* [10.x] Fix Process::fake() never matching multi-line commands by [@SjorsO](https://github.com/SjorsO) in https://github.com/laravel/framework/pull/50164

## [v10.45.0](https://github.com/laravel/framework/compare/v10.44.0...v10.45.0) - 2024-02-20

* [10.x] Update `Stringable` phpdoc by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/50075
* [10.x] Allow `Collection::select()` to work on `ArrayAccess` by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/50072
* [10.x] Add `before` to the `PendingBatch` by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/50058
* [10.x] Adjust rules call sequence by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50084
* [10.x] Fixes `Illuminate\Support\Str::fromBase64()` return type by [@SamAsEnd](https://github.com/SamAsEnd) in https://github.com/laravel/framework/pull/50108
* [10.x] Actually fix fromBase64 return type by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/50113
* [10.x] Fix warning and deprecation for Str::api by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/50114
* [10.x] Mark model instanse as not exists on deleting MorphPivot relation. by [@dkulyk](https://github.com/dkulyk) in https://github.com/laravel/framework/pull/50135
* [10.x] Adds Tappable and Conditionable to Relation class by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/50124
* [10.x] Added getQualifiedMorphTypeName to MorphToMany by [@dkulyk](https://github.com/dkulyk) in https://github.com/laravel/framework/pull/50153

## [v10.44.0](https://github.com/laravel/framework/compare/v10.43.0...v10.44.0) - 2024-02-13

* [10.x] Fix empty request for HTTP connection exception by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49924
* [10.x] Add Collection::select() method by [@morrislaptop](https://github.com/morrislaptop) in https://github.com/laravel/framework/pull/49845
* [10.x] Refactor `getPreviousUrlFromSession` method in UrlGenerator by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/49944
* [10.x] Add POSIX compliant cleanup to artisan serve by [@Tofandel](https://github.com/Tofandel) in https://github.com/laravel/framework/pull/49943
* [10.x] Fix infinite loop when global scopes query contains aggregates by [@mateusjunges](https://github.com/mateusjunges) in https://github.com/laravel/framework/pull/49972
* [10.x] Adds PHPUnit 11 as conflict by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/49957
* Revert "[10.x] fix Before/After validation rules" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/50013
* [10.x] Fix the phpdoc for replaceMatches in Str and Stringable helpers by [@joke2k](https://github.com/joke2k) in https://github.com/laravel/framework/pull/49990
* [10.x] Added `setAbly()` method for `AblyBroadcaster` by [@Rijoanul-Shanto](https://github.com/Rijoanul-Shanto) in https://github.com/laravel/framework/pull/49981
* [10.x] Fix in appendExceptionToException method exception type check by [@t1nkl](https://github.com/t1nkl) in https://github.com/laravel/framework/pull/49958
* [10.x] DB command: add sqlcmd -C flag when 'trust_server_certificate' is set by [@hulkur](https://github.com/hulkur) in https://github.com/laravel/framework/pull/49952
* Allows Setup and Teardown actions to be reused in alternative TestCase for Laravel by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49973
* [10.x] Add `toBase64()` and `fromBase64()` methods to Stringable and Str classes by [@mtownsend5512](https://github.com/mtownsend5512) in https://github.com/laravel/framework/pull/49984
* [10.x] Allows to defer resolving pcntl only if it's available by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50024
* [10.x] Fixes missing `Throwable` import and handle if `originalExceptionHandler` or `originalDeprecationHandler` property isn't used by alternative TestCase by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/50021
* [10.x] Type hinting for conditional validation rules by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/50017
* [10.x] Introduce new `Arr::take()` helper by [@ryangjchandler](https://github.com/ryangjchandler) in https://github.com/laravel/framework/pull/50015
* [10.x] Improved Handling of Empty Component Slots with HTML Comments or Line Breaks by [@comes](https://github.com/comes) in https://github.com/laravel/framework/pull/49966
* [10.x] Introduce Observe attribute for models by [@emargareten](https://github.com/emargareten) in https://github.com/laravel/framework/pull/49843
* [10.x] Add ScopedBy attribute for models by [@emargareten](https://github.com/emargareten) in https://github.com/laravel/framework/pull/50034
* [10.x] Update reserved names in `GeneratorCommand` by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/50043
* [10.x] fix Validator::validated get nullable array by [@helitik](https://github.com/helitik) in https://github.com/laravel/framework/pull/50056
* [10.x] Pass Herd specific env variables to "artisan serve" by [@mpociot](https://github.com/mpociot) in https://github.com/laravel/framework/pull/50069
* Remove regex case insensitivity modifier in UUID detection to speed it up slightly by [@maximal](https://github.com/maximal) in https://github.com/laravel/framework/pull/50067
* [10.x] HTTP retry method can accept array as first param by [@me-shaon](https://github.com/me-shaon) in https://github.com/laravel/framework/pull/50064
* [10.x] Fix DB::afterCommit() broken in tests using DatabaseTransactions by [@oprypkhantc](https://github.com/oprypkhantc) in https://github.com/laravel/framework/pull/50068

## [v10.43.0](https://github.com/laravel/framework/compare/v10.42.0...v10.43.0) - 2024-01-30

* [10.x] Add storage:unlink command by [@salkovmx](https://github.com/salkovmx) in https://github.com/laravel/framework/pull/49795
* [10.x] Unify `\Illuminate\Log\LogManager` method definition comments with `\Psr\Logger\Interface` by [@eusonlito](https://github.com/eusonlito) in https://github.com/laravel/framework/pull/49805
* [10.x] class-name string argument for global scopes by [@emargareten](https://github.com/emargareten) in https://github.com/laravel/framework/pull/49802
* [10.x] Add `hasIndex()` and minor Schema enhancements by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49796
* [10.x] Do not touch `BelongsToMany` relation when using `withoutTouching` by [@mateusjunges](https://github.com/mateusjunges) in https://github.com/laravel/framework/pull/49798
* [10.x] Check properties on mailables are initialized before sharing with the view by [@j3j5](https://github.com/j3j5) in https://github.com/laravel/framework/pull/49813
* [10.x] Remove duplicate actions/checkout from queue workflow by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/49828
* [10.x] Add `insertOrIgnoreUsing` for Eloquent by [@trovster](https://github.com/trovster) in https://github.com/laravel/framework/pull/49827
* [10.x] Make `hasIndex()` Order-sensitive  by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49840
* [10.x] Release action by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49838
* [10.x] Add MariaDb1060Platform by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49848
* [10.x] Unified Pivot and Model Doc Block `$guarded` by [@eusonlito](https://github.com/eusonlito) in https://github.com/laravel/framework/pull/49851
* [10.x] Introducing `beforeStartingTransaction` callback and use it in `LazilyRefreshDatabase` by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/framework/pull/49853
* [10.x] fix password max validation message by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/49861
* [10.x] Fix validation message used for max file size by [@mateusjunges](https://github.com/mateusjunges) in https://github.com/laravel/framework/pull/49879
* Update README.md by [@foremtehan](https://github.com/foremtehan) in https://github.com/laravel/framework/pull/49878
* [10.x] Adds `FormRequest[@getRules](https://github.com/getRules)()` method by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/49860
* [10.x] add addGlobalScopes method by [@emargareten](https://github.com/emargareten) in https://github.com/laravel/framework/pull/49880
* [10.x] Allow brick/math 0.12 by [@LogicSatinn](https://github.com/LogicSatinn) in https://github.com/laravel/framework/pull/49883
* [10.x] Add support for streamed JSON Response by [@pelmered](https://github.com/pelmered) in https://github.com/laravel/framework/pull/49873
* [10.x] Using the native fopen exception in LockableFile.php by [@eusonlito](https://github.com/eusonlito) in https://github.com/laravel/framework/pull/49895
* [10.x] Fix LazilyRefreshDatabase when testing artisan commands by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/49914
* [10.x] Fix expressions in with-functions doing aggregates by [@tpetry](https://github.com/tpetry) in https://github.com/laravel/framework/pull/49912
* [10.x] Fix redis tag entries never becoming stale if cache ttl is past time by [@jagers](https://github.com/jagers) in https://github.com/laravel/framework/pull/49864
* [10.x] Fix - The `Translator` may incorrectly report the locale of a missing translation key by [@VicGUTT](https://github.com/VicGUTT) in https://github.com/laravel/framework/pull/49900
* [10.x] fix Before/After validation rules by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/49871

## [v10.42.0](https://github.com/laravel/framework/compare/v10.41.0...v10.42.0) - 2024-01-23

* [10.x] Switch to hash_equals in `File::hasSameHash()` by [@simonhamp](https://github.com/simonhamp) in https://github.com/laravel/framework/pull/49721
* [10.x] fix Rule::unless for callable $condition by [@dbakan](https://github.com/dbakan) in https://github.com/laravel/framework/pull/49726
* [10.x] Adds JobQueueing event by [@dmason30](https://github.com/dmason30) in https://github.com/laravel/framework/pull/49722
* [10.x] Fix decoding issue in MailLogTransport by [@rojtjo](https://github.com/rojtjo) in https://github.com/laravel/framework/pull/49727
* [10.x] Implement "max" validation rule for passwords by [@angelej](https://github.com/angelej) in https://github.com/laravel/framework/pull/49739
* [10.x] Add multiple channels/routes to AnonymousNotifiable at once by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/49745
* [10.x] Sort service providers alphabetically by [@buismaarten](https://github.com/buismaarten) in https://github.com/laravel/framework/pull/49762
* [10.x] Global default options for the http factory by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49767
* [10.x] Only use `Carbon` if accessed from Laravel or also uses `illuminate/support` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49772
* [10.x] Add `Str::unwrap` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/49779
* [10.x] Allow Uuid and Ulid in Carbon::createFromId() by [@kylekatarnls](https://github.com/kylekatarnls) in https://github.com/laravel/framework/pull/49783
* [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49785

## [v10.41.0](https://github.com/laravel/framework/compare/v10.40.0...v10.41.0) - 2024-01-16

* [10.x] Add a `threshold` parameter to the `Number::spell` helper by [@caendesilva](https://github.com/caendesilva) in https://github.com/laravel/framework/pull/49610
* Revert "[10.x] Make ComponentAttributeBag Arrayable" by [@luanfreitasdev](https://github.com/luanfreitasdev) in https://github.com/laravel/framework/pull/49623
* [10.x] Fix return value and docblock by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/49627
* [10.x] Add an option to specify the default path to the models directory for `php artisan model:prune` by [@dbhynds](https://github.com/dbhynds) in https://github.com/laravel/framework/pull/49617
* [10.x] Allow job chains to be conditionally dispatched by [@fjarrett](https://github.com/fjarrett) in https://github.com/laravel/framework/pull/49624
* [10.x] Add test for existing empty test by [@lioneaglesolutions](https://github.com/lioneaglesolutions) in https://github.com/laravel/framework/pull/49632
* [10.x] Add additional context to Mailable assertion messages by [@lioneaglesolutions](https://github.com/lioneaglesolutions) in https://github.com/laravel/framework/pull/49631
* [10.x] Allow job batches to be conditionally dispatched by [@fjarrett](https://github.com/fjarrett) in https://github.com/laravel/framework/pull/49639
* [10.x] Revert parameter name change by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49659
* [10.x] Printing Name of The Method that Calls `ensureIntlExtensionIsInstalled` in `Number` class. by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/49660
* [10.x] Update pagination tailwind.blade.php by [@anasmorahhib](https://github.com/anasmorahhib) in https://github.com/laravel/framework/pull/49665
* [10.x] feat: add base argument to Stringable->toInteger() by [@adamczykpiotr](https://github.com/adamczykpiotr) in https://github.com/laravel/framework/pull/49670
* [10.x]: Remove unused class ShouldBeUnique when make a job by [@Kenini1805](https://github.com/Kenini1805) in https://github.com/laravel/framework/pull/49669
* [10.x] Add tests for Eloquent methods by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/49673
* Implement draft workflow by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49683
* [10.x] Fixing Types, Word and Returns of `Number`class. by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/49681
* [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49679
* [10.x] Officially support floats in trans_choice and Translator::choice by [@philbates35](https://github.com/philbates35) in https://github.com/laravel/framework/pull/49693
* [10.x] Use static function by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/49696
* [10.x] Revert "[10.x] Improve numeric comparison for custom casts" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49702
* [10.x] Add exit code to queue:clear, and queue:forget commands by [@bytestream](https://github.com/bytestream) in https://github.com/laravel/framework/pull/49707
* [10.x] Allow StreamInterface as raw HTTP Client body by [@janolivermr](https://github.com/janolivermr) in https://github.com/laravel/framework/pull/49705

## [v10.40.0](https://github.com/laravel/framework/compare/v10.39.0...v10.40.0) - 2024-01-09

* [10.x] `Model::preventAccessingMissingAttributes()` raises exception for enums & primitive castable attributes that were not retrieved by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/49480
* [10.x] Include system versioned tables for MariaDB by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49509
* [10.x] Fixes the `Arr::dot()` method to properly handle indexes array by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/49507
* [10.x] Expand Gate::allows & Gate::denies signature by [@antonkomarev](https://github.com/antonkomarev) in https://github.com/laravel/framework/pull/49503
* [10.x] Improve numeric comparison for custom casts by [@imahmood](https://github.com/imahmood) in https://github.com/laravel/framework/pull/49504
* [10.x] Add session except method by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/49520
* [10.x] Add `Number::clamp` by [@jbrooksuk](https://github.com/jbrooksuk) in https://github.com/laravel/framework/pull/49512
* [10.x] Fix Schedule test by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/49538
* [10.x] Use correct format of date by [@buismaarten](https://github.com/buismaarten) in https://github.com/laravel/framework/pull/49541
* [10.x] Clean Arr by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/49530
* [10.x] Make ComponentAttributeBag Arrayable by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/49524
* [10.x] Fix whenAggregated when default is not specified by [@lovePizza](https://github.com/lovePizza) in https://github.com/laravel/framework/pull/49521
* [10.x] Update AsArrayObject.php to use ARRAY_AS_PROPS flag by [@pintend](https://github.com/pintend) in https://github.com/laravel/framework/pull/49534
* [10.x] Remove invalid `RedisCluster::client()` call by [@tillkruss](https://github.com/tillkruss) in https://github.com/laravel/framework/pull/49560
* [10.x] Remove unused code from `PhpRedisConnector` by [@tillkruss](https://github.com/tillkruss) in https://github.com/laravel/framework/pull/49559
* [10.x] Flush about command during test runs by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49557
* [10.x] Fix parentOfParameter method by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/49548
* [10.x] Make the Schema Builder macroable by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/49547
* [10.x] Remove unused code from tests by [@imahmood](https://github.com/imahmood) in https://github.com/laravel/framework/pull/49566
* [10.x] Update Query/Builder.php $columns typehint by [@Grldk](https://github.com/Grldk) in https://github.com/laravel/framework/pull/49563
* [10.x] Add assertViewEmpty to TestView by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/49558
* [10.x] Update tailwind.blade.php for dark mode by [@sabinchacko03](https://github.com/sabinchacko03) in https://github.com/laravel/framework/pull/49515
* [10.x] Fix deprecation with null value in cache FileStore by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49578
* [10.x] Allow Vite asset path customization by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49437
* [10.x] Type hinting of the second parameter of date- and time-related `where*()` methods of `Illuminate\Database\Query\Builder` by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/49599
* [10.x] Fix Stringable::convertCase() return type by [@vaites](https://github.com/vaites) in https://github.com/laravel/framework/pull/49590
* Allow \Blade::stringable() to be called on native Iterables by [@tsjason](https://github.com/tsjason) in https://github.com/laravel/framework/pull/49591
* [10.x] Refactor time handling using `InteractsWithTime` trait method by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/49601
* [10.x] Add `assertCount` test helper by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/49609
* [10.x] Ability to establish connection without using Config Repository by [@deleugpn](https://github.com/deleugpn) in https://github.com/laravel/framework/pull/49527
* [10.x] Add APA style title helper by [@hotmeteor](https://github.com/hotmeteor) in https://github.com/laravel/framework/pull/49572
* [10.x] Fix usage of alternatives in error output by [@Mrjavaci](https://github.com/Mrjavaci) in https://github.com/laravel/framework/pull/49614
* [10.x] Use locks for queue job popping for PlanetScale's MySQL-compatible Vitess 19 engine by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49561

## [v10.39.0](https://github.com/laravel/framework/compare/v10.38.2...v10.39.0) - 2023-12-27

* [9.x] Support for phpredis 6.0.0 by [@MichalHubatka](https://github.com/MichalHubatka) in https://github.com/laravel/framework/pull/48380
* [10.x] Dynamic `maxTries` for queued jobs by [@mechelon](https://github.com/mechelon) in https://github.com/laravel/framework/pull/49473
* [10.x] Avoid TypeError when using json validation rule when PHP < 8.3 by [@Xint0](https://github.com/Xint0) in https://github.com/laravel/framework/pull/49474
* [10.x] Fix use statement compilation in Blade templates by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/49479
* [10.x] Allow testing prompts validation by [@cerbero90](https://github.com/cerbero90) in https://github.com/laravel/framework/pull/49447
* [10.x] Add 'Roundrobin' Symfony mailer transport driver by [@me-shaon](https://github.com/me-shaon) in https://github.com/laravel/framework/pull/49435

## [v10.38.2](https://github.com/laravel/framework/compare/v10.38.1...v10.38.2) - 2023-12-22

* [10.x] Add `conflict` for `doctrine/dbal:^4.0` to `illuminate/database` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49456
* [10.x] Simplify Arr::dot by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/49461
* [10.x] Illuminate\Filesystem\join_paths(): Argument #2 must be of type string, null given by [@tylernathanreed](https://github.com/tylernathanreed) in https://github.com/laravel/framework/pull/49467
* [10.x] Allow deprecation logging in tests by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49457
* [10.x] Fix missing Validation rules not working with nested array by [@aabadawy](https://github.com/aabadawy) in https://github.com/laravel/framework/pull/49449

## [v10.38.1](https://github.com/laravel/framework/compare/v10.38.0...v10.38.1) - 2023-12-20

* [10.x] Adds support for parse callbacks from anonymous classes by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/49432
* Revert "[10.x] Drop the primary key if it exists when adding a new primary key" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/49448
* [10.x] Fix installing DBAL on a fresh app by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49438
* [10.x] Add method to create request by [@dododedodonl](https://github.com/dododedodonl) in https://github.com/laravel/framework/pull/49446
* [10.x] Move `Illuminate\Foundation\Application::joinPaths()` to `Illuminate\Filesystem\join_paths()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49433

## [v10.38.0](https://github.com/laravel/framework/compare/v10.37.3...v10.38.0) - 2023-12-19

* [10.x] Add routeRoute method to test request by [@fragkp](https://github.com/fragkp) in https://github.com/laravel/framework/pull/49366
* [10.x] Update import & typo by [@chu121su12](https://github.com/chu121su12) in https://github.com/laravel/framework/pull/49370
* [10.x] Show default `false` values in `db:table` command by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/framework/pull/49379
* [10.x] Fix primary key creation for MySQL with `sql_require_primary_key` enabled by [@mtawil](https://github.com/mtawil) in https://github.com/laravel/framework/pull/49374
* [10.x] Add `charset` and `collation` method to `Blueprint` by [@gcazin](https://github.com/gcazin) in https://github.com/laravel/framework/pull/49396
* Fixes second run of `about` command on Octane by [@josecl](https://github.com/josecl) in https://github.com/laravel/framework/pull/49387
* [10.x] Fix bug in ArrayLock getCurrentOwner by [@Joostb](https://github.com/Joostb) in https://github.com/laravel/framework/pull/49393
* [10.x] Dynamo Batch Repository - Match Default Horizon Sort by [@evan-burrell](https://github.com/evan-burrell) in https://github.com/laravel/framework/pull/49391
* [10.x] Add Blade `[@session](https://github.com/session)` Directive by [@jrd-lewis](https://github.com/jrd-lewis) in https://github.com/laravel/framework/pull/49339
* [10.x] Improve `Arr::dot` performance by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/49386
* [10.x] Fix assertStatus() parameter order by [@marcovo](https://github.com/marcovo) in https://github.com/laravel/framework/pull/49404
* [10.x] Only set `defaultCasters` if not previously set by [@inxilpro](https://github.com/inxilpro) in https://github.com/laravel/framework/pull/49402
* [10.x] Fixes parameter type in `ManagesFrequencies` by [@Lucas-Schmukas](https://github.com/Lucas-Schmukas) in https://github.com/laravel/framework/pull/49399
* [10.x] Add SQLite support for `whereJsonContains` method by [@danieleambrosino](https://github.com/danieleambrosino) in https://github.com/laravel/framework/pull/49401
* [10x.] Use native json_validate in Validation by [@gtjamesa](https://github.com/gtjamesa) in https://github.com/laravel/framework/pull/49413
* [10.x] Introducing `isEmpty` and `isNotEmpty` to `ComponentAttributeBag` by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/49408
* [10.x] Drop the primary key if it exists when adding a new primary key by [@KieranFYI](https://github.com/KieranFYI) in https://github.com/laravel/framework/pull/49392
* [10.x] Improve schema builder `getColumns()` method by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49416
* [10.x] Add `MailMessage` helpers for plain text email notifications by [@onlime](https://github.com/onlime) in https://github.com/laravel/framework/pull/49407
* [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49426
* [10.x] Add Conditionable to Pipeline by [@shane-zeng](https://github.com/shane-zeng) in https://github.com/laravel/framework/pull/49429

## [v10.37.3](https://github.com/laravel/framework/compare/v10.37.2...v10.37.3) - 2023-12-13

* Flush middleware callbacks by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/commit/bb49a72c1a839b2b19d0fcea4e8b203a122454ef

## [v10.37.2](https://github.com/laravel/framework/compare/v10.37.1...v10.37.2) - 2023-12-13

* Ability to test chained job via closure by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/49337
* [10.x] Add `progress` option to `PendingBatch` by [@orkhanahmadov](https://github.com/orkhanahmadov) in https://github.com/laravel/framework/pull/49273
* [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49338
* [10.x] Avoid using `rescue()` in standalone `illuminate/database` component. by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49355
* [10.x] Exclude extension types on PostgreSQL when retrieving types by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49358
* [10.x] Revert "[10.x] Disconnecting the database connection after testing" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/49361

## [v10.37.1](https://github.com/laravel/framework/compare/v10.37.0...v10.37.1) - 2023-12-12

* [10.x] Disconnecting the database connection after testing by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/framework/pull/49327
* [10.x] Get user-defined types on PostgreSQL by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49303

## [v10.37.0](https://github.com/laravel/framework/compare/v10.35.0...v10.37.0) - 2023-12-12

* [10.x] Add `engine` method to `Blueprint` by [@jbrooksuk](https://github.com/jbrooksuk) in https://github.com/laravel/framework/pull/49250
* [10.x] Use translator from validator in `Can` and `Enum` rules by [@fancyweb](https://github.com/fancyweb) in https://github.com/laravel/framework/pull/49251
* [10.x] Get indexes of a table by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49204
* [10.x] Filesystem : can lock file on append of content by [@StephaneBour](https://github.com/StephaneBour) in https://github.com/laravel/framework/pull/49262
* [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49266
* [10.x] Fixes generating facades documentation shouldn't be affected by `php-psr` extension by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49268
* [10.x] Fixes `AboutCommand::format()` docblock by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49274
* [10.x] `Route::getController()` should return `null` when the accessing closure based route by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49269
* [10.x] Add "noActionOnUpdate" method in Illuminate/Database/Schema/ForeignKeyDefinition by [@hrsa](https://github.com/hrsa) in https://github.com/laravel/framework/pull/49297
* [10.x] Fixing number helper for floating 0.0 by [@mr-punyapal](https://github.com/mr-punyapal) in https://github.com/laravel/framework/pull/49277
* [10.x] Allow checking if lock succesfully restored by [@Joostb](https://github.com/Joostb) in https://github.com/laravel/framework/pull/49272
* [10.x] Enable DynamoDB as a backend for Job Batches by [@khepin](https://github.com/khepin) in https://github.com/laravel/framework/pull/49169
* [10.x] Removed deprecated and not used argument by [@Muetze42](https://github.com/Muetze42) in https://github.com/laravel/framework/pull/49304
* [10.x] Add Conditionable to Batched and Chained jobs by [@bretto36](https://github.com/bretto36) in https://github.com/laravel/framework/pull/49310
* [10.x] Include partitioned tables on PostgreSQL when retrieving tables by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49326
* [10.x] Allow to pass `Arrayable` or `Stringble` in rules `In` and `NotIn` by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/49055
* [10.x] Display error message if json_encode() fails by [@aimeos](https://github.com/aimeos) in https://github.com/laravel/framework/pull/48856
* [10.x] Allow error list per field by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49309
* [10.x] Get foreign keys of a table by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49264
* [10.x] PHPStan Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49343
* [10.x] Handle missing translations: more robust handling of callback return value by [@DeanWunder](https://github.com/DeanWunder) in https://github.com/laravel/framework/pull/49341

## [v10.35.0](https://github.com/laravel/framework/compare/v10.34.2...v10.35.0) - 2023-12-05

* [10.x] Add `Conditionable` trait to `AssertableJson` by [@khalilst](https://github.com/khalilst) in https://github.com/laravel/framework/pull/49172
* [10.x] Add `--with-secret` option to Artisan `down` command. by [@jj15asmr](https://github.com/jj15asmr) in https://github.com/laravel/framework/pull/49171
* [10.x] Add support for `Number::summarize` by [@jcsoriano](https://github.com/jcsoriano) in https://github.com/laravel/framework/pull/49197
* [10.x] Add Blade [@use](https://github.com/use) directive by [@simonhamp](https://github.com/simonhamp) in https://github.com/laravel/framework/pull/49179
* [10.x] Fixes retrying failed jobs causes PHP memory exhaustion errors when dealing with thousands of failed jobs by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49186
* [10.x] Add "substituteImplicitBindingsUsing" method to router by [@calebporzio](https://github.com/calebporzio) in https://github.com/laravel/framework/pull/49200
* [10.x] Cookies Having Independent Partitioned State (CHIPS) by [@fabricecw](https://github.com/fabricecw) in https://github.com/laravel/framework/pull/48745
* [10.x] Update InteractsWithDictionary.php to use base InvalidArgumentException by [@Grldk](https://github.com/Grldk) in https://github.com/laravel/framework/pull/49209
* [10.x] Fix docblock for wasRecentlyCreated by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/49208
* [10.x] Fix loss of attributes after calling child component by [@rojtjo](https://github.com/rojtjo) in https://github.com/laravel/framework/pull/49216
* [10.x] Fix typo in PHPDoc comment by [@caendesilva](https://github.com/caendesilva) in https://github.com/laravel/framework/pull/49234
* [10.x] Determine if the given view exists. by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49231

## [v10.34.2](https://github.com/laravel/framework/compare/v10.34.1...v10.34.2) - 2023-11-28

* [v10.x] Add missing methods to newly extended fake `Vite` instance by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/49165

## [v10.34.1](https://github.com/laravel/framework/compare/v10.34.0...v10.34.1) - 2023-11-28

* [10.x] Streamline `DatabaseMigrations` and `RefreshDatabase` events by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49153
* [10.x] Use HtmlString in Vite fake by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/framework/pull/49163

## [v10.34.0](https://github.com/laravel/framework/compare/v10.33.0...v10.34.0) - 2023-11-28

* [10.x] Fix `hex_color` validation rule by [@apih](https://github.com/apih) in https://github.com/laravel/framework/pull/49070
* [10.x] Prevent passing null to base64_decode in Encrypter by [@robtesch](https://github.com/robtesch) in https://github.com/laravel/framework/pull/49071
* [10.x] Alias Number class by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/49073
* [10.x] Added File Validation `extensions` by [@eusonlito](https://github.com/eusonlito) in https://github.com/laravel/framework/pull/49082
* [10.x] Add [@throws](https://github.com/throws) in doc-blocks by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/49091
* [10.x] Update docblocks for consistency by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/49092
* [10.x] Throw exception when trying to initiate `Collection` using `WeakMap` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49095
* [10.x] Only stage committed transactions by [@hansnn](https://github.com/hansnn) in https://github.com/laravel/framework/pull/49093
* Better transaction manager object design by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/49103
* [10.x] use php 8.3 `mb_str_pad()` for `Str::pad*` by [@amacado](https://github.com/amacado) in https://github.com/laravel/framework/pull/49108
* [10.x] Add Conditionable to TestResponse by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/49112
* [10.x] Allow multiple types in Collection's `ensure` method by [@ash-jc-allen](https://github.com/ash-jc-allen) in https://github.com/laravel/framework/pull/49127
* [10.x] Fix middleware "SetCacheHeaders" with download responses by [@clementbirkle](https://github.com/clementbirkle) in https://github.com/laravel/framework/pull/49138
* [10.x][Cache] Fix handling of `false` values in apc by [@simivar](https://github.com/simivar) in https://github.com/laravel/framework/pull/49145
* [10.x] Reset numeric rules after each attribute's validation by [@apih](https://github.com/apih) in https://github.com/laravel/framework/pull/49142
* [10.x] Extract dirty getter for `performUpdate` by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/49141
* [10.x] `ensure`: Resolve `$itemType` outside the closure by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/49137
* Allow "missing" method to be used on route groups by [@redelschaap](https://github.com/redelschaap) in https://github.com/laravel/framework/pull/49144
* [10.x] Get tables and views info by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49020
* [10.x] Fix `MorphTo::associate()` PHPDoc parameter by [@devfrey](https://github.com/devfrey) in https://github.com/laravel/framework/pull/49162
* [10.x] Make test error messages more multi-byte readable by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/49160
* [10.x] Generate a unique hash for anonymous components by [@billyonecan](https://github.com/billyonecan) in https://github.com/laravel/framework/pull/49156
* [10.x] Improves output when using `php artisan about --json` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/49154
* [10.x] Make fake instance inherit from `Vite` when using `withoutVite()` by [@orkhanahmadov](https://github.com/orkhanahmadov) in https://github.com/laravel/framework/pull/49150

## [v10.33.0](https://github.com/laravel/framework/compare/v10.32.1...v10.33.0) - 2023-11-21

- [10.x] Fix wrong parameter passing and add these rules to dependent rules by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/49008
- [10.x] Make Validator::getValue() public by [@shinsenter](https://github.com/shinsenter) in https://github.com/laravel/framework/pull/49007
- [10.x] Custom messages for `Password` validation rule by [@rcknr](https://github.com/rcknr) in https://github.com/laravel/framework/pull/48928
- [10.x] Round milliseconds in database seeder console output runtime by [@SjorsO](https://github.com/SjorsO) in https://github.com/laravel/framework/pull/49014
- [10.x] Add a `Number` utility class by [@caendesilva](https://github.com/caendesilva) in https://github.com/laravel/framework/pull/48845
- [10.x] Fix the replace() method in DefaultService class by [@jonagoldman](https://github.com/jonagoldman) in https://github.com/laravel/framework/pull/49022
- [10.x] Pass the property $validator as a parameter to the $callback Closure by [@shinsenter](https://github.com/shinsenter) in https://github.com/laravel/framework/pull/49015
- [10.x] Fix Cache DatabaseStore::add() error occur on Postgres within transaction by [@xdevor](https://github.com/xdevor) in https://github.com/laravel/framework/pull/49025
- [10.x] Support asserting against chained batches by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/49003
- [10.x] Prevent DB `Cache::get()` occur race condition by [@xdevor](https://github.com/xdevor) in https://github.com/laravel/framework/pull/49031
- [10.x] Fix notifications being counted as sent without a "shouldSend" method by [@joelwmale](https://github.com/joelwmale) in https://github.com/laravel/framework/pull/49030
- [10.x] Fix tests failure on Windows by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/49037
- [10.x] Add unless conditional on validation rules by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/49048
- [10.x] Handle string based payloads that are not JSON or form data when creating PSR request instances by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/49047
- [10.x] Fix directory separator CMD display on windows by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/49045
- [10.x] Fix mapSpread doc by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48941
- [10.x] Tiny `Support\Collection` test fix - Unused data provider parameter by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/49053
- [10.x] Feat: Add color_hex validation rule by [@nikopeikrishvili](https://github.com/nikopeikrishvili) in https://github.com/laravel/framework/pull/49056
- [10.x] Handle missing translation strings using callback by [@DeanWunder](https://github.com/DeanWunder) in https://github.com/laravel/framework/pull/49040
- [10.x] Add Str::transliterate to Stringable by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/49065
- Add Alpha Channel support to Hex validation rule by [@ahinkle](https://github.com/ahinkle) in https://github.com/laravel/framework/pull/49069

## [v10.32.1](https://github.com/laravel/framework/compare/v10.32.0...v10.32.1) - 2023-11-14

- [10.x] Add `[@pushElseIf](https://github.com/pushElseIf)` and `[@pushElse](https://github.com/pushElse)` by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/48990

## [v10.32.0](https://github.com/laravel/framework/compare/v10.31.0...v10.32.0) - 2023-11-14

- Update PendingRequest.php by [@mattkingshott](https://github.com/mattkingshott) in https://github.com/laravel/framework/pull/48939
- [10.x] Change array_key_exists with null coalescing assignment operator in FilesystemAdapter by [@miladev95](https://github.com/miladev95) in https://github.com/laravel/framework/pull/48943
- [10.x] Use container to resolve email validator class by [@orkhanahmadov](https://github.com/orkhanahmadov) in https://github.com/laravel/framework/pull/48942
- [10.x] Added `getGlobalMiddleware` method to HTTP Client Factory by [@pascalbaljet](https://github.com/pascalbaljet) in https://github.com/laravel/framework/pull/48950
- [10.x] Detect MySQL read-only mode error as a lost connection by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/48937
- [10.x] Adds more implicit validation rules for `present` based on other fields by [@diamondobama](https://github.com/diamondobama) in https://github.com/laravel/framework/pull/48908
- [10.x] Refactor set_error_handler callback to use arrow function in `InteractsWithDeprecationHandling` by [@miladev95](https://github.com/miladev95) in https://github.com/laravel/framework/pull/48954
- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48962
- Fix issue that prevents BladeCompiler to raise an exception when temporal compiled blade template is not found. by [@juanparati](https://github.com/juanparati) in https://github.com/laravel/framework/pull/48957
- [10.x] Fix how nested transaction callbacks are handled by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/48859
- [10.x] Fixes Batch Callbacks not triggering if job timeout while in transaction by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48961
- [10.x] expressions in migration computations fail by [@tpetry](https://github.com/tpetry) in https://github.com/laravel/framework/pull/48976
- [10.x] Fixes Exception: Cannot traverse an already closed generator when running Arr::first with an empty generator and no callback by [@moshe-autoleadstar](https://github.com/moshe-autoleadstar) in https://github.com/laravel/framework/pull/48979
- fixes issue with stderr when there was "]" character. by [@nikopeikrishvili](https://github.com/nikopeikrishvili) in https://github.com/laravel/framework/pull/48975
- [10.x] Fix Postgres cache store failed to put exist cache in transaction by [@xdevor](https://github.com/xdevor) in https://github.com/laravel/framework/pull/48968

## [v10.31.0](https://github.com/laravel/framework/compare/v10.30.1...v10.31.0) - 2023-11-07

- [10.x] Allow `Sleep::until()` to be passed a timestamp as a string by [@jameshulse](https://github.com/jameshulse) in https://github.com/laravel/framework/pull/48883
- [10.x] Fix whereHasMorph() with nullable morphs by [@MarkKremer](https://github.com/MarkKremer) in https://github.com/laravel/framework/pull/48903
- [10.x] Handle `class_parents` returning false in `class_uses_recursive` by [@RoflCopter24](https://github.com/RoflCopter24) in https://github.com/laravel/framework/pull/48902
- [10.x] Enable default retrieval of all fragments in `fragments()` and `fragmentsIf()` methods by [@tabuna](https://github.com/tabuna) in https://github.com/laravel/framework/pull/48894
- [10.x] Allow placing a batch on a chain by [@khepin](https://github.com/khepin) in https://github.com/laravel/framework/pull/48633
- [10.x] Dispatch 'connection failed' event in async http client request by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/48900
- authenticate method refactored to use null coalescing operator by [@miladev95](https://github.com/miladev95) in https://github.com/laravel/framework/pull/48917
- [10.x] Add support for Sec-Purpose header by [@nanos](https://github.com/nanos) in https://github.com/laravel/framework/pull/48925
- [10.x] Allow setting retain_visibility config option on Flysystem filesystems by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/48935
- [10.x] Escape forward slashes when exploding wildcard rules by [@matt-farrugia](https://github.com/matt-farrugia) in https://github.com/laravel/framework/pull/48936

## [v10.30.1](https://github.com/laravel/framework/compare/v10.30.0...v10.30.1) - 2023-11-01

- [10.x] Fix postgreSQL reserved word column names w/ guarded attributes broken in native column attributes implementation by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/48877

## [v10.30.0](https://github.com/laravel/framework/compare/v10.29.0...v10.30.0) - 2023-10-31

- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48815
- [10.x] Verify hash config by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48814
- [10.x] Fix the issue of using the now function within the ArrayCache in Lumen by [@cxlblm](https://github.com/cxlblm) in https://github.com/laravel/framework/pull/48826
- [10.x] Match service provider after resolved by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48824
- [10.x] Fix type error registering PSR Request by [@kpicaza](https://github.com/kpicaza) in https://github.com/laravel/framework/pull/48823
- [10.x] Ability to configure default session block timeouts by [@bytestream](https://github.com/bytestream) in https://github.com/laravel/framework/pull/48795
- [10.x] Improvements for `artisan migrate --pretend` command 🚀 by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/48768
- [10.x] Add support for getting native columns' attributes by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/48357
- fix(Eloquent/Builder): calling the methods on passthru base object should be case-insensitive by [@luka-papez](https://github.com/luka-papez) in https://github.com/laravel/framework/pull/48852
- [10.x] Fix `QueriesRelationships[@getRelationHashedColumn](https://github.com/getRelationHashedColumn)()` typehint by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/48847
- [10.x] Remember the job on the exception by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48830
- fix bug for always throwing exception when we pass a callable to throwUnlessStatus method [test included] by [@mhfereydouni](https://github.com/mhfereydouni) in https://github.com/laravel/framework/pull/48844
- [10.x] Dispatch events based on a DB transaction result by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/48705
- [10.x] Reset ShouldDispatchAfterCommitEventTest objects properties by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/48858
- [10.x] Throw exception when trying to escape array for database connection by [@sidneyprins](https://github.com/sidneyprins) in https://github.com/laravel/framework/pull/48836
- [10.x] Fix Stringable objects not converted to string in HTTP facade Query parameters and Body by [@LasseRafn](https://github.com/LasseRafn) in https://github.com/laravel/framework/pull/48849

## [v10.29.0](https://github.com/laravel/framework/compare/v10.28.0...v10.29.0) - 2023-10-24

- [10.x] Fixes `Str::password()` does not always generate password with numbers by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48681
- [10.x] Fixes cache:prune-stale-tags preg_match delimiter no escaped by [@ame1973](https://github.com/ame1973) in https://github.com/laravel/framework/pull/48702
- [10.x] Allow route:list to expand middleware groups in 'VeryVerbose' mode by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/48703
- [10.x] Fix model:prune command error with non-class php files by [@zlodes](https://github.com/zlodes) in https://github.com/laravel/framework/pull/48708
- [10.x] Show CliDumper source content on last line by [@CalebDW](https://github.com/CalebDW) in https://github.com/laravel/framework/pull/48707
- [10.x] Revival of the reverted changes in 10.25.0: `firstOrCreate` `updateOrCreate` improvement through `createOrFirst` + additional query tests  by [@mpyw](https://github.com/mpyw) in https://github.com/laravel/framework/pull/48637
- [10.x] allow resolving view from closure by [@PH7-Jack](https://github.com/PH7-Jack) in https://github.com/laravel/framework/pull/48719
- [10.x] Allow creation of PSR request with merged data by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48696
- [10.x] Update DocBlock for `convertCase` Method to Reflect Optional $encoding Parameter by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48729
- [10.x] Use ValidationException class from Validator Property by [@a-h-abid](https://github.com/a-h-abid) in https://github.com/laravel/framework/pull/48736
- [10.x] Implement Test Coverage for `Str::convertCase` Method by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48730
- [10.x] Extend Test Coverage for `Str::take` Function by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48728
- [10.x] Add `replaceMatches` to Str class by [@hosmelq](https://github.com/hosmelq) in https://github.com/laravel/framework/pull/48727
- [10.x] Fix duplicate conditions on retrying `SELECT` calls under `createOrFirst()` by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/framework/pull/48725
- [10.x] Uses `stefanzweifel/git-auto-commit-action[@v5](https://github.com/v5)` by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/48763
- [10.x] fix typo in comment by [@vintagesucks](https://github.com/vintagesucks) in https://github.com/laravel/framework/pull/48770
- [10.x] Require DBAL 3 when installing by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/48769
- [10.x] Escape the delimiter when extracting an excerpt from text by [@standaniels](https://github.com/standaniels) in https://github.com/laravel/framework/pull/48765
- [10.x] Fix `replaceMatches` in Str class by [@hosmelq](https://github.com/hosmelq) in https://github.com/laravel/framework/pull/48760
- [10.x] Moves logger instance creation to a protected method by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/48759
- [10.x] Add runningConsoleCommand(...$commands) method by [@trevorgehman](https://github.com/trevorgehman) in https://github.com/laravel/framework/pull/48751
- [10.x] Update annotations in wrap method to accommodate Collection instances by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48746
- [10.x] Add Tests for Str::replaceMatches Method by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48771
- [10.x] Do not bubble exceptions thrown rendering error view when debug is false (prevent infinite loops) by [@simensen](https://github.com/simensen) in https://github.com/laravel/framework/pull/48732
- [10.x] Correct phpdoc for Grammar::setConnection by [@Neol3108](https://github.com/Neol3108) in https://github.com/laravel/framework/pull/48779
- [10.x] Add `displayName` for queued Artisan commands by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/48778
- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48797
- [10.x] Make inherited relations and virtual attributes appear in model:show command by [@sebj54](https://github.com/sebj54) in https://github.com/laravel/framework/pull/48800

## [v10.28.0](https://github.com/laravel/framework/compare/v10.27.0...v10.28.0) - 2023-10-10

- [10.x] Fixed issue: Added a call to the `getValue` method by [@lozobojan](https://github.com/lozobojan) in https://github.com/laravel/framework/pull/48652
- [10.x] Add an example for queue retry range option by [@pionl](https://github.com/pionl) in https://github.com/laravel/framework/pull/48691
- [10.x] Add percentage to be used as High Order Messages by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/48689
- [10.x] Optimize `exists` validation for empty array input by [@mtawil](https://github.com/mtawil) in https://github.com/laravel/framework/pull/48684

## [v10.27.0](https://github.com/laravel/framework/compare/v10.26.2...v10.27.0) - 2023-10-09

- [10.x] Store blocks after prepare strings by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/48641
- [10.x] throw TransportException instead of Exception in SES mail drivers by [@bchalier](https://github.com/bchalier) in https://github.com/laravel/framework/pull/48645
- [10.x] Fix `Model::replicate()` when using unique keys by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/48636
- [10.x] Don't crash if replacement cannot be represented as a string by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/48530
- [10.x] Extended `pluck()` testcases by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/48657
- [10.x] Fixes `GeneratorCommand` not able to prevent uppercase reserved name such as  `__CLASS__` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48667
- [10.x] Fix timing sensitive flaky test by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/framework/pull/48664
- [10.x] Fixed implementation related to `afterCommit` on Postgres and MSSQL database drivers by [@SakiTakamachi](https://github.com/SakiTakamachi) in https://github.com/laravel/framework/pull/48662
- [10.x] Implement chunkById in descending order by [@cristiancalara](https://github.com/cristiancalara) in https://github.com/laravel/framework/pull/48666

## [v10.26.2](https://github.com/laravel/framework/compare/v10.26.1...v10.26.2) - 2023-10-03

- Revert "Hint query builder closures (#48562)" by @taylorotwell in https://github.com/laravel/framework/pull/48620

## [v10.26.1](https://github.com/laravel/framework/compare/v10.26.0...v10.26.1) - 2023-10-03

- [10.x] Fix selection of vendor files after searching by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/48619

## [v10.26.0](https://github.com/laravel/framework/compare/v10.25.2...v10.26.0) - 2023-10-03

- [10.x] Convert Expression to string for from in having subqueries by @ikari7789 in https://github.com/laravel/framework/pull/48525
- [10.x] Allow searching on `vendor:publish` prompt by @jessarcher in https://github.com/laravel/framework/pull/48586
- [10.x] Enhance Test Coverage for Macroable Trait by @salehhashemi1992 in https://github.com/laravel/framework/pull/48583
- [10.x] Add new SQL error messages by @magnusvin in https://github.com/laravel/framework/pull/48601
- [10.x] Ensure array cache considers milliseconds by @timacdonald in https://github.com/laravel/framework/pull/48573
- [10.x] Prevent `session:table` command from creating duplicates by @jessarcher in https://github.com/laravel/framework/pull/48602
- [10.x] Handle expiration in seconds by @timacdonald in https://github.com/laravel/framework/pull/48600
- [10.x] Avoid duplicate code for create table commands by extending new `Illuminate\Console\MigrationGeneratorCommand` by @crynobone in https://github.com/laravel/framework/pull/48603
- [10.x] Add Closure Type Hinting for Query Builders by @AJenbo in https://github.com/laravel/framework/pull/48562

## [v10.25.2](https://github.com/laravel/framework/compare/v10.25.1...v10.25.2) - 2023-09-28

- [10.x] Account for new MariaDB platform by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48563
- [10.x] Add Windows fallback for `multisearch` prompt by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/48565
- Revert "[10.x] Fix blade failing to compile when mixing inline/block [@php](https://github.com/php) directives" by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48575
- [10.x] Added Validation Macro Functionality Tests by [@salehhashemi1992](https://github.com/salehhashemi1992) in https://github.com/laravel/framework/pull/48570
- Revert expiry time changes by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48576

## [v10.25.1](https://github.com/laravel/framework/compare/v10.25.0...v10.25.1) - 2023-09-27

- [10.x] Correct parameter type on MakesHttpRequests:followRedirects() by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/48557
- [10.x] Fix `firstOrNew` on `HasManyThrough` relations by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48542
- [10.x] Fix "after commit" callbacks not running on nested transactions using `RefreshDatabase` or `DatabaseMigrations` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48523
- [10.x] Use the dedicated key getters in BelongsTo by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/48509
- [10.x] Fix undefined constant `STDIN` error with `Artisan::call` during a request by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/48559

## [v10.25.0](https://github.com/laravel/framework/compare/v10.24.0...v10.25.0) - 2023-09-26

- [10.x] Fix key type in [@return](https://github.com/return) tag of EnumeratesValues::ensure() docblock by [@wimski](https://github.com/wimski) in https://github.com/laravel/framework/pull/48456
- [10.x] Add str()->take($limit) and Str::take($string, $limit) by [@moshe-autoleadstar](https://github.com/moshe-autoleadstar) in https://github.com/laravel/framework/pull/48467
- [10.x] Throttle exceptions by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48391
- [10.x] Fix blade failing to compile when mixing inline/block [@php](https://github.com/php) directives by [@CalebDW](https://github.com/CalebDW) in https://github.com/laravel/framework/pull/48420
- [10.x] Fix test name for stringable position by [@shawnlindstrom](https://github.com/shawnlindstrom) in https://github.com/laravel/framework/pull/48480
- [10.x] Create fluent method convertCase by [@rmunate](https://github.com/rmunate) in https://github.com/laravel/framework/pull/48492
- [10.x] Fix `CanBeOneOfMany` giving erroneous results by [@Guilhem-DELAITRE](https://github.com/Guilhem-DELAITRE) in https://github.com/laravel/framework/pull/47427
- [10.x] Disable autoincrement for unsupported column type by [@ikari7789](https://github.com/ikari7789) in https://github.com/laravel/framework/pull/48501
- [10.x] Increase bcrypt rounds to 12 by [@valorin](https://github.com/valorin) in https://github.com/laravel/framework/pull/48494
- [10.x] Ensure array driver expires values at the expiry time by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48497
- [10.x] Fix typos by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/48513
- [10.x] Improve tests for `Arr::first` and `Arr::last` by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/48511
- [10.x] Set morph type for MorphToMany pivot model by [@gazben](https://github.com/gazben) in https://github.com/laravel/framework/pull/48432
- [10.x] Revert from using `createOrFirst` in other `*OrCreate` methods by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48531
- [10.x] Fix typos in tests by [@szepeviktor](https://github.com/szepeviktor) in https://github.com/laravel/framework/pull/48534
- [10.x] Adds `updateOrCreate` on HasManyThrough relations regression test by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48533
- [10.x] Convert exception rate limit to seconds by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48543
- [10.x] Adds the `firstOrCreate` and `createOrFirst` methods to the `HasManyThrough` relation by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48541
- [10.x] Handle custom extensions when caching views by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48524
- [10.x] Set prompt interactivity mode by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/48468

## [v10.24.0](https://github.com/laravel/framework/compare/v10.23.1...v10.24.0) - 2023-09-19

- Make types of  parameter of join method consistent in the Query Builder by [@melicerte](https://github.com/melicerte) in https://github.com/laravel/framework/pull/48386
- [10.x] Fix file race condition after view:cache and artisan up by [@roxik](https://github.com/roxik) in https://github.com/laravel/framework/pull/48368
- [10.x] Re-enable SQL Server CI by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/48393
- Update request.stub by [@olivsinz](https://github.com/olivsinz) in https://github.com/laravel/framework/pull/48402
- [10.x] phpdoc: Auth\Access\Response constructor allows null message by [@snmatsui](https://github.com/snmatsui) in https://github.com/laravel/framework/pull/48394
- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48390
- Turn off autocomplete for csrf_field by [@maxheckel](https://github.com/maxheckel) in https://github.com/laravel/framework/pull/48371
- [10.x] Remove PHP 8.1 Check for including Enums in Tests by [@Jubeki](https://github.com/Jubeki) in https://github.com/laravel/framework/pull/48415
- [10.x] Improve naming by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48413
- [10.x] Fix "Text file busy" error when call deleteDirectory by [@ycs77](https://github.com/ycs77) in https://github.com/laravel/framework/pull/48422
- Fix Cache::many() with small numeric keys by [@AlexKarpan](https://github.com/AlexKarpan) in https://github.com/laravel/framework/pull/48423
- [10.x] Update actions/checkout from v3 to v4 by [@tamiroh](https://github.com/tamiroh) in https://github.com/laravel/framework/pull/48439
- `lazyById` doesn't check availability of id (alias) column in database response and silently ends up with endless loop. `chunkById` does. by [@decadence](https://github.com/decadence) in https://github.com/laravel/framework/pull/48436
- [10.x] Allow older jobs to be faked by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48434
- [10.x] introduce `Str::substrPos` by [@amacado](https://github.com/amacado) in https://github.com/laravel/framework/pull/48421
- [10.x] Guess table name correctly in migrations if column's name have ('to', 'from' and/or 'in') terms by [@i350](https://github.com/i350) in https://github.com/laravel/framework/pull/48437
- [10.x] Refactored LazyCollection::take() to save memory by [@fuwasegu](https://github.com/fuwasegu) in https://github.com/laravel/framework/pull/48382
- [10.x] Get value attribute when default value is an enum by [@squiaios](https://github.com/squiaios) in https://github.com/laravel/framework/pull/48452
- [10.x] Composer helper improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48448
- [10.x] Test Symfony v6.4 by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48400

## [v10.23.1](https://github.com/laravel/framework/compare/v10.23.0...v10.23.1) - 2023-09-13

- Use PHP native json_validate in isJson function if available by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/48367
- [10.x] Remove and update a few tearDown methods. by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/48381
- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48378
- add "resolve" to `Component::ignoredMethods()` method by [@PH7-Jack](https://github.com/PH7-Jack) in https://github.com/laravel/framework/pull/48373
- [10.x] Add `notModified` method to HTTP client by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/48379
- [10.x] Update the visibility of setUp and tearDown by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/48383
- Revert "[10.x] Validate version and variant in `Str::isUuid()`" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/48385

## [v10.23.0](https://github.com/laravel/framework/compare/v10.22.0...v10.23.0) - 2023-09-12

- [10.x] Do not add token to AWS credentials without validating it first by [@mmehmet](https://github.com/mmehmet) in https://github.com/laravel/framework/pull/48297
- [10.x] Add array to docs of `ResponseFactory::redirectToAction` by [@NiclasvanEyk](https://github.com/NiclasvanEyk) in https://github.com/laravel/framework/pull/48309
- [10.x] Deduplicate exceptions by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48288
- [10.x] Change Arr::sortRecursiveDesc() method to static. by [@gkisiel](https://github.com/gkisiel) in https://github.com/laravel/framework/pull/48327
- [10.x] Validate version and variant in `Str::isUuid()` by [@inxilpro](https://github.com/inxilpro) in https://github.com/laravel/framework/pull/48321
- [10.x] Adds `make:view` Artisan command by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/48330
- [10.x] Make ComponentAttributeBag JsonSerializable by [@iamgergo](https://github.com/iamgergo) in https://github.com/laravel/framework/pull/48338
- [10.x] add missing method to message bag class by [@PH7-Jack](https://github.com/PH7-Jack) in https://github.com/laravel/framework/pull/48348
- [10.x] Add newResponse method to PendingRequest by [@denniseilander](https://github.com/denniseilander) in https://github.com/laravel/framework/pull/48344
- [10.x] Add before/after database truncation methods to DatabaseTruncation trait by [@cwilby](https://github.com/cwilby) in https://github.com/laravel/framework/pull/48345
- [10.x] Passthru test options by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/48335
- [10.x] Support for phpredis 6.0.0 by [@stemis](https://github.com/stemis) in https://github.com/laravel/framework/pull/48362
- [10.x] Improve test cases and achieve 100% code coverage by [@sohelrana820](https://github.com/sohelrana820) in https://github.com/laravel/framework/pull/48360
- [10.x] Support for phpredis 6.0.0 by [@stemis](https://github.com/stemis) in https://github.com/laravel/framework/pull/48364
- [10.x] Render mailable inline images by [@pniaps](https://github.com/pniaps) in https://github.com/laravel/framework/pull/48292

## [v10.22.0](https://github.com/laravel/framework/compare/v10.21.1...v10.22.0) - 2023-09-05

- [10.x] Add ulid testing helpers by [@Jasonej](https://github.com/Jasonej) in https://github.com/laravel/framework/pull/48276
- [10.x] Fix issue with table prefix duplication in DatabaseTruncation trait by [@mobidev86](https://github.com/mobidev86) in https://github.com/laravel/framework/pull/48291
- [10.x] Fixed a typo in phpdoc block by [@back2Lobby](https://github.com/back2Lobby) in https://github.com/laravel/framework/pull/48296

## [v10.21.1](https://github.com/laravel/framework/compare/v10.21.0...v10.21.1) - 2023-09-04

- [10.x] HotFix: throw captured `UniqueConstraintViolationException` if there are no matching records on `SELECT` retry by [@mpyw](https://github.com/mpyw) in https://github.com/laravel/framework/pull/48234
- [10.x] Adds testing helpers for Precognition by [@peterfox](https://github.com/peterfox) in https://github.com/laravel/framework/pull/48151
- [10.x] GeneratorCommand - Sorting possible models and events by [@TWithers](https://github.com/TWithers) in https://github.com/laravel/framework/pull/48249
- [10.x] Add Enum Support to the In and NotIn Validation Rules by [@geisi](https://github.com/geisi) in https://github.com/laravel/framework/pull/48247
- PHP 8.3 Support by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48265
- [10.x] Call `renderForAssertions` in all Mailable assertions by [@jamsch](https://github.com/jamsch) in https://github.com/laravel/framework/pull/48254
- [10.x] Introduce `requireEnv` helper by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/48261
- [10.x] Combine prefix with table for `compileDropPrimary` PostgreSQL by [@dyriavin](https://github.com/dyriavin) in https://github.com/laravel/framework/pull/48268
- [10.x] BelongsToMany Docblock Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48282

## [v10.21.0](https://github.com/laravel/framework/compare/v10.20.0...v10.21.0) - 2023-08-29

- [10.x] Add broadcastAs function at BroadcastNotificationCreated by [@raphaelcangucu](https://github.com/raphaelcangucu) in https://github.com/laravel/framework/pull/48136
- [10.x] Fix `createOrFirst` on transactions by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48144
- [10.x] Improve `PendingRequest::pool()` return type by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/48150
- [10.x] Adds start and end string replacement helpers by [@joedixon](https://github.com/joedixon) in https://github.com/laravel/framework/pull/48025
- [10.x] Fix flaky test using microtime by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48156
- [10.x] Allow failed job providers to be countable by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48177
- [10.x] Change the return type of getPublicToken function by [@fahamjv](https://github.com/fahamjv) in https://github.com/laravel/framework/pull/48173
- [10.x] Fix flakey `HttpClientTest` test by [@joshbonnick](https://github.com/joshbonnick) in https://github.com/laravel/framework/pull/48166
- [10.x] Give access to job UUID in the job queued event by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48179
- [10.x] Add `serializeAndRestore()` to `QueueFake` and`BusFake` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/48131
- Add visibility Support for Scoped Disk Configurations by [@okaufmann](https://github.com/okaufmann) in https://github.com/laravel/framework/pull/48186
- [10.x] Ensuring Primary Reference on Retry in `createOrFirst()` by [@mpyw](https://github.com/mpyw) in https://github.com/laravel/framework/pull/48161
- [10.x] Make the `firstOrCreate` methods in relations use `createOrFirst` behind the scenes by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/48192
- [10.x] Enhancing `updateOrCreate()` to Use `firstOrCreate()` by [@mpyw](https://github.com/mpyw) in https://github.com/laravel/framework/pull/48160
- [10.x] Introduce short-hand "false" syntax for Blade component props by [@ryangjchandler](https://github.com/ryangjchandler) in https://github.com/laravel/framework/pull/48084
- [10.x] Fix validation of attributes that depend on previous excluded attribute by [@hans-thomas](https://github.com/hans-thomas) in https://github.com/laravel/framework/pull/48122
- [10.x] Remove unused `catch` exception variables by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/48209
- Revert "feature: introduce short hand false syntax for component prop… by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48220
- [10.x] Return from maintenance middleware early if URL is excluded by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/48218
- [10.x] Array to string conversion error exception by [@hans-thomas](https://github.com/hans-thomas) in https://github.com/laravel/framework/pull/48219
- [10.x] Migrate to `laravel/facade-documenter` repository by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48223
- Remove unneeded Return type in Docblock of Illuminate\Database\Eloquent\Builder.php by [@FrazerFlanagan](https://github.com/FrazerFlanagan) in https://github.com/laravel/framework/pull/48228
- [10.x] Fix issues with updated_at by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/48230
- [10.x] Use Symfony Response in exception handler by [@thomasschiet](https://github.com/thomasschiet) in https://github.com/laravel/framework/pull/48226
- [10.x] Allow failed jobs to be counted by "connection" and "queue" by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48216
- [10.x] Add method `Str::convertCase` by [@rmunate](https://github.com/rmunate) in https://github.com/laravel/framework/pull/48224
- [10.x] Make the `updateOrCreate` methods in relations use `firstOrCreate` behind the scenes by [@mpyw](https://github.com/mpyw) in https://github.com/laravel/framework/pull/48213

## [v10.20.0](https://github.com/laravel/framework/compare/v10.19.0...v10.20.0) - 2023-08-22

- [10.x] Allow default values when merging values into a resource by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/48073
- [10.x] Adds a `createOrFirst` method to Eloquent by [@tonysm](https://github.com/tonysm) in https://github.com/laravel/framework/pull/47973
- [10.x] Allow utilising `withTrashed()`, `withoutTrashed()` and `onlyTrashed()` on `MorphTo` relationship even without `SoftDeletes` Model by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47880
- [10.x] Mark Request JSON data to be InputBag in docblocks by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/48085
- [10.x] Markdown Mailables: Allow omitting Footer and Header when customising components by [@jorisnoo](https://github.com/jorisnoo) in https://github.com/laravel/framework/pull/48080
- [10.x] Update EmailVerificationRequest return docblock by [@ahmedash95](https://github.com/ahmedash95) in https://github.com/laravel/framework/pull/48087
- [10.x] Add commonly reusable Composer related commands from 1st party packages by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48096
- [10.x] Add ability to measure a single callable and get result by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48077
- [10.x] Fixes incorrect method visibility and add unit tests for `Illuminate\Support\Composer` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/48104
- [10.x] Skip convert empty string to null test by [@hungthai1401](https://github.com/hungthai1401) in https://github.com/laravel/framework/pull/48105
- [10.x] Using complete insert for mysqldump when appending migration dump to schema file by [@emulgeator](https://github.com/emulgeator) in https://github.com/laravel/framework/pull/48126
- [10.x] Add `hasPackage` method to Composer class by [@emargareten](https://github.com/emargareten) in https://github.com/laravel/framework/pull/48124
- [10.x] Add `assertJsonPathCanonicalizing` method by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/48117
- [10.x] Configurable storage path via environment variable by [@sl0wik](https://github.com/sl0wik) in https://github.com/laravel/framework/pull/48115
- [10.x] Support providing subquery as value to `where` builder method by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/48116
- [10.x] Minor Tweaks by [@utsavsomaiya](https://github.com/utsavsomaiya) in https://github.com/laravel/framework/pull/48138

## [v10.19.0](https://github.com/laravel/framework/compare/v10.18.0...v10.19.0) - 2023-08-15

- [10.x] Fix typo in update `HasUniqueIds` by [@iamcarlos94](https://github.com/iamcarlos94) in https://github.com/laravel/framework/pull/47994
- [10.x] Gracefully handle scientific notation by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/48002
- [10.x] Fix docblocks for throw_if and throw_unless by [@AbdelElrafa](https://github.com/AbdelElrafa) in https://github.com/laravel/framework/pull/48003
- [10.x] Add `wordWrap` to `Str` by [@joshbonnick](https://github.com/joshbonnick) in https://github.com/laravel/framework/pull/48012
- [10.x] Fix RetryBatchCommand overlapping of failed jobs when run concurrently with the same Batch ID using isolatableId by [@rybakihor](https://github.com/rybakihor) in https://github.com/laravel/framework/pull/48000
- [10.x] Fix `assertRedirectToRoute` when route uri is empty by [@khernik93](https://github.com/khernik93) in https://github.com/laravel/framework/pull/48023
- [10.x] Fix empty table displayed when using the --pending option but there are no pending migrations by [@TheBlckbird](https://github.com/TheBlckbird) in https://github.com/laravel/framework/pull/48019
- [10.x] Fix forced use of write DB connection by [@oleksiikhr](https://github.com/oleksiikhr) in https://github.com/laravel/framework/pull/48015
- [10.x] Use model cast when builder created updated at value by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47942
- [10.x] Fix Collection::search and LazyCollection::search return type by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/48030
- [10.x] Add ability to customize class resolution in event discovery by [@bastien-phi](https://github.com/bastien-phi) in https://github.com/laravel/framework/pull/48031
- [10.x] Add `percentage` method to Collections by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/48034
- [10.x] Fix parsing error in console when parameter description contains `--` by [@rxrw](https://github.com/rxrw) in https://github.com/laravel/framework/pull/48021
- [10.x] Allow Listeners to dynamically specify delay using `withDelay` by [@CalebDW](https://github.com/CalebDW) in https://github.com/laravel/framework/pull/48026
- [10.x] Add dynamic return types to rescue helper by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/48062
- [10.x] createMany & createManyQuietly add count argument by [@JHWelch](https://github.com/JHWelch) in https://github.com/laravel/framework/pull/48048
- [10.x] Attributes support on default component slot by [@royduin](https://github.com/royduin) in https://github.com/laravel/framework/pull/48039
- [10.x] Add WithoutRelations attribute for model serialization by [@Neol3108](https://github.com/Neol3108) in https://github.com/laravel/framework/pull/47989
- [10.x] Can apply WithoutRelations to entire class by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/48068
- [10.x] createMany & createManyQuietly make argument optional by [@JHWelch](https://github.com/JHWelch) in https://github.com/laravel/framework/pull/48070

## [v10.18.0](https://github.com/laravel/framework/compare/v17.1...v10.18.0) - 2023-08-08

- [10.x] Allow DatabaseRefreshed event to include given `database` and `seed` options by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47923
- [10.x] Use generics in `throw_if` and `throw_unless` to indicate dynamic exception type by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/47938
- [10.x] Fixes artisan about --only should be case insensitive by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47955
- [10.x] Improve decimal shape validation by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47954
- docs: update phpdoc in Str helper for remove function by [@squiaios](https://github.com/squiaios) in https://github.com/laravel/framework/pull/47967
- [10.x] Remove return on void callback by [@gonzunigad](https://github.com/gonzunigad) in https://github.com/laravel/framework/pull/47969
- [9.x] Improve decimal shape validation by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47957
- [10.x] Add `content` method to Vite by [@michael-rubel](https://github.com/michael-rubel) in https://github.com/laravel/framework/pull/47968
- [10.x] Allow empty port in psql schema dump by [@Arzaroth](https://github.com/Arzaroth) in https://github.com/laravel/framework/pull/47988
- [10.x] Show config when the value is false or zero by [@saeedhosseiinii](https://github.com/saeedhosseiinii) in https://github.com/laravel/framework/pull/47987
- [10.x] Add getter for components on IO interaction by [@chris-ware](https://github.com/chris-ware) in https://github.com/laravel/framework/pull/47982

## [v10.17.1](https://github.com/laravel/framework/compare/v10.17.0...v10.17.1) - 2023-08-02

- [9.x] Back porting #47838 by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47840
- [9.x] Normalise predis command argument where it maybe an object.   by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47902
- [9.x] Migrate JSON data to shared InputBag by [@ImJustToNy](https://github.com/ImJustToNy) in https://github.com/laravel/framework/pull/47919
- [10.x] Fix docblocks of the dispatchable trait by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/47921
- [9.x] Circumvent PHP 8.2.9 date format bug that makes artisan serve crash by [@levu42](https://github.com/levu42) in https://github.com/laravel/framework/pull/47931
- [10.x] Fix prompt and console component spacing when calling another command by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/47928
- [10.x] Fix prompt rendering after `callSilent` by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/47929
- [10.x] Update ensure() collection method to correctly work with Interfaces and object inheritance by [@karpilin](https://github.com/karpilin) in https://github.com/laravel/framework/pull/47934

## [v10.17.0](https://github.com/laravel/framework/compare/v10.16.1...v10.17.0) - 2023-08-01

- [10.x] Update `TrustProxies` to rely on `$headers` if properly set by [@inxilpro](https://github.com/inxilpro) in https://github.com/laravel/framework/pull/47844
- [10.x] Accept protocols as argument for URL validation by [@MrMicky-FR](https://github.com/MrMicky-FR) in https://github.com/laravel/framework/pull/47843
- [10.x] Support human-friendly text for file size by [@jxxe](https://github.com/jxxe) in https://github.com/laravel/framework/pull/47846
- [10.x] Added UploadedFile as return type by [@khrigo](https://github.com/khrigo) in https://github.com/laravel/framework/pull/47847
- [10.x] Add option to adjust database default lock timeout by [@joelharkes](https://github.com/joelharkes) in https://github.com/laravel/framework/pull/47854
- [10.x] PHP 8.3 builds by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/47788
- [10.x] Add Collection::enforce() method by [@inxilpro](https://github.com/inxilpro) in https://github.com/laravel/framework/pull/47785
- [10.x] Allow custom mutex names for isolated commands by [@rybakihor](https://github.com/rybakihor) in https://github.com/laravel/framework/pull/47814
- Fix for issues with closure-based scheduled commands in schedule:test by [@mobidev86](https://github.com/mobidev86) in https://github.com/laravel/framework/pull/47862
- [10.x] Extract customised deleted_at column name from Model FQN by [@edvordo](https://github.com/edvordo) in https://github.com/laravel/framework/pull/47873
- [10.x] Adding Minutes Option in Some Frequencies by [@joaopalopes24](https://github.com/joaopalopes24) in https://github.com/laravel/framework/pull/47789
- [10.x] Add `config:show` command by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/47858
- [10.x] Test Improvements for `hashed` password by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47904
- [10.x] Use shared facade script by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47901
- [10.x] Add --test and --pest options to make:component by [@nshiro](https://github.com/nshiro) in https://github.com/laravel/framework/pull/47894
- [10.x] Prompts by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/46772
- [10.x] Migrate JSON data to shared InputBag by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47914
- [10.x] Fix `Factory::configure()` return type by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/47920
- [10.x] Fix Http global middleware for queue, octane, and dependency injection by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47915

## [v10.16.1](https://github.com/laravel/framework/compare/v10.17.1...v10.16.1) - 2023-07-26

- [10.x] Fix BusFake::assertChained() for a single job by [@gehrisandro](https://github.com/gehrisandro) in https://github.com/laravel/framework/pull/47832
- [10.x] Retain `$request->request` `InputBag` type by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47838

## [v10.16.0](https://github.com/laravel/framework/compare/v10.15.0...v10.16.0) - 2023-07-25

- [10.x] Improve display of sub-minute tasks in `schedule:list` command. by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/47720
- [10.x] Add new SQL error message "No connection could be made because the target machine actively refused it" by [@magnusvin](https://github.com/magnusvin) in https://github.com/laravel/framework/pull/47718
- [10.x] Ignore second in HttpRequestTest date comparison by [@kylekatarnls](https://github.com/kylekatarnls) in https://github.com/laravel/framework/pull/47719
- [10.x] Call `renderForAssertions` in `assertHasSubject` by [@ttrig](https://github.com/ttrig) in https://github.com/laravel/framework/pull/47728
- [10.x] We dont want Symfony to catch pcntl signal by [@ChristopheBorcard](https://github.com/ChristopheBorcard) in https://github.com/laravel/framework/pull/47725
- [10.x] Use atomic locks for command mutex by [@Gaitholabi](https://github.com/Gaitholabi) in https://github.com/laravel/framework/pull/47624
- [10.x] Improve typehint for Model::getConnectionResolver() by [@LukeTowers](https://github.com/LukeTowers) in https://github.com/laravel/framework/pull/47749
- [10.x] add getRedisConnection to ThrottleRequestsWithRedis by [@snmatsui](https://github.com/snmatsui) in https://github.com/laravel/framework/pull/47742
- [10.x] Adjusts for Volt by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/47757
- [10.x] Fix sql server paging problems by [@joelharkes](https://github.com/joelharkes) in https://github.com/laravel/framework/pull/47763
- [10.x] Typo type of data by [@hungthai1401](https://github.com/hungthai1401) in https://github.com/laravel/framework/pull/47775
- [10.x] Add missing tests for the `schedule:list` command. by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/47787
- [10.x] Fix `Str::replace` return type by [@datlechin](https://github.com/datlechin) in https://github.com/laravel/framework/pull/47779
- [10.x] Collection::except() with null returns all by [@pniaps](https://github.com/pniaps) in https://github.com/laravel/framework/pull/47821
- [10.x] fix issue #47727 with wrong return type by [@renky](https://github.com/renky) in https://github.com/laravel/framework/pull/47820
- [10.x] Remove unused variable in `VendorPublishCommand` by [@hungthai1401](https://github.com/hungthai1401) in https://github.com/laravel/framework/pull/47817
- [10.x] Remove unused variable in `MigrateCommand` by [@sangnguyenplus](https://github.com/sangnguyenplus) in https://github.com/laravel/framework/pull/47816
- [10.x] Revert 47763 fix sql server by [@dunhamjared](https://github.com/dunhamjared) in https://github.com/laravel/framework/pull/47792
- [10.x] Add test for Message ID, References and Custom Headers for Mailables by [@alexbowers](https://github.com/alexbowers) in https://github.com/laravel/framework/pull/47791
- [10.x] Add support for `BackedEnum` in Collection `groupBy` method by [@osbre](https://github.com/osbre) in https://github.com/laravel/framework/pull/47823
- [10.x] Support inline disk for scoped driver by [@alexbowers](https://github.com/alexbowers) in https://github.com/laravel/framework/pull/47776
- [10.x] Allowing bind of IPv6 addresses in development server by [@MuriloChianfa](https://github.com/MuriloChianfa) in https://github.com/laravel/framework/pull/47804
- [10.x] Add more info to issue template by [@driesvints](https://github.com/driesvints) in https://github.com/laravel/framework/pull/47828

## [v10.15.0](https://github.com/laravel/framework/compare/v10.14.1...v10.15.0) - 2023-07-11

- [10.x] Change return type of `getPrivateToken` in AblyBroadcaster by [@milwad](https://github.com/milwad)-dev in https://github.com/laravel/framework/pull/47602
- [10.x] Add toRawSql, dumpRawSql() and ddRawSql() to Query Builders by [@tpetry](https://github.com/tpetry) in https://github.com/laravel/framework/pull/47507
- [10.x] Fix recorderHandler not recording changes made by middleware by [@j3j5](https://github.com/j3j5) in https://github.com/laravel/framework/pull/47614
- Pass queue from Mailable to SendQueuedMailable job by [@Tarpsvo](https://github.com/Tarpsvo) in https://github.com/laravel/framework/pull/47612
- [10.x] Sub-minute Scheduling by [@jessarcher](https://github.com/jessarcher) in https://github.com/laravel/framework/pull/47279
- [10.x] Fixes failing tests running on DynamoDB Local 2.0.0 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47653
- [10.x] Allow password reset callback to modify the result by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/47641
- Forget with collections by [@joelbutcher](https://github.com/joelbutcher) in https://github.com/laravel/framework/pull/47637
- [10.x] Do not apply global scopes when incrementing/decrementing an existing model by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/47629
- [10.x] Adds inline attachments support for "notifications" markdown mailables by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/47643
- Assertions for counting outgoing mailables by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/47655
- [10.x] Add getRawQueryLog() method by [@fuwasegu](https://github.com/fuwasegu) in https://github.com/laravel/framework/pull/47623
- [10.x] Fix Storage::cloud() return type by [@tattali](https://github.com/tattali) in https://github.com/laravel/framework/pull/47664
- [10.x] Add `isUrl` to the `Str` class and use it from the validator by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/47688
- [10.x] Remove unwanted call to include stack traces by [@HazzazBinFaiz](https://github.com/HazzazBinFaiz) in https://github.com/laravel/framework/pull/47687
- [10.x] Make Vite throw a new `ManifestNotFoundException` by [@innocenzi](https://github.com/innocenzi) in https://github.com/laravel/framework/pull/47681
- [10.x] Move class from file logic in Console Kernel to dedicated method by [@CalebDW](https://github.com/CalebDW) in https://github.com/laravel/framework/pull/47665
- [10.x] Dispatch model pruning started and ended events by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/47669
- [10.x] Update DatabaseRule to handle Enums for simple where clause by [@CalebDW](https://github.com/CalebDW) in https://github.com/laravel/framework/pull/47679
- [10.x] Add data_remove helper by [@PhiloNL](https://github.com/PhiloNL) in https://github.com/laravel/framework/pull/47618
- [10.x] Added tests for `isUrl` to Str. by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/47690
- [10.x] Added `isUrl` to Stringable.  by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/47689
- [10.x] Tweak return type for missing config by [@sfreytag](https://github.com/sfreytag) in https://github.com/laravel/framework/pull/47702
- [10.x] Fix parallel testing without any database connection by [@deleugpn](https://github.com/deleugpn) in https://github.com/laravel/framework/pull/47705
- [10.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/47709
- [10.x] Allows HTTP exceptions to be thrown for views by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/47714

## [v10.14.1](https://github.com/laravel/framework/compare/v10.14.0...v10.14.1) - 2023-06-28

- [10.x] Fix `Dispatcher::until` return type by @Neol3108 in https://github.com/laravel/framework/pull/47585
- [10.x] Add Collection::wrap to add method on BatchFake by @schonhoff in https://github.com/laravel/framework/pull/47589
- [10.x] Fixes grammar in FoundationServiceProvider by @adampatterson in https://github.com/laravel/framework/pull/47593
- [10.x] Ensure duration is present by @timacdonald in https://github.com/laravel/framework/pull/47596

## [v10.14.0](https://github.com/laravel/framework/compare/v10.13.5...v10.14.0) - 2023-06-27

- [10.x] Add test for `withCookies` method in RedirectResponse by @milwad-dev in https://github.com/laravel/framework/pull/47383
- [10.x] Add new error message "SSL: Handshake timed out" handling to PDO Dete… by @yehorherasymchuk in https://github.com/laravel/framework/pull/47392
- [10.x] Add new error messages for detecting lost connections by @mfn in https://github.com/laravel/framework/pull/47398
- [10.x] Update phpdoc `except` method in Middleware by @milwad-dev in https://github.com/laravel/framework/pull/47408
- [10.x] Fix inconsistent type hint for `$passwordTimeoutSeconds` by @devfrey in https://github.com/laravel/framework/pull/47414
- Change visibility of `path` method in FileStore.php by @foremtehan in https://github.com/laravel/framework/pull/47413
- [10.x] Fix return type of `buildException` method by @milwad-dev in https://github.com/laravel/framework/pull/47422
- [10.x] Allow serialization of NotificationSent by @cosmastech in https://github.com/laravel/framework/pull/47375
- [10.x] Incorrect comment in `PredisConnector` and `PhpRedisConnector` by @hungthai1401 in https://github.com/laravel/framework/pull/47438
- [10.x] Can set custom Response for denial within `Gate@inspect()` by @cosmastech in https://github.com/laravel/framework/pull/47436
- [10.x] Remove unnecessary param in `addSingletonUpdate` by @milwad-dev in https://github.com/laravel/framework/pull/47446
- [10.x] Fix return type of `prefixedResource` & `prefixedResource` by @milwad-dev in https://github.com/laravel/framework/pull/47445
- [10.x] Add Factory::getNamespace() by @tylernathanreed in https://github.com/laravel/framework/pull/47463
- [10.x] Add `whenAggregated` method to `ConditionallyLoadsAttributes` trait by @akr4m in https://github.com/laravel/framework/pull/47417
- [10.x] Add PendingRequest `withHeader()` method by @ralphjsmit in https://github.com/laravel/framework/pull/47474
- [10.x] Fix $exceptTables to allow an array of table names by @cwilby in https://github.com/laravel/framework/pull/47477
- [10.x] Fix `eachById` on `HasManyThrough` relation by @cristiancalara in https://github.com/laravel/framework/pull/47479
- [10.x] Allow object caching to be disabled for custom class casters by @CalebDW in https://github.com/laravel/framework/pull/47423
- [10.x] "Can" validation rule by @stevebauman in https://github.com/laravel/framework/pull/47371
- [10.x] refactor(Parser.php): Removing the extra "else" statement by @saMahmoudzadeh in https://github.com/laravel/framework/pull/47483
- [10.x] Add `UncompromisedVerifier::class` to `provides()` in `ValidationServiceProvider` by @xurshudyan in https://github.com/laravel/framework/pull/47500
- [9.x] Fix SES V2 Transport "reply to" addresses by @jacobmllr95 in https://github.com/laravel/framework/pull/47522
- [10.x] Reindex appends attributes by @hungthai1401 in https://github.com/laravel/framework/pull/47519
- [10.x] Fix `ListenerMakeCommand` deprecations by @dammy001 in https://github.com/laravel/framework/pull/47517
- [10.x] Add `HandlesPotentiallyTranslatedString` trait  by @xurshudyan in https://github.com/laravel/framework/pull/47488
- [10.x] update [JsonResponse]: using match expression instead of if-elseif-else by @saMahmoudzadeh in https://github.com/laravel/framework/pull/47524
- [10.x] Add `withQueryParameters` to the HTTP client by @mnapoli in https://github.com/laravel/framework/pull/47297
- [10.x] Allow `%` symbol in component attribute names by @JayBizzle in https://github.com/laravel/framework/pull/47533
- [10.x] Fix Http client pool return type by @srdante in https://github.com/laravel/framework/pull/47530
- [10.x] Use `match` expression in `resolveSynchronousFake` by @osbre in https://github.com/laravel/framework/pull/47540
- [10.x] Use `match` expression in `compileHaving` by @osbre in https://github.com/laravel/framework/pull/47548
- [10.x] Use `match` expression in `getArrayableItems` by @osbre in https://github.com/laravel/framework/pull/47549
- [10.x] Fix return type in `SessionGuard` by @PerryvanderMeer in https://github.com/laravel/framework/pull/47553
- [10.x] Fix return type in `DatabaseQueue` by @PerryvanderMeer in https://github.com/laravel/framework/pull/47552
- [10.x] Fix return type in `DumpCommand` by @PerryvanderMeer in https://github.com/laravel/framework/pull/47556
- [10.x] Fix return type in `MigrateMakeCommand` by @PerryvanderMeer in https://github.com/laravel/framework/pull/47557
- [10.x] Add missing return to `Factory` by @PerryvanderMeer in https://github.com/laravel/framework/pull/47559
- [10.x] Update doc in Eloquent model by @alirezasalehizadeh in https://github.com/laravel/framework/pull/47562
- [10.x] Fix return types by @PerryvanderMeer in https://github.com/laravel/framework/pull/47561
- [10.x] Fix PHPDoc throw type by @fernandokbs in https://github.com/laravel/framework/pull/47566
- [10.x]  Add hasAny function to ComponentAttributeBag, Allow multiple keys in has function by @indykoning in https://github.com/laravel/framework/pull/47569
- [10.x] Ensure captured time is in configured timezone by @timacdonald in https://github.com/laravel/framework/pull/47567
- [10.x] Add Method to Report only logged exceptions by @joelharkes in https://github.com/laravel/framework/pull/47554
- [10.x] Add global middleware to `Http` client by @timacdonald in https://github.com/laravel/framework/pull/47525
- [9.x] Fixes unable to use `trans()->has()` on JSON language files. by @crynobone in https://github.com/laravel/framework/pull/47582

## [v10.13.5](https://github.com/laravel/framework/compare/v10.13.3...v10.13.5) - 2023-06-08

- Revert "[10.x] Update Kernel::load() to use same `classFromFile` logic as events" by @taylorotwell in https://github.com/laravel/framework/pull/47382

## [v10.13.3](https://github.com/laravel/framework/compare/v10.13.2...v10.13.3) - 2023-06-08

### What's Changed

- Narrow down array type for `$attributes` in `CastsAttributes` by @devfrey in https://github.com/laravel/framework/pull/47365
- Add test for `assertViewHasAll` method by @milwad-dev in https://github.com/laravel/framework/pull/47366
- Fix `schedule:list` to display named Jobs by @liamkeily in https://github.com/laravel/framework/pull/47367
- Support `ConditionalRules` within `NestedRules` by @cosmastech in https://github.com/laravel/framework/pull/47344
- Small test fixes by @stevebauman in https://github.com/laravel/framework/pull/47369
- Pluralisation typo in queue:clear command output by @sebsobseb in https://github.com/laravel/framework/pull/47376
- Add getForeignKeyFrom method by @iamgergo in https://github.com/laravel/framework/pull/47378
- Add shouldHashKeys to ThrottleRequests middleware by @fosron in https://github.com/laravel/framework/pull/47368

## [v10.13.2 (2023-06-05)](https://github.com/laravel/framework/compare/v10.13.1...v10.13.2)

### Added

- Added `Illuminate/Http/Client/PendingRequest::replaceHeaders()` ([#47335](https://github.com/laravel/framework/pull/47335))
- Added `Illuminate/Notifications/Messages/MailMessage::attachMany()` ([#47345](https://github.com/laravel/framework/pull/47345))

### Reverted

- Revert "[10.x] Remove session on authenticatable deletion v2" ([#47354](https://github.com/laravel/framework/pull/47354))

### Fixed

- Fixes usage of Redis::many() with empty array ([#47307](https://github.com/laravel/framework/pull/47307))
- Fix mapped renderable exception handling ([#47347](https://github.com/laravel/framework/pull/47347))
- Avoid duplicates in fillable/guarded on merge in Illuminate/Database/Eloquent/Concerns/GuardsAttributes.php ([#47351](https://github.com/laravel/framework/pull/47351))

### Changed

- Update Kernel::load() to use same classFromFile logic as events ([#47327](https://github.com/laravel/framework/pull/47327))
- Remove redundant 'setAccessible' methods ([#47348](https://github.com/laravel/framework/pull/47348))

## [v10.13.1 (2023-06-02)](https://github.com/laravel/framework/compare/v10.13.0...v10.13.1)

### Added

- Added `Illuminate\Contracts\Database\Query\ConditionExpression` interface and functional for this ([#47210](https://github.com/laravel/framework/pull/47210))
- Added return type for `Illuminate/Notifications/Channels/MailChannel::send()` ([#47310](https://github.com/laravel/framework/pull/47310))

### Reverted

- Revert "[10.x] Fix inconsistency between report and render methods" ([#47326](https://github.com/laravel/framework/pull/47326))

### Changed

- Display queue runtime in human readable format ([#47227](https://github.com/laravel/framework/pull/47227))

## [v10.13.0 (2023-05-30)](https://github.com/laravel/framework/compare/v10.12.0...v10.13.0)

### Added

- Added `Illuminate/Hashing/HashManager::isHashed()` ([#47197](https://github.com/laravel/framework/pull/47197))
- Escaping functionality within the Grammar ([#46558](https://github.com/laravel/framework/pull/46558))
- Provide testing hooks in `Illuminate/Support/Sleep.php` ([#47228](https://github.com/laravel/framework/pull/47228))
- Added missing methods to AssertsStatusCodes ([#47277](https://github.com/laravel/framework/pull/47277))
- Wrap response preparation in events ([#47229](https://github.com/laravel/framework/pull/47229))

### Fixed

- Fixed bug when function wrapped around definition of related factory ([#47168](https://github.com/laravel/framework/pull/47168))
- Fixed inconsistency between report and render methods ([#47201](https://github.com/laravel/framework/pull/47201))
- Fixes Model::isDirty() when AsCollection or AsEncryptedCollection have arguments ([#47235](https://github.com/laravel/framework/pull/47235))
- Fixed escaped String for JSON_CONTAINS ([#47244](https://github.com/laravel/framework/pull/47244))
- Fixes missing output on ProcessFailedException exception ([#47285](https://github.com/laravel/framework/pull/47285))

### Changed

- Remove useless else statements ([#47186](https://github.com/laravel/framework/pull/47186))
- RedisStore improvement - don't open transaction unless all values are serialaizable ([#47193](https://github.com/laravel/framework/pull/47193))
- Use carbon::now() to get current timestamp in takeUntilTimeout lazycollection-method ([#47200](https://github.com/laravel/framework/pull/47200))
- Avoid duplicates in visible/hidden on merge ([#47264](https://github.com/laravel/framework/pull/47264))
- Add a missing semicolon to CompilesClasses ([#47280](https://github.com/laravel/framework/pull/47280))
- Send along value to InvalidPayloadException ([#47223](https://github.com/laravel/framework/pull/47223))

## [v10.12.0 (2023-05-23)](https://github.com/laravel/framework/compare/v10.11.0...v10.12.0)

### Added

- Added `Illuminate/Queue/Events/JobTimedOut.php` ([#47068](https://github.com/laravel/framework/pull/47068))
- Added `when()` and `unless()` methods to `Illuminate/Support/Sleep` ([#47114](https://github.com/laravel/framework/pull/47114))
- Adds inline attachments support for markdown mailables ([#47140](https://github.com/laravel/framework/pull/47140))
- Added `Illuminate/Testing/Concerns/AssertsStatusCodes::assertMethodNotAllowed()` ([#47169](https://github.com/laravel/framework/pull/47169))
- Added `forceCreateQuietly` method ([#47162](https://github.com/laravel/framework/pull/47162))
- Added parameters to timezone validation rule ([#47171](https://github.com/laravel/framework/pull/47171))

### Fixed

- Fixes singleton and api singletons creatable|destryoable|only|except combinations ([#47098](https://github.com/laravel/framework/pull/47098))
- Don't use empty key or secret for DynamoDBClient ([#47144](https://github.com/laravel/framework/pull/47144))

### Changed

- Remove session on authenticatable deletion ([#47141](https://github.com/laravel/framework/pull/47141))
- Added error handling and ensure re-enabling of foreign key constraints in `Illuminate/Database/Schema/Builder::withoutForeignKeyConstraints()` ([#47182](https://github.com/laravel/framework/pull/47182))

### Refactoring

- Remove useless else statements ([#47161](https://github.com/laravel/framework/pull/47161))

## [v10.11.0 (2023-05-16)](https://github.com/laravel/framework/compare/v10.10.1...v10.11.0)

### Added

- Added the ability to extend the generic types for DatabaseNotificationCollection ([#47048](https://github.com/laravel/framework/pull/47048))
- Added `/Illuminate/Support/Carbon::createFromId()` ([#47046](https://github.com/laravel/framework/pull/47046))
- Added Name attributes on slots ([#47065](https://github.com/laravel/framework/pull/47065))
- Added Precognition-Success header ([#47081](https://github.com/laravel/framework/pull/47081))
- Added Macroable trait to Sleep class ([#47099](https://github.com/laravel/framework/pull/47099))

### Fixed

- Fixed `Illuminate/Database/Console/ShowModelCommand::getPolicy()` ([#47043](https://github.com/laravel/framework/pull/47043))

### Changed

- Remove return from channelRoutes method ([#47059](https://github.com/laravel/framework/pull/47059))
- Bug in `Illuminate/Database/Migrations/Migrator::reset()` with string path ([#47047](https://github.com/laravel/framework/pull/47047))
- Unify logic around cursor paginate ([#47094](https://github.com/laravel/framework/pull/47094))
- Clears resolved instance of Vite when using withoutVite ([#47091](https://github.com/laravel/framework/pull/47091))
- Remove workarounds for old Guzzle versions ([#47084](https://github.com/laravel/framework/pull/47084))

## [v10.10.1 (2023-05-11)](https://github.com/laravel/framework/compare/v10.10.0...v10.10.1)

### Added

- Added `/Illuminate/Collections/Arr::mapWithKeys()` ([#47000](https://github.com/laravel/framework/pull/47000))
- Added `dd` and `dump` methods to `Illuminate/Support/Carbon.php` ([#47002](https://github.com/laravel/framework/pull/47002))
- Added `Illuminate/Queue/Failed/FileFailedJobProvider` ([#47007](https://github.com/laravel/framework/pull/47007))
- Added arguments to the signed middleware to ignore properties ([#46987](https://github.com/laravel/framework/pull/46987))

### Fixed

- Added keys length check to prevent mget error in `Illuminate/Cache/RedisStore::many()` ([#46998](https://github.com/laravel/framework/pull/46998))
- 'hashed' cast - do not rehash already hashed value ([#47029](https://github.com/laravel/framework/pull/47029))

### Changed

- Used `Carbon::now()` instead of `now()` ([#47017](https://github.com/laravel/framework/pull/47017))
- Use file locks when writing failed jobs to disk ([b822d28](https://github.com/laravel/framework/commit/b822d2810d29ab1aedf667abc76ed969d28bbaf5))
- Raise visibility of Mailable prepareMailableForDelivery() ([#47031](https://github.com/laravel/framework/pull/47031))

## [v10.10.0 (2023-05-09)](https://github.com/laravel/framework/compare/v10.9.0...v10.10.0)

### Added

- Added `$isolated` and `isolatedExitCode` properties to `Illuminate/Console/Command` ([#46925](https://github.com/laravel/framework/pull/46925))
- Added ability to restore/set Global Scopes ([#46922](https://github.com/laravel/framework/pull/46922))
- Added `Illuminate/Collections/Arr::sortRecursiveDesc()` ([#46945](https://github.com/laravel/framework/pull/46945))
- Added `Illuminate/Support/Sleep` ([#46904](https://github.com/laravel/framework/pull/46904), [#46963](https://github.com/laravel/framework/pull/46963))
- Added `Illuminate/Database/Eloquent/Concerns/HasAttributes::castAttributeAsHashedString()` ([#46947]https://github.com/laravel/framework/pull/46947)
- Added url support for mail config ([#46964](https://github.com/laravel/framework/pull/46964))

### Fixed

- Fixed replace missing_unless ([89ac58a](https://github.com/laravel/framework/commit/89ac58aa9b4fb7ef9f3b2290921488da1454ed30))
- Gracefully handle invalid code points in e() ([#46914](https://github.com/laravel/framework/pull/46914))
- HasCasts returning false instead of true ([#46992](https://github.com/laravel/framework/pull/46992))

### Changed

- Use method on UploadedFile to validate image dimensions ([#46912](https://github.com/laravel/framework/pull/46912))
- Expose Js::json() helper ([#46935](https://github.com/laravel/framework/pull/46935))
- Respect parents on middleware priority ([#46972](https://github.com/laravel/framework/pull/46972))
- Do reconnect when redis throws connection lost error ([#46989](https://github.com/laravel/framework/pull/46989))
- Throw timeoutException instead of maxAttemptsExceededException when a job times out ([#46968](https://github.com/laravel/framework/pull/46968))

## [v10.9.0 (2023-04-25)](https://github.com/laravel/framework/compare/v10.8.0...v10.9.0)

### Added

- Add new HTTP status assertions ([#46841](https://github.com/laravel/framework/pull/46841))
- Allow pruning all cancelled and unfinished queue batches ([#46833](https://github.com/laravel/framework/pull/46833))
- Added `IGNITION_LOCAL_SITES_PATH` to `$passthroughVariables` in `ServeCommand.php` ([#46857](https://github.com/laravel/framework/pull/46857))
- Added named static methods for middleware ([#46362](https://github.com/laravel/framework/pull/46362))

### Fixed

- Fix date_format rule throw ValueError ([#46824](https://github.com/laravel/framework/pull/46824))

### Changed

- Allow separate directory for locks on filestore ([#46811](https://github.com/laravel/framework/pull/46811))
- Allow to whereMorphedTo work with null model ([#46821](https://github.com/laravel/framework/pull/46821))
- Use pivot model fromDateTime instead of assuming Carbon in `Illuminate/Database/Eloquent/Relations/Concerns/InteractsWithPivotTable::addTimestampsToAttachment()` ([#46822](https://github.com/laravel/framework/pull/46822))
- Make rules method in FormRequest optional ([#46846](https://github.com/laravel/framework/pull/46846))
- Throw LogicException when calling FileFactory@image() if mimetype is not supported ([#46859](https://github.com/laravel/framework/pull/46859))
- Improve job release method to accept date instance ([#46854](https://github.com/laravel/framework/pull/46854))
- Use foreignUlid if model uses HasUlids trait when call foreignIdFor ([#46876](https://github.com/laravel/framework/pull/46876))

## [v10.8.0 (2023-04-18)](https://github.com/laravel/framework/compare/v10.7.1...v10.8.0)

### Added

- Added syntax sugar to the Process::pipe method ([#46745](https://github.com/laravel/framework/pull/46745))
- Allow specifying index name when calling ForeignIdColumnDefinition@constrained() ([#46746](https://github.com/laravel/framework/pull/46746))
- Allow to customise redirect URL in AuthenticateSession Middleware ([#46752](https://github.com/laravel/framework/pull/46752))
- Added Class based after validation rules ([#46757](https://github.com/laravel/framework/pull/46757))
- Added max exceptions to broadcast event ([#46800](https://github.com/laravel/framework/pull/46800))

### Fixed

- Fixed compiled view file ends with .php ([#46755](https://github.com/laravel/framework/pull/46755))
- Fix validation rule names ([#46768](https://github.com/laravel/framework/pull/46768))
- Fixed validateDecimal() ([#46809](https://github.com/laravel/framework/pull/46809))

### Changed

- Add headers to exception in `Illuminate/Foundation/Application::abourd()` ([#46780](https://github.com/laravel/framework/pull/46780))
- Minor skeleton slimming (framework edition) ([#46786](https://github.com/laravel/framework/pull/46786))
- Release lock for job implementing ShouldBeUnique that is dispatched afterResponse() ([#46806](https://github.com/laravel/framework/pull/46806))

## [v10.7.1 (2023-04-11)](https://github.com/laravel/framework/compare/v10.7.0...v10.7.1)

### Changed

- Changed `Illuminate/Process/Factory::pipe()` method. It will be run pipes immediately ([e34ab39](https://github.com/laravel/framework/commit/e34ab392800bfc175334c90e9321caa7261c2d65))

## [v10.7.0 (2023-04-11)](https://github.com/laravel/framework/compare/v10.6.2...v10.7.0)

### Added

- Allow `Illuminate/Foundation/Testing/WithFaker` to be used when app is not bound ([#46529](https://github.com/laravel/framework/pull/46529))
- Allow Event::assertListening to check for invokable event listeners ([#46683](https://github.com/laravel/framework/pull/46683))
- Added `Illuminate/Process/Factory::pipe()` ([#46527](https://github.com/laravel/framework/pull/46527))
- Added `Illuminate/Validation/Validator::setValue` ([#46716](https://github.com/laravel/framework/pull/46716))

### Fixed

- PHP 8.0 fix for Closure jobs ([#46505](https://github.com/laravel/framework/pull/46505))
- Fix preg_split error when there is a slash in the attribute in `Illuminate/Validation/ValidationData` ([#46549](https://github.com/laravel/framework/pull/46549))
- Fixed Cache::spy incompatibility with Cache::get ([#46689](https://github.com/laravel/framework/pull/46689))
- server command: Fixed server Closing output on invalid $requestPort ([#46726](https://github.com/laravel/framework/pull/46726))
- Fix nested join when not JoinClause instance ([#46712](https://github.com/laravel/framework/pull/46712))
- Fix query builder whereBetween method with carbon date period ([#46720](https://github.com/laravel/framework/pull/46720))

### Changed

- Removes unnecessary parameters in `creatable()` / `destroyable()` methods in `Illuminate/Routing/PendingSingletonResourceRegistration` ([#46677](https://github.com/laravel/framework/pull/46677))
- Return non-zero exit code for uncaught exceptions ([#46541](https://github.com/laravel/framework/pull/46541))

## [v10.6.2 (2023-04-05)](https://github.com/laravel/framework/compare/v10.6.1...v10.6.2)

### Added

- Added trait `Illuminate/Foundation/Testing/WithConsoleEvents` ([#46694](https://github.com/laravel/framework/pull/46694))

### Changed

- Added missing ignored methods to `Illuminate/View/Component` ([#46692](https://github.com/laravel/framework/pull/46692))
- console.stub: remove void return type from handle ([#46697](https://github.com/laravel/framework/pull/46697))

## [v10.6.1 (2023-04-04)](https://github.com/laravel/framework/compare/v10.6.0...v10.6.1)

### Reverted

- Reverted ["Set container instance on session manager"Set container instance on session manager](https://github.com/laravel/framework/pull/46621) ([#46691](https://github.com/laravel/framework/pull/46691))

## [v10.6.0 (2023-04-04)](https://github.com/laravel/framework/compare/v10.5.1...v10.6.0)

### Added

- Added ability to set a custom class for the AsCollection and AsEncryptedCollection casts ([#46619](https://github.com/laravel/framework/pull/46619))

### Changed

- Set container instance on session manager ([#46621](https://github.com/laravel/framework/pull/46621))
- Added empty string definition to Str::squish function ([#46660](https://github.com/laravel/framework/pull/46660))
- Allow $sleepMilliseconds parameter receive a Closure in retry method from PendingRequest ([#46653](https://github.com/laravel/framework/pull/46653))
- Support contextual binding on first class callables ([de8d515](https://github.com/laravel/framework/commit/de8d515fc6d1fabc8f14450342554e0eb67df725), [e511a3b](https://github.com/laravel/framework/commit/e511a3bdb15c294866428b4fe665a4ad14540038))

## [v10.5.1 (2023-03-29)](https://github.com/laravel/framework/compare/v10.5.0...v10.5.1)

### Added

- Added methods to determine if API resource has pivot loaded ([#46555](https://github.com/laravel/framework/pull/46555))
- Added caseSensitive flag to Stringable replace function ([#46578](https://github.com/laravel/framework/pull/46578))
- Allow insert..select (insertUsing()) to have empty $columns ([#46605](https://github.com/laravel/framework/pull/46605), [399bff9](https://github.com/laravel/framework/commit/399bff9331252e64a3439ea43e05f87f901dad55))
- Added `Illuminate/Database/Connection::selectResultSets()` ([#46592](https://github.com/laravel/framework/pull/46592))

### Changed

- Make sure pivot model has previously defined values ([#46559](https://github.com/laravel/framework/pull/46559))
- Move SetUniqueIds to run before the creating event ([#46622](https://github.com/laravel/framework/pull/46622))

## [v10.5.0 (2023-03-28)](https://github.com/laravel/framework/compare/v10.4.1...v10.5.0)

### Added

- Added `Illuminate/Cache/CacheManager::setApplication()` ([#46594](https://github.com/laravel/framework/pull/46594))

### Fixed

- Fix infinite loading on batches list on Horizon ([#46536](https://github.com/laravel/framework/pull/46536))
- Fix whereNull queries with raw expressions for the MySql grammar ([#46538](https://github.com/laravel/framework/pull/46538))
- Fix getDirty method when using AsEnumArrayObject / AsEnumCollection ([#46561](https://github.com/laravel/framework/pull/46561))

### Changed

- Skip `Illuminate/Support/Reflector::isParameterBackedEnumWithStringBackingType` for non ReflectionNamedType ([#46511](https://github.com/laravel/framework/pull/46511))
- Replace Deprecated DBAL Comparator creation with schema aware Comparator ([#46517](https://github.com/laravel/framework/pull/46517))
- Added Storage::json() method to read and decode a json file ([#46548](https://github.com/laravel/framework/pull/46548))
- Force cast json decoded failed_job_ids to array in DatabaseBatchRepository ([#46581](https://github.com/laravel/framework/pull/46581))
- Handle empty arrays for DynamoDbStore multi-key operations ([#46579](https://github.com/laravel/framework/pull/46579))
- Stop adding constraints twice on *Many to *One relationships via one() ([#46575](https://github.com/laravel/framework/pull/46575))
- allow override of the Builder paginate() total ([#46415](https://github.com/laravel/framework/pull/46415))
- Add a possibility to set a custom on_stats function for the Http Facade ([#46569](https://github.com/laravel/framework/pull/46569))

## [v10.4.1 (2023-03-18)](https://github.com/laravel/framework/compare/v10.4.0...v10.4.1)

### Changed

- Move Symfony events dispatcher registration to Console\Kernel ([#46508](https://github.com/laravel/framework/pull/46508))

## [v10.4.0 (2023-03-17)](https://github.com/laravel/framework/compare/v10.3.3...v10.4.0)

### Added

- Added `Illuminate/Testing/Concerns/AssertsStatusCodes::assertUnsupportedMediaType()` ([#46426](https://github.com/laravel/framework/pull/46426))
- Added curl_error_code: 77 to DetectsLostConnections ([#46429](https://github.com/laravel/framework/pull/46429))
- Allow for converting a HasMany to HasOne && MorphMany to MorphOne ([#46443](https://github.com/laravel/framework/pull/46443))
- Add option to create macroable method for paginationInformation ([#46461](https://github.com/laravel/framework/pull/46461))
- Added `Illuminate/Filesystem/Filesystem::json()` ([#46481](https://github.com/laravel/framework/pull/46481))

### Fixed

- Fix parsed input arguments for command events using dispatcher rerouting ([#46442](https://github.com/laravel/framework/pull/46442))
- Fix enums uses with optional implicit parameters ([#46483](https://github.com/laravel/framework/pull/46483))
- Fix deprecations for embedded images in symfony mailer ([#46488](https://github.com/laravel/framework/pull/46488))

### Changed

- Added alternative database port in Postgres DSN ([#46403](https://github.com/laravel/framework/pull/46403))
- Allow calling getControllerClass on closure-based routes ([#46411](https://github.com/laravel/framework/pull/46411))
- Remove obsolete method_exists(ReflectionClass::class, 'isEnum') call ([#46445](https://github.com/laravel/framework/pull/46445))
- Convert eloquent builder to base builder in whereExists ([#46460](https://github.com/laravel/framework/pull/46460))
- Refactor shared static methodExcludedByOptions method to trait ([#46498](https://github.com/laravel/framework/pull/46498))

## [v10.3.3 (2023-03-09)](https://github.com/laravel/framework/compare/v10.3.2...v10.3.3)

### Reverted

- Reverted ["Allow override of the Builder paginate() total"](https://github.com/laravel/framework/pull/46336) ([#46406](https://github.com/laravel/framework/pull/46406))

## [v10.3.2 (2023-03-08)](https://github.com/laravel/framework/compare/v10.3.1...v10.3.2)

### Reverted

- Reverted ["FIX on CanBeOneOfMany trait giving erroneous results"](https://github.com/laravel/framework/pull/46309) ([#46402](https://github.com/laravel/framework/pull/46402))

### Fixed

- Fixes Expression no longer implements Stringable ([#46395](https://github.com/laravel/framework/pull/46395))

## [v10.3.1 (2023-03-08)](https://github.com/laravel/framework/compare/v10.3.0...v10.3.1)

### Reverted

- Reverted ["Use fallback when previous URL is the same as the current in `Illuminate/Routing/UrlGenerator::previous()`"](https://github.com/laravel/framework/pull/46234) ([#46392](https://github.com/laravel/framework/pull/46392))

## [v10.3.0 (2023-03-07)](https://github.com/laravel/framework/compare/v10.2.0...v10.3.0)

### Added

- Adding Pipeline Facade ([#46271](https://github.com/laravel/framework/pull/46271))
- Add Support for SaveQuietly and Upsert with UUID/ULID Primary Keys ([#46161](https://github.com/laravel/framework/pull/46161))
- Add charAt method to both Str and Stringable ([#46349](https://github.com/laravel/framework/pull/46349), [dfb59bc2](https://github.com/laravel/framework/commit/dfb59bc263a4e28ac8992deeabd2ccd9392d1681))
- Adds Countable to the InvokedProcessPool class ([#46346](https://github.com/laravel/framework/pull/46346))
- Add processors to logging (placeholders) ([#46344](https://github.com/laravel/framework/pull/46344))

### Fixed

- Fixed `Illuminate/Mail/Mailable::buildMarkdownView()` ([791f8ea7](https://github.com/laravel/framework/commit/791f8ea70b5872ae4483a32f6aeb28dd2ed4b8d7))
- FIX on CanBeOneOfMany trait giving erroneous results ([#46309](https://github.com/laravel/framework/pull/46309))

### Changed

- Use fallback when previous URL is the same as the current in `Illuminate/Routing/UrlGenerator::previous()` ([#46234](https://github.com/laravel/framework/pull/46234))
- Allow override of the Builder paginate() total ([#46336](https://github.com/laravel/framework/pull/46336))

## [v10.2.0 (2023-03-02)](https://github.com/laravel/framework/compare/v10.1.5...v10.2.0)

### Added

- Adding `Conditionable` train to Logger ([#46259](https://github.com/laravel/framework/pull/46259))
- Added "dot" method to Illuminate\Support\Collection class ([#46265](https://github.com/laravel/framework/pull/46265))
- Added a "channel:list" command ([#46248](https://github.com/laravel/framework/pull/46248))
- Added JobPopping and JobPopped events ([#46220](https://github.com/laravel/framework/pull/46220))
- Add isMatch method to Str and Stringable helpers ([#46303](https://github.com/laravel/framework/pull/46303))
- Add ArrayAccess to Stringable ([#46279](https://github.com/laravel/framework/pull/46279))

### Reverted

- Revert "[10.x] Fix custom themes not resetting on Markdown renderer" ([#46328](https://github.com/laravel/framework/pull/46328))

### Fixed

- Fix typo in function `createMissingSqliteDatbase` name in `src/Illuminate/Database/Console/Migrations/MigrateCommand.php` ([#46326](https://github.com/laravel/framework/pull/46326))

### Changed

- Generate default command name based on class name in `ConsoleMakeCommand` ([#46256](https://github.com/laravel/framework/pull/46256))
- Do not mutate underlying values on redirect ([#46281](https://github.com/laravel/framework/pull/46281))
- Do not use null to initialise $lastExecutionStartedAt in `ScheduleWorkCommand` ([#46285](https://github.com/laravel/framework/pull/46285))
- Remove obsolete function_exists('enum_exists') calls ([#46319](https://github.com/laravel/framework/pull/46319))
- Cast json decoded failed_job_ids to array in DatabaseBatchRepository::toBatch ([#46329](https://github.com/laravel/framework/pull/46329))

## [v10.1.5 (2023-02-24)](https://github.com/laravel/framework/compare/v10.1.4...v10.1.5)

### Fixed

- Fixed `Illuminate/Foundation/Testing/Concerns/InteractsWithDatabase::expectsDatabaseQueryCount()` $connection parameter ([#46228](https://github.com/laravel/framework/pull/46228))
- Fixed Facade Fake ([#46257](https://github.com/laravel/framework/pull/46257))

### Changed

- Remove autoload dumping from make:migration ([#46215](https://github.com/laravel/framework/pull/46215))

## [v10.1.4 (2023-02-23)](https://github.com/laravel/framework/compare/v10.1.3...v10.1.4)

### Changed

- Improve Facade Fake Awareness ([#46188](https://github.com/laravel/framework/pull/46188), [#46232](https://github.com/laravel/framework/pull/46232))

## [v10.1.3 (2023-02-22)](https://github.com/laravel/framework/compare/v10.1.2...v10.1.3)

### Added

- Added protected method `Illuminate/Http/Resources/Json/JsonResource::newCollection()` for simplifies collection customisation ([#46217](https://github.com/laravel/framework/pull/46217))

### Fixed

- Fixes constructable migrations ([#46223](https://github.com/laravel/framework/pull/46223))

### Changes

- Accept time when generating ULID in `Str::ulid()` ([#46201](https://github.com/laravel/framework/pull/46201))

## [v10.1.2 (2023-02-22)](https://github.com/laravel/framework/compare/v10.1.1...v10.1.2)

### Reverted

- Revert changes from `Arr::random()` ([cf3eb90](https://github.com/laravel/framework/commit/cf3eb90a6473444bb7a78d1a3af1e9312a62020d))

## [v10.1.1 (2023-02-21)](https://github.com/laravel/framework/compare/v10.1.0...v10.1.1)

### Added

- Add the ability to re-resolve cache drivers ([#46203](https://github.com/laravel/framework/pull/46203))

### Fixed

- Fixed `Illuminate/Collections/Arr::shuffle()` for empty array ([0c6cae0](https://github.com/laravel/framework/commit/0c6cae0ef647158b9554cad05ff39db7e7ad0d33))

## [v10.1.0 (2023-02-21)](https://github.com/laravel/framework/compare/v10.0.3...v10.1.0)

### Fixed

- Fixing issue where 0 is discarded as a valid timestamp ([#46158](https://github.com/laravel/framework/pull/46158))
- Fix custom themes not resetting on Markdown renderer ([#46200](https://github.com/laravel/framework/pull/46200))

### Changed

- Use secure randomness in Arr:random and Arr:shuffle ([#46105](https://github.com/laravel/framework/pull/46105))
- Use mixed return type on controller stubs ([#46166](https://github.com/laravel/framework/pull/46166))
- Use InteractsWithDictionary in Eloquent collection ([#46196](https://github.com/laravel/framework/pull/46196))

## [v10.0.3 (2023-02-17)](https://github.com/laravel/framework/compare/v10.0.2...v10.0.3)

### Added

- Added missing expression support for pluck in Builder ([#46146](https://github.com/laravel/framework/pull/46146))

## [v10.0.2 (2023-02-16)](https://github.com/laravel/framework/compare/v10.0.1...v10.0.2)

### Added

- Register policies automatically to the gate ([#46132](https://github.com/laravel/framework/pull/46132))

## [v10.0.1 (2023-02-16)](https://github.com/laravel/framework/compare/v10.0.0...v10.0.1)

### Added

- Standard Input can be applied to PendingProcess ([#46119](https://github.com/laravel/framework/pull/46119))

### Fixed

- Fix Expression string casting ([#46137](https://github.com/laravel/framework/pull/46137))

### Changed

- Add AddQueuedCookiesToResponse to middlewarePriority so it is handled in the right place ([#46130](https://github.com/laravel/framework/pull/46130))
- Show queue connection in MonitorCommand ([#46122](https://github.com/laravel/framework/pull/46122))

## [v10.0.0 (2023-02-14)](https://github.com/laravel/framework/compare/v10.0.0...10.x)

Please consult the [upgrade guide](https://laravel.com/docs/10.x/upgrade) and [release notes](https://laravel.com/docs/10.x/releases) in the official Laravel documentation.
