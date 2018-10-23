# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

<a name="1.0.1"></a>
## [1.0.1](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0...v1.0.1) (2017-10-24)


### Bug Fixes

* **minify:** `nameCache` assignment (`uglifyOptions.nameCache`) ([#147](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/147)) ([af11e8e](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/af11e8e))



<a name="1.0.0"></a>
# [1.0.0](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0-rc.0...v1.0.0) (2017-10-23)


### Features

* update to `uglify-es` ([#63](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/63)) ([1d62560](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/1d62560))

* add support for `parallelization` && `caching` (`options.parallel`) ([#77](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/77)) ([ee16639](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/ee16639))
* **index:** add `options` validation (`schema-utils`) ([#80](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/80)) ([f19b2de](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/f19b2de))


### Bug Fixes

* **deps:** cacache@10 with ISC licence ([#145](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/145)) ([9331034](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/9331034))
* typo "filterd" -> "filtered" ([#37](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/37)) ([238c373](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/238c373))
* **package:** mv uglify2 to `dependencies` && update `peerDependencies` ([#45](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/45)) ([93b0cd2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/93b0cd2))
* **uglify:** use Compress API not ast.transform ([990f2e2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/990f2e2))


### Code Refactoring

* apply `webpack-defaults` ([#35](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/35)) ([f6c5aa9](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/f6c5aa9))


### BREAKING CHANGES

* Enforces `peerDependencies` of `"webpack": ">= 3.0.0-rc.0 || ^3.0.0"`.
* Enforces `engines` of `"node": ">=4.3.0 < 5.0.0 || >= 5.10`



<a name="1.0.0-rc.0"></a>
# [1.0.0-rc.0](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0-beta.2...v1.0.0-rc.0) (2017-10-23)



<a name="1.0.0-beta.3"></a>
# [1.0.0-beta.3](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0-beta.2...v1.0.0-beta.3) (2017-09-29)



<a name="1.0.0-beta.2"></a>
# [1.0.0-beta.2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0-beta.1...v1.0.0-beta.2) (2017-07-21)


### Features

* add support for `parallelization` && `caching` (`options.parallel`) ([#77](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/77)) ([ee16639](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/ee16639))
* **index:** add `options` validation (`schema-utils`) ([#80](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/80)) ([f19b2de](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/f19b2de))



<a name="1.0.0-beta.1"></a>
# [1.0.0-beta.1](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v1.0.0-beta.0...v1.0.0-beta.1) (2017-07-06)


### Features

* update to `uglify-es` ([#63](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/63)) ([1d62560](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/1d62560))



<a name="1.0.0-beta.0"></a>
# [1.0.0-beta.0](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v0.1.4...v1.0.0-beta.0) (2017-06-29)


### Bug Fixes

* typo "filterd" -> "filtered" ([#37](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/37)) ([238c373](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/238c373))
* **package:** mv uglify2 to `dependencies` && update `peerDependencies` ([#45](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/45)) ([93b0cd2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/93b0cd2))
* **uglify:** use Compress API not ast.transform ([990f2e2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/990f2e2))


### Code Refactoring

* apply `webpack-defaults` ([#35](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/35)) ([f6c5aa9](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/f6c5aa9))


### BREAKING CHANGES

* Enforces `peerDependencies` of `"webpack": ">= 3.0.0-rc.0 || ^3.0.0"`.
* Enforces `engines` of `"node": ">=4.3.0 < 5.0.0 || >= 5.10`



<a name="0.4.6"></a>
## [0.4.6](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/compare/v0.1.4...v0.4.6) (2017-06-29)


### Bug Fixes

* typo "filterd" -> "filtered" ([#37](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/37)) ([238c373](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/238c373))
* **package:** mv uglify2 to `dependencies` && update `peerDependencies` ([#45](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/issues/45)) ([93b0cd2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/93b0cd2))
* **uglify:** use Compress API not ast.transform ([990f2e2](https://github.com/webpack-contrib/uglifyjs-webpack-plugin/commit/990f2e2))
