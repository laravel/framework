atob
===

Uses `Buffer` to emulate the exact functionality of the browser's atob.

Note: Unicode may be handled incorrectly (like the browser).

It turns base64-encoded <strong>a</strong>scii data back **to** <strong>b</strong>inary.

    (function () {
      "use strict";

      var atob = require('atob')
        , b64 = "SGVsbG8gV29ybGQ="
        , bin = atob(b64)
        ;

      console.log(bin); // "Hello World"
    }());

LICENSE
=======

Code copyright 2012-2015 AJ ONeal

Dual-licensed MIT and Apache-2.0

Docs copyright 2012-2015 AJ ONeal

Docs released under [Creative Commons](https://github.com/node-browser-compat/atob/blob/master/LICENSE.DOCS).
