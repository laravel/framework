'use strict';

var path = require('path');
var oshomedir = require('os-homedir');
var minimist = require('minimist');
var assign = require('object-assign');
var createExplorer = require('./lib/createExplorer');

var parsedCliArgs = minimist(process.argv);

module.exports = function (moduleName, options) {
  options = assign({
    packageProp: moduleName,
    rc: '.' + moduleName + 'rc',
    js: moduleName + '.config.js',
    argv: 'config',
    rcStrictJson: false,
    stopDir: oshomedir(),
    cache: true,
  }, options);

  if (options.argv && parsedCliArgs[options.argv]) {
    options.configPath = path.resolve(parsedCliArgs[options.argv]);
  }

  return createExplorer(options);
};
