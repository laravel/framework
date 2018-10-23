[![npm][npm]][npm-url]
[![node][node]][node-url]
[![deps][deps]][deps-url]
[![tests][tests]][tests-url]
[![coverage][cover]][cover-url]
[![code style][style]][style-url]
[![chat][chat]][chat-url]

<div align="center">
  <img width="100" height="100" title="Load Options"
    src="https://michael-ciniawsky.github.io/postcss-load-options/logo.svg"
  <a href="https://github.com/postcss/postcss">
    <img width="110" height="110" title="PostCSS"           src="http://postcss.github.io/postcss/logo.svg" hspace="10">
  </a>
  <h1>Load Options</h1>
</div>

<h2 align="center">Install</h2>

```bash
npm i -D postcss-load-options
```
<h2 align="center">Usage</h2>

### `package.json`

Create **`postcss`** section in your projects **`package.json`**.

```
App
  |– client
  |– public
  |
  |- package.json
```

```json
{
  "dependencies": {
    "sugarss": "0.2.0"
  },
  "postcss": {
    "parser": "sugarss",
    "map": false,
    "from": "path/to/src/file.css",
    "to": "path/to/dest/file.css"
  }
}
```

### `.postcssrc`

Create a **`.postcssrc`** file.

```
App
  |– client
  |– public
  |
  |- (.postcssrc|.postcssrc.json|.postcssrc.yaml)
  |- package.json
```

**`JSON`**
```json
{
  "parser": "sugarss",
  "map": false,
  "from": "path/to/src/file.css",
  "to": "path/to/dest/file.css"
}
```

**`YAML`**
```yaml
parser: sugarss
map: false
from: "/path/to/src.sss"
to: "/path/to/dest.css"
```

### `postcss.config.js` or `.postcssrc.js`

You may need some JavaScript logic to generate your config. For this case you can use a file named **`postcss.config.js`** or **`.postcssrc.js`**.

```
App
  |– client
  |– public
  |
  |- (postcss.config.js|.postcssrc.js)
  |- package.json
```

```js
module.exports = (ctx) => {
  return {
    parser: ctx.sugar ? 'sugarss' : false,
    map: ctx.env === 'development' ? ctx.map || false,
    from: 'path/to/src/file.css',
    to: 'path/to/dest/file.css'
  }
}
```

<h2 align="center">Options</h2>

**`parser`**:

```js
'parser': 'sugarss'
```

**`syntax`**:

```js
'syntax': 'postcss-scss'
```
**`stringifier`**:

```js
'stringifier': 'midas'
```

[**`map`**:](https://github.com/postcss/postcss/blob/master/docs/source-maps.md)

```js
'map': 'inline'
```

**`from`**:

```js
from: 'path/to/dest/file.css'
```

**`to`**:

```js
to: 'path/to/dest/file.css'
```

### Context

When using a function `(postcss.config.js)`, it is possible to pass context to `postcss-load-options`, which will be evaluated before loading your options. By default `ctx.env (process.env.NODE_ENV)` and `ctx.cwd (process.cwd())` are available.

<h2 align="center">Example</h2>

### <img width="80" height="80" src="https://worldvectorlogo.com/logos/nodejs-icon.svg">

```js
const { readFileSync } = require('fs')

const postcss = require('postcss')
const optionsrc = require('postcss-load-options')

const sss =  readFileSync('index.sss', 'utf8')

const ctx = { sugar: true,  map: 'inline' }

optionsrc(ctx).then((options) => {
  postcss()
    .process(sss, options)
    .then(({ css }) => console.log(css))
}))
```

<h2 align="center">Maintainers</h2>

<table>
  <tbody>
   <tr>
    <td align="center">
      <img width="150 height="150"
      src="https://avatars.githubusercontent.com/u/5419992?v=3&s=150">
      <br />
      <a href="https://github.com/michael-ciniawsky">Michael Ciniawsky</a>
    </td>
  </tr>
  <tbody>
</table>


[npm]: https://img.shields.io/npm/v/postcss-load-options.svg
[npm-url]: https://npmjs.com/package/postcss-load-options

[node]: https://img.shields.io/node/v/postcss-load-options.svg
[node-url]: https://nodejs.org/

[deps]: https://david-dm.org/michael-ciniawsky/postcss-load-options.svg
[deps-url]: https://david-dm.org/michael-ciniawsky/postcss-load-options

[tests]: http://img.shields.io/travis/michael-ciniawsky/postcss-load-options.svg
[tests-url]: https://travis-ci.org/michael-ciniawsky/postcss-load-options

[cover]: https://coveralls.io/repos/github/michael-ciniawsky/postcss-load-options/badge.svg
[cover-url]: https://coveralls.io/github/michael-ciniawsky/postcss-load-options

[style]: https://img.shields.io/badge/code%20style-standard-yellow.svg
[style-url]: http://standardjs.com/

[chat]: https://img.shields.io/gitter/room/postcss/postcss.svg
[chat-url]: https://gitter.im/postcss/postcss
