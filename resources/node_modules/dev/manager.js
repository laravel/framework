var util = require('util')
	, fs = require('fs')
	, sys = require('sys')
	, spawn = require('child_process').spawn
	, Inotify = require('inotify').Inotify
	, inotify = new Inotify()


module.exports = function(options) {
	return new NodeManager(options)
}

/**
 * The manager which watches files and restarts
 * Main options:
 *   run {string!}: the js-file to run
 *                 e.g "./app.js", no default, required
 *
 *  watchDir {string} the folder to watch, default: '.'
 *
 *  ignoredPaths {array}: array of ignored paths, which shouldn't not be watched
 *    members can be
 *      strings (matched as ===),
 *      regexps (like extension check) or
 *      function(p) { returns true if path p should be ignored }
 *    default: []
 *
 *
 * Logging options
 *   debug {bool}: adds additional output about watches and changes, default: false
 *   logger {object}: custom logger, must have error and debug methods
 *
 */
function NodeManager(options) {

	// public interface
	this.watchFile = watchFile
	this.watchFolder = watchFolder
	this.run = run
	this.start = start

	if (!options.run) {
		throw new Error("Please provide what to run. E.g. new NodeManager({ run: './app.js' })")
	}

	var ignoredPaths = options.ignoredPaths || []

	var logger = options.logger || {
		debug: function() {
			options.debug && console.log.apply(console, arguments)
		},

		error: function() {
			console.log.apply(console, arguments)
		}
	}

	var onRunOutput = options.onRunOutput || function(data) {
      util.print(data);
    }

	var onRunError = options.onRunError || function(data) {
      util.print(data);
    }

	/**
	 * Run with default watch config
	 */
	function start() {
		var dir = options.watchDir || '.'

		this.run();
		this.watchFile(dir); // watch current folder
		this.watchFolder(dir); // watch all files under current folder

		process.stdin.resume();
		process.stdin.setEncoding('utf8');
	}

	var child // references the child process, after spawned


	/**
	 * executes the command given by the argument
	 */
	function run() {
		// run the server
		var node = options.node || options.run.match(/\.coffee$/) ? 'coffee' : 'node'

		child = spawn(node, [options.run].concat(process.argv.slice(3)));

		// let the child's output escape.
		child.stdout.on('data', onRunOutput);
		child.stderr.on('data', onRunError);

		// let the user's typing get to the child
		process.stdin.pipe(child.stdin);

		console.log('\nStarting: ' + options.run);
	}

	var restartIsScheduled
		, restartTimer
		, lastRestartTime


	/**
	 * restarts the server
	 * doesn't restart more often than once per 10 ms
         * suspends restart a little bit in case of massive changes to save CPU
	 */
	function restart() {

		if (restartIsScheduled) return

		if (lastRestartTime && lastRestartTime + 500 > new Date) {
			// if last restart was recent, we postpone new restart a bit to save resources,
			// because it often means that many files are changed at once
			schedule(150)
		} else {
			// in any way we schedule restart in next 10 ms, so many change events on one file/dir lead to single restart
			schedule(10)
		}

		function schedule(ms) {
			restartIsScheduled = true
			setTimeout(function() {
				restartIsScheduled = false
				doRestart()
			}, ms)
		}

		function doRestart() {
			lastRestartTime = +new Date
			// kill if running
			if (child) child.kill();
			// run it again
			run();
		}
	}


	/**
	 * watches all files and subdirectories in given folder (recursively),
	 * excluding the folder itself
	 * @param root
	 */
	function watchFolder(root) {
		var files = fs.readdirSync(root);

		files.forEach(function(file) {
			var path = root + '/' + file
				, stat = fs.statSync(path);

			// watch file/folder
			if (isIgnoredPath(path)) {
				logger.debug("ignored path " + path)
				return
			}

			// ignore absent files
			if (!stat) {
				logger.error("ERROR: couldn't stat " + path)
				return
			}

			watchFile(path);

			// recur if directory
			if (stat.isDirectory()) {
				watchFolder(path);
			}
		});

	}

	function isIgnoredPath(path) {

		for (var i = 0; i < ignoredPaths.length; i++) {
			var test = ignoredPaths[i];

			logger.debug("isIgnoredPath: path " + path + " tested against " + test);
			if (test === path) return true;
			if (test instanceof RegExp && test.test(path)) return true;
			if (test instanceof Function && test(path)) return true;
		}

		logger.debug("path is ok: " + path)
		return false;
	}

	// arrays for debug events output
	var flag2event = {}
	var eventsAll = [
		"IN_ACCESS", "IN_ATTRIB", "IN_CLOSE", "IN_CLOSE_NOWRITE", "IN_CLOSE_WRITE", "IN_CREATE",
		"IN_DELETE", "IN_DELETE_SELF", "IN_DONT_FOLLOW", "IN_IGNORED", "IN_ISDIR",
		"IN_MASK_ADD", "IN_MODIFY", "IN_MOVE", "IN_MOVED_FROM", "IN_MOVED_TO", "IN_MOVE_SELF",
		"IN_ONESHOT", "IN_ONLYDIR", "IN_OPEN", "IN_Q_OVERFLOW", "IN_UNMOUNT"
	]
	eventsAll.forEach(function(event) {
		flag2event[Inotify[event]] = event
	})


	function watchFile(file) {

		logger.debug("Watch: " + file);

		function callback(event) {
			event.toString = function() {
				// translate event flags into string
				// e.g IN_CLOSE | IN_CLOSE_WRITE
				var txt = []
				eventsAll.forEach(function(e) {
					if (Inotify[e] & this.mask) txt.push(e)
				}.bind(this))
				return txt.join(' | ')
			}

			if (event.name) { // name of updated file inside watched folder
				var path = file + '/' + event.name
				if (isIgnoredPath(path)) {
					return
				}

				if (event.mask & Inotify.IN_CREATE) { // IN_CREATE always comes here
					// when a new file is created we wait for it's close_write
					watchFile(path)
					if (fs.statSync(path).isDirectory()) {
						watchFolder(path)
					}
					return
				}

				logger.debug(path + ' ' + event);

				restart()

			} else {
				logger.debug(file + ' ' + event);

				restart()
			}

		}


		// we watch for these events for files and folders
		// there may be many IN_MODIFY, so we don't watch it, but we await for IN_CLOSE_WRITE
		var watchEvents = [ 'IN_CLOSE_WRITE' , 'IN_CREATE' , 'IN_DELETE' , 'IN_DELETE_SELF' , 'IN_MOVE_SELF', 'IN_MOVE' ];

		inotify.addWatch({
			path: file,
			watch_for: watchEvents.reduce(function(prev, cur) {
				return prev | Inotify[cur]
			}, 0),
			callback: callback
		})

	}


}
