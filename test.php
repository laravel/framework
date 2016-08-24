<?php

require("vendor/autoload.php");

class Test
{
	public function __construct($first, Test2 $test2, $last)
	{
	}

}

class Test2
{

	public function __construct(Test3 $test3)
	{
	}

}

class Test3
{
	public function __construct()
	{
	}
}

interface ITest
{
}

$container = new Illuminate\Container\Container();
$containerOld = new Illuminate\Container\ContainerOld();

// $containerOld->make(function() {
// 	dump(func_get_args());
// });
$containerOld->call(function() {
	dump(func_get_args());
});
