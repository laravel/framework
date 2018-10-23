/*jshint strict:true node:true es5:true onevar:true laxcomma:true laxbreak:true eqeqeq:true immed:true latedef:true*/
(function () {
  "use strict";

  var atob = require('./index')
    , encoded = "SGVsbG8gV29ybGQ="
    , unencoded = "Hello World"
  /*
    , encoded = "SGVsbG8sIBZM"
    , unencoded = "Hello, 世界"
  */
    ;

  if (unencoded !== atob(encoded)) {
    console.log('[FAIL]', unencoded, atob(encoded));
    return;
  }

  console.log('[PASS] all tests pass');
}());
