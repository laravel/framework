const run = require('./test/util').run;

// run(cmd => cmd.arg().name('qwe').end().arg().name('zxc').end().act(function(opts, args) { console.log({opts, args}); }), ['qwe', 'zxc']) // cmd and args
//     .then(res => {
//         // code
//         // stdout
//         // stderr
//         console.log(res);
//     });

run(cmd => cmd.opt().name('version').short('v').only().flag().act((opts) => { return 'aasd'; }), ['-v']) // cmd and args
    .then(res => {
        // code
        // stdout
        // stderr
        console.log(res);
    });
