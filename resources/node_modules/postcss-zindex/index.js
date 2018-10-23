'use strict';

var postcss = require('postcss');

module.exports = postcss.plugin('postcss-zindex', function (opts) {
    opts = opts || {};
    return function (css) {
        var cache = require('./lib/layerCache')(opts);
        var nodes = [];
        var abort = false;
        // First pass; cache all z indexes
        css.walkDecls('z-index', function (decl) {
            // Check that no negative values exist. Rebasing is only
            // safe if all indices are positive numbers.
            if (decl.value[0] === '-') {
                abort = true;
                // Stop PostCSS iterating through the rest of the decls
                return false;
            }
            nodes.push(decl);
            cache.addValue(decl.value);
        });

        // Abort if we found any negative values
        // or there are no z-index declarations
        if (abort || !nodes.length) {
            return;
        }

        cache.optimizeValues();

        // Second pass; optimize
        nodes.forEach(function (decl) {
            // Need to coerce to string so that the
            // AST is updated correctly
            decl.value = cache.getValue(decl.value).toString();
        });
    };
});
