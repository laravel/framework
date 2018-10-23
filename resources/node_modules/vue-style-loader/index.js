/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
  Modified by Evan You @yyx990803
*/
var loaderUtils = require('loader-utils')
var path = require('path')
var hash = require('hash-sum')

module.exports = function () {}

module.exports.pitch = function (remainingRequest) {
  if (this.cacheable) this.cacheable()

  var isServer = this.target === 'node'
  var isProduction = this.minimize || process.env.NODE_ENV === 'production'
  var addStylesClientPath = loaderUtils.stringifyRequest(this, '!' + path.join(__dirname, 'lib/addStylesClient.js'))
  var addStylesServerPath = loaderUtils.stringifyRequest(this, '!' + path.join(__dirname, 'lib/addStylesServer.js'))

  var request = loaderUtils.stringifyRequest(this, '!!' + remainingRequest)
  var id = JSON.stringify(hash(request))

  // direct css import from js --> direct (how does this work when inside an async chunk? ...just don't do it)
  // css import from vue file --> component lifecycle linked
  // style embedded in vue file --> component lifecycle linked
  var isVue = /"vue":true/.test(remainingRequest)

  var shared = [
    '// style-loader: Adds some css to the DOM by adding a <style> tag',
    '',
    '// load the styles',
    'var content = require(' + request + ');',
    // content list format is [id, css, media, sourceMap]
    "if(typeof content === 'string') content = [[module.id, content, '']];",
    'if(content.locals) module.exports = content.locals;'
  ]

  if (!isServer) {
    // on the client: dynamic inject + hot-reload
    var code = [
      '// add the styles to the DOM',
      'var update = require(' + addStylesClientPath + ')(' + id + ', content, ' + isProduction + ');'
    ]
    if (!isProduction) {
      code = code.concat([
        '// Hot Module Replacement',
        'if(module.hot) {',
        ' // When the styles change, update the <style> tags',
        ' if(!content.locals) {',
        '   module.hot.accept(' + request + ', function() {',
        '     var newContent = require(' + request + ');',
        "     if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];",
        '     update(newContent);',
        '   });',
        ' }',
        ' // When the module is disposed, remove the <style> tags',
        ' module.hot.dispose(function() { update(); });',
        '}'
      ])
    }
    return shared.concat(code).join('\n')
  } else {
    // on the server: attach to Vue SSR context
    if (isVue) {
      // inside *.vue file: expose a function so it can be called in
      // component's lifecycle hooks
      return shared.concat([
        '// add CSS to SSR context',
        'var add = require(' + addStylesServerPath + ')',
        'module.exports.__inject__ = function (context) {',
        '  add(' + id + ', content, ' + isProduction + ', context)',
        '};'
      ]).join('\n')
    } else {
      // normal import
      return shared.concat([
        'require(' + addStylesServerPath + ')(' + id + ', content, ' + isProduction + ')'
      ]).join('\n')
    }
  }
}
