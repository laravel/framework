'use strict';
const execBuffer = require('exec-buffer');
const isJpg = require('is-jpg');
const mozjpeg = require('mozjpeg');

module.exports = opts => buf => {
	opts = Object.assign({}, opts);

	if (!Buffer.isBuffer(buf)) {
		return Promise.reject(new TypeError('Expected a buffer'));
	}

	if (!isJpg(buf)) {
		return Promise.resolve(buf);
	}

	const args = ['-outfile', execBuffer.output];

	if (typeof opts.quality !== 'undefined') {
		args.push('-quality', opts.quality);
	}

	if (opts.progressive === false) {
		args.push('-baseline');
	}

	if (opts.targa) {
		args.push('-targa');
	}

	if (opts.revert) {
		args.push('-revert');
	}

	if (opts.fastcrush) {
		args.push('-fastcrush');
	}

	if (typeof opts.dcScanOpt !== 'undefined') {
		args.push('-dc-scan-opt', opts.dcScanOpt);
	}

	if (opts.notrellis) {
		args.push('-notrellis');
	}

	if (opts.notrellisDC) {
		args.push('-notrellis-dc');
	}

	if (opts.tune) {
		args.push(`-tune-${opts.tune}`);
	}

	if (opts.noovershoot) {
		args.push('-noovershoot');
	}

	if (opts.arithmetic) {
		args.push('-arithmetic');
	}

	if (opts.dct) {
		args.push('-dct', opts.dct);
	}

	if (typeof opts.quantTable !== 'undefined') {
		args.push('-quant-table', opts.quantTable);
	}

	if (opts.smooth) {
		args.push('-smooth', opts.smooth);
	}

	if (opts.maxmemory) {
		args.push('-maxmemory', opts.maxmemory);
	}

	args.push(execBuffer.input);

	return execBuffer({
		input: buf,
		bin: mozjpeg,
		args
	}).catch(err => {
		err.message = err.stderr || err.message;
		throw err;
	});
};
