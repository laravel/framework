# cli-usage

Easily show the usage of your CLI tool from a Markdown string
or file. You can just plug `cli-usage` in without thinking
about paramters, or you can handle that your self using the
`.get` API end-point.

## Install

```
npm install cli-usage
```

## Usage

Most basic usage, just plug in:

```javascript
var usage = require('cli-usage');
usage();
//=> If help-flag is passed, print usage
//=> and exit with code 0.
```

This will listen for `-h`, `-help` or `--help` passed
into your CLI and try to locate a `usage.md` file from
the directory of the file. If `help` is passed and the
`usage.md` file found, the usage will be printed and
the application will exit with code `0`.

You can also pass in a filename or a string.

```javascript
var usage = require('cli-usage');
usage('./some/path/to/usage.md');
```

or

```javascript
var usage = require('cli-usage');
usage('# Simple usage');
```

### Get compiled usage
Instead of `cli-usage` doing all the work, you can
also just retrieve the compiled usage text and handle
it your self.

#### Example
```javascript
var usage = require('cli-usage');

console.log(usage.get('# some custom markdown from string'));

console.log(usage.get('./usage.md'));
```
