<?php

/*

Breacking changes :

-Container::call cant't anymore take a default method (3rd param)
-Container::call doesn't resolve from container
-After resolving callbacks no longer check the resolved subject
-Closure parameters are not passed through an array
-Closure call from Container::make will have the container as first parameter but it's not
the case with Container::call

Features :

-Container::call and Container::make have the same behaviour except that Container::call
resolve from outside the container and Container::make resolve from inside the container
-Container::call and Container::make supprots : closure, "class@method",
[object, "method"], "object::method" and "class" notations
-Contextual binding and extenders supports all types
-If you give a closure as a parameter it will be called and his return value will be take
as parameter

 */

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


$container = new Illuminate\Container\Container();
// $container = new Illuminate\Container\ContainerOld();

$container->bind("oauth_scopes", [
	"user-email" => "User email",
	"user-username" => "User username"
]);

dump($container->make("oauth_scopes"));
