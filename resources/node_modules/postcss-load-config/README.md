[![npm][npm]][npm-url]
[![node][node]][node-url]
[![deps][deps]][deps-url]
[![tests][tests]][tests-url]
[![coverage][cover]][cover-url]
[![code style][style]][style-url]
[![chat][chat]][chat-url]

<div align="center">
  <img width="100" height="100" title="Load Options" src="http://michael-ciniawsky.github.io/postcss-load-options/logo.svg">
  <a href="https://github.com/postcss/postcss">
    <img width="110" height="110" title="PostCSS"           src="http://postcss.github.io/postcss/logo.svg" hspace="10">
  </a>
  <img width="100" height="100" title="Load Plugins" src="http://michael-ciniawsky.github.io/postcss-load-plugins/logo.svg">
  <h1>Load Config</h1>
</div>

<h2 align="center">Install</h2>

```bash
npm i -D postcss-load-config
```

<h2 align="center">Usage</h2>

```
npm i -S|-D postcss-plugin
```

Install plugins and save them to your ***package.json*** dependencies/devDependencies.

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
  "postcss": {
    "parser": "sugarss",
    "map": false,
    "from": "/path/to/src.sss",
    "to": "/path/to/dest.css",
    "plugins": {
      "postcss-plugin": {}
    }
  }
}
```

### `.postcssrc`

Create a **`.postcssrc`** file in JSON or YAML format.

It's also allowed to use extensions (**`.postcssrc.json`** or **`.postcssrc.yaml`**). That could help your text editor to properly interpret the file.

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
  "from": "/path/to/src.sss",
  "to": "/path/to/dest.css",
  "plugins": {
    "postcss-plugin": {}
  }
}
```

**`YAML`**
```yaml
parser: sugarss
map: false
from: "/path/to/src.sss"
to: "/path/to/dest.css"
plugins:
  postcss-plugin: {}
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

You can export the config as an `{Object}`

```js
module.exports = {
  parser: 'sugarss',
  map: false,
  from: '/path/to/src.sss',
  to: '/path/to/dest.css',
  plugins: {
    'postcss-plugin': {}
  }
}
```

Or export a `{Function}` that returns the config (more about the param `ctx` below)

```js
module.exports = (ctx) => ({
  parser: ctx.parser ? 'sugarss' : false,
  map: ctx.env === 'development' ? ctx.map : false,
  from: ctx.from,
  to: ctx.to,
  plugins: {
    'postcss-plugin': ctx.plugin
  }
})
```

Plugins can be loaded in either using an `{Object}` or an `{Array}`.

##### `{Object}`

```js
module.exports = (ctx) => ({
  ...options
  plugins: {
    'postcss-plugin': ctx.plugin
  }
})
```

##### `{Array}`

```js
module.exports = (ctx) => ({
  ...options
  plugins: [
    require('postcss-plugin')(ctx.plugin)
  ]
})
```
> :warning: When using an Array, make sure to `require()` them.

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
from: 'path/to/src.css'
```

**`to`**:

```js
to: 'path/to/dest.css'
```

<h2 align="center">Plugins</h2>

### Options

**`{} || null`**: Plugin loads with defaults.

```js
'postcss-plugin': {} || null
```
> :warning: `{}` must be an **empty** object

**`[Object]`**: Plugin loads with given options.

```js
'postcss-plugin': { option: '', option: '' }
```

**`false`**: Plugin will not be loaded.

```js
'postcss-plugin': false
```

### Order

Plugin **order** is determined by declaration in the plugins section.

```js
{
  plugins: {
    'postcss-plugin': {}, // plugins[0]
    'postcss-plugin': {}, // plugins[1]
    'postcss-plugin': {}  // plugins[2]
  }
}
```

<h2 align="center">Context</h2>

When using a function (`postcss.config.js` or `.postcssrc.js`), it is possible to pass context to `postcss-load-config`, which will be evaluated while loading your config. By default `ctx.env (process.env.NODE_ENV)` and `ctx.cwd (process.cwd())` are available.

<h2 align="center">Examples</h2>

**postcss.config.js**

```js
module.exports = (ctx) => ({
  parser: ctx.parser ? 'sugarss' : false,
  map: ctx.env === 'development' ? ctx.map : false,
  plugins: {
    'postcss-import': {},
    'postcss-nested': {},
    cssnano: ctx.env === 'production' ? {} : false
  }
})
```

### <img width="80" height="80" src="https://worldvectorlogo.com/logos/nodejs-icon.svg">

```json
"scripts": {
  "build": "NODE_ENV=production node postcss",
  "start": "NODE_ENV=development node postcss"
}
```

```js
const { readFileSync } = require('fs')

const postcss = require('postcss')
const postcssrc = require('postcss-load-config')

const css = readFileSync('index.sss', 'utf8')

const ctx = { parser: true, map: 'inline' }

postcssrc(ctx).then(({ plugins, options }) => {
  postcss(plugins)
    .process(css, options)
    .then((result) => console.log(result.css))
})
```

### <img width="80" height="80" src="https://worldvectorlogo.com/logos/gulp.svg">

```json
"scripts": {
  "build": "NODE_ENV=production gulp",
  "start": "NODE_ENV=development gulp"
}
```

```js
const { task, src, dest, series, watch } = require('gulp')

const postcss = require('gulp-postcssrc')

const css = () => {
  src('src/*.css')
    .pipe(postcss())
    .pipe(dest('dest'))
})

task('watch', () => {
  watch(['src/*.css', 'postcss.config.js'], css)
})

task('default', series(css, 'watch'))
```

### <img width="80" height="80" src="https://worldvectorlogo.com/logos/webpack.svg">

```json
"scripts": {
  "build": "NODE_ENV=production webpack",
  "start": "NODE_ENV=development webpack-dev-server"
}
```

```js
module.exports = (env) => ({
  module: {
    rules: [
      {
        test: /\.css$/
        use: [
          'style-loader',
          {
            loader: 'css-loader',
            options: { importLoaders: 1 } }
          },
          'postcss-loader'
        ]
      }
    ]
  }
})
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
    <td align="center">
      <img width="150 height="150"
        src="https://avatars.githubusercontent.com/u/2437969?v=3&s=150">
      <br />
      <a href="https://github.com/ertrzyiks">Mateusz Derks</a>
    </td>
  </tr>
  <tbody>
</table>

<h2 align="center">Contributors</h2>

<table>
  <tbody>
   <tr>
    <td align="center">
      <img width="150" height="150"
        src="https://avatars.githubusercontent.com/u/1483538?v=3&s=150">
      <br />
      <a href="https://github.com/sparty02">Ryan Dunckel</a>
    </td>
    <td align="center">
      <img width="150" height="150"
        src="https://avatars.githubusercontent.com/u/6249643?v=3&s=150">
      <br />
      <a href="https://github.com/pcgilday">Patrick Gilday</a>
    </td>
    <td align="center">
      <img width="150" height="150"
        src="https://avatars.githubusercontent.com/u/5603632?v=3&s=150">
      <br />
      <a href="https://github.com/daltones">Dalton Santos</a>
    </td>
  </tr>
  <tbody>
</table>

[npm]: https://img.shields.io/npm/v/postcss-load-config.svg
[npm-url]: https://npmjs.com/package/postcss-load-config

[node]: https://img.shields.io/node/v/postcss-load-plugins.svg
[node-url]: https://nodejs.org/

[deps]: https://david-dm.org/michael-ciniawsky/postcss-load-config.svg
[deps-url]: https://david-dm.org/michael-ciniawsky/postcss-load-config

[style]: https://img.shields.io/badge/code%20style-standard-yellow.svg
[style-url]: http://standardjs.com/

[tests]: http://img.shields.io/travis/michael-ciniawsky/postcss-load-config.svg
[tests-url]: https://travis-ci.org/michael-ciniawsky/postcss-load-config

[cover]: https://coveralls.io/repos/github/michael-ciniawsky/postcss-load-config/badge.svg
[cover-url]: https://coveralls.io/github/michael-ciniawsky/postcss-load-config

[chat]: https://img.shields.io/gitter/room/postcss/postcss.svg
[chat-url]: https://gitter.im/postcss/postcss
