# Release Notes for 12.x

## [Unreleased](https://github.com/laravel/framework/compare/v12.33.0...12.x)

## [v12.33.0](https://github.com/laravel/framework/compare/v12.32.5...v12.33.0) - 2025-10-07

* Fix compiling queries that use orderByRaw with expressions by [@LukeTowers](https://github.com/LukeTowers) in https://github.com/laravel/framework/pull/57228
* [12.x] Narrow type after `Str::is*(...)` check by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/57230
* [12.x] Fix invalid docblock by [@tm1000](https://github.com/tm1000) in https://github.com/laravel/framework/pull/57240
* [12.x] Refactor switch to match by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/57236
* [12.x] Refactor switch to match by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/57237
* [12.x] Fix rounded issue in exception frame component by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57239
* [12.x] Ensure calling job within a group works as expected by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/57224
* fix: remove duplicated word in `Str::apa` method by [@balboacodes](https://github.com/balboacodes) in https://github.com/laravel/framework/pull/57254
* refactor: add |null in docblock by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/57253
* [12.x] Improve `php artisan config:cache` and `php artisan optimize` error messages for non-serializable values by [@mathiasgrimm](https://github.com/mathiasgrimm) in https://github.com/laravel/framework/pull/57249
* [12.x] Ensure cookie lifetime matches session lifetime in StartSession middleware by [@michaelcontento](https://github.com/michaelcontento) in https://github.com/laravel/framework/pull/57266
* Run tests on PostgreSQL version 18 by [@JurianArie](https://github.com/JurianArie) in https://github.com/laravel/framework/pull/57232
* [12x.] reduce repeated inserts in tests by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57273
* [12.x] Fix using pushIf blade directive with complex conditions (#57264) by [@hosni](https://github.com/hosni) in https://github.com/laravel/framework/pull/57274
* [12.x] Add Stringable::doesntContain() to match API symmetry by [@michaelcontento](https://github.com/michaelcontento) in https://github.com/laravel/framework/pull/57279
* [12.x] Improve BroadcastManager error messages when trying to get a Broadcaster by [@mathiasgrimm](https://github.com/mathiasgrimm) in https://github.com/laravel/framework/pull/57275
* [12.x] HTTP Client: add mergeUrlParameters() to combine URL parameters without overwriting by [@leek](https://github.com/leek) in https://github.com/laravel/framework/pull/57282

## [v12.32.5](https://github.com/laravel/framework/compare/v12.32.4...v12.32.5) - 2025-09-30

## [v12.32.4](https://github.com/laravel/framework/compare/v12.32.3...v12.32.4) - 2025-09-30

* [12.x] Use `Container::getInstance()` in `ComposerScripts::prePackageUninstall()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57226

## [v12.32.3](https://github.com/laravel/framework/compare/v12.32.2...v12.32.3) - 2025-09-30

* [12.x] Define LARAVEL_START if not already defined by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57222
* [12.x] Clean up redundant type hints in docblocks by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57219

## [v12.32.2](https://github.com/laravel/framework/compare/v12.32.1...v12.32.2) - 2025-09-30

## [v12.32.1](https://github.com/laravel/framework/compare/v12.32.0...v12.32.1) - 2025-09-30

* [13.x] Fix scopedBy attribute not following inheritance chain by [@Muffinman](https://github.com/Muffinman) in https://github.com/laravel/framework/pull/57213
* [12.x] Fix AWS S3 adapter's constructor not allowing decorated adapter instances by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57217

## [v12.32.0](https://github.com/laravel/framework/compare/v12.31.1...v12.32.0) - 2025-09-30

* [12.x] fix static analysis error  by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57162
* Fix: Handle non-string returns from Htmlable::toHtml() in e() helper by [@Carnicero90](https://github.com/Carnicero90) in https://github.com/laravel/framework/pull/57157
* [12.x] Fix pending attributes in schedule group by [@jamessa](https://github.com/jamessa) in https://github.com/laravel/framework/pull/57156
* Remove Request overview from Exceptions by [@barryvdh](https://github.com/barryvdh) in https://github.com/laravel/framework/pull/57158
* [12.x] Pass "throw" option from scoped to parent disk by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57163
* [12.x] Make docblock return type in line with actual return type by [@parijke](https://github.com/parijke) in https://github.com/laravel/framework/pull/57164
* [12.x] Adjust `Arr` typehints by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57165
* [12.x] Track filesystem adapter decoration by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57167
* [12.x] Batch Job Failure Callbacks Support by [@yitzwillroth](https://github.com/yitzwillroth) in https://github.com/laravel/framework/pull/55916
* [12.x] Fix operator precedence by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57169
* [12.x] Clean up after filesystem manager tests by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57168
* Fix: Improve validateInteger ergonomics and fix BC break by [@ntm-dev](https://github.com/ntm-dev) in https://github.com/laravel/framework/pull/57175
* [12.x] Fix nested `can` and inherit models on route groups by [@bonroyage](https://github.com/bonroyage) in https://github.com/laravel/framework/pull/57172
* [12.x] Syntax highlight on the frontend by [@avosalmon](https://github.com/avosalmon) in https://github.com/laravel/framework/pull/57184
* [12.x] Add missing Closure type to Collection::pluck() docblock  by [@Bariss61](https://github.com/Bariss61) in https://github.com/laravel/framework/pull/57178
* Add database afterRollback callback support and tests by [@maltekuhr](https://github.com/maltekuhr) in https://github.com/laravel/framework/pull/57180
* fix: add return type by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/57192
* [12.x] Adds support enums for `ThrottleRequests::using` method by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/57190
* [12.x] Introduce "after" rate limiting by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/57125
* [12.x] Json schema nullable by [@Katalam](https://github.com/Katalam) in https://github.com/laravel/framework/pull/57181
* [12.x] Dispatch framework events on composer `pre-package-uninstall` event by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57144
* [12.x] Add Http::batch by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/56946
* [12.x] [Mail] Update `queue` PHPDoc according to function behavior by [@MrYamous](https://github.com/MrYamous) in https://github.com/laravel/framework/pull/57207
* [12.x] Remove unnecessary parentheses by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57212
* [12.x] Remove unnecessary parentheses by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57210
* [12.x] Fixes error renderer report page by [@xiCO2k](https://github.com/xiCO2k) in https://github.com/laravel/framework/pull/57208
* [12.x] Extend SQS FIFO and fair queue support by [@patrickcarlohickman](https://github.com/patrickcarlohickman) in https://github.com/laravel/framework/pull/57187

## [v12.31.1](https://github.com/laravel/framework/compare/v12.31.0...v12.31.1) - 2025-09-23

* Revert "[12.x] Reintroduce short-hand "false" syntax for Blade component props" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/57151

## [v12.31.0](https://github.com/laravel/framework/compare/v12.30.1...v12.31.0) - 2025-09-23

* Bump vite from 7.1.2 to 7.1.6 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot)[bot] in https://github.com/laravel/framework/pull/57114
* [12.x] Reintroduce short-hand "false" syntax for Blade component props by [@PerryvanderMeer](https://github.com/PerryvanderMeer) in https://github.com/laravel/framework/pull/57104
* [12.x] Allow Number parse helpers to return false by [@platoindebugmode](https://github.com/platoindebugmode) in https://github.com/laravel/framework/pull/57127
* [12.x] Refactor `RedisTaggedCache@flush()` to allow for custom connections by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/57122
* [12.x] Use light-dark scheme for exception renderer by [@pxlrbt](https://github.com/pxlrbt) in https://github.com/laravel/framework/pull/57128
* [12.x] Replace logger helper and log function concrete return type ?LogManager with abstract ?LoggerInterface by [@abdelrahmenAyman](https://github.com/abdelrahmenAyman) in https://github.com/laravel/framework/pull/57028
* [12.x] Fix session value is missing assertion by [@barclaymichael](https://github.com/barclaymichael) in https://github.com/laravel/framework/pull/57134
* median() div swapped for intdiv() by [@artumi-richard](https://github.com/artumi-richard) in https://github.com/laravel/framework/pull/57148
* [12.x] Fix PHP 8.5 null-key deprecations by [@IonBazan](https://github.com/IonBazan) in https://github.com/laravel/framework/pull/57137

## [v12.30.1](https://github.com/laravel/framework/compare/v12.30.0...v12.30.1) - 2025-09-18

* [12.x] Fix: Apply intl extension check to ordinal position to prevent issues by [@BinaryKitten](https://github.com/BinaryKitten) in https://github.com/laravel/framework/pull/57112

## [v12.30.0](https://github.com/laravel/framework/compare/v12.29.0...v12.30.0) - 2025-09-18

* [12.x] Allow newer versions for phiki/phiki than 2.0.0 by [@hebbet](https://github.com/hebbet) in https://github.com/laravel/framework/pull/57075
* [12.x] Use null coalescing for memoryExceededExitCode by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/57090
* [12.x] Fix 'can' function that was defined in RouterRegistrar in #54648 by [@pdewit](https://github.com/pdewit) in https://github.com/laravel/framework/pull/57072
* [12.x] Fix SQS FIFO and fair queue support by [@patrickcarlohickman](https://github.com/patrickcarlohickman) in https://github.com/laravel/framework/pull/57080
* atomically flush redis cache tags by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/57098
* [12.x] Add type hints to `\Illuminate\Support\Str` by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/57096
* Doc: Update Database Connection getElapsedTime comment to specify unit by [@glensc](https://github.com/glensc) in https://github.com/laravel/framework/pull/57099
* [12.x] Add support for Ordinal Position in validation messages by [@BinaryKitten](https://github.com/BinaryKitten) in https://github.com/laravel/framework/pull/57109
* [12.x] Fix exception frame file path on Windows by [@avosalmon](https://github.com/avosalmon) in https://github.com/laravel/framework/pull/57103
* Add fallback to copy buttons on new exception page by [@joaokamun](https://github.com/joaokamun) in https://github.com/laravel/framework/pull/57092
* [12.x] Adds `Macroable` trait to `Illuminate/Support/Benchmark` by [@1tim22](https://github.com/1tim22) in https://github.com/laravel/framework/pull/57107

## [v12.29.0](https://github.com/laravel/framework/compare/v12.28.1...v12.29.0) - 2025-09-16

* Ensure cached and uncached routes share same precedence when resolving actions and names by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/56920
* [12.x] Re-enable previously commented assertions by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56930
* [12.x] Reorder .gitignore entries for consistency and readability by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56963
* [12.x] SQLite: Allow setting any pragmas by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/56962
* refactor: remove unused array from docblock by [@alipowerful7](https://github.com/alipowerful7) in https://github.com/laravel/framework/pull/56961
* PendingResourceRegistration withoutMiddleware never returns array by [@moshe-autoleadstar](https://github.com/moshe-autoleadstar) in https://github.com/laravel/framework/pull/56959
* [12.x] Allow not having "fakerphp/faker" installed by [@SjorsO](https://github.com/SjorsO) in https://github.com/laravel/framework/pull/56953
* [12.x] Fix Validator placeholderHash PHPDoc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56947
* [12.x] Handle MariaDB innodb_snapshot_isolation=ON by [@Muffinman](https://github.com/Muffinman) in https://github.com/laravel/framework/pull/56945
* [12.x] Add PhpRedis pack ignore numbers option by [@tuandp](https://github.com/tuandp) in https://github.com/laravel/framework/pull/56941
* test(support): add edge-case tuples for preg_replace_array by [@realpvz](https://github.com/realpvz) in https://github.com/laravel/framework/pull/56937
* [12.x] Allow for BackedEnum on dynamic blade component by [@gehrisandro](https://github.com/gehrisandro) in https://github.com/laravel/framework/pull/56940
* [12.x] Remove one redundant array access by [@vincentvanhoven](https://github.com/vincentvanhoven) in https://github.com/laravel/framework/pull/56931
* [12.x] Add withoutGlobalScopesExcept() to keep only specified global scopes by [@theHocineSaad](https://github.com/theHocineSaad) in https://github.com/laravel/framework/pull/56957
* [12.x] Make visibility consistent by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56970
* [12.x] Change list to tuple in PHPDoc block by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/56967
* [12.x] Improve `AggregateServiceProvider` docblocks by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56968
* [12.x] add --whisper option to schedule:work command by [@thojo0](https://github.com/thojo0) in https://github.com/laravel/framework/pull/56969
* [12.x] Update Faker suggestion to match skeleton version by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56974
* Refactor: use str_contains() instead of strpos() for clarity by [@arshidkv12](https://github.com/arshidkv12) in https://github.com/laravel/framework/pull/56979
* [12.x] remove unnecessary `with()` helper call by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56975
* [12.x] Config: Move some items into pragmas by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56980
* Add callback support to takeUntilTimeout in LazyCollection by [@kamilkozak](https://github.com/kamilkozak) in https://github.com/laravel/framework/pull/56981
* [12.x] Utilize the is_finite() PHP function by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56990
* [12.x] Use property promotion in `MessageLogged` and narrow `$level` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56989
* [12.x] do not use `with()` helper when no second argument is passed by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56986
* [12.x] Correct the type of $handler from Connection::whenQueryingForLongerThan by [@sethsandaru](https://github.com/sethsandaru) in https://github.com/laravel/framework/pull/56987
* [12.x] Some quick fixes by [@theHocineSaad](https://github.com/theHocineSaad) in https://github.com/laravel/framework/pull/56991
* tests: Ensure transaction callbacks run in FIFO order by [@realpvz](https://github.com/realpvz) in https://github.com/laravel/framework/pull/56973
* Pass $attributes and $parent arguments to Factory Sequence by [@fritz-c](https://github.com/fritz-c) in https://github.com/laravel/framework/pull/56972
* [12.x] - Support `Castable` on `Enum` by [@jrseliga](https://github.com/jrseliga) in https://github.com/laravel/framework/pull/56977
* [12.x] add trailing commas in multiline method signatures by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56992
* [12.x] Improve docblocks for nullable parameters by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56995
* [12.x] Improve docblocks for nullable parameters by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56996
* [12.x] Improve docblocks for nullable parameters by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56997
* Revert "[12.x] Config: Move some items into pragmas" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/57003
* [12.x]: Cache Session Driver by [@joaopalopes24](https://github.com/joaopalopes24) in https://github.com/laravel/framework/pull/56887
* [12.x] Add support for #[UseResource(...)] and #[UseResourceCollection(...)] attributes on models by [@Lukasss93](https://github.com/Lukasss93) in https://github.com/laravel/framework/pull/56966
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/57010
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/57031
* [12.x] Infinite method chaining in contextual binding builder by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57026
* [12.x] Improved manager typehints by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/57024
* Bump vite from 5.4.19 to 5.4.20 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot)[bot] in https://github.com/laravel/framework/pull/57009
* [12.x] Correct APC cache store docblock types by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/57020
* [12.x] Enable dynamic tries() method on Queueable Listeners by [@glioympas](https://github.com/glioympas) in https://github.com/laravel/framework/pull/57014
* [12.x] Add --json option to ScheduleListCommand by [@dxnter](https://github.com/dxnter) in https://github.com/laravel/framework/pull/57006
* [12.x] `with()` helper call simplification by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/57041
* [12.x] handle all Enum types for default values by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/57040
* [12.x] Refactor chained method calls for readability by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57050
* [12.x] Improve docblock wording by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57056
* [12.x] Refactor chained method calls for readability by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/57054
* [12.x] Update local exception page by [@avosalmon](https://github.com/avosalmon) in https://github.com/laravel/framework/pull/57036
* [12.x] Add ability to control QueueWorker memory exceeded exit code by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/57044
* [12.x] Ensure `laravel-cloud-socket` respects `LOG_LEVEL` by [@PeteBishwhip](https://github.com/PeteBishwhip) in https://github.com/laravel/framework/pull/57071

## [v12.28.1](https://github.com/laravel/framework/compare/v12.28.0...v12.28.1) - 2025-09-04

* [12.x] Rename `group` to `messageGroup` property by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56919
* Fix PHP_CLI_SERVER_WORKERS inside laravel/sail by [@akyrey](https://github.com/akyrey) in https://github.com/laravel/framework/pull/56923
* Allow RouteRegistrar to be Macroable by [@moshe-autoleadstar](https://github.com/moshe-autoleadstar) in https://github.com/laravel/framework/pull/56921
* [12.x] Fix SesV2Transport docblock by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/56917
* [12.x] Prevent unnecessary query logging on exceptions with a custom renderer by [@luanfreitasdev](https://github.com/luanfreitasdev) in https://github.com/laravel/framework/pull/56874
* [12.x] Reduce meaningless intermediate variables by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56927

## [v12.28.0](https://github.com/laravel/framework/compare/v12.27.1...v12.28.0) - 2025-09-03

* [11.x] Correct how base options for missing config files are preloaded by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/framework/pull/56216
* [11.x] backport #56235 by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56236
* [11.x] Consistent use of `mb_split()` to split strings into words by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56617
* [11.x] `CacheSchedulingMutex` should use lock connection by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56614
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56630
* [11.x] Update `orchestra/testbench-core` deps by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56636
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56649
* [11.x] Fix exception page not preparing SQL bindings by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56651
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56849
* [11.x] Chore: Decouple Str::random() from Validator by [@michaeldyrynda](https://github.com/michaeldyrynda) in https://github.com/laravel/framework/pull/56852
* [11.x] Allow a wider range of `brick/math` versions by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56890
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56894
* [12.x] Switch back to ternaries in `DatabaseManager` to allow for empty named connections by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56906
* [12.x] Update config/database.php to match the latest skeleton configuration by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56905
* Update fluent() helper by [@tanthammar](https://github.com/tanthammar) in https://github.com/laravel/framework/pull/56900
* [12.x] Add method to retrieve the command on InvokedProcess by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/56886
* [12.x] provide a default slot name when compiling by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56883
* [12.x] Allow enums on model connection property and methods by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56896
* [12.x] Adds internal class by [@nunomaduro](https://github.com/nunomaduro) in https://github.com/laravel/framework/pull/56903

## [v12.27.1](https://github.com/laravel/framework/compare/v12.27.0...v12.27.1) - 2025-09-02

* [12.x] Allow a wider range of `brick/math` versions by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56891
* [12.x] Fix secure_url() breaking changes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56885

## [v12.27.0](https://github.com/laravel/framework/compare/v12.26.4...v12.27.0) - 2025-09-02

* [12.x] Add prepend option for Str::plural() by [@caseydwyer](https://github.com/caseydwyer) in https://github.com/laravel/framework/pull/56802
* [12.x] Fix multi-line embedded image replacement in mail views by [@iammursal](https://github.com/iammursal) in https://github.com/laravel/framework/pull/56828
* [12.x] Add supports for SQS Fair Queue by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56763
* [12.x] Support enum values in `Collection` `countBy` method by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56830
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56838
* [12.x] Fix docblocks and all() method in ArrayStore for consistency by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56845
* [12.x] Improve Grammar in ArrayLock by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56844
* [12.x] Normalize comments for timestampsTz() and nullableTimestampsTz() by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56840
* [12.x] Reduce meaningless intermediate variables by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56843
* [12.x] Simpler and consistent `Arr::collapse()` by [@weshooper](https://github.com/weshooper) in https://github.com/laravel/framework/pull/56842
* [12.x] Improving readability by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56847
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56850
* [12.x] Remove extra space before line number in exception trace by [@mtbossa](https://github.com/mtbossa) in https://github.com/laravel/framework/pull/56863
* [12.x] Remove unused variable by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56861
* [12.x] Add support for `UnitEnum` in `Collection` `groupBy` method by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56857
* [12.x] Add missing void return type to test methods by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56860
* [12.x] Improve `countBy` docblock in `Collection` to allow for enum callback by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56856
* [12.x] Improve `InteractsWithContainer` return types by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/56853
* [12.x] Allow mass assignment for value object casting. by [@AbdelElrafa](https://github.com/AbdelElrafa) in https://github.com/laravel/framework/pull/56871
* [12.x] Allows `APP_BASE_PATH` from `$_SERVER` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56868
* [12.x] Fix typo in docblock by [@dwightwatson](https://github.com/dwightwatson) in https://github.com/laravel/framework/pull/56867
* [12.x] Allow enums in other DatabaseManager methods by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56878
* Add health score badge to README by [@jonathimer](https://github.com/jonathimer) in https://github.com/laravel/framework/pull/56875
* [12.x] Let `toPrettyJson()` accepts options by [@lucasmichot](https://github.com/lucasmichot) in https://github.com/laravel/framework/pull/56876

## [v12.26.4](https://github.com/laravel/framework/compare/v12.26.3...v12.26.4) - 2025-08-29

* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56792
* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56794
* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56795
* [12.x] Add support for nested array notation within `loadMissing` by [@angus-mcritchie](https://github.com/angus-mcritchie) in https://github.com/laravel/framework/pull/56711
* [12.x] Colocate Container build functions with the `SelfBuilding` interface by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56731
* perf: optimize loop performance by pre-calculating array counts in Str::apa() and fileSize() methods by [@AmadulHaque](https://github.com/AmadulHaque) in https://github.com/laravel/framework/pull/56796
* fix: Helper function secure_url not always returning a string by [@SOD96](https://github.com/SOD96) in https://github.com/laravel/framework/pull/56807
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56803
* [12.x] Parse Redis "friendly" algorithm names into integers by [@mateusjatenee](https://github.com/mateusjatenee) in https://github.com/laravel/framework/pull/56800
* [12.x] Remove [@return](https://github.com/return) tags from constructors by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56814
* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56813
* [12.x] Use FQCN for [@mixin](https://github.com/mixin) annotation for consistency by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56811
* [12.x] Remove leftover `method_exists` checks by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/56821
* [12.x] Fix use array_first and array_last by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56820
* Support enum in Collection -> keyBy() by [@zKoz210](https://github.com/zKoz210) in https://github.com/laravel/framework/pull/56786
* Adds make:config command by [@inmanturbo](https://github.com/inmanturbo) in https://github.com/laravel/framework/pull/56819

## [v12.26.3](https://github.com/laravel/framework/compare/v12.26.2...v12.26.3) - 2025-08-27

* [12.x] add back return type by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56774
* fix: base class guard in return types is breaking custom guards by [@phadaphunk](https://github.com/phadaphunk) in https://github.com/laravel/framework/pull/56779
* [12.x] Standardise polyfill dependencies by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56781
* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56790
* [12.x] Refactor duplicated logic in ReplacesAttributes by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56789
* [12.x] Improve output grammar in `ScheduleRunCommand` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56776

## [v12.26.2](https://github.com/laravel/framework/compare/v12.26.1...v12.26.2) - 2025-08-26

* [12.x] fix: csrf_token can return null by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56768
* [12.x] Fix `date_format` validation on DST Timezone by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56767
* [12.x] Fix event helper by [@jasonvarga](https://github.com/jasonvarga) in https://github.com/laravel/framework/pull/56773

## [v12.26.1](https://github.com/laravel/framework/compare/v12.26.0...v12.26.1) - 2025-08-26

* [12.x] fix: add polyfill requirement to illuminate packages by [@erikgaal](https://github.com/erikgaal) in https://github.com/laravel/framework/pull/56765
* [12.x] revert changes to `old()` helper by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56769

## [v12.26.0](https://github.com/laravel/framework/compare/v12.25.0...v12.26.0) - 2025-08-26

* [12.x] feat: add native return types to helper functions by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56684
* [12.x] Allow passing enum to `Database` attribute by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56688
* [12.x] Clean up redundant type hints in docblocks by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56690
* Add ability to specify a transaction mode for SQLite connection by [@panda-madness](https://github.com/panda-madness) in https://github.com/laravel/framework/pull/56681
* [12.x] Fix `spliceIntoPosition` docblock to allow `string|int` values by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56698
* [12.x] Use array_first and array_last polyfills by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/56703
* [12.x] Fix path to Str in exception markdown by [@apreiml](https://github.com/apreiml) in https://github.com/laravel/framework/pull/56705
* [12.x] Add `withHeartbeat` method to `LazyCollection` by [@JosephSilber](https://github.com/JosephSilber) in https://github.com/laravel/framework/pull/56477
* [12.x] Add toPrettyJson method by [@WendellAdriel](https://github.com/WendellAdriel) in https://github.com/laravel/framework/pull/56697
* [12.x] Use `array_first` and `array_last` by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/56706
* [12.x] Do not dispatch `MessageLogged` twice by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56713
* [12.x] Order classes alphabetically by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56743
* [12.x] Normalize file path separators for commands on Windows by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56734
* [12.x] Improve `queue:prune-failed` tests coverage by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56732
* [12.x] Align trait usage for consistency by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56727
* [12.x] Fix composer suggests for illuminate/container by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56722
* Add nullableTimestampsTz method to Blueprint by [@mohamedhabibwork](https://github.com/mohamedhabibwork) in https://github.com/laravel/framework/pull/56720
* Add possibility to override symbol when using currency format by [@PhilippeThouvenot](https://github.com/PhilippeThouvenot) in https://github.com/laravel/framework/pull/56749
* [12.x] Revert #56608 by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56752
* Revert "Add possibility to override symbol when using currency format" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/56753
* [12.x] Support `null` parameter in `BusFake::chain()` method by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/56750
* [12.x] Remove unnecessary return in ddBody for consistency by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56759
* [12.x] Make interface accept UnitEnum by [@parijke](https://github.com/parijke) in https://github.com/laravel/framework/pull/56758
* [12.x] Fix concurrency closure invocation: use base64 encoding by [@sashko-guz](https://github.com/sashko-guz) in https://github.com/laravel/framework/pull/56757
* [12.x] `ArrayStore::all()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56751
* [12.x] Fix: Add `$forceWrap` property to JsonResource for consistent API response #56724 by [@achrafAa](https://github.com/achrafAa) in https://github.com/laravel/framework/pull/56736
* [12.x] Ensures casts objects can be transformed into strings by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/56687

## [v12.25.0](https://github.com/laravel/framework/compare/v12.24.0...v12.25.0) - 2025-08-18

* [12.x] Prioritize Current Schema When Resolving the Table Name in `db:table` Command by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/56646
* [12.x] Add `allowedUrls` through `preventStrayRequests` by [@rabrowne85](https://github.com/rabrowne85) in https://github.com/laravel/framework/pull/56645
* [12.x] Add "Copy as Markdown" button to error page by [@mpociot](https://github.com/mpociot) in https://github.com/laravel/framework/pull/56657
* [12.x] Indicate that `Context@scope()` may throw by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56655
* [12.x] Remove [@throws](https://github.com/throws) phpDocs in the TransformToResource trait by [@adelf](https://github.com/adelf) in https://github.com/laravel/framework/pull/56667
* [12.x] Improve docblocks for InteractsWithDatabase by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56666
* [12.x] Fix prevent group attribute pollution in schedule by [@People-Sea](https://github.com/People-Sea) in https://github.com/laravel/framework/pull/56677
* Add new `mergeVisible`, `mergeHidden` and `mergeAppends` methods. by [@jonerickson](https://github.com/jonerickson) in https://github.com/laravel/framework/pull/56678

## [v12.24.0](https://github.com/laravel/framework/compare/v12.23.1...v12.24.0) - 2025-08-13

* [8.4] Use PHP 8.4 array helpers in Arr utils by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56631
* [12.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56635
* [12.x] Update `orchestra/testbench-core` deps by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56637
* refactor: update cid param name by [@cpenned](https://github.com/cpenned) in https://github.com/laravel/framework/pull/56634
* [12.x] Cache Singleton/Scoped attribute checks by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56633
* [12.x] Add `Arr::push()` by [@inxilpro](https://github.com/inxilpro) in https://github.com/laravel/framework/pull/56632
* [12.x] Add error message for `doesnt_contain` rule by [@apih](https://github.com/apih) in https://github.com/laravel/framework/pull/56644

## [v12.23.1](https://github.com/laravel/framework/compare/v12.23.0...v12.23.1) - 2025-08-12

## [v12.23.0](https://github.com/laravel/framework/compare/v12.22.1...v12.23.0) - 2025-08-12

* [12.x] Prevent unintended sleep on early failure of assertSequence by [@xHeaven](https://github.com/xHeaven) in https://github.com/laravel/framework/pull/56583
* [12.x] Redis cluster broadcaster by [@vadimonus](https://github.com/vadimonus) in https://github.com/laravel/framework/pull/56581
* [12.x] Alias Benchmark class by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/56594
* [12.x] Add support for drop patterns to the `make:migration` command's `TableGuesser`. by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56608
* [12.x] Improve collection return types by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/56599
* [12.x] Fix collection typo by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/56597
* Fix return type docblock for resetAttempts method in RateLimiter by [@jonagoldman](https://github.com/jonagoldman) in https://github.com/laravel/framework/pull/56596
* Add 'page' field to paginator links by [@compico](https://github.com/compico) in https://github.com/laravel/framework/pull/56603
* [12.x] Add support for inline attachments in Resend transport by [@jayanratna](https://github.com/jayanratna) in https://github.com/laravel/framework/pull/56598
* Fix test failures in PHPUnit 12.3.2 by [@KentarouTakeda](https://github.com/KentarouTakeda) in https://github.com/laravel/framework/pull/56610
* [12.x] Use new error and exception handler getters by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56623
* [12.x] Use PHP 8.4 array helpers by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56619
* [12.x] Prefer Symfony PHP polyfills over `function_exists` calls by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/56621
* [12.x] `Bind` attribute accepts UnitEnum by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56616
* [12.x] Add Vitess-specific safe to retry errors by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56615
* [12.x] Handle null as a falsy condition by [@negoziator](https://github.com/negoziator) in https://github.com/laravel/framework/pull/56612
* Added "after" support for morphs and nullableMorphs Blueprint by [@marcogermani87](https://github.com/marcogermani87) in https://github.com/laravel/framework/pull/56613
* [12.x] Fix usage of `Scoped` and `Singleton` on interfaces by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56620
* [12.x] Online (concurrently) index creation for PostgreSQL and SqlServer by [@vadimonus](https://github.com/vadimonus) in https://github.com/laravel/framework/pull/56625

## [v12.22.1](https://github.com/laravel/framework/compare/v12.21.0...v12.22.1) - 2025-08-08

* [12.x] Improved assertion message by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56579
* [12.x] Fixed version increment by [@dciancu](https://github.com/dciancu) in https://github.com/laravel/framework/pull/56588
* [12.x] Normalize file path separators in `make:migration` command on Windows by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56591
* Revert "[12.x] Improve PHPDoc blocks for array of arguments in Gate" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/56593

## [v12.21.0](https://github.com/laravel/framework/compare/v12.20.0...v12.21.0) - 2025-07-22

* fix(vite): #55793 add explicit as-script to link tag for script modul… by [@midsonlajeanty](https://github.com/midsonlajeanty) in https://github.com/laravel/framework/pull/55794
* [12.x] Allow globally disabling Factory parent relationships via `Factory::dontExpandRelationshipsByDefault()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56154
* [12.x] Adds checking if a value is between two columns by [@DarkGhostHunter](https://github.com/DarkGhostHunter) in https://github.com/laravel/framework/pull/56119
* [12.x] Ensure database connection is always restored by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/56258
* [12.x] Fix handling of `Htmlable` objects in `Js::convertDataToJavaScriptExpression()` by [@jj15asmr](https://github.com/jj15asmr) in https://github.com/laravel/framework/pull/56253
* Reduce meaningless intermediate variables. by [@LjjGit](https://github.com/LjjGit) in https://github.com/laravel/framework/pull/56265
* [12.x] Improve typehints for `AbstractCursorPaginator@through()` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56267
* Use `Date` facade instead of `time()` for `password_confirmed_at` check by [@dylanbr](https://github.com/dylanbr) in https://github.com/laravel/framework/pull/56270
* [12.x] fix: Collection::transform() and Paginator::through() return types by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56273
* [12.x] Merge 11.x into 12.x by [@u01jmg3](https://github.com/u01jmg3) in https://github.com/laravel/framework/pull/56289
* [12.x] Reduce meaningless intermediate variables by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56288
* [12.x] Refactor build Method to Use Null Coalescing Assignment for Default C… by [@Ashot1995](https://github.com/Ashot1995) in https://github.com/laravel/framework/pull/56283
* [12.x] minor code formatting improvements by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56296
* [12.x] Use more specific route binding exception message for child routes by [@jessekoerhuis](https://github.com/jessekoerhuis) in https://github.com/laravel/framework/pull/56298
* [12.x] Fix Possible Undefined Variables by [@calfc](https://github.com/calfc) in https://github.com/laravel/framework/pull/56292
* [12.x] Fix: Ensure scheduler `dailyAt()` method parses minutes and ignores seconds when seconds are provided by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56308
* [12.x] Allows for strict boolean validation by [@peterfox](https://github.com/peterfox) in https://github.com/laravel/framework/pull/56313
* Improve `SeedCommand` console output by [@Jehong-Ahn](https://github.com/Jehong-Ahn) in https://github.com/laravel/framework/pull/56310
* [12.x] Add unified enum support across framework docs by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56271
* [12.x] Allows for strict numeric validation by [@peterfox](https://github.com/peterfox) in https://github.com/laravel/framework/pull/56328
* [12.x] Update PHPDoc annotations in `Validation` by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/56321
* [12.x] Add operator class support for PostgreSQL GiST spatial indexes by [@joteejotee](https://github.com/joteejotee) in https://github.com/laravel/framework/pull/56324
* Fix multipart array value parsing in HTTP client (#55732) by [@joteejotee](https://github.com/joteejotee) in https://github.com/laravel/framework/pull/56302
* Fixes bug with ShouldBeUniqueUntilProcessing locks getting stuck due to Middleware by [@TWithers](https://github.com/TWithers) in https://github.com/laravel/framework/pull/56318
* [12.x] add prompts based expectations to PendingCommand by [@BinaryKitten](https://github.com/BinaryKitten) in https://github.com/laravel/framework/pull/56260
* [12.x] Add Singleton and Scoped attributes to Container by [@riasvdv](https://github.com/riasvdv) in https://github.com/laravel/framework/pull/56334
* Fix unsetting model castable attribute when cast to object (#56335) by [@guram-vashakidze](https://github.com/guram-vashakidze) in https://github.com/laravel/framework/pull/56343
* [12.x]  Fix/memory improvement by [@CharrafiMed](https://github.com/CharrafiMed) in https://github.com/laravel/framework/pull/56345
* [12.x] Add hasMailer method to the mailable class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/56340
* [12.x] Consistent use of `mb_split()` to split strings into words by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/56338
* [12.x] Add toStringable to Uri by [@Kyrch](https://github.com/Kyrch) in https://github.com/laravel/framework/pull/56359
* [12.x] Fix PHPStan Integrations by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56369
* Add 'isEmpty' and 'isNotEmpty' to Fluent by [@cworreschk](https://github.com/cworreschk) in https://github.com/laravel/framework/pull/56370
* [12.x] Add mergeMetadata method to the Mailable class by [@kevinb1989](https://github.com/kevinb1989) in https://github.com/laravel/framework/pull/56376
* Add 'dontReportUsing' to filter exceptions to be reported by [@pelmered](https://github.com/pelmered) in https://github.com/laravel/framework/pull/56361

## [v12.20.0](https://github.com/laravel/framework/compare/v12.19.3...v12.20.0) - 2025-07-08

* [12.x] Pass TransportException to NotificationFailed event by [@hackel](https://github.com/hackel) in https://github.com/laravel/framework/pull/56061
* [12.x] use `offset()` in place of `skip()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56081
* [12.x] use `limit()` in place of `take()` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56080
* [12.x] Display job queue names when running queue:work with --verbose option by [@seriquynh](https://github.com/seriquynh) in https://github.com/laravel/framework/pull/56086
* [12.x] use `offset()` and `limit()` in tests by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56089
* [12.x] Localize “Pagination Navigation” aria-label by [@andylolz](https://github.com/andylolz) in https://github.com/laravel/framework/pull/56103
* [12.x] Enhance the test coverage for Pipeline::through() by [@azim-kordpour](https://github.com/azim-kordpour) in https://github.com/laravel/framework/pull/56100
* [12.x] Added `JsonSerializable` interface to `Uri` Class by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/56097
* [12.x] Display job connection name when running queue:work with --verbose option by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56095
* [12.x] Fix PHPDoc for Arr::sole method by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56096
* [12.x] when a method returns `$this` set the return type to `static` by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56092
* [12.x] Use `int<0, max>` as docblock return type for database operations that return a count by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56117
* [12.x] Add missing [@throws](https://github.com/throws) annotation to Number by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56116
* [12.x] Correct PHPDoc for Arr::sole callable type to avoid return type ambiguity by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56108
* Change return types of through (pagination) and transform (collection) by [@glamorous](https://github.com/glamorous) in https://github.com/laravel/framework/pull/56105
* [12.x] Add maintenance mode facade for easier driver extension by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/56090
* [12.x] Cache isSoftDeletable(), isPrunable(), and isMassPrunable() directly in model by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/56078
* [12.x] Throws not throw by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56120
* [12.x] Fix [@param](https://github.com/param) docblock to allow string by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56121
* [11.x] Pass the limiter to the when & report callbacks by [@jimmypuckett](https://github.com/jimmypuckett) in https://github.com/laravel/framework/pull/56129
* [12.x] remove the "prefix" option for cache password resets by [@browner12](https://github.com/browner12) in https://github.com/laravel/framework/pull/56127
* [12.x] Make Model::currentEncrypter public by [@JaZo](https://github.com/JaZo) in https://github.com/laravel/framework/pull/56130
* [12.x] Add throws docblock by [@amirhshokri](https://github.com/amirhshokri) in https://github.com/laravel/framework/pull/56137
* [12.x] Narrow integer range for `Collection` methods by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56135
* [12.x] Allows using `--model` and `--except` via `PruneCommand` command by [@hosni](https://github.com/hosni) in https://github.com/laravel/framework/pull/56140
* [12.x] Support Passing `Htmlable` Instances to `Js::from()` by [@jj15asmr](https://github.com/jj15asmr) in https://github.com/laravel/framework/pull/56159
* #56124 Properly escape column defaults by [@asmecher](https://github.com/asmecher) in https://github.com/laravel/framework/pull/56158
* [12.x] Return early on belongs-to-many relationship `syncWithoutDetaching` method when empty values are given by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/56157
* [12.x] Add fakeFor and fakeExceptFor methods to Queue facade by [@MrPunyapal](https://github.com/MrPunyapal) in https://github.com/laravel/framework/pull/56149
* [11.x] Backport test fixes by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56183
* Revert "[11.x] Pass the limiter to the when & report callbacks" by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56184
* Add failWhen method to ThrottlesExceptions job middleware by [@michaeldzjap](https://github.com/michaeldzjap) in https://github.com/laravel/framework/pull/56180
* [12.x] Update Castable contract to accept string array by [@hosmelq](https://github.com/hosmelq) in https://github.com/laravel/framework/pull/56177
* Feature: doesntStartWith() and doesntEndWith() string methods by [@balboacodes](https://github.com/balboacodes) in https://github.com/laravel/framework/pull/56168
* [12.x] Add context remember functions by [@btaskew](https://github.com/btaskew) in https://github.com/laravel/framework/pull/56156
* [12.x] Fix queue fake cleanup to always restore original queue manager by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/56165
* [12.x] Pass the limiter to the when & report callbacks by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56187
* [12.x] Add `Closure`-support to `$key`/`$value` in Collection `pluck()` method by [@ralphjsmit](https://github.com/ralphjsmit) in https://github.com/laravel/framework/pull/56188
* [12.x] Add `collection()` to Config repository by [@KennedyTedesco](https://github.com/KennedyTedesco) in https://github.com/laravel/framework/pull/56200
* Add int to allowed types of value in DatabaseRule by [@vkarchevskyi](https://github.com/vkarchevskyi) in https://github.com/laravel/framework/pull/56199
* [12.x] Fix Event fake cleanup to always restore original event dispatcher by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/56189
* [12.x] Align PHPDoc style in Number::parseFloat with the rest of the class by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56206
* [12.x] Inconsistent use of [@return](https://github.com/return) type by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56207
* [12.x] Resolve issue with Factory make when automatic eager loading by [@jackbayliss](https://github.com/jackbayliss) in https://github.com/laravel/framework/pull/56211
* [12.x] Refactor driver initialization using null coalescing assignment in Manager by [@Ashot1995](https://github.com/Ashot1995) in https://github.com/laravel/framework/pull/56210
* [12.x] Add URL signature macros to `Request` docblock by [@duncanmcclean](https://github.com/duncanmcclean) in https://github.com/laravel/framework/pull/56230
* [12.x] Update PHPDoc for dataForSometimesIteration by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/56229
* [12.x] Avoid unnecessary filtering when no callback is provided by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/56225
* [12.x] Make `Fluent` class iterable by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/56218
* Improve Mailable assertion error messages with expected vs actual values by [@ahinkle](https://github.com/ahinkle) in https://github.com/laravel/framework/pull/56221
* [12.x] Add `@​context` Blade directive by [@martinbean](https://github.com/martinbean) in https://github.com/laravel/framework/pull/56146
* [12.x] fix: AsCommand properties not being set on commands by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56235
* [12.x] Ensure `withLocale` and `withCurrency` always restore previous state by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/56234

## [v12.19.3](https://github.com/laravel/framework/compare/v12.19.2...v12.19.3) - 2025-06-18

* [12.x] Fix model pruning when non model files are in the same directory by [@rojtjo](https://github.com/rojtjo) in https://github.com/laravel/framework/pull/56071

## [v12.19.2](https://github.com/laravel/framework/compare/v12.19.1...v12.19.2) - 2025-06-17

## [v12.19.1](https://github.com/laravel/framework/compare/v12.19.0...v12.19.1) - 2025-06-17

* Revert "[12.x] Check if file exists before trying to delete it" by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/56072

## [v12.19.0](https://github.com/laravel/framework/compare/v12.18.0...v12.19.0) - 2025-06-17

* [11.x] Fix validation to not throw incompatible validation exception by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55963
* [12.x] Correct testEncryptAndDecrypt to properly test new methods by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/55985
* [12.x] Check if file exists before trying to delete it by [@Jellyfrog](https://github.com/Jellyfrog) in https://github.com/laravel/framework/pull/55994
* Clear cast caches when discarding changes by [@willtj](https://github.com/willtj) in https://github.com/laravel/framework/pull/55992
* [12.x] Handle Null Check in Str::contains by [@Jellyfrog](https://github.com/Jellyfrog) in https://github.com/laravel/framework/pull/55991
* [12.x] Remove call to deprecated `getDefaultDescription` method by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/55990
* Bump brace-expansion from 2.0.1 to 2.0.2 in /src/Illuminate/Foundation/resources/exceptions/renderer by [@dependabot](https://github.com/dependabot) in https://github.com/laravel/framework/pull/55999
* Enhance error handling in PendingRequest to convert TooManyRedirectsE… by [@achrafAa](https://github.com/achrafAa) in https://github.com/laravel/framework/pull/55998
* [12.x] fix: remove Model intersection from UserProvider contract by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/56013
* [12.x] Remove the only [@return](https://github.com/return) tag left on a constructor by [@JordanchoEftimov](https://github.com/JordanchoEftimov) in https://github.com/laravel/framework/pull/56001
* [12.x] Introduce `ComputesOnceableHashInterface` by [@Jacobs63](https://github.com/Jacobs63) in https://github.com/laravel/framework/pull/56009
* [12.x] Add assertRedirectBackWithErrors to TestResponse by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55987
* [12.x] collapseWithKeys - Prevent exception in base case by [@DeanWunder](https://github.com/DeanWunder) in https://github.com/laravel/framework/pull/56002
* [12.x] Standardize size() behavior and add extended queue metrics support by [@sylvesterdamgaard](https://github.com/sylvesterdamgaard) in https://github.com/laravel/framework/pull/56010
* [11.x] Fix `symfony/console:7.4` compatibility by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/56015
* [12.x] Improve constructor PHPDoc for controller middleware definition by [@JordanchoEftimov](https://github.com/JordanchoEftimov) in https://github.com/laravel/framework/pull/56021
* Remove `@return` tags from constructors by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/56024
* [12.x] sort helper functions in alphabetic order by [@gigabites19](https://github.com/gigabites19) in https://github.com/laravel/framework/pull/56031
* [12.x] add Attachment::fromUploadedFile method by [@rodrigopedra](https://github.com/rodrigopedra) in https://github.com/laravel/framework/pull/56027
* [12.x]: Add UseEloquentBuilder attribute to register custom Eloquent Builder by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/56025
* [12.x] Improve PHPDoc for the Illuminate\Cache folder files by [@JordanchoEftimov](https://github.com/JordanchoEftimov) in https://github.com/laravel/framework/pull/56028
* [12.x] Add a new model cast named asFluent by [@azim-kordpour](https://github.com/azim-kordpour) in https://github.com/laravel/framework/pull/56046
* [12.x] Introduce `FailOnException` job middleware by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/56037
* [12.x] isSoftDeletable(), isPrunable(), and isMassPrunable() to model class by [@shaedrich](https://github.com/shaedrich) in https://github.com/laravel/framework/pull/56060

## [v12.18.0](https://github.com/laravel/framework/compare/v12.17.0...v12.18.0) - 2025-06-10

* document `through()` method in interfaces to fix IDE warnings by [@harryqt](https://github.com/harryqt) in https://github.com/laravel/framework/pull/55925
* [12.x] Add encrypt and decrypt Str helper methods by [@KIKOmanasijev](https://github.com/KIKOmanasijev) in https://github.com/laravel/framework/pull/55931
* [12.x] Add a command option for making batchable jobs by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/55929
* [12.x] fix: intersect Authenticatable with Model in UserProvider phpdocs by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/54061
* [12.x] feat: create UsePolicy attribute by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55882
* [12.x] `ScheduledTaskFailed` not dispatched on scheduled forground task fails by [@achrafAa](https://github.com/achrafAa) in https://github.com/laravel/framework/pull/55624
* [12.x] Add generics to `Model::unguarded()` by [@axlon](https://github.com/axlon) in https://github.com/laravel/framework/pull/55932
* [12.x] Fix SSL Certificate and Connection Errors Leaking as Guzzle Exceptions by [@achrafAa](https://github.com/achrafAa) in https://github.com/laravel/framework/pull/55937
* Fix deprecation warning in PHP 8.3 by ensuring string type in explode() by [@Khuthaily](https://github.com/Khuthaily) in https://github.com/laravel/framework/pull/55939
* revert: #55939 by [@NickSdot](https://github.com/NickSdot) in https://github.com/laravel/framework/pull/55943
* [12.x] feat: Add WorkerStarting event when worker daemon starts by [@Orrison](https://github.com/Orrison) in https://github.com/laravel/framework/pull/55941
* [12.x] Allow setting the `RequestException` truncation limit per request by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55897
* [12.x] feat: Make custom eloquent castings comparable for more granular isDirty check by [@SanderSander](https://github.com/SanderSander) in https://github.com/laravel/framework/pull/55945
* [12.x] fix alphabetical order by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55965
* [12.x] Use native named parameter instead of unused variable by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55964
* [12.x] add generics to Model attribute related methods and properties by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55962
* [12.x] Supports PHPUnit 12.2 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55961
* [12.x] feat: Add ability to override SendQueuedNotifications job class by [@Orrison](https://github.com/Orrison) in https://github.com/laravel/framework/pull/55942
* [12.x] Fix timezone validation test for PHP 8.3+ by [@platoindebugmode](https://github.com/platoindebugmode) in https://github.com/laravel/framework/pull/55956
* Broadcasting Utilities by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55967
* [12.x] Remove unused $guarded parameter from testChannelNameNormalization method by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55973
* [12.x] Validate that `outOf` is greater than 0 in `Lottery` helper by [@mrvipchien](https://github.com/mrvipchien) in https://github.com/laravel/framework/pull/55969
* [12.x] Allow retrieving all reported exceptions from `ExceptionHandlerFake` by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55972

## [v12.17.0](https://github.com/laravel/framework/compare/v12.16.0...v12.17.0) - 2025-06-03

* [11.x] Backport `TestResponse::assertRedirectBack` by [@GrahamCampbell](https://github.com/GrahamCampbell) in https://github.com/laravel/framework/pull/55780
* Add support for sending raw (non-encoded) attachments in Resend mail by [@Roywcm](https://github.com/Roywcm) in https://github.com/laravel/framework/pull/55837
* [12.x] chore: return Collection from timestamps methods by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55871
* [12.x] fix: fully qualify collection return type by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55873
* [12.x] Fix Blade nested default component resolution for custom namespaces by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/55874
* [12.x] Fix return types in console command handlers to void by [@michaelnabil230](https://github.com/michaelnabil230) in https://github.com/laravel/framework/pull/55876
* [12.x] Ability to perform higher order static calls on collection items by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/55880
* Adds Resource helpers to cursor paginator by [@jsandfordhughescoop](https://github.com/jsandfordhughescoop) in https://github.com/laravel/framework/pull/55879
* Add reorderDesc() to Query Builder by [@ghabriel25](https://github.com/ghabriel25) in https://github.com/laravel/framework/pull/55885
* [11.x] Fixes Symfony Console 7.3 deprecations on closure command by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55888
* [12.x] Add `AsUri` model cast by [@ash-jc-allen](https://github.com/ash-jc-allen) in https://github.com/laravel/framework/pull/55909
* [12.x] feat: Add Contextual Implementation/Interface Binding via PHP8 Attribute by [@yitzwillroth](https://github.com/yitzwillroth) in https://github.com/laravel/framework/pull/55904
* [12.x] Add tests for the `AuthenticateSession` Middleware by [@imanghafoori1](https://github.com/imanghafoori1) in https://github.com/laravel/framework/pull/55900
* [12.x] Allow brick/math ^0.13 by [@jnoordsij](https://github.com/jnoordsij) in https://github.com/laravel/framework/pull/54964
* [12.x] fix: Factory::state and ::prependState generics by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55915

## [v12.16.0](https://github.com/laravel/framework/compare/v12.15.0...v12.16.0) - 2025-05-27

* [12.x] Change priority in optimize:clear by [@amirmohammadnajmi](https://github.com/amirmohammadnajmi) in https://github.com/laravel/framework/pull/55792
* [12.x] Fix `TestResponse::assertSessionMissing()` when given an array of keys by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55800
* [12.x] Allowing `Context` Attribute to Interact with Hidden by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55799
* Add support for sending raw (non-encoded) attachments in Resend mail driver by [@Roywcm](https://github.com/Roywcm) in https://github.com/laravel/framework/pull/55803
* [12.x] Added option to always defer for flexible cache by [@Zwartpet](https://github.com/Zwartpet) in https://github.com/laravel/framework/pull/55802
* [12.x] style: Use null coalescing assignment (??=) for cleaner code by [@mohsenetm](https://github.com/mohsenetm) in https://github.com/laravel/framework/pull/55823
* [12.x] Introducing `Arr::hasAll` by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55815
* [12.x] Restore lazy loading check by [@decadence](https://github.com/decadence) in https://github.com/laravel/framework/pull/55817
* [12.x] Minor language update by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55812
* fix(cache/redis): use connectionAwareSerialize in RedisStore::putMany() by [@superbiche](https://github.com/superbiche) in https://github.com/laravel/framework/pull/55814
* [12.x] Fix `ResponseFactory` should also accept `null` callback by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55833
* [12.x] Add template variables to scope by [@wietsewarendorff](https://github.com/wietsewarendorff) in https://github.com/laravel/framework/pull/55830
* [12.x] Introducing `toUri` to the `Stringable` Class by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55862
* [12.x] Remove remaining [@return](https://github.com/return) tags from constructors by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55858
* [12.x] Replace alias `is_integer()` with `is_int()` to comply with Laravel Pint by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/55851
* Fix argument types for Illuminate/Database/Query/Builder::upsert() by [@jellisii](https://github.com/jellisii) in https://github.com/laravel/framework/pull/55849
* [12.x] Add `in_array_keys` validation rule to check for presence of specified array keys by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/55807
* [12.x] Add `Rule::contains` by [@stevebauman](https://github.com/stevebauman) in https://github.com/laravel/framework/pull/55809

## [v12.15.0](https://github.com/laravel/framework/compare/v12.14.1...v12.15.0) - 2025-05-20

* [12.x] Add locale-aware number parsing methods to Number class by [@informagenie](https://github.com/informagenie) in https://github.com/laravel/framework/pull/55725
* [12.x] Add a default option when retrieving an enum from data by [@elbojoloco](https://github.com/elbojoloco) in https://github.com/laravel/framework/pull/55735
* Revert "[12.x] Update "Number::fileSize" to use correct prefix and add prefix param" by [@ziadoz](https://github.com/ziadoz) in https://github.com/laravel/framework/pull/55741
* [12.x] Remove apc by [@AhmedAlaa4611](https://github.com/AhmedAlaa4611) in https://github.com/laravel/framework/pull/55745
* [12.x] Add param type for `assertJsonStructure` & `assertExactJsonStructure` methods by [@milwad-dev](https://github.com/milwad-dev) in https://github.com/laravel/framework/pull/55743
* [12.x] Fix type casting for environment variables in config files by [@adamwhp](https://github.com/adamwhp) in https://github.com/laravel/framework/pull/55737
* [12.x] Preserve "previous" model state by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55729
* [12.x] Passthru `getCountForPagination` on an Eloquent\Builder by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/55752
* [12.x] Add `assertClientError` method to `TestResponse` by [@shane-zeng](https://github.com/shane-zeng) in https://github.com/laravel/framework/pull/55750
* Install Broadcasting Command Fix for Livewire Starter Kit by [@joshcirre](https://github.com/joshcirre) in https://github.com/laravel/framework/pull/55774
* Clarify units for benchmark value for IDE accessibility by [@mike-healy](https://github.com/mike-healy) in https://github.com/laravel/framework/pull/55781
* Improved PHPDoc Return Types for Eloquent's Original Attribute Methods by [@clementbirkle](https://github.com/clementbirkle) in https://github.com/laravel/framework/pull/55779
* [12.x] Prevent `preventsLazyLoading` exception when using `automaticallyEagerLoadRelationships` by [@devajmeireles](https://github.com/devajmeireles) in https://github.com/laravel/framework/pull/55771
* [12.x] Add `hash` string helper by [@istiak-tridip](https://github.com/istiak-tridip) in https://github.com/laravel/framework/pull/55767
* [12.x] Update `assertSessionMissing()` signature to match `assertSessionHas()` by [@nexxai](https://github.com/nexxai) in https://github.com/laravel/framework/pull/55763
* Fix: php artisan db command if no password by [@mr-chetan](https://github.com/mr-chetan) in https://github.com/laravel/framework/pull/55761
* [12.x] Types: InteractsWithPivotTable::sync by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/55762
* [12.x] feat: Add `current_page_url` to Paginator by [@mariomka](https://github.com/mariomka) in https://github.com/laravel/framework/pull/55789
* Correct return type in PhpDoc for command fail method by [@Muetze42](https://github.com/Muetze42) in https://github.com/laravel/framework/pull/55783
* [12.x] Add `assertRedirectToAction` method to test redirection to controller actions by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/55788
* [12.x] Add Context contextual attribute by [@martinbean](https://github.com/martinbean) in https://github.com/laravel/framework/pull/55760

## [v12.14.1](https://github.com/laravel/framework/compare/v12.14.0...v12.14.1) - 2025-05-13

* [10.x] Refine error messages for detecting lost connections (Debian bookworm compatibility) by [@mfn](https://github.com/mfn) in https://github.com/laravel/framework/pull/53794
* [10.x] Bump minimum `league/commonmark` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/53829
* [10.x] Backport 11.x PHP 8.4 fix for str_getcsv deprecation by [@aka-tpayne](https://github.com/aka-tpayne) in https://github.com/laravel/framework/pull/54074
* [10.x] Fix attribute name used on `Validator` instance within certain rule classes by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54943
* Add `Illuminate\Support\EncodedHtmlString` by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/54737
* [11.x] Fix missing `return $this` for `assertOnlyJsonValidationErrors` by [@LeTamanoir](https://github.com/LeTamanoir) in https://github.com/laravel/framework/pull/55099
* [11.x] Fix `Illuminate\Support\EncodedHtmlString` from causing breaking change by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55149
* [11.x] Respect custom path for cached views by the `AboutCommand` by [@alies-dev](https://github.com/alies-dev) in https://github.com/laravel/framework/pull/55179
* [11.x] Include all invisible characters in Str::trim by [@laserhybiz](https://github.com/laserhybiz) in https://github.com/laravel/framework/pull/54281
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55302
* [11.x] Remove incorrect syntax from mail's `message` template by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55530
* [11.x] Allows to toggle markdown email encoding by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55539
* [11.x] Fix `EncodedHtmlString` to ignore instance of `HtmlString` by [@jbraband](https://github.com/jbraband) in https://github.com/laravel/framework/pull/55543
* [11.x] Test Improvements by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55549
* [11.x] Install Passport 13.x by [@hafezdivandari](https://github.com/hafezdivandari) in https://github.com/laravel/framework/pull/55621
* [11.x] Bump minimum league/commonmark by [@andrextor](https://github.com/andrextor) in https://github.com/laravel/framework/pull/55660
* Backporting Timebox fixes to 11.x by [@valorin](https://github.com/valorin) in https://github.com/laravel/framework/pull/55705
* Test SQLServer 2017 on Ubuntu 22.04 by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55716
* [11.x] Fix Symfony 7.3 deprecations by [@crynobone](https://github.com/crynobone) in https://github.com/laravel/framework/pull/55711
* Easily implement broadcasting in a React/Vue Typescript app (Starter Kits) by [@tnylea](https://github.com/tnylea) in https://github.com/laravel/framework/pull/55170

## [v12.14.0](https://github.com/laravel/framework/compare/v12.13.0...v12.14.0) - 2025-05-13

* [12.x] Support `useCurrent` on date and year column types by [@nicholasbrantley](https://github.com/nicholasbrantley) in https://github.com/laravel/framework/pull/55619
* [12.x] Update "Number::fileSize" to use correct prefix and add prefix param by [@Boy132](https://github.com/Boy132) in https://github.com/laravel/framework/pull/55678
* [12.x] Update PHPDoc for whereRaw to allow Expression as $sql by [@mitoop](https://github.com/mitoop) in https://github.com/laravel/framework/pull/55674
* Revert "[12.x] Make Blueprint Resolver Statically" by [@taylorotwell](https://github.com/taylorotwell) in https://github.com/laravel/framework/pull/55690
* [12.x] Support Virtual Properties When Serializing Models by [@beschoenen](https://github.com/beschoenen) in https://github.com/laravel/framework/pull/55691
* [12.X] Fix `Http::preventStrayRequests` error propagation when using `Http::pool` by [@LeTamanoir](https://github.com/LeTamanoir) in https://github.com/laravel/framework/pull/55689
* [12.x] incorrect use of generics in Schema\Builder by [@taka-oyama](https://github.com/taka-oyama) in https://github.com/laravel/framework/pull/55687
* [12.x] Add option to disable MySQL ssl when restoring or squashing migrations by [@andersonls](https://github.com/andersonls) in https://github.com/laravel/framework/pull/55683
* [12.x] Add `except` and `exceptHidden` methods to `Context` class by [@xurshudyan](https://github.com/xurshudyan) in https://github.com/laravel/framework/pull/55692
* [12.x] Container `currentlyResolving` utility by [@jrseliga](https://github.com/jrseliga) in https://github.com/laravel/framework/pull/55684
* [12.x] Container `currentlyResolving` test by [@jrseliga](https://github.com/jrseliga) in https://github.com/laravel/framework/pull/55694
* [12.x] Fix handling of default values for route parameters with a binding field by [@stancl](https://github.com/stancl) in https://github.com/laravel/framework/pull/55697
* Move Timebox for Authentication and add to password resets by [@valorin](https://github.com/valorin) in https://github.com/laravel/framework/pull/55701
* [12.x] perf: Optimize BladeCompiler by [@rzv-me](https://github.com/rzv-me) in https://github.com/laravel/framework/pull/55703
* [12.x] perf: support iterables for event discovery paths by [@calebdw](https://github.com/calebdw) in https://github.com/laravel/framework/pull/55699
* [12.x] Types: AuthorizesRequests::resourceAbilityMap by [@liamduckett](https://github.com/liamduckett) in https://github.com/laravel/framework/pull/55706
* [12.x] Add flexible support to memoized cache store by [@timacdonald](https://github.com/timacdonald) in https://github.com/laravel/framework/pull/55709
* [12.x] Introduce Arr::from() by [@daniser](https://github.com/daniser) in https://github.com/laravel/framework/pull/55715
* [12.x] Fix the `getCurrentlyAttachedPivots` wrong `morphClass` for morph to many relationships by [@amir9480](https://github.com/amir9480) in https://github.com/laravel/framework/pull/55721
* [12.x] Improve typehints for Http classes by [@cosmastech](https://github.com/cosmastech) in https://github.com/laravel/framework/pull/54783
* Add deleteWhen for throttle exceptions job middleware by [@moshe-autoleadstar](https://github.com/moshe-autoleadstar) in https://github.com/laravel/framework/pull/55718

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
