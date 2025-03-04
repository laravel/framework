# Release Notes for 12.x

## [Unreleased](https://github.com/laravel/framework/compare/v12.1.0...12.x)

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
