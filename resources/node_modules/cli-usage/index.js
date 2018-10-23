var TerminalRenderer = require('marked-terminal');
var marked = require('marked');
var path = require('path');
var fs = require('fs');

var argv = process.argv;
var DEFAULT_FILENAME = 'usage.md';
var possibleFile = path.join(path.dirname(argv[1]), DEFAULT_FILENAME);

marked.setOptions({
  // Define custom renderer
  renderer: new TerminalRenderer()
});

module.exports = function (str) {
  if (!isHelp()) {
    return void 0;
  }

  console.log(get(str));
  process.exit(0);
};

module.exports.get = get;

function get (str) {
  if (str && path.extname(str) === '.md') {
    return fromFile(path.resolve(path.dirname(argv[1]), str));
  }

  if (str) {
    return fromString(str);
  }

  if (fs.existsSync(possibleFile)) {
    return fromFile(possibleFile);
  }

  throw Error('Could not locate usage source. Need pass inn file or text, or have usage.md in same dir as CLI');
}

function fromFile (filename) {
  return marked(fs.readFileSync(filename).toString());
}

function fromString (text) {
  return marked(text);
}

function isHelp () {
  var without = argv.slice(2);
  return without.some(function (option) {
    return check(option, 'h') || check(option, 'help');
  });
}

function check (option, needle) {
  return option.indexOf('-' + needle) !== -1 || option.indexOf('--' + needle) !== -1;
}
