# Release Notes for 5.7.x

## v5.7.2 (2018-09-06)

### Added
- Added `moontoast/math` suggestion to `Support` module ([#79edf5c70c9a54c75e17da62ba3649f24b874e09](https://github.com/laravel/framework/commit/79edf5c70c9a54c75e17da62ba3649f24b874e09))
- Send an event when the user's email is verified ([#045cbfd95c611928aef1b877d1a3dc60d5f19580](https://github.com/laravel/framework/commit/045cbfd95c611928aef1b877d1a3dc60d5f19580))
- Allow email verification middleware to work with API routes ([#0e23b6afa4d1d8b440ce7696a23fa770b4f7e5e3](https://github.com/laravel/framework/commit/0e23b6afa4d1d8b440ce7696a23fa770b4f7e5e3))
- Add Builder::whereJsonLength() ([#5e33a96cd5fe9f5bea953a3e07ec827d5f19a9a3](https://github.com/laravel/framework/commit/5e33a96cd5fe9f5bea953a3e07ec827d5f19a9a3), [#f149fbd0fede21fc3a8c0347d1ab9ee858727bb4](https://github.com/laravel/framework/commit/f149fbd0fede21fc3a8c0347d1ab9ee858727bb4))
- Pass configuration key parameter to updatePackageArray in Preset ([#25457](https://github.com/laravel/framework/pull/25457))
- Let the WorkCommand specify whether to stop when queue is empty ([#2524c5ee89a0c5e6e4e65c13d5f9945075bb299c](https://github.com/laravel/framework/commit/2524c5ee89a0c5e6e4e65c13d5f9945075bb299c))

### Changed
- Make email verification scaffolding translatable ([#25473](https://github.com/laravel/framework/pull/25473))
- Do not mock console output by default ([#b4339702dbdc5f1f55f30f1e6576450f6277e3ae](https://github.com/laravel/framework/commit/b4339702dbdc5f1f55f30f1e6576450f6277e3ae))
- Allow daemon to stop when there is no more jobs in the queue ([#157a15080b95b26b2ccb0677dceab4964e25f18d](https://github.com/laravel/framework/commit/157a15080b95b26b2ccb0677dceab4964e25f18d))
  
### Fixed
- Do not send email verification if user is already verified ([#25450](https://github.com/laravel/framework/pull/25450))
- Fixed required carbon version ([#394f79f9a6651b103f6e065cb4470b4b347239ea](https://github.com/laravel/framework/commit/394f79f9a6651b103f6e065cb4470b4b347239ea))

## v5.7.1 (2018-09-04)

### Fixed
- Fixed an issue with basic auth when no field is defined

### Changed
- Remove X-UA-Compatible meta tag ([#25442](https://github.com/laravel/framework/pull/25442))
- Added default array value for redis config ([#25443](https://github.com/laravel/framework/pull/25443))

## v5.7.0 (2018-09-04)

Check the upgrade guide in the [Official Laravel Documentation](https://laravel.com/docs/5.7/upgrade).
