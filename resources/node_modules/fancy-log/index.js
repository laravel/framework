'use strict';
/*
  Initial code from https://github.com/gulpjs/gulp-util/blob/v3.0.6/lib/log.js
 */
var chalk = require('chalk');
var timestamp = require('time-stamp');

function getTimestamp(){
  return '['+chalk.grey(timestamp('HH:mm:ss'))+']';
}

function log(){
  var time = getTimestamp();
  process.stdout.write(time + ' ');
  console.log.apply(console, arguments);
  return this;
}

function info(){
  var time = getTimestamp();
  process.stdout.write(time + ' ');
  console.info.apply(console, arguments);
  return this;
}

function dir(){
  var time = getTimestamp();
  process.stdout.write(time + ' ');
  console.dir.apply(console, arguments);
  return this;
}

function warn(){
  var time = getTimestamp();
  process.stderr.write(time + ' ');
  console.warn.apply(console, arguments);
  return this;
}

function error(){
  var time = getTimestamp();
  process.stderr.write(time + ' ');
  console.error.apply(console, arguments);
  return this;
}

module.exports = log;
module.exports.info = info;
module.exports.dir = dir;
module.exports.warn = warn;
module.exports.error = error;
