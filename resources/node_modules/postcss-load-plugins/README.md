[![npm][npm]][npm-url]
[![node][node]][node-url]
[![deps][deps]][deps-url]
[![tests][tests]][tests-url]
[![coverage][cover]][cover-url]
[![code style][style]][style-url]
[![chat][chat]][chat-url]

<div align="center">
  <img width="100" height="100" title="Load Plugins" src="http://michael-ciniawsky.github.io/postcss-load-plugins/logo.svg">
  <a href="https://github.com/postcss/postcss">
    <img width="110" height="110" title="PostCSS"           src="http://postcss.github.io/postcss/logo.svg" hspace="10">
  </a>
  <h1>Load Plugins</h1>
</div>

<h2 align="center">Install</h2>

```bash
npm i -D postcss-load-plugins
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
    "plugins": {
      "postcss-plugin": {}
    }
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
  "plugins": {
    "postcss-plugin": {}
  }
}
```

**`YAML`**
```yaml
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

Plugins can be loaded in either using an `{Object}` or an `{Array}`.

##### `{Object}`

```js
module.exports = (ctx) => ({
  plugins: {
    'postcss-plugin': ctx.plugin
  }
})
```

##### `{Array}`

```js
module.exports = (ctx) => ({
  plugins: [
    require('postcss-plugin')(ctx.plugin)
  ]
})
```

<h2 align="center">Options</h2>

Plugin **options** can take the following values.

**`{}`: Plugin loads with defaults**

```js
'postcss-plugin': {} || null
```

> :warning: `{}` must be an **empty** object

**`{Object}`: Plugin loads with options**

```js
'postcss-plugin': { option: '', option: '' }
```

**`false`: Plugin will not be loaded**

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

### Context

When using a function `(postcss.config.js)`, it is possible to pass context to `postcss-load-plugins`, which will be evaluated before loading your plugins. By default `ctx.env (process.env.NODE_ENV)` and `ctx.cwd (process.cwd())` are available.

<h2 align="center">Examples</h2>

**`postcss.config.js`**

```js
module.exports = (ctx) => ({
  plugins: {
    postcss-import: {},
    postcss-modules: ctx.modules ? {} : false,
    cssnano: ctx.env === 'production' ? {} : false
  }
})
```

### <img width="80" height="80" src="https://worldvectorlogo.com/logos/nodejs-icon.svg">

```js
const { readFileSync } = require('fs')

const postcss = require('postcss')
const pluginsrc = require('postcss-load-plugins')

const css = readFileSync('index.css', 'utf8')

const ctx = { modules: true }

pluginsrc(ctx).then((plugins) => {
  postcss(plugins)
    .process(css)
    .then((result) => console.log(result.css))
})
```

<h2 align="center">Maintainers</h2>

<table>
  <tbody>
    <tr>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/michael-ciniawsky.png?v=3&s=150">
        <br>
        <a href="https://github.com/michael-ciniawsky">Michael Ciniawsky</a>
      </td>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/ertrzyiks.png?v=3&s=150">
        <br>
        <a href="https://github.com/ertrzyiks">Mateusz Derks</a>
      </td>
    </tr>
  </tbody>
</table>

<h2 align="center">Contributors</h2>

<table>
  <tbody>
    <tr>
      <td align="center">
        <img width="150" height="150"
        src="https://github.com/Kovensky.png?v=3&s=150">
        <br>
        <a href="https://github.com/Kovensky">Diogo Franco</a>
      </td>
    </tr>
  </tbody>
</table>


[npm]: https://img.shields.io/npm/v/postcss-load-plugins.svg
[npm-url]: https://npmjs.com/package/postcss-load-plugins

[node]: https://img.shields.io/node/v/postcss-load-plugins.svg
[node-url]: https://nodejs.org/

[deps]: https://david-dm.org/michael-ciniawsky/postcss-load-plugins.svg
[deps-url]: https://david-dm.org/michael-ciniawsky/postcss-load-plugins

[tests]: http://img.shields.io/travis/michael-ciniawsky/postcss-load-plugins.svg
[tests-url]: https://travis-ci.org/michael-ciniawsky/postcss-load-plugins

[cover]: https://coveralls.io/repos/github/michael-ciniawsky/postcss-load-plugins/badge.svg
[cover-url]: https://coveralls.io/github/michael-ciniawsky/postcss-load-plugins

[style]: https://img.shields.io/badge/code%20style-standard-yellow.svg
[style-url]: http://standardjs.com/

[chat]: https://img.shields.io/gitter/room/postcss/postcss.svg
[chat-url]: https://gitter.im/postcss/postcss
