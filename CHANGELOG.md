# Release Notes for 11.x

<<<<<<< HEAD
## [Unreleased](https://github.com/laravel/framework/compare/v11.0.0..master)
=======
## [Unreleased](https://github.com/laravel/framework/compare/v10.30.1...10.x)

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
