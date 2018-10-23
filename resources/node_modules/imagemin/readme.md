# imagemin [![Build Status](https://img.shields.io/travis/imagemin/imagemin.svg)](https://travis-ci.org/imagemin/imagemin) [![Build status](https://ci.appveyor.com/api/projects/status/wlnem7wef63k4n1t?svg=true)](https://ci.appveyor.com/project/ShinnosukeWatanabe/imagemin)

> Minify images seamlessly


## Install

```
$ npm install --save imagemin
```


## Usage

```js
const imagemin = require('imagemin');
const imageminJpegtran = require('imagemin-jpegtran');
const imageminPngquant = require('imagemin-pngquant');

imagemin(['images/*.{jpg,png}'], 'build/images', {
	plugins: [
		imageminJpegtran(),
		imageminPngquant({quality: '65-80'})
	]
}).then(files => {
	console.log(files);
	//=> [{data: <Buffer 89 50 4e …>, path: 'build/images/foo.jpg'}, …]
});
```


## API

### imagemin(input, output, [options])

Returns `Promise<Object[]>` in the format `{data: Buffer, path: String}`.

#### input

Type: `Array`

Files to be optimized. See supported `minimatch` [patterns](https://github.com/isaacs/minimatch#usage).

#### output

Type: `string`

Set the destination folder to where your files will be written. If no destination is specified no files will be written.

#### options

Type: `Object`

##### plugins

Type: `Array`

[Plugins](https://www.npmjs.com/browse/keyword/imageminplugin) to use.

### imagemin.buffer(buffer, [options])

Returns `Promise<Buffer>`.

#### buffer

Type: `Buffer`

Buffer to optimize.

#### options

Type: `Object`

##### plugins

Type: `Array`

[Plugins](https://www.npmjs.com/browse/keyword/imageminplugin) to use.


## Related

- [imagemin-cli](https://github.com/imagemin/imagemin-cli) - CLI for this module
- [imagemin-app](https://github.com/imagemin/imagemin-app) - GUI app for this module
- [gulp-imagemin](https://github.com/sindresorhus/gulp-imagemin) - Gulp plugin
- [grunt-contrib-imagemin](https://github.com/gruntjs/grunt-contrib-imagemin) - Grunt plugin


## License

MIT © [imagemin](https://github.com/imagemin)
