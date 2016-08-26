<?php

/*

Breacking changes :

-Container::call cant't anymore take a default method (3rd param)
-Container::call doesn't resolve from container
-After resolving callbacks no longer check the resolved subject

Features :

-Container::call and Container::make have the same behaviour except that Container::call
resolve from outside the container and Container::make resolve from inside the container
-Container::call and Container::make supprots : closure, "class@method",
[object, "method"], "object::method" and "class" notations
-Contextual binding support all types

 */

require("vendor/autoload.php");

class Test
{
	public function __construct(Test2 $test2)
	{
	}

}

class Test2
{

	public function __construct(Test3 $test3)
	{
		dump($test3);
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

$container = new Illuminate\Container\Container();
// $container = new Illuminate\Container\ContainerOld();

$container->when(Test2::class)->needs(Test3::class)->give(Test3::class);

$container->make(Test2::class);

