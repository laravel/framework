# Adjust Source-map Loader

[![NPM](https://nodei.co/npm/adjust-sourcemap-loader.png)](http://github.com/bholloway/adjust-sourcemap-loader)

Webpack loader that adjusts source maps.

Use as a **loader** to debug source-maps or to adjust source-maps between other loaders.

Use as a **module filename template** to ensure the final source-map are to your liking.

## Usage : Loader

``` javascript
require('adjust-sourcemap?format=absolute!babel?sourceMap');
```

### Source maps required

Note that **source maps** must be enabled on any preceding loader. In the above example we use `babel?sourceMap`.

### Apply via webpack config

It is preferable to adjust your `webpack.config` so to avoid having to prefix every `require()` statement:

``` javascript
module.exports = {
  module: {
    loaders: [
      {
        test   : /\.js/,
        loaders: ['adjust-sourcemap?format=absolute', 'babel?sourceMap']
      }
    ]
  }
};
```

## Usage : Module filename template

Specifying a certain format as the final step in a loader chain will **not** influence the final source format that Webpack will output. Instead the format is determined by the **module filename template**.

There are limitations to the filename templating that Webpack provides. This package may also operate as a custom template function that will convert output source-map sources to the desired `format`.

In the following example we ensure project-relative source-map sources are output.

```javascript
var templateFn = require('adjust-sourcemap-loader')
  .moduleFilenameTemplate({
    format: 'projectRelative'
  });

module.exports = {
  output: {
    ...
    devtoolModuleFilenameTemplate        : templateFn,
    devtoolFallbackModuleFilenameTemplate: templateFn
  }
};
```

## Options

As a loader, options may be set using [query parameters](https://webpack.github.io/docs/using-loaders.html#query-parameters) or by using [programmatic parameters](https://webpack.github.io/docs/how-to-write-a-loader.html#programmable-objects-as-query-option). Programmatic means the following in your `webpack.config`.

```javascript
module.exports = {
   adjustSourcemapLoader: {
      ...
   }
}
```

Where `...` is a hash of any of the following options.

* **`debug`** : `boolean|RegExp` May be used alone (boolean) or with a `RegExp` to match the resource(s) you are interested in debugging.

* **`fail`** : `boolean` Implies an **Error** if a source-map source cannot be decoded.

* **`format`** : `string` Optional output format for source-map `sources`. Must be the camel-case name of one of the available `codecs`. Omitting the format will result in **no change** and the outgoing source-map will match the incomming one.

* **`root`** : `boolean` A boolean flag that indices that a `sourceRoot` path sould be included in the output map. This is contingent on a `format` being specified.

* **`codecs`** : `Array.<{name:string, decode:function, encode:function, root:function}>` Optional Array of codecs. There are a number of built-in codecs available. If you specify you own codecs you will loose those that are built-in. However you can include them from the `codec/` directory.

Note that **query** parameters take precedence over **programmatic** parameters.

### Changing the format

Built-in codecs that may be specified as a `format` include:

* `absolute`
* `outputRelative`
* `projectRelative`
* `webpackProtocol`
* `sourceRelative` (works for loader only, **not** Module filename template)

### Specifying codecs

There are additional built-in codecs that do not support encoding. These are still necessary to decode source-map sources. If you specify your own `options.codecs` then you should **also include the built-in codecs**. Otherwise you will find that some sources cannot be decoded.

The existing codecs may be found in `/codec`, or on the loader itself:

```javascript
var inBuiltCodecs = require('adjust-sourcemap-loader').codecs,
    myCodecs      = [
      {
        name  : 'foo',
        decode: function(uri) {...},
        encode: function(absolute) {...},
        root  : function() {...}
      },
      ...
    ];

module.exports = {
   adjustSourcemapLoader: {
      codecs: inBuiltCodecs.concat(myCodecs)
   }
}
```

The codec **order is important**. Those that come first have precedence. Any codec that detects a distinct URI should be foremost so that illegal paths are not encountered by successive codecs.

### Abstract codecs

A codec that detects generated code and cannot `decode()` a URI to an absolute file path.

Instead of implementing `encode()` or `root()` it should instead specify `abstract:true`. Its `decode()` function then may return `boolean` where it detects such generated sources.

For example, a built-in abstract codec will match the **Webpack bootstrap** code and ensure that its illegal source uri is not encountered by later coders.

## How it works

The loader will receive a source map as its second parameter, so long as the preceding loader was using source-maps.

The exception is the **css-loader** where the source-map is in the content, which is **not currently supported** .

The source-map `sources` are parsed by applying **codec.decode()** functions until one of them returns an absolute path to a file that exists. The exception is abstract codecs, where the source with remain unchanged.

If a format is specified then the source-map `sources` are recreated by applying the **codec.encode()** function for the stated `format` and (where the `root` option is specified) the **codec.root()** function will set the source-map `sourceRoot`.

If a codec does not specify **codec.encode()** or **codec.root()** then it may **not** be used as the `format`.

