({
  name: 'node_modules/almond/almond',
  include: ['js/forge'],
  out: 'js/forge.min.js',
  wrap: {
    startFile: 'start.frag',
    endFile: 'end.frag'
  },
  preserveLicenseComments: false
})
