# caniuse-api [![Build Status](https://travis-ci.org/Nyalab/caniuse-api.svg?branch=master)](https://travis-ci.org/Nyalab/caniuse-api) [![Build status](https://ci.appveyor.com/api/projects/status/6j3na522bv3bxfa5/branch/master?svg=true)](https://ci.appveyor.com/project/MoOx/caniuse-api/branch/master)

request the caniuse data to check browsers compatibilities

## Installation

```console
$ npm install caniuse-api --save
```

## Usage

```js
const caniuse = require('caniuse-api')

caniuse.getSupport('border-radius')
caniuse.isSupported('border-radius', 'ie 8, ie 9')
caniuse.setBrowserScope('> 5%, last 1 version')
caniuse.getSupport('border-radius')
// ...
```

## API

#### `caniuse.getSupport(feature)`

_ask since which browsers versions a feature is available_

* `y`: Since which browser version the feature is available
* `n`: Up to which browser version the feature is unavailable
* `a`: Up to which browser version the feature is partially supported
* `X`: Up to which browser version the feature is prefixed

```js
caniuse.getSupport('border-radius', true)
/*
[ safari: { y: 3.1, x: 4 },
  opera: { n: 10, y: 10.5 },
  ios_saf: { y: 3.2, x: 3.2 },
  ie_mob: { y: 10 },
  ie: { n: 8, y: 9 },
  firefox: { a: 2, x: 3.6, y: 3 },
  chrome: { y: 4, x: 4 },
  and_chr: { y: 39 } ]
*/
```

#### `caniuse.isSupported(feature, browsers)`

_ask if a feature is supported by some browsers_

```js
caniuse.isSupported('border-radius', 'ie 8, ie 9') // false
caniuse.isSupported('border-radius', 'ie 9') // true
```

#### `caniuse.find(query)`

_search for a caniuse feature name_

Ex:

```js
caniuse.find('radius') // ['border-radius']
caniuse.find('nothingness') // []
caniuse.find('css3')
/*
[ 'css3-boxsizing',
  'css3-colors',
  'css3-cursors-newer',
  'css3-cursors',
  'css3-tabsize' ]
*/
```

#### `caniuse.getLatestStableBrowsers()`

_get the current version for each browser_

```js
caniuse.getLatestStableBrowsers()
/*
[ 'safari 8',
  'opera 26',
  'ios_saf 8.1',
  'ie_mob 11',
  'ie 11',
  'firefox 33',
  'chrome 39' ]
*/
```

#### `caniuse.getBrowserScope()`

_returns a list of browsers currently used for the scope of operations_

```js
caniuse.getBrowserScope()
/*
[ 'safari',
  'opera',
  'op_mini',
  'ios_saf',
  'ie_mob',
  'ie',
  'firefox',
  'chrome',
  'android',
  'and_uc',
  'and_chr' ]
*/
```

#### `caniuse.setBrowserScope(browserscope)`

_if you do not like the default browser scope, you can set it globally by using this method_

* browserscope should be a 'autoprefixer' formatted string

```js
caniuse.setBrowserScope('> 5%, last 2 versions, Firefox ESR, Opera 12.1')
```


---

## [Changelog](CHANGELOG.md)

## [License](LICENSE)
