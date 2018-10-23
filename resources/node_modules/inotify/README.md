# node-inotify - monitoring file system events in Gnu/Linux with [NodeJS][nodejs_home]
[![Build Status](https://travis-ci.org/c4milo/node-inotify.svg?branch=master)](https://travis-ci.org/c4milo/node-inotify)

The inotify API provides a mechanism for monitoring file system events.
Inotify can be used to monitor individual files, or to monitor directories.
When a directory is monitored, inotify will return events for the directory
itself, and for files inside the directory. [(ref: GNU/Linux Manual)][inotify.7]

## Installation
[NodeJS][nodejs_dev] versions 0.10.x, 0.12.x, 4.x.x, 5.x.x and IO.js 1.x, 2.x, 3.x are currently supported and tested.

### Install from NPM

```shell
    $ npm install inotify
```

### Install from git

```shell
$ npm install node-gyp -g
$ git clone git://github.com/c4milo/node-inotify.git
$ cd node-inotify
$ node-gyp rebuild
```

## API
  * `var inotify = new Inotify()`: Creates a new instance of Inotify. By default it's in persistent mode.
  You can specify `false` in `var inotify = new Inotify(false)` to use the non persistent mode.

  * `var wd = inotify.addWatch(arg)`:  Adds a watch for files or directories. This will then return a watch descriptor. The argument is an object as follows
```javascript
    var arg = {
        // Path to be monitored.
        path: '.',
        // An optional OR'ed set of events to watch for.
        // If they're not specified, it will use
        // Inotify.IN_ALL_EVENTS by default.
        watch_for: Inotify.IN_ALL_EVENTS,
        // Callback function that will receive each event.
        callback: function (event) {}
    }
```
You can call this function as many times as you want in order to monitor different paths.
**Monitoring of directories is not recursive**: to monitor subdirectories under a directory, additional *watches* must be created.

  * `inotify.removeWatch(watch_descriptor)`: Remove a watch associated with the watch_descriptor param and returns `true` if the action was successful or `false` in the opposite case. Removing a watch causes an `Inotify.IN_IGNORED` event to be generated for this watch descriptor.

  * `inotify.close()`: Remove all the watches and close the inotify's file descriptor. Returns `true` if the action was successful or false in the opposite case.

### Event object structure
```javascript
var event = {
    watch: Watch descriptor,
    mask: Mask of events,
    cookie: Cookie that permits to associate events,
    name: Optional name of the object being watched
};
```

The `event.name` property is only present when an event is returned for a file inside a watched directory; it identifies the file path name relative to the watched directory.


## Example of use

```javascript
    var Inotify = require('inotify').Inotify;
    var inotify = new Inotify(); //persistent by default, new Inotify(false) //no persistent

    var data = {}; //used to correlate two events

    var callback = function(event) {
        var mask = event.mask;
        var type = mask & Inotify.IN_ISDIR ? 'directory ' : 'file ';
        if (event.name) {
            type += ' ' + event.name + ' ';
        } else {
            type += ' ';
        }
        // the purpose of this hell of 'if' statements is only illustrative.

        if (mask & Inotify.IN_ACCESS) {
            console.log(type + 'was accessed ');
        } else if (mask & Inotify.IN_MODIFY) {
            console.log(type + 'was modified ');
        } else if (mask & Inotify.IN_OPEN) {
            console.log(type + 'was opened ');
        } else if (mask & Inotify.IN_CLOSE_NOWRITE) {
            console.log(type + ' opened for reading was closed ');
        } else if (mask & Inotify.IN_CLOSE_WRITE) {
            console.log(type + ' opened for writing was closed ');
        } else if (mask & Inotify.IN_ATTRIB) {
            console.log(type + 'metadata changed ');
        } else if (mask & Inotify.IN_CREATE) {
            console.log(type + 'created');
        } else if (mask & Inotify.IN_DELETE) {
            console.log(type + 'deleted');
        } else if (mask & Inotify.IN_DELETE_SELF) {
            console.log(type + 'watched deleted ');
        } else if (mask & Inotify.IN_MOVE_SELF) {
            console.log(type + 'watched moved');
        } else if (mask & Inotify.IN_IGNORED) {
            console.log(type + 'watch was removed');
        } else if (mask & Inotify.IN_MOVED_FROM) {
            data = event;
            data.type = type;
        } else if (mask & Inotify.IN_MOVED_TO) {
            if ( Object.keys(data).length &&
                data.cookie === event.cookie) {
                console.log(type + ' moved to ' + data.type);
                data = {};
            }
        }
    }
    var home_dir = {
        // Change this for a valid directory in your machine.
        path:      '/home/camilo',
        watch_for: Inotify.IN_OPEN | Inotify.IN_CLOSE,
        callback:  callback
    };

    var home_watch_descriptor = inotify.addWatch(home_dir);

    var home2_dir = {
        // Change this for a valid directory in your machine
        path:      '/home/bob',
        watch_for: Inotify.IN_ALL_EVENTS,
        callback:  callback
    };

    var home2_wd = inotify.addWatch(home2_dir);

```

## Inotify Events

### Watch for:
 * **Inotify.IN_ACCESS:** File was accessed (read)
 * **Inotify.IN_ATTRIB:** Metadata changed, e.g., permissions, timestamps, extended attributes, link count (since Linux 2.6.25), UID, GID, etc.
 * **Inotify.IN_CLOSE_WRITE:** File opened for writing was closed
 * **Inotify.IN_CLOSE_NOWRITE:** File not opened for writing was closed
 * **Inotify.IN_CREATE:** File/directory created in the watched directory
 * **Inotify.IN_DELETE:** File/directory deleted from the watched directory
 * **Inotify.IN_DELETE_SELF:** Watched file/directory was deleted
 * **Inotify.IN_MODIFY:** File was modified
 * **Inotify.IN_MOVE_SELF:** Watched file/directory was moved
 * **Inotify.IN_MOVED_FROM:** File moved out of the watched directory
 * **Inotify.IN_MOVED_TO:** File moved into watched directory
 * **Inotify.IN_OPEN:** File was opened
 * **Inotify.IN_ALL_EVENTS:** Watch for all kind of events
 * **Inotify.IN_CLOSE:**  (IN_CLOSE_WRITE | IN_CLOSE_NOWRITE)  Close
 * **Inotify.IN_MOVE:**  (IN_MOVED_FROM | IN_MOVED_TO)  Moves

### Additional Flags:
 * **Inotify.IN_ONLYDIR:** Only watch the path if it is a directory.
 * **Inotify.IN_DONT_FOLLOW:** Do not follow symbolics links
 * **Inotify.IN_ONESHOT:** Only send events once
 * **Inotify.IN_MASK_ADD:** Add (OR) events to watch mask for this pathname if it already exists (instead of replacing the mask).

### The following bits may be set in the `event.mask` property returned in the callback
 * **Inotify.IN_IGNORED:** Watch was removed explicitly with inotify.removeWatch(watch_descriptor) or automatically (the file was deleted, or the file system was unmounted)
 * **Inotify.IN_ISDIR:** Subject of this event is a directory
 * **Inotify.IN_Q_OVERFLOW:** Event queue overflowed (wd is -1 for this event)
 * **Inotify.IN_UNMOUNT:** File system containing the watched object was unmounted


## FAQ
### Why inotify does not watch directories recursively?
http://www.quora.com/Inotify-monitoring-of-directories-is-not-recursive-Is-there-any-specific-reason-for-this-design-in-Linux-kernel


## License
(The MIT License)

Copyright 2017 Node-Inotify AUTHORS. All rights reserved.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to
deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
IN THE SOFTWARE.


[inotify.7]: http://www.kernel.org/doc/man-pages/online/pages/man7/inotify.7.html "http://www.kernel.org/doc/man-pages/online/pages/man7/inotify.7.html"
[nodejs_home]: http://www.nodejs.org
[nodejs_dev]: http://github.com/joyent/node
[code_example]: http://gist.github.com/476119
