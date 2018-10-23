Hacking on forge
================

Want to hack on forge? Great! Here are a few notes:

Code
----

* In general, follow a common [Node.js Style Guide][].
* Use version X.Y.Z-dev in dev mode.
* Use version X.Y.Z for releases.

Versioning
----------

* Follow the [Semantic Versioning][] guidelines.

Release Process
---------------

* commit changes
* `$EDITOR package.json`: update to release version and remove `-dev` suffix.
* `git commit package.json -m "Release {version}."`
* `git tag {version}`
* `$EDITOR package.json`: update to next version and add `-dev` suffix.
* `git commit package.json -m "Start {next-version}."`
* `git push`
* `git push --tags`

To ensure a clean upload, use a clean updated checkout, and run the following:

* `git checkout {version}`
* `npm publish`

[Node.js Style Guide]: http://nodeguide.com/style.html
[jshint]: http://www.jshint.com/install/
[Semantic Versioning]: http://semver.org/
