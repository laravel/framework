#!/usr/bin/env php
<?php

$sourceDirectory = __DIR__.'/../src/Illuminate';
$readmeTemplate  = __DIR__.'/illuminate-readme.md';

$dirs = glob($sourceDirectory.'/*', GLOB_ONLYDIR);

foreach ($dirs as $dir)
{
	// extract part of the folder name
	$parts = explode('/', $dir);

	// set variables
	list($vendor, $name) = array_slice($parts, - 2);
	$package = strtolower($vendor.'/'.$name);
	$readme  = $sourceDirectory.'/'.$name.'/readme.md';

	// get template
	$content = file_get_contents($readmeTemplate);

	// replace variables in template
	$replacements = [
		'@name'    => $name,
		'@vendor'  => $vendor,
		'@package' => $package,
	];
	$output       = str_replace(array_keys($replacements), array_values($replacements), $content);

	// write package readme
	file_put_contents($readme, $output);
}

