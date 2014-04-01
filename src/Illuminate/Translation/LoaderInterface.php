<?php namespace Illuminate\Translation; interface LoaderInterface { public function load($locale, $group, $namespace = null); public function addNamespace($namespace, $hint); }
