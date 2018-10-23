# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

<a name="3.0.2"></a>
## [3.0.2](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.1...v3.0.2) (2017-10-25)


### Bug Fixes

* refer to the `entrypoint` instead of the first `module` (`module.identifier`)  ([#601](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/601)) ([d5a1de2](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/d5a1de2))



<a name="3.0.1"></a>
## [3.0.1](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0...v3.0.1) (2017-10-03)


### Bug Fixes

* **index:** stricter check for `shouldExtract !== wasExtracted` ([#605](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/605)) ([510704f](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/510704f))
* get real path from `__filename` instead of `__dirname` (`NS`) ([8de6558](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/8de6558))



<a name="3.0.0"></a>
# [3.0.0](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v2.1.2...v3.0.0) (2017-07-10)


### Bug Fixes

* add missing `options.ignoreOrder` details in Error message ([#539](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/539)) ([dd43832](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/dd43832))


### Code Refactoring

* Apply webpack-defaults & webpack 3.x support ([#540](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/540)) ([7ae32d9](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/7ae32d9))


### BREAKING CHANGES

* Enforces `engines` of `"node": ">=4.3.0 < 5.0.0 || >= 5.10`

- refactor: DeprecationWarning: Chunk.modules [543](https://github.com/webpack-contrib/extract-text-webpack-plugin/pull/543)
* Updates to `Chunk.mapModules`. This release is not backwards compatible with `Webpack 2.x` due to breaking changes in webpack/webpack#4764

- fix: css generation order issue see: webpack/webpack#5225
* Enforces `peerDependencies` of `"webpack": "^3.1.0"`. 



<a name="3.0.0-rc.2"></a>
# [3.0.0-rc.2](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-rc.1...v3.0.0-rc.2) (2017-07-10)


### Bug Fixes

* Modules shouldn't be passed to sort ([#568](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/568)) ([113cabb](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/113cabb))



<a name="3.0.0-rc.1"></a>
# [3.0.0-rc.1](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-rc.0...v3.0.0-rc.1) (2017-07-07)


### Bug Fixes

* Module sorting ([27e3a28](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/27e3a28))



<a name="3.0.0-rc.0"></a>
# [3.0.0-rc.0](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-beta.3...v3.0.0-rc.0) (2017-07-07)


### Code Refactoring

* Update deprecated `chunk.modules` functions ([#553](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/553)) ([be7936d](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/be7936d))


### BREAKING CHANGES

* Updates to `Chunk.mapModules | forEachModule | getNumberOfModules`. This release is not backwards compatible with `Webpack 2.x` due to breaking changes in webpack/webpack#4764



<a name="3.0.0-beta.3"></a>
# [3.0.0-beta.3](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-beta.2...v3.0.0-beta.3) (2017-06-24)

### Bug Fixes

* Distribute schema with package ([5d0c28f](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/5d0c28f))


<a name="3.0.0-beta.2"></a>
# [3.0.0-beta.2](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-beta.1...v3.0.0-beta.2) (2017-06-24)

 * Skipped due to deployment issues with schema

<a name="3.0.0-beta.1"></a>
# [3.0.0-beta.1](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v3.0.0-beta.0...v3.0.0-beta.1) (2017-06-24)


 * Skipped due to deployment issues with schema


<a name="3.0.0-beta.0"></a>
# [3.0.0-beta.0](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v2.1.2...v3.0.0-beta.0) (2017-06-23)


### Bug Fixes

* add missing `options.ignoreOrder` details in Error message ([#539](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/539)) ([dd43832](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/dd43832))


### Code Refactoring

* Apply webpack-defaults ([#542](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/542)) ([292e217](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/292e217))
* Chunk.modules deprecation warning ([28171b2](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/28171b2))


### BREAKING CHANGES

* Updates to `Chunk.mapModules`. This release is not backwards compatible with `Webpack 2.x` due to breaking changes in webpack/webpack#4764
* Enforces `peerDependencies` of `"webpack": ">= 3.0.0-rc.0 || ^3.0.0"`.
* Enforces `engines` of `"node": ">=4.3.0 < 5.0.0 || >= 5.10`



<a name="2.1.2"></a>
## [2.1.2](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v2.1.1...v2.1.2) (2017-06-08)



<a name="2.1.1"></a>
## [2.1.1](https://github.com/webpack-contrib/extract-text-webpack-plugin/compare/v2.1.0...v2.1.1) (2017-06-08)


### Bug Fixes

* add a not null check for the content property before throwing error ([#404](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/404)) ([58dd5d3](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/58dd5d3))
* **loader:** rm unnecessary `this.cacheable` (caching) ([#530](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/530)) ([c3cb091](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/c3cb091))
* don't extract from common async chunks ([#508](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/508)) ([e595417](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/e595417))
* validation schema (`schema-utils`) ([#527](https://github.com/webpack-contrib/extract-text-webpack-plugin/issues/527)) ([dfeb347](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/dfeb347))



<a name="2.1.0"></a>
# [2.1.0](https://github.com/webpack/extract-text-webpack-plugin/compare/v2.0.0...v2.1.0) (2017-03-05)

### Features

* The plugin **filename** accepts a function now. [c9a19ad](https://github.com/webpack-contrib/extract-text-webpack-plugin/commit/c9a19ad), closes [#423](https://github.com/webpack-contrib/extract-text-webpack-plugin/pull/423)

<a name="2.0.0"></a>
# [2.0.0](https://github.com/webpack/extract-text-webpack-plugin/compare/v2.0.0-rc.3...v2.0.0) (2017-02-24)

<a name="2.0.0-rc.2"></a>
# [2.0.0-rc.2](https://github.com/webpack/extract-text-webpack-plugin/compare/v2.0.0-rc.1...v2.0.0-rc.2) (2017-01-28)


### Bug Fixes

* **schema:** allow `extract` to accept omit/remove flags ([8ce93d5](https://github.com/webpack/extract-text-webpack-plugin/commit/8ce93d5)), closes [#371](https://github.com/webpack/extract-text-webpack-plugin/issues/371)
* **schema:** connect loader schema with the code properly ([03bb4aa](https://github.com/webpack/extract-text-webpack-plugin/commit/03bb4aa))
* **schema:** emit proper error messages ([70cbd4b](https://github.com/webpack/extract-text-webpack-plugin/commit/70cbd4b))


### Features

* **errors:** show nicer errors if there are extra fields ([76a171d](https://github.com/webpack/extract-text-webpack-plugin/commit/76a171d))



<a name="2.0.0-rc.1"></a>
# [2.0.0-rc.1](https://github.com/webpack/extract-text-webpack-plugin/compare/v2.0.0-rc.0...v2.0.0-rc.1) (2017-01-28)


### Bug Fixes

* **options:** pass proper loader options to children ([#266](https://github.com/webpack/extract-text-webpack-plugin/issues/266)) ([6abf42d](https://github.com/webpack/extract-text-webpack-plugin/commit/6abf42d))



<a name="2.0.0-rc.0"></a>
# [2.0.0-rc.0](https://github.com/webpack/extract-text-webpack-plugin/compare/v2.0.0-beta.5...v2.0.0-rc.0) (2017-01-26)


### Bug Fixes

* **readme:** Incorrect loader configuration ([e477cc7](https://github.com/webpack/extract-text-webpack-plugin/commit/e477cc7))


### Features

* **extract:** return an array of loader objects ([#343](https://github.com/webpack/extract-text-webpack-plugin/issues/343)) ([74b86e0](https://github.com/webpack/extract-text-webpack-plugin/commit/74b86e0))



# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.
