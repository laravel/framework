### Version 0.3.1 (2014-08-16) ###

- Improved: Updated the source-map-url dependency to 0.3.0.


### Version 0.3.0 (2014-07-02) ###

- Removed: Argument checking. It’s not worth it. (Possibly
  backwards-incompatible change.)
- Added: The `sourceRoot` property of source maps may now be ignored, which can
  be useful when resolving sources outside of the browser.
- Added: It is now possible to resolve only the URLs of sources, without
  reading them.


### Version 0.2.0 (2014-06-22) ###

- Changed: The result of `resolveSources` is now an object, not an array. The
  old result array is available in the `sourcesContent` property.
  (Backwards-incompatible change.)
- Changed: `sources` has been renamed to `sourcesContent` in the result object
  of `resolve`. (Backwards-incompatible change.)
- Added: `resolveSources` now also returns all sources fully resolved, in the
  `sourcesResolved` property.
- Added: The result object of `resolve` now contains the `sourcesResolved`
  property from `resolveSources`.


### Version 0.1.4 (2014-06-16) ###

- Fixed: `sourcesContent` was mis-typed as `sourceContents`, which meant that
  the `sourcesContent` property of source maps never was used when resolving
  sources.


### Version 0.1.3 (2014-05-06) ###

- Only documentation and meta-data changes.


### Version 0.1.2 (2014-03-23) ###

- Improved: Source maps starting with `)]}'` are now parsed correctly. The spec
  allows source maps to start with that character sequence to prevent XSSI
  attacks.


### Version 0.1.1 (2014-03-06) ###

- Improved: Make sourceRoot resolving more sensible.

  A source root such as `/scripts/subdir` is now treated as `/scripts/subdir/`
  — that is, as a directory called “subdir”, not a file called “subdir”.
  Pointing to a file as source root does not makes sense.



### Version 0.1.0 (2014-03-03) ###

- Initial release.
