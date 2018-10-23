#!/usr/bin/env node
/* eslint-disable no-nested-ternary */
'use strict';
var meow = require('meow');
var internalIp = require('./');

var cli = meow({
	help: [
		'Usage',
		'  $ internal-ip',
		'',
		'Options',
		'  -4, --ipv4  Return the IPv4 address (default)',
		'  -6, --ipv6  Return the IPv6 address',
		'',
		'Examples',
		'  $ internal-ip',
		'  192.168.0.123',
		'  $ internal-ip --ipv6',
		'  fe80::200:f8ff:fe21:67cf'
	]
}, {
	alias: {
		4: 'ipv4',
		6: 'ipv6'
	}
});

var fn = cli.flags.ipv4 ? 'v4' : cli.flags.ipv6 ? 'v6' : 'v4';

console.log(internalIp[fn]());
