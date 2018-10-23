var assert = require('assert');
var path = require('path');
var usage = require('./');
var fs = require('fs');

var origFs = fs.readFileSync;
var origMarked = fs.origMarked;

describe('cli-usage', function () {
  afterEach(function() {
    fs.readFileSync = origFs;
  });

  describe('get', function () {
    it('should get compiled markdown from file input', function () {
      var expected = 'expected';
      fs.readFileSync = function (filename) {
        assert.equal(path.basename(filename), 'file.md');
        return expected;
      };
      assert.ok(usage.get('file.md').indexOf(expected) !== -1);
    });
  });
});
