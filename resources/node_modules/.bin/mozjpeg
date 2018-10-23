#!/usr/bin/env node
'use strict';

var spawn = require('child_process').spawn;
var mozjpeg = require('./');
var input = process.argv.slice(2);

spawn(mozjpeg, input, {stdio: 'inherit'})
	.on('exit', process.exit);
