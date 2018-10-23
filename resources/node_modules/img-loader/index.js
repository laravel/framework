'use strict'

var imagemin = require('imagemin')
var imageminGifsicle = require('imagemin-gifsicle')
var imageminMozjpeg = require('imagemin-mozjpeg')
var imageminOptipng = require('imagemin-optipng')
var imageminPngquant = require('imagemin-pngquant')
var imageminSvgo = require('imagemin-svgo')
var loaderUtils = require('loader-utils')

var defaults = {
  enabled: true,
  gifsicle: {},
  mozjpeg: {},
  optipng: {},
  svgo: {}
}

module.exports = function (content) {
  this.cacheable && this.cacheable()

  var options = Object.assign(
    Object.create(defaults),
    loaderUtils.getOptions(this)
  )
  if (!options.enabled) {
    return content
  }

  var use = [
    options.gifsicle && imageminGifsicle(options.gifsicle),
    options.mozjpeg && imageminMozjpeg(options.mozjpeg),
    options.optipng && imageminOptipng(options.optipng),
    options.svgo && imageminSvgo(options.svgo),
    options.pngquant && imageminPngquant(options.pngquant)
  ].filter(Boolean)
  if (use.length === 0) {
    return content
  }

  var callback = this.async()
  imagemin
    .buffer(content, { use: use })
    .then(function (buffer) { callback(null, buffer) })
    .catch(function (error) { callback(error) })
}

module.exports.raw = true
