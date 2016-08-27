<?php
require("vendor/autoload.php");

class Test
{
	public function __construct(Test2 $test2)
	{
	}

	public function test()
	{
		return "foo";
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

function testPerf()
{
	$container = new Illuminate\Container\Container();
	// $container = new Illuminate\Container\ContainerOld();

	// $container->singleton(Test::Class);

	for ($i=0; $i < 10000; $i++) {
		$container->make(Test::class);
	}
}


