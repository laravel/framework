'use strict';

var glob = require('glob');

/**
 * Expand one or more patterns into an Array of files.
 *
 * ## Examples:
 *
 * ```
 * globs('../*.js', function (err, jsfiles) {
 *   console.log(jsfiles);
 * })
 *
 * globs(['*.js', '../*.js'], function (err, jsfiles) {
 *   console.log(jsfiles)
 * })
 *
 * globs(['*.js', '../*.js'], { cwd: '/foo' }, function (err, jsfiles) {
 *   console.log(jsfiles)
 * })
 * ```
 *
 * @param {String|Array} patterns One or more patterns to match
 * @param {Object} [options] Options
 * @param {Function} callback Function which accepts two parameters: err, files
 */
var globs = module.exports = function (patterns, options, callback) {
  var pending
    , groups = [];

  // not an Array?  make it so!
  if (!Array.isArray(patterns)) {
    patterns = [ patterns ];
  }

  pending = patterns.length;

  // parameter shifting is really horrible, but i'm
  // mimicing glob's api...
  if (typeof options === 'function') {
    callback = options;
    options = {};
  }

  if (!pending) {
    // nothing to do
    // ensure callback called asynchronously
    return process.nextTick(function() {
      callback(null, []);
    })
  }

  // walk the patterns
  patterns.forEach(function (pattern) {
    // grab the files
    glob(pattern, options, function (err, files) {
      if (err) {
        return callback(err);
      }

      // add the files to the group
      groups = groups.concat(files);

      pending -= 1;
      // last pattern?
      if (!pending) {
        // done
        return callback(null, groups);
      }
    });
  });
};

/**
 * Synchronously Expand one or more patterns to an Array of files
 *
 * @api public
 * @param {String|Array} patterns
 * @param {Object} [options]
 * @return {Array}
 */
globs.sync = function (patterns, options) {
  options = options || {};

  var groups = []
    , index
    , length;

  if (!Array.isArray(patterns)) {
    patterns = [ patterns ];
  }

  for (index = 0, length = patterns.length; index < length; index++) {
    groups = groups.concat(glob.sync(patterns[index], options));
  }

  return groups;
};
