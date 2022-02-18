# Release Notes for 9.x

## [Unreleased](https://github.com/laravel/framework/compare/v9.1.0...9.x)


## [v9.1.0 (2022-02-15)](https://github.com/laravel/framework/compare/v9.0.2...v9.1.0)

### Added
* Added the ability to use the uniqueFor method for Jobs by @andrey-helldar in https://github.com/laravel/framework/pull/40974
* Add filtering of route:list by domain by @Synchro in https://github.com/laravel/framework/pull/40970
* Added dropForeignIdFor method to match foreignIdFor method by @bretto36 in https://github.com/laravel/framework/pull/40950
* Adds `Str::excerpt` by @nunomaduro in https://github.com/laravel/framework/pull/41000
* Make:model --morph flag to generate MorphPivot model by @michael-rubel in https://github.com/laravel/framework/pull/41011
* Add doesntContain to higher order proxies by @edemots in https://github.com/laravel/framework/pull/41034

### Changed
* Improve types on model factory methods by @axlon in https://github.com/laravel/framework/pull/40902
* Add support for passing array as the second parameter for the group method. by @hossein-zare in https://github.com/laravel/framework/pull/40945
* Makes `ExceptionHandler::renderForConsole` internal on contract by @nunomaduro in https://github.com/laravel/framework/pull/40956
* Put the error message at the bottom of the exceptions by @nshiro in https://github.com/laravel/framework/pull/40886
* Expose next and previous cursor of cursor paginator by @gdebrauwer in https://github.com/laravel/framework/pull/41001

### Fixed
* Fix FTP root config by @driesvints in https://github.com/laravel/framework/pull/40939
* Allows tls encryption to be used with port different than 465 with starttls by @nicolalazzaro in https://github.com/laravel/framework/pull/40943
* Catch suppressed deprecation logs by @nunomaduro in https://github.com/laravel/framework/pull/40942
* Fix typo in method documentation by @shadman-ahmed in https://github.com/laravel/framework/pull/40951
* Patch regex rule parsing due to `Rule::forEach()` by @stevebauman in https://github.com/laravel/framework/pull/40941
* Fix replacing request options by @driesvints in https://github.com/laravel/framework/pull/40954
* Fix `MessageSent` event by @driesvints in https://github.com/laravel/framework/pull/40963
* Add firstOr() function to BelongsToMany relation by @r-kujawa in https://github.com/laravel/framework/pull/40828
* Fix `isRelation()` failing to check an `Attribute` by @rodrigopedra in https://github.com/laravel/framework/pull/40967
* Fix default pivot attributes by @iamgergo in https://github.com/laravel/framework/pull/40947
* Fix enum casts arrayable behaviour by @diegotibi in https://github.com/laravel/framework/pull/40885
* Solve exception error: Undefined array key "", in artisan route:list command by @manuglopez in https://github.com/laravel/framework/pull/41031
* Fix Duplicate Route Namespace by @moisish in https://github.com/laravel/framework/pull/41021
* Fix the error message when no routes are detected by @LukeTowers in https://github.com/laravel/framework/pull/41017
* Fix mails with tags and metadata are not queuable by @joostdebruijn in https://github.com/laravel/framework/pull/41028


## [v9.0.2 (2022-02-10)](https://github.com/laravel/framework/compare/v9.0.1...v9.0.2)

### Added
* Add disabled directive by @belzaaron in https://github.com/laravel/framework/pull/40900

### Changed
* Widen the type of `Collection::unique` `$key` parameter by @NiclasvanEyk in https://github.com/laravel/framework/pull/40903
* Makes `ExceptionHandler::renderForConsole` internal by @nunomaduro in https://github.com/laravel/framework/pull/40936
* Removal of Google Font integration from default exception templates by @bashgeek in https://github.com/laravel/framework/pull/40926
* Allow base JsonResource class to be collected by @jwohlfert23 in https://github.com/laravel/framework/pull/40896

### Fixed
* Fix Support\Collection reject method type definition by @joecampo in https://github.com/laravel/framework/pull/40899
* Fix SpoofCheckValidation namespace change by @eduardor2k in https://github.com/laravel/framework/pull/40923
* Fix notification email recipient by @driesvints in https://github.com/laravel/framework/pull/40921
* Fix publishing visibility by @driesvints in https://github.com/laravel/framework/pull/40918
* Fix Mailable->priority() by @giggsey in https://github.com/laravel/framework/pull/40917


## [v9.0.1 (2022-02-09)](https://github.com/laravel/framework/compare/v9.0.0...v9.0.1)

### Changed
* Improves `Support\Collection` each method type definition by @zingimmick in https://github.com/laravel/framework/pull/40879

### Fixed
* Update Mailable.php by @rentalhost in https://github.com/laravel/framework/pull/40868
* Switch to null coalescing operator in Conditionable by @inxilpro in https://github.com/laravel/framework/pull/40888
* Bring back old return behaviour by @ankurk91 in https://github.com/laravel/framework/pull/40880


## [v9.0.0 (2022-02-08)](https://github.com/laravel/framework/compare/8.x...v9.0.0)

Check the upgrade guide in the [Official Laravel Upgrade Documentation](https://laravel.com/docs/9.x/upgrade). Also you can see some release notes in the [Official Laravel Release Documentation](https://laravel.com/docs/9.x/releases).
