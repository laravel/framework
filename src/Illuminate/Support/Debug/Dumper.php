<?php namespace Illuminate\Support\Debug;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class Dumper {

	/**
	 * Var dump a value elegantly.
	 *
	 * @param  mixed  $value
	 * @return string
	 */
	public function dump($value)
	{
		$cloner = new VarCloner();
		$dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();
		$dumper->dump($cloner->cloneVar($value));
	}
}
