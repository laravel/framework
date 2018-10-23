Changelog
===


### `v4.6.1`

1. Adds npm ignore file, ignoring tests and examples from package.
2. Fixes CI builds

### `v4.6.0`

1. Adds support for Icon URL in Growl ([by @gucong3000](https://github.com/mikaelbr/node-notifier/pull/115))
2. Adds options for passing host and port to cli tool ([reported by @el-davo](https://github.com/mikaelbr/node-notifier/issues/106))
3. Fixes sanitize response on `notify` callback ([by @MadLittleMods](https://github.com/mikaelbr/node-notifier/commit/a44454a11eff452a8b55f9fbe291e189ed088708))
4. Fixes use of new line in messages ([by @gucong3000](https://github.com/mikaelbr/node-notifier/pull/115))
5. Fixes use of `file:///xxx` protocol icon paths for Windows 8.1 ([by @gucong3000](https://github.com/mikaelbr/node-notifier/pull/118))
6. Fixes non-TTY usage and piping messages ([reported by @simensen](https://github.com/mikaelbr/node-notifier/issues/109))
7. Updates vendor terminal-notifier version to 1.6.3 ([reported by @kid-icarus](https://github.com/mikaelbr/node-notifier/pull/120))

### `v4.5.0`

#### Additions
1. Adds syntactic sugar for `notify`. Now able to just pass message:
```js
notifier.notify('My message');
```

See [#45](https://github.com/mikaelbr/node-notifier/issues/45) for more info.

#### Fixes
1. Improvements to docs and examples
2. Updates `semver` dependency to support Webpacking with Electron.

### `v4.4.0`

1. Changes to exec terminal-notifier through execFile to allow for asar-packages
2. Adds support for remote growl server
3. Adds support for win7 with electron asar-package

### `v4.3.1`
Obligatory patch fix:
1. Adds new stdin CLI options to docs

### `v4.3.0`
1. Adds support for piping messages in to CLI.
(With `node-notifier` installed as a CLI `npm i -g node-notifier`)

```shell
➜ echo "Message" | notify
➜ echo "Message" | notify -t "My Title"
➜ echo "Some message" | notify -t "My Title" -s
```

### `v4.2.3`
1. Fixed input arguments to CLI to be strings where they should be strings.

### `v4.2.2`
1. Fixed no notification when no message for the CLI. [#58](https://github.com/mikaelbr/node-notifier/pull/58)
2. Changes `which` test to be sync, avoiding some edge cases with multiple notifications.

### `v4.2.1`
1. Minor fix for docs in CLI usage

### `v4.2.0`
1. Adds CLI support.
2. Fixes Debug "HRESULT : 0xC00CE508" exception on Win8. PR [#49](https://github.com/mikaelbr/node-notifier/pull/49)

### `v4.1.2`
1. Fixes correct terminal-notifier (own fork https://github.com/mikaelbr/terminal-notifier)
   to support activate / click.

### `v4.1.1`
1. Fixes proper error codes for balloon: #42
2. Removes unused debug files: #41
3. Patches differences between subtitle for notify-send: #43
4. Updates terminal-notifier dependency (removing black borders) #44 #18

### `v4.1.0`
1. Adds support for changing host and port for Growl.

### `v4.0.3`
1. Fixes Notification center issue with multiple callback events.
2. Fixes error in source code: Fixes long-spaces to proper spaces

### `v4.0.2`
1. Fixes issue with immidiate notifu notifications (with `wait : false`)
2. Fixes issue with boolean flags for notifu.
3. Restructures directories. Making it easier to require notifiers directly.

### `v4.0.1`
1. Fixes issue with optional callback for notify-send

### `v4.0.0`
Major changes and breaking API.
1. require('node-notifier') now returns an instance with fallbackable notifications.
```js
var notifier = require('node-notifier');
notifier.notify();
```
2. Introduced a `wait` property (default `false`), to get user input for
Notification Center, Windows Toaster, Windows Balloons and Growl. Sadly not
for notify-send.
```js
var notifier = require('node-notifier');
notifier.notify({ wait: true }, function (err, response) {
     // response is response after user have interacted
     // with the notification or the notification has timed out.
});
```
3. All notification instances are now event emitters, emitting events
`click` or `timeout`. This is only applicable if `{ wait: true }`.
```js
var notifier = require('node-notifier');
notifier.on('click', function (notificationObject, options) {
     // options.someArbitraryData === 'foo'
});
notifier.notify({ wait: true, someArbitraryData: 'foo' });
```
4. WindowsToaster and NotificationCenter now can have sounds by doing `{ sound: true }`.
Default NotificationCenter sound is Bottle. Can still use define sound on
Mac:
```js
var notifier = require('node-notifier');
notifier.notify({ sound: true });
// For mac (same as sound: true on Windows 8)
notifier.notify({ sound: 'Morse' });
```

### `v3.4.0`
1. Adds Growl as priority over Balloons

### `v3.3.0`
1. Adds support for native Windows 7 and earlier (through task bar balloons)
2. Changes growl implementation. Adds better support for GNTP

### `v3.2.1`
1. Fixes support for notifications from folders with spaces on Windows.

### `v3.2.0`
1. Adds native Windows 8 support.

### `v3.1.0`
1. Adds Growl as fallback for Mac OS X pre 10.8.

### `v3.0.6`
1. Fixes typo: Changes Growl app name from `Gulp` to `Node`.

### `v3.0.5`
1. Maps common options between the different notifiers. Allowing for common usage with different notifiers.

### `v3.0.4`
1. Fixes expires for notify-send (Issue #13)

### `v3.0.2`
1. Fixes version check for Mac OS X Yosemite

### `v3.0.0`
1. Updates terminal-notifier to version 1.6.0; adding support for appIcon and contentImage
2. Removes parsing of output sent from notifier (Notification Center)
