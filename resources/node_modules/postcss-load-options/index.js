// ------------------------------------
// #POSTCSS - LOAD OPTIONS
// ------------------------------------

'use strict'

var resolve = require('path').resolve

var config = require('cosmiconfig')
var assign = require('object-assign')

var loadOptions = require('./lib/options')

/**
 * @author Michael Ciniawsky (@michael-ciniawsky) <michael.ciniawsky@gmail.com>
 * @description Autoload Options for PostCSS
 *
 *
 * @module postcss-load-options
 * @version 1.2.0
 *
 * @requires cosmiconfig
 * @requires object-assign
 * @requires lib/options
 *
 * @method optionsrc
 *
 * @param  {Object} ctx Context
 * @param  {String} path Directory
 * @param  {Object} options Options
 * @return {Object} options PostCSS Options
 */
module.exports = function optionsrc (ctx, path, options) {
  ctx = assign({ cwd: process.cwd(), env: process.env.NODE_ENV }, ctx)

  path = path ? resolve(path) : process.cwd()

  options = assign({ rcExtensions: true }, options)

  if (!ctx.env) process.env.NODE_ENV = 'development'

  var file

  return config('postcss', options)
    .load(path)
    .then(function (result) {
      if (!result) console.log('PostCSS Options could not be loaded')

      file = result ? result.filepath : ''

      return result ? result.config : {}
    })
    .then(function (options) {
      if (typeof options === 'function') options = options(ctx)

      if (typeof options === 'object') options = assign(options, ctx)

      return options
    })
    .then(function (options) {
      return { options: loadOptions(options), file: file }
    })
    .catch(console.log)
}
