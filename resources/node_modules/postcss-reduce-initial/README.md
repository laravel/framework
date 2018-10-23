# [postcss][postcss]-reduce-initial [![Build Status](https://travis-ci.org/ben-eb/postcss-reduce-initial.svg?branch=master)][ci] [![NPM version](https://badge.fury.io/js/postcss-reduce-initial.svg)][npm] [![Dependency Status](https://gemnasium.com/ben-eb/postcss-reduce-initial.svg)][deps]

> Reduce `initial` definitions to the *actual* initial value, where possible.


## Install

With [npm](https://npmjs.org/package/postcss-reduce-initial) do:

```
npm install postcss-reduce-initial --save
```


## Example

This module will replace the `initial` CSS keyword with the *actual* value,
when this value is smaller than the `initial` definition itself. For example,
the initial value for the `min-width` property is `0`; therefore, these two
definitions are equivalent;

### Input

```css
h1 {
    min-width: initial;
}
```

### Output

```css
h1 {
    min-width: 0;
}
```

See the [data](data/values.json) for more conversions. This data is courtesy
of Mozilla.


## Usage

See the [PostCSS documentation](https://github.com/postcss/postcss#usage) for
examples for your environment.


## Contributors

Thanks goes to these wonderful people ([emoji key](https://github.com/kentcdodds/all-contributors#emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
| [<img src="https://avatars.githubusercontent.com/u/1282980?v=3" width="100px;"/><br /><sub>Ben Briggs</sub>](http://beneb.info)<br />[💻](https://github.com/ben-eb/postcss-reduce-initial/commits?author=ben-eb) [📖](https://github.com/ben-eb/postcss-reduce-initial/commits?author=ben-eb) 👀 [⚠️](https://github.com/ben-eb/postcss-reduce-initial/commits?author=ben-eb) | [<img src="https://avatars.githubusercontent.com/u/551712?v=3" width="100px;"/><br /><sub>Chris Walker</sub>](http://thechriswalker.github.com/)<br />[🐛](https://github.com/ben-eb/postcss-reduce-initial/issues?q=author%3Athechriswalker) [💻](https://github.com/ben-eb/postcss-reduce-initial/commits?author=thechriswalker) |
| :---: | :---: |
<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors] specification. Contributions of
any kind welcome!


## License

[Template:CSSData] by Mozilla Contributors is licensed under [CC-BY-SA 2.5].

[Template:CSSData]: https://developer.mozilla.org/en-US/docs/Template:CSSData
[CC-BY-SA 2.5]: http://creativecommons.org/licenses/by-sa/2.5/

MIT © [Ben Briggs](http://beneb.info)


[all-contributors]: https://github.com/kentcdodds/all-contributors
[ci]:      https://travis-ci.org/ben-eb/postcss-reduce-initial
[deps]:    https://gemnasium.com/ben-eb/postcss-reduce-initial
[npm]:     http://badge.fury.io/js/postcss-reduce-initial
[postcss]: https://github.com/postcss/postcss
