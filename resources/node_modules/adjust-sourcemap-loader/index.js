/*
 * MIT License http://opensource.org/licenses/MIT
 * Author: Ben Holloway @bholloway
 */
'use strict';

var assign = require('lodash.assign');

module.exports = assign(require('./lib/loader'), {
  moduleFilenameTemplate: require('./lib/module-filename-template'),
  codec                 : require('./codec')
});