#!/usr/bin/env node

'use strict';

/* eslint global-require: off, import/order: off, no-console: off */
require('../lib/polyfills');

const debug = require('debug')('webpack-dev-server');
const fs = require('fs');
const net = require('net');
const path = require('path');
const importLocal = require('import-local');
const open = require('opn');
const portfinder = require('portfinder');
const addDevServerEntrypoints = require('../lib/util/addDevServerEntrypoints');
const createDomain = require('../lib/util/createDomain'); // eslint-disable-line

// Prefer the local installation of webpack-dev-server
if (importLocal(__filename)) {
  debug('Using local install of webpack-dev-server');
  return;
}

const Server = require('../lib/Server');
const webpack = require('webpack'); // eslint-disable-line

function versionInfo() {
  return `webpack-dev-server ${require('../package.json').version}\n` +
  `webpack ${require('webpack/package.json').version}`;
}

function colorInfo(useColor, msg) {
  if (useColor) {
    // Make text blue and bold, so it *pops*
    return `\u001b[1m\u001b[34m${msg}\u001b[39m\u001b[22m`;
  }
  return msg;
}

function colorError(useColor, msg) {
  if (useColor) {
    // Make text red and bold, so it *pops*
    return `\u001b[1m\u001b[31m${msg}\u001b[39m\u001b[22m`;
  }
  return msg;
}

// eslint-disable-next-line
const defaultTo = (value, def) => value == null ? def : value;

const yargs = require('yargs')
  .usage(`${versionInfo()}\nUsage: https://webpack.js.org/configuration/dev-server/`);

require('webpack/bin/config-yargs')(yargs);

// It is important that this is done after the webpack yargs config,
// so it overrides webpack's version info.
yargs.version(versionInfo);

const ADVANCED_GROUP = 'Advanced options:';
const DISPLAY_GROUP = 'Stats options:';
const SSL_GROUP = 'SSL options:';
const CONNECTION_GROUP = 'Connection options:';
const RESPONSE_GROUP = 'Response options:';
const BASIC_GROUP = 'Basic options:';

// Taken out of yargs because we must know if
// it wasn't given by the user, in which case
// we should use portfinder.
const DEFAULT_PORT = 8080;

yargs.options({
  bonjour: {
    type: 'boolean',
    describe: 'Broadcasts the server via ZeroConf networking on start'
  },
  lazy: {
    type: 'boolean',
    describe: 'Lazy'
  },
  inline: {
    type: 'boolean',
    default: true,
    describe: 'Inline mode (set to false to disable including client scripts like livereload)'
  },
  progress: {
    type: 'boolean',
    describe: 'Print compilation progress in percentage',
    group: BASIC_GROUP
  },
  'hot-only': {
    type: 'boolean',
    describe: 'Do not refresh page if HMR fails',
    group: ADVANCED_GROUP
  },
  stdin: {
    type: 'boolean',
    describe: 'close when stdin ends'
  },
  open: {
    type: 'string',
    describe: 'Open the default browser, or optionally specify a browser name'
  },
  useLocalIp: {
    type: 'boolean',
    describe: 'Open default browser with local IP'
  },
  'open-page': {
    type: 'string',
    describe: 'Open default browser with the specified page',
    requiresArg: true
  },
  color: {
    type: 'boolean',
    alias: 'colors',
    default: function supportsColor() {
      return require('supports-color');
    },
    group: DISPLAY_GROUP,
    describe: 'Enables/Disables colors on the console'
  },
  info: {
    type: 'boolean',
    group: DISPLAY_GROUP,
    default: true,
    describe: 'Info'
  },
  quiet: {
    type: 'boolean',
    group: DISPLAY_GROUP,
    describe: 'Quiet'
  },
  'client-log-level': {
    type: 'string',
    group: DISPLAY_GROUP,
    default: 'info',
    describe: 'Log level in the browser (info, warning, error or none)'
  },
  https: {
    type: 'boolean',
    group: SSL_GROUP,
    describe: 'HTTPS'
  },
  key: {
    type: 'string',
    describe: 'Path to a SSL key.',
    group: SSL_GROUP
  },
  cert: {
    type: 'string',
    describe: 'Path to a SSL certificate.',
    group: SSL_GROUP
  },
  cacert: {
    type: 'string',
    describe: 'Path to a SSL CA certificate.',
    group: SSL_GROUP
  },
  pfx: {
    type: 'string',
    describe: 'Path to a SSL pfx file.',
    group: SSL_GROUP
  },
  'pfx-passphrase': {
    type: 'string',
    describe: 'Passphrase for pfx file.',
    group: SSL_GROUP
  },
  'content-base': {
    type: 'string',
    describe: 'A directory or URL to serve HTML content from.',
    group: RESPONSE_GROUP
  },
  'watch-content-base': {
    type: 'boolean',
    describe: 'Enable live-reloading of the content-base.',
    group: RESPONSE_GROUP
  },
  'history-api-fallback': {
    type: 'boolean',
    describe: 'Fallback to /index.html for Single Page Applications.',
    group: RESPONSE_GROUP
  },
  compress: {
    type: 'boolean',
    describe: 'Enable gzip compression',
    group: RESPONSE_GROUP
  },
  port: {
    describe: 'The port',
    group: CONNECTION_GROUP
  },
  'disable-host-check': {
    type: 'boolean',
    describe: 'Will not check the host',
    group: CONNECTION_GROUP
  },
  socket: {
    type: 'String',
    describe: 'Socket to listen',
    group: CONNECTION_GROUP
  },
  public: {
    type: 'string',
    describe: 'The public hostname/ip address of the server',
    group: CONNECTION_GROUP
  },
  host: {
    type: 'string',
    default: 'localhost',
    describe: 'The hostname/ip address the server will bind to',
    group: CONNECTION_GROUP
  },
  'allowed-hosts': {
    type: 'string',
    describe: 'A comma-delimited string of hosts that are allowed to access the dev server',
    group: CONNECTION_GROUP
  }
});

const argv = yargs.argv;
const wpOpt = require('webpack/bin/convert-argv')(yargs, argv, {
  outputFilename: '/bundle.js'
});

function processOptions(webpackOptions) {
  // process Promise
  if (typeof webpackOptions.then === 'function') {
    webpackOptions.then(processOptions).catch((err) => {
      console.error(err.stack || err);
      process.exit(); // eslint-disable-line
    });
    return;
  }

  const firstWpOpt = Array.isArray(webpackOptions) ? webpackOptions[0] : webpackOptions;

  const options = webpackOptions.devServer || firstWpOpt.devServer || {};

  if (argv.bonjour) { options.bonjour = true; }

  if (argv.host !== 'localhost' || !options.host) { options.host = argv.host; }

  if (argv['allowed-hosts']) { options.allowedHosts = argv['allowed-hosts'].split(','); }

  if (argv.public) { options.public = argv.public; }

  if (argv.socket) { options.socket = argv.socket; }

  if (!options.publicPath) {
    // eslint-disable-next-line
    options.publicPath = firstWpOpt.output && firstWpOpt.output.publicPath || '';
    if (!/^(https?:)?\/\//.test(options.publicPath) && options.publicPath[0] !== '/') {
      options.publicPath = `/${options.publicPath}`;
    }
  }

  if (!options.filename) { options.filename = firstWpOpt.output && firstWpOpt.output.filename; }

  if (!options.watchOptions) { options.watchOptions = firstWpOpt.watchOptions; }

  if (argv.stdin) {
    process.stdin.on('end', () => {
      process.exit(0); // eslint-disable-line no-process-exit
    });
    process.stdin.resume();
  }

  if (!options.hot) { options.hot = argv.hot; }

  if (!options.hotOnly) { options.hotOnly = argv['hot-only']; }

  if (!options.clientLogLevel) { options.clientLogLevel = argv['client-log-level']; }

  // eslint-disable-next-line
  if (options.contentBase === undefined) {
    if (argv['content-base']) {
      options.contentBase = argv['content-base'];
      if (Array.isArray(options.contentBase)) {
        options.contentBase = options.contentBase.map(val => path.resolve(val));
      } else if (/^[0-9]$/.test(options.contentBase)) { options.contentBase = +options.contentBase; } else if (!/^(https?:)?\/\//.test(options.contentBase)) { options.contentBase = path.resolve(options.contentBase); }
      // It is possible to disable the contentBase by using `--no-content-base`, which results in arg["content-base"] = false
    } else if (argv['content-base'] === false) {
      options.contentBase = false;
    }
  }

  if (argv['watch-content-base']) { options.watchContentBase = true; }

  if (!options.stats) {
    options.stats = {
      cached: false,
      cachedAssets: false
    };
  }

  if (typeof options.stats === 'object' && typeof options.stats.colors === 'undefined') { options.stats.colors = argv.color; }

  if (argv.lazy) { options.lazy = true; }

  if (!argv.info) { options.noInfo = true; }

  if (argv.quiet) { options.quiet = true; }

  if (argv.https) { options.https = true; }

  if (argv.cert) { options.cert = fs.readFileSync(path.resolve(argv.cert)); }

  if (argv.key) { options.key = fs.readFileSync(path.resolve(argv.key)); }

  if (argv.cacert) { options.ca = fs.readFileSync(path.resolve(argv.cacert)); }

  if (argv.pfx) { options.pfx = fs.readFileSync(path.resolve(argv.pfx)); }

  if (argv['pfx-passphrase']) { options.pfxPassphrase = argv['pfx-passphrase']; }

  if (argv.inline === false) { options.inline = false; }

  if (argv['history-api-fallback']) { options.historyApiFallback = true; }

  if (argv.compress) { options.compress = true; }

  if (argv['disable-host-check']) { options.disableHostCheck = true; }

  if (argv['open-page']) {
    options.open = true;
    options.openPage = argv['open-page'];
  }

  if (typeof argv.open !== 'undefined') {
    options.open = argv.open !== '' ? argv.open : true;
  }

  if (options.open && !options.openPage) { options.openPage = ''; }

  if (argv.useLocalIp) { options.useLocalIp = true; }

  // Kind of weird, but ensures prior behavior isn't broken in cases
  // that wouldn't throw errors. E.g. both argv.port and options.port
  // were specified, but since argv.port is 8080, options.port will be
  // tried first instead.
  options.port = argv.port === DEFAULT_PORT ? defaultTo(options.port, argv.port) : defaultTo(argv.port, options.port);

  if (options.port != null) {
    startDevServer(webpackOptions, options);
    return;
  }

  portfinder.basePort = DEFAULT_PORT;
  portfinder.getPort((err, port) => {
    if (err) throw err;
    options.port = port;
    startDevServer(webpackOptions, options);
  });
}

function startDevServer(webpackOptions, options) {
  addDevServerEntrypoints(webpackOptions, options);

  let compiler;
  try {
    compiler = webpack(webpackOptions);
  } catch (e) {
    if (e instanceof webpack.WebpackOptionsValidationError) {
      console.error(colorError(options.stats.colors, e.message));
      process.exit(1); // eslint-disable-line
    }
    throw e;
  }

  if (argv.progress) {
    compiler.apply(new webpack.ProgressPlugin({
      profile: argv.profile
    }));
  }

  const suffix = (options.inline !== false || options.lazy === true ? '/' : '/webpack-dev-server/');

  let server;
  try {
    server = new Server(compiler, options);
  } catch (e) {
    const OptionsValidationError = require('../lib/OptionsValidationError');
    if (e instanceof OptionsValidationError) {
      console.error(colorError(options.stats.colors, e.message));
          process.exit(1); // eslint-disable-line
    }
    throw e;
  }

  ['SIGINT', 'SIGTERM'].forEach((sig) => {
    process.on(sig, () => {
      server.close(() => {
        process.exit(); // eslint-disable-line no-process-exit
      });
    });
  });

  if (options.socket) {
    server.listeningApp.on('error', (e) => {
      if (e.code === 'EADDRINUSE') {
        const clientSocket = new net.Socket();
        clientSocket.on('error', (clientError) => {
          if (clientError.code === 'ECONNREFUSED') {
            // No other server listening on this socket so it can be safely removed
            fs.unlinkSync(options.socket);
            server.listen(options.socket, options.host, (err) => {
              if (err) throw err;
            });
          }
        });
        clientSocket.connect({ path: options.socket }, () => {
          throw new Error('This socket is already used');
        });
      }
    });
    server.listen(options.socket, options.host, (err) => {
      if (err) throw err;
      // chmod 666 (rw rw rw)
      const READ_WRITE = 438;
      fs.chmod(options.socket, READ_WRITE, (fsError) => {
        if (fsError) throw fsError;

        const uri = createDomain(options, server.listeningApp) + suffix;
        reportReadiness(uri, options);
      });
    });
  } else {
    server.listen(options.port, options.host, (err) => {
      if (err) throw err;
      if (options.bonjour) broadcastZeroconf(options);

      const uri = createDomain(options, server.listeningApp) + suffix;
      reportReadiness(uri, options);
    });
  }
}

function reportReadiness(uri, options) {
  const useColor = argv.color;
  const contentBase = Array.isArray(options.contentBase) ? options.contentBase.join(', ') : options.contentBase;

  if (!options.quiet) {
    let startSentence = `Project is running at ${colorInfo(useColor, uri)}`;
    if (options.socket) {
      startSentence = `Listening to socket at ${colorInfo(useColor, options.socket)}`;
    }
    console.log((argv.progress ? '\n' : '') + startSentence);

    console.log(`webpack output is served from ${colorInfo(useColor, options.publicPath)}`);

    if (contentBase) { console.log(`Content not from webpack is served from ${colorInfo(useColor, contentBase)}`); }

    if (options.historyApiFallback) { console.log(`404s will fallback to ${colorInfo(useColor, options.historyApiFallback.index || '/index.html')}`); }

    if (options.bonjour) { console.log('Broadcasting "http" with subtype of "webpack" via ZeroConf DNS (Bonjour)'); }
  }

  if (options.open) {
    let openOptions = {};
    let openMessage = 'Unable to open browser';

    if (typeof options.open === 'string') {
      openOptions = { app: options.open };
      openMessage += `: ${options.open}`;
    }

    open(uri + (options.openPage || ''), openOptions).catch(() => {
      console.log(`${openMessage}. If you are running in a headless environment, please do not use the open flag.`);
    });
  }
}

function broadcastZeroconf(options) {
  const bonjour = require('bonjour')();
  bonjour.publish({
    name: 'Webpack Dev Server',
    port: options.port,
    type: 'http',
    subtypes: ['webpack']
  });
  process.on('exit', () => {
    bonjour.unpublishAll(() => {
      bonjour.destroy();
    });
  });
}

processOptions(wpOpt);
