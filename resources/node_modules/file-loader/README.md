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
  <h1>File Loader</h1>
  <p>Instructs webpack to emit the required object as file and to return its public url.</p>
</div>

<h2 align="center">Install</h2>

```bash
npm install --save-dev file-loader
```

<h2 align="center">Usage</h2>

By default the filename of the resulting file is the MD5 hash of the file's contents
with the original extension of the required resource.

``` javascript
var url = require("file-loader!./file.png");
// => emits file.png as file in the output directory and returns the public url
// => returns i. e. "/public-path/0dcbbaa701328a3c262cfd45869e351f.png"
```

By default a file is emitted, however this can be disabled if required (e.g. for server
side packages).

``` javascript
var url = require("file-loader?emitFile=false!./file.png");
// => returns the public url but does NOT emit a file
// => returns i. e. "/public-path/0dcbbaa701328a3c262cfd45869e351f.png"
```

#### Filename templates

You can configure a custom filename template for your file using the query parameter `name`. For instance, to copy a file from your `context` directory into the output directory retaining the full directory structure, you might use `?name=[path][name].[ext]`.

By default, the path and name you specify will output the file in that same directory and will also use that same URL path to access the file.

You can specify custom output and public paths by using the `outputPath`, `publicPath` and `useRelativePath` query name parameters:

```
use: "file-loader?name=[name].[ext]&publicPath=assets/foo/&outputPath=app/images/"
```

`useRelativePath` should be `true` if you wish to generate relative URL to the each file context
```javascript
{
 loader: 'file-loader',
 query: {
  useRelativePath: process.env.NODE_ENV === "production"
 }
}
```

#### Filename template placeholders

* `[ext]` the extension of the resource
* `[name]` the basename of the resource
* `[path]` the path of the resource relative to the `context` query parameter or option.
* `[hash]` the hash of the content, `hex`-encoded `md5` by default
* `[<hashType>:hash:<digestType>:<length>]` optionally you can configure
  * other `hashType`s, i. e. `sha1`, `md5`, `sha256`, `sha512`
  * other `digestType`s, i. e. `hex`, `base26`, `base32`, `base36`, `base49`, `base52`, `base58`, `base62`, `base64`
  * and `length` the length in chars
* `[N]` the N-th match obtained from matching the current file name against the query param `regExp`

#### Examples

``` javascript
require("file-loader?name=js/[hash].script.[ext]!./javascript.js");
// => js/0dcbbaa701328a3c262cfd45869e351f.script.js

require("file-loader?name=html-[hash:6].html!./page.html");
// => html-109fa8.html

require("file-loader?name=[hash]!./flash.txt");
// => c31e9820c001c9c4a86bce33ce43b679

require("file-loader?name=[sha512:hash:base64:7].[ext]!./image.png");
// => gdyb21L.png
// use sha512 hash instead of md5 and with only 7 chars of base64

require("file-loader?name=img-[sha512:hash:base64:7].[ext]!./image.jpg");
// => img-VqzT5ZC.jpg
// use custom name, sha512 hash instead of md5 and with only 7 chars of base64

require("file-loader?name=picture.png!./myself.png");
// => picture.png

require("file-loader?name=[path][name].[ext]?[hash]!./dir/file.png")
// => dir/file.png?e43b20c069c4a01867c31e98cbce33c9
```

<h2 align="center">Contributing</h2>

Don't hesitate to create a pull request. Every contribution is appreciated. In development you can start the tests by calling `npm test`.

<h2 align="center">Maintainers</h2>

<table>
  <tbody>
    <tr>
      <td align="center">
        <img width="150" height="150"
        src="https://avatars.githubusercontent.com/sokra?v=3">
        <br />
        <a href="https://github.com/">Tobias Koppers</a>
      </td>
      <td align="center">
        <img width="150" height="150"
        src="https://avatars.githubusercontent.com/SpaceK33z?v=3">
        <br />
        <a href="https://github.com/">Kees Kluskens</a>
      </td>
      <td align="center">
        <img width="150" height="150"
        src="https://avatars.githubusercontent.com/mobitar?v=3">
        <br />
        <a href="https://github.com/">Mo Bitar</a>
      </td>
    </tr>
  </tbody>
</table>


<h2 align="center">LICENSE</h2>

MIT

[npm]: https://img.shields.io/npm/v/file-loader.svg
[npm-url]: https://npmjs.com/package/file-loader

[node]: https://img.shields.io/node/v/file-loader.svg
[node-url]: https://nodejs.org

[deps]: https://david-dm.org/webpack-contrib/file-loader.svg
[deps-url]: https://david-dm.org/webpack-contrib/file-loader

[tests]: http://img.shields.io/travis/webpack-contrib/file-loader.svg
[tests-url]: https://travis-ci.org/webpack-contrib/file-loader

[cover]: https://coveralls.io/repos/github/webpack-contrib/file-loader/badge.svg
[cover-url]: https://coveralls.io/github/webpack-contrib/file-loader

[chat]: https://badges.gitter.im/webpack/webpack.svg
[chat-url]: https://gitter.im/webpack/webpack
