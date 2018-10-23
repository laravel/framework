(function(root, factory) {
  if(typeof define === 'function' && define.amd) {
    define([], factory);
  } else {
    root.forge = factory();
  }
})(this, function() {
