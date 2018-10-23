# Changelog

## 2.2.2

- Fixed: `options.configPath` and `--config` flag are respected.

## 2.2.0-1

- 2.2.0 included a number of improvements but somehow broke stylelint. The changes were reverted in 2.2.1, to be restored later.

## 2.1.3

- Licensing improvement: switched from `json-parse-helpfulerror` to `parse-json`.

## 2.1.2

- Fixed: bug where an `ENOENT` error would be thrown is `searchPath` referenced a non-existent file.
- Fixed: JSON parsing errors in Node v7.

## 2.1.1

- Fixed: swapped `graceful-fs` for regular `fs`, fixing a garbage collection problem.

## 2.1.0

- Added: Node 0.12 support.

## 2.0.2

- Fixed: Node version specified in `package.json`.

## 2.0.1

- Fixed: no more infinite loop in Windows.

## 2.0.0

- Changed: module now creates cosmiconfig instances with `load` methods (see README).
- Added: caching (enabled by the change above).
- Removed: support for Node versions <4.

## 1.1.0

- Add `rcExtensions` option.

## 1.0.2

- Fix handling of `require()`'s within JS module configs.

## 1.0.1

- Switch Promise implementation to pinkie-promise.

## 1.0.0

- Initial release.
