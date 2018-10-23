[![npm][npm]][npm-url]
[![node][node]][node-url]
[![deps][deps]][deps-url]
[![test][test]][test-url]
[![coverage][cover]][cover-url]
[![chat][chat]][chat-url]

<div align="center">
  <a href="http://json-schema.org">
    <!-- src="https://webpack-contrib.github.io/schema-utils/logo.png" -->
    <img width="180" height="180"
      src="https://raw.githubusercontent.com/json-schema-org/json-schema-org.github.io/master/img/logo.png">
  </a>
  <a href="https://github.com/webpack/webpack">
    <img width="200" height="200" hspace="10"
      src="https://webpack.js.org/assets/icon-square-big.svg">
  </a>
  <h1>Schema Utils</h1>
</div>

<h2 align="center">Install</h2>

```bash
npm install --save schema-utils
```

<h2 align="center">Usage</h2>

### `validateOptions`

```js
import validateOptions from 'schema-utils'

validateOptions('path/to/schema.json', options, 'Loader/Plugin Name')
```

<h2 align="center">Examples</h2>

### Loader

```js
import { getOptions } from 'loader-utils'
import validateOptions from 'schema-utils'

function loader (src, map) {
  const options = getOptions(this) || {}

  validateOptions('path/to/schema.json', options, 'Loader Name')
}
```

### Plugin

```js
import Tapable from 'tapable'
import validateOptions from 'schema-utils'

class Plugin extends Tapable {
  constructor (options) {
    validateOptions('path/to/schema.json', options, 'Plugin Name')
  }
}
```

<h2 align="center">Maintainers</h2>

<table>
  <tbody>
    <tr>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/bebraw.png?v=3&s=150">
        </br>
        <a href="https://github.com/bebraw">Juho Vepsäläinen</a>
      </td>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/d3viant0ne.png?v=3&s=150">
        </br>
        <a href="https://github.com/d3viant0ne">Joshua Wiens</a>
      </td>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/michael-ciniawsky.png?v=3&s=150">
        </br>
        <a href="https://github.com/michael-ciniawsky">Michael Ciniawsky</a>
      </td>
    </tr>
  <tbody>
</table>


[npm]: https://img.shields.io/npm/v/schema-utils.svg
[npm-url]: https://npmjs.com/package/schema-utils

[node]: https://img.shields.io/node/v/schema-utils.svg
[node-url]: https://nodejs.org

[deps]: https://david-dm.org/webpack-contrib/schema-utils.svg
[deps-url]: https://david-dm.org/webpack-contrib/schema-utils

[test]: http://img.shields.io/travis/webpack-contrib/schema-utils.svg
[test-url]: https://travis-ci.org/webpack-contrib/schema-utils

[cover]: https://codecov.io/gh/webpack-contrib/schema-utils/branch/master/graph/badge.svg
[cover-url]: https://codecov.io/gh/webpack-contrib/schema-utils

[chat]: https://img.shields.io/badge/gitter-webpack%2Fwebpack-brightgreen.svg
[chat-url]: https://gitter.im/webpack/webpack
