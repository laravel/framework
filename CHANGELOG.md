# Release Notes for 10.x

## [Unreleased](https://github.com/laravel/framework/compare/v10.20.0...10.x)

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
- [10.x] Fix Http global middleware for queue, octane, and dependecy injection by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/47915

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

- Revert "[10.x] Fix inconsistentcy between report and render methods" ([#47326](https://github.com/laravel/framework/pull/47326))

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
- Fixed inconsistentcy between report and render methods ([#47201](https://github.com/laravel/framework/pull/47201))
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

- Revert "[10.x] Fix custom themes not reseting on Markdown renderer" ([#46328](https://github.com/laravel/framework/pull/46328))

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
- Fix custom themes not reseting on Markdown renderer ([#46200](https://github.com/laravel/framework/pull/46200))

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
