# Release Notes for 13.x

## [Unreleased](https://github.com/laravel/framework/compare/v13.9.0...13.x)

## [v13.9.0](https://github.com/laravel/framework/compare/v13.8.0...v13.9.0) - 2026-05-13

* [13.x] Fix issue using custom aws credential providers by [@iWader](https://github.com/iWader) in https://github.com/laravel/framework/pull/60000
* [13.x] Revert "Correct Factory::configure [@return](https://github.com/return) to $this (#59963)" by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/60004
* [13.x] Replace `mb_split` with `preg_split` by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/60012
* [13.x] Keep calls to implode() consistent by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/60013
* [13.x] update `rand()` to `mt_rand()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/60018
* [13.x] remove `mt_srand()` deprecated "mode" argument by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/60020
* [13.x] Remove useless `fail-fast` option by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/60019
* [13.x] Prefer spaceship operator when possible by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/60015
* [13.x] Fix incorrectly opened DocBlocks by [@CasEbb](https://github.com/CasEbb) in https://github.com/laravel/framework/pull/60014
* [13.x] Ensure that the named arguments are sorted during a call by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/60017
* [13.x] Rely on guzzlehttp/psr7 for nested multipart array expansion - Fix #59992 by [@RomainMazB](https://github.com/RomainMazB) in https://github.com/laravel/framework/pull/59984
* [13.x] Add PreparesForDispatch interface for Jobs by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59879
* Add support to scoped filesystem for Cloud by [@jeremynikolic](https://github.com/jeremynikolic) in https://github.com/laravel/framework/pull/60030
* [13.x] Unused `$parameters` in `validate*case()` by [@weshooper](https://github.com/weshooper) in https://github.com/laravel/framework/pull/60024
* [13.x] Narrow attachment url scheme by [@benbjurstrom](https://github.com/benbjurstrom) in https://github.com/laravel/framework/pull/60034
* [13.x] Skip allocation in mergeFillable/Appends/Hidden/Visible when input is empty by [@olivier-zenchef](https://github.com/olivier-zenchef) in https://github.com/laravel/framework/pull/60008
* add generic return types to `Builder` paginate methods by [@levikl](https://github.com/levikl) in https://github.com/laravel/framework/pull/60045
* [13.x] Make PendingDispatch conditionable by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/60047
* [13.x] Display error in `queue:pause` when `Worker` isn't pausable by [@weshooper](https://github.com/weshooper) in https://github.com/laravel/framework/pull/60023
* [13.x] Add tests for `Attachment::fromUrl()` URL scheme validation by [@mdalikadar](https://github.com/mdalikadar) in https://github.com/laravel/framework/pull/60054
* [13.x] Fix [@params](https://github.com/params) typo in toPrettyJson docblocks by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/60050
* [13.x] re-add docblock for `apply()` method by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/60055
* [13.x] Add unicode modifier to preg_split by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/60056
* [13.x] Add name to MigrationStarted/MigrationEnded events by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/60059
* [13.x] Ability to override the Worker timeout exit code by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/60072
* [13.x] Add method to convert a Password instance to a passwordrules string by [@imliam](https://github.com/imliam) in https://github.com/laravel/framework/pull/60070
* add index for database performance by [@DGarbs51](https://github.com/DGarbs51) in https://github.com/laravel/framework/pull/60073
* [13.x] Add optional disk storage for large SQS queue payloads by [@Orrison](https://github.com/Orrison) in https://github.com/laravel/framework/pull/59734
* [13.x] Cloud queue metrics by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/60074
* [13.x] reset Lottery on test case teardown by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/60083
* [13.x] Add support for `after_commit` for Cloud queue metrics by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/60078
* [13.x] Remove Composer `github-oauth` credentials on Linux & Windows Actions by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/60095
* Revert "[13.x] Remove Composer `github-oauth` credentials on Linux & Windows Actions" by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/60100
* [13.x] Support config caching with Cloud queues by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/60094
* [13.x] Support Concurrency Run Timeouts by [@dbpolito](https://github.com/dbpolito) in https://github.com/laravel/framework/pull/60105
* [13.x] Allow passing a Closure to `ThrottlesExceptions` middleware by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/60103
* [13.x] Add enum support to contextual attribute binding by [@Tresor-Kasenda](https://github.com/Tresor-Kasenda) in https://github.com/laravel/framework/pull/60092
* [13.x] Add foreignUuidFor schema helper by [@Tresor-Kasenda](https://github.com/Tresor-Kasenda) in https://github.com/laravel/framework/pull/60091
* [13.x] Add unicode modifier to SeeInHtml normalize whitespace regex by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/60090
* [13.x] Replace [@return](https://github.com/return) with [@var](https://github.com/var) on property docblocks by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/60087
* Fix grammatical error in Lottery::alwaysLose() PHPDoc comment by [@rpsohag](https://github.com/rpsohag) in https://github.com/laravel/framework/pull/60086
* Fix typo in Sleep::microsecond() PHPDoc comment by [@rpsohag](https://github.com/rpsohag) in https://github.com/laravel/framework/pull/60085

## [v13.8.0](https://github.com/laravel/framework/compare/v13.7.0...v13.8.0) - 2026-05-05

* [12.x] `schedule:list` display expression in the correct timezone by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/59307
* [12.x] Fix validation wildcard array message type error by [@sadique-cws](https://github.com/sadique-cws) in https://github.com/laravel/framework/pull/59339
* Preserve class type of mocked classes by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/59353
* Preserve types on partialMock() and spy() by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/59384
* Fix missing UnitEnum support in ModelNotFoundException by [@jtheuerkauf](https://github.com/jtheuerkauf) in https://github.com/laravel/framework/pull/59423
* [12.x] Fix macros with static closures by [@FeBe95](https://github.com/FeBe95) in https://github.com/laravel/framework/pull/59449
* Correct Storage::fake() return type by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/59469
* [12.x] Fix callable type for freezeTime, freezeSecond, and travelTo by [@nbayramberdiyev](https://github.com/nbayramberdiyev) in https://github.com/laravel/framework/pull/59466
* [12.x] Support string abstract in mock/partialMock/spy PHPDoc by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/59477
* Document thrown exceptions in FilesystemAdapter by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/59534
* Hint \Redis `@mixin` on Connection by [@AJenbo](https://github.com/AJenbo) in https://github.com/laravel/framework/pull/59532
* [12.x] Use PDO subclass polyfill by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59640
* [12.x] Fix infinite rate limiter TTL on custom increments by [@paulandroshchuk](https://github.com/paulandroshchuk) in https://github.com/laravel/framework/pull/59693
* [12.x] Support named credential providers for SQS queue connections by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59754
* [12.x] Prevent array to string conversion in signature validation by [@alies-dev](https://github.com/alies-dev) in https://github.com/laravel/framework/pull/59778
* [12.x] Memoize credentials in SqsConnector by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59867
* [12.x] Disable pausing on managed queue workers by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59871
* [DRAFT] Verify merging `12.x` branch by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59929
* [13.x] Exclude expired locks in DatabaseLock::isLock by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/59948
* [13.x] Merge attribute-provided middleware with existing middleware by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59944
* [13.x] Tighten getCurrentSchemaListing [@return](https://github.com/return) in MySQL and SQLite builders by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59942
* [13.x] Correct Repository::setStore [@return](https://github.com/return) to $this by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59940
* [13.x] Correct Limit::none() [@return](https://github.com/return) type to Unlimited by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59938
* [13.x] Add collation to processColumns and getColumns return shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59937
* [13.x] Mark processViews schema field nullable in [@return](https://github.com/return) shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59941
* Improve docblock wording in AurthorizationException by [@Talha-74](https://github.com/Talha-74) in https://github.com/laravel/framework/pull/59930
* [13.x] Add Worker Pausing/Resuming events by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59895
* [13.x] Allow PHPStan to infer the pivot type when passing the pivot model directly by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59959
* [13.x] Document missing $health param on ApplicationBuilder::withRouting by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59968
* [13.x] Correct Log\Context\Repository::handleUnserializeExceptionsUsing [@return](https://github.com/return) to $this by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59965
* [13.x] Correct Attribute caching toggles [@return](https://github.com/return) to $this by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59962
* [13.x] Correct Factory::configure [@return](https://github.com/return) to $this by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59963
* [13.x] Correct Translator::handleMissingKeysUsing [@return](https://github.com/return) to $this by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59964
* [13.x] Correct Password::min [@return](https://github.com/return) to static by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59967
* [13.x] Mark processIndexes type field nullable in [@return](https://github.com/return) shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59961
* Add `assertSessionMissingInput` by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/59970
* [13.x] Mark generation type field nullable in processColumns [@return](https://github.com/return) shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59960
* [13.x] Allow custom on delete/update by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/59986
* Allow mail default driver to accept enums by [@Tresor-Kasenda](https://github.com/Tresor-Kasenda) in https://github.com/laravel/framework/pull/59973
* [13.x] LocalScope private recursion  by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59979
* [13.x] Add an environment filter to the `schedule:list` command by [@m-fi](https://github.com/m-fi) in https://github.com/laravel/framework/pull/59993
* [13.x] Add generic result type to collection min/max methods by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59991
* [13.x] Drop 12.x release notes and update heading by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59987
* [13.x] Add enum support to QueueFake assertPushedOn method by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/59990
* [13.x] Improvements to collection sort docblocks by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59988
* [13.x] Add all* queue inspection methods by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59997
* [13.x] Add support for `SortDirection` enum to query builder classes by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59865

## [v13.7.0](https://github.com/laravel/framework/compare/v13.6.0...v13.7.0) - 2026-04-28

* [13.x] Apply rector fixes by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59787
* Support enum in LazyCollection -> keyBy() by [@Back1ng](https://github.com/Back1ng) in https://github.com/laravel/framework/pull/59809
* [13.x] Add enum support to ConcurrencyManager driver method by [@maherelgamil](https://github.com/maherelgamil) in https://github.com/laravel/framework/pull/59801
* [13.x] Allow arrays for assertSoftDeleted & assertNotSoftDeleted by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59796
* [13.x] Extract exception context in `JsonFormatter` when `ExceptionHandler` is not bound by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59799
* [13.x] Add isLocked to the Lock class by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59791
* Fix route registration for domain-scoped routes by [@Bottelet](https://github.com/Bottelet) in https://github.com/laravel/framework/pull/59793
* [13.x] Mark `Scope@apply` builder parameter as having covariant template by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59790
* [13.x] Allowing `DebounceFor` attribute to be inherited by [@TWithers](https://github.com/TWithers) in https://github.com/laravel/framework/pull/59795
* [13.x] Fix PendingDispatch resolving Cache for every dispatched job by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59821
* [13.x] Add bulk JSON path assertions to TestResponse by [@cyrodjohn](https://github.com/cyrodjohn) in https://github.com/laravel/framework/pull/59829
* [13.x] Fix false positives in LazyCollection::has() for duplicate keys by [@Button99](https://github.com/Button99) in https://github.com/laravel/framework/pull/59832
* [13.x] Add UnitEnum type support for $limiterName on RateLimitedWithRedis by [@trippo](https://github.com/trippo) in https://github.com/laravel/framework/pull/59841
* [13.x] Allow jobs to react to worker signals by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59833
* [13.x] Honor empty JSON:API sparse fieldsets by [@prateekbhujel](https://github.com/prateekbhujel) in https://github.com/laravel/framework/pull/59813
* [13.x] Fix flaky DynamoBatchTest timing assertions by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59844
* [13.x] Memoize credentials in SqsConnector by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59866
* [13.x] Disable pausing on managed queue workers by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59870
* [13.x] Fix getMigrationBatches return type annotation by [@mahfuz-rahman007](https://github.com/mahfuz-rahman007) in https://github.com/laravel/framework/pull/59876
* [13.x] Fix PHPDoc typo in MigrationRepositoryInterface by [@mahfuz-rahman007](https://github.com/mahfuz-rahman007) in https://github.com/laravel/framework/pull/59875
* [13.x] Add UnitEnum support to Cache Repository touch method by [@shane-zeng](https://github.com/shane-zeng) in https://github.com/laravel/framework/pull/59864
* [13.x] Prevent array query params from bypassing signed URL validation by [@maherelgamil](https://github.com/maherelgamil) in https://github.com/laravel/framework/pull/59860
* [13.x] Add enum support to setDefaultDriver in QueueManager, LogManager, and SessionManager by [@maherelgamil](https://github.com/maherelgamil) in https://github.com/laravel/framework/pull/59861
* [13.x] Add enum support to RedisManager purge method by [@genius-asif-hub](https://github.com/genius-asif-hub) in https://github.com/laravel/framework/pull/59857
* [13.x] Fix factory hasAttached method pivot JSON attribute handling by [@rmd974](https://github.com/rmd974) in https://github.com/laravel/framework/pull/59856
* [13.x] Implement CanFlushLocks on NullStore and MemoizedStore by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59850
* [13.x] Introduce WorkerInterrupted event by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59848
* [13.x] Fix MigrationRepositoryInterface return type docblocks (object vs array) by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59887
* int argument for Collection::sortBy() by [@lorenzolosa](https://github.com/lorenzolosa) in https://github.com/laravel/framework/pull/59894
* [13.x] Add detailed [@return](https://github.com/return) shape to Schema\Builder::getForeignKeys by [@maherelgamil](https://github.com/maherelgamil) in https://github.com/laravel/framework/pull/59903
* [13.x] Fix EloquentModelDecimalCastingTest assertion across brick/math versions by [@maherelgamil](https://github.com/maherelgamil) in https://github.com/laravel/framework/pull/59904
* [13.x] Correct Lock getCurrentOwner [@return](https://github.com/return) type to string|null by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59890
* [13.x] Correct Batch fresh and add [@return](https://github.com/return) to self|null by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59891
* [13.x] Align Mailable::cc [@return](https://github.com/return) with sibling fluent methods by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59892
* [13.x] Add support for `SortDirection` enum to collections and Arr by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59859
* Add [@fonts](https://github.com/fonts) Blade directive and Vite font optimization runtime by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/59584
* [13.x] Refactor: add `match` by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/59914
* [13.x] Refactor: remove unnecessary call function by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/59915
* [13.x] Refactor: improve tests by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/59912
* Align Enumerable all, times and range [@return](https://github.com/return) with implementations by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59911
* Align Enumerable search and flatten [@return](https://github.com/return) with implementations by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59910
* Specify Translation Loader namespaces shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59913
* Fix duplicate type key in getTypes/processTypes return shape by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59909
* int argument for sortByDesc and Enumerable sort methods by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59907
* Match processForeignKeys return shape to Builder::getForeignKeys by [@mosabbirrakib](https://github.com/mosabbirrakib) in https://github.com/laravel/framework/pull/59908

## [v13.6.0](https://github.com/laravel/framework/compare/v13.5.0...v13.6.0) - 2026-04-21

* [13.x] Use `version_compare` function by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59687
* [13.x] Flip misordered assertions arguments by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59691
* [13.x] Remove unused variable in `catch()` by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59689
* [13.x] Fix number abbreviation rollover between unit tiers by [@Button99](https://github.com/Button99) in https://github.com/laravel/framework/pull/59692
* [13.x ]Use Null and Isset coalescing when possible by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59690
* [13.x] Change `count` array comparison to empty array comparison to improve performance by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59688
* [13.x] testsuite by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59702
* [13.x] Enforce static calls by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59704
* [13.x] Allow Table Attribute on child to override parent by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59701
* [13.x] Return null from Cursor::fromEncoded for malformed payloads by [@bipinks](https://github.com/bipinks) in https://github.com/laravel/framework/pull/59699
* [13.x] Port forward rate limiter fix by [@paulandroshchuk](https://github.com/paulandroshchuk) in https://github.com/laravel/framework/pull/59706
* [13.x] Add debounceable queued jobs by [@matthewnessworthy](https://github.com/matthewnessworthy) in https://github.com/laravel/framework/pull/59507
* [13.x] Support JSON responses for the built-in health route by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/59710
* [13.x] Ensure Queue::route string defaults to queue only by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59711
* [13.x] Fix failOnUnknownFields query parameter handling by [@cyrodjohn](https://github.com/cyrodjohn) in https://github.com/laravel/framework/pull/59728
* [13.x] Fix flaky QueueWorkerTest by freezing time before computing retryUntil by [@bipinks](https://github.com/bipinks) in https://github.com/laravel/framework/pull/59727
* [13.x]  Allow array of pivot arrays to be passed to hasAttached by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59723
* [13.x] Fix TypeError in digits_between validation rule on non-string values by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59717
* [13.x] Add enum support to PasswordBrokerManager by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59714
* [13.x] Add enum support to BroadcastManager by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59713
* Change attempts column type from tiny to small integer by [@ju-gow](https://github.com/ju-gow) in https://github.com/laravel/framework/pull/59718
* [13.x] Get rid of useless Mockery::close by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59730
* [13.x] Fix Vite CSS not loaded from nested chunk imports by [@karim1999](https://github.com/karim1999) in https://github.com/laravel/framework/pull/59662
* [13.x] Support named credential providers for SQS queue connections by [@kieranbrown](https://github.com/kieranbrown) in https://github.com/laravel/framework/pull/59733
* [13.x] Enforce stricter assertions by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59749
* [13.x] Cast to string before preg_match in decimal, max_digits, and min_digits rules by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59739
* [13.x] Ignore PHPUnit security advisory GHSA-qrr6-mg7r-m243 by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59761
* [13.x] Allow assertDatabase has & missing to accept arrays by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59752
* [13.x ] Normalize Carbon by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59750
* [13.x] Implement CanFlushLocks on FailoverStore by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59738
* [13.x] Validate MAC across all decryption keys by [@ma32kc](https://github.com/ma32kc) in https://github.com/laravel/framework/pull/59742
* [13.x] Use generic TModel in additional places in Factory class by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59780
* [13.x] Ensure assertModelMissing and assertModelExists dont silently pass by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59772
* [13.x] Introduce `JsonFormatter` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59756
* [13.x] Add prefersJsonResponses() to the application builder by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/59753
* [13.x] Add support for Cloudflare Email Service by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/59735
* [13.x] Add enum support to NotificationChannelManager channel and driver methods by [@yousefkadah](https://github.com/yousefkadah) in https://github.com/laravel/framework/pull/59783

## [v13.5.0](https://github.com/laravel/framework/compare/v13.4.0...v13.5.0) - 2026-04-14

* [13.x] Support #[Delay] attribute on queued mailables by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59580
* [13.x] Added inheritance support for Controller Middleware attributes. by [@niduranga](https://github.com/niduranga) in https://github.com/laravel/framework/pull/59597
* [13.x] Normalize phpredis SSL context for single and cluster connections   by [@timmylindh](https://github.com/timmylindh) in https://github.com/laravel/framework/pull/59569
* [13.x] Memoize the result of `TestCase@withoutBootingFramework()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59610
* [13.x] Add missing [@throws](https://github.com/throws) and docblocks for concurrency and model in… by [@scabarcas17](https://github.com/scabarcas17) in https://github.com/laravel/framework/pull/59602
* [13.x] Fix that retries of `ShouldBeUniqueUntilProcessing` jobs are force-releasing locks they don't own by [@kohlerdominik](https://github.com/kohlerdominik) in https://github.com/laravel/framework/pull/59567
* [13.x] Add first-class Redis Cluster support for Queue and ConcurrencyLimiter by [@timmylindh](https://github.com/timmylindh) in https://github.com/laravel/framework/pull/59533
* [13.x] chore: Update PHP version from 8.2 to 8.3 in `bin/test.sh` script by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59605
* [13.x] Fix RedisQueueTest by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59613
* [13.x] Add enum support to CacheManager store and driver methods by [@yousefkadah](https://github.com/yousefkadah) in https://github.com/laravel/framework/pull/59637
* [13.x] Fix redirectUsersTo() overwriting redirectGuestsTo() callback by [@timmylindh](https://github.com/timmylindh) in https://github.com/laravel/framework/pull/59633
* [13.x] Add ability to detect unserializable values returned from cache by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59630
* [13.x] Fix loose comparison false positive in NotPwnedVerifier with magic hash passwords by [@scabarcas17](https://github.com/scabarcas17) in https://github.com/laravel/framework/pull/59644
* [13.x] Refactor `Skip` middleware by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59651
* [13.x] Resolve stan errors on MySqlSchemaState by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59652
* [13.x] Allow closure values in updateOrCreate and firstOrNew by [@yousefkadah](https://github.com/yousefkadah) in https://github.com/laravel/framework/pull/59647
* [13.x] Add enum support to MailManager mailer and driver methods by [@yousefkadah](https://github.com/yousefkadah) in https://github.com/laravel/framework/pull/59645
* [13.x] Add enum support to AuthManager guard and shouldUse methods by [@yousefkadah](https://github.com/yousefkadah) in https://github.com/laravel/framework/pull/59646
* [13.x] Add spatie/fork to composer suggestions by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/59660
* [13.x] Add enum support to Manager driver method by [@scabarcas17](https://github.com/scabarcas17) in https://github.com/laravel/framework/pull/59659
* [13.x] Fix custom driver binding bug and improve  by [@ollieread](https://github.com/ollieread) in https://github.com/laravel/framework/pull/59614
* [13.x] Improve PHPDoc for "safe" method with conditional return type by [@leo95batista](https://github.com/leo95batista) in https://github.com/laravel/framework/pull/59684
* [13.x] Bump Retry action in CI by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59681
* [13.x] Move Scope interface [@template](https://github.com/template) from method-level to class-level to fix LSP violation by [@kayw-geek](https://github.com/kayw-geek) in https://github.com/laravel/framework/pull/59675
* [13.x] Combine consecutive `isset` and `unset` by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59685
* [13.x] Changes `strlen` comparison to 0 to direct empty string compare by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59686

## [v13.4.0](https://github.com/laravel/framework/compare/v13.3.0...v13.4.0) - 2026-04-07

* [13.x] Fix missing `Illuminate\Queue\Attributes\Delay` attribute by [@fadez](https://github.com/fadez) in https://github.com/laravel/framework/pull/59504
* [13.x] Fix `$request->interval()` failing with very small float values  by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59502
* [13.x] Add pint.json to export-ignore by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59497
* [13.x] Add --ignore-scripts to yarn in BroadcastingInstallCommand by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59494
* [13.x] Fix static closure binding in remaining manager classes by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59493
* [13.x] Fix CollectedBy attribute not resolving through abstract parent classes by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59488
* [13.x] Fix: Allow runtime property overrides (onQueue) to take precedence over class attributes by [@niduranga](https://github.com/niduranga) in https://github.com/laravel/framework/pull/59468
* [13.x] Use #[Delay] attribute in Bus Dispatcher by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59514
* [13.x] Use #[Delay] attribute in NotificationSender by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59513
* [13.x] Add `overflow` option to Carbon plus and minus by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59509
* [13.x] Fix: respect null redirect in unauthenticated exception handler by [@timmylindh](https://github.com/timmylindh) in https://github.com/laravel/framework/pull/59505
* [13.x] Fix TypeError in starts_with/ends_with validation rules on non-string values by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59541
* [13.x] allow null to be passed directly to redirectGuestsTo() by [@timmylindh](https://github.com/timmylindh) in https://github.com/laravel/framework/pull/59526
* Revert "[13.x] Remove unnecessary clone in SessionManager to prevent duplicate Redis connections" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/59542
* [13.x] Fix deprecation warning in Contains and DoesntContain rules when values contain null by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59561
* [13.x] Fix Str::markdown() and Str::inlineMarkdown() crash on null input by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59554
* [13.x] Add queue methods to inspect jobs by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59511
* Feature/form request strict mode by [@NurullahDemirel](https://github.com/NurullahDemirel) in https://github.com/laravel/framework/pull/59430
* Bump vite from 7.3.1 to 7.3.2 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot)[bot] in https://github.com/laravel/framework/pull/59571
* [13.x] Fix deprecation warning in In and NotIn rules when values contain null by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59576
* [13.x] Add flushState to FormRequest to reset global strict mode between tests by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59574
* [13.x] Fix `#[WithoutRelations]` queue attribute not being inherited by child classes by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/59568

## [v13.3.0](https://github.com/laravel/framework/compare/v13.2.0...v13.3.0) - 2026-04-01

* [13.x] Forward releaseOnTerminationSignals through schedule groups by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59357
* [13.x] Fix sub-minute scheduling skips at minute boundaries by [@JoshSalway](https://github.com/JoshSalway) in https://github.com/laravel/framework/pull/59331
* [13.x] Display memory usage in verbose queue worker output by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59379
* [13.x] Update WithoutOverlapping@shared() for clarity by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59375
* [13.x] Fix dependency injection of faked queueing dispatcher by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59378
* [13.x] Fix incrementEach/decrementEach to scope to model instance by [@JoshSalway](https://github.com/JoshSalway) in https://github.com/laravel/framework/pull/59376
* [13.x] Add array value types to Support module docblocks by [@Anthony14FR](https://github.com/Anthony14FR) in https://github.com/laravel/framework/pull/59383
* [13.x] Add lost connection to WorkerStopReason by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59370
* [13.x] MariaDbSchemaState uses mysql --version for client detection instead of mariadb --version by [@kylemilloy](https://github.com/kylemilloy) in https://github.com/laravel/framework/pull/59360
* [13.x] Add enum support to QueueManager connection methods by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59389
* [13.x] Setup rector by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59385
* [13.x] Improve `Arr::whereNotNull()` docs by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59411
* [13.x] Pass request to afterResponse callback by [@bilfeldt](https://github.com/bilfeldt) in https://github.com/laravel/framework/pull/59410
* [13.x] Add isNotEmpty() method to Uri class by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59408
* [13.x] Add missing capitalize parameter to Stringable::initials() by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59407
* [13.x] Fix trait initializer collision with Attribute parsing by [@sadique-cws](https://github.com/sadique-cws) in https://github.com/laravel/framework/pull/59404
* [13.x] Add session to supported drivers comment by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59399
* [13.x] Add `->file()` method to `$request->safe()` by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59396
* [13.x] Add enum support to LogManager channel and driver methods by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59391
* [13.x] Fix MorphTo eager load matching when ownerKey is null and result key is a non-primitive by [@wietsewarendorff](https://github.com/wietsewarendorff) in https://github.com/laravel/framework/pull/59394
* [13.x] Remove unnecessary clone in SessionManager to prevent duplicate Redis connections by [@JoshSalway](https://github.com/JoshSalway) in https://github.com/laravel/framework/pull/59323
* [13.x] Use FQCN for Str in exception renderer blade templates by [@bankorh](https://github.com/bankorh) in https://github.com/laravel/framework/pull/59412
* Allow variadic args for model attributes by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/59421
* [13.x] CollectedBy Attribute should follow inheritence by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59419
* [13.x] Fix deprecation notice in JSON:API resources by [@alihamze](https://github.com/alihamze) in https://github.com/laravel/framework/pull/59418
* [13.x] Add withoutFragment() method to Uri class by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59413
* [13.x] Fix macros with static closures by [@FeBe95](https://github.com/FeBe95) in https://github.com/laravel/framework/pull/59414
* [13.x] Fix sum() docblock to include key parameter in callback signature by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59444
* [13.x] Add assertHasNoAttachments() method to Mailable by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59443
* [13.x] Add a driver method to the MailFake class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/59448
* [13.x] Cache getLockForPopping() result in DatabaseQueue by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/59435
* [13.x] prefer `new Collection()` over `collect()` helper by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/59453
* [13.x] remove unnecessary `array_flip()` calls by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/59452
* Make Collection methods compatible with extended subclass constructors by [@ProjektGopher](https://github.com/ProjektGopher) in https://github.com/laravel/framework/pull/59455
* [13.x] `UnitTest` test attribute by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59432
* [13.x] prefer `isset()` over `in_array()` for better performance by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/59457
* [13.x] remove temporary variable by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/59456
* [13.x] Add BatchStarted event  by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59458
* [13.x] Preserve URI fragment when decoding query string by [@Nipun404](https://github.com/Nipun404) in https://github.com/laravel/framework/pull/59481
* fix: allow returning Stringable objects in casts()-method by [@Bloemendaal](https://github.com/Bloemendaal) in https://github.com/laravel/framework/pull/59479
* [13.x] Fix manager breaking when called with static closure by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59470
* Prevents installed package from executing malicious code via `postinstall` in `install:broadcasting` command by [@duncanmcclean](https://github.com/duncanmcclean) in https://github.com/laravel/framework/pull/59485

## [v13.2.0](https://github.com/laravel/framework/compare/v13.1.1...v13.2.0) - 2026-03-24

* feat(queue): support enums in `#[Queue]` and `#[Connection]` by [@innocenzi](https://github.com/innocenzi) in https://github.com/laravel/framework/pull/59278
* Improve raw SQL binding substitution performance by [@gufoe](https://github.com/gufoe) in https://github.com/laravel/framework/pull/59277
* [13.x] fix: add missing negate for SeeInHtml assertion by [@jesperbeisner](https://github.com/jesperbeisner) in https://github.com/laravel/framework/pull/59303
* [13.x] Allow for passing enums to attributes by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/59297
* [13.x] Add releaseOnSignal param to withoutOverlapping by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59298
* Add symmetrical, expressive attributes by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/59284
* [13.x] Fix LazyPromise::wait() signature compatibility with Guzzle's PromiseInterface by [@shavonn](https://github.com/shavonn) in https://github.com/laravel/framework/pull/59301
* [13.x] `schedule:list` display expression in the correct timezone by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/59286
* Handle exceptions in eventStream to prevent fatal error by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/59292
* [13.x] Ensure connectUsing works with UnitEnum / FileManager drive docblock by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59306
* [13.x] Include columns and index in UniqueConstraintViolationException by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59299
* [13.x] Add TimedOut worker stop reason by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59310
* Fix Table attribute incrementing not working for Pivot models by [@sadique-cws](https://github.com/sadique-cws) in https://github.com/laravel/framework/pull/59336
* [13.x] Ensure ScopedBy Attribute works with inheritance by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59332
* Modify sum callback to include item key by [@mathieutu](https://github.com/mathieutu) in https://github.com/laravel/framework/pull/59322
* [13.x] Bound error page query listener to prevent memory bloat in Octane by [@JoshSalway](https://github.com/JoshSalway) in https://github.com/laravel/framework/pull/59309
* [13.x] Allow opting out of worker Job exception reporting by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59308
* [13.x] Adds mobile safe-area-inset support to exception renderer by [@dr-codswallop](https://github.com/dr-codswallop) in https://github.com/laravel/framework/pull/59341
* [13.x] Allow passing multiple arrays to has factory method by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59343
* [13.x] Allow Backoff Attribute to be variadic by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59354

## [v13.1.1](https://github.com/laravel/framework/compare/v13.1.0...v13.1.1) - 2026-03-18

* Break queue dependency by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/59275

## [v13.1.0](https://github.com/laravel/framework/compare/v13.0.0...v13.1.0) - 2026-03-18

* [12.x] Correct truncate exceptions at by [@bretto36](https://github.com/bretto36) in https://github.com/laravel/framework/pull/59239
* [13.x] Remove useless \Mockery::close() by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59253
* Fix: Batch::add() wipes queue assignment on first job in array chains by [@ProjektGopher](https://github.com/ProjektGopher) in https://github.com/laravel/framework/pull/59233
* Improvements for asserting HTML in text by [@jasonmccreary](https://github.com/jasonmccreary) in https://github.com/laravel/framework/pull/59161
* [13.x] Use Carbon::now() instead of now() helper by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/59252
* [13.x] Fix null broadcaster deprecation warning in PHP 8.5 by [@mortenscheel](https://github.com/mortenscheel) in https://github.com/laravel/framework/pull/59269
* [12.x] Fix float pluralization in trans_choice() by [@JulianGlueck](https://github.com/JulianGlueck) in https://github.com/laravel/framework/pull/59268
* [12.x] Fix tests on PHP 8.5 by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59251
* [13.x] Add toString to Uri by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/59259

## [v13.0.0](https://github.com/laravel/framework/compare/v12.54.1...v13.0.0) - 2026-03-17

* Revert "[12.x] Query builder PDO fetch modes" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/54709
* [13.x] Prepare branch alias for Laravel 13 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54701
* [12.x] Query builder PDO fetch modes + columns fix by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/54734
* [13.x] Fix Tests/CI environments by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54760
* [13.x] Requires PHP 8.3 as minimum version by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54763
* [13.x] Add missing parameters to `Response` methods `throw()` and `throwIf()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54798
* [13.x] Fix scope removal in nested where conditions by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/54816
* [13.x] Remove function existence checks by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/54876
* [13.x] Removed unneeded default argument by [@AndrewMast](https://github.com/AndrewMast) in https://github.com/laravel/framework/pull/54900
* [13.x] Fix unresolved merge conflict in Concurrency composer.json by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/55233
* [13.x] Fixes merge conflict by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55294
* [13.x] Error exit code for clear command by [@mbardelmeijer](https://github.com/mbardelmeijer) in https://github.com/laravel/framework/pull/55355
* [13.x] Add #[\Override] to the BatchFake class methods by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55358
* [13.x] PDO Fetch modes by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/55394
* Allow Listeners to dynamically specify deleteWhenMissingModels by [@L3o-pold](https://github.com/L3o-pold) in https://github.com/laravel/framework/pull/55508
* [13.x] Do not allow new model instances to be created during boot by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/55685
* Fix typo in `Blueprint`: `datetime` => `dateTime` by [@TheJoeSchr](https://github.com/TheJoeSchr) in https://github.com/laravel/framework/pull/55859
* Feature: add support straight join in mysql by [@jferdi24](https://github.com/jferdi24) in https://github.com/laravel/framework/pull/55786
* [13.x] Register subdomain routes before routes that are not linked to a domain by [@gdebrauwer](https://github.com/gdebrauwer) in https://github.com/laravel/framework/pull/55921
* [13.x] Supports Symfony 7.4 & 8.0 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56029
* [13.x] Change to hyphenate prefixes by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/framework/pull/56172
* [13.x] Use exception object in JobAttempted event by [@bert-w](https://github.com/bert-w) in https://github.com/laravel/framework/pull/56148
* [13.x] remove superfluous element by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56303
* Add eventStream signature to ResponseFactory contract by [@csfh](https://github.com/csfh) in https://github.com/laravel/framework/pull/56306
* fix: align ResponseFactory::eventStream signature with interface by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/56484
* [13.x] `Cache::touch()` & `Store::touch()` for TTL Extension by [@yitzwillroth](https://github.com/yitzwillroth) in https://github.com/laravel/framework/pull/55954
* [13.x] Make QueueBusy event consistent with other queue events by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56673
* [13.x] use clearer pagination view names by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56307
* [13.x] Update `countBy` docblock in `Enumerable` interface to allow for enum callback by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56897
* [13.x] Generate plural morph pivot table name by [@willrowe](https://github.com/willrowe) in https://github.com/laravel/framework/pull/56832
* [13.x] Resolve Symfony Console `add()` method deprecation by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/56488
* [13.x] Add command method to contract by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/56978
* Refactor: replace strpos check with str_contains for clarity by [@arshidkv12](https://github.com/arshidkv12) in https://github.com/laravel/framework/pull/57042
* Remove unnecessary parameters by [@arshidkv12](https://github.com/arshidkv12) in https://github.com/laravel/framework/pull/57047
* [README.md] change laravel bootcamp to laravel learn by [@MoZayedSaeid](https://github.com/MoZayedSaeid) in https://github.com/laravel/framework/pull/57176
* [13.x] Resolve issues with tests by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/57258
* [13.x] Bind manager instances to custom driver closures by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57173
* [13.x] Compile full DELETE with JOIN including ORDER BY and LIMIT in MySQL grammar by [@tegos](https://github.com/tegos) in https://github.com/laravel/framework/pull/57196
* [13.x] Flush `Str` factories when tearing down test case by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57296
* [13.x] Update reset password notification subject by [@ganyicz](https://github.com/ganyicz) in https://github.com/laravel/framework/pull/57882
* [13.x] Update verification email subject capitalization by [@ganyicz](https://github.com/ganyicz) in https://github.com/laravel/framework/pull/57884
* [13.x] Simplify preg_replace_array callback by removing unnecessary foreach loop by [@sumaiazaman](https://github.com/sumaiazaman) in https://github.com/laravel/framework/pull/57924
* [13.x] Fix changes from Laravel 12 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/57919
* [13.x] Default `PendingRequest@pool()` to use 2 for concurrency by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57972
* [13.x] Copy `Symfony\Component\HttpFoundation\Request::get()` functionality to avoid breaking changes. by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58081
* [13.x] Defer registering schedule registered using  `ApplicationBuilder::withScheduling()` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58160
* [13.x] Return data object from `ModelInspector` to make `show:model` more flexible by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/58230
* [13.x] Add ability to default queue by class type by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58094
* [13.x] Add reason to WorkerStopping event by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58341
* [13.x] Add starting to Monitor Contract by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58399
* [13.x] add dispatchAfterResponse to the Dispatcher Contract by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58428
* [13.x] Add origin verification to request forgery protection by [@benbjurstrom](https://github.com/benbjurstrom) in https://github.com/laravel/framework/pull/58400
* [13.x] Improve `Enumerable` interface docblock types by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/58181
* Add missing chain method to interface by [@Jeroen-G](https://github.com/Jeroen-G) in https://github.com/laravel/framework/pull/58429
* [13.x] Use unescaped unicode in `Js` support class by default by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/58471
* [13.x] Add enum types to repository contract / allow enums for tagged caches by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58481
* [13.x] Restore eager-loaded relations when deserializing collections by [@dxnter](https://github.com/dxnter) in https://github.com/laravel/framework/pull/58477
* [13.x] Bump minimum PHPUnit by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58537
* [13.x] Respect default value for class dependencies in BoundMethod::call by [@comhon-project](https://github.com/comhon-project) in https://github.com/laravel/framework/pull/58553
* [13.x] Bump minimum `symfony/process` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58548
* [13.x] Fix `illuminate/json-schema` dependencies by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58612
* [13.x] Add `hasSole` and `hasMany` to the `Enumerable` interface by [@JosephSilber](https://github.com/JosephSilber) in https://github.com/laravel/framework/pull/58610
* [13.x] Ensure bootstrap withMiddleware works for the DownCommand by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58571
* [13.x] Remove override attribute on removed method by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58628
* [13.x] Ensures compatibility with `symfony/console` 8 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58629
* [13.x] Add `cc` to Mailer contract by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58667
* Fix ThrottleRequests over-throttling with multiple distinct rate limit keys (#54386) by [@HeathNaylor](https://github.com/HeathNaylor) in https://github.com/laravel/framework/pull/58707
* [13.x] Add `markEmailAsUnverified` to `MustVerifyEmail` interface by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/58701
* [13.x] Adds previous exceptions in exception view by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/58680
* Attributes by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/58578
* [13.x] Accept CarbonInterval for PendingProcess timeouts by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/58842
* [13.x] Add Setup/TearDown trait attributes by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58685
* [13.x] Allow aliases to be set in Signature Attribute (#58853) by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58874
* [13.x] Adds PHPUnit 13 support by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/58890
* [13.x] Display route binding fields in `route:list` output by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58889
* [13.x] feat: respect `DeleteWhenMissingModels` attribute on queued notifications by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/58908
* [13.x] Resolve DeleteNotificationWhenMissingModelTest  by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58919
* [13.x] add missing methods to Queue interface by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/58914
* [13.x] chore: define closure type on Middleware by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/58929
* [13.x] Ensure SyncQueue JobAttempted gets the actual exception by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58954
* [13.x] Throw exception when served disks share the same URI by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58960
* [13.x] withoutOverlapping docblock by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/58973
* [13.x] Fix `composer.json` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/58975
* [13.x] Update the dependencies version by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/58995
* [13.x] Normalize composer.json by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/58996
* [13.x] Add `flushLocks()` support to Cache stores by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/58907
* [13.x] Add cache `flushLocks()` events by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/59006
* [13.x] Refactor parameter names that are implemented from the interface by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/59015
* [13.x] Add missing [@throws](https://github.com/throws) into docblock for various methods by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/59016
* Add insertOrIgnoreReturning method by [@antonkomarev](https://github.com/antonkomarev) in https://github.com/laravel/framework/pull/59025
* Add corner case tests for insertOrIgnoreReturning by [@antonkomarev](https://github.com/antonkomarev) in https://github.com/laravel/framework/pull/59028
* Extra validation on query builder upsert by [@antonkomarev](https://github.com/antonkomarev) in https://github.com/laravel/framework/pull/59029
* [13.x] Add ErrorBag attribute support for FormRequest by [@Tresor-Kasenda](https://github.com/Tresor-Kasenda) in https://github.com/laravel/framework/pull/59033
* [13.x] Add controller middleware attribute by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/59030
* [13.x] Add Authorize controller middleware attribute by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/59048
* [13.x] Fix `symfony/translation` deps by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59054
* [13.x] Remove supports for `laravel/serializable-closure` v1 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59053
* Add saveOrIgnore Eloquent Model method for conflict-safe inserts by [@antonkomarev](https://github.com/antonkomarev) in https://github.com/laravel/framework/pull/59026
* [13.x] Add support for named arguments in event dispatching and broadcasting by [@ph7jack](https://github.com/ph7jack) in https://github.com/laravel/framework/pull/59075
* [13.x] Supports `pda/pheanstalk` 8.0+ and remove 5.x by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59072
* [13.x] Bump dependencies by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/59069
* [13.x] Add ability to set channel name via Log contextual attribute by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59101
* [13.x] Ensure insertOrIgnoreReturning only marks records as modified when rows are inserted by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59083
* [13.x] Clean up ModelInfo by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59080
* [13.x] Clean up JsonApi by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59079
* [13.x] Indicate that raw queries should be literal strings by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/59081
* [13.x] Update brick/math constraint and rounding mode constant by [@balu-lt](https://github.com/balu-lt) in https://github.com/laravel/framework/pull/59107
* [13.x] fix: MorphToMany morphClass type by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/59110
* [13.x] Rename Middleware attribute parameter from $value to $middleware  by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59133
* [13.x] fix: QueueRoutes docblocks for getRoute and $routes property  by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59119
* [13.x] fix: DoesntContain docblock typo by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59117
* [13.x] BusFake assertNothingDispatched should check all dispatches by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59118
* [13.x] fix: Align JsonApiResource flushState maxRelationshipDepth with trait default  by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59116
* [13.x] Make Cache touch() TTL required and remove redundant value fetching  by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59121
* [13.x] Fix previousPath() for external referrers by [@faytekin](https://github.com/faytekin) in https://github.com/laravel/framework/pull/59159
* Add depth parameter to Arr::dot() by [@faytekin](https://github.com/faytekin) in https://github.com/laravel/framework/pull/59150
* [13.x] Drop method_exists checks in MonitorCommand   by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59151
* [12.x] Add strict integer validation to Numeric validation rule by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/59156
* [12.x] Add *OrFail transaction methods to `BelongsToMany` by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59153
* [13.x] Add Exception to BatchCanceled event by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59163
* [13.x] Add support for `brick/math` 0.16 by [@balu-lt](https://github.com/balu-lt) in https://github.com/laravel/framework/pull/59165
* Bump tar from 7.5.9 to 7.5.11 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot)[bot] in https://github.com/laravel/framework/pull/59164
* [12.x] Add missing *OrFail transaction methods to BelongsToMany by [@erhanurgun](https://github.com/erhanurgun) in https://github.com/laravel/framework/pull/59168
* [12.x] Add inOrderOf() method to query builder by [@faytekin](https://github.com/faytekin) in https://github.com/laravel/framework/pull/59162
* [12.x] Add tcp_keepalive option to PhpRedis connector by [@heikokrebs](https://github.com/heikokrebs) in https://github.com/laravel/framework/pull/59158
* [13.x] Add schedule:pause / resume command by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59169
* [12.x] un`tap` PendingRequest by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/59188
* [12.x] Fix float to int deprecation in trans_choice() for certain locales by [@hamedelasma](https://github.com/hamedelasma) in https://github.com/laravel/framework/pull/59174
* [12.x] Allow `touch()` to accept multiple columns by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/59175
* Revert "Add composite index to jobs table migration for improved queue polling" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/59202
* [12.x] Add fluent string validation rule builder by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59201
* [13.x] Add schedule resume and pause events by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59172
* [13.x] insertOrIgnoreReturning with multiple unique keys by [@tpetry](https://github.com/tpetry) in https://github.com/laravel/framework/pull/59187
* Update `Command::withProgressBar` phpdoc to account for arrow functions and non-void return types by [@billypoke](https://github.com/billypoke) in https://github.com/laravel/framework/pull/58766
* [12.x] Lazily evaluate value for constraints in `HasOneOrManyThrough` by [@Jacobs63](https://github.com/Jacobs63) in https://github.com/laravel/framework/pull/59231
* Add string helper to get initials from a string by [@denjaland](https://github.com/denjaland) in https://github.com/laravel/framework/pull/59230
* fix:  Strip gzip-compressed output from concurrent process response by [@NikhiltGhalme](https://github.com/NikhiltGhalme) in https://github.com/laravel/framework/pull/59224
* [12.x] Fix failing tests introduced by #59201 by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59207
* [12.x] Avoid redundant `Util::getParameterClassName()` call in container resolution by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59220
* [12.x] Add missing conditional validation rule builders by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59209
* [12.x] Skip placeholder replacements when message does not contain them by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59211
* [12.x] Use `array_push` with spread operator in `MessageBag::all()` by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59217
* [12.x] Cache Route instances in CompiledRouteCollection::getByName() by [@SanderMuller](https://github.com/SanderMuller) in https://github.com/laravel/framework/pull/59221
* [13.x] Add additional Artisan attributes for usage, help and hidden by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/59204
* [12.x] Accept CarbonInterval for retry sleep duration by [@riesjart](https://github.com/riesjart) in https://github.com/laravel/framework/pull/59232
* [12.x] Fix failing phpstan by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/59245
* [12.x] Update comments for PlanetScale MySQL and PostgreSQL by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/59244
* [12.x] Use big integers for database cache expiration column by [@tanerkay](https://github.com/tanerkay) in https://github.com/laravel/framework/pull/59243
* [13.x] Allow brick/math ^0.17 by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59249
* [12.x] Display file path and line number for closure routes in `route:list` by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/59237
* [12.x] Add wantsMarkdown() and acceptsMarkdown() request methods by [@joetannenbaum](https://github.com/joetannenbaum) in https://github.com/laravel/framework/pull/59238
* [13.x] Ensure RequiredUnless handles null by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/59235
