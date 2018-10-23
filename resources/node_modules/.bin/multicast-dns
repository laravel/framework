#!/usr/bin/env node

var mdns = require('multicast-dns')()
var path = require('path')

if (process.argv.length < 3) {
  console.error('Usage: %s <hostname>', path.basename(process.argv[1]))
  process.exit(1)
}
var hostname = process.argv[2]

mdns.on('response', function (response) {
  response.answers.forEach(function (answer) {
    if (answer.name === hostname) {
      console.log(answer.data)
      process.exit()
    }
  })
})

mdns.query(hostname, 'A')

// Give responses 3 seconds to respond
setTimeout(function () {
  console.error('Hostname not found')
  process.exit(1)
}, 3000)
