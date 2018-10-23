# img-loader

[![npm Version][npm-image]][npm]
[![Greenkeeper badge][greenkeeper-image]][greenkeeper]
[![Build Status][build-image]][build]

[![JS Standard Style][style-image]][style]
[![MIT License][license-image]][LICENSE]

Image minimizing loader for webpack 2, meant to be used with [url-loader](https://github.com/webpack/url-loader), [file-loader](https://github.com/webpack/file-loader), or [raw-loader](https://github.com/webpack/raw-loader)

> Minify PNG, JPEG, GIF and SVG images with [imagemin](https://github.com/imagemin/imagemin)

*Issues with the minimized output should be reported [to imagemin](https://github.com/imagemin/imagemin/issues).*

Comes with the following optimizers:

- [gifsicle](https://github.com/imagemin/imagemin-gifsicle) — *Compress GIF images*
- [mozjpeg](https://github.com/imagemin/imagemin-mozjpeg) — *Compress JPEG images*
- [optipng](https://github.com/imagemin/imagemin-optipng) — *Compress PNG images*
- [pngquant](https://github.com/imagemin/imagemin-pngquant) — *Compress PNG images*
- [svgo](https://github.com/imagemin/imagemin-svgo) — *Compress SVG images*


## Install

```sh
$ npm install img-loader --save-dev
```


## Usage

[Documentation: Using loaders](http://webpack.github.io/docs/using-loaders.html)

```javascript
module: {
  rules: [
    {
      test: /\.(jpe?g|png|gif|svg)$/i,
      use: [
        'url-loader?limit=10000',
        'img-loader'
      ]
    }
  ]
}
```

The default minification includes: `gifsicle`, `mozjpeg`, `optipng`, & `svgo`. Each with their default settings.

`pngquant` can be enabled by configuring it in the options.


### Options

Options can also be passed by specifying properties matching each optimizer in your rule options. `false` or `null` can be used to disable one of the default optimizers.

For more details on each plugin's options, see their documentation on [Github](https://github.com/imagemin).

``` javascript
{
  module: {
    rules: [
      {
        test: /\.(jpe?g|png|gif|svg)$/i,
        use: [
          'url-loader?limit=10000',
          {
            loader: 'img-loader',
            options: {
              enabled: process.env.NODE_ENV === 'production',
              gifsicle: {
                interlaced: false
              },
              mozjpeg: {
                progressive: true,
                arithmetic: false
              },
              optipng: false, // disabled
              pngquant: {
                floyd: 0.5,
                speed: 2
              },
              svgo: {
                plugins: [
                  { removeTitle: true },
                  { convertPathData: false }
                ]
              }
            }
          }
        ]
      }
    ]
  }
}
```


## License

This software is free to use under the MIT license. See the [LICENSE-MIT file][LICENSE] for license text and copyright information.

[npm]: https://www.npmjs.org/package/img-loader
[npm-image]: https://img.shields.io/npm/v/img-loader.svg
[greenkeeper-image]: https://badges.greenkeeper.io/thetalecrafter/img-loader.svg
[greenkeeper]: https://greenkeeper.io/
[build]: https://travis-ci.org/thetalecrafter/img-loader
[build-image]: https://img.shields.io/travis/thetalecrafter/img-loader.svg
[style]: https://github.com/feross/standard
[style-image]: https://img.shields.io/badge/code%20style-standard-brightgreen.svg
[license-image]: https://img.shields.io/npm/l/img-loader.svg
[LICENSE]: https://github.com/thetalecrafter/img-loader/blob/master/LICENSE-MIT
