<?php

require("vendor/autoload.php");

class Test
{

	public function __construct($first, Test2 $test2, $last)
	{
		var_dump($first);
		var_dump($last);
	}

}

interface TestInterface
{
}

class Test2
{

	public function __construct()
	{
	}

	public function Antoine(Test $test)
	{
		return "OK";
	}

}

$container = new Illuminate\Container\Container();

$ret = $container->resolve(function() {
    return func_get_args();
});

var_dump($ret);
