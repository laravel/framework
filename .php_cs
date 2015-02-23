<?php

$finder = Symfony\Component\Finder\Finder::create()
	->files()
	->in(__DIR__)
	->name('*.stub')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return Symfony\CS\Config\Config::create()
	->fixers(['-psr2'])
	->finder($finder);
