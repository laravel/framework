{
	'targets': [
		{
			'target_name': 'inotify',
			'sources': [
				'src/bindings.cc',
				'src/node_inotify.cc'
			],
			"include_dirs" : [
				"<!(node -e \"require('nan')\")"
			]
		}
	]
}
