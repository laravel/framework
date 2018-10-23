// ------------------------------------
// # POSTCSS - LOAD CONFIG - INDEX
// ------------------------------------

'use strict'

var resolve = require('path').resolve

var config = require('cosmiconfig')
var assign = require('object-assign')

var loadOptions = require('postcss-load-options/lib/options.js')
var loadPlugins = require('postcss-load-plugins/lib/plugins.js')

/**
 * Autoload Config for PostCSS
 *
 * @author Michael Ciniawsky (@michael-ciniawsky) <michael.ciniawsky@gmail.com>
 * @license MIT
 *
 * @module postcss-load-config
 * @version 1.2.0
 *
 * @requires comsiconfig
 * @requires object-assign
 * @requires postcss-load-options
 * @requires postcss-load-plugins
 *
 * @method postcssrc
 *
 * @param  {Object} ctx Context
 * @param  {String} path Config Directory
 * @param  {Object} options Config Options
 *
 * @return {Promise} config PostCSS Config
 */
module.exports = function postcssrc (ctx, path, options) {
  ctx = assign({ cwd: process.cwd(), env: process.env.NODE_ENV }, ctx)

  path = path ? resolve(path) : process.cwd()

  options = assign({ rcExtensions: true }, options)

  if (!ctx.env) process.env.NODE_ENV = 'development'

  var file

  return config('postcss', options)
    .load(path)
    .then(function (result) {
      if (!result) throw Error('No PostCSS Config found in: ' + path)

      file = result ? result.filepath : ''

      return result ? result.config : {}
    })
    .then(function (config) {
      if (typeof config === 'function') config = config(ctx)
      else config = assign(config, ctx)

      if (!config.plugins) config.plugins = []

      return {
        plugins: loadPlugins(config),
        options: loadOptions(config),
        file: file
      }
    })
}
