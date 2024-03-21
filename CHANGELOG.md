# Release Notes for 11.x

## [Unreleased](https://github.com/laravel/framework/compare/v11.0.8...11.x)

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
