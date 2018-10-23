randomfill
===

[![Version](http://img.shields.io/npm/v/randombytes.svg)](https://www.npmjs.org/package/randombytes) [![Build Status](https://travis-ci.org/crypto-browserify/randombytes.svg?branch=master)](https://travis-ci.org/crypto-browserify/randombytes)

randomfill from node that works in the browser.  In node you just get crypto.randomBytes, but in the browser it uses .crypto/msCrypto.getRandomValues

```js
var randomFill = require('randomfill');
var buf
randomFill.randomFillSync(16);//get 16 random bytes
randomFill.randomFill(16, function (err, resp) {
  // resp is 16 random bytes
});
```
