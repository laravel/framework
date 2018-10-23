# Resolve URL Loader

[![NPM](https://nodei.co/npm/resolve-url-loader.png)](http://github.com/bholloway/resolve-url-loader)

Webpack loader that resolves relative paths in url() statements based on the original source file.

Use in conjunction with the [sass-loader](https://www.npmjs.com/package/sass-loader) and specify your asset `url()` relative to the `.scss` file in question.

This loader will use the source-map from the SASS compiler to locate the original `.scss` source file and write a more Webpack-friendly path for your asset. The CSS loader can then locate your asset for individual processing.


## Getting started

```bash
# via yarn
yarn add resolve-url-loader --dev

# via npm
npm install --save resolve-url-loader --dev
```


## Usage

Plain CSS works fine:

``` javascript
var css = require('!css-loader!resolve-url-loader!./file.css');
```

or using [sass-loader](https://github.com/jtangelder/sass-loader):

``` javascript
var css = require('!css-loader!resolve-url-loader!sass-loader?sourceMap!./file.scss');
```

Use in tandem with the [`style-loader`](https://github.com/webpack/style-loader) to compile sass and to add the css rules to your document:

``` javascript
require('!style!css!resolve-url!./file.css');
```

and

``` javascript
require('!style-loader!css-loader!resolve-url-loader!sass-loader?sourceMap!./file.scss');
```

### Apply via webpack config

It is preferable to adjust your `webpack.config` so to avoid having to prefix every `require()` statement:

``` javascript
module.exports = {
  module: {
    loaders: [
      {
        test   : /\.css$/,
        loaders: ['style-loader', 'css-loader', 'resolve-url-loader']
      }, {
        test   : /\.scss$/,
        loaders: ['style-loader', 'css-loader', 'resolve-url-loader', 'sass-loader?sourceMap']
      }
    ]
  }
};
```

### IMPORTANT

#### Source maps required

Note that **source maps** must be enabled on any preceding loader. In the above example we use `sass?sourceMap`.

In some use cases (no preceding transpiler) there will be no incoming source map. Therefore we do not warn if the source-map is missing.

However if there is an incoming source-map then it must imply `source` information at each CSS `url()` statement.

#### Don't omit `-loader`

> Your `Webpack.config.js` should **always** use the long-form of the loader name (i.e. the `-loader` suffix).

There is another package called `resolve-url` which Webpack can confuse with `resolve-url-loader`.

There are other common examples. Such as `jshint` and `jshint-loader` packages being confused.

These conflicts are **very hard to debug** and will send you crazy. Your `Webpack.config.js` should **always** use the long-form of the loader name (i.e. the `-loader` suffix)

### Options

Options may be set using [query parameters](https://webpack.github.io/docs/using-loaders.html#query-parameters) or by using [programmatic parameters](https://webpack.github.io/docs/how-to-write-a-loader.html#programmable-objects-as-query-option). Programmatic means the following in your `webpack.config`.

``` javascript
module.exports = {
   resolveUrlLoader: {
      ...
   }
}
```

Where `...` is a hash of any of the following options.

* `sourceMap` Generate a source-map.

* `attempts` Limit searching for any files not where they are expected to be. This is unlimited by default so you will want to set it `1` or some small value.

* `silent` Do not display warnings on CSS syntax or source-map error.

* `fail` Syntax or source-map errors will result in an error.

* `keepQuery` Keep query string and hash within url. I.e. `url('./MyFont.eot?#iefix')`, `url('./MyFont.svg#oldiosfix')`.

* `debug` Show verbose information on the file paths being searched.

* `root` An optional directory within which search may be performed. Relative paths are permitted. Where omitted `process.cwd()` is used and should be sufficient for most use cases.

There are some additional hacks available without support. Only do this if you know what you are doing.

* `absolute` Forces the url() to be resolved to an absolute path. This is considered 
[bad practice](http://webpack.github.io/docs/how-to-write-a-loader.html#should-not-embed-absolute-paths).

* `includeRoot` (experimental, non-performant) Include the project `root` in file search. The `root` option need not be specified but `includeRoot` is only really useful if your `root` directory is shallower than your build working directory.

Note that query parameters take precedence over programmatic parameters.

## How it works

A [rework](https://github.com/reworkcss/rework) process is run on incoming CSS.

Each `url()` statement that implies an asset triggers a file search using node `fs` operations. The asset should be relative to the original source file that was transpiled. This original source is determined by consulting the incoming source-map at the point of the `url()` statement.

Usually the asset is found relative to the original source file `O(1)`.

However in cases where there is no immediate match, we start searching both deeper and shallower from the starting directory `O(n)`. Note that `n` may be limited by the `attempts` option.

This file search "magic" is mainly for historic reasons, to work around broken packages whose assets are not where we would expect.

Shallower paths must be limited to avoid the whole file system from being considered. Progressively shallower paths within the `root` will be considered. Paths featuring a `package.json` or `bower.json` file will not be considered.

If the asset is not found then the `url()` statement will not be updated with a Webpack module-relative path. However if the `url()` statement has no source-map `source` information the loader will fail.

The loader will also fail when input source-map `sources` cannot all be resolved relative to some consistent path within `root`.

Use the `debug` option to see exactly what paths are being searched.

## Limitations / Known-issues

### File search "magic"

Failure to find an asset will trigger a file search of your project.

This feature was for historic reasons, to work around broken packages, whose assets are not where we would expect. Such problems are rare now and many users may not be aware of the search feature.

We now have the `attempts` option to limit this feature. However by default it is unlimited (`attempts=0`) which could make your build non-performant.

You should explicitly set `attempts=1` and increase the value only if needed. We will look to make this the default in the next major release.


### Mixins

Where `url()` statements are created in a mixin the source file may then be the mixin file, and not the file calling the mixin. Obviously this is **not** the desired behaviour.

The incoming source map can vary greatly with different transpilers and their mixins. Use a [source map visualiser](http://sokra.github.io/source-map-visualization/#custom-choose) to see more.  If the source-map shows the correct original file and the mixin still doesn't work then raise an issue and point to the visualisation.

Ultimately you will need to work around this. Try to avoid the mixin. Worst case you can try the `includeRoot` option to force a search of your project sources.

### Compatiblity

#### Webpack

This loader was written for Webpack 1 and has been tweaked to also support with Webpack 2.

If you find any Webpack 2 problems please comment on any similar existing issue or raise a new one.

#### Node-sass

> **IMPORTANT**
> 
> Avoid the combination of **Webpack 1** with **node-sass@^4.0.0**.
>
> Use **Webpack 2** if you need latest **node-sass**

Since `node-sass@>=4.0.0` source-maps have sometimes featured negative column values. Since this loader relies on source-maps this can cause a fatal error.

I don't have a lot of data on this. If you are stuck in Webpack 1 and find that this combination actually works ok for you please let me know.

## Getting help

Webpack is difficult to configure but extremely rewarding.

I am happy for you to **raise an issue** to ask a question regarding this package. However ensure you follow the check-list first.

Currently I am **not** [dogfooding](https://en.wikipedia.org/wiki/Eating_your_own_dog_food) this loader in my own work. I may rely on you being able to isolate the problem in a simple example project and to help debug.

I am happy this loader helps so many people. Open-source is provided as-is so please try not project your frustrations. There are some really great people who follow this project who can help.

### Issues

Before raising a new issue:

* remove this loader and make sure it is not a problem with a different loader in your config (most often the case)
* check [stack overflow](http://stackoverflow.com/search?q=resolve-url-loader) for an answer
* review [previous issues](/issues?utf8=%E2%9C%93&q=is%3Aissue) that may be similar
* be prepared to create a **simple open-source project** that exhibits your problem, should the solution not be immediately obvious to us
* be prepared to use a [source map visualisation](http://sokra.github.io/source-map-visualization/#custom-choose) to check the transpiler has correct source maps coming out
* (ideally) debug some code and let me know where the problem sits

### Pull requests

I am happy to take **pull requests**, however:

* Ensure your change is **backwards compatible** - not all users will be using the same version of Webpack or SASS that you do.
* Follow the **existing code style**.
* Uncomon use-cases/fixes should be opt-in per a new **option**.
* Do **not** overwrite existing variables with new values. I would prefer your change variable names elsewhere if necessary.
* Add **comments** that describe why the code is necessary - i.e. what edge case are we solving. Otherwise we may rewrite later and break your use-case.
