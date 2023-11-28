# Release Notes for 11.x

<<<<<<< HEAD
<<<<<<< HEAD
## [Unreleased](https://github.com/laravel/framework/compare/v11.0.0..master)
=======
## [Unreleased](https://github.com/laravel/framework/compare/v10.30.1...10.x)
=======
## [Unreleased](https://github.com/laravel/framework/compare/v10.33.0...10.x)

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
>>>>>>> 10.x

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
>>>>>>> 10.x


## [v11.0.0 (2023-??-??)](https://github.com/laravel/framework/compare/v11.0.0...master)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/11.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/11.x/releases).
