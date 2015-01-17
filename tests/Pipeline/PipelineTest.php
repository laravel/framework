<?php

use Illuminate\Pipeline\Pipeline;

class PipelineTest extends PHPUnit_Framework_TestCase {

	public function testPipelineBasicUsage()
	{
		$pipeTwo = function($piped, $next) {
			$_SERVER['__test.pipe.two'] = $piped;
			return $next($piped);
		};

		$result = (new Pipeline(new Illuminate\Container\Container))
					->send('foo')
					->through(['PipelineTestPipeOne', $pipeTwo])
					->then(function($piped) {
						return $piped;
					});

		$this->assertEquals('foo', $result);
		$this->assertEquals('foo', $_SERVER['__test.pipe.one']);
		$this->assertEquals('foo', $_SERVER['__test.pipe.two']);

		unset($_SERVER['__test.pipe.one']);
		unset($_SERVER['__test.pipe.two']);
	}

}

class PipelineTestPipeOne {
	public function handle($piped, $next) {
		$_SERVER['__test.pipe.one'] = $piped;
		return $next($piped);
	}
}
