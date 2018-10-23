#!/usr/bin/env node

if (process.argv.length < 3) {
	console.log('Found 1 argument. Expected at least two.');
	console.log('Usage: \n  node-dev app.js');
	process.exit(1);
}

var manager = require('dev')({
	ignoredPaths: [
		'./public', // static folder for an express project
		/\.dirtydb$/, /\.db$/,  // sqlite db
		/\/\./  // all files and directories starting with dot
	],
	run: process.argv[2]
})


manager.start();
