/*!
 * filter-array <https://github.com/jonschlinkert/filter-array>
 *
 * Copyright (c) 2014-2015 Jon Schlinkert, contributors.
 * Licensed under the MIT License
 */

'use strict';

var typeOf = require('kind-of');
var filter = require('arr-filter');
var mm = require('micromatch');

/**
 * Filter array against given glob
 * patterns, regex or given function.
 *
 * ```js
 * var filter = require('filter-array');
 *
 * filter(['a', 'b', 'c', 'b', 'c', 'e'], function(ele) {
 *   return ele === 'a' || ele === 'b';
 * });
 *
 * //=> ['a', 'b', 'b']
 * ```
 *
 * @name   filterArray
 * @param  {Array} `arr` array to filter
 * @param  {Array|String|Function|RegExp} `filters`
 * @param  {Object} `opts` options to pass to [micromatch]
 * @return {Array}
 * @api public
 */

module.exports = function filterArray(arr, filters, opts) {
  if (arr.length === 0) {
    return [];
  }

  if (typeOf(filters) === 'function' || typeOf(filters) === 'regexp') {
    var isMatch = mm.matcher(filters, opts);

    return filter(arr, function _filter(val) {
      return isMatch(val);
    });
  }

  if (typeOf(filters) === 'string' || typeOf(filters) === 'array') {
    return filter(arr, mm.filter(filters, opts));
  }

  return [];
};
