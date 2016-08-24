<?php

require("vendor/autoload.php");

class Test
{

	public function __construct($first, Test2 $test2, $last)
	{
		dump($first);
		dump($last);
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
		dump("CONSTRUCT");
	}

	public static function test()
	{
	}

	public function test2()
	{
	}

}

interface ITest
{
}

$container = new Illuminate\Container\Container();
$containerOld = new Illuminate\Container\ContainerOld();

// $container->make(Test2::class);

// $container->when("Test2")->needs("Test")->give(100);

dump(method_exists("Test3::test"));
