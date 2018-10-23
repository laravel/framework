[![npm][npm]][npm-url]
[![node][node]][node-url]
[![deps][deps]][deps-url]
[![tests][tests]][tests-url]
[![coverage][cover]][cover-url]
[![chat][chat]][chat-url]

<div align="center">
  <a href="https://github.com/webpack/webpack">
    <img width="200" height="200"
      src="https://webpack.js.org/assets/icon-square-big.svg">
  </a>
  <h1>webpack Dev Middleware</h1>
</div>

It's a simple wrapper middleware for webpack. It serves the files emitted from webpack over a connect server. This should be used for **development only**.

It has a few advantages over bundling it as files:

* No files are written to disk, it handle the files in memory
* If files changed in watch mode, the middleware no longer serves the old bundle, but delays requests until the compiling has finished. You don't have to wait before refreshing the page after a file modification.
* I may add some specific optimization in future releases.

<h2 align="center">Install</h2>

```
npm install webpack-dev-middleware --save-dev
```

<h2 align="center">Usage</h2>

``` javascript
var webpackMiddleware = require("webpack-dev-middleware");
app.use(webpackMiddleware(...));
```

Example usage:

``` javascript
app.use(webpackMiddleware(webpack({
	// webpack options
	// webpackMiddleware takes a Compiler object as first parameter
	// which is returned by webpack(...) without callback.
	entry: "...",
	output: {
		path: "/"
		// no real path is required, just pass "/"
		// but it will work with other paths too.
	}
}), {
	// publicPath is required, whereas all other options are optional

	noInfo: false,
	// display no info to console (only warnings and errors)

	quiet: false,
	// display nothing to the console

	lazy: true,
	// switch into lazy mode
	// that means no watching, but recompilation on every request

	watchOptions: {
		aggregateTimeout: 300,
		poll: true
	},
	// watch options (only lazy: false)

	publicPath: "/assets/",
	// public path to bind the middleware to
	// use the same as in webpack

	index: "index.html",
	// The index path for web server, defaults to "index.html".
	// If falsy (but not undefined), the server will not respond to requests to the root URL.

	headers: { "X-Custom-Header": "yes" },
	// custom headers

	mimeTypes: { "text/html": [ "phtml" ] },
	// Add custom mime/extension mappings
	// https://github.com/broofa/node-mime#mimedefine
	// https://github.com/webpack/webpack-dev-middleware/pull/150

	stats: {
		colors: true
	},
	// options for formating the statistics

	reporter: null,
	// Provide a custom reporter to change the way how logs are shown.

	serverSideRender: false,
	// Turn off the server-side rendering mode. See Server-Side Rendering part for more info.
}));
```

## Advanced API

This part shows how you might interact with the middleware during runtime:

* `close(callback)` - stop watching for file changes
	```js
	var webpackDevMiddlewareInstance = webpackMiddleware(/* see example usage */);
	app.use(webpackDevMiddlewareInstance);
	// After 10 seconds stop watching for file changes:
	setTimeout(function(){
	  webpackDevMiddlewareInstance.close();
	}, 10000);
	```

* `invalidate()` - recompile the bundle - e.g. after you changed the configuration
	```js
	var compiler = webpack(/* see example usage */);
	var webpackDevMiddlewareInstance = webpackMiddleware(compiler);
	app.use(webpackDevMiddlewareInstance);
	setTimeout(function(){
	  // After a short delay the configuration is changed
	  // in this example we will just add a banner plugin:
	  compiler.apply(new webpack.BannerPlugin('A new banner'));
	  // Recompile the bundle with the banner plugin:
	  webpackDevMiddlewareInstance.invalidate();
	}, 1000);
	```

* `waitUntilValid(callback)` - executes the `callback` if the bundle is valid or after it is valid again:
	```js
	var webpackDevMiddlewareInstance = webpackMiddleware(/* see example usage */);
	app.use(webpackDevMiddlewareInstance);
	webpackDevMiddlewareInstance.waitUntilValid(function(){
	  console.log('Package is in a valid state');
	});
	```

## Server-Side Rendering

**Note: this feature is experimental and may be removed or changed completely in the future.**

In order to develop a server-side rendering application, we need access to the [`stats`](https://github.com/webpack/docs/wiki/node.js-api#stats), which is generated with the latest build.

In the server-side rendering mode, __webpack-dev-middleware__ would sets the `stat` to `res.locals.webpackStats` before invoking the next middleware, where we can render pages and response to clients.

Notice that requests for bundle files would still be responded by __webpack-dev-middleware__ and all requests will be pending until the building process is finished in the server-side rendering mode.

```javascript
// This function makes server rendering of asset references consistent with different webpack chunk/entry confiugrations
function normalizeAssets(assets) {
  return Array.isArray(assets) ? assets : [assets]
}

app.use(webpackMiddleware(compiler, { serverSideRender: true })

// The following middleware would not be invoked until the latest build is finished.
app.use((req, res) => {
  
  const assetsByChunkName = res.locals.webpackStats.toJson().assetsByChunkName
  
  // then use `assetsByChunkName` for server-sider rendering
  // For example, if you have only one main chunk:

	res.send(`
<html>
  <head>
    <title>My App</title>
		${
			normalizeAssets(assetsByChunkName.main)
			.filter(path => path.endsWith('.css'))
			.map(path => `<link rel="stylesheet" href="${path}" />`)
			.join('\n')
		}
  </head>
  <body>
    <div id="root"></div>
		${
			normalizeAssets(assetsByChunkName.main)
			.filter(path => path.endsWith('.js'))
			.map(path => `<script src="${path}"></script>`)
			.join('\n')
		}
  </body>
</html>		
	`)

})
```

<h2 align="center">Contributing</h2>

Don't hesitate to create a pull request. Every contribution is appreciated. In development you can start the tests by calling `npm test`.

<h2 align="center">Maintainers</h2>

<table>
  <tbody>
    <tr>
      <td align="center">
        <img width="150 height="150"
        src="https://avatars.githubusercontent.com/SpaceK33z?v=3">
        <br />
        <a href="https://github.com/SpaceK33z">Kees Kluskens</a>
      </td>
    <tr>
  <tbody>
</table>


<h2 align="center">LICENSE</h2>

#### [MIT](./LICENSE)

[npm]: https://img.shields.io/npm/v/webpack-dev-middleware.svg
[npm-url]: https://npmjs.com/package/webpack-dev-middleware

[node]: https://img.shields.io/node/v/webpack-dev-middleware.svg
[node-url]: https://nodejs.org

[deps]: https://david-dm.org/webpack/webpack-dev-middleware.svg
[deps-url]: https://david-dm.org/webpack/webpack-dev-middleware

[tests]: http://img.shields.io/travis/webpack/webpack-dev-middleware.svg
[tests-url]: https://travis-ci.org/webpack/webpack-dev-middleware

[cover]: https://codecov.io/gh/webpack/webpack-dev-middleware/branch/master/graph/badge.svg
[cover-url]: https://codecov.io/gh/webpack/webpack-dev-middleware

[chat]: https://badges.gitter.im/webpack/webpack.svg
[chat-url]: https://gitter.im/webpack/webpack
