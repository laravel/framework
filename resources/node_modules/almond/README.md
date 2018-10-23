#almond

A replacement [AMD](https://github.com/amdjs/amdjs-api/wiki/AMD) loader for
[RequireJS](http://requirejs.org). It provides a minimal AMD API footprint that includes [loader plugin](http://requirejs.org/docs/plugins.html) support. Only useful for built/bundled AMD modules, does not do dynamic loading.

## Why

Some developers like to use the AMD API to code modular JavaScript, but after doing an optimized build,
they do not want to include a full AMD loader like RequireJS, since they do not need all that functionality.
Some use cases, like mobile, are very sensitive to file sizes.

By including almond in the built file, there is no need for RequireJS.
almond is around **1 kilobyte** when minified with Closure Compiler and gzipped.

Since it can support certain types of loader plugin-optimized resources, it is a great
fit for a library that wants to use [text templates](http://requirejs.org/docs/api.html#text)
or [CoffeeScript](https://github.com/requirejs/require-cs) as part of
their project, but get a tiny download in one file after using the
[RequireJS Optimizer](http://requirejs.org/docs/optimization.html).

If you are building a library, the wrap=true support in the RequireJS optimizer
will wrap the optimized file in a closure, so the define/require AMD API does not
escape the file. Users of your optimized file will only see the global API you decide
to export, not the AMD API. See the usage section below for more details.

So, you get great code cleanliness with AMD and the use of powerful loader plugins
in a tiny wrapper that makes it easy for others to use your code even if they do not use AMD.

If you want a single file build output but without the module APIs included, you might want to consider [AMDclean](https://github.com/gfranko/amdclean).

## Restrictions

It is best used for libraries or apps that use AMD and:

* optimize all the modules into one file -- no dynamic code loading.
* all modules have IDs and dependency arrays in their define() calls -- the RequireJS optimizer will take care of this for you.
* only have **one** requirejs.config() or require.config() call.
* the requirejs.config/require.config call needs to be included in the build output. This is particularly important for making sure any [map config](http://requirejs.org/docs/api.html#config-map) use still works.
* do not use the `var require = {};` style of [passing config](http://requirejs.org/docs/api.html#config).
* do not use [RequireJS multiversion support/contexts](http://requirejs.org/docs/api.html#multiversion).
* do not use require.toUrl() or require.nameToUrl().
* do not use [packages/packagePaths config](http://requirejs.org/docs/api.html#packages). If you need to use packages that have a main property, [volo](https://github.com/volojs/volo) can create an adapter module so that it can work without this config. Use the `amdify add` command to add the dependency to your project.

What is supported:

* dependencies with relative IDs.
* define('id', {}) definitions.
* define(), require() and requirejs() calls.
* loader plugins that can inline their resources into optimized files, and
can access those inlined resources synchronously after the optimization pass.
The [text](http://requirejs.org/docs/api.html#text) and
[CoffeeScript](https://github.com/requirejs/require-cs) plugins are two such plugins.

## Download

[Latest release](https://github.com/requirejs/almond/raw/latest/almond.js)


## Usage

[Download the RequireJS optimizer](http://requirejs.org/docs/download.html#rjs).

[Download the current release of almond.js](https://github.com/requirejs/almond/raw/latest/almond.js).

Run the optimizer using [Node](http://nodejs.org) (also [works in Java](https://github.com/requirejs/r.js/blob/master/README.md)):

    node r.js -o baseUrl=. name=path/to/almond include=main out=main-built.js wrap=true

This assumes your project's top-level script file is called main.js and the command
above is run from the directory containing main.js. If you prefer to use a build.js build profile instead of command line arguments, [this RequireJS optimization section](http://requirejs.org/docs/optimization.html#pitfalls) has info on how to do that.

wrap=true will add this wrapper around the main-built.js contents (which will be minified by UglifyJS:

    (function () {
        //almond will be here
        //main and its nested dependencies will be here
    }());

If you do not want that wrapper, leave off the wrap=true argument.

These optimizer arguments can also be used in a build config object, so it can be used
in [runtime-generated server builds](https://github.com/requirejs/r.js/blob/master/build/tests/http/httpBuild.js).


## Triggering module execution <a name="execution"></a>

As of RequireJS 2.0 and almond 0.1, modules are only executed if they are
called by a top level require call. The data-main attribute on a script tag
for require.js counts as a top level require call.

However with almond, it does not look for a data-main attribute, and if your
main JS module does not use a top level require() or requirejs() call to
trigger module loading/execution, after a build, it may appear that the code
broke -- no modules execute.

The 2.0 RequireJS optimizer has a build config, option **insertRequire** that you
can use to specify that a require([]) call is inserted at the end of the built
file to trigger module loading. Example:

    node r.js -o baseUrl=. name=path/to/almond.js include=main insertRequire=main out=main-built.js wrap=true

or, if using a build config file:

```javascript
{
    baseUrl: '.',
    name: 'path/to/almond',
    include: ['main'],
    insertRequire: ['main'],
    out: 'main-built.js',
    wrap: true
}
```

This will result with `require(["main"]);` at the bottom of main-built.js.

## Exporting a public API

If you are making a library that is made up of AMD modules in source form, but will be built with almond into one file, and you want to export a small public
API for that library, you can use the `wrap` build config to do so. Build
config:

```javascript
{
    baseUrl: '.',
    name: 'path/to/almond',
    include: ['main'],
    out: 'lib-built.js',
    wrap: {
        startFile: 'path/to/start.frag',
        endFile: 'path/to/end.frag'
    }
}
```

Where start.frag could look like this:

```javascript
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        //Allow using this built library as an AMD module
        //in another project. That other project will only
        //see this AMD call, not the internal modules in
        //the closure below.
        define([], factory);
    } else {
        //Browser globals case. Just assign the
        //result to a property on the global.
        root.libGlobalName = factory();
    }
}(this, function () {
    //almond, and your modules will be inlined here
```

and end.frag is like this:
```javascript
    //The modules for your project will be inlined above
    //this snippet. Ask almond to synchronously require the
    //module value for 'main' here and return it as the
    //value to use for the public API for the built file.
    return require('main');
}));
```

After the build, then the built file should be structured like so:

* start.frag
* almond.js
* modules for your lib, including 'main'
* end.frag

## License

MIT

## Code of Conduct

[jQuery Foundation Code of Conduct](https://jquery.org/conduct/).

## Common errors

Explanations of common errors:

### incorrect module build, no module name

In almond 3.0.0 and earlier, this would show up as "deps is undefined", where
this line is mentioned:

    if (!deps.splice) {

In 3.0.1+ the error is explicitly: "incorrect module build, no module name".

This means that there is a define()'d module, but it is missing a name,
something that looks like this:

    define(function () {});
    //or
    define([], function () {});


when it should look like:

    define('someName', function () {});
    //or
    define('someName', [], function () {});


This is usually a sign that the tool you used to combine all the modules
together did not properly name an anonymous AMD module.

Multiple modules built into a single file **must** have names in the define calls.
Otherwise almond has no way to assign the module to a name key for use in the code.

The fix is to use a build tool that understand AMD modules and inserts the module
IDs in the build. The
[requirejs optimizer](http://requirejs.org/docs/optimization.html) is a build tool
that can do this correctly.

### x missing y

It means that module 'x' asked for module 'y', but module 'y' was not available.

This usually means that 'y' was not included in the built file that includes almond.

almond can only handle modules built in with it, it cannot dynamically load
modules from the network.


### No y

It means that a `require('y')` call was done but y was not available.

This usually means that 'y' was not included in the built file that includes almond.

almond can only handle modules built in with it, it cannot dynamically load
modules from the network.

## How to get help

* Open issues in the [issue tracker](https://github.com/requirejs/almond/issues).

## Contributing

Almond follows the
[same contribution model as requirejs](http://requirejs.org/docs/contributing.html)
and is considered a sub-project of requirejs.